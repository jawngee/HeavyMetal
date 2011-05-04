<?
uses('sys.mail.email');

/**
 * This class implements mail sending using PHP's mail() function.  Underneath it uses sendmail or postfix.
 */
class MailEmail extends Email
{
    /**
     * @return bool
     */
     public function send()
     {
         if (is_array($this->to))
             $to=implode(', ',$this->to);
         else
             $to=$this->to;

         $headers=$this->build_headers();
         $body=$this->build_body();
         
         return mail($to,$this->subject,$body,$headers);
     }
}