<?php

/**
 * Observer class (needed to attach to an Observable and be notified)
 */
abstract class Observer
{
	protected $params=array();
	
	public function __construct($params=null)
	{
		if($params)
		  $this->params = $params;
	}
	
    abstract public function update($observable, $event, &$data);
}

/**
 * Abstract observable class to implement the observer design pattern
 *
 * @abstract
 */
class ObservableModel extends Model
{
    /**
     * An array of Observer objects to notify
     *
     * @access private
     * @var array 
     */
    var $_observers = array();


    /**
     * Constructor
     */
    function __construct($id=null,$fields=null,$db=null,$row=null, $cacheable=false)
    {
        $this->_observers = array();

        // Load observers from observers.conf  (e.g. ActivityLogger, Notifier, Messenger)
        $config=Config::Get('observers/eventmap');
        
        /*
         * class_name:   (use instanceof to check so we can include superclasses here)
         *      - <event like post_create, accept, ignore, decline>
         *          observers:
         *              - ActivityPublisher
         *              - Notifier
         *              - Messenger
         *
         * May be possible to have a single Notifier class e.g. with multiple templates based on class/event
         *
         */
        
        // autoload observers
        $conf=Config::Get('observers');
        if($conf->auto)
        {        
            foreach($conf->auto->items as $obs)
               uses("observer.$obs");

            foreach($config->items as $observable_class => $event_observers)
            {
            	if ($this instanceof $observable_class)
            	{
                    foreach($event_observers->items as $event)
                    {
                    	foreach($event->observers->items as $item) //$observer => $params)
                    	{ 
                    		$params = $item->params;
                    		$parameters = ($params) ? $params->items : null;
    
                    		$instance = new $item->class($parameters);
                    		$this->attach($instance, $event->event);		
                    	}
                    }
            	}
            }
        }
        
        
        parent::__construct($id,$fields,$db,$row,$cacheable);
    }

    

    /**
     * Update each attached observer object and return an array of their return values
     *
     * @access public
     * @return array Array of return values from the observers
     */
    function notify($event, $data=null)
    {
    	$return = array();
    	$data = ($data)?$data:array();
    	
        // Iterate through the _observers array
        if (isset($this->_observers[$event]))
        {
            foreach ($this->_observers[$event] as $observer) {
                $return[] = $observer->update($this, $event, $data);
            }
        }
        
        return $return;
    }

    /**
     * Attach an observer object
     *
     * @access public
     * @param object $observer An observer object to attach
     * @return void 
     */
    function attach( &$observer, $event)
    {
    	trace(get_class($this), 'Attached observer ' . get_class($observer) . ' for event '. $event);
    	
        // Make sure we haven't already attached this object as an observer
        if (is_object($observer))
        {
            $class = get_class($observer);
            
            
            if (isset($this->_observers[$event]))
	            foreach ($this->_observers[$event] as $check)
	                if (is_a($check, $class))
	                    return;

            if (!isset($this->_observers[$event]))
                $this->_observers[$event]=array();
            
            $this->_observers[$event][] = $observer;
        }
    }

    /**
     * Detach an observer object
     *
     * @access public
     * @param object $observer An observer object to detach
     * @return boolean True if the observer object was detached
     */
    function detach( $observer, $event)
    {
        // Initialize variables
        $retval = false;

        if (isset($this->_observers[$event]))
        {
	        
	        $key = array_search($observer, $this->_observers[$event]);
	        
	        if ( $key !== false )
	        {
	            unset($this->_observers[$event][$key]);
	            $retval = true;
	        }
        }
                
        return $retval;
    }
    
    
    protected function pre_create(&$fields)
    {
    	parent::pre_create($fields);
    	$this->notify(__FUNCTION__);
    }
    
    protected function post_create()
    {
        parent::post_create();
        $this->notify(__FUNCTION__);
    }
    
    protected function pre_read()
    {
        parent::pre_read();
        $this->notify(__FUNCTION__);
    }
    
    protected function post_read()
    {
        parent::post_read();
        $this->notify(__FUNCTION__);
    }
    
    protected function pre_update(&$fields)
    {
        parent::pre_update($fields);
        $this->notify(__FUNCTION__);
    }
    
    protected function post_update($fields)
    {
        parent::post_update($fields);
        $this->notify(__FUNCTION__);
    }
    
    protected function pre_delete()
    {
        parent::pre_delete();
        $this->notify(__FUNCTION__);
    }
    
    protected function post_delete()
    {
        parent::post_delete();
        $this->notify(__FUNCTION__);
    }    
}


        /**
         * STUFF TO MIGRATE OR ADAPT:
         */
        
        /* 
         * AddNotification found in :
         *  job/application/index.php         // Notifier can listen on Message/post_create for message-type 5)
         *  mail/index.php (index -- sending) // Notifier can listen on ProfileMail/post_create
         *  pitch/invite.php (send)           // Not used, but if it were, Notifier could listen on AuditionInvite/post_create 
         *  portfolio/rate.php (media)        // Notifier can listen on Rating/post_create
         *  profile/friend.php (request)      // Notifier can listen on Friend (or ConnectionInvite)/post_create
         *  profiles/index.php (media -- commenting)  // Notifier can listen on Comment/post_create (for comments on portfolio items)
         *  register.php (create -- alerting them to Ken Woo email)  // Notifier can listen on Profile/post_create
         *
         *  TODO:  Figure out if AddNotification needs more info than is on the object
         *  If not, then all the Notifier needs to (initially) do in update($obj,$event) is call AddNotification with the right parameters for that instanceof $obj
         *  
         *  Then, once that's adapted, we can get get fancier with subscriptions (based on object/event pairs)
         *  
         *  TODO:  Notifier needs to DigUp the object being rated/commented on to find the owner and item type.
         *  TODO:  Until ConnectionInvites come online, Notifier needs to check friend status before queuewing a notification
         *  
         *  
         */

        
        /*
         * new ProfileMail() found in:  (aside from where the user is specifically sending something
         *
         * job/application/index.php (index -- new submission to your listing)       // Messenger can listen on Message/post_create (for message type 5) -- Needs to look up $listing 
         * partnerships/index.php (entry --  new comment on your entry)              // Messenger can listen on Comment/post_create (for entry comments)
         * pitch/comment.php (index -- alerting them to a new comment on the pitch)  // Messenger can listen on Comment/post_create (for projects)
         * pitch/invite.php (send -- you've been invited to audition)                // Not used, but if it were, Messenger could listen on AuditionInvite/post_create 
         * portfolio/rate.php (media)                                                // Messenger can listen on Rating/post_create
         * profile/friend.php (request / add)                                        // Messenger can listen on Friend (or ConnectionInvite)/post_create and (accept)
         * profiles/index.php (media -- someone commented on your portfolio item)    // Messenger can listen on Comment/post_create (for portfolio item comments)
         * register.php (create -- Ken Woo email)                                    // Messenger can listen on Profile/post_create
         * 
         */ 
        
        
        /*
         * Activity logging currently happening in:
         * 
         * model/top/user_contribution.php
         * model/top/user_object_attachment.php
         * model/top/user_opinion.php
         * model/listing.php  (special case b/c of complex listing creation process)
         *
         * controller/job/cast.php (special case b/c of complex listing creation process)
         * controller/job/crew.php (special case b/c of complex listing creation process)
         *
         * model/label.php (supressed)
         * model/object_label.php (supressed)
         * model/media (surpressed)
         * model/opinion/favorite (surpressed)
         * model/opinion/flag (surpressed)  -- should be notifying admin
         * model/portfolios/category (surpressed)
         * model/project/team_member (surpressed)
         * 
         * 
         * "AttributeView";"";""
         * "CastListingView";"";""
         * "Category";"ObjectMediaView";"CommentView"
         * "Category";"ObjectMediaView";""
         * "Credit";"";""
         * "CrewListingView";"";""
         * "EntryView";"ObjectMediaView";""
         * "EntryView";"";"CommentView"
         * "EntryView";"";"Rating"
         * "EntryView";"";""
         * "Message";"ObjectMediaView";""
         * "Message";"";"Rating"
         * "Message";"";""
         * "MessageView";"";""
         * "ProfileView";"ProfileView";""
         * "ProfileView";"Status";""
         * "ProjectView";"ObjectMediaView";""
         * "ProjectView";"";"CommentView"
         * "ProjectView";"";"Rating"
         * "ProjectView";"";""
         *          
         */

        
        /*
         * Search for new Flash();
         * $flash->add(...)
         * 
         * controller/mail/index.php (send -- message sent)
         * controller/partnerships/index.php (entry -- for comment?)
         * controller/pitch/comment.php (index -- defunct?)
         * controller/pitch/index.php (cast/crew -- cast/crew has been created/updated)
         * controller/pitch/invite.php (send -- you have been invited...)
         * shell/legacy/commands/processvideo.php (defunct... video is ready msg)
         * 
         * 
         * 
         * Flasher?
         */