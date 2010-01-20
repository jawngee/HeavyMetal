<?
/**
 * Provides two-way keyed encoding using XOR Hashing and Mcrypt
 * 
 * @copyright     Copyright 2009-2012 Jon Gilkison and Trunk Archive Inc
 * @package       application
 * 
 * Original code: Rick Ellis
 * http://codeigniter.com/user_guide/license.html
 * 
 * Modified to be more heavymetal-ish by Jon Gilkison.  Made mcrypt a requirement.
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

uses('sys.app.config');

/**
 * Provides two-way keyed encoding using XOR Hashing and Mcrypt
 * 
 * @package		application
 * @subpackage	utility
 * @link          http://wiki.getheavy.info/index.php/Encryption
 */
class Encryption
{
	private $key	= '';
	private $hash	= 'sha1';
	private $mode	= MCRYPT_MODE_ECB;
	private $cipher	= MCRYPT_RIJNDAEL_256;
	
	/**
	 * Constructor
	 */
	function __construct($key=null,$hash='sha1',$cipher=MCRYPT_RIJNDAEL_256)
	{
		if ($key==null)
		{
			$config=Config::Get('encryption');
			$this->key=md5($config->key);
			$this->hash=$config->hash ? $config->hash : $hash;
			$this->cipher=$config->cipher ? constant($config->cipher) : MCRYPT_RIJNDAEL_256;
		}

		$this->key=md5($config->key);
		$this->hash=$config->hash ? $config->hash : $hash;
	}

	/**
	 * Encode
	 *
	 * Encodes the message string using bitwise XOR encoding.
	 * The key is combined with a random hash, and then it
	 * too gets converted using XOR. The whole thing is then run
	 * through mcrypt (if supported) using the randomized key.
	 * The end result is a double-encrypted message string
	 * that is randomized with each call to this function,
	 * even if the supplied message and key are the same.
	 *
	 * @access	public
	 * @param	string	the string to encode
	 * @param	string	the key
	 * @return	string
	 */
	function encode($string, $key = null)
	{
		if (!$key)
			$key = $this->key;
			
		$enc = $this->_xor_encode($string, $key);
		
		$init_size = mcrypt_get_iv_size($this->cipher, $this->mode);
		$init_vect = mcrypt_create_iv($init_size, MCRYPT_RAND);
		$enc=mcrypt_encrypt($this->cipher, $key, $enc, $this->mode, $init_vect);
		
		return base64_encode($enc);		
	}
  	
	/**
	 * Decode
	 *
	 * Reverses the above process
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	function decode($string, $key = '')
	{
		if (!$key)
			$key = $this->key;
		
		$dec = base64_decode($string);
		
		 if ($dec === FALSE)
		 	return FALSE;
		 	
		$init_size = mcrypt_get_iv_size($this->cipher, $this->mode);
		$init_vect = mcrypt_create_iv($init_size, MCRYPT_RAND);
		$dec=rtrim(mcrypt_decrypt($this->cipher, $key, $dec, $this->mode, $init_vect), "\0");
		 	
		return $this->_xor_decode($dec, $key);
	}
  	
	/**
	 * XOR Encode
	 *
	 * Takes a plain-text string and key as input and generates an
	 * encoded bit-string using XOR
	 *
	 * @access	private
	 * @param	string
	 * @param	string
	 * @return	string
	 */	
	function _xor_encode($string, $key)
	{
		$rand = '';
		while (strlen($rand) < 32)
			$rand .= mt_rand(0, mt_getrandmax());

		$rand = $this->hash($rand);
		
		$enc = '';
		for ($i = 0; $i < strlen($string); $i++)
			$enc .= substr($rand, ($i % strlen($rand)), 1).(substr($rand, ($i % strlen($rand)), 1) ^ substr($string, $i, 1));
				
		return $this->_xor_merge($enc, $key);
	}
  	
	/**
	 * XOR Decode
	 *
	 * Takes an encoded string and key as input and generates the
	 * plain-text original message
	 *
	 * @access	private
	 * @param	string
	 * @param	string
	 * @return	string
	 */	
	function _xor_decode($string, $key)
	{
		$string = $this->_xor_merge($string, $key);
		
		$dec = '';
		for ($i = 0; $i < strlen($string); $i++)
			$dec .= (substr($string, $i++, 1) ^ substr($string, $i, 1));
	
		return $dec;
	}
  	
	/**
	 * XOR key + string Combiner
	 *
	 * Takes a string and key as input and computes the difference using XOR
	 *
	 * @access	private
	 * @param	string
	 * @param	string
	 * @return	string
	 */	
	function _xor_merge($string, $key)
	{
		$hash = $this->hash($key);
		$str = '';
		for ($i = 0; $i < strlen($string); $i++)
			$str .= substr($string, $i, 1) ^ substr($hash, ($i % strlen($hash)), 1);

		return $str;
	}

  	
	/**
	 * Hash encode a string
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */		
	function hash($str)
	{
		return ($this->hash == 'sha1') ? sha1($str) : md5($str);
	}
	
}
