<?
/**
 * Attribute Reader - A class for reading metadata from PHP classes
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

uses('system.app.dynamic_object');
uses('system.app.cache');

/**
 * 
 * @package		application
 * @subpackage	core
 * @link          http://wiki.getheavy.info/index.php/Attributes,_Metadata_and_Annotations
 */
class AttributeReader extends DynamicObject
{
	/**
	 * Constructor
	 *
	 * @param array $attributes
	 */
//	public function __construct($attributes)
//	{
//		if (!$attributes)
//			return;
//		
//		foreach($attributes as $key => $item)
//			if (is_array($item))
//				$this->props[$key]=new AttributeReader($item);
//			else
//				$this->props[$key]=$item;
//	}


   	/**
   	 * Parses YAML from the doc comments for a class or method, returning the yaml as a string
   	 *
   	 * @param string $doc_comment The doc comment parsed using PHP's reflection
   	 * @return string The parsed out YAML.
   	 */
	private static function ParseDocComments($doc_comment)
	{
		$comments=explode("\n",$doc_comment);
		
		$yaml='';
		$within=false;
		
		foreach($comments as $comment)
		{
			$line=substr(trim($comment),2);
			if (strpos($line,'[[')===0)
				$within=true;
			else if ($within)
			{
				if (strpos($line,']]')===0)
					break;
				else
					$yaml.=$line."\n";
			}
		}
		
		return $yaml;
	}
	
	/**
	 * Fetches the metadata for a method of a class
	 *
	 * @param mixed $class An instance of the class or it's name as a string
	 * @param string $method The name of the method 
	 * @return AttributeReader An attribute reader instance
	 */
	
	public static function MethodAttributes($class,$method_name)
	{
		if (!($class instanceof ReflectionClass))
			$class=new ReflectionClass($class);
			
		$method=new ReflectionMethod($class->getName(),$method_name);
		
	 	$cache=Cache::GetCache('attributes');
	 	
	 	$key='ca-'.$class->getFileName().'-'.$method_name;
 		$reader=$cache->get($key);
 				
 		if (!$reader)
 		{
			$yaml=AttributeReader::ParseDocComments($method->getDocComment());
			$reader=new AttributeReader(syck_load($yaml));

			$parent=$class->getParentClass();
			if ($parent)
			try
			{
				$pmeta=AttributeReader::MethodAttributes($parent,$method_name);
				$reader=$pmeta->merge($reader);
			}
	 		catch(ReflectionException $ex)
	 		{
	 		}
	 		
			$cache->set($key,$reader);			
 		}
		

 		return $reader;
	}
	
	/**
	 * Fetches the metadata for a class
	 *
	 * @param mixed $class An instance of a class or it's name as a string
	 * @return AttributeReader An attribute reader instance
	 */
	public static function ClassAttributes($class)
	{
		if (!($class instanceof ReflectionClass))
			$class=new ReflectionClass($class);
		
	 	$cache=Cache::GetCache('attributes');
	 	
	 	$key='cm-'.$class->getFileName();
 		$reader=$cache->get($key);
 				
 		if (!$reader)
 		{
			$yaml=AttributeReader::ParseDocComments($class->getDocComment());
			$reader=new AttributeReader(syck_load($yaml));
			
			$parent=$class->getParentClass();
			if ($parent)
			{
				$pmeta=AttributeReader::ClassAttributes($parent);
				$reader=$pmeta->merge($reader);
			}
	 		
			$cache->set($key,$reader);
 		}
 				
		
		return $reader; 
	}
	
	/**
	 * Fetches the metadata for a property of a class
	 *
	 * @param mixed $class An instance of the class or it's name as a string
	 * @param string $prop The name of the property 
	 * @return AttributeReader An attribute reader instance
	 */
	
	public static function PropertyAttributes($class,$prop_name)
	{
		if (!($class instanceof ReflectionClass))
			$class=new ReflectionClass($class);
			
		$prop=new ReflectionProperty($class->getName(),$prop_name);

		$cache=Cache::GetCache('attributes');
	 	
	 	$key='cp-'.$class->getFileName().'-'.$prop_name;
 		$reader=$cache->get($key);
 				
 		if (!$reader)
 		{
			$yaml=AttributeReader::ParseDocComments($prop->getDocComment());
			
			$reader=new AttributeReader(syck_load($yaml));

			
			$parent=$class->getParentClass();
			if ($parent)
			{
				$pmeta=AttributeReader::PropertyAttributes($parent,$prop_name);
				$reader=$pmeta->merge($reader);
			}
			
			$cache->set($key,$reader);
 		}
		
 		

 		return $reader;		
	}
}