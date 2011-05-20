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


/**
 * Contains a sort order for a finder
 */
class Sort
{
    /**
     * The value the document store uses for ASC
     * @var string
     */
    protected $asc_value='1';

    /**
     * The value the document store uses for DESC
     * @var string
     */
    protected $desc_value='-1';
    
	public $field;				/** Name of the field being sorted on */
	public $direction='1';		/** Direction of the sort */
	public $finder=null;        /** Instance of the finder that owns this.
	
	/**
	 * Constructor
	 * 
	 * @param string $field Name of the field being sorted on
	 */
	public function __construct($finder,$field)
	{
		$this->finder=$finder;
		$this->field=$field;
	}

	/**
	 * Overridden magic method.  Allows the properties 'asc' and 'desc' to be "retrieved".  When they are
	 * requested as properties, they set the direction state of the object.
	 */
   	function __get($prop_name)
   	{
   		if ($prop_name=='asc')
   			$this->direction=$this->asc_value;
   		else if ($prop_name=='desc')
   			$this->direction=$this->desc_value;
   		else
   			throw new ModelException("Field '$prop_name' is an unknown sort order.");

   		return $this->finder;
   	}
}

 