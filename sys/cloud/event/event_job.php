<?
/**
 * An event job
 */
abstract class EventJob
{
    /**
     * The raw message.
     * @var string
     */
    protected $message;

    /**
     * Runs the event.
     * 
     * @abstract
     * @param  $message This is the string data returned from the delivery service.
     * @return void
     */
    abstract function run($message);
}