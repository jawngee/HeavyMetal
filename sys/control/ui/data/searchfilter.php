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
	public $grouping_template='ctrl/searchfilter/grouping';
	public $token_template='ctrl/searchfilter/token';
	public $container_template='ctrl/searchfilter/container';
	public $always_expanded=false;
	
	
	public $filters=null;
	
	public $use_filter=null;
	
	public $templates=array();
	
 	public function init()
	{
		parent::init();
		
		$this->templates['order_by']=new Template($this->orderby_template);
		$this->templates['radio']=new Template($this->radio_template);
		$this->templates['multi']=new Template($this->multi_template);
		$this->templates['lookup']=new Template($this->lookup_template); 
  		$this->templates['lookup_select']=new Template($this->lookup_select_template);
		$this->templates['lookup_checkbox']=new Template($this->lookup_checkbox_template);
		$this->templates['range']=new Template($this->range_template);
		$this->templates['text']=new Template($this->text_template);
		$this->templates['location']=new Template($this->location_template);
		$this->templates['date']=new Template($this->date_template);
		$this->templates['list']=new Template($this->list_template);
		$this->templates['grouping']=new Template($this->grouping_template);
		$this->templates['token']=new Template($this->token_template);
		
		
	}

	function link($parameter,$value,$removevalues=null)
	{
		return $this->controller->request->uri->build(null,null,array($parameter=>$value),$removevalues);
	}
	
	function option($parameter,$value)
	{
		return $this->controller->request->uri->build(null,null,array($parameter=>$value));
	}
	
	function value($parameter)
	{
		return $this->controller->request->input->{$parameter};
	}
	
	function multi_link($parameter, $value)
	{
		if ($this->controller->request->input->exists($parameter))
			return $this->controller->request->uri->build(null,null,null,array($parameter));
					
		return $this->link($parameter,$value);
	}
	
	function clear($parameter)
	{
		return $this->controller->request->uri->build(null,null,null,(is_array($parameter))?$parameter:array($parameter));
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
				return $this->controller->request->uri->build(null,null,null,array($parameter));
			else
				return $this->controller->request->uri->build(null,null,array($parameter=>$value));	
		
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
				return $this->controller->request->uri->build(null,null,array($parameter=>$values),array($parameter=>$value));
			}
			else
			{
				array_push($values,$value);
				return $this->controller->request->uri->build(null,null,array($parameter=>$values));	
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
		return $this->controller->request->uri->build(null,null,array($parameter=>$value, "direction"=>$direction));
	}
	
	function active_order($parameter,$value,$direction)
	{
		return (
			$this->controller->exists($parameter) && 
			($this->controller->{$parameter}==$value) &&
			($this->controller->direction==$direction)
		);
	}
	
	function active($parameter,$value)
	{
		return ($this->controller->exists($parameter) && (strtolower($this->controller->{$parameter})==strtolower($value)));
	}
	
	function build()
	{
		$result='';

		$rendered='';
		
		foreach($this->filters as $field => $section)
		{
			$rendered .= $this->render_filter($field, $section);
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
	
	
	function render_filter($field, $section)
	{
		$rtn = null;
		
		$value=$this->controller->request->input->{$field};

		if(!$this->use_filter || $this->use_filter==$field)
		{
			switch($section->type)
			{
				case 'radio':
				case 'direction':
					if (!$section->hidden)
						$rtn.=$this->templates['radio']->render(array('meta' =>$this->controller->appmeta, 'field' => $field, 'section' => $section, 'control' => $this, 'value' => $value));
					break;
				case 'order_by':
				case 'multi':
				case 'range':
				case 'text':
				case 'list':
				case 'location':
				case 'date':
					if (!$section->hidden)
						$rtn.=$this->templates[$section->type]->render(array('meta' =>$this->controller->appmeta, 'field' => $field, 'section' => $section, 'control' => $this, 'value' => $value));
					break;
				case 'lookup':
		        case 'lookup_select':
				case 'lookup_checkbox':
					if ($section->facet) {
						$rows = $this->results['facet_counts'][($section->facet->field)?$section->facet->field:$section->filter];
					}
					else
						$rows=Channel::GetDatasource($section->datasource,null,null,$count='not needed');
					
					if (!$section->hidden)
						$rtn.=$this->templates[$section->type]->render(array('meta' =>$this->controller->appmeta, 'field' => $field, 'section' => $section, 'control' => $this, 'items' => $rows, 'value' => $value));
					break;
				case 'grouping':
				{	
					$rendered_subfilters = ''; 
					if ($section->filters) {
						foreach($section->filters as $subfield => $subsection)
							$rendered_subfilters .= $this->render_filter($subfield, $subsection);
					}

					if (!$section->hidden)
						$rtn.=$this->templates[$section->type]->render(array('meta' =>$this->controller->appmeta, 'field' => $field, 'section' => $section, 'control' => $this, 'value' => $value, 'facets' => $this->results['facet_counts'], 'rendered_subfilters' => $rendered_subfilters));
					break;
				}
			}
	    }
	    else if ($this->use_filter && isset($this->templates[$this->use_filter]))
	    { 
	    	$rtn.=$this->templates[$this->use_filter]->render(array('meta' =>$this->controller->appmeta, 'field' => $field, 'section' => $section, 'control' => $this, 'value' => $value));
	    }		
	    
	    return $rtn;
	}
}
