<?
/**
 * Logs to a logfile
 */
class LogFileLogger extends Logger
{
   public function do_log($level,$category,$message)
   {
	error_log("[".getmypid()."] | $level | ".date('m/d/Y - h:i:s A T')." | $category | $message\n",3,$this->target);
   }
}