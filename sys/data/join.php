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
 * Join class, represents a join between two model filters.
 */
class Join
{
	public $column=null;			/** The column to join on */
	public $foreign_column=null;	/** Foreign column to join on */
	public $model=null;				/** Reference to the Model being joined on */
	public $filter=null;			/** Reference to the filter */
	public $kind='inner';
	public $filter_in_join=false;    /** Whether the filter clause is built as part of the join
	                                    or part of the where statement (default) */
	
	/**
	 * Constructor
	 * 
	 * @param string $column The column for the join
	 * @param string $foreign_column The foreign column for the join
	 * @param Filter $filter The filter for the join
	 */
	public function __construct($column,$filter,$foreign_column,$kind='inner',$filter_in_join=false)
	{
		$this->column=$column;
		$this->foreign_column=$foreign_column;
		$this->model=$filter->model;
		$this->filter=$filter;
		$this->kind=$kind;
		$this->filter_in_join = $filter_in_join;
	}
} 