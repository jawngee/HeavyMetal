<?
/**
 * Provides a wrapper around the query string, to allow controls to manipulate it.
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

/**
 * 
 * @package		application
 * @subpackage	request
 * @link          http://wiki.getheavy.info/index.php/URI
 */
class Query
{
 	private $items=array();
 	
 	public function __construct($query=null)
 	{
 		$this->items=array();

 		if (!$query) // Use HTTP request
 		{ 		
	 		foreach($_GET as $key => $item)
		 		if (is_array($item))
	 				$this->items[$key]=$item;
	 			else
	 				$this->items[$key]=xss_clean($item);
 		}
 		else // Parse it out ourselves (from a render:port)
 		{
 			$matches = array();
 			preg_match_all("/([^=&]+)=([^&]+)/", $query, $matches);
 			
 			$keys = $matches[1];
 			$values = $matches[2];
 			
 			for($i=0; $i<count($keys); $i++)
 				$this->items[$keys[$i]]=$values[$i];
 		}
 	}
 	
 	/**
	 * Gets the value of a query item
 	 * 
 	 * @param string $name Name of the query value
 	 * @return string The value of the query item
 	 */
 	function get_value($name)
 	{
 		return (isset($this->items[$name])) ? $this->items[$name] : false;
 	}
 	
 	/**
	 * Gets the first key in the query string that contains the substring $match
	 * (currently used to hang onto repeater id during pagination for ajax repeaters) 
 	 * 
 	 * @param string $match The string to match
 	 * @return mixed
 	 */
	function get_key_like($match)
	{
		foreach($this->items as $key => $item)
			if (stristr($key, $match))
				return $key;
		
		return null;
	}
	
	/**
	 * Gets the value of a query item as a number
	 * 
	 * @param string $name The name of the query item
	 * @return number
	 */
 	function get_number($name)
 	{
 		$value=$this->get_value($name);
 		
 		return (is_numeric($value)) ? $value : false;
 	}
 	
 	/**
 	 * Sets the value of a query item
 	 * 
 	 * @param string $name
 	 * @param string $value
 	 * @return Query
 	 */
 	function set_value($name,$value)
 	{
 		$this->items[$name]=$value;
 		
 		return $this;
 	}
 	
 	
 	function add_value($name,$value)
 	{
 		$val = strtolower($value);

 		if (!isset($this->items[$name]))
 		{
 			$this->items[$name] = array($val);
 		}
 		else
 		{
 			$vals = $this->items[$name];
 			
 			if (!is_array($vals))
 				$this->items[$name] = array($vals, $val);
 			else
 				if (!in_array($val, $vals))
 					$this->items[$name][] = $val; 			
 		} 		

 		return $this;
 	}
 	
 	/**
 	 * Removes a value from the query
 	 * 
 	 * @param string $name
 	 * @return Query
 	 */
 	function remove_value($name, $value=null)
 	{
 		$i=0;
 		foreach ($this->items as $n => $v)
 		{
 			if (strtolower($n)===strtolower($name))
 			{
 				if (!$value || strtolower($value)===strtolower($v))
 				{
 					array_splice($this->items,$i,1);
 					break;
 				}
 				elseif (is_array($v))
 				{
 					$j=0;
 					foreach($v as $inner_val)
 					{
 						if (strtolower($value)===strtolower($inner_val))
 						{
 							array_splice($this->items[$n],$j,1);
 							break;
 						}
 						
 						$j++;
 					}
 				}
 			}
 			
 			$i++;
 		}
 		
 		return $this;
 	}

 	
 	/**
 	 * Returns the query string
 	 * 
 	 * @param $newvalues
 	 * @param $removevalues
 	 * @return unknown_type
 	 */
 	function build($newvalues=null,$removevalues=null)
 	{
 		$result='';
 		
 		if ($removevalues!=null)
			foreach($removevalues as $key => $value) 		
 				$this->remove_value($key, $value);

 		if ($newvalues!=null)
			foreach($newvalues as $key => $value)
				$this->items[$key]=$value;
 
 				
 		$items=$this->items;
 				
 				
		$result='';
		
		foreach($items as $key=>$value)
		{
			if (!empty($key))
			{
				if (is_array($value))
				{
					foreach($value as $item)
						$result.=$key.rawurlencode("[]")."=".rawurlencode(strtolower($item))."&";
				}
				else
					$result.=$key.'='.rawurlencode(strtolower($value))."&";
			}
		}
			
 		$result=trim($result,'&');
		
		if ($result=='')
			return '';
		else
 			return '?'.$result;
 	}
}