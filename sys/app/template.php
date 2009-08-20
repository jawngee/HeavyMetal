<?
/**
 * A "buffered" view.  Useful for rendering a view repeatedly as it only reads the file from disk once.
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

/**
 * A "buffered" view.  Useful for rendering a view repeatedly as it only reads the file from disk once.
 * 
 * @package		application
 * @subpackage	view
 * @link          http://wiki.getheavy.info/index.php/Views
 */
class Template
{
	private $view_contents=null;
	public $data=null;
	
	/**
	 * Constructor
	 * 
	 * @param string $view The name of the view to use as the template.
	 */
	public function __construct($view)
	{
		
		$view=PATH_APP.'view/'.$view.EXT;
		
		if (!file_exists($view))
			throw new Exception("View file '$view' does not exist.");

		$contents=preg_replace("|{{([^}]*)}}|m",'<?=$1?>',file_get_contents($view));
	
		$this->view_contents=$contents;
	}
	
	/**
	 * Renders the template.
	 */
	public function render($data)
	{
		extract($data);
		ob_start();
		eval("?>".$this->view_contents);
		$result=ob_get_contents();
		@ob_end_clean();
		
		return $result;
	}
}