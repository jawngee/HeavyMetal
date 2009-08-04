<?
/**
*
* Copyright (c) 2009, Jon Gilkison and Massify LLC.
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
	private $fields=array();		/** List of filtered fields */

	public  $model=null;			/** Reference to the Model being filtered */
	public $class=null;
	
	public $select='*';

	public $order_by=null;			/** OrderBy class for controlling sort order */
	
	public $limit=null;				/** Result limits */
	public $offset=null;			/** Result offset */
	
	protected $grouped=null;		/** is this a grouped filter? */
	
	public $mode="AND";				/** Default query mode */
	
	public $distinct=false;			/** should this filter return distinct values? */
	
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
	   			$field_name=(($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->model->table_name.'.' : '').trim($field);
	   			// handle selects from functions returning rows
 	   			if (strstr($this->model->table_name,'('))
                    $field_name = trim($field);
	   			
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
                
   			$result.=strtoupper($join->kind).' JOIN '.$join->model->table_name.' ON '.
   			         $this->trim_fcn_name($join->model->table_name).'.'.$join->foreign_column.'='.
   			         $this->trim_fcn_name($this->model->table_name).'.'.$join->column.' ';
   			
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
   	
   	/**
   	 * Converts table names like permissions.my_permissions('1234556') to my_permissions for joins
   	 * 
   	 * @param $table_name
   	 * @return trimmed table_name
   	 */
   	function trim_fcn_name($table_name)
   	{
   		// handles joins on functions returning rows
   		$paren_pos = strpos($table_name,'(');
   		
        if ($paren_pos > 0) {
        	$table_token_pos = strpos($table_name,'.') + 1;
        	
        	$table_name = substr($table_name, $table_token_pos, $paren_pos - $table_token_pos );
        }
        
        return $table_name;
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

   			$result.="$select FROM ".$this->model->table_name." ";
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


   		// TODO: FIND OUT WHY THIS IS FUCKED UP. (should be fixed with pdm_permissions merge)
   		//$result=preg_replace('#AND\s*AND#','AND',$result);
   		
		return $result;
   	}
   	
   	/**
   	 * Builds the filter and executes the sql
   	 */
   	function execute($select=null)
   	{
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
   			
   		$result="DELETE FROM ".$this->model->table_name." ";
   		
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
   		if (preg_match_all('#([a-z0-9_-]*)\s*([!=<>]+|by|contains any|contains all|contains|starts with|ends with|within|not in|in|is not|is)\s*([^&|]*)(?:&|\|)*#',$query,$matches))
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
   							$this->{$vars[$i]}->not_equal($this->unpack($vals[$i]));
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
   		}
   	}
   	
   	/**
   	 * Runs the filter, returning an array of found models
   	 *
   	 * @return array Array of found models
   	 */
   	function find()
   	{
   		$result=array();
   		
   		$rows=$this->execute($this->build_select()); 
		$class=$this->class;
   		foreach($rows as $row)
   		{
   			$obj=new $class(null,null,null,$row);
   			$result[]=$obj;
   		}
   		
   		return $result;
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
   	 * Fetches the result as an array, versus an ADODBRecordSet
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
   	 * Builds the filter and executes the sql, returning the total count of items.
   	 */
   	function get_count($field=null, $distinct=false)
   	{
   		if(!$field)
   		   $field = $this->model->primary_key;

   		 return $this->model->db->count($field,$this->model->table_name,$this->to_sql(FALSE,false,false),$distinct);
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

}
   	
function filter($model)
{
	return Filter::Model($model);
}
