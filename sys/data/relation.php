<?php
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


uses('system.data.database');


/**
 * Represents a field/column in a database
 */
class Relation
{
    const RELATION_UNKNOWN = 0;
    const RELATION_SINGLE = 1;
    const RELATION_MANY = 2;
    
    public $parent;         /** Parent model **/
    public $name;           /** Relation name */
    public $type;           /** Relation type */
    public $model;          /** Model represented by the relation */
    public $foreign_field;  /** Name of the foreign key field */
    public $field;          /** Name of the field the relation is on, can be null meaning that the relation is a backwards link */
    
    private $value=null;        /** Current value, default is null */
    
    /**
    * Constructor
    * 
    * @param string $name Name of the relation
    * @param string $type Type of the relation
    * @param string $model Name of the model, eg 'user/profile'
    * @param string $foreign_field Name of the foreign field
    * @param string $field Name of the field
    * @param mixed $value Value of the field
    */
    public function __construct($parent,$name,$type,$model,$foreign_field,$field=null,$value=null)
    { 
        $this->parent=$parent;
        $this->name=$name;
        $this->type=$type;
        $this->model=$model;
        $this->foreign_field=$foreign_field;
        $this->field=$field;
        $this->value=$value;
    }
    
    /**
     * Gets the related items or item
     * 
     * @return mixed The value of the relation
     */
    public function get_value()
    {
        if ($this->value!=null)
            return $this->value;
            
        if ($this->type==self::RELATION_MANY)
            $this->value = $this->get_many();
        else
        	$this->value = $this->get_single();

        return $this->value;
    }
    
    // Hooks to allow subclasses to instantiate relations differently if necessary
    public function get_single()
    {
        if ($this->field!=null)
        {
            if (isset($this->parent->fields[$this->field]))
            {
		if ($this->field == $this->parent->primary_key)
		      $field=$this->parent->primary_key_value;
		else
                      $field=$this->parent->db->escape_value($this->field->type,$this->field->value);
            
                if (($field==null) || ($field=='') || ($field=="''"))
                    return null;
                    
                return filter($this->model)
                           ->{$this->foreign_field}->equals($field)
                           ->first();
            }
            else
                throw new ModelException("The field '$this->field' could not be found on the parent model.");
        }
        else
            return filter($this->model)
                       ->{$this->foreign_field}->equals($this->parent->primary_key_value)
                       ->first();
    }
    
    public function get_many()
    {
    	return filter($this->model)
    	           ->{$this->foreign_field}->equals($this->parent->primary_key_value)
    	           ->find();
    }

}
