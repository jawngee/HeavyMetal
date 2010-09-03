<?
/**
 * Search Result Filter Control
 *
 * This control is used to show facets and sort criteria
 * for search results and faceted browsing
 * 
 * <php:resultfilters> is a convenience implementation if no customization is needed
 * It will iterate through the controller config file
 * and apply the appropriate templates using the ResultFilterControl.
 * 
 * You can also use ResultFilterControl stand-alone e.g. <php:resultfilter field="category" />
 */

uses('system.app.control');
uses('system.control.ui.data.resultfilter');


class ResultFiltersControl extends Control
{	
	/*
	 * The renderer map in the config file should look like this:
	 * 
	 * renderer_map:
	 *     lookup:
	 *         container: <path to template which performs any pre-processing/layout>
	 *         item: <path to template which performs the layout for each result>
	 *     malefemale:
	 *         container: <path to special template to limit choices and determine if one is selected>
	 *         item: <path to template to display link icons depending on what's been selected>
	 *
	 *
	 * TODO:  once this control is complete, retire searchfilters control and modularize generic_search_controller
	 * 
	 */
	
	public $container_template=null;
	public $datasource=null;   // usually facetcounts
	protected $filter_class = 'ResultFilterControl';
	
	function build()
	{
		$result='';
		$rendered='';
			
		$resultfilter = new $this->filter_class();
		$resultfilter->controller = $this->controller;
		$resultfilter->datasource = $this->datasource;
		
		foreach($this->controller->appmeta->filter as $field => $filter) {
			if ($filter->hidden != 'true') {
				$resultfilter->clear();
				$resultfilter->field = $field;
				$resultfilter->datasource = $this->datasource;
				$resultfilter->init();
	
				$rendered .= $resultfilter->build();
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