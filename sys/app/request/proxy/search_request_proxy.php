<?

uses('system.app.request.proxy.request_proxy');
uses('system.app.request.proxy.search_uri_proxy');

/**
 * The standard Request scheme.  Parameters/values will be searched in GET or POST arrays
 * E.g. http://<server>/<base>/<segments...>?param1=val1&params[]=val2&params[]=val3
 * 
 * @author PDM
 *
 */
class SearchRequestProxy extends RequestProxy
{
	
	protected function uri()
	{
		return new SearchURIProxy($this->request->uri);
	}
	
	/***********************
	 * Input helper methods
	 ***********************/
	
	
	/**
	 * Was the parameter passed in the Input?
	 * 
	 * @param string $parameter The parameter to check
	 * @return boolean
	 */
	public function exists($parameter, $value=null)
	{
		if (!$value)
			return $this->request->input->exists($parameter); 
		
		else
			return $this->pair_exists($parameter, $value);
	}	
	
	/**
	 * Was the parameter / value pair passed in the Input?
	 * 
	 * @param string $parameter The parameter to look up
	 * @param string $value The value to check
	 * @return boolean
	 */
	protected function pair_exists($parameter, $value)
	{
		if ($this->request->input->is_array($parameter))
		{
			$values = $this->request->input->get_array($parameter);
			return in_array(strtolower($value), $values);
		}
		else
		{
			$val = $this->request->input->{$parameter};
			return ($val && strtolower($value)===strtolower($val));
		}
	}
	
	/**
	 * Get the value for the parameter passed in the Input
	 *
	 * @param $parameter
	 * @return string
	 */	
	public function get_value($parameter)
	{
		if (!$parameter)
			return null;

		return $this->request->input->{$parameter};
		
	}

	
	/**
	 * Get the array value for the parameter passed in the Input
	 * 
	 * @param $parameter
	 * @return mixed
	 */	
	public function get_array($parameter)
	{
		return $this->request->input->get_array($parameter);		
	}
}
	
