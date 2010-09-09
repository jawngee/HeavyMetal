<?

/**
 * Wrapper for the URI object that simplifies
 * getting of input values and associative pairs
 * and creation of new URIs for applications
 * 
 * Can be subclassed to read/write an alternative URI schemes
 * thereby making any URI-dependent functionality, URI-agnostic.
 * 
 * @author PDM
 *
 */
abstract class URIProxy
{
	protected $uri=null;
	
	public function __construct($uri)
	{
		if (!$uri)
			throw new Exception("Cannot instantiate URIProxy without reference to URI");
	
		// Cloning the URI so that we can mutate this copy without affecting 
		// other parts of the application which may reference the original.
		$this->uri = clone $uri;
		
	}

	
	/**
	 * Proxy gets to the underlying URI
	 */
	public function __get($uri_member)
	{
		return $this->uri->{$uri_member};
	}

	
	/**
	 * Proxy sets to the underlying URI
	 */
	public function __set($uri_member, $value)
	{
		return $this->uri->{$uri_member} = $value;
	}
	
	
	/**
	 * Proxy method calls to the underlying URI
	 */
	public function __call($uri_method, $params)
	{
		return call_user_func_array(array( $this->uri, $uri_method ), $params);
	}	
}