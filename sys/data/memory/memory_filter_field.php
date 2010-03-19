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
uses('system.data.filter');
uses('system.data.memory.memory_filter');

/**
 * A filtered field (used by MemoryFilter)
 *
 */
class MemoryFilterField extends FilterField
{
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
		// perf. optimization (nothing here to filter further)
		if (empty($this->filter->objects))
			return $this->filter;
		
		$results = array();

		foreach($this->filter->objects as $object)
        {
            $set_value = $object[$this->field->name];	

            if ($set_value == $value || ($include_null && is_null($set_value)))
        	   $results[] = $object;
        }

        return new MemoryFilter($results, $this->model, $this->filter->class);
	}

	/**
	 * Not Equal
	 *
	 * @param mixed $value The value to use for comparison
	 */
	function not_equal($value, $include_null=false)
	{	
		// perf. optimization (nothing here to filter further)
		if (empty($this->filter->objects))
			return $this->filter;
		
        $results = array();
        
        foreach($this->filter->objects as $object)
        {
            $set_value = $object[$this->field->name]; 

            if ($set_value != $value || ($include_null && is_null($set_value)))
               $results[] = $object;
        }

        return new MemoryFilter($results, $this->model, $this->class);
	}


	/**
	 * Equals any value in the supplied list.
	 *
	 * @param array $values The values to use for comparison
	 */
	function equals_any($values)
	{	
		// perf. optimization (nothing here to filter further)
		if (empty($this->filter->objects))
			return $this->filter;
		
        $results = array();
        
        foreach($this->filter->objects as $object)
        {
            $set_value = $object[$this->field->name]; 

            if (in_array($set_value, $values))
               $results[] = $object;
        }

        return new MemoryFilter($results, $this->model, $this->class);
	}

	/**
	 * Determines if a value is in a set
	 *
	 * @param mixed $values The values to use for comparison (array or another filter)
	 */
	function is_in($values, $include_null=false)
	{	
		// perf. optimization (nothing here to filter further)
		if (empty($this->filter->objects))
			return $this->filter;
		
        $results = array();
        
        foreach($this->filter->objects as $object)
        {
            $set_value = $object[$this->field->name]; 

            if (in_array($set_value, $values) || ($include_null && is_null($set_value)))
               $results[] = $object;
        }

        return new MemoryFilter($results, $this->model, $this->class);
	}

	/**
	 * Determines if a value is NOT in a set
	 *
	 * @param mixed $values The values to use for comparison
	 */
	function is_not_in($values, $include_null=false)
	{	
		// perf. optimization (nothing here to filter further)
		if (empty($this->filter->objects))
			return $this->filter;
		
        $results = array();
        
        foreach($this->filter->objects as $object)
        {
            $set_value = $object[$this->field->name]; 

            if (!in_array($set_value, $values) || ($include_null && is_null($set_value)))
               $results[] = $object;
        }

        return new MemoryFilter($results, $this->model, $this->class);
	}

	/**
	 * Starts with
	 *
	 * @param mixed $value The value to use for comparison
     * @param mixed $op Used to swap ILIKE for LIKE or ~ if indexes require it.
	 */
	function starts_with($value, $op='ILIKE', $caseconv=null)
	{	
		// perf. optimization (nothing here to filter further)
		if (empty($this->filter->objects))
			return $this->filter;
		
		$results = array();
		
        foreach($this->filter->objects as $object)
        {
            $set_value = $object[$this->field->name];
            $expression =  "/^".$value."/" . (($op=='ILIKE' || $caseconv) ? "i" :'');

            if (preg_match($expression, $set_value));	
                $results[] = $object;
        }
        
        return new MemoryFilter($results, $this->model, $this->class);  
	}

	/**
	 * Contains
	 *
	 * @param mixed $value The value to use for comparison
     * @param mixed $op Used to swap ILIKE for LIKE or ~ if indexes require it.
	 */
	function contains($value, $op='ILIKE', $caseconv=null)
	{	
		// perf. optimization (nothing here to filter further)
		if (empty($this->filter->objects))
			return $this->filter;
		
        $results = array();
        
        foreach($this->filter->objects as $object)
        {
            $set_value = $object[$this->field->name];
            $expression =  "/".$value."/" . (($op=='ILIKE' || $caseconv) ? "i" :'');

            if (preg_match($expression, $set_value));   
                $results[] = $object;
        }
        
        return new MemoryFilter($results, $this->model, $this->class);  
	}

	/**
	 * Contains
	 *
	 * @param mixed $value The value to use for comparison
     * @param mixed $op Used to swap ILIKE for LIKE or ~ if indexes require it.
	 */
	function contains_any($values, $op='ILIKE', $caseconv=null)
	{	
		// perf. optimization (nothing here to filter further)
		if (empty($this->filter->objects))
			return $this->filter;
		
        $results = array();
        
        foreach($this->filter->objects as $object)
        {
        	foreach($values as $value)
        	{
	            $set_value = $object[$this->field->name];
	            $expression =  "/^".$value."/" . (($op=='ILIKE' || $caseconv) ? "i" :'');

	            if (preg_match($expression, $set_value));   
	            {
	                $results[] = $object;
	                continue;
	            }
            }
        }
        
        return new MemoryFilter($results, $this->model, $this->class);  
		
	}
	
	/**
	 * Contains
	 *
	 * @param mixed $value The value to use for comparison
     * @param mixed $op Used to swap ILIKE for LIKE or ~ if indexes require it.
	 */
	function contains_all($values, $op='ILIKE', $caseconv=null)
	{	
		// perf. optimization (nothing here to filter further)
		if (empty($this->filter->objects))
			return $this->filter;
		
        $results = array();
        
        foreach($this->filter->objects as $object)
        {
        	$mismatches = 0;
        	
            foreach($values as $value)
            {
                $set_value = $object[$this->field->name];
                $expression =  "/^".$value."/" . (($op=='ILIKE' || $caseconv) ? "i" :'');

                if (!preg_match($expression, $set_value));   
                {
                    $mismatches++;
                }
            }
            
            if ($mismatches==0)
                $results[] = $object;                
        }
        
        return new MemoryFilter($results, $this->model, $this->class);  
	}
	
	
	/**
	 * Ends with
	 *
	 * @param mixed $value The value to use for comparison
	 */
	function ends_with($value, $op='ILIKE', $caseconv=null)
	{	
		// perf. optimization (nothing here to filter further)
		if (empty($this->filter->objects))
			return $this->filter;
		
        $results = array();
        
        foreach($this->filter->objects as $object)
        {
            $set_value = $object[$this->field->name];
            $expression =  "/".$value.'$/' . (($op=='ILIKE' || $caseconv) ? "i" :'');

            if (preg_match($expression, $set_value));   
                $results[] = $object;
        }
        
        return new MemoryFilter($results, $this->model, $this->class);  
	}

	
	/**
	 * Greater than or equal to
	 *
	 * @param mixed $value The value to use for comparison
	 */
	function greater_equal($value)
	{	
		// perf. optimization (nothing here to filter further)
		if (empty($this->filter->objects))
			return $this->filter;
		
        $results = array();
        
        foreach($this->filter->objects as $object)
        {
            $set_value = $object[$this->field->name]; 

            if ($set_value >= $value)
               $results[] = $object;
        }

        return new MemoryFilter($results, $this->model, $this->class);
	}

	/**
	 * Less than or equal to
	 *
	 * @param mixed $value The value to use for comparison
	 */
	function less_equal($value)
	{	
		// perf. optimization (nothing here to filter further)
		if (empty($this->filter->objects))
			return $this->filter;
		
        $results = array();
        
        foreach($this->filter->objects as $object)
        {
            $set_value = $object[$this->field->name]; 

            if ($set_value <= $value)
               $results[] = $object;
        }

        return new MemoryFilter($results, $this->model, $this->class);
	}

	/**
	 * Greater than
	 *
	 * @param mixed $value The value to use for comparison
	 */
	function greater($value)
	{
		// perf. optimization (nothing here to filter further)
		if (empty($this->filter->objects))
			return $this->filter;
		
        $results = array();
        
        foreach($this->filter->objects as $object)
        {
            $set_value = $object[$this->field->name]; 

            if ($set_value > $value)
               $results[] = $object;
        }

        return new MemoryFilter($results, $this->model, $this->class);
	}

	/**
	 * Within a specified range
	 *
	 * @param mixed $min The minumum value
	 * @param mixed $max The maximum value
	 */
	function within($min,$max)
	{	
		// perf. optimization (nothing here to filter further)
		if (empty($this->filter->objects))
			return $this->filter;
		
		
        $results = array();
        
        foreach($this->filter->objects as $object)
        {
            $set_value = $object[$this->field->name]; 

            if ($set_value <= $max && $set_value >= $min)
               $results[] = $object;
        }

        return new MemoryFilter($results, $this->model, $this->class);
	}


	/**
	 * Less than
	 *
	 * @param mixed $value The value to use for comparison
	 */
	function less($value)
	{
		// perf. optimization (nothing here to filter further)
		if (empty($this->filter->objects))
			return $this->filter;
		
		
        $results = array();
        
        foreach($this->filter->objects as $object)
        {
            $set_value = $object[$this->field->name]; 

            if ($set_value < $value)
               $results[] = $object;
        }

        return new MemoryFilter($results, $this->model, $this->class);
	}

	/**
	 * Not null
	 */
	function not_null()
	{
		// perf. optimization (nothing here to filter further)
		if (empty($this->filter->objects))
			return $this->filter;
		
		$results = array();
        
        foreach($this->filter->objects as $object)
        {
            $set_value = $object[$this->field->name]; 

            if (!empty($set_value))
               $results[] = $object;
        }

        return new MemoryFilter($results, $this->model, $this->class);
	}

	/**
	 * Is Null
	 */
	function is_null()
	{
		// perf. optimization (nothing here to filter further)
		if (empty($this->filter->objects))
			return $this->filter;
		
        $results = array();
        
        foreach($this->filter->objects as $object)
        {
            $set_value = $object[$this->field->name]; 

            if (empty($set_value))
               $results[] = $object;
        }

        return new MemoryFilter($results, $this->model, $this->class);
	}
}

