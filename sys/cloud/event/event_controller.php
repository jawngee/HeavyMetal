<?
uses('sys.cloud.event.event_manager');

class EventController extends Controller
{
    protected $event_manager='default';
    
    public function index()
    {
        EventManager::GetManager($this->event_manager)->process();
    }
}