<?
/**
 * Prototype Ajax Renderer
 *  
 * @package		application
 * @subpackage	request
 * @link          http://wiki.getheavy.info/index.php/Ajax_Renderer
 */
class PrototypeRenderer
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
				$result="Element.update(\"".$attrs['id']."\",\"".$content."\");";
			break;
			case 'replace':
				$result="Element.replace(\"".$attrs['id']."\",\"".$content."\").remove();";
			break;
			case 'insert':
				$where=(isset($attrs['where'])) ? $attrs['where'] : 'before';
				$result="new Insertion.".ucfirst(strtolower($where))."(\"".$attrs['id']."\",\"".$content."\");";
			break;
			case 'remove':
				$fade=(isset($attrs['fade'])) ? $attrs['fade'] : false;
				if ($fade)
			       $result='Effect.Fade("'.$attrs['id'].'",{ afterFinish:function(effect) { $(effect.element).remove(); }});';
				else
			       $result='$("'.$attrs['id'].'").remove();'; 
			break;
			case 'hide':
				$result='$("'.$attrs['id'].'").hide();'; 
			break;
			case 'show':
				$result='$("'.$attrs['id'].'").show();';
			
			default:
				$result="Tag '$tag' not implemented.";
		}
		
		return $result;
	}
}
