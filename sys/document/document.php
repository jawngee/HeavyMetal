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
**/

uses('sys.document.document_store');

/**
 * Exception for when a document cannot be found.
 */
class DocumentNotFoundException extends Exception {}

/**
 * Represents a document within a document store such as MongoDB, etc.
 */
class Document
{
    /**
     * Document is in a new state.
     */
    const DOCUMENT_NEW=0;

    /**
     * Document is in a live state.
     */
    const DOCUMENT_LIVE=1;

    /**
     * Document is in a deleted state.
     */
    const DOCUMENT_DELETED=2;

    /**
     * Document is in a dirty state.
     */
    const DOCUMENT_DIRTY=3;

    /**
     * Contains the "properties" of the document.
     * @var array|\ArrayObject|string
     */
    public $doc_fields=array();

    /**
     * Document state
     * @var int
     */
    public $doc_state=Document::DOCUMENT_NEW;

    /**
     * Pointer to the document store this document uses.
     * @var DocumentStore
     */
    public $doc_db;

    /**
     * The name of the database to use for this document.  Must override in your custom document classes.
     * @var string
     */
    public $doc_database;

    /**
     * The name of the collection to use for this document.
     * @var string
     */
    public $doc_collection;

    /**
     * Document constructor.
     * 
     * @param null $id The id for a pre-existing document
     * @param null $fields Fields to use to populate the properties of the document.
     */
    public function __construct($id=null,$fields=null)
    {
        try
        {
            $this->doc_db=DocumentStore::Get($this->doc_database);
        }
        catch(Exception $ex)
        {
            
        }
        
        $props=get_object_vars($this);
        $props['_id']=($id) ? $id : uuid();
        
        foreach($props as $key => $prop)
        {
            if (strpos($key,'doc_')===FALSE)
            {
            	if (is_array($prop))
            		$this->doc_fields[$key]=new ArrayObject();
            	else
                	$this->doc_fields[$key]=$prop;
                unset($this->$key);
            }
        }

        if ($id!=null)
        {
            if (is_numeric($id))
                $id=(int)$id;
            
            $res=$this->doc_db->query($this,array('_id'=>$id),null,0,0,false);
            if (count($res)==0)
                throw new DocumentNotFoundException("Invalid document identifier: $id");

            $fields=$res[0];
        }

        if ($fields!=null)
        {
            foreach($fields as $key=>$val)
            {
                if (array_key_exists($key,$this->doc_fields))
                {
                    if (((is_array($val)) || ($val instanceof Traversable)) && (array_key_exists('__class',$val)))
                    {
                        $class=$val['__class'];
                        if (class_exists($class))
                            $val=new $class(null,$val);
                    }
                    else if (is_string($val))
                    	$val=utf8_decode($val);

                    $this->doc_fields[$key]=$val;
                }
            }
        }
    }
    

    /**
     * Static method creates an instance of a given document.
     * @static
     * @param  $document The name of the document.
     * @param null $id The id of the document if pre-existing
     * @param null $fields The fields to populate it's properties
     * @return Document
     */
    public static function Instance($document,$id=null,$fields=null)
    {
		uses("document.$document");
	
		$modelparts=explode('.',$document);
		$class=str_replace('_','',array_pop($modelparts));
		$instance=new $class($id,$fields);
	
		return $instance;
    }
    
    /**
     * @param  $name
     * @return array|ArrayObject|string
     */
    public function __get($name)
    {
    	if ($name=='id')
    		$name='_id';
    		
        return $this->doc_fields[$name];
    }

    /**
     * @param  $name
     * @param  $value
     * @return void
     */
    public function __set($name,$value)
    {
    	if ($name=='id')
    		$name='_id';
    	
    	$this->doc_fields[$name]=$value;

        if ($this->doc_state==Document::DOCUMENT_LIVE)
            $this->doc_state=Document::DOCUMENT_DIRTY;
    }

    /**
     * Pre-save hook
     * @param bool $updated Is this an update or a new save?
     * @return bool
     */
    public function pre_save($updated=false)
    {
        return true;
    }

    /**
     * Post-save hook
     * @param bool $updated Is this an update or a new save?
     * @return void
     */
    public function post_save($updated=false)
    {

    }

    /**
     * Pre-delete hook.
     * @return bool
     */
    public function pre_delete()
    {
        return true;
    }

    /**
     * Post-delete hook.
     * @return void
     */
    public function post_delete()
    {

    }

    /**
     * Saves the document to it's store
     * @return void
     */
    public function save()
    {
        $this->doc_db->save($this);
    }

    /**
     * Removes a document from it's store.
     * @return void
     */
    public function remove()
    {
        $this->doc_db->remove($this);
    }

    /**
     * Flattens the data of a document and any of it's child documents recursively.
     * @static
     * @param  $data
     * @return array
     */
    private static function Flatten($data)
    {
        $result=$data;
        
		foreach($result as $key => $value)
		{
			if (is_string($value))
                $result[$key]=utf8_encode($value);
			else if ($value instanceof Document)
                $result[$key]=$value->to_array();
            else if ($value instanceof ArrayObject)
            	$result[$key]=Document::Flatten($value->getArrayCopy());
            else if (is_array($value))
            {
                $result[$key]=Document::Flatten($value);
            }
		}

		return $result;
    }

    /**
     * Converts a document to an array of values.
     * @return array
     */
    public function to_array()
    {
        $data=Document::Flatten($this->doc_fields);
        $data['__class']=get_class($this);
        return $data;
    }

    /**
     * Converts a document to a json string.
     * @return string
     */
    public function to_json()
    {
         return json_encode($this->to_array());
    }
}
