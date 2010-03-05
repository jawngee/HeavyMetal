<?
/**
 * Contains error handling for production systems.
 *
 * @author		user
 * @date		Jun 2, 2007
 * @time		1:33:17 AM
 * @file		production.php
 * @copyright  Copyright (c) 2007 massify.com, all rights reserved.
 */

/**
 * Dummy function for dump.  Does nothing.
 */
function dump($data){}

/**
 * Dummy function for debug.  Does nothing.
 */
function trace($category,$message){}

/**
 * Global exception handler
 */
function exception_handler($exception)
{
	// TODO: Error logging via email, page redirect on error
	try
	{
		uses_system('mail/mail');
		$session=Session::Get();

		email('error/exception','donotreply@massify.com','wtf@massifycorp.com','[EXCEPTION] '.$exception->getMessage(),array('session'=>$session,'exception'=>$exception));
	}
	catch(Exception $ex)
	{
	}

	include PATH_PUB.'ohnoes.html';
}


// set the default exception handler
set_exception_handler('exception_handler');
