<?
/**
 * SQS Request wrapper
 * Very simple wrapper around the SQS API.  Other PHP implementations were grossly
 * lacking or java engineered.
 */

uses('system.cloud.provider.amazon.aws_request');

/**
 * Represents a sqs api request.
 */
class SQSRequest extends AWSRequest 
{
	/**
	 * Constructor
	 *
	 * @param string $id The Amazon account ID
	 * @param string $secret The Amazon account secret
	 * @param string $action The API action
	 * @param string $endpoint The URL at amazon to call
	 */
	public function __construct($action,$id=null,$secret=null,$endpoint=null)
	{
		parent::__construct($action,$id,$secret,$endpoint);
		
		$this->endpoint='http://queue.amazonaws.com/';

		$this->parameters['Version'] = '2008-01-01';      
	}

	/**
	 * Sends the request
	 *
	 * @return SimpleXMLElement
	 */
	public function send($queue=null)
	{
		$this->parameters['Signature']=$this->sign('POST',$queue);

        $query = $this->_getParametersAsString($this->parameters);
        $url = parse_url ($this->endpoint);
        $post  = "POST " . $queue . " HTTP/1.0\r\n";
        $post .= "Host: " . $url['host'] . "\r\n";
        $post .= "Content-Type: application/x-www-form-urlencoded; charset=utf-8\r\n";
        $post .= "Content-Length: " . strlen($query) . "\r\n";
        $post .= "User-Agent: Fucking Just Work Already\r\n";
        $post .= "\r\n";
        $post .= $query;

        $port = array_key_exists('port',$url) ? $url['port'] : null;
        $scheme = '';

        switch ($url['scheme']) 
        {
            case 'https':
                $scheme = 'ssl://';
                $port = $port === null ? 443 : $port;
                break;
            default:
                $scheme = '';
                $port = $port === null ? 80 : $port;
        }

        $response = '';
        if ($socket = @fsockopen($scheme . $url['host'], $port, $errno, $errstr, 10)) 
        {
            fwrite($socket, $post);

            while (!feof($socket))
                $response .= fgets($socket, 1160);

			fclose($socket);

            list($other, $responseBody) = explode("\r\n\r\n", $response, 2);
            $other = preg_split("/\r\n|\n|\r/", $other);
            list($protocol, $code, $text) = explode(' ', trim(array_shift($other)), 3);
        }
        else
            throw new Exception ("Unable to establish connection to host " . $url['host'] . " $errstr");
		
		//@TODO: Add handling for different status codes
		if ($code!=200)
			throw new AWSException("Unknown response code '".$code."'.\n\n".$responseBody);

		$result=simplexml_load_string($responseBody);

		if ($result->Errors)
			throw new AWSException($result->Errors->Error->Message);
			
		return $result;
	}
}
