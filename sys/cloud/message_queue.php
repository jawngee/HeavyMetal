<?
abstract class MessageQueue
{
	private static $_mqueues=array();
	
	/**
	 * Fetches a named database from the connection pool.
	 *
	 * @param string $name Name of the database to fetch from the connection pool.
	 * @return Database The named database.
	 */
	public static function GetQueue($name)
	{
		if (isset(self::$_mqueues[$name]))
			return self::$_mqueues[$name];
		else
		{
			$conf=Config::Get('cloud');

			if (isset($conf->dsn->items[$name]))
			{
				$dsn=$conf->dsn->items[$name]->queue;
				
				$matches=array();
				if (preg_match_all('#([a-z]*)://([^@]*)@(.*)#',$dsn,$matches))
				{
					$driver=$matches[1][0];
					$auth=$matches[2][0];
					$secret=$matches[3][0];
					
					uses('system.cloud.driver.queue.'.$driver);
					
					$class=$driver."Driver";
					$mqueue=new $class($auth,$secret);
					
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