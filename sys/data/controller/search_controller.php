<?
/**
 *
 * Copyright (c) 2009, Jon Gilkison and Trunk Archive Inc.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *   this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright
 *   notice, this list of conditions and the following disclaimer in the
 *   documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * This is a modified BSD license (the third clause has been removed).
 * The BSD license may be found here:
 * 
 * http://www.opensource.org/licenses/bsd-license.php
 *
 *
 * Controller implements search behavior generically.  Typically you will only need to 
 * set up a configuration file, create a couple of views and be up and running.
 * 
 * You must specify metadata when creating subclasses of this controller.  
 * 
 * NOTE: If you override any of these methods, YOU MUST reintroduce the screens as metadata is not
 * inherited from parent classes.
 */

uses('system.app.request.proxy.search_request_proxy');

class SearchController extends Controller
{
	/*
	 * Context for the config file
	 *
	 * @var Stirng
	 */
	protected $context='search';

	protected $no_query_text = '';
	
	/**
	 * The application specific metadata
	 *
	 * @var AttributeReader
	 */
	public $appmeta=null;
		
	/**
	 * Constructor
	 * 
	 * @param string $root The root uri path
	 * @param array $segments The uri segments following the root path
	 * @param bool $from_controller
	 */
	public function __construct($request)
 	{
 		parent::__construct($request);
 		$filename=PATH_CONFIG.$this->context."/".$this->metadata->app.".conf";

 		if (file_exists($filename))
 		{
 			$yaml=file_get_contents($filename);
 			$this->appmeta=new AttributeReader(yaml_parse($yaml));
 		}
 		else 
 			throw new Exception("No app metadata specified.");

 		// Wrap the controller's Request object in a simplified proxy for search 
 		$this->request = new SearchRequestProxy($this->request);
 	}
 	 		
 	
 	/**
 	 * Loads a model from the config.
 	 *
 	 * @param string $smodel The model path, ie. profiles/profile
 	 * @param mixed $id The id of the model.
 	 * @return Model Instantiated model.
 	 */
	protected function get_model($smodel,$id=null)
	{
 		uses("model.$smodel");
		
 		$class=str_replace('_','',array_pop(explode('.',$smodel)));
 		return new $class($id);
	}	
	
	/**
	 * wrapper which allows subclasses to get away from the standard "?q=whatever" syntax
	 * and implement something different (e.g. more restful)
	 */
	public function get_text_query()
	{
		return $this->request->get_value('q');
	}
	
	public function get_text_query_description()
	{
		return '&quot;'.$this->get_text_query().'&quot;';
	}
	
	public function get_no_text_query_url($query)
	{
		$uri = $this->controller->request->uri;
		$uri->query->remove_value('q');
		
		return $uri->build();
	}
	
	protected function init_filter()
	{
		if ($this->appmeta->init_filter)
			return $this->appmeta->init_filter." & ";
			
		return "";
	}
	
	protected function get_filter($initial_filter_string=null)
	{
		$smodel=$this->appmeta->search_model;
 		
 		uses("model.$smodel");
 		
 		$class=str_replace('_','',array_pop(explode('.',$smodel)));
 		$instance=new $class();
 		
		$filter = new Filter($instance,$class);
		
		if ($initial_filter_string)
			$filter->parse($initial_filter_string);
		else if ($this->init_filter())
			$filter->parse($this->init_filter());

		return $filter;
	}
	
	protected function get_attributes()
	{
		return $this->appmeta->filter;
	}
 	
	public function search_tokens()
	{
		$tokens = array();
		
		if ($this->get_text_query())
			$tokens[] = array(
				'field'=>'query',
				'value'=>$this->get_text_query(), 
				'description'=>$this->get_text_query_description(), 
				'remove_url'=>$this->get_no_text_query_url($this->get_text_query(), $this->no_q_value));
		else
			$tokens[] = array(
				'field'=>'query',
				'value'=>null, 
				'description'=>$this->no_query_text, 
				'remove_url'=>null);

		if ($this->location)
		{
			$remove_url = $this->request->uri
				->query
				->remove_value('location');
			
			$tokens[] = array('field'=>'location', 'value'=>$this->location, 'description'=>$this->location, 'remove_url'=>$remove_url->build());
		}	
		
		foreach($this->appmeta->filter as $field=>$section)
		{
			$value=$this->request->get_value($field);

			if($section->description && $value)
			{
				if (!is_array($value))
					$value = array($value);
					
				foreach($value as $val)
				{
					$token = null;
					
					if($section->description_prepend)
						$token .= $section->description_prepend . ' ';
						
					$token .= $val;
					
					if($section->description_append)
						$token .= ' ' . $section->description_append;
				
					if (!empty($token))
						$tokens[] = array(
							'field'=>$field, 
							'value'=>$val, 
							'description'=>comma_or_explode($this->request->uri->multi_seperator, $token), 
							'remove_url'=>$this->remove_breadcrumb_url($field, $val));
				}
			}
		}
		
		return $tokens;
	}
	
	public function remove_breadcrumb_url($field, $value)
	{
		$uri = $this->request->uri
			->query
			->remove_value($field, $value);
			
		return $uri->build();
	}
	
	
 	/**
	 * Display the search page.
	 * 
	 * [[
	 * 
	 * view: generic_search/index
	 * ]]
	 */
    public function index($filter=null)
 	{
 		$filter = $this->build_filter($filter);

 		$lim = $this->request->uri->query->get_value('limit');
 		$pg = $this->request->uri->query->get_value('page');
 		
 		$filter->limit = ($lim) ? $lim : $this->appmeta->page_size;
 		$filter->offset=($pg) ? (($pg-1) * $filter->limit) : 0;

		// is this search a saved one?
/*
		if ($this->session->id)
			$current_saved_id = filter('search/saved')
				->profile_id->equals($this->session->id)
				->path->equals(rtrim($this->uri->root,'/'))
				->query->equals(urldecode(ltrim($this->uri->query->build(),'?')))
				->get_one('id');
*/				
		$results = $filter->get_rows();

		$count = (is_numeric($results['total_count'])) ? $results['total_count'] : $filter->get_count();

		//Need to adjust offset if filters have changed and we were at the end of a longer paginated list of previous results
		if ($filter->offset > $count)
			$filter->offset = floor($count/$filter->limit)*$filter->limit;

			
		return array(
			'form' => $this->appmeta->form,
		    'current_saved_id' => $current_saved_id,
			'sorts' => $this->appmeta->sort->order_by->options,
			'page_size' => $filter->limit,
 			'count' => $count,
			'filter_description_tokens' => $this->search_tokens(),
			'query' => $this->get_text_query(),
			'results'=> $results
 		);
 	}
 	

 	public function build_filter_field($filter, $key, $section)
 	{
 		$value = trim($this->request->get_value($key));
 		
 		// Add to the description string
 		if ($value && ($section->description) )
 		{
			$description = null;
			
			if ($section->description_before)
				$description .= $description_before;
				
			$description .= $value;
			
			if ($section->description_after)
				$description .= $description_after;
				
 			$filter_description_tokens[$key] = $description;
 		}

 		
 		switch($section->type)
 		{
 			case "radio":
 			case "multi":
 				foreach($section->options as $name=>$option)
                     {
                         if ($value==$name){
                             $filterstr.=$option->filter.'&';
                             }
                     }
                     break;
 			case "faceted":
 				$sf=$section->filter;
 				if ($value)
 				{
 					if ($section->select_multiple) // multi-select
 					{
	                	$arr = $this->request->get_array($key);
						if ($section->join_model && $section->join_column && $section->join_foreign_column)
						{
							$join_filter = filter($section->join_model);
	                        if($section->case_insensitive)   
	                            $join_filter->{$sf}->is_in($arr, $section->allow_nulls, 'lower');
							else
		    					$join_filter->{$sf}->is_in($arr, $section->allow_nulls);
	                        $join_filter->select='';
	                            $filter->join($section->join_column, $join_filter, $section->join_foreign_column, ($section->allow_nulls)?'LEFT':'INNER');                          
	                    }
	                    else
	                    {
	                        if($section->case_insensitive)    
	        	                $filter->{$sf}->is_in($arr, $section->allow_nulls, 'lower');
	                        else
	            	            $filter->{$sf}->is_in($arr, $section->allow_nulls);
	                    }
	 						
 					}
 					else // single select
 					{
 						if ($section->join_model && $section->join_column && $section->join_foreign_column)
	 					{
	                        $join_filter = filter($section->join_model);
	                        
	                        if ($section->case_insensitive)
		                        $join_filter->{$sf}->equals($value, $section->allow_nulls, 'LIKE', 'lower');
	                        else
		                        $join_filter->{$sf}->equals($value);
		                    $join_filter->select='';
	                        $filter->join($section->join_column, $join_filter, $section->join_foreign_column, ($section->allow_nulls)?'LEFT':'INNER'); 							
	 					}
	 					else
	 					{
							if ($section->case_insensitive)
	 							$filter->{$sf}->equals($value, $section->allow_nulls, 'LIKE', 'lower');
	 						else
	 							$filter->{$sf}->equals(array($value), $section->allow_nulls);
	 					}
 					}
 				}
 				break;
 			case "text":
 				$sf=$section->filter;
 				if ($value)
 				{
 				    if ($section->join_model && $section->join_column && $section->join_foreign_column)
                        {
                            $join_filter = filter($section->join_model);
                            $join_filter->{$sf}->contains(array($value));
                            $join_filter->select='';
                            $filter->join($section->join_column, $join_filter, $section->join_foreign_column);                          
                        }
                        else
                        {
                            $filter->{$sf}->contains(array($value));
                        }
 				}
 			    break;
 			case "date":
 				$sf=$section->filter;
 				if ($value)
 					$filter->{$sf}->greater_equal(date('m/d/Y',time()-($value * 24 * 60 * 60)));
 				break;
 			case "location":
 				$sf=$section->filter;
				$this->handle_location($filter, $section, $key);
                break;

 		}
 	
 	}
 	
 	public function handle_location($filter, $section, $key)
 	{
 		$sf = $section->filter;
 		
 		$lat = $this->lat;
 		$long = $this->long;
 		
 		if (is_numeric($lat) && is_numeric($long))
 		{
 			uses_model('location/object_location');
 			$distance_filter = ObjectLocation::DistanceFilter($lat, $long, $value, 'mi');
 			$filter->join($sf, $distance_filter, 'object_id');
 		}
 		
 	}
 	
 	public function handle_text_query($filter)
 	{
 		$text_query = $this->get_text_query();
 		
 		if ($text_query)
 		{
 			foreach($this->appmeta->text_query as $val)
 			{
 				$filter_description_tokens[] = $text_query;
 				
	 			$filter->or->{$val}->contains($text_query);
 			}	
 		} 		
 	}
 	
 	public function build_filter($filter=null)
 	{
 		$filter_description_tokens = array();
 		
 		if (!$filter)
	        $filter=$this->get_filter($this->init_filter());
	
 		$filter_attributes = $this->get_attributes();

 		// process the q parameter
 		if (!$filter->q_value) // q_value can be manually set in $filter if desired (e.g. re-searching based on spelling suggs)
 			$this->handle_text_query($filter);

 		// process the rest
 		foreach($filter_attributes as $key=>$section)
 		{
			// Handle nested filters
 			if ($section->type == 'grouping')
 				foreach($section->filters as $subkey=>$subsection)
 					$this->build_filter_field($filter, $subkey, $subsection);
 			
 			$this->build_filter_field($filter, $key, $section);
 		}
 		
		
 		$filterstr=trim($filterstr,'&');
 		
 		$filter->parse($filterstr);

 		
 		$sb = strtolower($this->request->get_value('order_by'));
 		$od = strtolower($this->request->get_value('direction'));
 
 		if ($sb)
 		{
  			foreach($this->appmeta->sort->order_by->options as $key => $params)
 			{
 				if (strtolower($params->orby_by) == strtolower($sb))
 				{
 					$sb = $params->orby_by;
 					$od = ($od)?$od:strtolower($params->direction);
 					$filter->order_by->{$sb}->{$od};
 					break;
 				}
 			}
 		}
 		else
 		{
 			// Are there any default sort criteria defined
 			$order_by = $this->appmeta->sort->order_by;
 			if ($order_by)
 			{
 				$options = $order_by->options;
 				if ($options)
 				{
 					$sorts = $options;
 					foreach($sorts as $key => $params)
 					{
 						if ($params->default)
 						{
	 						$sb = $params->orby_by;
	 						$od = ($params->direction)?strtolower($params->direction):'asc';
	 						$filter->order_by->{$sb}->{$od};
 						}
 					}
 				}
 			}
 		}
 		
 		// add in description
 		$filter->filter_description_tokens = $filter_description_tokens;
 		
		trace("controller",$filter->show_query());
		trace("controller",$filterstr);

        return $filter;
 	}

 	
}
