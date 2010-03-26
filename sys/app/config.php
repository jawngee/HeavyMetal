<?
/**
 * Configuration
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
	
/**
 * Thrown if there are configuration exceptions
 * 
 * @package		application
 * @subpackage	config
 */
class ConfigException extends Exception {}

/**
 * Thrown if there are configuration exceptions
 * 
 * @package		application
 * @subpackage	config
 */
class ConfigMissingException extends ConfigException {}

/**
 * Thrown if there are configuration exceptions
 * 
 * @package		application
 * @subpackage	config
 */
class ConfigInvalidFormatException extends ConfigException {}


/**
 * Configuration Reader.  Supports json or yaml formats. Config files are stored in app/conf/.
 * 
 * <code>
 * $config=Config::Get('nameofconfig');
 * </code>
 * 
 * @package		application
 * @subpackage	config
 * @link          http://wiki.getheavy.info/index.php/Configuration_and_Environments
 */
 class Config implements ArrayAccess, Iterator, Countable
 {
 	const FORMAT_YAML='yaml';
 	const FORMAT_JSON='json';
 	const FORMAT_PHP='php';
 	
 	/** Array of configuration items */
 	public $items=null;
 	
 	private $format=null;
 	
 	private $filename=null;
 	
 	/** Static array of preloaded configurations */
 	private static $_configs=array();
 	
 	/** Global environment configuration */
 	public static $environment_config=null;
 	
 	/** Global environment configuration */
 	public static $environment='';
 	
 	/** Config map */
 	private static $_config_map=array();
 	
 	/**
 	 * Constructor
 	 */
 	public function __construct($config,$filename=null,$format=null)
 	{
 		$this->format=$format;
 		$this->filename=$filename;
 		$this->items=$config;
 		
 		if(is_array($config))
    		foreach($config as $key => $item)
    			if (is_array($item))
    				$this->items[$key]=new Config($item);
 	}
 	
 	/**
	*  Callback method for getting a property
	*/
   	function __get($prop_name)
   	{
       if (isset($this->items[$prop_name]))
           return $this->items[$prop_name];
   	}

  	/**
	*  Callback method for setting a property
	*/
   	function __set($prop_name, $value)
   	{
   		$this->items[$prop_name]=$value;
   		return true;
   	}
   	
	/**
	 * Loads a configuration, or fetches a pre-loaded one from the cache.
	 * 
	 * @param string $what The name of the configuration to load.
	 */
 	public static function Get($what,$cached=false)
 	{	
 		if (isset(self::$_config_map[$what])) 
 			$what=self::$_config_map[$what];
 			
 		if (isset(self::$_configs[$what]))
 			return self::$_configs[$what];
 		else
 		{
 			if ($cached)
 			{
 				$cache=Cache::GetCache('conf');
 				$conf=$cache->get($what);
 				
 				if ($conf)
 				{
 					self::$_configs[$what]=$conf;
 					return $conf;
 				}
 			}
 			
 			$filename=PATH_CONFIG.$what;
 			$format=null;
 			
 			$data=null;
 			
 			if (file_exists($filename.'.js'))
 			{
 				$format="js";
				$data=json_decode(file_get_contents($filename.'.js'),true);
 			}
			else if (file_exists($filename.'.conf'))
 			{
 				$format="yaml";
				$data=syck_load(file_get_contents($filename.'.conf'));
 			}
			else if (file_exists($filename.'.php'))
			{
 				$format="php";
				ob_start();
				$data = include($filename.$ext);
				ob_get_clean();
			}
			else
				throw new ConfigMissingException("Missing Config File '$what'.");
			
			if (!is_array($data) && ($data!=null))
				throw new ConfigInvalidFormatException("Invalid Config Format '$what'.");
			
			$conf=new Config($data,$filename,$format);
 			self::$_configs[$what]=$conf;

 			if ($cached)
 				$cache->set($what,$conf);
 			
 			return $conf;
 		}
 	}
 
  	
 	/** 
 	 * Loads a specific environment, or the default one specified in the .conf
 	 * 
 	 * @param string $env The environment to load
 	 */
 	public static function LoadEnvironment($env=null)
 	{
 		$config=Config::Get('environment');
 		
 		if ($env==null)
 			$env=$config->environment;
		
		self::$environment=$env;
		
 		if ($config->{$env}!=null)
 		{
 			self::$environment_config=$config->{$env};
 			
 			// if in debug, load the developer's custom environment and merge it.
 			if ($env=='debug')
			{
				try
				{
					$your_config=Config::Get('environment.user');
					
					foreach($your_config->items as $key=>$custom)
						if (isset(self::$environment_config->items[$key]))
							foreach($custom->items as $k => $v)
								self::$environment_config->items[$key]->items[$k]=$v;
						else
							self::$environment_config->items[$key]=$custom;
				} 
				catch (Exception $ex)
				{
					// do nothing, means your_environment doesn't exist.	
				}
			}
			
 			if (self::$environment_config->defines!=null)
 				foreach(self::$environment_config->defines->items as $key => $value)
 				{
 					define($key,$value);
 				}

 			if (self::$environment_config->uses!=null)
 				foreach(self::$environment_config->uses->before->items as $item)
 					uses($item);
 					
 			if (self::$environment_config->config_map!=null)
 				self::$_config_map=self::$environment_config->config_map->items;
 					
 			define('ENVIRONMENT',$env);
 		}
 	}
 	
	/** 
 	 * Loads a specific environment, or the default one specified in the .conf
 	 * 
 	 * @param string $env The environment to load
 	 */
 	public static function ShutdownEnvironment()
 	{
		if ((self::$environment_config->uses!=null) && (self::$environment_config->uses->after!=null))
			foreach(self::$environment_config->uses->after->items as $item)
				uses($item);
 	}

 
 	/**
	 * Array access
	 *
	 * @param unknown_type $offset
	 * @return unknown
	 */
	function offsetExists($offset)
	{
		return isset($this->items[$offset]);
	}
	
	/**
	 * Array access
	 *
	 * @param unknown_type $offset
	 * @return unknown
	 */
	function offsetGet($offset)
	{
		if (!isset($this->items[$offset]))
			return null;
			
		return $this->items[$offset];
	}
	
	/**
	 * Array access
	 *
	 * @param unknown_type $offset
	 * @return unknown
	 */
	function offsetUnset($offset)
	{
		unset($this->items[$offset]);
	}
	
	/**
	 * Array access
	 *
	 * @param unknown_type $offset
	 * @return unknown
	 */
	function offsetSet($offset,$value)
	{
		$this->items[$offset]=$value;	
	}
	
    /**
	 * Iterator implementation
     */
	public function key()
    {
        return key($this->items);
    }

    /**
	 * Iterator implementation
     */
    public function current()
    {
        return current($this->items);
    }

    /**
	 * Iterator implementation
     */
    public function next()
    {
        return next($this->items);
    }

    /**
	 * Iterator implementation
     */
    public function rewind()
    {
        return reset($this->items);
    }

    /**
	 * Iterator implementation
     */
    public function valid()
    {
        return (bool) $this->current();
    }
    
    /**
     * Countable interface
     */
     public function count()
     {
     	return count($this->items);
     }
     
     /**
      * Returns the configuration as an associative array.
      * @return array The configuration as an associative array.
      */
     public function to_array()
     {
     	$result=array();
     	foreach($this->items as $key => $value)
     		if ($value instanceof Config)
     			$result[$key]=$value->to_array();
     		else
     			$result[$key]=$value;
     			
     	return $result;
     }

    /**
     * Saves the configuration
     * 
     * @param string $name The name of the file to save to, optional
     * @param string $format The format to save the file in, optional
     */
 	public function save($name=null,$format=null)
 	{
 		$filename=($name) ? PATH_CONFIG.$name : $this->filename;
 		$format=($format) ? $format : $this->format;
 		
 		$conf=$this->to_array();
 		
 		switch($format)
 		{
 			case 'js':
 				file_put_contents($filename.'.js',json_encode($conf));
 				break;
 			case 'yaml':
 				file_put_contents($filename.'.conf',syck_dump($conf));
 				break;
 			default:
 				throw new Exception("Invalid format for saving.");
 		}
 	} 
 }