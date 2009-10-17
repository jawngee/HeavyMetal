<?
/**
 * Logs to a logfile
 */
class StdErrLogger extends Logger
{
   public function do_log($level,$category,$message)
   {
        $fp=fopen("php://stderr",'w');
        fwrite($fp,"[".getmypid()."] | $level | ".date('m/d/Y - h:i:s A T')." | $category | $message\n");
        fclose($fp);
   }
}