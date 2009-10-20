<?
/**
 * Repeater Control
 *
 * @author		Nico Westerdale
 * @date		Apr 6, 2009
 * @time		12:45:16 AM
 * @file		groupie.php
 * @copyright  Copyright (c) 2009 massify.com, all rights reserved.
 * 
 * Templates have several counts available on them.
 * 
 * count				The current number of items rendered by the entire control
 * item_count			The current number of items rendered within just one container
 * item_count_total		The total number of items rendered within this container and all child containers
 * 
 */


uses('system.control.ui.data.databound_control');

class GroupieControl extends DataboundControl
{
	public $container_template=null;		/** container template */
	public $config_template=null;			/** config template file */
	public $section=null;                   /** section within the config file */
	public $editing=false;					/** Is in edit mode */
	
	/* not sure what this is */
	public $selected_id=null;
	
    /*
     * store references to current items
     */
    public $current=null;
    public $current_index=0;

    public $render_item_as_view=false;
    
	
	public function get_template($item_template)
	{
		if ($this->render_item_as_view)
			return new View($this->parent, $item_template);
		else
		        return new Template($item_template);		
	}
	/**
	 * Builds the control
	 *
	 * @return string
	 */
	public function build() {
		$result='';
		$rows=$this->get_data();
		$this->count=0;
		$config=Config::Get($this->config_template);
		$out=null;
		
		if($this->section)
		  $config=$config->{$this->section};

		if ($this->container_template!=null) {
			$view=$this->get_template($this->container_template);

			if ($rows && count($rows)>0)
			        $out = $this->build_item($config, $rows);
			
			$result=$view->render(array('total_count' => $this->total_count, 'count' => $this->count, 'control' => $this, 'content' => $out['content'], 'item_count_total' => $out['count']));
		} else {
			if ($rows && count($rows)>0)
			        $out = $this->build_item($config, $rows);

			$result=$out['content'];
		}
		return $result;
	}
	
	
	/**
	 * Recursive function that builds one level based on the config file
	 * 	calls build_item_data, which in turn recurvisly calls this.
	 * Parameters: $config: 'config item', $rows: 'data source'
	 *
	 * @return array
	 */
	private function build_item($config, $rows_full) { 
		$rendered='';
		$item_count_total=0;
		$rows_current=array();
		if ($config!=null) {
			if ($config->groupies) {
				
				// loop over all fields for groupies
				foreach ($config->groupies->items as $groupie) {
					$groupie = $groupie->group;
					
					$rows=$rows_full;
					
					//filter by all fields provided
					if ($groupie->fields) {
						//$rows = $this->filter_similar($groupie->fields->items[0]->items, $rows_full);	//this is AND not OR
						$rows_filter=$rows_full;
						foreach ($groupie->fields->items as $field)
							$rows_filter = $this->filter_similar($field->items, $rows_filter);	//this is OR :-)
						$rows=$rows_filter;
					}
					
					//perform the groupby
					if ($groupie->groupby) {
						foreach ($groupie->groupby->items as $groupby) {
							//run over rows and chunk them into groups based on the groupby item, handing that off to recurse through for each
							while (count($rows) > 0)
							{
								$rows_group=array();
								$rows_remaining=$rows;
								$offset=0;
								for ($i = 0; $i < count($rows); $i++)
								{
									if ($rows[0][$groupby]==$rows[$i][$groupby]) 
									{
										array_push($rows_group, $rows[$i]);		//add row to the group of rows
										$rows_remaining = $this->RemoveElement($i+$offset,$rows_remaining);		//remove row from remaining list
										$offset--;
									}
								}
								$rows=$rows_remaining;
								
								$out = $this->build_item_data($groupie, $rows_group);
								$rendered.=$out['content'];
								$item_count_total+=$out['count'];
								$rows_current+=$out['rows'];
							}
						}
					} else {
						//no groupby needed
						$out = $this->build_item_data($groupie, $rows);
						$rendered.=$out['content'];
						$item_count_total+=$out['count'];
						$rows_current+=$out['rows'];
					}
				}
			}
		}
		
		$out = array('content'=>$rendered, 'count'=>$item_count_total, 'rows'=>$rows_current);
		return $out;
	}
	
	private function RemoveElement($Position, $Array) {
		for($Index = $Position; $Index < count($Array) - 1; $Index++)
			$Array[$Index] = $Array[$Index + 1];
		array_pop($Array);
		return $Array;
	} 

	/**
	 * Recursive function that renders data based on the config file
	 * 	called by build_item, and recurvisly calls build_item again
	 * Parameters: $groupie: 'config item', $rows: 'data source'
	 *
	 * @return array
	 */
	private function build_item_data($groupie, $rows) {
		$rendered='';
		$item_count=0;
		$item_count_total=0;
		$rows_current=array();
		// process the item template (lowest levels only)
		if (($rows!=null) && ($groupie->item_template!=null)) {
			
			// if there is a field map defined, map the values from the row
			// into the control->attributes array
			if($groupie->control_attribute_map) {
				$map_items = $groupie->control_attribute_map->items;

				foreach($map_items as $item)
				{	
					foreach($item->items as $attribute => $mapping)
						$this->attributes[$attribute] = $rows[0][$mapping];
				}
			}
			
			
			try {
				$template=$this->get_template($groupie->item_template);
			} catch (Exception $e) { 
				// The only exception currently thrown is if the item template file isn't there
				// (in this case, we'll look for item.php in the same location)
				$item_segments = explode("/", $groupie->item_template);
				array_pop($item_segments); // ditch original file name
				$item_segments[] = "item"; // add default file 
				$template=new Template(implode("/", $item_segments));
			}

			$total_rows = count($rows);
			foreach($rows as $row) {
				$rendered.=$template->render(array('item' => $row, 'control' => $this, 'count' => $this->count, 'total_count' => $this->total_count, 'item_count' => $item_count, 'total_rows' => $total_rows));
				
				$this->current=&$row;
				
				if ($this->selected_id==$row['id'])
					$this->current_index=$this->count;
				
				$this->count++;
				$item_count++;
				$item_count_total++;
			}
			$rows_current=$rows;
		} else {
			//Keep on recursing as no item_templates present
			$out = $this->build_item($groupie, $rows);
			$rendered.=$out['content'];
			$item_count_total+=$out['count'];
			$rows_current+=$out['rows'];
		}
		
		// process the container if present, passing in all rendered content
		if ($groupie->container_template!=null) {
			$view=$this->get_template($groupie->container_template); //View($this->parent,$groupie->container_template);  // shitloads faster to use Template
			$rendered=$view->render(array('current_index' => $this->current_index, 'total_count' => $this->total_count, 'count' => $this->count, 'control' => $this, 'content' => $rendered, 'item_count' => $item_count, 'item_count_total' => $item_count_total, 'items' => $rows_current, 'config' => $groupie));
		}
		
		$out = array('content'=>$rendered, 'count'=>$item_count_total, 'rows'=>$rows_current);
		return $out;
	}
	

	/*
	 * Filter out items based on properties array
	 * 
	 * @returns array of rows data object
	 */
	private function filter_similar($props,$rows) {
		$result=array();
		for ($i = 0; $i < count($rows); $i++) {
			$row = $rows[$i];
			if ($row instanceof Model)
				$row = $row->to_array();
				
			$intersect=array_intersect_assoc($props,$row);
			if ($intersect==$props) {
				$result[] =& $rows[$i];
			}
		}
		return $result;
	}
	
}