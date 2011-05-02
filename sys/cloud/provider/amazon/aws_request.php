<?
/**
 * SimpleDB Request wrapper
 * Very simple wrapper around the SimpleDB API.  Other PHP implementations were grossly
 * lacking or java engineered.
 */

uses('system.external.PEAR.HTTP.Request');
uses('system.cloud.provider.amazon.aws_exception');

/**
 * Represents a simple db api request.
 */
class AWSRequest
{
	/**
	 * Secrent key
	 *
	 * @var string
	 */
	protected $secret;
	
	/**
	 * Array of parameters
	 *
	 * @var array
	 */
	protected $parameters=array();
	
	/**
	 * Service endpoint
	 *
	 * @var string
	 */
	protected $endpoint=null;
	
	/**
	 * List of attributes
	 *
	 * @var array
	 */
	public $attributes=array();
	
	/**
	 * Signature version to use
	 */
	const SIGNATURE_VERSION='2';

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
		if ($endpoint)
			$this->endpoint=$endpoint;
			
		$this->secret=($secret) ? $secret : AMAZON_SECRET;
			
		$this->parameters['Action']=$action;
        $this->parameters['AWSAccessKeyId'] = ($id) ? $id : AMAZON_ID;
		$this->parameters['Timestamp']=gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", time());
		$this->parameters['SignatureVersion']=self::SIGNATURE_VERSION;
		$this->parameters['SignatureMethod']='HmacSHA256';
	}
	
	/**
	 * Allows setting params by refereing to properties on the object.
	 *
	 * @param string $name
	 * @return mixed The value of the parameter
	 */
	public function __get($name)
	{
		return $this->parameters[$name];
	}
	
	/**
	 * Sets the value of a parameter
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function __set($name,$value)
	{
		$this->parameters[$name]=$value;
	}
	
	protected function _urlencode($value) 
	{
		return str_replace('%7E', '~', rawurlencode($value));
    }
	
    protected function _getParametersAsString(array $parameters)
    {
        $queryParameters = array();
        foreach ($parameters as $key => $value) {
            $queryParameters[] = $key . '=' . $this->_urlencode($value);
        }
        return implode('&', $queryParameters);
    }
    
	/**
	 * Signs the request
	 *
	 * @return string
	 */
	protected function sign($method='POST', $queue=null)
	{
        $data = $method;
        $data .= "\n";
        
        $endpoint = parse_url($this->endpoint);
        $data .= $endpoint['host'];
        $data .= "\n";

        if ($queue) {
        	$uri  = $queue;
        } else {
        	$uri = "/";
        }
        $uriencoded = implode("/", array_map(array($this, "_urlencode"), explode("/", $uri)));
        $data .= $uriencoded;
        $data .= "\n";

        uksort($this->parameters, 'strcmp');
        
        $data .= $this->_getParametersAsString($this->parameters);
        
       return base64_encode(
            hash_hmac('sha256', $data, $this->secret, true)
        );
	}
	

	/**
	 * Sends the request
	 *
	 * @return SimpleXMLElement
	 */
	public function send($queue=null)
	{
		$this->parameters['Signature']=$this->sign('GET',$queue);

 		$request=new HTTP_Request($this->endpoint);
		$request->setMethod('GET');
		foreach($this->parameters as $name=>$value)
			$request->addQueryString($name,$value);
			
		$misses=0;
		for(;;)
		{
			$request->sendRequest();
		
			//@TODO: Add handling for different status codes
			if ($request->getResponseCode()==200)
				break;

            vomit($request->getResponseBody());
				
			$misses++;
			sleep($misses);
			
			if ($misses==3)
				throw new AWSException($request->getResponseBody());
		}

		$body=$request->getResponseBody();
		$response=simplexml_load_string($body);

		if ($response->Errors)
			throw new AWSException($response->Errors->Error->Message);
			
		return $response;
	}
}
