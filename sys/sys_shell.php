<?
/**
 * System functionality for shell
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
 * Safely gets arguments from the environment trying a couple of different methods.
 * Taken from Pear:GetOpt by Andrei Zmievski <andrei@php.net>
 * 
 * @return array The parsed arguments
 */
function get_args()
{
	global $argv;
	if (!is_array($argv))
	{
		if (!@is_array($_SERVER['argv']))
		{
			if (!@is_array($GLOBALS['HTTP_SERVER_VARS']['argv']))
				throw new Exception("Could not read cmd args (register_argc_argv=Off?)");

			return $GLOBALS['HTTP_SERVER_VARS']['argv'];
		}
		
		return $_SERVER['argv'];
	}
    
    return $argv;
}

/**
 * Parses arguments into commands/options and switches
 */
function parse_args()
{
	$result=array();
	$args=array_slice(get_args(),1);
	$vals=array();
	for($i=0; $i<count($args);$i++)
		if(!preg_match('#--([^=]*)(?:[=]*)(.*)#',$args[$i],$vals))
			$result[]=$args[$i];

	return $result;
}

/**
 * Parses arguments into commands/options and switches
 */
function parse_switches()
{
	$result=array();
	$args=array_slice(get_args(),1);
	$vals=array();
	for($i=0; $i<count($args);$i++)
		if(preg_match('#--([^=]*)(?:[=]*)(.*)#',$args[$i],$vals)==1)
			$result[$vals[1]]=($vals[2]=='') ? true : $vals[2];			

	return $result;
}

/**
 * Handles SIG handlers
 */
$sigterm=false;
$sighup=false;

/**
 * SIG handler
 */
function sig_handler($signo) 
{
	global $sigterm, $sigup;
	
	if($signo == SIGTERM)
		$sigterm = true;
 	else if($signo == SIGHUP)
		$sighup = true;
}

/**
 * Forks the current process.
 */
function fork()
{
	ini_set("max_execution_time", "0");
	ini_set("max_input_time", "0");
	set_time_limit(0);
	
	pcntl_signal(SIGTERM, "sig_handler");
	pcntl_signal(SIGHUP, "sig_handler");
	pcntl_signal(SIGINT, "sig_handler");
	
	$pid = pcntl_fork();
	file_put_contents('php://stdout',$pid);
	if($pid == -1)
	    die("There is no fork()!");
	
	if($pid)
	{
	    echo($pid);
	    exit(0);
	}
}