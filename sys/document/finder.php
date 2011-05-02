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

uses('sys.document.finder_field');
uses('sys.document.sort_by');

/**
 * The finder is a query generator for finding documents.  It's nearly identical to Filter.
 */
abstract class Finder
{
    /**
     * The name of the class to use for finder fields.  MUST be overridden in decendant classes.
     * @var string
     */
    var $finder_field_class='FinderField';

    /**
     * The name of the class to use for sortby.  MUST be overridden in decendant classes.
     * @var string
     */
    var $finder_sort_by_class='SortBy';

    /**
     * Instance of the document this finder can search.
     * @var Document
     */
    var $document=null;

    /**
     * The fields the user has specified to query
     * @var array
     */
    var $fields=array();

    /**
     * The SortBy class that holds the sorting parameters.
     * @var SortBy
     */
    var $sort_by=null;

    /**
     * Finder constructor.
     * @param  $document
     */
    public function __construct($document)
    {
        $this->document=$document;
        $this->sort_by=new $this->finder_sort_by_class($this, $document);
    }

    /**
     * Creates a finder for documents of a given name.
     * @static
     * @param  $document_name
     * @return Finder
     */
    public static function Document($document_name)
	{
     	$document_name=str_replace('/','.',$document_name);

		uses("document.$document_name");

		$parts=explode('.',$document_name);

		$class=str_replace('_','', array_shift($parts));
		$instance=new $class();

        return $instance->doc_db->create_finder($instance);
	}

	/**
     * @param  $prop_name
     * @return mixed
     */
   	function __get($prop_name)
   	{
   		$prop_name=($prop_name=='id') ? '_id':$prop_name;
   		
   		if (array_key_exists($prop_name,$this->document->doc_fields))
   		{
   			if (isset($this->fields[$prop_name]))
   				$result=&$this->fields[$prop_name];
   			else
   			{
		   		$result=new $this->finder_field_class($this,$prop_name);
		   		$this->fields[$prop_name]=&$result;
   			}

   		}
   		else
   			return null;
   			//throw new ModelException("Field '$prop_name' doesn't exist in this filter.");

   		return $result;
   	}

    /**
     * @param  $prop_name
     * @param  $prop_value
     * @return bool
     */
   	function __set($prop_name, $prop_value)
   	{
  		$prop_name=($prop_name=='id') ? '_id':$prop_name;

  		if (isset($this->document->doc_fields[$prop_name]))
   		{
   			if (isset($this->fields[$prop_name]))
   				$this->fields[$prop_name]->equals($prop_value);
   			else
   			{
		   		$result=new $this->finder_field_class($this,$prop_name);
 		   		$result->equals($prop_value);
		   		$this->fields[$prop_name]=&$result;
   			}

	   		return true;
   		}
   		else
   			return false;
    }

    /**
     * Builds the array of queried fields.
     * @return array
     */
    protected function build_query()
    {
        $query=array();
        foreach($this->fields as $ff)
            $query[$ff->field]=$ff->expr;

        return $query;
    }

    /**
     * Builds the array of sort fields.
     * @return array
     */
    protected function build_sorts()
    {
        $sorts=array();
        foreach($this->sort_by->sorts as $sort)
        	$sorts[$sort->field]=$sort->direction;

        return $sorts;
    }

    /**
     * Performs a search and returns an array of Document classes.
     * @param int $offset The offset into the results.
     * @param int $limit The number of items to return
     * @return array
     */
    public function find($offset=0,$limit=0)
    {
        $query=$this->build_query();
        $sorts=$this->build_sorts();

        return $this->document->doc_db->query($this->document,$query,$sorts,$offset,$limit);
    }

    /**
     * Returns the first Document found by the query.
     * @return Document
     */
    public function first()
    {
        $res=$this->find();
        if (count($res)==0)
            return null;

        return $res[0];
    }

    /**
     * Returns the number of items found by a query.
     * @return int
     */
    public function count()
    {
        $query=$this->build_query();

        return $this->document->doc_db->count($this->document,$query);
    }
}

/**
 * Convience function to create a finder.
 * @param  $document_name
 * @return Finder
 */
function finder($document_name)
{
	return Finder::Document($document_name);
}
