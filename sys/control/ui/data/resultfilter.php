<?
/**
 * Search Result Filter Control
 *
 * This control is used to show facets and sort criteria
 * for search results and faceted browsing
 * 
 * At base, this is a Repeater with some extra information
 * and methods at it's disposal to interpret a primary result set
 *
 * The datasource will be the datasource form the filter definition in the config file.
 * ... but can be overridden in the tag.
 */

uses('system.control.ui.data.repeater');


class ResultFilterControl extends RepeaterControl
{
	public $field=null;			/** field name.  If null, then all fields */
	
	public $filter_definition;
	
	public function clear()
	{
		$this->field = null;
		$this->item_template = null;
		$this->container_template = null;
	}
	
	public function init()
	{	
		parent::init();

		$this->current_index = 0;
		
		// make sure $field was specified
		if (!$this->field)
			throw new Exception("field must be specified in resultfilter control");
		$this->filter_definition = $this->controller->appmeta->filter->{$this->field};
		
		if (!$this->datasource) /* Default to the datasource definition in the config file */
			$this->datasource = $this->filter_definition->datasource;
		$filter_type = $this->filter_definition->type;

		// Set container and item templates from config file if not passed into tag
		if (!$this->item_template)
			$this->item_template = $this->controller->appmeta->renderer_map->{$filter_type}->item;
			
		if (!$this->container_template)
			$this->container_template = $this->controller->appmeta->renderer_map->{$filter_type}->container;
	
			
		// dig specific facet counts out of facets
		if (is_assoc($this->datasource))
		{
			$facet = $this->datasource[$this->filter_definition->facet->field];
			$this->datasource = array();
			foreach($facet as $value => $count)
				$this->datasource[] = array('value' => $value, 'count' => $count); 
		}
	}
	
	
	function has_value($parameter)
	{
		return ($this->controller->request->input->exists($parameter));
	}
	
	function link($parameter,$value,$removevalues=null)
	{	
		return $this->controller->request->uri->build(null,null,$values,$removevalues);
	}

	function radio_link($parameter, $value, $removevalues=null)
	{ 
		return $this->link($parameter,$value,$removevalues);
	}
		
	function active($parameter,$value=null)
	{
		$values=$this->controller->get_value($parameter);
		
		if (empty($values))
			return false;
		
		$active = false;
		
		if (!is_array($values))
			$vals[] = $values;
		else
			$vals = $values;
		
		// is this parameter being used to filter at all?
		if (!$value && !empty($vals))
			return true;

		// if so, check against the passed value
		foreach($vals as $val)
			if ($value && $value!='' && strtolower($val)==strtolower($value))
				$active = true;

		return $active;
	}
	
	function checkbox($parameter, $value, $removevalues=null)
	{
		$values=$this->controller->get_value($parameter);

		if (!is_array($values))
			$vals[] = $values;
		else
			$vals = $values;
		
			
		if (count($vals)>0)
		{
			if(in_array(rawurlencode(strtolower($value)),$vals))
			{
				array_splice($vals,array_search(rawurlencode(strtolower($value)),$vals),1);
				return $this->link($parameter,$vals,array_merge($removevalues,array($parameter=>rawurlencode(strtolower($value)))));
			}
			else
			{
				array_push($vals,rawurlencode(strtolower($value)));
				return $this->link($parameter,$vals,$removevalues);
			}
		}
		
		$link=$this->link($parameter,array($value),$removevalues);
		
		return $link;
	}
	
}