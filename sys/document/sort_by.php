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

uses('system.document.sort');

/**
 * Contains a list of sort orders for a document finder
 */
class SortBy
{
    /**
     * The name of the class to use for sorts
     * @var string
     */
    protected $sort_class='Sort';
    
	public $sorts=array();		/** List of sort orders */	
	private $document=null;		/** Reference to the finder's document */
	private $finder=null;		/** Reference to finder object */
	
	/**
	 * Constructor
	 * 
	 * @param Document $document A reference to the document being filtered/sorted
	 */
	public function __construct($finder,$document)
	{
		$this->finder=$finder;
		$this->document=$document;
	}
	
	/**
	 * Allows us to declare ordering by requesting "properties" of the object by field name.
	 * When a property is requested, a new Order class is created for the requested
	 * field, or a pre-existing one is returned if it already exists.
	 */
	function __get($prop_name)
   	{
   		if (isset($this->sorts[$prop_name]))
   			$result=&$this->sorts[$prop_name];
   		else if (array_key_exists($prop_name,$this->document->doc_fields))
	   		$result=new $this->sort_class($this->finder,$prop_name);
   		else
	   		$result=new $this->sort_class($this->finder,$prop_name,true);
   		
   		$this->sorts[$prop_name]=&$result;
   		return $result;
   	}

   	/**
   	 * Clears out the sorts
   	 */
   	function clear()
   	{
   		$this->sorts=array();
   	}
}
 