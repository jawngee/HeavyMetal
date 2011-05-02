<?
uses('sys.cloud.event.event_job');

/**
 * The event manager is an interface for publishing and processing events to services like Amazon SNS or another similar delivery system.
 *
 * Events are a good way of running long running jobs when an event takes place in your application.  It's also a good means for allowing
 * completely isolated and disassociated applications to communicate events back and forth.
 *
 * Each event manager instance can have a number of topics, or channels, to which you can:
 *
 *   - Subscribe to a topic to listen for events
 *   - Publish events to a topic.
 *
 * Published events have a subject and a message body.  The message body is limited to 8K, but can be any json_encodable() data.
 *
 * When setting up a subscriber to listen for an event, you'll have to create a matching controller to be the HTTP callback the
 * delivery system will talk to.  Each driver will have a server controller implementation that you can inherit your controller
 * from.
 *
 * When a subscriber receives a message, the event manager will then dispatch the message to a custom EventJob class to run.
 *
 * You should be aware that delivery of messages is not guaranteed to be in time order, so using to synchronize data requires
 * some special handling.
 *
 * To get an instance of your event manager:
 *
 * <code>
 *      $mgr=EventManager::GetManager('nameofyourmanager');
 * </code>
 *
 * To send a message:
 *
 * <code>
 *      EventManager::Notify('nameofyourmanager','topic','subject',array('some'=>'data'));
 * </code>
 *
 * The data can be a Document, DynamicObject, Model or an array.  Basically anything you can json_encode().
 *
 * The event manager configuration is stored in the 'event' section of cloud.conf.  Below is a sample:
 *
 * <code>
 * event:
 *  sns:
 *    dsn: sns://AMAZON_ID@AMAZON_SECRET
 *    topics:
 *      login_events:
 *        uid: 'arn:aws:sns:us-east-1:13123123:login_events'
 *        jobs: 'app.jobs.login'
 *        resubscribe: true
 *        listen: true
 *        publish: false
 *      backend_events:
 *        uid: 'arn:aws:sns:us-east-1:123123123123:backend_events'
 *        jobs: 'app.jobs.backend'
 *        resubscribe: true
 *        listen: false
 *        publish: true
 * </code>
 *
 * Each event manager has a variety of topics (or channels) that clients can subscribe to, and/or send messages to.
 * The uid is the unique ID for the topic specific to whatever backend.  For Amazon SNS it's the ARN (Amazon Resource Name).
 * Jobs is the namespace that contains whatever EventJob's should be run when notifications are received.
 * Resubscribe will automatically resubscribe if it should somehow become unsubscribed.
 * Listen controls if incoming notifications for a given topic should be listened to or discarded on this server.
 * Publish controls if this server can publish to a particular topic.
 *
 *
 * @throws Exception
 *
 */
abstract class EventManager
{
    protected $topics;      /** Array of topics */
    protected $conf;        /** Configuration */

    protected $mutecount=0;     /** The mute level of the notifications.  > 0 supresses publishing. */

    /**
     * Static array of manager instances
     * @var array
     */
	private static $_managers=array();

    /**
     * Constructor.
     * @param  $conf The configuration for this event manager
     * @param  $dsn The DSN
     */
    public function __construct($conf,$dsn)
    {
        $this->conf=$conf;
    }

	/**
	 * Fetches a named event manager.
	 *
	 * @param string $name Name of the event manager to fetch.
	 * @return EventManager The event manager
	 */
	public static function GetManager($name)
	{
		if (isset(self::$_managers[$name]))
			return self::$_managers[$name];
		else
		{
			$conf=Config::Get('cloud');

			if ($conf->notification->{$name}->dsn)
			{
				$dsn=$conf->notification->{$name}->dsn;

				$matches=array();
				if (preg_match_all('#([a-z]*)://([^@]*)@(.*)#',$dsn,$matches))
				{
					$driver=$matches[1][0];

					uses('sys.cloud.event.'.$driver.'.'.$driver.'_event_manager');

					$class=$driver."EventManager";
					$nm=new $class($conf->notification,$dsn);
                    $nm->topics=$conf->notification->{$name}->topics;
					self::$_managers[$name]=$nm;
					return $nm;
				}
			}

			throw new Exception("Cannot find event manager named '$name' in Config.");
		}
	}

    /**
     * Publishes a message to an event manager.
     *
     * @static
     * @param  $name The name of the event manager
     * @param  $topic   The topic name
     * @param  $subject The subject of the message
     * @param  $data An array or Document to send as data.
     * @return void
     */
    static function Notify($name,$topic,$subject,$data)
    {
        $mgr=self::GetManager($name);
        $mgr->publish($topic,$subject,$data);
    }

    /**
     * Increases the mute count.  Mute count > 0 supresses publishing.
     * @return void
     */
    public function mute()
    {
        $this->mutecount++;
    }

    /**
     * Decreases the mute count.  Mute count > 0 supresses publishing.
     * @return void
     */
    public function unmute()
    {
        $this->mutecount--;
    }

    /**
     * Determines if this event manager is muted
     * @return bool
     */
    public function muted()
    {
        return ($this->mutecount>0);
    }

    /**
     * Looks up the configuration for a topic based on it's uid
     * @param  $uid The uid to look up.
     * @return Config
     */
    protected function find_conf($uid)
    {
        foreach($this->conf as $topic => $data)
        {
            if ($data->uid==$uid)
                return $data;
        }
    }

    /**
     * Runs a job for a given message.
     * 
     * @param  $topic_uid
     * @param  $subject
     * @param  $message
     * @return
     */
    public function dispatch($topic_uid,$subject,$message)
    {
        $conf=$this->find_conf($topic_uid);

        if (!$conf->listen)
            return;

        $job=$conf->jobs.'.'.$subject."_job";

        uses($job);
        $class=str_replace('_','',$job)."Job";
        $instance=new $class($message);
        $instance->run();
    }

    /**
     * Creates a topic.
     *
     * @abstract
     * @param  $topic The name of the topic to create.
     * @return void
     */
    abstract function create_topic($topic);

    /**
     * Deletes a topic.
     *
     * @abstract
     * @param  $topic The name of the topic to delete.
     * @return void
     */
    abstract function delete_topic($topic);

    /**
     * List all of the subscriptions for this event manager across all topics.
     * @abstract
     * @return void
     */
    abstract function subscriptions();

    /**
     * Subscribes to a topic.
     *
     * @abstract
     * @param  $topic The name of a topic to subscribe to.
     * @return void
     */
    abstract function subscribe($topic,$endpoint=null,$protocol='http');

    /**
     * Gets a list of subscriptions for a topic.
     *
     * @abstract
     * @param  $topic The name of the topic to see subscriptions for.
     * @return void
     */
    abstract function subscribed($topic);

    /**
     * Unsubscribes from a topic.
     * @abstract
     * @param  $topic The topic to unsubscribe from.
     * @return void
     */
    abstract function unsubscribe($topic);

    /**
     * Confirms a subscription.
     * @abstract
     * @param  $topic_uid The UID of the topic to confirm the subscription
     * @param  $token The confirmation token.
     * @return void
     */
    abstract function confirm_subscription($topic_uid,$token);

    /**
     * @abstract
     * @param  $topic The topic to publish to.
     * @param  $subject The subject of the message.
     * @param  $data The data to publish.  Can be a Document, DynamicObject, Model, array or string.
     * @return void
     */
    abstract function publish($topic,$subject,$data);

    /**
     * Processes the incoming notification and dispatches it.
     *
     * @abstract
     * @return void
     */
    abstract function process();
}