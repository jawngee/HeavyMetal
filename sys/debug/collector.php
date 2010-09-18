<?
class Collector
{
	public static $start_time=0;

	public static $log=array();
	public static $queries=array();
	public static $views=array();
	public static $errors=array();
	
	public static function Init()
	{
		self::$start_time=microtime(true);
		
		set_error_handler('Collector::Error');
		set_exception_handler('Collector::Exception');
	}

	public static function Error($errno,$errstr, $errfile, $errline)
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
	
		// create a hash of the error
//		$error=md5($errfile.$errline.$errstr);
	
		self::$errors[]=array(
			"errno" => $errno,
			"errstr" => $errstr,
			"errfile" => $errfile,
			"errline" => $errline
		);
		
		return true;
	}
	
	public static function Exception($exception) 
	{
		vomit($exception);
		throw $exception;
	}
	
	public static function Trace($category,$message)
	{
		self::$log[]=array(
			"time" => microtime(true)-self::$start_time,
			"category" => $category,
			"message" => $message,
			"backtrace" => debug_backtrace()
		);
	}
	
	public static function StartQuery($query,$values)
	{
		self::$queries[]=array(
			"backtrace" => debug_backtrace(),
			"query" => $query,
			"values" => $values,
			"time" => microtime(true)-self::$start_time,
			"start" => microtime(true)
		);		
	}
	
	public static function EndQuery($query)
	{
		$last=count(self::$queries)-1;
		
		self::$queries[$last]['duration']=microtime(true)-self::$queries[$last]["start"];
	}
	
	public static function View($view,$content)
	{
		self::$views[]=array(
			"backtrace" => debug_backtrace(),
			"view" => $view,
			"content" => $content
		);		
	}
	
	public static function Config()
	{
		
	}
	
	public static function Screen()
	{
		
	}
}

function trace($category,$message) 
{
	Collector::Trace($category,$message);
}

Collector::Init();