<?
uses('sys.mail.mailer');
uses('sys.app.view');

/**
 * Represents an email message.  This is an abstract class that should be implemented for specific
 * vendors.  For an idea of how to do this, see sys.mail.mail.mail_email
 *
 * @throws MailerException
 * 
 */
abstract class Email
{
    /**
     * @var string The MIME boundary for the main part of the email.
     */
    protected $boundary=null;

    public $from=null;              /** @var string Who the email is from. */
    public $to=array();             /** @var array Who the email is addressed to.  Multiple emails are concatenated together, not sent separately. */
    public $cc=array();             /** @var array Who to cc the email to. */
    public $bcc=array();            /** @var array Who to BCC th email to.  */
    public $subject;                /** @var string The subject of the email. */
    protected $headers=array();     /** @var array The mail headers. */
    protected $attachments=array(); /** @var array The attachments for the email, an array of file paths */

    protected $content=array();     /** @var array The array of content. */

    protected $conf;                /** @var Config The configuration */

    /**
     * Constructor
     * 
     * @param  $conf
     * @param array $to
     * @param array $cc
     * @param array $bcc
     * @param string $subject
     * @param string $plain_body
     */
    public function __construct($conf,$to=null,$cc=null,$bcc=null,$subject=null,$plain_body=null)
    {
        $this->conf=$conf;
        $this->boundary='HEAVYMETAL_'.md5(time());

        if ($conf->from)
            $this->from=$conf->from;

        if ($to)
        {
            if (is_array($to))
                $this->to=$to;
            else
                $this->to[]=$to;
        }

        if ($cc)
        {
            if (is_array($cc))
                $this->cc=$cc;
            else
                $this->cc[]=$cc;
        }

        if ($bcc)
        {
            if (is_array($bcc))
                $this->bcc=$bcc;
            else
                $this->bcc[]=$bcc;
        }

        if ($subject)
            $this->subject=$subject;

        if ($plain_body)
            $this->add_body($plain_body);
    }

    /**
     * Adds body to the content.
     * 
     * @param  $body
     * @param string $content_type
     */
    public function add_body($body,$content_type='text/plain')
    {
        $this->content[]=array(
            'content_type'=>$content_type,
            'body'=>$body
        );
    }

    /**
     * Renders a template and adds it to the content.
     *
     * @throws MailerException
     * @param  $template
     * @param  $data
     * @return void
     */
    public function add_template($template,$data)
    {
        $conf=Config::Get('mail');

        if (!$conf->templates->{$template})
            throw new MailerException("Missing '{$template}' in templates section of mail configuration.");

        $t=$conf->templates->{$template};

        foreach($t->views as $view)
            $this->add_view($t->root,$view->view,$data,$view->content_type);

        foreach($t->attachments as $attachment)
            $this->add_attachment($attachment);

    }

    /**
     * Renders a view and adds it to the content.
     * 
     * @param  $root
     * @param  $view
     * @param  $data
     * @param string $content_type
     * @return void
     */
    public function add_view($root,$view,$data,$content_type='text/plain')
    {
        $v=new View($view,null,PATH_APP.str_replace('.','/',$root).'/');
        $body=$v->render($data);
        $this->add_body($body,$content_type);
    }

    /**
     * Adds an attachment.
     * 
     * @param  $filepath
     * @return void
     */
    public function add_attachment($filepath)
    {
        $this->attachments[]=$filepath;
    }

    /**
     * Builds the headers for the email.
     * 
     * @return string
     */
    protected function build_headers()
    {
         $headers=array();

         $headers[]="From: {$this->from}";
         $headers[]="Reply-To: {$this->from}";

         if (count($this->cc)>0)
             $headers[]="CC: ".implode(', ',$this->cc);

         if (count($this->bcc)>0)
             $headers[]="BCC: ".implode(', ',$this->cc);

         $headers[]="MIME-Version: 1.0";

        if (count($this->attachments)>0)
            $headers[]="Content-Type: multipart/mixed;";
        else
            $headers[]="Content-Type: multipart/alternative;";
         $headers[]="        boundary={$this->boundary}";
         $headers=array_merge($headers,$this->headers);

         return implode(PHP_EOL,$headers);
    }

    /**
     * Builds the body for the email.
     * 
     * @return string
     */
    protected function build_body()
    {
        $message=array();

        $boundary=$this->boundary;
        
        if (count($this->attachments)>0)
        {
            $boundary='HEAVYMETAL_'.uuid();

            $message[]="--{$this->boundary}";
            $message[]="Content-Type: multipart/alternative;";
            $message[]="        boundary={$boundary}";
            $message[]="";
        }
        
        foreach($this->content as $content)
        {
            $message[]="--{$boundary}";
            $message[]="Content-Transfer-Encoding: 8bit";
            $message[]="Content-Type: {$content['content_type']};";
            $message[]="        charset=UTF-8";
            $message[]="";
            $message[]=$content['body'];
            $message[]="";
            $message[]="";
        }

        $message[]="--{$boundary}--";
        $message[]="";

        if (count($this->attachments)>0)
        {
            $message[]=$this->build_attachments();
        }

        return implode(PHP_EOL,$message);
    }

    /**
     * @return null|string
     */
    protected function build_attachments()
    {
        $result=array();

        if(count($this->attachments)==0)
            return;

        foreach($this->attachments as $file)
        {
            if (!file_exists($file))
            {
                $file=PATH_APP.$file;
                if (!file_exists($file))
                    continue;
            }

            $fname=array_pop(explode('/',$file));
            $data=file_get_contents($file);
            $content_id=uuid();

            $result[]="--{$this->boundary}";
            $result[]="Content-Type: ".mime_content_type($file)."; name=\"$fname\"";
            $result[]="Content-Transfer-Encoding: base64";
            $result[]="Content-ID: ".uuid();
            $result[]="Content-Disposition: attachment;";
            $result[]=" filename=\"$fname\"";
            $result[]="";
            $result[]=chunk_split(base64_encode($data),68,PHP_EOL);
            $result[]="";
        }

        if (count($result)==0)
            return null;

        $message[]="--{$this->boundary}--";
        $message[]="";

        return implode(PHP_EOL,$result);
    }

    /**
     * @abstract
     * @return void
     */
    abstract function send();
}