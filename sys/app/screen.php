<?
/**
 * Abstract base class for controller screens.
 * 
 * @copyright     Copyright 2009-2012 Jon Gilkison and Trunk Archive Inc
 * @package       application
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
 */

uses('system.app.attribute_reader');

/**
 * Abstract base class for screens for controllers.  Screens are invoked before
 * and after a method on a controller is called.
 * 
 * @package		application
 * @subpackage	controller
 * @link          http://wiki.getheavy.info/index.php/Screens
 */
abstract class Screen
{
	private static $_screens=array();
	
	/**
	 * Runs a screen.
	 * 
	 * @param string $which Which screen to run, "before" or "after"
	 * @param Controller $controller The controller
	 * @param AttributeReader $method_meta The method metadata
	 * @param Array $data Array of data that the screen(s) can add to.
	 */
	public static function Run($which,$controller,$method_meta,&$data,&$args)
	{
		$c=$controller->metadata->{$which};
		$m=$method_meta->{$which};
		
		if (!$c) $c=new AttributeReader();
		if (!$m) $m=new AttributeReader();
		
		$screens=$c->merge($m);
		foreach($screens as $name=>$screen)
		if (!$screen->ignore)
		{
			if (isset(self::$_screens[$name]))
				$s=self::$_screens[$name];
			else
			{
				uses('screen.'.$name);
				$class=$name.'Screen';
				$s=new $class();
				self::$_screens[$name]=$s;
			}
			
			$s->{$which}($controller, $screen, $data,$args);
		}
	}
	
	/**
	 * Called before a controller's method is called.
	 * 
	 * @param Controller $controller The controller
	 * @param AttributeReader $method_meta The method metadata
	 * @param Array $data Array of data that the screen(s) can add to.
	 */
	public function before($controller,$metadata,&$data,&$args) {  }
	
	/**
	 * Called after a controller's method is called.
	 * 
	 * @param Controller $controller The controller
	 * @param AttributeReader $method_meta The method metadata
	 * @param Array $data Array of data that the screen(s) can add to.
	 */
	public function after($controller,$metadata,&$data,&$args) {  }
}