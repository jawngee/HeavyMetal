<?
/**
*
* Copyright (c) 2009, Jon Gilkison and Trunk Archive Inc.
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*
* - Redistributions of source code must retain the above copyright notice,
*   this list of conditions and the following disclaimer.
* - Redistributions in binary form must reproduce the above copyright
*   notice, this list of conditions and the following disclaimer in the
*   documentation and/or other materials provided with the distribution.
*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
* AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
* IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
* ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
* LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
* CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
* SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
* INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
* CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
* ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
* POSSIBILITY OF SUCH DAMAGE.
*
* This is a modified BSD license (the third clause has been removed).
* The BSD license may be found here:
* 
* http://www.opensource.org/licenses/bsd-license.php
*
*/

uses('system.data.database');
uses('system.data.relation');
uses('system.data.field');
uses('system.data.validator');
uses('system.data.filter');
uses("system.app.dynamic_object");

/**
 * Exception thrown by model class
 */
class ModelException extends Exception {}


/**
 * Base model class
 */
class Model implements ArrayAccess
{
    /**
     * The model is in a new state, meaning it is not in the DB
     */
    const STATE_NEW=0;

    /**
     * The model is in a valid state, meaning it has been loaded from the DB
     */
    const STATE_VALID=1;

    /**
     * The model is in a deleted state, meaning it has been deleted from the DB
     */
    const STATE_DELETED=2;

    /** The model is in a dirty state, meaning a value has changed. */
    const STATE_DIRTY=3;

    public $model_state=Model::STATE_NEW;		/** Current state of the model */
    public $db=null;					/** Reference to the database connection */
    public $table_name=null;			/** Name of the table 'shema.name' */
    public $validators=null;			/** List of validators */
    public $fields=null;				/** List of fields */
    public $primary_key=null;			/** Name of the primary key */
    public $primary_key_value=null;		/** Primary key value */
    public $database='default';	/** Name of the database */
    public $related=array();			/** Related tables */
    public $readonly=false;				/** Is this a read-only model? */

    /**
     * Creates an instance of a model
     */
    public static function Instance($model,$id=null,$fields=null,$db=null,$row=null, $cacheable=false)
    {
		uses("model.$model");
	
		$class=str_replace('_','',array_pop(explode('.',$model)));
		$instance=new $class($id,$fields,$db,$row,$cacheable);
	
		return $instance;
    }
	
    /**
     * Called when an object is constructed for special case setup or
     * setting up business rules.		
     *
     * @return void
     */
    protected function describe()
    {

    }
	
    /**
     * Constructor
     * 
     * @param  mixed $id ID of object, can be null.
     * @param  array $fields Array of Field objects to bind properties to, can be null
     * @param  Database $db Database connection, can be null
     * @param  array	$row Database row to bind to, can be null
     */
    public function __construct($id=null,$fields=null,$db=null,$row=null)
    {
	// If a db object has been passed in, assign it.
	if (isset($db))
		$this->db=$db;
	else if (isset($this->database))
		$this->db=$this->get_db();

	// give the class a chance to describe itself for special case
	// or business rule setup
	$this->describe();
		
		// CRUD:  pre_read hook
        if (isset($id) || isset($fields) || isset($row))

	$this->pre_read();
        
	if (isset($id))
	{
		$this->primary_key_value=$id;
		$this->reload();
	}
	else if (isset($fields))  // someone passed in fields to assign
	{
		// loop through and assign values
		foreach($fields as $key=>$value)
		$this->fields[$key]=$value;
	}
	else if (isset($row))	// someone passsed in a db row for us to bind to.
		$this->bind($row);
        
		// CRUD:  post_read hook
        if (isset($id) || isset($fields) || isset($row))
            $this->post_read();
    }
    
    /**
     * returns an instance of the model's database,
     * override in base classes where you have special needs.
     */
    protected function get_db()
    {
    	return Database::Get($this->database);
    }

    /**
     * Reloads the model from the database
     */
    public function reload()
    {
	// find by id
	$result=$this->db->fetch_row($this->table_name, $this->primary_key, $this->primary_key_value);

	// no result?  exit ...
	if (!$result)
	    throw new Exception("Result $result");

	// create our fields array if it doesn't yet exist.
	//$this->fields=array();

	// loop through the results and assign the values
	foreach($result as $key=>$value)
	    if (!is_numeric($key))
	    {
		if (isset($this->fields[$key]))
		{
		    if ($this->fields[$key]->type==Field::MULTI)
			$this->fields[$key]->value=$this->db->parse_array($value);
		    else if ($this->fields[$key]->type==Field::BOOLEAN)
			$this->fields[$key]->value=($value=='t') ? true : ($value===true) ? true : false;
		    else
			$this->fields[$key]->value=$value;
		}
		else
		    $this->fields[$key]=new Field($key,Field::STRING,1000,'Undocumented column',true,$value);
	    }

	$this->model_state=Model::STATE_VALID;
    }
	
	
	/**
	* Binds the object to a row from a recordset
	* 
     * @param  array	$row Database row to bind to
	*/
	public function bind($row)
	{
  		// make sure our fields array exists
		if (!isset($this->fields))				
			$this->fields=array();
		
		// loop through and assign values
		foreach($row as $key=>$value)
			if (!is_numeric($key))
			{
				if ($key==$this->primary_key)
					$this->primary_key_value=$value;
				else  if (isset($this->fields[$key]))
				{
					if ($this->fields[$key]->type==Field::MULTI)
						$this->fields[$key]->value=$this->db->parse_array($value);
					else if ($this->fields[$key]->type==Field::BOOLEAN)
						$this->fields[$key]->value=($value=='t') ? true : ($value===true) ? true : false;
					else if ($this->fields[$key]->type==Field::OBJECT)
					{
						if (trim($value)=='')
							$this->fields[$key]->value=new DynamicObject();
						else
							$this->fields[$key]->value=new DynamicObject($value);
					}
					else
						$this->fields[$key]->value=$value;
				}
				else
					$this->fields[$key]=new Field($key,Field::STRING,1000,'',true,$value);
			}

	  	$this->model_state=Model::STATE_VALID;
	}
	
    
	/**
	*  Callback method for getting a property
	*/
   	function &__get($prop_name)
   	{
   		$val=null;
   		
   		if ($prop_name==$this->primary_key)
   			$val=$this->primary_key_value;
       	else if (isset($this->fields[$prop_name]))
           $val=$this->fields[$prop_name]->value;
		else if (isset($this->related[$prop_name]))
		{
			$val=$this->related[$prop_name]->get_value();
		}
		
		return $val;
   	}

   	/**
   	*  Callback method for setting a property
   	*/
   	function __set($prop_name, $prop_value)
   	{
   		if ($this->readonly)
   			return false;
   			
   		if ($prop_name==$this->primary_key)
   		{
   			if ($this->model_state!=Model::STATE_NEW)
	   			$this->model_state=Model::STATE_DIRTY;
   			$this->primary_key_value=$prop_value;
   		}
		else if (isset($this->fields[$prop_name]))
		{
   			if ($this->model_state!=Model::STATE_NEW)
	   			$this->model_state=Model::STATE_DIRTY;
       		$this->fields[$prop_name]->value = $prop_value;
       		$this->fields[$prop_name]->dirty = true;
		}
       	else
       		return false;
       
       return true;
   	}


   	/**
   	 * This method just decorates the model with transient fields used only in this request.
   	 * Transient fields aren't expected to be stored... so they aren't set to dirty
   	 * 
   	 * @param mixed $prop_name the name of the property being added
   	 * @param mixed $prop_value the value of the property being added
   	 */
   	function add($prop_name, $prop_value)
   	{
   		$this->fields[$prop_name]=new Field($prop_name,Field::TEXT,0,'',false,$prop_value, Field::COMPARE_EQUALS, true/*transient*/);
   	}

   	/***
   	* Recursive validation method
   	*/
	private function dovalidate(&$result, $field, $validator)
	{
   		if (is_array($validator))
   		{
   			foreach($validator as $vdator)
   				$this->dovalidate($result,$field,$vdator);
		}
		else 
		{
			if (!$validator->validate($this,$field,$this->fields))
				$result[count($result)]=new ValidationError($field,$validator->_message,$validator->_data);
		}
	}
	       	
   	/**
   	*  validates the object's data, returns true if validates, otherwise returns an associative array of errors
   	*/
   	function validate()
   	{
   		if (!isset($this->validators))
   			return true;
   			
   		$result=array();
   			
   		foreach($this->validators as $field => $validator)
   			$this->dovalidate($result,$field,$validator);
   			
   		if (count($result)==0)
   			return true;
   		else
   			return $result;
	}
   	
   	protected function pre_save()
   	{
   	}
   	
   	protected function post_save()
   	{
   	}
   	
   	
   	/**
   	 * CRUD hooks
   	 * 
   	 * These work like DB triggers:
   	 * The pre_create and pre_update methods have the option to modify the field array that gets persisted
   	 * The post_update methods receives field data so they can check what was updated.
   	 */
    protected function pre_create(&$new_fields)
   	{
   	}
   	
   	protected function post_create()
   	{
   	}
    
    protected function pre_read()
   	{
   	}
   	
   	protected function post_read()
   	{
   	}
    
   	protected function pre_update(&$new_fields)
   	{
   	}
   	
   	protected function post_update($old_fields)
   	{
   	}
   	
   	protected function pre_delete()
	{
	}
	
	protected function post_delete($old_fields)
	{
	} 
   	
   	/**
   	* called to save this object to the DB store
   	*/
   	public function save($validate_model=true)
   	{
   		if ($this->readonly)
   			throw new ModelException('This is a read-only model, you cannot save, update or delete.');
 		
 		$this->pre_save();

   		$fields=array();
   		foreach($this->fields as $key => $value)
//   			if ((($value->value!=null) || ($value->notnull)) || ($value->type==Field::BOOLEAN) || ($value->type==Field::STRING) || ($value->type==Field::TEXT))
			if (($value->dirty) || ($value->notnull) || ($value->type==Field::OBJECT))
			{
				if ($this->model_state==Model::STATE_VALID)
					$this->model_state=Model::STATE_DIRTY;

				if ($value->type==Field::BOOLEAN)
					$fields[$key]=($value->value==true) ? 't':'f';
				else if ($value->type==Field::MULTI)
					$fields[$key]=$this->db->collapse_array($value->value);
				else if ($value->type==Field::OBJECT)
					$fields[$key]=($value->value instanceof DynamicObject) ? $value->value->to_string() : $value->value;
				else
				{
					$fields[$key]=($value->value===0) ? '0' : $value->value;
				}
			}
 		
   		if ($this->model_state==Model::STATE_NEW)
   		{
   		    // CRUD:  pre_create hook
   			$this->pre_create($fields);
   			
   			// validation moved here in case pre-create is needed to render a valid model
   			if($validate_model) // So it turns out that there are cases when we need to save 'invalid' data (loading Celtx data i.e.)
   			{
   				$result=$this->validate();
   				if (is_array($result))
   					return $result;
   			}
   			
   			$result=true;
   			$result = $this->primary_key_value=$this->do_insert($fields);
 			
   			if ($result)
   			{
	   			$this->model_state=Model::STATE_VALID;
	 			
	 			// CRUD:  post_create hook
	 	        $this->post_create();
	 	        
	 			$this->post_save();
   			}
   		}
   		else if ($this->model_state==Model::STATE_DIRTY)
   		{
   		    // CRUD:  pre_update hook
   			$this->pre_update($fields);

   			// validation moved here in case pre-update is needed to render a valid model
   			if($validate_model) // So it turns out that there are cases when we need to save 'invalid' data (loading Celtx data i.e.)
   			{
   				$result=$this->validate();
   				if (is_array($result))
   					return $result;
   			}
   			
   			
   		    $result=$this->do_update($fields);
 			
   		    if ($result)
   		    {
	   		    $this->model_state=Model::STATE_VALID;
	 			
	 			// CRUD:  post_update hook
		        $this->post_update($fields);
	 			
		        $this->post_save();
   		    }
   		}
   		else if ($this->model_state==Model::STATE_DELETED)
   			throw new Exception("Model is invalid state for saving.");

   		return $result;
	}
	

    protected function do_select()
    {
        return $this->db->fetch_row($this->table_name,$this->primary_key,$this->primary_key_value);
    }

    protected function do_insert($fields)
    {
	return $this->db->insert($this->table_name, $this->primary_key, $fields);
    }
	
	protected function do_update($fields)
	{
		return $this->db->update($this->table_name,$this->primary_key,$this->primary_key_value,$fields);
	}
    
	protected function do_delete()
    {
    	return $this->db->delete($this->table_name,$this->primary_key,$this->primary_key_value);
    }
    
	protected function non_transient($fields)
	{
		$non_transients = array();

		foreach($fields as $key => $value)
		{
			if ($this->fields[$key]->transient==false)
			{
				$non_transients[$key] = $value;
			}
		}

		return $non_transients;
	}
    
    
	
	/**
	* called to delete the object from the DB
	*/
	function delete()
	{
   		if ($this->readonly)
   			throw new ModelException('This is a read-only model, you cannot save, update or delete.');

		if (($this->model_state==Model::STATE_VALID) || ($this->model_state==Model::STATE_DIRTY))
		{
			$old_fields = $this->fields;
			
			// CRUD:  pre_delete hook
			$this->pre_delete();
			
			$result = $this->do_delete();
			
			if ($result)
			{
				$this->model_state=Model::STATE_DELETED;
				
	 			// CRUD:  post_delete hook
	 			$this->post_delete($old_fields);

			}
		}
	}

	function bind_data(&$data)
	{
   		foreach($this->fields as $key => $value)
   				$data[$key]=$value->value;
   		
   		$data[$this->primary_key]=$this->primary_key_value;
	}

	function copy(&$source)
	{
		foreach($source->fields as $key=>$value)
			if (($key!=$this->primary_key) && (isset($this->fields[$key])))
		   		$this->{$key}=$source->{$key};
	}
	
	/**
	 * Binds input to model fields.
	 *
	 * @param Input $input Input to fetch values from (Input::Get() or Input::Post())
	 * @param array $allowed  Map of form fields to field names, required.
	 */
   	function bind_input($input,$allowed)
	{
   		foreach($allowed as $form_name => $name)
   			if ($input->exists($form_name) && ($this->fields[$name]!=null))
   			{
   				switch ($this->fields[$name]->type)
   				{
   					case Field::NUMBER:
   						$this->__set($name,$input->get_num($form_name));
   						break;
					case Field::TIMESTAMP:
   						$this->__set($name,$input->get_date($form_name));
   						break;
					case Field::BOOLEAN:
   						$this->__set($name,$input->get_boolean($form_name));
   						break;
                    case Field::MULTI:
                        $this->__set($name,$input->get_array($form_name));
                        break;
                    case Field::OBJECT:
                        break;
                    default:
   			   			$this->__set($name,$input->get_string($form_name));
   						break;
   				}
   		   	}
   	}

	 
	 /**
	  * Converts to a flat array
	  * @return array Returns the fields as a flattened array
	  */
	 public function to_array()
	 {
	 	foreach($this->fields as $key => $field)
   	 		$result[$key]=$field->value;

	 	$result[$this->primary_key]=$this->primary_key_value;
	 		
	 	return $result;
	 }


	/**
	 * Array access
	 */
	function offsetExists($offset)
	{
		if ($offset==$this->primary_key)
			return true;
		
		return isset($this->fields[$offset]);
	}
	
	/**
	 * Array access
	 */
   	function offsetGet($offset)
	{
		if ($offset==$this->primary_key)
			return $this->primary_key_value;
		
		return $this->{$offset};
	}
	
	/**
	 * Array access
	 */
	function offsetUnset($offset)
	{
		
	}
	
	/**
	 * Array access
	 */
	function offsetSet($offset,$value)
	{
	}


	/*
	 * @desc - generate something unique
	 * 
	 * @value string - the value to check
	 * @field string - the field to use
	 * @subset array - the other fields
	 */
	function unique_value($value, $field='unique_title', $subset=null, $space_replace="", $separator="")
	{
		
		$converted=clean_string($value);
		$filter=new Filter($this, get_class($this));
		$filter->{$field}->starts_with($converted);
		$filter->select=$field;
		
		if($subset!=null)
			foreach($subset as $sub)
				$filter->{$sub}->equals($this->{$sub});
				
		$similar=$filter->get_rows();
		
		
		if(is_array($similar) && count($similar)>0)
		{		
			foreach($similar as $same)
				$chopped[]=(int)str_replace(array($converted,$space_replace,$separator), "", $same[$field]);
		
			//make count a number
			$count=max($chopped);
			$converted.=$separator.(++$count);
		}
		
		return $converted;
	}
	

	
}
