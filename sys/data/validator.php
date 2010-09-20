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

/**
 * Represents a validation error
 */
class ValidationError
{
	/**
	* Field name
	*/
	var $field;

	/**
	* Error message
	*/
	var $error;
	
	/**
	* Constructor
	* @param string $field Name of the field
	* @param string	$error Error message
	*/
	function ValidationError($field,$error)
	{
			$this->field=$field;
			$this->error=$error;
	}
}

/**
* Abstract base class for all server validators
*/
abstract class Validator
{
	/**
	* Error message
	*/
	var $_message;

	/**
	* Constructor.  Hidden from public to enforce a static factory method for
	* instatiating validators.
	* 
	* @param string	$message error message
	* @param object $data 
	*/
	protected function Validator($message,$data)
	{
		$this->_message=$message;
		$this->_data=$data;
	}
	
	/**
	* Called when a field is to be validated.  Should only validate it's particular function.
	* If the field can't be validated one way or the other, for instance the field has a null value,
	* this function should return true.
	* 
	* @param object $obj Object to validate
	* @param string	$field Field name to validate
	* @param array $fields Array of Field objects 
	*/
	abstract function validate(&$obj,&$field,&$fields);
}

/**
* Insures that a given field contains a value
*/
class RequiredValidator extends Validator
{
	static function Create($message)
	{
		return new RequiredValidator($message,null);

	}

	function validate(&$obj,&$field,&$fields)
	{
		return (isset($fields[$field]) && ($fields[$field]->value!=""));
	}
}


/**
* Insures that a given field is greater than equal to $minlength and the field is less than equal to $maxvalue
*/
class LengthValidator extends Validator
{
	var $_minlength;
	var $_maxlength;
	
	static function Create($minlength,$maxlength,$message)
	{
		$result=new LengthValidator($message,null);
		
		$result->_minlength=min($minlength,$maxlength);
		$result->_maxlength=max($minlength,$maxlength);
		
		return $result;
	}

	function validate(&$obj,&$field,&$fields)
	{
		if (isset($fields[$field]) && ($fields[$field]->value!=""))
		{
			$len=strlen($fields[$field]->value);
			return ( ($len>=$this->_minlength) && ($len<=$this->_maxlength) );
		}
		else
			return ($this->_minlength<=0);
	}
}

/**
* Compares a field to a specific value	
*/
class ValueValidator extends Validator
{
	const GREATER=0;
	const GREATER_EQUAL=1;
	const LESS=2;
	const LESS_EQUAL=3;
	const EQUAL=4;
	const NOT_EQUAL=54;
	
	var $_comparetype;
	var $_value;
	
	static function Create($comparetype,$value,$message)
	{
		$result=new ValueValidator($message,null);
		
		$result->_value=$value;
		$result->_comparetype=$comparetype;
		
		return $result;
	}

	function validate(&$obj,&$field,&$fields)
	{
		if (isset($fields[$field]))
		{
			switch($this->_comparetype)
			{
				case ValueValidator::GREATER:
					return ($fields[$field]->value > $this->_value);
					
				case ValueValidator::GREATER_EQUAL:
					return ($fields[$field]->value >= $this->_value);
					
				case ValueValidator::LESS:
					return ($fields[$field]->value < $this->_value);
					
				case ValueValidator::LESS_EQUAL:
					return ($fields[$field]->value <= $this->_value);
					
				case ValueValidator::EQUAL:
					return ($fields[$field]->value == $this->_value);
					
				case ValueValidator::NOT_EQUAL:
					return ($fields[$field]->value != $this->_value);
			}
		}

		return true;
	}
}

/**
* Compares two field values
*/
class CompareValidator extends Validator
{
	const GREATER=0;
	const GREATER_EQUAL=1;
	const LESS=2;
	const LESS_EQUAL=3;
	const EQUAL=4;
	const NOT_EQUAL=5;
	
	var $_comparetype;
	var $_field;
	
	static function Create($comparetype,$field,$message)
	{
		$result=new CompareValidator($message,null);
		
		$result->_field=$field;
		$result->_comparetype=$comparetype;
		
		return $result;
	}

	function validate(&$obj,&$field,&$fields)
	{
		if (isset($fields[$field]))
		{
			switch($this->_comparetype)
			{
				case CompareValidator::GREATER:
					return ($fields[$field]->value > $fields[$this->_field]->value);
					
				case CompareValidator::GREATER_EQUAL:
					return ($fields[$field]->value >= $fields[$this->_field]->value);
					
				case CompareValidator::LESS:
					return ($fields[$field]->value < $fields[$this->_field]->value);
					
				case CompareValidator::LESS_EQUAL:
					return ($fields[$field]->value <= $fields[$this->_field]->value);
					
				case CompareValidator::EQUAL:
					return ($fields[$field]->value == $fields[$this->_field]->value);
					
				case CompareValidator::NOT_EQUAL:
					return ($fields[$field]->value != $fields[$this->_field]->value);
			}
		}

		return true;
	}
}

/**
* Insures that a given field is unique in the database.
*/
class DBFieldValidator extends Validator
{
	static function Create($message)
	{
		return new DBFieldValidator($message,null);

	}
	
	function validate(&$obj,&$field,&$fields)
	{
		if ($obj->model_state==Model::STATE_NEW)
			return ($obj->db->get_one("SELECT count($field) FROM {$obj->table_name} WHERE $field='".$fields[$field]->value."'")==0);
		else
		{
			$sql="SELECT count($field) FROM $obj->table_name WHERE $field='".$fields[$field]->value."' AND $obj->primary_key<>$obj->primary_key_value";
			return ($obj->db->get_one($sql)==0);
		}
	}
}

/**
* Validates that a given field value is of a specific format.
*/
class FormatValidator extends Validator
{
	const IS_ALNUM			=	0;
	const IS_ALPHA			=	1;
	const IS_CREDITCARD		=	2;
	const IS_DATE			=	3;
	const IS_DIGITS			=	4;
	const IS_EMAIL			=	5;
	const IS_FLOAT			=	6;
	const IS_HEX			=	7;
	const IS_HOSTNAME		=	8;
	const IS_INT			=	9;
	const IS_PHONE			=	10;
	const IS_URI			=	11;
	const IS_ZIP			=	12;
	const IS_ALNUM_SPACE	=	13;
	
	var $_istype;
	
	static function Create($type, $message)
	{
		$result=new FormatValidator($message,null);
		$result->_istype=$type;
		return $result;
	}

	public static function isEmail($email) 
	{  
		// First, we check that there's one @ symbol, and that the lengths are right  
		if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) 
			return false;  // Email invalid because wrong number of characters in one section, or wrong number of @ symbols.    
		
		// Split it into sections to make life easier  
		$email_array = explode("@", $email);  
		$local_array = explode(".", $email_array[0]);  
		for ($i = 0; $i < sizeof($local_array); $i++) 
			if (!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$", $local_array[$i])) 
				return false;    

		if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) 
		{ 
			// Check if domain is IP. If not, it should be valid domain name    
			$domain_array = explode(".", $email_array[1]);    
			if (sizeof($domain_array) < 2) 
				return false; // Not enough parts to domain    
	
			for ($i = 0; $i < sizeof($domain_array); $i++) 
				if (!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$", $domain_array[$i])) 
					return false;      
		}
	
		return true;
	} 		
	
	function validate(&$obj,&$field,&$fields)
	{
		if (isset($fields[$field]) && ($fields[$field]->value!=""))
		{
			switch($this->_istype)
			{
				case FormatValidator::IS_ALNUM:
					return ctype_alnum($fields[$field]->value);
					
				case FormatValidator::IS_ALPHA:
					return ctype_alpha($fields[$field]->value);
					
				case FormatValidator::IS_CREDITCARD:
					return false;
					
				case FormatValidator::IS_DATE:
					return false;
					
				case FormatValidator::IS_DIGITS:
					return false;
					
				case FormatValidator::IS_EMAIL:
					return FormatValidator::isEmail($fields[$field]->value);
					
				case FormatValidator::IS_FLOAT:
					return false;
					
				case FormatValidator::IS_HEX:
					return ctype_xdigit($fields[$field]->value);
					
				case FormatValidator::IS_HOSTNAME:
					return false;
					
				case FormatValidator::IS_INT:
					return false;
					
				case FormatValidator::IS_PHONE:
					return false;
					
				case FormatValidator::IS_URI:
					return false;
					
				case FormatValidator::IS_ZIP:
					return false;

				case FormatValidator::IS_ALNUM_SPACE:
					return ctype_alnum(str_replace(" ","",$fields[$field]->value));
			}
		}
		else 
			return true;
	}
}
