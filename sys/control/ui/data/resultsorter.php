<?
/**
 * Search Result Filter Control
 *
 * This control is used to show sort criteria
 * for search results
 * 
 * At base, this is a Repeater with some extra information
 * and methods at it's disposal to interpret a primary result set
 *
 */

uses('system.control.ui.data.repeater');


class ResultSorterControl extends RepeaterControl
{
	
	public $label = '';
	
	
	public function init()
	{
		parent::init();

		$this->label = $this->controller->appmeta->sort->order_by->label;
		
		// Copy the sort options from the config file as the repeater datasources
		$index = 0;
		foreach ($this->controller->appmeta->sort->order_by->options as $opt => $values)
		{
			foreach ($values as $key => $value)
				$this->datasource[$index][$key] = $value;		
		
			$index++;
		}
		
		// Set container and item templates from config file if not passed into tag
		if (!$this->item_template)
			$this->item_template = $this->controller->appmeta->renderer_map->sort->item;
			
		if (!$this->container_template)
			$this->container_template = $this->controller->appmeta->renderer_map->sort->container;
	}
	
}