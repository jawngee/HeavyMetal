<?
/**
 * View helpers for html related business.
 */

/**
 * Generates javascript to post a form via ajax.
 * 
 * Partial rendering allows you to post to a controller and only have it render a single 
 * control on that page.  Use it by specifying the php ID of the control
 * 
 * @param string $form_id The javascript ID of the form to post.
 * @param string $confirm A confirm message to display to the user.
 * @param string $callback Javascript code to execute on callback.
 * @param string $partial The php ID of the control to render, if doing partial rendering.
 * @param string $target The javascript ID of the element to update with the result (Partial rendering only.)
 * @return string The generated javascript.
 */
function post_form($form_id, $confirm=null, $callback=null, $partial=null, $target=null)
{
	$result='';
	$confirm_callback='';

	if ($confirm)
		$confirm_callback="Overlaid.hide();";

	$headers='';
	
	if ($partial)
		$headers=" requestHeaders: ['X-Render-Partial','$partial','X-Response-Target','$target'], ";

	if($callback)
		$result.="new Ajax.Request($('$form_id').action, { $headers method:$('$form_id').method, parameters:$('$form_id').serialize(true),onComplete:function(){".$confirm_callback.$callback."}}); return false;";
	else
		$result.="new Ajax.Request($('$form_id').action, { $headers method:$('$form_id').method, parameters:$('$form_id').serialize(true),onComplete:function(){".$confirm_callback."}}); return false;";

	if ($confirm) {
		$result = "Overlaid.onConfirm = function(){".$result."}.bind(Overlaid); Overlaid.confirm('Confirm', '$confirm', '  Ok  ');";
	}

	return $result;
}


function big_link($link, $text, $classname='panel-button', $rel=null, $a_classname=null)
{
	$string = "<div class=\"$classname\">" .
			"	<div class=\"pbl\"> </div>" .
			"	<div class=\"pbm\"><a class=\"$a_classname\" rel=\"$rel\" href=\"$link\">$text</a></div>" .
			"	<div class=\"pbr\"> </div>" . 
		"</div>";
	return $string;
}

function plus_button($text,$href,$id="plus_button",$class="button1 plus-button")
{
	return "<a id=\"$id\" href=\"$href\" class=\"$class\">" .
	"	<img src=\"/i/all/btns/plus.png\">" . 
	"	$text" . 
	"</a>";
}
 
/**
 * 
 * Generates an html option element.
 * 
 * @param $name
 * @param $value
 * @param $current_value
 * @return unknown_type
 */
function option($name,$value,$current_value=null)
{
	return "<option value='$value'".(((string)$current_value==(string)$value)?" selected='true' ":"").">$name</option>";
}

/**
 * 
 * Generates an html radio element.
 * 
 * @param $name
 * @param $value
 * @param $current_value
 * @param $class
 * @return unknown_type
 */
function radio($name,$value,$current_value=null,$class='no-border')
{
	return "<input ".(($class==null)?"":" class='$class'")."type='radio' name='$name' value='$value'".(($current_value==$value)?" checked='true' ":"")." />";
}

/**
 * 
 * Generates an html checkbox element.
 * 
 * @param $name
 * @param $value
 * @param $current_value
 * @param $class
 * @return unknown_type
 */
function checkbox($name,$value,$current_value=null,$class='no-border')
{
	echo "<input type='checkbox' ".(($class==null)?"":"class='$class'")." id='$name' name='$name' value='$value'".(($value==$current_value)?" checked='true' ":"")." />";
}

/**
 * Fetches the value from an input object, returning a default value if it doesn't exist.
 * 
 * @param $what
 * @param $input
 * @param $object
 * @return unknown_type
 */
function get_value($what,$input=null,&$object=null)
{
	if ($input && $input->exists($what)) {
		return $input->$what;
	} elseif (isset($object->fields[$what])) {
		return $object->$what;
	}  elseif ($object!=null && count($object->fields) == 0) {
		return $object;		//NW: added this to allow passing a simple object without fields
	} else {
		return null;
	}
	//original:
	//return (($input && $input->exists($what)) ? $input->get_string($what) : ((isset($object->fields[$what])) ? $object->$what : null));
}


/**
 * 
 * @param $what
 * @param $input
 * @param $object
 * @param $options
 * @param $class
 * @param $assoc
 * @param $use_id
 * @return unknown_type
 */
function select($what,$input=null,$object=null,$options,$class=null,$assoc=true,$use_id=true)
{
	$result="<select ".($class==null ? '' : "class='$class'").($use_id==true ? " id='$what'" : '')." name='$what'>\n";
	foreach($options as $value => $name)
	{
		if($assoc)
			$result.=option($name,$value,get_value($what,$input,$object))."\n";
		else
			$result.=option($name,$name,get_value($what,$input,$object))."\n";
	}
	$result.="</select>\n";

	return $result;
}

/**
 * 
 * @param $what
 * @param $input
 * @param $object
 * @param $options
 * @param $class
 * @param $id
 * @param $label
 * @return unknown_type
 */
function select_recordset($what,$input=null,$object=null,$options,$class=null,$id,$label,$use_id=true)
{
	$result="<select ".($class==null ? '' : "class='$class'").(!$use_id ? '' : " id='$what'")."' name='$what'>\n";
	foreach($options as $option)
		$result.=option($option[$label],$option[$id],get_value($what,$input,$object))."\n";
	$result.="</select>\n";

	return $result;
}

/**
 * Generates a list.
 * 
 * @param $class
 * @param $data
 * @return unknown_type
 */
function ul($class,$data,$tag='ul')
{

	$result="<$tag class='$class'>\n";
	if (!is_array($data))
		$result.="<li>$data</li>\n";
	else
		foreach($data as $item)
			$result.="<li>$item</li>\n";
	$result.="</$tag>\n";

	return $result;
}

/**
 * Replace line breaks with br tags.
 * 
 * @param string $value The string to fix.
 * @return string
 */
function replace_breaks($value)
{
	return str_replace("\n","<br />",$value);
}

/**
 * Returns an empty string for null values, needed for database shit.
 * 
 * @param $value
 * @return unknown_type
 */
function null_string($value)
{
	return ($value==null) ? '' : $value;
}

/**
 * Parses markdown text and transforms it into html.
 * 
 * @param $text
 * @return unknown_type
 */
function markdown($text)
{
	uses('system.util.markdown');

	# Setup static parser variable.
	static $parser;
	if (!isset($parser)) {
		$parser_class = MARKDOWN_PARSER_CLASS;
		$parser = new $parser_class;
	}

	# Transform text using parser.
	return $parser->transform($text);
}

/**
 * This function is a fucking abortion. Shameel is a fucking poser.
 * 
 * @param $array
 * @param $url_segment1
 * @param $item_key1
 * @param $item_key2
 * @param $url_segment2
 * @param $item_key3
 * @return unknown_type
 */
function grouped_loop($array, $url_segment1, $item_key1, $item_key2, $url_segment2="", $item_key3="")
{
	/* Parameter examples:
	 * $url_segment1 = '/pitches/'
	 * $item_key1 = 'related_name'
	 * $item_key2 = 'object_name'
	 * $url_segment2 = '/cast/';
	 * $item_key3 = 'object_id'
	 *
	 */

	// Grabs the total count of array items and sets the counter at 1
	$item_count = count($array);
	$count = 1;
	$grouped_string = "";

	if ($item_count == 1)
	{
		$grouped_string .= sprintf("<a href=\"%s%s%s%s\">%s</a>",
			$url_segment1,
			$array[0][$item_key1],
			$url_segment2,
			$array[0][$item_key3],
			limit_string($array[0][$item_key2],30)
		);
	}

	if ($item_count>1)
	{
		foreach ($array as $item)
		{
			if ($count == $item_count) $grouped_string.= " and ";
			$grouped_string .= sprintf("<a href=\"%s%s%s%s\">%s</a>",
					$url_segment1,
					$item[$item_key1],
					$url_segment2,
					$item[$item_key3],
					limit_string($item[$item_key2],30)
				);
			if ($count < $item_count - 1) $grouped_string.= ", ";
			$count++;
		}
	}

	return $grouped_string;
}

/**
 * Prints an rss feed out.
 * 
 * @param $title
 * @param $type
 * @return unknown_type
 */
function insert_rss($title, $type)
{
	printf("<link rel=\"alternate\" type=\"application/rss+xml\" title=\"%s\" href=\"/rss/%s?%s\">",
		$title,
		$type,
		$_SERVER['QUERY_STRING']);
}

function json_from_array($array)
{
	$result='';
	foreach($array as $id => $value)
	{
		$result.="$id:'$value',";
	}
	return "{".rtrim($result,",")."}";
}

function jsarray_from_array($array)
{
	$result='';
	foreach($array as $id => $value)
	{
		$result.="'$id',";
	}
	return "[".rtrim($result,",")."]";
}



/* used in the repeat for find last item -
 * applies class name
 */
function last_item($total_count,$count,$class=' last')
{
	return ($total_count==$count)?$class:'';
}

function last_array_item($array,$item,$class=' last')
{
	return ($item==end($array))?$class:'';
}

function pluralize($count,$singular,$plural=false)
{
	if(!$plural) $plural=$singular.'s';
	return($count==1?$singular:$plural);
}

function trim_long_word($word, $maxlength)
{
	$words =  explode (" ", $word);
	$numwords = count ($words);
	if ($numwords==1 && strlen($words[0])>$maxlength)
	{
		return substr($word, 0, $maxlength).'...';

	}
	else return $word;
}

function media_src($item,$size)
{
	return IMAGE_SERVER."/".$item['file_path']."$size.jpg";
}

/* default value */
function default_value($checking, $default='Any')
{
	return ($checking==null)?$default:$checking;
}

/* applies alternating css class (zero based) */
function even_odd($count, $existing="", $even="even", $odd="odd")
{
	if($existing != "")
		$existing .= " ";
		
	return $existing . ((++$count % 2 == 0) ? $even : $odd);
}


