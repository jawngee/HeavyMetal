<?
uses('system.data.keyvalue_db');
uses('system.data.simple_model');
uses('system.cloud.message_queue');

class ObjectQueue 
{
	const OBJECT_VERSION='2008.12.30';
	
	private $domain=null;
	private $queue=null;
	private $qmanager=null;
	private $db=null;
	
	private static $_oqueues=array();
	
	/**
	 * Fetches a named object queue.
	 *
	 * @param string $name Name of the queue to fetch
	 * @return ObjectQueue The named object queue
	 */
	public static function GetQueue($name)
	{
		if (isset(self::$_oqueues[$name]))
			return self::$_oqueues[$name];
		else
		{
			$q=new ObjectQueue($name);
			self::$_oqueues[$name]=$q;
			return $q;
		}
	}
	
	/**
	 * Constructor.  Private.
	 *
	 * @param unknown_type $name
	 */
	private function __construct($name)
	{
		$conf=Config::Get('cloud');

		if (isset($conf->channel->items[$name]))
		{
			$this->queue=$name;
			$this->qmanager=MessageQueue::GetQueue($conf->channel->items[$name]->queue);
			$this->db=KeyValueDB::Get($conf->channel->items[$name]->db);
			$this->domain=$conf->channel->items[$name]->domain;
		}
		else
			throw new Exception("Cannot find queue named '$name' in Config.");
	}
			
	/**
	 * Sends an object to the queue.
	 *
	 * @param unknown_type $object
	 */
	public static function Send($queue, SimpleModel $object)
	{
		$queue=ObjectQueue::GetQueue($queue);
		
		$object->save($queue->domain, $queue->db);
		$queue->qmanager->send($queue->queue,$object->uid);
	}
	
	/**
	 * Fetches an object from the queue.
	 *
	 * @param unknown_type $channel
	 * @return unknown
	 */
	public static function Fetch($queue)
	{
		$queue=ObjectQueue::GetQueue($queue);
		
		$item=$queue->qmanager->receive($queue->queue);
		if (count($item)>0)
		{
			$queue->qmanager->delete($queue->queue, $item[0]['receipt']);
			$item=new SimpleModel($item[0]['body'],$queue->domain,$queue->db);
			return $item;
		}
		
		return null;
	}
}