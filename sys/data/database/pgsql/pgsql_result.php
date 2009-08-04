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

/**
 * Wraps a postgresql resultset for streaming through iterating
 */
class PGSQLResult implements Iterator
{
	private $result=null;
	
	private $cindex=0;
	
	private $last=null;
	
	public $count=0;
	
	public function __construct($result)
	{
		$this->result=$result;
		$this->count=pg_num_rows($result);
	}
	
	/**
	 * Iterator impl.
	 */
    public function key()
    {
        return $this->cindex;
    }

	/**
	 * Iterator impl.
	 */
    public function current()
    {
    	return $this->last;
    }

	/**
	 * Iterator impl.
	 */
    public function next()
    {
    	$this->last=pg_fetch_assoc($this->result);
    	
    	if (!$this->last)
    		$this->cindex++;
    	return $this->last;
    }

	/**
	 * Iterator impl.
	 */
    public function rewind()
    {
    	$this->cindex=0;
    	pg_result_seek($this->result,0);
    	$this->last=pg_fetch_assoc($this->result);
    	return $this->last;
    }

	/**
	 * Iterator impl.
	 */
    public function valid()
    {
        return ($this->last!=null);
    }	
    
    public function to_array()
    {
    	pg_result_seek($this->result,0);
    	return pg_fetch_all($this->result);
    }
}