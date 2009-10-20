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

uses("system.app.dynamic_object");

/**
 * Represents a field/column in a database
 */
class Field
{
	const STRING=1;			/** varchar **/
	const TEXT=2;			/** text **/
	const NUMBER=3;			/** int, float, number **/
	const TIMESTAMP=4;		/** timestamp **/
	const BOOLEAN=5;		/** bool **/
	const BLOB=6;			/** blob, binary **/
	const MULTI=7;			/** array **/
	const OBJECT=8;			/** Dynamic object that is serialized to a text field **/

	const COMPARE_EQUALS=1;				/** equals **/
	const COMPARE_STARTS_WITH=2;		/** starts with **/
	const COMPARE_CONTAINS=3;			/** contains **/
	const COMPARE_ENDS_WITH=4;			/** ends with **/
	const COMPARE_GREATER_EQUAL=5;		/** greater or equal **/
	const COMPARE_LESS_EQUAL=6;			/** less or equal **/
	const COMPARE_GREATER=7;			/** greater **/
	const COMPARE_LESS=8;				/** less **/
	const COMPARE_NOT_NULL=9;			/** not null **/
	const COMPARE_IS_NULL=10;			/** is null **/
	
	public $name;								/** Column name */
	public $type;								/** Column type */
	public $length;								/** Length (for string types) */
	public $notnull=false;						/** Can be null? */
	public $value=null;							/** Current value, default is null */
	public $description=''; 					/** Description of field */
	public $comparison=Field::COMPARE_EQUALS;	/** Defaults comparison to use */
	public $dirty=false;						/** Has this field been changed? */
	public $db_type='';							/** Database type */
	public $transient=false;                    /** Doesn't need to be stored? */
	
	/**
	* Constructor
	* 
	* @param string $name name of the field
	* @param string	$type type of field
	* @param int $length length/size of the field
	* @param bool $notnull if the field can contain null values (TRUE) or not (FALSE)
	* @param object $value value of the field
	* @param const $comparison (Not sure if this is used -- probably should be a comparison function pointer to handle sorting)
	* @param boolean $transient true if the field should NOT be persisted to the database (default false).  Used by Model->add()
	*/
	public function __construct($name,$type=Field::TEXT,$length=0,$description='',$notnull=false,$value=null, $comparison=Field::COMPARE_EQUALS, $transient=false)
	{
		$this->name=$name;
		$this->type=$type;
		$this->length=$length;
		$this->notnull=$notnull;
		$this->value=$value;
		$this->description=$description;
		$this->comparison=$comparison;
		$this->transient=$transient;
		
		// we can't know if array types have been changed,
		// so they are always dirty.
		if ($this->type==Field::MULTI)
			$this->dirty=true;
	}
	
	
	/**
	 * compare -- used for sorting
	 *
	 * @param Field $field the field to compare this to
	 * @param string $direction the direction of the sort needed
	 *
	 * @return:
	 *   If $this == $field return 0
	 *   If $this > $field return 1
	 *   If $this < $field return -1
	 */
	public function compare($field, $direction='ASC')
	{
		$reverser = ($direction=='DESC')?-1:1;
		
		// use $this field type to drive the comparison
		// NUMBER STRING TIMESTAMP BOOLEAN
		switch($field->type)
		{
			case STRING:
			case TEXT:
				return strcmp($this->value, $field->value)*$reverser;
			case NUMBER:
			case TIMESTAMP:
			case BOOLEAN:
			default:
				if ($this->value == $field->value) {
        			return 0;
    			}
    			return ($this->value < $field->value) ? -1*$reverser : 1*$reverser;
		}
		
	}
}
