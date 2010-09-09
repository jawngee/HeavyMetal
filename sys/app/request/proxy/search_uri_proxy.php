<?

uses('system.app.request.proxy.uri_proxy');

/**
 * The standard URI scheme.  Parameters/values will be searched in GET or POST arrays
 * E.g. http://<server>/<base>/<segments...>?param1=val1&params[]=val2&params[]=val3
 * 
 * The only information of interest is in the query parameters.  
 * The segments are not used to pass information in this scheme.
 * 
 * @author PDM
 *
 */
class SearchURIProxy extends URIProxy
{
	/**
	 * Proxy URI manipulation to query parameters
	 */
		
	public function add_value($parameter, $value)
	{
		$this->uri->query->add_value($parameter, $value);
		
		return $this;
	}
	
	public function set_value($parameter, $value)
	{
		$this->uri->query->set_value($parameter, $value);
	
		return $this;
	}

	
	public function remove_value($parameter, $value)
	{
		$this->uri->query->remove_value($parameter, $value);
		
		return $this;
	}
}