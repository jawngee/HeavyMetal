<?

/**
 * Contains a list of highlighting instructions for a filter
 */
class Highlights
{
	public $fields=array();	/** List of fields to facet */	
	private $model=null;		/** Reference to the filter's model */
	private $filter=null;		/** Reference to filter object */
	public $config=array(); /** any addt'l facet parameters */
	
	/*
	 * Sample parameters:  hl=true&hl.fl=position_description&hl.fragsize=0
	 */
	
	/**
	 * Constructor
	 * 
	 * @param Model $model A reference to the model being filtered/sorted
	 */
	public function __construct($filter,$model)
	{
		$this->filter=$filter;
		$this->model=$model;
	}
	
	
	function __get($prop_name)
   	{
   		if (isset($this->model->fields[$prop_name]))
   			$this->fields[] = $prop_name;
   		
   		return $this->filter;
   	}
   	

   	function __set($prop_name, $prop_value)
   	{
   		$this->config[$prop_name] = $prop_value;
   	}
   	
}
 