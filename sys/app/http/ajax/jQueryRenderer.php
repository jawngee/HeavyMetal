<?
/**
 * Prototype Ajax Renderer
 *  
 * @package		application
 * @subpackage	request
 * @link          http://wiki.getheavy.info/index.php/Ajax_Renderer
 */
class jQueryRenderer
{

	public function __construct(){}
	
	/**
	 * Render
	 * 
	 * @param $id
	 * @param $attr
	 * @param $content
	 * @return string
	 */
	public function render($tag, $attrs, $content)
	{
		
		$result='';
		    
		switch($tag)
		{
			case 'update':
				$result="$(\"".$attrs['selector']."\").html(\"".$content."\");";
			break;
			
			case 'prepend':
				$result="$(\"".$attrs['selector']."\").prepend(\"".$content."\");";
			break;
			
			case 'append':
				$result="$(\"".$attrs['selector']."\").append(\"".$content."\");";
			break;
			
			case 'replace':
				$result="$(\"".$attrs['selector']."\").replaceAll(\"".$content."\");";
			break;
			
			case 'insert':
				$where=(isset($attrs['where'])) ? $attrs['where'] : 'before';
				$result="$(\"".$attrs['selector']."\").".strtolower($where)."(\"".$content."\");";
			break;
			
			case 'remove':
				$fade=(isset($attrs['fade'])) ? $attrs['fade'] : false;
				if ($fade)
			       $result='$("'.$attrs['selector'].'".fadeOut("slow", function() { $(this).remove(); });';
				else
			       $result='$("'.$attrs['selector'].'").remove();'; 
			break;
			case 'hide':
				$result='$("'.$attrs['selector'].'").hide();'; 
			break;
			
			case 'show':
				$result='$("'.$attrs['selector'].'").show();';
				
			default:
				$result="Tag '$tag' not implemented.";
		}
		
		return $result;
	}
}
