<?
uses('system.cloud.queue.message_queue');
uses('system.util.encrypt');

class Message
{
	protected $account=null;
	protected $queue=null;
	
	public $attributes=array();
	
	public function __construct($account,$queue,$data=null)
	{
		$this->account=$account;
		$this->queue=$queue;
		
		if (is_array($data))
			$this->attributes=$data;
		else if (is_string($data))
			$this->attributes=json_decode($data);
	}
	
	public static function Next($account,$queue)
	{
		$q=MessageQueue::Get($account);
		
		try
		{
			$message=$q->receive(1);
			
			$c=new Encrypt();
			$message=$c->decode($message);

			return new Message($account,$queue,$message);
		}
		catch(AWSException $ex)
		{
			return null;
		}
	}

	public function __set($name,$value)
	{
		$this->attributes[$name]=$value;
	}
	
	public function __get($name)
	{
		return $this->attributes[$name];
	}
	
	public function send()
	{
		$c=new Encrypt();
		$message=$c->encode(json_encode($this->attributes));
		
		$q=MessageQueue::Get($account);
		
		try
		{
			$q->send($this->queue,$message);
			return TRUE;
		}
		catch(AWSException $ex)
		{
			return FALSE;
		}
	}
}