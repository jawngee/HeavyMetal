<?
abstract class MessageQueue
{
	private static $_mqueues=array();
	
	/**
	 * Fetches a named database from the connection pool.
	 *
	 * @param string $name Name of the database to fetch from the connection pool.
	 * @return MessageQueue The named database.
	 */
	public static function GetQueue($name)
	{
		if (isset(self::$_mqueues[$name]))
			return self::$_mqueues[$name];
		else
		{
			$conf=Config::Get('cloud');

			if ($conf->queue->{$name}->dsn)
			{
				$dsn=$conf->queue->{$name}->dsn;
				
				$matches=array();
				if (preg_match_all('#([a-z]*)://([^@]*)@(.*)#',$dsn,$matches))
				{
					$driver=$matches[1][0];
					
					uses('sys.cloud.queue.'.$driver.'.'.$driver.'_message_queue');
					
					$class=$driver."MessageQueue";
					$mqueue=new $class($dsn);
					
					self::$_mqueues[$name]=$mqueue;
					return $mqueue;
				}
			}

			throw new Exception("Cannot find queue named '$name' in Config.");
		}
	}

	/**
	 * Lists queues
	 *
	 * @return array
	 */
	abstract public function list_queues();

	/**
	 * Creates a new message queue
	 *
	 * @param string $name Name of the queue
	 * @param string $visibility Default visibility timeout for the queue
	 * @return string The identifier for the queue
	 */
	abstract public function create_queue($name,$visibility=30);
	
	/**
	 * Deletes a queue
	 *
	 * @param string $queue The queue's identifier
	 * @return bool
	 */
	abstract public function delete_queue($queue);
	
	/**
	 * Get the queue's approximate message count.
	 *
	 * @param string $queue
	 * @return int
	 */
	abstract public function message_count($queue);

	
	/**
	 * Sends a message to the queue
	 *
	 * @param string $queue
	 * @param string $message
	 * @return string
	 */
	abstract public function send($queue, $message);

	
	/**
	 * Gets messages from the queue
	 *
	 * @param int $count The number of messages to fetch.
	 * @param int $visibility The duration that this item is hidden in the queue before being visible again.
	 */
	abstract public function receive($queue, $count=1, $visibility=null);


	
	/**
	 * Delete a message from the queue
	 *
	 * @param string $queue
	 * @param string $receipt_handle
	 * @return bool
	 */
	abstract public function delete($queue, $receipt_handle);
}