<?
/**
 * Core functions and path definitions.
 * 
 * http://wiki.github.com/jawngee/HeavyMetal/sys-and-uses
 * 
 * @copyright     Copyright 2009-2012 Jon Gilkison and Massify LLC
 * @link          http://wiki.getheavy.info/index.php/Sys,_Namespaces_and_Includes
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
	
require_once 'core/exceptions.php';
require_once 'core/xss.php';
require_once 'core/utils.php';
require_once 'core/view.php';
require_once 'core/http_utils.php';


/**
 * Default script extension.
 * @var string
 */
define('EXT', '.php');			


/**
 * This function must be called before any thing else in the framework is used.
 * 
 * @param string $root_path Allows you to override the root path for your application.
 */
function init($root_path=null)
{
	/**
	 * Root path to system files.
	 * @var string
	 */
	define('PATH_SYS', dirname(__FILE__)."/");						
	
	if (!$root_path)
		$root_path=dirname(dirname(__FILE__)."..")."/";

	/**
	 * Root path
	 * @var string
	 */
	define('PATH_ROOT', $root_path); 							
	
	/**
	 * App path
	 * @var string
	 */
	define('PATH_APP', PATH_ROOT."app/");						
	
	/**
	 * Config file path
	 * @var string
	 */
	define('PATH_CONFIG', PATH_APP."conf/");					
	
	/**
	 * Temp file path
	 * @var string
	 */
	define('PATH_TEMP', PATH_ROOT."tmp/");						
	
	/**
	 * Vendor library file path
	 * @var string
	 */
	define('PATH_VENDOR', PATH_ROOT."vendor/");					
}


/**
 * Uses is a pretty wrapper around include and require.  Gives everything a more namespace vibe.
 * 
 * The first component of the uses, is one of the following path specifiers:
 * 
 * 	- app			Application path
 * 	- application	Application path
 *  - sys			The system path
 *  - system		The system path
 *  - vendor		The vendor path
 *  - model			The path to the app's models
 *  - channel		The path to the app's data channels
 *  - control		Path to either the system controls or the app controls
 *  				(Will try the system controls first, then the app controls) 
 * 
 * For example, to load the Controller class which is in /sys/app/controller.php you would
 * do the following:
 * 
 * <code>
 * uses('system.app.controller');
 * </code>
 * 
 * Can support wildcards:
 * 
 * <code>
 * uses('system.data.validators.*');
 * </code>
 * 
 * @param string $namespace
 */
function uses($namespace)
{
	$altpath=null;
	
	$parts=explode('.',$namespace);
	$type=array_shift($parts);
	$path='';
	
	switch($type)
	{
		case 'app':
		case 'application':
			$path=PATH_APP;
			break;
		case 'sys':
		case 'system':
			$path=PATH_SYS;
			break;
		case 'vendor':
			$path=PATH_VENDOR;
			break;
		case 'model':
			$path=PATH_APP.'model/';
			break;
		case 'channel':
			$path=PATH_APP.'channel/';
			break;
		case 'control':
			$path=PATH_SYS.'control/';
			$altpath=PATH_APP.'control/';
			break;
		case 'screen':
			$path=PATH_APP.'screen/';
			$altpath=PATH_SYS.'app.screen/';
			break;
	}
	
	
	if ($parts[count($parts)-1]=="*")
	{
		array_pop($parts);
		$namespace=implode('/',$parts);
		
		$files=array();
		
		$files=files($path.$namespace);
		foreach($files as $file)
			require_once($file);
	}
	else
	{
		$namespace=implode('/',$parts);
		
		if ($altpath)
			if (!file_exists($path.$namespace.EXT))
				$path=$altpath;
		
		require_once($path.$namespace.EXT);
	}
}

