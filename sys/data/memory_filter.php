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
uses_system('data/field');
uses_system('data/filter');
uses_system('data/memory_filter_field');

/**
 * MemoryFilter - simple in-memory implementation of Filter (created with $filter->stash())
 * (mainly used to reduce db queries by pulling a larger chunk of related data at once and refining it in memory)
 */
class MemoryFilter extends Filter // TODO:  Factor out the interface for Filter and FilterField
{
	public $objects = array();

	/**
	 * Constructor
	 * 
	 * @param Array $objects Array of rows or of model objects to filter
	 * @param Model $model The model to filter
	 */
	public function __construct($objects=null, $model, $class)
	{
		if ($objects)
			$this->objects = $objects; // otherwised initialized to empty array

		$this->model = $model;
		$this->class=$class;
		$this->order_by=new OrderBy($this,$model);
	}
	

	/**
	* Callback method for getting a property
	*/
   	function __get($prop_name)
   	{
   		if ($prop_name=='or')
   		{
            throw new Exception("OR conditions not supported by MemoryFilter");
   		}
   		
        if (isset($this->model->fields[$prop_name]))
        {
            if (isset($this->fields[$prop_name]))
                $result=&$this->fields[$prop_name];
            else
            {
                $result=new MemoryFilterField($this,$this->model->fields[$prop_name]);
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
                $result=new MemoryFilterField($this,$field);
                $this->fields[$prop_name]=&$result;
            }
        }
        else
        {
        	// add it to the model and filter
        	$field=new Field($prop_name, Field::STRING);
        	$result=new MemoryFilterField($this, $field);
        	$this->fields[$prop_name]=&$result;
        }
   			
   		return $result;
   	}
   	
   	/**
  	*  Callback method for setting a property
   	*/
   	function __set($prop_name, $prop_value)
   	{
        throw new Exception('Assignment not supported within MemoryFilter');
   	}


  	
   	
   	/**
   	 * TODO:  Bulk update()?
   	 */
   	
   	/**
   	 * Builds the filter and executes the sql
   	 */
   	function delete()
   	{
        throw new Exception('delete not supported within MemoryFilter');
   	}
   	


   	
   	/**
   	 * Runs the filter, returning an array of found models
   	 *
   	 * @return array Array of found models
   	 */
   	function find($offset=0,$limit=null)
   	{
   		$this->sort();
   		return array_slice($this->objects, $offset, $limit);
   	}
   	
   	/**
   	 * Runs teh filter, returning the first found model
   	 *
   	 * @return Model
   	 */
   	function first()
   	{
   		$this->sort();
        return $this->objects[0];
   	}
   	
   	function last()
   	{
   		$this->sort();
   		return end($this->objects[0]);
   	}
   	
   	/**
   	 * Builds the filter and executes the sql, returning a single row
   	 */
   	function get_row($select=null)
   	{
   		$this->sort();
        return $this->objects[0];
   	}
   	
   	/**
   	 * Fetches the result as an array, versus an ADODBRecordSet
   	 *
   	 * @param string $select
   	 * @return array
   	 */
   	function get_rows($select=null)
   	{
   		$this->sort();		
        return $this->objects;
   	}

   	
   	
   	/**
   	 * Builds the filter and executes the sql, returning a single row
   	 */
   	function get_one($field)
   	{
   		$this->sort();
   		return $this->objects[0][$field]; 
   	}

   	function get_array($field)
   	{
   		$this->sort();

   		$arr = array();

   		foreach($this->objects as $object)
   			$arr[] = $object[$field];
   			
   		return $arr;
   	}
   	
   	function sort()
   	{
   		if ($this->order_by->orderings())
	   		usort($this->objects, array($this,"compare_objects"));
   	}
   	
   	function compare_objects($a,$b)
   	{
		return $this->order_by->compare($a,$b);
	}
   	
   	
   	/**
   	 * Builds the filter and executes the sql, returning the total count of items.
   	 */
   	function get_count($field=null, $distinct=false)
   	{
   		if (!$field && !$distinct)
   		   return count($this->objects);
   		   
        if(!$field)
           $field = $this->model->primary_key;

        if (!$distinct)
        {
	   		$count=0;
	   		
	   		foreach($this->objects as $object)
	   		{
	   			if (!is_null($object[$field]))
	   		 	{
	   			   $count++;
	   			}
	   		}
	   		
	   		return $count;
        }
        else // distinct
        {
            $unique=array();

            foreach($this->objects as $object)
            {
            	$unique[$object[$field]]='foo';
            }
            
            return count($unique);
        }
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
        throw new Exception('Joins not supported by MemoryFilter');
   	}
   	
   	/**
   	 * Does a group by aggregate query
   	 *
   	 * @param string $by The column to group by
   	 * @param int $count The minimum number of matching items to have
   	 */
   	function group_by($by,$count)
   	{
        throw new Exception('Grouping not supported by MemoryFilter');
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


}

