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

uses('system.data.field');
uses('system.data.filter_field');
uses('system.data.order_by');
uses('system.data.join');

/**
 * Model filter
 */
class Filter
{
	private $joins=array();			/** List of joins */
	protected $fields=array();		/** List of filtered fields */

	public  $model=null;			/** Reference to the Model being filtered */
	public $class=null;
	
	public $table_alias=null;
	
	public $select='*';

	public $order_by=null;			/** OrderBy class for controlling sort order */
	
	public $limit=null;				/** Result limits */
	public $offset=null;			/** Result offset */
	
	protected $grouped=null;		/** is this a grouped filter? */
	
	public $mode="AND";				/** Default query mode */
	
	public $distinct=false;			/** should this filter return distinct values? */
	
	public $filter_description_tokens=null;
	
	private $grouplevel=0;
	
	/**
	 * Constructor
	 * 
	 * @param Model $model The model to filter
	 */
	public function __construct($model,$class)
	{
		$this->model = $model;
		$this->class=$class;
		$this->order_by=new OrderBy($this,$model);
		
		$this->generate_table_alias();
	}
	
	public static function Model($model)
	{
		$model=str_replace('/','.',$model);
		
		uses("model.$model");
		
		$parts=explode('.',$model);
		
		$class=str_replace('_','',$parts[1]);
		$instance=new $class();
		
		return new Filter($instance,$class);
	}
	
	/**
	* Callback method for getting a property
	*/
   	function __get($prop_name)
   	{
   		if ($prop_name=='or')
   		{
 			$this->grouplevel++;
   			return $this;
   		}
   		
   		if (isset($this->model->fields[$prop_name]))
   		{
   			if (isset($this->fields[$prop_name]))
   				$result=&$this->fields[$prop_name];
   			else
   			{
		   		$result=new FilterField($this,$this->model->fields[$prop_name]);
		   		$this->fields[$prop_name]=&$result;
   			}
   			
   		}
   		else if ($prop_name==$this->model->primary_key)
   		{
   			if (isset($this->fields[$prop_name]))
   				$result=&$this->fields[$prop_name];
   			else
   			{
   				$field=new Field($prop_name,Field::NUMBER);
		   		$result=new FilterField($this,$field);
		   		$this->fields[$prop_name]=&$result;
   			}
   		}
   		else
   			return null;
   			//throw new ModelException("Field '$prop_name' doesn't exist in this filter.");
   		
   		$result->and=($this->grouplevel==0);
   			
		if ($this->grouplevel>0)
   			$this->grouplevel--;
   			
   		return $result;
   	}
   	
   	/**
  	*  Callback method for setting a property
   	*/
   	function __set($prop_name, $prop_value)
   	{
  		if (isset($this->model->fields[$prop_name]))
   		{
   			if (isset($this->fields[$prop_name]))
   				$this->fields[$prop_name]->equals($prop_value);
   			else
   			{
		   		$result=new FilterField($this,$this->model->fields[$prop_name]);
   				$result->and=($this->grouplevel==0);
		   		$result->equals($prop_value);
		   		$this->fields[$prop_name]=&$result;

		   		if ($this->grouplevel>0)
   					$this->grouplevel--;
   			}

	   		return true;
   		}
   		else if ($prop_name==$this->model->primary_key)
   		{
   			if (isset($this->fields[$prop_name]))
   				$this->fields[$prop_name]->equals($prop_value);
   			else
   			{
   				$field=new Field($prop_name,Field::NUMBER);
		   		$result=new FilterField($this,$field);
		   		$result->equals($prop_value);
		   		$this->fields[$prop_name]=&$result;
   				$result->and=($this->grouplevel==0);

		   		if ($this->grouplevel>0)
   					$this->grouplevel--;
     		}

	   		return true;
   		}
   		else
   			return false;//throw new ModelException("Field '$prop_name' doesn't exist in this filter.");
    }
   	
    /**
     * Builds select statement
     *
     * @return string The built select statement
     */
   	function build_select()
   	{
   			
   		$result='';
   		
   		if (($this->select!='')&&($this->select!=null))
   		{
	   		$fields=explode(',',$this->select);
	   		foreach($fields as $field)
 			{
                    $field_name = trim($field);
	   			
                // When to prefix with the table alias
 				if ($this->table_alias && ($field_name=='*' ||                                 // e.g. profiles11.* 
 				    isset($this->model->fields[$field_name]) ||         // e.g. profiles11.display_name (field in the model)
 				    $field_name==$this->model->primary_key ||           // e.g. profiles11.id  (model primary key)
 				    preg_match("/^[a-z0-9_]+\s+as /i", $field_name)) )   // e.g. profiles11.created as profile_created
 			    {
 				    $field_name=$this->table_alias.'.'.$field_name;
 			    }
 			    
	   			$result.=$field_name.', ';
	   		}
	   		$result=trim($result,', ');
   		}

   		if (count($this->joins)==0)
   			return $result;
   		
   		$result.=', ';
   		
   		
   		foreach($this->joins as $join)
   		{
			$join_select=$join->filter->build_select();
			if(!empty($join_select))
			     $result.=$join_select.', ';
   		}
		return trim($result,', ');
   	}
   	
   	/**
   	 * Builds the where clause
   	 *
   	 * @return string The built where clause
   	 */
   	function build_where()
   	{
   		$result='';
   		$grouped=false;
   		
   		foreach($this->fields as $field)
   		{
   			if ((!$field->and) && (!$grouped))
   			{
   				$result.="(";
   				$grouped=true;
   			} 
   			else if (($field->and) && ($grouped))
   			{
   				$result=rtrim($result," OR ").") $this->mode ";
   				$grouped=false;
   			}
   			
   			if ($grouped)
	   			$result.=$field->value." OR ";
   			else
	   			$result.=$field->value." $this->mode ";
   		}
   		
   		if ($grouped)
   			$result=rtrim($result," OR ").") $this->mode ";
   		   		
   		foreach($this->joins as $join)
   		{
   			if (!$join->filter_in_join)
   			{
   			  $where = $join->filter->build_where();
   			  if($where)
    		       $result.=$where." $this->mode ";
    		}
   		}

   		return rtrim($result," $this->mode ");
   	}
   	
   	/**
   	 * Builds join list
   	 *
   	 * @return string Built SQL for joins and joins of joins of joins, etc.
   	 */
   	function build_joins()
   	{
   		$result='';
   		
   		foreach($this->joins as $join)
   		{
			$join_on = ($join->model->db->supports(Database::FEATURE_TABLE_ALIAS)) ? 
				$join->model->table_name.' '.$join->filter->table_alias :
				$join->model->table_name;
                
   			$result.=strtoupper($join->kind).' JOIN '.$join_on.' ON '.
   			         $join->filter->table_alias.'.'.$join->foreign_column.'='.
   			         $this->table_alias.'.'.$join->column.' ';
   			
   			if ($join->filter_in_join)
   			{
   				// Write the join filter's conditions into the JOIN instead of the WHERE.
   				$where = rtrim($join->filter->build_where());
   				if (!empty($where))
   				     $result .= " AND ".$where. " ";
   			}
   			
   			$result.=$join->filter->build_joins();
   		}
   		return $result;
   	}
   	
   	
   	function show_query()
   	{
   		return $this->to_sql();
   	}
   	
   	/**
   	 * Builds the filter and returns a string of SQL
   	 */
   	function to_sql($select=null,$include_order=true,$include_limit=true)
   	{
   		$result='';
   		
   		if ($select!==FALSE)
   		{
   			if ($select==null)
   				$select=$this->build_select();
   			
   			if (($this->grouped!=null) || ($this->distinct))
	   			$result="SELECT DISTINCT ";
   			else
   				$result="SELECT ";

			$from = ($this->model->db->supports(Database::FEATURE_TABLE_ALIAS)) ? 
				$this->model->table_name.' '.$this->table_alias :
				$this->model->table_name;	
   				
   			$result.=$select." FROM ".$from." ";
   		}
   		
   		if (count($this->joins)>0)
   			$result.=$this->build_joins();
   		
   		$where=$this->build_where();
   		if ($where!='')
   			$result.=" WHERE ".$where;
   			
   		if ($this->grouped!=null)
   			$result.=" GROUP BY ".$this->model->table_name.'.'.$this->grouped[0].' HAVING COUNT('.$this->model->table_name.'.'.$this->grouped[0].')<='.$this->grouped[1];

   		if ($include_order && ($this->model->db->supports(Database::FEATURE_ORDERBY)))
   			$result.=$this->order_by->to_sql();

   		if ($include_limit && ($this->model->db->supports(Database::FEATURE_OFFSETLIMIT)))
   		{
   			if ($this->offset!=null)
				$result.=" OFFSET $this->offset ";

			if ($this->limit!=null)
				$result.=" LIMIT $this->limit ";
   		}

		return $result;
   	}
   	
   	/**
   	 * Builds the filter and executes the sql
   	 */
   	function execute($select=null,$offset=null,$limit=null)
   	{
   		if ($offset)
   			$this->offset=$offset;
   			
   		if ($limit)
   			$this->limit=$limit;
   			
   		return $this->model->db->execute($this->to_sql($select),null,null);
   	}
   	
   	
   	/**
   	 * TODO:  Bulk update()?
   	 */
   	
   	/**
   	 * Builds the filter and executes the sql
   	 */
   	function delete()
   	{
   		if (!$this->model->db->supports(Database::FEATURE_BULKDELETE))
   			return false;
   			
		$from = ($this->model->db->supports(Database::FEATURE_TABLE_ALIAS)) ? 
			$this->model->table_name.' '.$this->table_alias :
			$this->model->table_name;	
   			
   		$result="DELETE FROM ".$from;
   		
   		if (count($this->fields)>0)
   		{
   			$result.=" WHERE ";

	   		foreach($this->fields as $field)
	   			$result.=$field->value." $this->mode ";
   			
	   		$result=rtrim($result," $this->mode ");
   		}
   		
   		return $this->model->db->execute($result);
   	}
   	
   	
   	private function unpack($val)
   	{
   		$val=str_replace('$amp;','&',$val);
   		if (strpos($val,'[')===0)
   		{	
   			$val=trim(trim($val,'['),']');
   			return explode(',',$val);
   		}
   		
   		return $val;
   	}
   	
   	/**
   	 * Parses a psuedo query string.  
   	 *
   	 * @param string $query
   	 */
   	function parse($query)
   	{
   		// hack
   		$query=str_replace('&amp;','$amp;',$query);
   		$matches=array();
   		if (preg_match_all('#([A-Za-z0-9_-]*)\s*([!=<>]+|by|contains any|contains all|contains|starts with|ends with|within|not in|in|is not|is)\s*([^&|]*)(?:&|\|)*#',$query,$matches))
   		{
   			$vars=$matches[1];
   			$operators=$matches[2];
   			$vals=$matches[3];
   			$varcount=count($vars);

   			for($i=0; $i<$varcount; $i++)
   			{
   				if (($vars[$i]=='order') && ($operators[$i]=='by'))
   				{
   					if (strpos($vals[$i],' ')>0)
   					{
   						$order=explode(' ',$vals[$i]);
   						$this->order_by->{$order[0]}->{$order[1]};
   					}
   					else
   						$this->order_by->{$vals[$i]};
   				}
   				else if ($vars[$i]=='offset')
   					$this->offset=$vals[$i];
   				else if ($vars[$i]=='limit')
   					$this->limit=$vals[$i];
                else //if (isset($this->model->fields[$vars[$i]])) /* this check is handled in magic __get __set */
                {         
                	if (($vars[$i]==$this->model->primary_key) || ($this->{$vars[$i]}))     
   					switch($operators[$i])
   					{
   						case '=':
   							$this->{$vars[$i]}->equals($this->unpack($vals[$i]));
   							break;
   						case '!=':
   							$this->{$vars[$i]}->not_equal($this->unpack($vals[$i]),true /*include nulls*/);
   							break;
   						case '<=':
   							$this->{$vars[$i]}->less_equal($this->unpack($vals[$i]));
   							break;
   						case '>=':
   							$this->{$vars[$i]}->greater_equal($this->unpack($vals[$i]));
   							break;
   						case '>':
   							$this->{$vars[$i]}->greater($this->unpack($vals[$i]));
   							break;
   						case '<':
   							$this->{$vars[$i]}->less($this->unpack($vals[$i]));
   							break;
   						case 'within':
   							$range=$this->unpack($vals[$i]);
   							$this->{$vars[$i]}->withins($range[0],$range[1]);
   							break;
   						case 'not in':
   							$this->{$vars[$i]}->is_not_in($this->unpack($vals[$i]));
   							break;
   						case 'in':
   							$this->{$vars[$i]}->is_in($this->unpack($vals[$i]));
   							break;
   						case 'is':
   							$this->{$vars[$i]}->is_null();
   							break;
   						case 'is not':
   							$this->{$vars[$i]}->not_null();
   							break;
   						case 'starts with':
   							$this->{$vars[$i]}->starts_with($this->unpack($vals[$i]));
   							break;
   						case 'ends with':
   							$this->{$vars[$i]}->ends_with($this->unpack($vals[$i]));
   							break;
   						case 'contains':
   							$this->{$vars[$i]}->contains($this->unpack($vals[$i]));
   							break;
   						case 'contains any':
   							$this->{$vars[$i]}->contains_any($this->unpack($vals[$i]));
   							break;
   						case 'contains all':
   							$this->{$vars[$i]}->contains_all($this->unpack($vals[$i]));
   							break;
   					}
   				}
   			}
   		
   			return $vars;
   		}
   	}
   	
   	/**
   	 * Runs the filter, returning an array of found models
   	 *
   	 * @return array Array of found models
   	 */
   	function find($offset=null,$limit=null)
   	{
   		$result=array();
   		
   		$rows=$this->execute($this->build_select(),$offset,$limit); 
		$class=$this->class;
   		foreach($rows as $row)
   		{
   			$obj=new $class(null,null,null,$row);
   			$result[]=$obj;
   		}
   		
   		return $result;
   	}
   	
   	function stash($cache_key=null, $cache_expiry=0)
   	{
   		uses('system.data.memory.memory_filter');

   		// CHECK CACHE?
   		if ($cache_key && $cache_expiry > 0)
		{
			$cache=CacheMoney::GetCache('stash');
			$result  = $cache->get($cache_key);
						
			if ($result)
			{
				$data_items =  unserialize($result);
				return new MemoryFilter($data_items, $this->model, $this->class);
			}
		}
   		
		// Do the lookup
   		$data_items = $this->get_rows();
		
   		// CACHE THE RESULT?
   		if ($cache_key && $cache_expiry > 0)
   		{
			$cache=CacheMoney::GetCache('stash');
			$cache->set($cache_key, serialize($data_items), $cache_expiry);
   		}
   		
   		return new MemoryFilter($data_items, $this->model, $this->class);
   	}
   	
   	/**
   	 * Runs teh filter, returning the first found model
   	 *
   	 * @return Model
   	 */
   	function first()
   	{
   		//$this->order_by->clear(); // why clear?  then I can't impose my own ordering, e.g. most recently created...
   		$this->order_by->{$this->model->primary_key}->asc; // this is really just a default or tie-breaker
   		
   		$row=$this->get_row();
        
   		if (empty($row))
   			return null;

   		$class=$this->class;
	   		
	   	$obj=new $class(null,null,null,$row);
	
		return $obj;
   	}
   	
   	function last()
   	{
        //$this->order_by->clear(); // why clear?  then I can't impose my own ordering, e.g. most recently created...
   		$this->order_by->{$this->model->primary_key}->desc; // this is really just a default or tie-breaker
   		
   	    $row=$this->get_row();
        
        if (empty($row))
            return null;

        $class=$this->class;
            
        $obj=new $class(null,null,null,$row);
    
        return $obj;
   	}
   	
   	/**
   	 * Builds the filter and executes the sql, returning a single row
   	 */
   	function get_row($select=null)
   	{
   		return $this->model->db->get_row($this->to_sql($select));
   	}
   	
   	/**
   	 * Fetches the result as an array versus a DatabaseResult
   	 *
   	 * @param string $select
   	 * @return array
   	 */
   	function get_rows($select=null)
   	{
   		return $this->model->db->get_rows($this->to_sql($select));
   	}

   	/**
   	 * Builds the filter and executes the sql, returning a single row
   	 */
   	function get_one($field)
   	{
   		$result=$this->model->db->get_one($this->to_sql($field));
   		return $result;
   	}

   	/**
   	 * Executes the sql returning a single dimensional array of the specified result field
   	 */
   	function get_array($field)
   	{
   		$arr = array();

   		foreach($this->execute($field) as $row)
   			$arr[] = $row[$field];
   			
   		return $arr;
   	}
   	
   	
   	/**
   	 * Executes the sql returning an associative array of the $key => $value specified.
   	 */
	function get_map($key=null, $value=null)
	{
	   if ($key && $value)
	   {
	       $arr = array();
	       foreach($this->execute($key.','.$value) as $row)
	           $arr[$row[$key]] = $row[$value];
	   
	       return $arr;
	   }
	   
	   return null;
	}
   	

   	/**
   	 * Builds the filter and executes the sql, returning the total count of items.
   	 */
   	function get_count($field=null, $distinct=false)
   	{
   		if(!$field)
   		   $field = $this->model->primary_key;

 		$table_alias = ($this->model->db->supports(Database::FEATURE_TABLE_ALIAS)) ? 
				$this->table_alias :
				null;	

   		 return $this->model->db->count($field,$this->model->table_name,$this->to_sql(FALSE,false,false),$distinct,$table_alias);
   	}
   	
   	/**
   	 * Adds a join to another model to the filter
   	 *
   	 * @param string $column The column on the filter to be joined
   	 * @param string $foreign_column The column on the current filter to join on
   	 * @param Filter $filter The filter to be joined
   	 */
   	function join($column,$filter,$foreign_column, $kind='inner', $filter_in_join=false)
   	{
   		$this->joins[]=new Join($column,$filter,$foreign_column,$kind, $filter_in_join);
   	}
   	
   	/**
   	 * Does a group by aggregate query
   	 *
   	 * @param string $by The column to group by
   	 * @param int $count The minimum number of matching items to have
   	 */
   	function group_by($by,$count)
   	{
   		$this->grouped=array($by,$count);
   	}
   	
   	private function get_comparison($comparison)
   	{
   		switch(strtoupper($comparison))
   		{
			case 'STARTS_WITH':
				return Field::COMPARE_STARTS_WITH;
			case 'CONTAINS':
				return Field::COMPARE_CONTAINS;
			case 'ENDS_WITH':
				return Field::COMPARE_ENDS_WITH;
			case 'GREATER_EQUAL':
				return Field::COMPARE_GREATER_EQUAL;
			case 'LESS_EQUAL':
				return Field::COMPARE_LESS_EQUAL;
			case 'GREATER':
				return Field::COMPARE_GREATER;
			case 'LESS':
				return Field::COMPARE_GREATER;
			case 'NOT_NULL':
				return Field::COMPARE_NOT_NULL;
			case 'IS_NULL':
				return Field::COMPARE_IS_NULL;
			default:
				return Field::COMPARE_EQUALS;
		}
   	}
   	
   	/**
   	 * Binds the input from a request to the filter's fields.  
   	 *
   	 * @param unknown_type $input
   	 * @param unknown_type $allowed
   	 * @param unknown_type $compare_suffix
   	 */
   	function bind_input($input,$allowed,$compare_suffix='_cmp')
   	{
   		foreach($allowed as $form_name => $name)
   			if ($input->exists($form_name) && ($this->model->fields[$name]!=null))
   			{
   				if ($input->exists($form_name.$compare_suffix))
   					$comparison=$this->get_comparison($input->get_string($form_name.$compare_suffix));
   				else
   					$comparison=$this->model->fields[$name]->comparison;
   				
   				
	   			switch($comparison)
	   			{
					case Field::COMPARE_EQUALS:
						$this->{$name}=$input->get_string($form_name);
						break;
						
					case Field::COMPARE_STARTS_WITH:
						$this->{$name}->starts_with($input->get_string($form_name));
						break;
					
					case Field::COMPARE_CONTAINS:
						$this->{$name}->contains($input->get_string($form_name));
						break;
					
					case Field::COMPARE_ENDS_WITH:
						$this->{$name}->ends_with($input->get_string($form_name));
						break;
					
					case Field::COMPARE_GREATER_EQUAL:
						$this->{$name}->greater_equal($input->get_string($form_name));
						break;
					
					case Field::COMPARE_LESS_EQUAL:
						$this->{$name}->less_equal($input->get_string($form_name));
						break;
					
					case Field::COMPARE_GREATER:
						$this->{$name}->greater($input->get_string($form_name));
						break;
					
					case Field::COMPARE_LESS:
						$this->{$name}->less($input->get_string($form_name));
						break;
					
					case Field::COMPARE_NOT_NULL:
						$this->{$name}->not_null();
						break;
					
					case Field::COMPARE_IS_NULL:
						$this->{$name}->is_null();
						break;
	   			}
   				
   				
   			}
   	}


	/**
	 * Generates a somewhat-readable table_alias to use in SQL joins
	 * (too bad the code isn't readable!)
	 * 
	 * @return unknown_type
	 */
	private function generate_table_alias()
	{
              $mdl_parts = split('\.',$this->model->table_name);

              $tablename = $mdl_parts[1];
        
        
              if ($this->model->db->supports(Database::FEATURE_TABLE_ALIAS))
              {
	        $tbl_parts = split('_',$tablename);
	        $readable = $tbl_parts[0];
	        
	        if (count($tbl_parts) > 1)
	        {
	            $readable .= '_';
	        
	            for($i=1; $i<count($tbl_parts); $i++)
	                $readable .= $tbl_parts[$i][0];
	        }
	        
	        $uniq = (isset($_REQUEST['TAKE_A_NUMBER']) && 
	        		 count($_REQUEST['TAKE_A_NUMBER'])>0) ? array_pop($_REQUEST['TAKE_A_NUMBER']) : rand(0,9999);
	        $this->table_alias = $readable . $uniq;	

              }
              else
              {
        	// not supported, so we'll just use tablename, e.g.  "select profiles.gender from ... "
        	$this->table_alias = $tablename;
              }
	}
}
   	
function filter($model)
{
	return Filter::Model($model);
}
