<?
uses('sys.app.view');

class MailerException extends Exception {}
/**
 * The Mailer is a class that simplifies sending email and email templates.  It abstracts the
 * underlying mail delivery system (php's mail, postfix, smtp, amazon ses, etc.).  It's fairly
 * heavy config based.
 *
 * Sending a simple text email:
 *
 * <code>
 * uses('sys.mail.mailer');
 *
 * Mailer::Send('default','someone@somewhere.com',null,null,'Subject','Body');
 * </code>
 *
 * The first parameter specifies the named configuration in the mail.conf configuration file.
 * This configuration, in it's most basic form, looks like:
 *
 * <code>
 * mailers:
 *   default:
 *     from: "Do Not Reply <shh@donotreply.com>"
 *     class: sys.mail.mail.mail_email
 * </code>
 *
 * The top level element is the container for all mailer configurations.  This allows you to have
 * multiple configurations for different needs.  For example, using php's mail() function for
 * error alerts and using Amazon's SES service for application to user emails.
 *
 * The second level element is the name of the configuration.
 *
 * Within that element you must specify the email address to send from and the class to use for
 * sending the email.  You can also specify just the driver name, in this case it would simply be
 * 'mail':
 *
 * <code>
 * mailers:
 *   default:
 *     from: "Do Not Reply <shh@donotreply.com>"
 *     class: mail
 * </code>
 *
 * The following are valid classes:
 *
 *   * mail - sys.mail.mail.mail_email (usess PHP's mail() function)
 *   * ses - sys.mail.ses.ses_email (uses Amazon SES)
 *
 * Additionally you can add support for other senders by creating descendant of the Email class.  Typically, you'll only
 * have to override the constructor and the send method.
 *
 * In addition to sending simple emails, you can also send out templates.  Templates are specified in the configuration
 * in the template section:
 *
 * <code>
 * mailers:
 *   default:
 *     from: "Do Not Reply <shh@donotreply.com>"
 *     class: mail
 * templates:
 *   new_user:
 *     root: app.mail.user
 *     views:
 *       - view: new_user.text
 *         content_type: text/plain
 *       - view: new_user.html
 *         content_type: text/html
 *     attachments:
 *       - 'mail/user/attachment/new_user.pdf'
 *       - 'mail/user/attachment/new_user.pdf'
 *       - 'mail/user/attachment/new_user.pdf'
 * </code>
 *
 * In the example above, we have one template named 'new_user' defined.
 *
 * The 'root' element in the template's definition is the namespace where the views reside.
 *
 * The 'views' element contains a list of all of the views that this template uses.  Each template can have multiple views
 * which allows you to build multipart emails very easily.  For example, you'd have one for plain text, one for html.
 *
 * THe 'attachments' element allows you to specify attachments that should be included with the email.
 *
 * To send a template, first make sure they are defined in your config and then:
 *
 * <code>
 * uses('sys.mail.mailer');
 *
 * Mailer::SendTemplate('default','new_user','someone@somewhere.com',null,null,'Subject',array('somedata'=>'yes'));
 * </code>
 *
 * @throws MailerException
 *
 */
class Mailer
{
    /**
     * Creates a new Email message.
     * 
     * @static
     * @throws MailerException
     * @param string $mailer The name of the mailer configuration to use
     * @param mixed $to Who the email is sent to
     * @param mixed $cc Who the email is CC'd to
     * @param mixed $bcc Who the email is BCC'd to
     * @param string $subject The subject of the email
     * @return Email
     */
    public static function NewMessage($mailer,$to=null,$cc=null,$bcc=null,$subject=null)
    {
        $conf=Config::Get('mail');

        if (!$conf->mailers->{$mailer})
            throw new MailerException("Missing '{$mailer}' in mail configuration.");

        $class=$conf->mailers->{$mailer}->class;
        if (!$class)
            throw new MailerException("Missing class in '{$mailer}' configuration.");

        $components=explode('.',$class);

        if (count($components)==0)
            throw new MailerException("Invalid driver in '{$mailer}' configuration.");

        if (count($components)==1)
        {
            $uses='sys.mail.'.$class.'.'.$class.'_email';
            $class=$class.'Email';
        }
        else
        {
            $uses=$class;
            $class=str_replace('_','',array_pop($components));
        }

        uses($uses);
        return new $class($conf->mailers->{$mailer},$to,$cc,$bcc,$subject);
    }

    /**
     * Sends a template.
     *
     * @static
     * @param string $mailer The name of the mailer configuration to use
     * @param string $template The name of the template in the config to use
     * @param mixed $to Who the email is sent to
     * @param mixed $cc Who the email is CC'd to
     * @param mixed $bcc Who the email is BCC'd to
     * @param string $subject The subject of the email
     * @param array $data Array of data to be processed by the template's views
     * @param string $content_type The mime type of the email content
     * @param array $attachments Any additional attachments to include with this email
     * @return bool
     */
    public static function SendTemplate($mailer,$template,$to,$cc,$bcc,$subject,$data,$content_type='text/plain',$attachments=null)
    {
        $email=self::NewMessage($mailer,$to,$cc,$bcc,$subject);
        $email->add_template($template,$data,$content_type);

        if ($attachments)
            foreach($attachments as $attachment)
                $email->add_attachment($attachment);

        return $email->send();
    }

    /**
     * @static
     * @param string $mailer The name of the mailer configuration to use
     * @param mixed $to Who the email is sent to
     * @param mixed $cc Who the email is CC'd to
     * @param mixed $bcc Who the email is BCC'd to
     * @param string $subject The subject of the email
     * @param string $body The body of the message
     * @param string $content_type The mime type of the email content
     * @param array $attachments Any additional attachments to include with this email
     * @return bool
     */
    public static function Send($mailer,$to,$cc,$bcc,$subject,$body,$content_type='text/plain',$attachments=null)
    {
        $email=self::NewMessage($mailer,$to,$cc,$bcc,$subject);
        $email->add_body($body,$content_type);

        if ($attachments)
            foreach($attachments as $attachment)
                $email->add_attachment($attachment);

        return $email->send();

    }
}