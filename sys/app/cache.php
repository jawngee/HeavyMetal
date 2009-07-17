<?
uses('system.app.config');

/**
 * Abstract cache class and cache factory.
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
 * 
 * @package		application
 * @subpackage	cache
 * @link          http://wiki.getheavy.info/index.php/Cache
 */
abstract class Cache
{
	/**
	 * Static list of caches
	 * @var array
	 */
	private static $_caches=array();
	
	/**
	 * Configuration data
	 * @var Config
	 */
	private static $_conf=null;
	
	
	/**
	 * Fetches a named cache.
	 *
	 * @param string $name Name of the cache.
	 * @return Cache The specified cache, or a NullCache if one couldn't be found.
	 */
	public static function GetCache($name)
	{
		if (isset(self::$_caches[$name]))
			return self::$_caches[$name];

		if (!self::$_conf)
			self::$_conf=Config::Get('cache',false);

		if (self::$_conf->{$name})
		{
			uses('system.app.caches.'.self::$_conf->{$name}->driver);
					
			$class=self::$_conf->{$name}->driver."Cache";
			$cache=new $class(self::$_conf->{$name});
		}
		else
		{
			trigger_error("Could not find cache '$name' in configuration file.",E_USER_WARNING);
			uses('system.app.caches.null');
			$cache=NullCache();
		}

		self::$_caches[$name]=$cache;
		return $cache;
	}
	
	/**
	 * Constructor 
	 * 
	 * @param Config $config Configuration data, if any.
	 */
	public function __construct($config=null)
	{
		
	}

	/**
	 * Sets an item in the cache
	 * 
	 * @param string $key The key to set
	 * @param mixed $data The data to store
	 * @param int $ttl Time to live, amount of time to store in cache.  Zero = eternity.
	 */
	abstract function set($key,$data,$ttl=0);

	/**
	 * Gets an item from the cache
	 * 
	 * @param string $key The key to fetch
	 * @return mixed The data, if no data then null
	 */
	abstract function get($key);

	/**
	 * Deletes an object from the cache
	 */
	abstract function delete($key);
	
	/**
	 * Determines if the cache is enabled.
	 * 
	 * @return bool
	 */
	abstract function enabled();

}