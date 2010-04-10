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
 * SOLR Search Controller
 *
 * Controller implements SOLR Search behavior (including fulltext, facets, counts, and keyword highlighting).  
 * After setting up SOLR to mirror your current search datasource, you need only set up a configuration file
 * and ensure that your search controller now extends SOLRSearchController.
 * 
 * You must specify metadata when creating subclasses of this controller.  
 * 
 * NOTE: If you override any of these methods, YOU MUST reintroduce the screens as metadata is not
 * inherited from parent classes.
 */
 
uses('system.data.controller.generic_search_controller');

class SOLRSearchController extends GenericSearchController
{		
	
	public function index($filter=null)
 	{
 		$data = parent::index($filter);

 		$data['spellcheck'] = $data['results']['spellcheck'];
 		
 		return $data;
 	}
	
    protected function get_filter($initial_filter_string=null)
    {
        uses('system.data.search.solr.solr_filter');
        $smodel=$this->appmeta->search_model;
        
        uses("model.$smodel");
        
        $class=str_replace('_','',array_pop(explode('.',$smodel)));
        $instance=new $class();
        
        $filter = new SOLRFilter($instance,$class,false);
       
        // Set query parser if specified
        $query_parser = $this->appmeta->query_parser;
        $filter->query_parser = $query_parser;

        // Set result format if specified
        $result_format = $this->appmeta->result_format;
        $filter->result_format = $result_format;

        // Grab facet search extension if present
        $facet_search_ext = $this->appmeta->facet_search_ext;
        $filter->facet_search_ext = $facet_search_ext;
        
        // Load boost function if present
        $boost_function = $this->appmeta->boost_function;
        $filter->boost_function = $boost_function;

        // Turn on clustering if requested
        if ($this->appmeta->clustering)
        	$filter->clustering = true;
        
        // Turn on spellcheck if requested
        if ($this->appmeta->spellcheck)
        	$filter->spellcheck = true;
        	
        // Load facet config if present
        $facet_configs = $this->appmeta->facets;
		foreach ($facet_configs as $key => $value)
	        $filter->facet->{$key} = $value;
            
        if ($initial_filter_string)
            $vars = $filter->parse($initial_filter_string);
		else if ($this->init_filter())
			$vars = $filter->parse($this->init_filter());
  
        return $filter;
    }

    public function build_filter_field($filter, $key, $section)
 	{
 	 	// set up the faceting params
 		if ($section->facet)
 		{
 			$sf=($section->facet->field)?$section->facet->field:$section->filter;
 			$facet = $filter->facet->{$sf};
 			$facet->field_ext = $filter->facet_search_ext;
			
 			$facet->filter_name = $section->filter;
 			
 			foreach($section->facet as $attr=>$value)
 				$facet->{$attr} = $value;

 			// Handle count reducing tag/exclude for multi-choice filter fields
 			if ($section->type == 'lookup_checkbox')
 			{
 				$facet->multi = true;
 			}

 		} 		

 		parent::build_filter_field($filter, $key, $section);
 	}
 	
 	
 	public function handle_location($filter, $section, $key)
 	{
 		$sf = $section->filter;

 		if ($this->get->lat && $this->get->long && $this->get->radius)
 		{
	 		$filter->lat->value = 'lat='.$this->get->lat;
	 		$filter->long->value = 'long='.$this->get->long;
	 		$filter->radius->value = "$key=".$this->get->radius;

 		}
 	}

 	
 	public function handle_text_query($filter)
 	{
 		$req_text_query = $this->get_text_query();
 		
		if (isset($this->appmeta->text_query))
		{

 			foreach($this->appmeta->text_query as $val)
 			{
 				$filter_description_tokens[] = $req_text_query;
 			
 				$filter->{$val}->q_param = true;
 				$filter->or->{$val}->contains($req_text_query);
 			}
		}
		else
		{
			// set q_value in solr_filter directly
			$filter->q_value = $req_text_query;
		}	

 		if ($req_text_query)
 		{
	 	    // currently only using highlighting for text_query (not narrowing filters)
	 	    // but this can be changed if desired
	 	    
	 		foreach($this->appmeta->highlight as $key => $value)
	 		{
	 			if ($key=='fields')
		 			foreach ($value as $field)
			 			$filter->highlight->{$field};
	 			else
	 				$filter->highlight->{$key} = urlencode($value); 
	 		}	
 		} 	 		
 	}
}
