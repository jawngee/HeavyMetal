<?
/**
 * Represents a message handler
 */
abstract class MessageHandler
{
    /**
     * @var Config Configuration object.
     */
    public $conf=null;
   
    /**
     * Constructor
     *
     * @param Config $config Configuration parameters for the message handler
     */
    public function __construct($conf)
    {
        $this->conf=$conf;
    }
}