<?
/**
 * jQuery Ajax Renderer
 *  
 * @package		application
 * @subpackage	request
 * @link          http://wiki.getheavy.info/index.php/Ajax_Renderer
 */
class jQueryRenderer
{

	public function __construct()
	{
		content_type('text/javascript');
	}
	
	/**
	 * Render
	 * 
	 * @param $id
	 * @param $attrs
	 * @param $content
	 * @return string
	 */
	public function render($tag, $attrs, $content)
	{
		
		$result='';
		$selector=str_replace('"', '\"', $attrs['selector']);
		    
		switch($tag)
		{
			case 'update':
				$result="$(\"".$selector."\").html(\"".$content."\");";
			break;
			
			case 'prepend':
				$result="$(\"".$selector."\").prepend(\"".$content."\");";
			break;
			
			case 'append':
				$result="$(\"".$selector."\").append(\"".$content."\");";
			break;
			
			case 'before':
				$result="$(\"".$selector."\").before(\"".$content."\");";
			break;
			
			case 'after':
				$result="$(\"".$selector."\").after(\"".$content."\");";
			break;
			
			case 'replace':
				$result="$(\"".$selector."\").replaceWith(\"".$content."\");";
			break;
			
			case 'insert':
				$where=(isset($attrs['where'])) ? $attrs['where'] : 'before';
				$result="$(\"".$selector."\").".strtolower($where)."(\"".$content."\");";
			break;
			
			case 'remove':
				$fade=(isset($attrs['fade'])) ? $attrs['fade'] : false;
				if ($fade)
			       $result='$("'.$selector.'").fadeOut("' . $fade . '", function() { $(this).remove(); });';
				else
			       $result='$("'.$selector.'").remove();'; 
			break;
			case 'hide':
				$result='$("'.$selector.'").hide();'; 
			break;
			
			case 'show':
				$result='$("'.$selector.'").show();';
				
			default:
				$result="Tag '$tag' not implemented.";
		}
		
		return $result;
	}
}
