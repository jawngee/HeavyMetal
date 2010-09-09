<?
/**
 * Search Result Filter Control
 *
 * This control is used to show facets
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
	
	function radio_link($parameter, $value, $removevalues=null)
	{ 
		return $this->link($parameter,$value,$removevalues);
	}
	
	function checkbox($parameter, $value, $removevalues=null)
	{
		$values=$this->controller->request_scheme->get_array($parameter);		

		$uri = $this->controller->request->uri->copy();
		
		$value = strtolower($value);
		
		foreach($values as $val)
		{
			// if this option is currently filtering, stop filtering
			if($value===$val)
			{
				return $this->link(null,null,array($parameter=>$value));
			}
		}

		$link=$this->link_multi($parameter,$value,$removevalues);
		
		return $link;
	}
	
	
	function link($parameter, $value, $removevalues=null)
	{
		return $this->link_common($parameter, $value, $removevalues);
	}
	
	function link_multi($parameter, $value, $removevalues=null)
	{
		return $this->link_common($parameter, $value, $removevalues, true);
	}
	
	protected function link_common($parameter,$value=null,$removevalues=null, $multi=false)
	{
		$uri = $this->controller->request->uri->copy();
		
		// remove values
		foreach($removevalues as $key=>$val)
		{
			$uri->query->remove_value($key, $val);
		}

		// add new
		if ($multi)
			$uri->query->add_value($parameter, $value); // used for multi-select
		else
			$uri->query->set_value($parameter, $value);

			
		// remove facet query parameter
		$uri->query->remove_value('facet');
		
		return $uri->build();
	}
	
}