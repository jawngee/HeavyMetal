<?
/**
 * Base class for view controls.
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
	
	
uses('system.app.controller');
uses('system.app.layout');
uses('system.app.session');

/**
 * Control is the base class for all ui controls.
 * 
 * @package		application
 * @subpackage	view
 * @link          http://wiki.getheavy.info/index.php/Controls
 */
 abstract class Control
 {
  	/** ID of the control */
 	public $id='';

	/** Controls visibility of control */
 	public $visible=true;

 	/** The owning layout */
 	public $layout=null;
 	
 	/** The owning controller */
 	public $controller=null;
 	
 	/** Parsed sub content */
 	protected $content=null;
 	
 	/** Session **/
 	public $session=null;
 	
 	/** URI **/
 	public $uri=null;
 	
 	/** Stores the attributes passed in from the view */
 	public $attributes=array();
 	
	/** 
	 * Constructor
	 * 
	 * @param Component Parent component, null if none.
	 */
	public function __construct(View $view=null, $content=null)
	{
		$this->view=$view;
		if ($view)
			$this->controller=$view->controller;
			
		$this->session=Session::Get();
		$this->content=$content;
	}
	
 	/**
 	 * Called after all controls have been loaded by the parent.
 	 */
	public function init()
	{
		$this->layout=Layout::MasterLayout();
	}

	/**
	 * Builds the control's content
	 * 
	 * @return string The built content
	 */
	abstract function build();
	
	
	/**
	 * Renders the control by building it
	 * 
	 * @return string The rendered control
	 */
 	public function render()
 	{
 		if ($this->visible==false)
 			return '';
 			
	 	$result=$this->build();

	 	return $result;
 	}
 	
 	/**
 	 * Renders and displays the control
 	 */
 	public function display()
 	{
 		echo $this->render();
 	}
  }