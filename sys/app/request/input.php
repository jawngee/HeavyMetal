<?
/**
 * Wraps request variables.
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

uses('sys.app.dynamic_object');
uses('sys.app.request.upload');

/**
 * Thrown if a requested value isn't found.
 * 
 * @package		application
 * @subpackage	request
 */
class ValueNotFoundException extends Exception {}

/**
 * Input filter class
 * 
 * @package		application
 * @subpackage	request
 * @link          http://wiki.getheavy.info/index.php/Input
 */
final class Input extends DynamicObject
{
	protected static $_input=null;

	/**
	 * Fetches a filtered Request input
	 * 
	 * @return Input A filtered Input object based on $_REQUEST
	 */
	public static function Get()
	{
		if (self::$_input==null)
		{
			foreach($_GET as $key=>$value)
				$_GET[$key]=xss_clean($value);
				
			foreach($_POST as $key=>$value)
				$_POST[$key]=xss_clean($value);
			
			self::$_input=new Input(array_merge($_GET,$_POST));
		}
		
		return self::$_input;
	}
	
	/**
	 * Property getter
	 *
	 * @param unknown_type $name
	 * @return unknown
	 */
	public function __get($name)
	{
		if (!isset($this->props[$name]))
			return null;
			
		if ($this->props[$name] instanceof Input)
			return $this->props[$name];
			
		return xss_clean($this->props[$name]);
	}
	
	/**
	 * Determines if input has a value with the given name:
	 * 
	 * <code>
	 * $input=Input::Get();
	 * 
	 * if ($input->exists('name','age','sex')
	 * {
	 * 	// ...
	 * }
	 * </code>
	 *
	 * @return bool True if all arguments exist, otherwise false.
	 */
	function exists()
	{
		$args=func_get_args();
		
		foreach($args as $arg)
			if (!isset($this->props[$arg]))
				return false;
				
		return true;
	}
	

	/**
	 * Fetches a number of values.  If any of the values specified don't exist
	 * throws an exception.
	 * 
	 * <code>
	 * $input=Input::Post();
	 * 
	 * try 
	 * {
	 * 		list($name,$sex,$age)=$input->fetch('name','sex','age');
	 * } 
	 * catch(ValueNotFoundException $ex)
	 * {
	 * 		// do something.
	 * }
	 * </code>
	 * 
	 * @return array The values to fetch.
	 */
	function fetch()
	{
		$args=func_get_args();
		$result=array();
		
		foreach($args as $arg)
		{
			if (!isset($this->props[$arg]))
				throw new ValueNotFoundException("Does not contain value for '$arg'.");
			
			$result[]=xss_clean($this->props[$arg]);
		}
				
		return $result;
	}

	
	/**
	 * Fetches the value, insuring it's a number
	 *
	 * @param unknown_type $prop_name
	 * @param unknown_type $default_value
	 * @return unknown
	 */
	function get_num($prop_name,$default_value=null)
	{
		if ((!isset($this->props[$prop_name]))||(trim($this->props[$prop_name])=='')||(!is_numeric($this->props[$prop_name])))
			return $default_value;
			
		return ($this->props[$prop_name]) ? $this->props[$prop_name] : '0';
	}
	
	 /**
     * Fetches the value, insuring it's an array
     *
     * @param unknown_type $prop_name
     * @return unknown
     */
    function get_array($prop_name)
    {
        if ((!isset($this->props[$prop_name])) || (!is_a($this->props[$prop_name], "Input")))
            return false;
			
		$r = array();
		foreach($this->props[$prop_name]->props as $id => $val)
			$r[] = xss_clean($val);
		
		return $r;
    }
	
	/**
	 * Returns the value as a boolean
	 *
	 * @param unknown_type $prop_name
	 * @return unknown
	 */
	function get_boolean($prop_name, $default=false)
	{
		if (!isset($this->props[$prop_name]))
			return $default;
			
		return (($this->props[$prop_name] == 'on') || ($this->props[$prop_name]=='true'));
	}
	
	/**
	 * Returns the value as a valid date.
	 *
	 * @param unknown_type $prop_name
	 * @return unknown
	 */
	function get_date($prop_name)
	{
		if (!isset($this->props[$prop_name]))
			return FALSE;
		
		$value=str_replace('/','-',trim($this->get_string($prop_name)));
		if (!preg_match('/^\d{1,2}-\d{1,2}-(\d{2}|\d{4})$/', $value))
            return FALSE;

		return $value;
	}
}
