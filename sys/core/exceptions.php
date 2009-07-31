<?php
/**
 * Core exceptions
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
 * Represents an error response.
 * 
 * @package		application
 * @subpackage	core
 */
abstract class ErrorResponseException extends Exception 
{
	/**
	 * Returns the error code
	 */
	abstract function error_code();
}


/**
 * 404 Not Found exception
 * 
 * @package		application
 * @subpackage	core
 */
class NotFoundException extends ErrorResponseException
{
	public function error_code() { return '404 Not Found'; }
}

/**
 * 400 Bad Request exception
 * 
 * @package		application
 * @subpackage	core
 */
class BadRequestException extends ErrorResponseException
{
	public function error_code() { return '400 Bad Request'; }
}

/**
 * 400 Bad Request exception
 * 
 * @package		application
 * @subpackage	core
 */
class InvalidParametersException extends ErrorResponseException
{
	public function error_code() { return '400 Bad Request'; }
}


/**
 * 410 Gone exception
 * 
 * @package		application
 * @subpackage	core
 */
class GoneException extends ErrorResponseException
{
	public function error_code() { return '410 Gone'; }
}


/**
 * 500 Internal Server Error exception
 * 
 * @package		application
 * @subpackage	core
 */
class InternalServerErrorException extends ErrorResponseException
{
	public function error_code() { return '500 Internal Server Error'; }
}

/**
 * Custom error response.
 * 
 * @package		application
 * @subpackage	core
 */
class CustomErrorResponseException extends ErrorResponseException
{
	public function error_code() { return $this->getCode().' '.$this->getMessage(); }
}

/**
 * Not allowed error response.
 * 
 * @package		application
 * @subpackage	core
 */
class NotAllowedException extends ErrorResponseException
{
	public function error_code() { return '405 Method Not Allowed'; }
}


