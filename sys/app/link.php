<?
/**
 * Allows creation of link tags, for use by controls for such
 * things as pagination, etc... supports Ajax pagination
 * 
 * @package		HeavyMetal
 * @category 	Application
 * @author     	Peter Dixon-Moses (pd@massifycorp.com)
 * @copyright  	2007 Massify LLC
 */

uses('sys.app.request.query');

/**
 * LINK
 */
 class Link
 {
 	public $uri='';
 	public $ajax=false;
	public $link_child='';
	public $control_id=null;
	public $render_container_id=null;
	public $other_attributes=array();
	
 	//public $segments=
 	//public $query=null;
 	
 	public function __construct($uri, $link_child=null, $ajax=false, $control_id=null, $render_container_id=null, $other_attributes=array())
 	{
 		$this->uri=$uri;
 		$this->ajax=$ajax;
		$this->link_child=$link_child;
		$this->control_id=$control_id;
 		$this->render_container_id=$render_container_id;
 		$this->other_attributes=$other_attributes;
 	}

 	/**
 	 * Switches to refreshing using onclick and Prototype for AJAX
 	 */
 	function set_ajax_container_id($ajax_container_id)
 	{
		$this->render_container_id=$ajax_container_id;
	} 		
	
 	/**
 	 *  Inspect this value
 	 */
 	function get_ajax_container_id()
 	{
		return $this->render_container_id;
	} 	
	
 	/**
 	 * Returns the entire <a...>link_child</a>
 	 */
 	function build()
 	{ 
		
 	//	onclick="new Ajax.Request('{{$link}}',{method:'get',parameters:{filter:'{{$field}}'},requestHeaders:['X-Render-Partial','{{$control->id}}','X-Response-Target','{{$control->response_target}}']});
 		$link_string = "<a ";
		if ($this->ajax) { // Using AJAX 
			$link_string .= "onclick=\"new Ajax.Request('".$this->uri."',{requestHeaders:['X-Render-Partial','" . $this->control_id . "','X-Response-Target','" . $this->render_container_id . "']})\" ";
			$link_string .= "href=\"javascript:void(0)\" ";
		} else { // Not Using AJAX
			$link_string .= "href=\"".$this->uri."\" ";
		}
		$link_string .= implode(", ", $this->other_attributes);
		$link_string .= ">";
		$link_string .= $this->link_child;
		$link_string .= "</a>";

		return $link_string;
 	}
}