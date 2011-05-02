<?
uses('sys.cloud.event.event_manager');
uses('sys.cloud.provider.amazon.sns_request');

class SNSEventManager extends EventManager
{
	private $id=null;
	private $secret=null;
    
    public function __construct($conf,$dsn)
	{
        parent::__construct($conf,$dsn);

        $matches=array();
        if (preg_match_all('#([a-z]*)://([^@]*)@(.*)#',$dsn,$matches))
		{
            $this->id=$matches[2][0];
            $this->secret=$matches[3][0];
        }
        else
            throw new Exception("DSN: '$dsn' is in wrong format.");

	}
    
    function create_topic($topic)
    {

    }

    function delete_topic($topic)
    {

    }

    function subscriptions()
    {

    }

    function subscribe($topic,$endpoint=null,$protocol='http')
    {
        if ($endpoint==null)
            $endpoint=SITE_URL.'/_event/';
        
        $arn=$this->topics->{$topic}->arn;

        if (!$arn)
            return;

        $n=new SNSRequest('Subscribe');
        $n->TopicArn=$arn;
        $n->Endpoint=$endpoint;
        $n->Protocol=$protocol;
        $n->send();
    }

    function subscribed($topic)
    {

    }

    function unsubscribe($subscription)
    {
        $n=new SNSRequest('Unsubscribe');
        $n->SubscriptionArn=$subscription;
        $n->send();
    }

    function confirm_subscription($uid,$token)
    {
        $n=new SNSRequest('ConfirmSubscription');
        $n->Token=$token;
        $n->TopicArn=$uid;
        $n->send();
    }

    function publish($topic,$subject,$data)
    {
        if ($this->mutecount>0)
            return;
        
        $arn=$this->topics->{$topic}->arn;

        if (!$arn)
            return;

        $n=new SNSRequest('Publish');
        $n->TopicArn=$arn;
        $n->Subject=$subject;

        if ($data instanceof Document)
            $data=json_encode($data->to_array());
        else if (is_array($data))
            $data=json_encode($data);

        $n->Message=$data;

        $n->send();
    }
    

    
    function process()
    {
        $json=trim(file_get_contents('php://input'));
        $data=json_decode($json);

//        $fh=fopen('/tmp/sns.log','a');
//        fwrite($fh,"\n\nNEW REQUEST ".time()."\n");
//        fwrite($fh,$json);
//        ob_start();
//        print_r($data);
//        $poststr=ob_get_clean();
//        fwrite($fh,$poststr);
//        fclose($fh);

        switch ($data->Type)
        {
            case 'SubscriptionConfirmation':
                $this->confirm_subscription($data->TopicArn,$data->Token);

                break;
            case 'Notification':
                $this->dispatch($data->TopicArn,$data->Subject,$data->Message);
                
                break;
        }
    }

}