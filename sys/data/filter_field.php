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

/**
 * A filtered field
 */
class FilterField
{
	public $filter;		/** Filter that owns this filtered field */
	public $field;		/** The Field object being filtered */
	public $value;		/** The value of the filter */

	public $and=true;
	
	private $model=null;
	
	/**
	 * Constructor
	 *
	 * @param Field $field The field being filtered
	 */
	public function __construct($filter,$field,$and=true)
	{
		$this->filter=&$filter;
		$this->model=$filter->model;
		$this->field=&$field;
		$this->and=$and;
	}

	/**
	 * Equals
	 *
	 * @param mixed $value The value to use for comparison
	 */
	function equals($value, $include_null=false)
	{
		if (is_array($value))
		{
			if ($this->field->type==Field::MULTI)
			{
				if ($value instanceof Filter)
					throw new DatabaseException('Cannot use a filter on an array field.');

				$this->value="(";
				foreach($value as $val)
					$this->value.="$val = ANY(".(($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->filter->model->table_name.'.' : '').$this->field->name.") AND ";
				$this->value=rtrim($this->value,'AND ').")";
			}
			else
			{
				$val=(($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->filter->model->table_name.'.' : '').$this->field->name." IN (";
				foreach($value as $item)
				{
					$val.=$this->model->db->escape_value($this->field->type,$item).',';
				}

				$this->value=rtrim($val,',').')';
			}
		}
		else
		{
			$value=$this->model->db->escape_value($this->field->type,$value);
			$this->value=(($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->filter->model->table_name.'.' : '').$this->field->name."=$value";
		}
		
		if ($include_null)
            $this->value="(".$this->value." OR ".(($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->filter->model->table_name.'.' : '').$this->field->name." IS NULL)";
		
		return $this->filter;
	}

	/**
	 * Equals
	 *
	 * @param mixed $value The value to use for comparison
	 */
	function not_equal($value, $include_null=false)
	{
		$value=$this->model->db->escape_value($this->field->type,$value);
		$this->value=(($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->filter->model->table_name.'.' : '').$this->field->name."<>$value";

		if ($include_null)
            $this->value="(".$this->value." OR ".(($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->filter->model->table_name.'.' : '').$this->field->name." IS NULL)";

		return $this->filter;
	}


	/**
	 * Equals any value in the supplied list.
	 *
	 * @param array $values The values to use for comparison
	 */
	function equals_any($values)
	{
		if ($this->field->type==Field::MULTI)
		{
			$this->value="(";
			foreach($values as $value)
				$this->value.="$value = ANY(".(($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->filter->model->table_name.'.' : '').$this->field->name.") OR ";
			$this->value=rtrim($this->value,'OR ').")";
		}
		else
		{
			$this->value='(';

			foreach($values as $value)
				$this->value.=(($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->filter->model->table_name.'.' : '').$this->field->name."=".$this->model->db->escape_value($this->field->type,$value)." OR ";

			$this->value=rtrim($this->value,' OR ').')';
		}

		return $this->filter;
	}

	/**
	 * Determines if a value is in a set
	 *
	 * @param mixed $values The values to use for comparison (array or another filter)
	 */
	function is_in($values, $include_null=false)
	{
		$this->value=(($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->filter->model->table_name.'.' : '').$this->field->name." IN (";

		if ($values instanceof Filter)
			$this->value.=$values->to_sql();
		else
			foreach($values as $value)
				$this->value.=$this->model->db->escape_value($this->field->type,$value).',';

		$this->value=rtrim($this->value,',').')';
		
		if ($include_null)
            $this->value="(".$this->value." OR ".(($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->filter->model->table_name.'.' : '').$this->field->name." IS NULL)";

		return $this->filter;
	}

	/**
	 * Determines if a value is NOT in a set
	 *
	 * @param mixed $values The values to use for comparison
	 */
	function is_not_in($values, $include_null=false)
	{
		$this->value=(($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->filter->model->table_name.'.' : '').$this->field->name." NOT IN (";

		if ($values instanceof Filter)
			$this->value.=$values->to_sql();
		else
			foreach($values as $value)
				$this->value.=$this->model->db->escape_value($this->field->type,$value).',';

		$this->value=rtrim($this->value,',').')';
		
		if ($include_null)
			$this->value="(".$this->value." OR ".(($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->filter->model->table_name.'.' : '').$this->field->name." IS NULL)";

		return $this->filter;
	}

	/**
	 * Starts with
	 *
	 * @param mixed $value The value to use for comparison
     * @param mixed $op Used to swap ILIKE for LIKE or ~ if indexes require it.
	 */
	function starts_with($value, $op='ILIKE', $caseconv=null)
	{
		$value=$this->model->db->escape_value($this->field->type,$value.'%');
		$value=$this->wrap($value,$caseconv);
		$field=$this->wrap((($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->filter->model->table_name.'.' : '').$this->field->name,$caseconv);
		
		$this->value=$field." $op $value";

		return $this->filter;
	}

	/**
	 * Contains
	 *
	 * @param mixed $value The value to use for comparison
     * @param mixed $op Used to swap ILIKE for LIKE or ~ if indexes require it.
	 */
	function contains($value, $op='ILIKE', $caseconv=null)
	{
		$value=$this->model->db->escape_value($this->field->type,'%'.$value.'%');
		$value=$this->wrap($value,$caseconv);
		$field=$this->wrap((($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->filter->model->table_name.'.' : '').$this->field->name,$caseconv);
		
		$this->value=$field." $op $value";

		return $this->filter;
	}

	/**
	 * Contains
	 *
	 * @param mixed $value The value to use for comparison
     * @param mixed $op Used to swap ILIKE for LIKE or ~ if indexes require it.
	 */
	function contains_any($values, $op='ILIKE', $caseconv=null)
	{
		$result='(';
		
		foreach($values as $value)
		{
			$value=$this->model->db->escape_value($this->field->type,'%'.$value.'%');
			$value=$this->wrap($value,$caseconv);
			$field=$this->wrap((($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->filter->model->table_name.'.' : '').$this->field->name,$caseconv);
			
			$result.="(".$field." $op $value) OR";
		}
		
		$this->value=trim($result, 'OR').') ';

		return $this->filter;
	}
	
	/**
	 * Contains
	 *
	 * @param mixed $value The value to use for comparison
     * @param mixed $op Used to swap ILIKE for LIKE or ~ if indexes require it.
	 */
	function contains_all($values, $op='ILIKE', $caseconv=null)
	{
		$result='(';
		
		foreach($values as $value)
		{
			$value=$this->model->db->escape_value($this->field->type,'%'.$value.'%');
	        $value=$this->wrap($value,$caseconv);
	        $field=$this->wrap((($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->filter->model->table_name.'.' : '').$this->field->name,$caseconv);

			$result.="(".$field." $op $value) AND";
		}
		
		$this->value=trim($result, 'AND').') ';

		return $this->filter;
	}
	
	
	/**
	 * Ends with
	 *
	 * @param mixed $value The value to use for comparison
	 */
	function ends_with($value, $op='ILIKE', $caseconv=null)
	{
		$value=$this->model->db->escape_value($this->field->type,'%'.$value);
	    $value=$this->wrap($value,$caseconv);
	    $field=$this->wrap((($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->filter->model->table_name.'.' : '').$this->field->name,$caseconv);
	 
		$this->value=$field." $op $value";

		return $this->filter;
	}

	/**
	 * Greater than or equal to
	 *
	 * @param mixed $value The value to use for comparison
	 */
	function greater_equal($value)
	{
		$value=$this->model->db->escape_value($this->field->type,$value);
		$this->value=(($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->filter->model->table_name.'.' : '').$this->field->name." >= $value";

		return $this->filter;
	}

	/**
	 * Less than or equal to
	 *
	 * @param mixed $value The value to use for comparison
	 */
	function less_equal($value)
	{
		$value=$this->model->db->escape_value($this->field->type,$value);
		$this->value=(($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->filter->model->table_name.'.' : '').$this->field->name." <= $value";

		return $this->filter;
	}

	/**
	 * Greater than
	 *
	 * @param mixed $value The value to use for comparison
	 */
	function greater($value)
	{
		$value=$this->model->db->escape_value($this->field->type,$value);
		$this->value=(($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->filter->model->table_name.'.' : '').$this->field->name." > $value";

		return $this->filter;
	}

	/**
	 * Within a specified range
	 *
	 * @param mixed $min The minumum value
	 * @param mixed $max The maximum value
	 */
	function within($min,$max)
	{
		$min=$this->model->db->escape_value($this->field->type,$min);
		$max=$this->model->db->escape_value($this->field->type,$max);
		$this->value='('.(($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->filter->model->table_name.'.' : '').$this->field->name." >= $min AND ".(($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->filter->model->table_name.'.' : '').$this->field->name." <= $max)";

		return $this->filter;
	}


	/**
	 * Less than
	 *
	 * @param mixed $value The value to use for comparison
	 */
	function less($value)
	{
		$value=$this->model->db->escape_value($this->field->type,$value);
		$this->value=(($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->filter->model->table_name.'.' : '').$this->field->name." < $value";

		return $this->filter;
	}

	/**
	 * Not null
	 */
	function not_null()
	{
		$this->value=(($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->filter->model->table_name.'.' : '').$this->field->name." IS NOT NULL";

		return $this->filter;
	}

	/**
	 * Is Null
	 */
	function is_null()
	{
		$this->value=(($this->model->db->supports(Database::FEATURE_PREFIX_COLUMNS)) ? $this->filter->model->table_name.'.' : '').$this->field->name." IS NULL";

		return $this->filter;
	}
	
	private function wrap($value, $caseconv=null)
	{
        return ($caseconv)?"$caseconv($value)":$value;
	}
}

