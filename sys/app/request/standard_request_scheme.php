<?

/**
 * Mechanism for parsing and building URIs.  
 * 
 * This is a higher-level abstraction from the URI class supporting
 * get/set/add/remove/exists functions to test and create new URIs
 * which use GET parameters to pass information to controllers.
 * 
 *  (Currently implemented by generic_search_controller any derived search control
 * 
 * @author PDM
 *
 */
abstract class RequestScheme
{	
	public $request=null;
	
	public function __construct($request)
	{
		if (!request)
			throw new Exception("Cannot instantiate Scheme without reference to request");
	
		$this->request = $request;
		
	}
	
	/**
	 * Is the parameter set in the URI?
	 * 
	 * @param string $parameter The parameter to check
	 * @return boolean
	 */
	abstract function exists($parameter);
	
	
	/**
	 * Is the parameter / value pair set in the URI?
	 * 
	 * @param string $parameter The parameter to look up
	 * @param string $value The value to check
	 * @return boolean
	 */
	abstract function pair_exists($parameter, $value);
	
	abstract function get_value($parameter);
	
	abstract function get_array($parameter);
}

/**
 * The standard Request scheme.  Parameters/values will be searched in GET or POST arrays
 * E.g. http://<server>/<base>/<segments...>?param1=val1&params[]=val2&params[]=val3
 * 
 * @author PDM
 *
 */
class StandardRequestScheme extends RequestScheme
{
	public function exists($parameter)
	{
		return $this->request->input->exists($parameter); 
	}	
	
	
	public function pair_exists($parameter, $value)
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
	
	
	public function get_value($parameter)
	{
		if (!$parameter)
			return null;

		return $this->request->input->{$parameter};
		
	}
	
	
	public function get_array($parameter)
	{
		return $this->request->input->get_array($parameter);		
	}
}
	
