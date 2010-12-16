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
	public $field=null;
	
	public $filter_definition;
	
	
	public $select_multiple=null;
	
	public $more_facet=false;
	
	public $facet_definition;

	private $lookback=null;
	
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
		$this->facet_definition = ($this->more_facet) 
			? $this->filter_definition->more_facet
			: $this->filter_definition->facet;
		
		if (!$this->datasource) /* Default to the datasource definition in the config file */
			$this->datasource = $this->filter_definition->datasource;
		$filter_type = $this->filter_definition->type;

		// Set container and item templates from config file if not passed into tag
		if (!$this->item_template)
			$this->item_template = $this->controller->appmeta->renderer_map->{$filter_type}->item;
			
		if (!$this->container_template)
			$this->container_template = $this->controller->appmeta->renderer_map->{$filter_type}->container;

		// radio or checkbox (default radio)?
		if (!$this->select_multiple)
			$this->select_multiple = $this->filter_definition->select_multiple;
			
			
		// dig specific facet counts out of facets
		if (is_assoc($this->datasource))
		{
			$facet = $this->datasource[$this->filter_definition->filter];
			$this->datasource = array();
			foreach($facet as $value => $count)
				$this->datasource[] = array('value' => $value, 'count' => $count); 
		}
		
		// dig 
	}
	
	
	function checkbox($parameter, $value, $removevalues=null)
	{
		$values=$this->controller->request->get_array($parameter);		
		
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
		$uri = $this->controller->request->uri
			->remove_values($removevalues);
			
		// add new
		if ($multi)
			$uri->add_value($parameter, $value); // used for multi-select
		else
			$uri->set_value($parameter, $value);

			
		$uri = $this->trim_uri($uri);
		
		return $uri->build();
	}
	
	protected function trim_uri($uri)
	{
		$uri->root = str_replace('/morefacet', '', $uri->root);
		
		$uri->query->remove_value('facet');

		return $uri;
	}
	
	protected function render_template($template, $key, $row)
	{
		
		$rendered_template = '';
		
		$value  = trim($row['value']); // stub
		
		if ($this->facet_definition->divider)
		{
			if (!$this->lookback || strtolower($this->lookback[0]) != strtolower($value[0]))
			{
				$rendered_template = $this->render_divider($value[0]);	
				$this->lookback = $value;
			}
		}
		
		$link   = ($this->filter_definition->select_multiple) 
			? $this->checkbox($this->field, $value)
			: $this->link($this->field, $value);
		
		$active = $this->controller->request->exists($this->field, $value);
		
		$fcount  = $row['count']; // stub
		
		$facet_count = null;

		if (is_numeric($this->facet_definition->count_ceiling) && $this->facet_definition->count_ceiling < $fcount)
	       	$facet_count = $this->facet_definition->count_ceiling . '+';
		else
			$facet_count = $fcount;
			
		if (!empty($value))
			$rendered_template .= $template->render(
				array(
					'control' => $this, 
					
					'value'  => $value,
					'link'   => $link,
					'active' => $active,
					'facet_count' => $facet_count
				));
		
		$this->current=&$row;
		$this->current_index++;
		$this->count++;		
		
		
		return $rendered_template;
	}
	
	private function render_divider($divider_text)
	{
		$divider = $this->get_template($this->facet_definition->divider);
		
		return $divider->render(
			array(
				'control' => $this,
				'divider_text' => $divider_text
			));
	}
	
	protected function render_container($template, $rendered)
	{
		$container = new Template($template);
		
		// Is a value selected for this field
		$active = $this->controller->request
			->exists($this->field);

		// The link to clear the current selection for this field
		$offlink = $this->controller->request->uri
			->remove_value($this->field, 
					 $this->controller->request->get_value($this->field))
			->build();

		// The link to pop out the rest of any abbreviated facet list
		$morelink = $this->controller->request->uri;
		$morelink->root .= '/morefacet';
		$morelink->query->set_value('facet', $this->field);
		$morelink = $morelink->build();
			
		return $container->render(
			array(
				'control' => $this, 
				'count'=>$this->count, 
				'content' => $rendered,
			
				'field'   => $this->field,
				'active'  => $active,
				'offlink' => $offlink,
				'morelink' => $morelink
			));
	}
}