<?
//return;
/**
 * Trace output for debugging purposes.
 *
 * @author		user
 * @date		Jun 2, 2007
 * @time		1:06:10 AM
 * @file		trace.php
 * @copyright  Copyright (c) 2007 massify.com, all rights reserved.
 */
trace('System','Trace End.');

// array of errors
global $page_errors;

// error cache to remove duplicates
global $error_cache;

// number of db hits
global $db_hits;
global $db_queries;
$queries=$db_queries;
$sesh=md5(time());
//if (isset($queries) && count($queries)>0)
//{
//	uses_model('system/db_stats');
//
//	foreach ($queries as $query)
//	{
//		$trace='';
//		foreach($query["backtrace"] as $backtrace)
//			$trace.=str_replace(PATH_ROOT, "", $backtrace["file"])." - ".(isset($backtrace["class"]) ? $backtrace["class"]."->" : "").$backtrace["function"]." - line # ".$backtrace["line"]."\r\n";
//
//		$stat=new DbStats();
//		$stat->stack_trace=$trace;
//		$stat->query=$query['query'];
//		$stat->query_time=$query['time'];
//		$stat->php_session=$sesh;
//		$stat->url=$_SERVER["PATH_INFO"];
//		
//		$stat->save();
//	}
//}

// debug log messages
global $debug_log;

// get the rendering time
$page_time=microtime(true)-REQUEST_START;

// get the result of the page rendering and print it
$result=ob_get_clean();
echo $result;

if (isset($_GET['notrace']) || defined("DISABLE_TRACE"))
	return;

//if (in_uri("_form") || in_uri("_include") || in_uri("_ajax"))
//{
//	include 'errors-log.php';			
//	exit(0);
//}

$includes=get_included_files();

$stats=array(
	"uri" => $_SERVER["PATH_INFO"],
	"errors" => count($page_errors),
	"size" => round(strlen($result)/1024,2),
	"time" => round($page_time,4),
	"includes" => count($includes),
	"db_hits" => $db_hits
);

$prev_stats=(isset($_SESSION["PREV_STATS"])) ? $_SESSION["PREV_STATS"] : null;

if (isset($loaded_views))
	$grouped_includes['Primary View'][]=array_shift($loaded_views);

$total_size=0;
$include_time=microtime(true);
if (isset($includes))
{
	if (isset($loaded_views))
		$includes=array_merge($includes,$loaded_views);
		
	foreach($includes as $include)
		if ((strpos($include,'external')==false) && (strpos($include,'debug')==false) && (file_exists($include))) 
		{
			$file=fopen($include,"rb");
			$filesize=filesize($include);
			$total_size+=$filesize;
			fread($file,$filesize);
			fclose($file);
		}
}
$include_time=microtime(true)-$include_time;

$grouped_includes['Views']=array();
$grouped_includes['Controls']=array();
$grouped_includes['Controller']=array();
$grouped_includes['Model']=array();
$grouped_includes['Config']=array();
$grouped_includes['System']=array();
$grouped_includes['Misc.']=array();

if (isset($includes))
	foreach($includes as $include)
	{
		if (strpos($include,PATH_VIEW)===0)
			$grouped_includes['Views'][]=$include;
		else if (strpos($include,PATH_CONTROL)===0)
			$grouped_includes['Controls'][]=$include;
		else if (strpos($include,PATH_CONTROLLER)===0)
			$grouped_includes['Controller'][]=$include;
		else if (strpos($include,PATH_MODEL)===0)
			$grouped_includes['Model'][]=$include;
		else if (strpos($include,PATH_CONFIG)===0)
			$grouped_includes['Config'][]=$include;
		else if (strpos($include,PATH_SYS)===0)
			$grouped_includes['System'][]=$include;
		else
			$grouped_includes['Misc.'][]=$include;
	}


?>

<!-- styles for error pages -->
<style>
	div#page-errors { padding:0px; background:white; color:#000; }
	div#page-errors, div#page-errors td, div#page-errors ul li { font-family: "Lucida Grande", Calibri, Helvetica, Arial; font-size:12px; }
	div#page-errors .stats { padding:5px; font-size:14px; color:#000; background:#DADADA; }
	div#page-errors a { color:#000; font-size:14px; }
	div#page-errors thead { background:#ddd; }
	div#page-errors th { text-align:left; font-weight:bold; font-size:14px; border:1px solid #a4a4a4; }
	div#page-errors td { border:1px solid #ddd; }
	div#page-errors h2 { font-size:14px; border-bottom:1px solid #ccc; padding-bottom:5px; margin-top:20px; color:#000; }
	div#page-errors h2 { font-size:12px; padding-bottom:5px; margin-top:20px; color:#000; }
	#trace-includes table { width:400px; }
	
	div#errors-tabs { background: #DADADA; }
	div#page-errors h1 { width:100%; background:#DADADA; padding:5px; }
	
	div#page-errors ul { list-style-type: none; padding:0px; margin:0px;}
	div#page-errors ul li { float:left; padding:5px; font-size:14px; background:#BABABA; margin-right:2px; border-top:2px solid #a4a4a4; }
	div#page-errors ul li a { text-decoration:none; }
	div#page-errors ul li:hover, div#page-errors ul li a:hover { color:#FFFFFF; background-color:#228fdd; }
	div#page-errors ul li.selected { background:#FFFFFF; border-top:2px solid #228fdd; color:#000; }
	div#page-errors ul li.selected a:hover { background:#FFFFFF; color:#000; }
</style>
<script type="text/javascript">
	var selectedTraceTab=null;
	var lastDiv=null;
	
	function toggle_trace(anchor,who)
	{
		parentLi=$(anchor).up();
		if (parentLi!=selectedTraceTab)
		{
			if (selectedTraceTab!=null)
				selectedTraceTab.removeClassName('selected');
			
			parentLi.addClassName('selected');
			selectedTraceTab=parentLi;
			
			if (lastDiv!=null)
				lastDiv.hide();
			
			lastDiv=$(who);
			
			lastDiv.show();
		}
		
		anchor.blur();
	}
</script>
<div id="page-errors">

	<div id="divDebugInfo" style="position:absolute; z-index:100000; top:0px; left:0px; visibility:hidden; ">
		<form method="post" action="/work/qa/switch">
			<input type="text" id="person" name="person"  /><input type="submit" value="Switch" />
		</form>
		<div id="people_choices" style="background:white; font-size:12px;"></div>
		<script type="text/javascript">
			var autocomplete=new Ajax.Autocompleter("person", "people_choices", "/work/qa/get_people", {});
		</script> 
	</div>
	<div id="divDebugShow" style="position:absolute; z-index:100000; top:0px; left:0px;">&dagger;</div>
<script type="text/javascript">
Event.observe($('divDebugShow'), 'mouseover', function() {
	$('divDebugShow').hide();
	$('divDebugInfo').setStyle({visibility:'visible'});;
});
</script>

<!-- 	
<iframe src="/work/misc/switch" style="position:absolute; top:0px; left:0px; overflow:hidden; width:600px; height:48px; border:0px none"></iframe>
 -->

	<div class="stats">For URI: <strong><?=$stats["uri"]?></strong>.  <strong><?=$stats["includes"]?></strong> total includes at <?=round($total_size/1024,4)?>k in <?=round($include_time,4)?> seconds.  <strong><?=$stats["errors"]?></strong> total warnings/errors.  <strong><?=$stats["size"]?>k</strong> built in <strong><?=$stats["time"]?></strong> seconds. <strong><?=$stats["db_hits"]?></strong> database hits.</div>
	<? if ($prev_stats!=null): ?>
	<div class="stats">For URI: <strong><?=$prev_stats["uri"]?></strong>.  <strong><?=$stats["includes"]?></strong> total includes.  <strong><?=$prev_stats["errors"]?></strong> total warnings/errors.  <strong><?=$prev_stats["size"]?>k</strong> built in <strong><?=$prev_stats["time"]?></strong> seconds. <strong><?=$prev_stats["db_hits"]?></strong> database hits.</div>
	<? endif; ?>

	<div id="errors-tabs">
		<ul>
			<li class="selected"><a href="javascript:void(0);" onclick="javascript:toggle_trace(this,'trace-errors')">Errors/Warnings</a></li>
			<li><a href="javascript:void(0);" onclick="javascript:toggle_trace(this,'trace-debug')">Debug Log</a></li>
			<li><a href="javascript:void(0);" onclick="javascript:toggle_trace(this,'trace-includes')">Includes</a></li>
			<li><a href="javascript:void(0);" onclick="javascript:toggle_trace(this,'trace-db')">Database</a></li>
			<li><a href="javascript:void(0);" onclick="javascript:toggle_trace(this,'trace-config')">Configuration</a></li>
			<li><a href="javascript:void(0);" onclick="javascript:toggle_trace(this,'trace-session')">Session</a></li>
			<li><a href="javascript:void(0);" onclick="javascript:toggle_trace(this,'trace-cookies')">Cookies</a></li>
			<li><a href="javascript:void(0);" onclick="javascript:toggle_trace(this,'trace-request')">Request</a></li>
			<li><a href="javascript:void(0);" onclick="javascript:toggle_trace(this,'trace-globals')">Globals</a></li>
			<li><a href="javascript:void(0);" onclick="javascript:toggle_trace(this,'trace-server')">Server</a></li>
		</ul>
		<br clear="both" />
	</div>
	<div style="padding:5px;">
	<table id="trace-errors" style="display:none" cellspacing="0" cellpadding="5">
		<thead>
			<th>Type</th>
			<th>File</th>
			<th>Line</th>
			<th>Message</th>
		</thead>
		<tbody>
	<?
		foreach ($page_errors as $error):
	?>
			<tr>
				<td><strong><?= ($error["errno"]==E_USER_WARNING) ? "WARNING" : "NOTICE" ?></strong></td>
				<td><?=str_replace(PATH_ROOT,"",$error["errfile"])?></td>
				<td><?=$error["errline"]?></td>
				<td><?=$error["errstr"]?></td>
			</tr>
	<?
		endforeach;
	?>
		</tbody>
	</table>

	<table id ="trace-debug" style="display:none" cellspacing="0" cellpadding="5">
		<thead>
			<th>Time</th>
			<th>Delta</th>
			<th>File</th>
			<th>Line</th>
			<th>Category</th>
			<th>Message</th>
		</thead>
		<tbody>
	<?
		$last_time=$debug_log[0]["time"]-REQUEST_START;
		foreach ($debug_log as $entry):
	?>
			<tr>
				<td align="right"><?=round(($entry["time"]-REQUEST_START)*1000,2)?> ms</td>
				<td align="right"><?=round((($entry["time"]-REQUEST_START)-$last_time)*1000,2)?> ms</td>
				<td><?=str_replace(PATH_ROOT,"",$entry["backtrace"][0]["file"])?></td>
				<td><?=$entry["backtrace"][0]["line"]?></td>
				<td><?=$entry["category"]?></td>
				<td><?=$entry["message"]?></td>
			</tr>
	<?
			$last_time=$entry["time"]-REQUEST_START;
		endforeach;
	?>
		</tbody>
	</table>
 
	<div id="trace-includes" style="display:none">
		<table style="" cellspacing="0" cellpadding="5">
			<thead>
				<th>#</th>
				<th>File</th>
				<th>Size</th>
			</thead>
			<tbody>
		<? foreach($grouped_includes as $key=>$group)
			if (count($group)>0):
				$total_size=0;
			
		?>
			<tr>
				<td colspan="3"><?=$key ?></td>
			</tr>
		<?
			$count=0;
			$size=0;
			foreach ($group as $include):
				if ((strpos($include,'external')==false) && (strpos($include,'debug')==false)):
					$size=filesize($include); 
					$total_size+=$size;
		?>
				<tr>
					<td><?=++$count;?></td>
					<td><a href="txmt://open?url=file://<?=str_replace(PATH_ROOT,DEV_MACHINE_PATH,$include)?>"><?=str_replace(PATH_ROOT,"",$include)?></a></td>
					<td><?=round($size/1024,4)?>k</td>
				</tr>
		<?
				endif;
			endforeach;
		?>
				<tr>
					<td colspan="2" align="right"><strong>Total Size:</strong></td>
					<td><?=round($total_size/1024,4)?>k</td>
		<? endif; ?>
			</tbody>
		</table>
	</div>
	
	<table id="trace-db" style="display:none" cellspacing="0" cellpadding="5">
		<thead>
			<th>Hit #</th>
			<th>Back Trace</th>
			<th>Query</th>
			<th>Time</th>
		</thead>
		<tbody>
	<?
		$i=0;
		$total_time=0;
		foreach ($db_queries as $query):
	?>
			<tr>
				<td><?=++$i?></td>
				<td>
	<?
		$b=count($query["backtrace"]);
		$total_time+=$query["time"]; 
		foreach($query["backtrace"] as $backtrace):
	?>
					<strong><?=$b--?></strong>:&nbsp;&nbsp;<?=str_replace(PATH_ROOT, "", $backtrace["file"])?> - <?=(isset($backtrace["class"]) ? $backtrace["class"]."->" : "")?><?=$backtrace["function"]?> - line # <?=$backtrace["line"]?><br />
	<?
		endforeach;
	?>
				</td>
				<td><?=$query["query"]?></td>
				<td><?=round($query["time"],4)?> sec.</td>
			</tr>
	<?
		endforeach;
	?>
			<tr>
				<td colspan="3" align="right"><strong>Total Time:</strong></td>
				<td><?=round($total_time,4)?></td>
			</tr>
		</tbody>
	</table>

	<table id="trace-config" style="display:none" cellspacing="0" cellpadding="5">
		<thead>
			<th>Group</th>
			<th>Key</th>
			<th>Value</th>
		</thead>
		<tbody>
	<?
		if (class_exists("GlobalConfig")):
			$config=GlobalConfig::GetConfig();			
			foreach ($config as $groupkey => $group):
				foreach($group as $key => $option):
	?>
			<tr>
				<td><?=$groupkey?></td>
				<td><?=$key?></td>
				<td><?=$option?></td>
			</tr>
	<?
				endforeach;
			endforeach;
		endif;
	?>
		</tbody>
	</table>
	<div id="trace-session" style="display:none">
	<h2>Session</h2>
	<table cellspacing="0" cellpadding="5">
		<thead>
			<th>Key</th>
			<th>Value</th>
		</thead>
		<tbody>
	<?
		uses('system.app.session');
		$session=Session::Get();
		
		foreach ($session->data as $key => $value):
	?>
			<tr>
				<td><?=$key?></td>
				<td><? dump($value); ?></td>
			</tr>
	<?
		endforeach;
	?>
		</tbody>
	</table>
	<h2>PHP Session</h2>
	<table cellspacing="0" cellpadding="5">
		<thead>
			<th>Key</th>
			<th>Value</th>
		</thead>
		<tbody>
	<?
		foreach ($_SESSION as $key => $value):
	?>
			<tr>
				<td><?=$key?></td>
				<td><? if (is_array($value)) print_r($value); else echo $value; ?></td>
			</tr>
	<?
		endforeach;
	?>
		</tbody>
	</table>

	<table id="trace-cookies" style="display:none" cellspacing="0" cellpadding="5">
		<thead>
			<th>Key</th>
			<th>Value</th>
		</thead>
		<tbody>
	<?
		foreach ($_COOKIE as $key => $value):
	?>
			<tr>
				<td><?=$key?></td>
				<td><?=$value?></td>
			</tr>
	<?
		endforeach;
	?>
		</tbody>
	</table>
	</div>
	
	<div id="trace-request" style="display:none">
	<h2>Request</h2>
	<table id="trace-request" style="" cellspacing="0" cellpadding="5">
		<thead>
			<th>Key</th>
			<th>Value</th>
		</thead>
		<tbody>
	<?
		foreach ($_REQUEST as $key => $value):
	?>
			<tr>
				<td><?=$key?></td>
				<td><?=$value?></td>
			</tr>
	<?
		endforeach;
	?>
		</tbody>
	</table>
	<h2>Post</h2>
	<table id="trace-post" style="" cellspacing="0" cellpadding="5">
		<thead>
			<th>Key</th>
			<th>Value</th>
		</thead>
		<tbody>
	<?
		foreach ($_POST as $key => $value):
	?>
			<tr>
				<td><?=$key?></td>
				<td><?=$value?></td>
			</tr>
	<?
		endforeach;
	?>
		</tbody>
	</table>
	</div>
	
	<table id="trace-globals" style="display:none" cellspacing="0" cellpadding="5">
		<thead>
			<th>Key</th>
			<th>Value</th>
		</thead>
		<tbody>
	<?
		foreach ($GLOBALS as $key => $value):
			if ($key!="result"):
	?>
			<tr>
				<td><?=$key?></td>
				<td><?=$value?></td>
			</tr>
	<?
			endif;
		endforeach;
	?>
		</tbody>
	</table>

	<table id="trace-server" style="display:none" cellspacing="0" cellpadding="5">
		<thead>
			<th>Variable</th>
			<th>Value</th>
		</thead>
		<tbody>
	<?
		foreach ($_SERVER as $key => $value):
	?>
			<tr>
				<td><?=$key?></td>
				<td><?=$value?></td>
			</tr>
	<?
		endforeach;
	?>
		</tbody>
	</table>
	</div>
</div>

<script>
	toggle_trace($('errors-tabs').down().down().down(),'trace-errors');
</script>
<?
	$_SESSION["PREV_STATS"]=$stats;
?>