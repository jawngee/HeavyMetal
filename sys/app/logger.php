<?

/**
 * Holds log level constants
 */
class LogLevel
{
    const NOTICE='notice';
    const INFO='info';
    const WARNING='warning';
    const ERROR='error';
}

/**
 * Utility class for logging
 */
abstract class Logger
{
    private static $_loggers=array();
    
    protected $conf;
    protected $type;
    protected $target;
    protected $enabled;

    public function __construct($type,$target,$enabled,$conf=null)
    {
        $this->conf=$conf;
        $this->type=$type;
        $this->target=$target;
        $this->enabled=$enabled;
    }
    
    
    /**
     * Fetches a configured logger.
     *
     * @param string $name Name of the logger.
     * @return array An array of loggers
     */
    public static function GetLogger($name)
    {
        if (isset(self::$_loggers[$name]))
            return self::$_loggers[$name];
        else
        {
            $conf=Config::Get('logger');

            if (isset($conf->items[$name]))
            {
            	if (!$conf->items[$name]->enabled)
            		return null;
            		
            	$logtypes=$conf->items[$name]->output;
                $target=$conf->items[$name]->target;
                $enabled=$conf->items[$name]->enabled;
                
                $types=explode(',',$logtypes);
                foreach($types as $type)
                {
                    uses("system.app.driver.logger.$type");
                    $class=$type."Logger";
                        
                    $logger=new $class($type,$target,$conf->items[$name]);
                                
                    self::$_loggers[$name][]=$logger;
                }
                
                return self::$_loggers[$name];
            }

            return null;
        }
    }
    
   public static function Log($name,$level,$category,$message)
   {
        $loggers=Logger::GetLogger($name);
        foreach($loggers as $logger)
            $logger->do_log($level,$category,$message);
   }
   
   abstract function do_log($level,$category,$message);
}