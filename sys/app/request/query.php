<?
/**
 * Provides a wrapper around the query string, to allow controls to manipulate it.
 * 
 * @copyright     Copyright 2009-2012 Jon Gilkison and Massify LLC
 * @link          http://wiki.getheavy.info/index.php/URI
 * @package       system.app
 * @subpackage    request
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
 */

class Query
{
 	private $items=array();
 	
 	public function __construct()
 	{
 		$this->items=array();

 		foreach($_GET as $key => $item)
			$this->items[$key]=xss_clean($item);
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
 	 * @return unknown_type
 	 */
 	function set_value($name,$value)
 	{
 		$this->items[$name]=$value;
 	}
 	
 	/**
 	 * Removes a value from the query
 	 * 
 	 * @param $name
 	 * @return unknown_type
 	 */
 	function remove_value($name)
 	{
 		unset($this->items[$name]);
 	}
 	
 	/**
 	 * Removes a value from the query
 	 * 
 	 * @param $name
 	 * @return unknown_type
 	 */
 	function remove($name)
 	{
 		unset($this->items[$name]);
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
 		
 		$items=$this->items;
 		
 		if ($removevalues!=null)
			foreach($removevalues as $key)
				unset($items[$key]);
 		
		if ($newvalues!=null)
			foreach($newvalues as $key => $value)
				$items[$key]=$value;
 		
		$result='';
		
		foreach($items as $key=>$value)
		{
			if (is_array($value))
			{
				foreach($value as $item)
					$result.=$key.urlencode("[]")."=".urlencode($item)."&";
			}
			else
				$result.=$key.'='.urlencode($value)."&";
		}
				
 		$result=trim($result,'&');
		
		if ($result=='')
			return '';
		else
 			return '?'.$result;
 	}
}