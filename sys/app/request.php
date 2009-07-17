<?
/**
 * Abstract representation of a request.
 * 
 * @copyright     Copyright 2009-2012 Jon Gilkison and Massify LLC
 * @package       application
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
 */

uses('sys.app.request.input');
uses('sys.app.request.uri');
uses('sys.app.request.query');

/**
 * Represents the request.
 * 
 * @package		application
 * @subpackage	request
 * @link          http://wiki.getheavy.info/index.php/Requests
 */
 class Request
 {
  	/**
 	 * Contains post-path uri segments and query values
  	 * 
  	 * @var URI
  	 */
 	public $uri=null;
 	
 	/**
 	 * Stores the input instance for post and get variables
 	 * 
 	 * @var Input
 	 */
 	public $input=null;
 	
 	/**
 	 * Stores the input instance for uploaded files
 	 *  
 	 * @var Input
 	 */
 	public $files=null;
 	
 	/**
 	 * Request method: GET, PUT, POST, DELETE, etc.
 	 *
 	 * @var string
 	 */
 	public $method=null;
 	
 	
	/**
	 * Constructor
	 * 
	 * @param string $method The request method
	 * @param string $root The root uri path
	 * @param array $segments The uri segments following the root path
	 */
 	public function __construct($root,&$segments)
 	{
 		$this->method='GET';
 		
 		$this->uri=new URI($root,$segments);
 		$this->query=new Query();
 		
 		// assign the get and post vars
 		$this->input=Input::Get();
 		$this->files=Upload::Files();
 	}
 
 
 	/**
 	 * Slings the request back to the referring URL if one exists, otherwise to another destination.
 	 * @param string $or_else The URI to redirect to if there isn't a referrer.
 	 */
 	public function slingback($or_else='/')
 	{
 		if ((isset($_SERVER['HTTP_REFERER'])) && ($_SERVER['HTTP_REFERER']!=null))
 			return redirect($_SERVER['HTTP_REFERER']);
 			
 		return redirect($or_else);
 	}
  	
 	/**
 	 * Redirects to another controller
 	 * 
 	 * @param string $where Where to redirect to.
 	 */
 	public function reroute($where,&$data=null)
 	{
 		Dispatcher::Dispatch($where,$data);
 		die;
 	}
 }