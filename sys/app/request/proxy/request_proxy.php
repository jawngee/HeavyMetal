<?

/**
 * Wrapper for the Request object that simplifies
 * getting / setting of input values and associative pairs
 * 
 * Can be subclassed to read/write an alternative URI schemes
 * thereby making any URI-dependent functionality, URI-agnostic.
 * 
 * @author PDM
 *
 */
abstract class RequestProxy
{	
	protected $request=null;
	
	public function __construct($request)
	{
		if (!$request)
			throw new Exception("Cannot instantiate RequestProxy without reference to request");
	
		$this->request = $request;
		
	}
	
	
	/**
	 * Proxy gets to the underlying request
	 */
	public function __get($request_member)
	{
		if ($request_member==='uri')
			return $this->uri();
		else
			return $this->request->{$request_member};
	}

	
	/**
	 * Proxy sets to the underlying request
	 */
	public function __set($request_member, $value)
	{
		return $this->request->{$request_member} = $value;
	}
	
	
	/**
	 * Proxy method calls to the underlying request
	 */
	public function __call($request_method, $params)
	{
		return call_user_func_array(array( $this->request, $request_method ) , $params);
	}
	
	
	/**
	 * Can be overridden to return a conforming URIProxy
	 */
	protected function uri()
	{
		return $this->request->uri;
	}
	
	
	/**
	 * Return the wrapped request
	 */
	public function request()
	{
		return $this->request;
	}
	
	/***********************
	 * Input helper methods
	 ***********************/
	
	
	/**
	 * Was the parameter passed?
	 * 
	 * @param string $parameter The parameter to check
	 * @return boolean
	 */
	abstract function exists($parameter);
	
	
	/**
	 * Was the parameter / value pair passed?
	 * 
	 * @param string $parameter The parameter to look up
	 * @param string $value The value to check
	 * @return boolean
	 */
	abstract protected function pair_exists($parameter, $value);
	
	/**
	 * Get the value for the parameter passed
	 *
	 * @param $parameter
	 * @return string
	 */
	abstract function get_value($parameter);
	
	/**
	 * Get the array value for the parameter passed
	 * 
	 * @param $parameter
	 * @return mixed
	 */
	abstract function get_array($parameter);
}
