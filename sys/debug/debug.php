<?
/**
 * Debugging functions
 * 
 * @author		user
 * @date		Jun 2, 2007
 * @time		1:00:47 AM
 * @file		debug.php
 * @copyright  Copyright (c) 2007 massify.com, all rights reserved.
 */

/**
 * Print's an object using <pre> tags and escaping entities for display in an html page.
 * 
 * @param mixed $data The data to print.
 */
function dump($data,$level=0)
{
	$trace=debug_backtrace();
	
	print <<<EOD
	<p>Dump from '{$trace[$level]['file']}' at line #{$trace[$level]['line']}:</p>
EOD;
	
	if ($data instanceof Model)
		$data=$data->to_array();
	
		print '<pre>';
	if (is_object($data) || is_array($data))
	{
		ob_start();
		print_r($data);
		$data=ob_get_clean();
	}
	
	print htmlentities($data);
	print '</pre>';
}

/**
 * Exactly like dump but dies right after.  You can optionally include trace as well.
 *
 * @param mixed $data The data to dump
 * @param bool $show_trace Determines if trace should be shown.
 */
function vomit($data)
{
	dump($data,1);
	
	if ($show_trace)
		uses_system('debug/trace');
		
	die;
}

function vomit_ajax($data)
{
	$result='';
	if (is_object($data) || is_array($data))
	{
		ob_start();
		print_r($data);
		$data=ob_get_clean();
	}
	
	$result.=htmlentities($data);

	$result=str_replace('"',"'",$result);
	
	$result='console.log("'.$result.'");';
	
	print $result; 
	die;
}

// array of errors
$page_errors=array();

// error cache to remove duplicates
$error_cache=array();

// number of db hits
$db_hits=0;
$db_queries=array();

$loaded_views=array();

// debug log messages
$debug_log=array();

/** easier to read caller-chain */
function dump_call_trace()
{
    $s = '';

    $traceArr = debug_backtrace();
    array_shift($traceArr);
    $tabs = sizeof($traceArr)-1;
    foreach($traceArr as $arr)
    {
        for ($i=0; $i < $tabs; $i++) $s .= ' ';
        $tabs -= 1;
        if (isset($arr['class'])) $s .= $arr['class'].'.';
        $args = array();
        if(!empty($arr['args'])) foreach($arr['args'] as $v)
        {
            if (is_null($v)) $args[] = 'null';
            else if (is_array($v)) $args[] = 'Array['.sizeof($v).']';
            else if (is_object($v)) $args[] = 'Object:'.get_class($v);
            else if (is_bool($v)) $args[] = $v ? 'true' : 'false';
            else
            {
                $v = (string) @$v;
                $args[] = "\"".$str."\"";
            }
        }
        $s .= $arr['function'].'('.implode(', ',$args).')'; 
        $Line = (isset($arr['line'])? $arr['line'] : "unknown");
        $File = (isset($arr['file'])? $arr['file'] : "unknown");
        $s .= sprintf(" # line %4d, file: %s", $Line, $File);
        $s .= "\n";
    }   
    return dumpit($s);
}


/** 
 * Logs a debug message
 * 
 * @param string $category The message's category
 * @param string $message The message
 */
function trace($category,$message)
{
	global $debug_log;
	
//	error_log("$category:$message\n", 3, PATH_ROOT.'/tmp/px-error.log');
	
	
	$debug_log[]=array(
		"time" => microtime(true),
		"category" => $category,
		"message" => $message,
		"backtrace" => debug_backtrace()
	);
}

trace('system','Trace started.');


/**
 * Disables trace on a page.
 */
function disable_trace()
{
	define("DISABLE_TRACE",true);
}


/**
 * Debug error handler
 */
function error_handler($errno,$errstr, $errfile, $errline)
{
	if($errno==E_USER_ERROR)
	{
        echo "<b>ERROR</b> [$errno] $errstr<br />\n";
		echo "  Fatal error on line $errline in file $errfile";
		echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
		echo "Aborting...<br />\n";
    	
    	exit(1);
    
    	return;
	}
	
	// ignore adodb warnings	
	if ((strpos($errfile,"adodb")===false) && (strpos($errstr,"Deprecated")===false))
	{
		// create a hash of the error
		$error=md5($errfile.$errline.$errstr);
	
		global $error_cache;
		
		// if error isn't in cache ...
		if (!isset($error_cache[$error]))
		{
			global $page_errors;
			$page_errors[]=array(
				"errno" => $errno,
				"errstr" => $errstr,
				"errfile" => $errfile,
				"errline" => $errline
			);
				
			// add to cache so that we won't log the bug again
			$error_cache[$error]=1;
		}
		if (strpos($errstr,'require_once')===0)
		{
			uses_system('debug/trace');
			return;
		}
	}

	return true;
}

// set up the debug error handler
set_error_handler("error_handler");

function exception_handler($exception) {
	dump($exception);
  echo "Uncaught exception: " , $exception->getMessage(), "\n";
}

set_exception_handler("exception_handler");

// start output buffering so we can weigh the pages
ob_start();
//disable_trace();
