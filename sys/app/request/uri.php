<?
/**
 * Allows manipulation of the request's URI and/or query string, for use by controls 
 * for such things as pagination, etc.
 * 
 * @copyright     Copyright 2009-2012 Jon Gilkison and Trunk Archive Inc
 * @package       application
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
 */


uses('sys.app.request.query');

/**
 * 
 * 
 * @package		application
 * @subpackage	request
 * @link          http://wiki.getheavy.info/index.php/URI
 */
class URI
{
	public $multi_seperator = '_';
	
	/**
	 * The root of the URI
	 * 
	 * @var string
	 */
 	public $root='';
 	
 	/**
 	 * 
 	 * The segments of the URI
 	 * 
 	 * @var array
 	 */
 	public $segments=array();
 	
 	/**
 	 * Wraps query string
 	 * 
 	 * @var Query
 	 */
 	public $query=null;
 	
 	public function __construct($root=null, $segments=null)
 	{
 		if (($root==null) && ($segments==null))
 		{
 		 	$path=(isset ($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : @ getenv('PATH_INFO');
			$segments=explode('/',$path);
			array_shift($segments);
			$root=array_shift($segments);
			
			for($i=0;$i<count($segments);$i++)
				$segments[$i] = strtolower($segments[$i]);
 		}
 		
 		if ((strlen($root)==0) || ($root[0]!='/'))
 			$root='/'.$root;
 		
 		$this->root=$root;
 		$this->segments=$segments;
 		$this->query=new Query();
 	}
 	
 	public function __clone() 
 	{
	    foreach($this as $key => $val) 
	    {
	        if(is_object($val)||(is_array($val)))
	        {
	            $this->{$key} = unserialize(serialize($val));
	        }
	    }
	} 
 	

	public function copy()
	{
		return clone $this;
	}
	
	public function set_root($root)
	{
		$this->root = $root;
		
		return $this;
	}
	
	
	
 	/**
	 * Gets the value of a segment-value in the URI
 	 * 
 	 * @param string $name Gets the value of a segment-value pair
 	 * @return string
 	 */
 	public function get_value($name)
 	{
 		for($i=0; $i<count($this->segments)-1; $i++)
 			if ($this->segments[$i]===strtolower($name))
 				return $this->segments[$i+1];

 		return false;
 	}
 	
 	public function get_array($name)
 	{
 		$values = array();
 		
 		for($i=0; $i<count($this->segments)-1; $i++)
 			if ($this->segments[$i]===strtolower($name))
 				$values[] = $this->segments[$i+1];
 		
	 	return $values; 		
 	}
 	
 	/**
 	 * Makes sure that the value of the segment-value in the URI is a number
 	 * 
 	 * @param string $name Name of the segment-value pair
 	 * @return number
 	 */
 	public function get_number($name)
 	{
 		$value=$this->get_value($name);
 		
 		return (is_numeric($value) ? $value : false);
 	}
 	
 	/**
 	 * Sets the value of a URI segment-pair
 	 * or single segment (if value param is omitted)
 	 * 
 	 * @param string $name
 	 * @param string $value
 	 */
 	function set_value($name,$value=null)
 	{
 		if (!$name)
 			return $this;
 		
 		if (!$value)
 		{
 			$this->segments[]=strtolower($name);
 			return $this;
 		}
 			
 		for($i=0; $i<count($this->segments)-1; $i++)
 		{
	 		if ($this->segments[$i]===strtolower($name))
 			{
 				array_splice($this->segments,$i+1,1,strtolower($value));
 				return $this;
 			}
 		}
 				
 		return $this->add_value($name, $value);
 	}
 	
 	function add_value($name, $value=null)
 	{
 		$found = false;
 		
 	 	if (!$name)
 			return $this;
 		
 		// If $value wasn't specified, just add a segment for $name
 		if (!$value)
 		{
 			$this->segments[]=strtolower($name);
 			return $this;
 		} 		
 	
 		// Look through existing segments to see if a multi-valued segment already exists for this $name
 	 	for($i=0; $i<count($this->segments)-1; $i++)
 		{
	 		if ($this->segments[$i]===strtolower($name))
 			{
 				if (isset($this->segments[$i+1]))
 				{
 					$vals = explode($this->multi_seperator,$this->segments[$i+1]);

 					if (!in_array(strtolower($value), $vals))
 					{
 						$vals[] = strtolower($value);
 						$this->segments[$i+1] = implode($this->multi_seperator,$vals);
 					
 						$found = true;
 					}	
 				}
 			}
 		}
 				
 		// If no multi-valued segment exists.  Add a new segment pair for $name/$value.
 		if (!$found)
 		{
 			$this->segments[] = strtolower($name);
 			$this->segments[] = strtolower($value);
 		}
 			
 		
 		return $this;
 	}
 	
 	/**
 	 * Removes a segment-value pair from the URI
 	 * 
 	 * @param string $name
 	 * @param string $value
 	 */
 	function remove_value($name, $value=null)
 	{
 		for($i=0; $i<count($this->segments)-1; $i++)
 		{
 			if ($this->segments[$i]===$name)
 			{
 				if (!$value || strtolower($value)===$this->segments[$i+1])
 				{
 					array_splice($this->segments,$i,2);
 				}	
				else
				{	// Here we're checking for OR conditions expressed in the url as /key/value1_value2
					$vals = explode($this->multi_seperator,$this->segments[$i+1]);

					for($j=0; $j<count($vals); $j++)
					{
						if (strtolower($value)===$vals[$j])
						{
							array_splice($vals,$j,1);
							
							$this->segments[$i+1] = implode($this->multi_seperator,$vals);						
						}
					}
				}
 			}
 		}
 			
 		return $this;//->remove($name);
 	}
 	
	/**
 	 * Removes a segment from the URI
	 * 
	 * @param string $name
	 */
 	function remove($name)
 	{
 		for($i=0; $i<count($this->segments); $i++)
 		{
 			if ($this->segments[$i]==strtolower($name))
 			{
 				array_splice($this->segments,$i,1);
 				return $this;
 			}
 		}
 			
 		return $this;		
 	}
 	
 	/**
 	 * Removes multiple segments from the URI
	 * 
	 * @param array Array of values, or key=>value pairs to remove
	 */
	public function remove_values($removevalues=null)
	{
 		foreach($removevalues as $key=>$value)
 		{
 			if (is_numeric($key))
 			{
 				$this->remove($value);	
 			}
 			else
 			{
 				$this->remove_value($key, $value);
 			}
 		}
 		
 		return $this;
	}
 	
 	/**
 	 * Replace a single segment in the URI
 	 * 
 	 * @param string $name
 	 * @param string $what
 	 */
 	function replace($name,$what)
 	{
 		for($i=0; $i<count($this->segments); $i++)
 			if ($this->segments[$i]==strtolower($name))
 			{
 				$this->segments[$i]=strtolower($what);
 				return $this;
 			}
 	
 	 	return $this;	
 	}
 	

 	/**
 	 * Returns the values + original path as a complete URI
 	 * 
 	 * @param $newvalues
 	 * @param $queryvalues
 	 * @param $removevalues
 	 * @return unknown_type
 	 */
 	function build($newvalues=null, $removevalues=null, $queryvalues=null, $removequeryvalues=null)
 	{ 		
 		$segs=$this->segments;
		
 		// remove any values or key=>value pairs first
 		if ($removevalues!=null)
 			foreach($removevalues as $key=>$value)
 			{
 				$this->remove_value($key, $value);
 			}
 		
  		if ($newvalues!=null)
	 		foreach($newvalues as $key=>$value)
	 		{
 				if (is_array($value))
					foreach ($value as $val)
						$values[] = strtolower($val);
	 			else
	 				$values[] = strtolower($value);
	 				 			
		 		$added=false;

	 			foreach($values as $val) 
 				{	
	 			
					if (is_numeric($key) && count($segs)>=$key)
	 				{   
	 					// allows segment to be added at a specific index (or in front of the current value)
	 					//$segs[]=$value;
						array_splice($segs,$key,0,$val);
	 					$added=true;
	
	 				}
	 				else for($i=0; $i<count($segs); $i++)
		 				if ($segs[$i]==$key)
			 			{
		 					array_splice($segs,$i+1,1,$val);
		 					
		 					$added=true;
		 					break;
		 				}
	 			
		 			if (!$added)
			 			array_splice($segs,count($segs),0,array($key,$val));
 				}
	 		}
	 		
		for($i=0; $i<count($segs); $i++)
			$segs[$i] = rawurlencode($segs[$i]);
		 	
 		return rtrim($this->root."/".implode('/',$segs).$this->query->build($queryvalues,$removequeryvalues), '/');
 	}
}