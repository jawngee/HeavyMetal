<?
uses('sys.cloud.event.event_manager');
uses('sys.document.document');

/**
 * Shell controller for basic event management
 */
class CloudEventController extends Controller
{
    /**
     * Subscribes this server to a topic.
     * 
     * @param  $topic The topic to subscribe to.
     */
    public function subscribe($topic,$endpoint)
    {
        EventManager::GetManager('sns')->subscribe($topic,$endpoint);
    }

    public function unsubscribe($subscription)
    {
        EventManager::GetManager('sns')->unsubscribe($subscription);
    }

    public function confirm($uid,$token)
    {
        EventManager::GetManager('sns')->confirm_subscription($uid,$token);
    }

    public function test($topic,$subject)
    {
        $d=new Document();
        $d->cool='Yes';
        $d->awesome=12;
        $d->fuckyeah=array(1,2,3,4,5);
        EventManager::GetManager('sns')->publish($topic,$subject,$d);
    }
}