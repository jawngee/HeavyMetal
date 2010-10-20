<?
/**
 * Repeater Control
 *
 * @author		nc@trunkarchive.com
 * @date		Oct. 19th, 2010
 * @time		2:37 PM
 * @file		nonrepeater.php
 * @copyright  Copyright (c) 2010 trunkarchive.com, all rights reserved.
 */

class NonRepeaterControl extends Control
{
	public $item_template=null; 			/** item template */
	
	/**
	 * Builds the control
	 *
	 * @return string
	 */
	public function build()
	{
		$view=new View($this->item_template,$this->controller);	
		return $view->render(array('count'=>1, 'control' => $this, 'item' => $this->datasource));
	}
	
}
