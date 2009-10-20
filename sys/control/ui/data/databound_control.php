<?
uses('system.app.control');
uses('system.data.channel');
uses('system.app.view');
uses('system.app.template');
uses('system.app.link');
uses('system.app.request.uri');

/**
 * Base class for databound controls, such as the datagrid and the repeater.
 */
abstract class DataboundControl extends Control
{
	/**
	 * The name of the data channel to pull the datasource from.  DEPRECATED.
	 * 
	 * @deprecated 
	 * @var string
	 */
	public $channel=null;
	
	/**
	 * Determines if pagination should be turned on or not.
	 *
	 * @var bool
	 */
	public $allowpaging=true;				

	/**
	 * The number of items per page.
	 *
	 * @var int
	 */
	public $page_size=0;
	
	/**
	 * The current page number.
	 *
	 * @var int
	 */
	public $current_page=0;

	/**
	 * This can either be the uri query or a variable bound in the view.
	 * 
	 * For uri queries, the following schemes are supported: controller, channel, model.
	 * 
	 * The queries will look something like:
	 * 
	 * controller://path/path#results?arg1=val&q=asdads asd ad ad&arg=[123,232,123]
	 * channel://channel/datasource?arg1=val&q=asdads asd ad ad&arg=[123,232,123]
	 * model://profiles/profile_view?arg1!=val&q=asdads asd ad ad&arg=[123,232,123]
	 *
	 * @var mixed
	 */
	public $datasource=null;
	
	/**
	 * Total number of items in the datasource
	 *
	 * @var int
	 */
	public $total_count=null;
	
	/**
	 * Total number of items rendered
	 *
	 * @var int
	 */
 	public $count=0;
 	
 	
	public $lowerBound=0;
	public $upperBound=0;
	public $pageno=0;
	public $lastpage=0;

	/**
	 * Determines if ajax pagination should be used.
	 * 
	 * @todo Implement
	 * @var unknown_type
	 */
	public $ajax=false;

	/**
	 * The ajax api endpoint
	 *
	 * @todo Implement
	 * @var unknown_type
	 */
	public $api_endpoint=null;
	
	/**
	 * The template to use for pagination.
	 *
	 * @var unknown_type
	 */
	public $pagination_template='global/search/pagination';
	
	/**
 	 * Sorting in use  
 	 */
    public $sortable=null;
    
    /**
 	 * Filter in use  
 	 */
    public $filtrable=null;
	
	/**
	 * Initializes the control
	 */
 	public function init()
	{
		parent::init();
		
		if ($this->uri)  // allows override for pagination links
			$this->uri = new URI($this->uri);
		else if (isset($this->controller->uri))
			$this->uri = $this->controller->uri;
		else
		        $this->uri=new URI();

		if ($this->allowpaging)
		{		
			if ($this->uri->query->get_number($this->id.'_pg'))
				$this->current_page=$this->uri->query->get_number($this->id.'_pg');
	
			if ($this->current_page==0)
				$this->uri->query->remove_value($this->id.'_pg');
	    }
	    else
	    {
	    	$this->total_count = $this->count;  // defeats the get_count called in Channel
	    }

	}
	

	/**
	 * Generates the next page link
	 *
	 * @return string
	 */
	function next_page_link()
 	{
 		return $this->uri->build(null,array($this->id.'_pg' => $this->current_page+1));
 	}

 	/**
 	 * Generates the previous page link
 	 *
 	 * @return string
 	 */
 	function prev_page_link()
 	{
	 		return $this->uri->build(null,array($this->id.'_pg' => $this->current_page-1));
 	}
	
 	/**
 	 * Generates the page link.
 	 *
 	 * @param int $page
 	 * @return string
 	 */
 	function page_link($page)
 	{
		return $this->uri->build(null,array($this->id.'_pg' => $page));
 	}

	/**
     * Generates the Sorting
     * 
     * @return string
     */
    function sorting()
    {
    	if($this->sortable==null)
			return;
    
    	$result='';
		$rendered='';

		$template=new Template($this->sorting->item_template);
		
		foreach($this->sort_options as $field => $config_items)
		{
			$option = $config_items->items;
			$link = $this->uri->build(null,array("sortby"=>$field),array($this->id.'_pg'));			
			$rendered.=$template->render(array('sortby' => $this->sortby, 'field' => $field, 'option' => $option, 'control' => $this, 'link' => $link));				
		}
		
		if ($this->sorting->container_template!=null)
		{
			$view=new View($this->parent,$this->sorting->container_template);
			$result=$view->render(array('control' => $this, 'content' => $rendered));
		}
		else
			$result=$rendered;

		return $result;
    }
    
	/**
     * Generates the Filters
     * 
     * @return string
     */
    function filtering()
    {
    	if($this->filtrable==null)
			return;
    
    	$result='';
		$rendered='';

		$template=new Template($this->filtering->item_template);

		$link = $this->uri->build(null,null,array('filter'));
			
		foreach($this->filters as $field => $config_items)
		{
			$option = $config_items->items;
			$rendered.=$template->render(array('filter' => $this->filter, 'field' => $field, 'option' => $option, 'control' => $this, 'uri' => $link));				
		}
		
		if ($this->filtering->container_template!=null)
		{
			$view=new View($this->parent,$this->filtering->container_template);
			$result=$view->render(array('control' => $this, 'content' => $rendered));
		}
		else
			$result=$rendered;

		return $result;
    }
    
    protected function set_boundaries()
    {
    	$this->lowerBound = ($this->current_page * $this->page_size) + 1;
		$tempvar = ($this->lowerBound + $this->page_size - 1);
		$this->upperBound = ($tempvar <= $this->total_count) ? $tempvar : $this->total_count;

		$this->pageno = $this->current_page+1;
		$this->lastpage = ceil($this->total_count / $this->page_size);

		$this->firstpage  = $_SERVER['REQUEST_URI'];
		$this->firstpage  = explode($this->id."_pg", $this->firstpage);
		$this->firstpage  = $this->firstpage[0].'results_pg=0';

		if ($this->pageno < 1)
			$this->pageno = 1;
		elseif ($this->pageno > $this->lastpage)
			$this->pageno = $this->lastpage;
    }
 	
 	/**
 	 * Generates the pagination
 	 * 
 	 * @TODO: Clean this up, this is batshit insane.
 	 *
 	 * @return string
 	 */
 	function pagination($class='')
 	{
		if(!$this->allowpaging)
			return;	
			
		$this->set_boundaries();

		// Build links before passing to template to handle AJAX if necessary
		$firstpagelink = new Link($this->page_link(0), 1, $this->ajax, $this->id, $this->response_target);
		$prevpagelink = new Link($this->page_link($this->pageno-2), "<div class='pagin-left'></div>", $this->ajax, $this->id, $this->response_target);

		$fourprevpagelink = new Link($this->page_link($this->pageno-5), $this->pageno-4, $this->ajax, $this->id, $this->response_target);
		$threeprevpagelink = new Link($this->page_link($this->pageno-4), $this->pageno-3, $this->ajax, $this->id, $this->response_target); 
		$twoprevpagelink = new Link($this->page_link($this->pageno-3), $this->pageno-2, $this->ajax, $this->id, $this->response_target);
		$oneprevpagelink = new Link($this->page_link($this->pageno-2), $this->pageno-1, $this->ajax, $this->id, $this->response_target);
		
		$onenextpagelink = new Link($this->page_link($this->pageno), $this->pageno+1, $this->ajax, $this->id, $this->response_target);
		$twonextpagelink = new Link($this->page_link($this->pageno+1), $this->pageno+2, $this->ajax, $this->id, $this->response_target);
		$threenextpagelink = new Link($this->page_link($this->pageno+2), $this->pageno+3, $this->ajax, $this->id, $this->response_target);
		$fournextpagelink = new Link($this->page_link($this->pageno+3), $this->pageno+4, $this->ajax, $this->id, $this->response_target);

		$nextpagelink = new Link($this->page_link($this->pageno), "<div class='pagin-right'></div>", $this->ajax, $this->id, $this->response_target);
		$lastpagelink = new Link($this->page_link($this->lastpage-1), $this->lastpage, $this->ajax, $this->id, $this->response_target);
		
		$template = new Template ($this->pagination_template);
	
		$rendered=$template->render(array(
			'count' => $this->count,
			'lowerBound' => $this->lowerBound,
			'upperBound' => $this->upperBound,
			'pageno' => $this->pageno,

			'firstpage' => $this->firstpage,
			'lastpage' => $this->lastpage,
			
			'firstpagelink' => $firstpagelink->build(),
			'prevpagelink' => $prevpagelink->build(),

			'fourprevpagelink' => $fourprevpagelink->build(),
			'threeprevpagelink' => $threeprevpagelink->build(),
			'twoprevpagelink' => $twoprevpagelink->build(),
			'oneprevpagelink' => $oneprevpagelink->build(),

			'onenextpagelink' => $onenextpagelink->build(),
			'twonextpagelink' => $twonextpagelink->build(),
			'threenextpagelink' => $threenextpagelink->build(),
			'fournextpagelink' => $fournextpagelink->build(),		

			'nextpagelink' => $nextpagelink->build(),
			'lastpagelink' => $lastpagelink->build(), 
			'class' => $class
		));

		return $rendered;
 	}
 	 	
	/**
	 * Fetches the data
	 *
	 * @return mixed The data from the datasource
	 */
	protected function get_data($order_by=null, $dir='asc')
	{
		
		if($this->sortable!=null)
		{			
			$conf=Config::Get('search/sorts');
			$this->sorting = $conf->{$this->sortable};
	        $this->sort_options = $this->sorting->options->items;
	
	        if (!$this->sort_options)
	            throw new Exception("No sort options found");
			
			$this->sortby = ($this->controller->get->exists("sortby")) ? $this->controller->get->get_string("sortby") : $this->sortby = $conf->{$this->sortable}->default_option;
			
			$order_by = $this->sort_options[$this->sortby]->orby_by;
			$dir = $this->sort_options[$this->sortby]->direction;
		}
		
		if($this->filtrable!=null)
		{			
			$conf=Config::Get('search/filters');
			$this->filtering = $conf->{$this->filtrable};
	        $this->filters = $this->filtering->options->items;
	
	        if (!$this->filters)
	            throw new Exception("No filters found");

			$this->filter = ($this->controller->get->exists("filter")) ? $this->controller->get->get_string("filter") : $this->filter = $this->filtering->default_option;
			
			$filter = $this->filters[$this->filter]->filter;
			if($filter)
			{			
		        if (!strpos($this->datasource,'?'))
					$this->datasource.='?';
				else
					$this->datasource.='&';
					
				$this->datasource.=$this->filters[$this->filter]->filter;
			}
			
		}
		
		
		$rows=null;
		if ($this->channel!=null)
		{
			user_error('Using the channel attribute on a repeater is deprecated',E_USER_WARNING);
			
			$channel=Channel::Get($this->channel);
			$rows=$channel->datasource($this->datasource,$this->current_page*$this->page_size,$this->page_size,$this->total_count);
		}
		else if (gettype($this->datasource)=='string')
		{
			if (strpos($this->datasource,'://')>1)
			{			
				$ds=$this->datasource;
				if ($order_by)
				{
					if (!strpos($ds,'?'))
						$ds.='?';
					else
						$ds.='&';
					$ds.='order by '.$order_by.' '.$dir;
				}
				
				$rows=Channel::GetDatasource($ds,$this->current_page*$this->page_size,$this->page_size,$this->total_count);
			}
			else
			{
				user_error('Using datasources on controllers is deprecated',E_USER_WARNING);
				$rows=$this->controller->datasource($this->datasource,$this->current_page*$this->page_size,$this->page_size,$this->total_count);
			}
		}
		else
		{
			$rows=$this->datasource;
			if (is_array($rows))
			{
				if(isset($rows['total_count']))
					$this->total_count = $rows['total_count'];

				$this->count = (isset($rows['count'])) ? $rows['count'] : count($rows); 
			}
		}
			
		return $rows;
	}
}
