<?php
/**
 * HTTP utility functions
 * 
 * @copyright     Copyright 2009-2012 Jon Gilkison and Trunk Archive Inc
 * @package       application
 * @subpackage	  core
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
 * Sets the content type
 * 
 * @param string $type The content type.
 */
function content_type($type)
{
	@header("Content-type:$type");
}


/**
 * Redirects a request to another resource
 * 
 * @param string $where Where to redirect to
 */
function redirect($where)
{
	@header("Location:$where");
	die;
}

/**
 * Set cookie
 *
 * Accepts six parameter, or you can submit an associative
 * array in the first parameter containing all the values.
 *
 * @param	string	the value of the cookie
 * @param	string	the number of seconds until expiration
 * @param	string	the cookie domain.  Usually:  .yourdomain.com
 * @param	string	the cookie path
 * @param	string	the cookie prefix
 * @return	void
 */
function set_cookie($name = '', $value = '', $expire = '', $domain = '', $path = '/', $prefix = '')
{
	if (!is_numeric($expire))
		$expire = time() - 86500;
	else
	{
		if ($expire > 0)
			$expire = time() + $expire;
		else
			$expire = 0;
	}
	
	if (!defined('SESSION_DISABLED'))
		@setcookie($prefix.$name, $value, $expire, $path, $domain, 0);
}

/**
 * Delete a COOKIE
 *
 * @param	mixed
 * @param	string	the cookie domain.  Usually:  .yourdomain.com
 * @param	string	the cookie path
 * @param	string	the cookie prefix
 * @return	void
 */
function delete_cookie($name = '', $domain = '', $path = '/', $prefix = '')
{
	set_cookie($name, '', '', $domain, $path, $prefix);
}

	
/**
 * Fetch an item from the COOKIE array
 *
 * @param	string
 * @param	bool
 * @return	mixed
 */
function get_cookie($index = '')
{
	if (!isset($_COOKIE[$index]))
		return FALSE;

	if (is_array($_COOKIE[$index]))
	{
		$cookie = array();
		foreach($_COOKIE[$index] as $key => $val)
			$cookie[$key] = $val;
	
		return $cookie;
	}
	else
		return $_COOKIE[$index];
}