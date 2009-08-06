<?php
/**
 * Utility functions
 * 
 * @copyright     Copyright 2009-2012 Jon Gilkison and Massify LLC
 * @package       application
 * @subpackage	  core
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
  * Print's an object using pre tags and escaping entities for display in an html page.
  * Used for quick debugging.
  * 
  * @param mixed $data The data to print.
  */
function dump($data)
{
	print '<pre>';
	if (is_object($data) || is_array($data))
	{
		ob_start();
		print_r($data);
		$data=ob_get_clean();
	}
	
	print htmlentities($data);
	print '</pre>';
}


/**
 * Exactly like dump but dies right after.  You can optionally include trace as well.
 * For quick and dirty debugging.
 *
 * @param mixed $data The data to dump
 * @param bool $show_trace Determines if trace should be shown.
 */
function vomit($data,$show_trace=false)
{
	if ($data instanceof Model)
		dump($data->to_array());
	else
		dump($data);
	
	die;
}

/**
 * Tries to find any matching method in a given class.
 * 
 * @param $class string Name of the class
 * @return string The name of the found method.
 */
function find_methods($class)
{
	$args=array_slice(func_get_args(),1);
	foreach($args as $arg)
	{
		if (method_exists($class,$arg))
			return $arg;
	}
			
	return FALSE;
}

/**
 * Generates a unique id based on RFC 4122 sans dashes
 * 
 * @return string Returns a unique identifier
 */
function uuid() 
{
    return sprintf('%04x%04x%04x%03x4%04x%04x%04x%04x',
        mt_rand(0, 65535), 
        mt_rand(0, 65535),
        mt_rand(0, 65535),
        mt_rand(0, 4095),
        bindec(substr_replace(sprintf('%016b', mt_rand(0, 65535)), '01', 6, 2)),
        mt_rand(0, 65535), 
        mt_rand(0, 65535), 
        mt_rand(0, 65535) 
    ); 
}

/**
 * Generates a unique path for files to help with cache/cdn/spreading across filesystem
 *
 * @return string The generated path.  This path has not been created.
 */
function uuid_path($segments=3)
{
	$uid=uuid();
	$result='';
	
	if ($segments>strlen($uid)/2)
		$segments=strlen($uid)/2;

	for($i=0; $i<$segments; $i++)
		$result.=substr($uid,$i*2,2).'/';
		
	return $result;
}

/**
 * Ascends a file path by a given level.
 * 
 * @param string $path The file path
 * @param int $levels Number of levels to ascend
 * @return string
 */
function ascend_path($path,$levels)
{
	$dir=explode('/',$path);
	return implode(array_slice($dir,0,count($dir)-$levels),'/');
}


/** 
 * Strips non alpha numeric characters from a string, including spaces
 * 
 * @param string $string The string to process.
 * @return string description
 */
function clean_string($string)
{
	return strtolower(preg_replace("/[^a-zA-Z0-9]/", "", $string));
}

