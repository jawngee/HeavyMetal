<?
/**
 * Null cache, performs request level caching only.
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
 * Null cache, performs only request level caching.
 * 
 * @package		application
 * @link          http://wiki.getheavy.info/index.php/Cache
 * @subpackage	cache
 */
class NullCache extends Cache
{
	/**
	 * Static cache
	 * @var array
	 */
	private $cache=array();
	
	/**
	 * Is the cache enabled?
	 * @var bool
	 */
	private $enabled=true;
	
	
	/**
	 * Constructor 
	 * 
	 * @param Config $config Configuration data, if any.
	 */
	public function __construct($config=null)
	{
		if ($config)
			$this->enabled=$config->enabled;
	}

	/**
	 * Sets an item in the cache
	 * 
	 * @param string $key The key to set
	 * @param mixed $data The data to store
	 * @param int $ttl Time to live, amount of time to store in cache.  Zero = eternity.
	 */
	public function set($key,$data,$ttl=0)
	{
		if ($this->enabled)
			$this->cache[$key]=$data;
			
		return true;
	}

	/**
	 * Gets an item from the cache
	 * 
	 * @param string $key The key to fetch
	 * @return mixed The data, if no data then null
	 */
	public function get($key)
	{
		if (($this->enabled) && (isset($this->cache[$key])))
			return $this->cache[$key];
			
		return null;
	}

	/**
	 * Deletes an object from the cache
	 */
	function delete($key)
	{
		if (isset($this->cache[$key]))
			unset($this->cache[$key]);
	}
	
	/**
	 * Determines if the cache is enabled.
	 * 
	 * @return bool
	 */
	public function enabled()
	{
		return $this->enabled;
	}
}