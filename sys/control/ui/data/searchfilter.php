<?
/**
 * Search Filter Control
 *
 */
uses('system.app.control');
uses('system.app.view');
uses('system.app.template');
uses('system.app.link');
uses('system.app.request.input');
uses('system.data.channel');

class SearchFilterControl extends Control
{
	public $orderby_template='ctrl/searchfilter/order_by';
	public $radio_template='ctrl/searchfilter/radio';
	public $multi_template='ctrl/searchfilter/multi';
	public $lookup_template='ctrl/searchfilter/lookup';
	public $lookup_select_template='ctrl/searchfilter/lookup_select';
	public $lookup_checkbox_template='ctrl/searchfilter/lookup_checkbox';
	public $range_template='ctrl/searchfilter/range';
	public $text_template='ctrl/searchfilter/text';
	public $location_template='ctrl/searchfilter/location';
	public $date_template='ctrl/searchfilter/date';
	public $list_template='ctrl/searchfilter/list';
	public $divider_template='ctrl/searchfilter/divider';
	public $faceted_template='ctrl/searchfilter/faceted';
	public $token_template='ctrl/searchfilter/token';
	public $container_template='ctrl/searchfilter/container';
	public $always_expanded=false;
	
	
	public $filters=null;
	
	public $use_filter=null;
	
 	public function init()
	{
		parent::init();
	}

	function link($parameter,$value,$removevalues=null)
	{
		return $this->controller->request->uri->build(null,array($parameter=>$value),$removevalues);
	}
	
	function option($parameter,$value)
	{
		return $this->controller->request->uri->build(null,array($parameter=>$value));
	}
	
	function value($parameter)
	{
		return $this->controller->request->input->{$parameter};
	}
	
	function multi_link($parameter, $value)
	{
		if ($this->controller->request->input->exists($parameter))
			return $this->controller->request->uri->build(null,null,array($parameter));
					
		return $this->link($parameter,$value);
	}
	
	function clear($parameter)
	{
		return $this->controller->request->uri->build(null,null,(is_array($parameter))?$parameter:array($parameter));
	}
	
	function has_value($parameter)
	{
		return ($this->controller->request->input->exists($parameter));
	}
	
	function checked($parameter, $value)
	{
		$values=$this->request->input->get_array($parameter);
		
		if ($this->controller->request->input->exists($parameter))
		{
			foreach($values as $val)
				if (strtolower($value)==strtolower($val))
					return true;
		}
				
		return false;
	}
	
	function radio($parameter, $value)
	{
		if ($this->controller->request->input->exists($parameter))
			if (($this->controller->request->input->exists($parameter) && ($this->controller->request->input->{$parameter}==$value)))
				return $this->controller->request->uri->build(null,null,array($parameter));
			else
				return $this->controller->request->uri->build(null,array($parameter=>$value));	
		
		$link=$this->link($parameter,$value);
		
		return $link;
	}
	
	function checkbox($parameter, $value)
	{
		if ($this->controller->request->input->exists($parameter))
		{
			$values=$this->request->input->get_array($parameter);
			//if value is in the query already
			if(in_array($value,$values))
			{
				array_splice($values,array_search($value,$values),1);
				return $this->controller->request->uri->build(null,array($parameter=>$values),array($parameter=>$value));
			}
			else
			{
				array_push($values,$value);
				return $this->controller->request->uri->build(null,array($parameter=>$values));	
			}
		}
		
		$link=$this->link($parameter,array($value));
		
		return $link;
	}
	
	function radio_link($parameter, $value, $removevalues=null)
	{ 
		if (($this->controller->request->input->exists($parameter) && ($this->controller->request->input->{$parameter}==$value)))
			return $this->controller->request->uri->build(null,null,array($parameter));
					

		return $this->link($parameter,$value,$removevalues);
	}
	
	function order_by($parameter, $value, $direction)
	{		
		return $this->controller->request->uri->build(null,array($parameter=>$value, "direction"=>$direction));
	}
	
	function active_order($parameter,$value,$direction)
	{
		return (
			$this->controller->request->input->exists($parameter) && 
			($this->controller->request->input->{$parameter}==$value) &&
			($this->controller->request->input->direction==$direction)
		);
	}
	
	function active($parameter,$value)
	{
		return ($this->controller->request->input->exists($parameter) && (strtolower($this->controller->request->input->{$parameter})==strtolower($value)));
	}
	
	function build()
	{
		$result='';

		$rendered='';
	
		
		$templates['order_by']=new Template($this->orderby_template);
		$templates['radio']=new Template($this->radio_template);
		$templates['multi']=new Template($this->multi_template);
		$templates['lookup']=new Template($this->lookup_template); 
  		$templates['lookup_select']=new Template($this->lookup_select_template);
		$templates['lookup_checkbox']=new Template($this->lookup_checkbox_template);
		$templates['range']=new Template($this->range_template);
		$templates['text']=new Template($this->text_template);
		$templates['location']=new Template($this->location_template);
		$templates['date']=new Template($this->date_template);
		$templates['list']=new Template($this->list_template);
		$templates['divider']=new Template($this->divider_template);
		$templates['faceted']=new Template($this->faceted_template);
		$templates['token']=new Template($this->token_template);
		

		
		foreach($this->filters as $field => $section)
		{
			$value=$this->controller->request->input->{$field};

			if(!$this->use_filter || $this->use_filter==$field)
			{			
				switch($section->type)
				{
					case 'radio':
					case 'direction':
						$rendered.=$templates['radio']->render(array('meta' =>$this->controller->appmeta, 'field' => $field, 'section' => $section, 'control' => $this, 'value' => $value));
						break;
					case 'order_by':
					case 'multi':
					case 'range':
					case 'text':
					case 'list':
					case 'location':
					case 'date':
						$rendered.=$templates[$section->type]->render(array('meta' =>$this->controller->appmeta, 'field' => $field, 'section' => $section, 'control' => $this, 'value' => $value));
						break;
					case 'lookup':
			        case 'lookup_select':
					case 'lookup_checkbox':
						if ($section->facet) {
							$rows = $this->results['facet_counts'][$section->filter];
						}
						else
							$rows=Channel::GetDatasource($section->datasource,null,null,$count='not needed');
						$rendered.=$templates[$section->type]->render(array('meta' =>$this->controller->appmeta, 'field' => $field, 'section' => $section, 'control' => $this, 'items' => $rows, 'value' => $value));
						break;
					case 'faceted':	
					case 'divider':
						$rendered.=$templates[$section->type]->render(array('meta' =>$this->controller->appmeta, 'field' => $field, 'section' => $section, 'control' => $this, 'value' => $value, 'facets' => $this->results['facet_counts']));
						break;
				}
		    }
		    else if ($this->use_filter && isset($templates[$this->use_filter]))
		    { 
		    	$rendered.=$templates[$this->use_filter]->render(array('meta' =>$this->controller->appmeta, 'field' => $field, 'section' => $section, 'control' => $this, 'value' => $value));
		    }
		}
				
		if (!empty($this->container_template))
		{
			$view=new View($this->container_template,$this->controller);

			$result=$view->render(array('meta'=>$this->controller->appmeta, 'control' => $this, 'content' => $rendered));
		}
		else
		{
			$result=$rendered;
		}

		return $result;
	}
}
