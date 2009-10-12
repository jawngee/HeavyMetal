<?
uses('system.cloud.amazon.sqs_request');

/**
 * Message queue driver for SQS
 */
class SQSDriver extends MessageQueue 
{
	private $id=null;
	private $secret=null;

	/**
	 * Constructor
	 *
	 * @param string $id Amazon ID
	 * @param string $secret Amazon Secret
	 */
	public function __construct($id,$secret)
	{
		$this->id=$id;
		$this->secret=$secret;
	}
	
	/**
	 * Lists queues
	 *
	 * @return array
	 */
	public function list_queues()
	{
		$req=new SQSRequest('ListQueues',$this->id,$this->secret);
		$response=$req->send();
		
		$result=array();
		foreach($response->ListQueuesResult->QueueUrl as $url)
			$result[]=(String)$url;
		
		return $result;
	}

	/**
	 * Creates a new message queue
	 *
	 * @param string $name Name of the queue
	 * @param string $visibility Default visibility timeout for the queue
	 * @return string The identifier for the queue
	 */
	public function create_queue($name,$visibility=30)
	{
		$req=new SQSRequest('CreateQueue',$this->id,$this->secret);
		$req->QueueName=$name;
		$req->DefaultVisibilityTimeout=$visibility;
		$response=$req->send();
		
		return (String)$response->CreateQueueResult->QueueUrl;
	}
	
	/**
	 * Deletes a queue
	 *
	 * @param string $queue The queue's identifier
	 * @return bool
	 */
	public function delete_queue($queue)
	{
		$req=new SQSRequest('DeleteQueue',$this->id,$this->secret,$queue);
		$response=$req->send("/$queue/");
		
		return true;
	}
	
	/**
	 * Get the queue's approximate message count.
	 *
	 * @param string $queue
	 * @return int
	 */
	public function message_count($queue)
	{
		$req=new SQSRequest('GetQueueAttributes',$this->id,$this->secret,$queue);
		$req->AttributeName='ApproximateNumberOfMessages';
		$response=$req->send("/$queue/");

		return (String)$response->GetQueueAttributesResult->Attribute->Value;
	}
	
	/**
	 * Sends a message to the queue
	 *
	 * @param string $queue
	 * @param string $message
	 * @return string
	 */
	public function send($queue, $message)
	{
		$req=new SQSRequest('SendMessage',$this->id,$this->secret);
		$req->MessageBody=$message;
		$response=$req->send("/$queue/");
		
		return (String)$response->SendMessageResult->MessageId;
	}
	
	/**
	 * Gets messages from the queue
	 *
	 * @param int $count The number of messages to fetch.
	 * @param int $visibility The duration that this item is hidden in the queue before being visible again.
	 */
	public function receive($queue, $count=1, $visibility=null)
	{
		$req=new SQSRequest('ReceiveMessage',$this->id,$this->secret,$queue);
		$req->MaxNumberOfMessages=$count;
		
		if ($visibility)
			$req->VisibilityTimeout=$visibility;
			
		$response=$req->send("/$queue/");
		
		$result=array();
		
		foreach($response->ReceiveMessageResult->Message as $message)
			$result[]=array(
				'receipt'=>(String)$message->ReceiptHandle,
				'body' =>(String)$message->Body
			);
		
		return $result;
	}

	
	/**
	 * Delete a message from the queue
	 *
	 * @param string $queue
	 * @param string $receipt_handle
	 * @return bool
	 */
	public function delete($queue, $receipt_handle)
	{
		$req=new SQSRequest('DeleteMessage',$this->id,$this->secret,$queue);
		$req->ReceiptHandle=$receipt_handle;
		$req->send("/$queue/");
		
		return true;
	}
}