<?
/**
 * Session
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


uses('sys.app.config');
uses('sys.utility.encryption');

/**
 * Session container.
 * 
 * Session in HeavyMetal is stored as secure cookie to ensure horizontal
 * scalability.  
 * 
 * @package		application
 * @subpackage	session
 * @link          http://wiki.getheavy.info/index.php/Session
 */
class Session
{
	private $name=null;
	private $domain=null;
	private $duration=null;
	private $config=null;
	private $salt='salt';
	
	// static instance
	private static $_instance=null;
	
	/**
	 * Contains all of the session data.
	 * 
	 * @var array
	 */
	public $data=array();
	
	/**
	 * Constructor
	 */
	public function __construct($config)
	{
		$this->config=$config;
		
		$this->domain=$config->domain;
		$this->duration=$config->duration;
		$this->name=$config->name;
		if ($config->salt)
			$this->salt=$config->salt;
			
		$this->load_session();
	}
	
	/**
	 * Fetches the current session.
	 * 
	 * @return Session The current session instance.
	 */
	public static function Get($session='default')
	{
		if (self::$_instance==null)
		{
			$aconfig=Config::Get('session');
			
			$config=$aconfig->{$session};
			
			if ($config->class)
			{
				uses($config->class);
				$class=array_pop(explode('.',$config->class)).'Session';
				
				if (!class_exists($class))
					throw new Exception("Could not find $class in {$config->class}.");
					
				self::$_instance=new $class($config);
			}
			else
				self::$_instance=new Session($config);
		}
		
		return self::$_instance;
	}

   
	/**
	*  Callback method for getting a property
	*/
   	function __get($prop_name)
   	{
		if (isset($this->data[$prop_name]))
           return $this->data[$prop_name];
		else
			return null;
   	}

   	/**
   	*  Callback method for setting a property
   	*/
   	function __set($prop_name, $prop_value)
   	{
   		if (($prop_value==null) && (isset($this->data[$prop_name])))
   			unset($this->data[$prop_name]);
   		else
			$this->data[$prop_name] = $prop_value;
			
		return true;
   	}
	
	/**
	 * Generates the auth ticket
	 */
	public function build_session()
	{
		// for development
		if (defined('ORIGIN_IP_ADDRESS'))
			$ipaddy=ORIGIN_IP_ADDRESS;
		else
			$ipaddy=(isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';

		$ticket=implode('|@@!@@|',array(serialize($this->data),(time()+$this->duration),$ipaddy));
		$ticket.="|@@!@@|".md5($ticket.$this->salt);

		$encrypter=new Encryption();
		$ticket=$encrypter->encode($ticket);

		return $ticket;
	}

	/**
	 * Generates the auth ticket and auto login cookies for a validated user
	 * 
	 * @param bool $remember_login Should the user's login info be remembered for auto-login?
	 */
	public function load_session($ticket=null,$origin_ip=null)
	{
		// get the ticket cookie
		if ($ticket==null)
			$ticket=get_cookie($this->name);
		
		if ($ticket==false)
			return;
			
		$encrypter=new Encryption();
		$cookie=$encrypter->decode($ticket);
		list($content,$time,$ip,$md5)=explode("|@@!@@|",$cookie);
		
		if ($origin_ip)
			$ipaddy=$origin_ip;
		else if (defined('ORIGIN_IP_ADDRESS'))
			$ipaddy=ORIGIN_IP_ADDRESS;
		else
			$ipaddy=(isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';
			
		$newmd5=md5(implode('|@@!@@|',array($content,$time,$ipaddy)).$this->salt);

		
		
		if (($content) && ($time>time()) &&	($ip==$ipaddy) && ($newmd5==$md5)) 
			$this->data=unserialize($content);
	}
	
	/**
	 * Saves the current session
	 */
	public function save()
	{
		$ticket=$this->build_session();
		// auth ticket is good for 20 minutes to prevent spoofing.
		set_cookie($this->name,$ticket,$this->duration,$this->domain);
	}
	
	/**
	 * Deletes the current session
	 */
	public function delete()
	{
		$this->data=array();
		delete_cookie($this->name);
	}
}