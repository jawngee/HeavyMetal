<?
/**
 * Logs to a logfile
 */
class StdOutLogger extends Logger
{
   public function do_log($level,$category,$message)
   {
        $fp=fopen("php://stdout",'w');
        fwrite($fp,"[".getmypid()."] | $level | ".date('m/d/Y - h:i:s A T')." | $category | $message\n");
        fclose($fp);
   }
}