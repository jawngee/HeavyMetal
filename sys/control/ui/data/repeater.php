<?
/**
 * Repeater Control
 *
 * @author		user
 * @date		Jun 13, 2007
 * @time		12:45:16 AM
 * @file		repeater.php
 * @copyright  Copyright (c) 2007 massify.com, all rights reserved.
 */


uses('system.control.ui.data.databound_control');

class RepeaterControl extends DataboundControl
{
	public $item_template=null; 			/** item template */
	public $container_template=null;		/** container template */
	public $editing=false;					/** Is in edit mode */
	
	/*
	 * Data returned from the datasource
	 * 
	 */
	public $rows=null;

    /*
     * store references to current items
     */
    public $current=null;
    public $current_index=0;
    /*
     * flag for checking similar items in a recordset 
     * totatly dependant on the recordsets having an ID field
     */
	public $similar_ids=array();
	
	public function get_template($item_template)
	{
		return new Template($item_template);
	}
	/**
	 * Builds the control
	 *
	 * @return string
	 */
	public function build()
	{
		$result='';

		$this->rows=$this->get_data();			

		$rendered='';
		$this->count=0;		

		if (($this->rows!=null) && ($this->item_template!=null))
		{

			try
			{
				$template=$this->get_template($this->item_template);
			}
			catch (Exception $e)
			{ 
				// The only exception currently thrown is if the item template file isn't there
				// (in this case, we'll look for item.php in the same location)
				$item_segments = explode("/", $this->item_template);
				array_pop($item_segments); // ditch original file name
				$item_segments[] = "item"; // add default file 
				
				$template=new Template(implode("/", $item_segments));
			}

			foreach($this->rows as $key => $row)
			{
				$rendered .= $this->render_template($template, $key, $row);
		    }
		}

		if ($this->container_template!=null)
		{
			$result = $this->render_container($this->container_template, $rendered);
		}
		else
			$result=$rendered;

		return $result;
	}
	
	
	/**
	 * Factored out of build to allow subclasses to override this behavior
	 *
	 * @param $template Template
	 * @param $key
	 * @param $row
	 * 
	 * @return string
	 */
	protected function render_template($template, $key, $row)
	{
		$rendered_template = '';
		
		if (is_numeric($key)) // With SOLR, some meta info gets added to the rows which repeater should ignore
        {
			$id = (($row instanceof Model)||($row instanceof Document)) ? $row->id: $row['id'];
			if (!array_key_exists($id,$this->similar_ids))
			{
				$rendered_template=$template->render(array('item' => $row, 'control' => $this, 'count' => $this->count, 'total_count'=>$this->total_count));
			}
			
			$this->current=&$row;
			$this->current_index++;
			$this->count++;
        }
        
        return $rendered_template;
	}
	
	
	protected function render_container($template, $rendered)
	{
		$view=new View($template,$this->controller);
		
		return $view->render(array('total_count'=>$this->total_count, 'count'=>$this->count, 'control' => $this, 'content' => $rendered));
	}
	
	/*
	 * Groups like items
	 * 
	 * @return mixed Array of items with similar properties
	 */
	public function fetch_similar($props,$same_day=true)
	{
		$result=array();

		$last_date=strtotime($this->current['created'])-(24*60*60);

		for ($i = $this->current_index; $i < count($this->rows); $i++)
		{
			$c = strtotime($this->rows[$i]['created']);

			if ($c<$last_date && $same_day)
				break;

			$intersect=array_intersect_assoc($props,$this->rows[$i]->to_array());
			
			if ($intersect==$props)
			{
				$this->similar_ids[$this->rows[$i]->id]='used';
				$result[] =& $this->rows[$i]->to_array();
			}
		}
		return $result;
	}
	
	/*
	 * Returns repeater child node content
	 * 
	 */
	public function get_contents($node=null)
 	{
  		if($node!=null && $contents = (string)$this->content->{$node})
 			return $contents;
	}
}
