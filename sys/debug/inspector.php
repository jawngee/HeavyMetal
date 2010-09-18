<? if ((Dispatcher::RequestType()!='html') || (defined('DISABLE_TRACE'))) return; ?>
<?
	$finaltime=microtime(true)-Collector::$start_time;
	$result=ob_get_clean();
	echo $result;
	
	$finalsize=strlen($result)/1024;

	$modules=array();
	
	foreach(Config::$environment_config->collector->modules->items as $key => $module)
	{
		uses($module);
		$class=$key.'DebugModule';
		$modules[$key]=new $class();
	}
		
	if ($finaltime<0.9)
		$status='<span style="color:green">&#x2714;</span>';
	else if ($finaltime<1.2)
		$status='<span style="color:orange">&#x2714;</span>';
	else
		$status='<span style="color:red">&#x2716;</span>';
			
?>

<link rel="stylesheet" href="<?php echo Config::$environment_config->inspector->stylesheet; ?>" type="text/css" />
<script type="text/javascript" src="<?php echo Config::$environment_config->inspector->javascript; ?>"></script>

<div id="heavymetal-inspector-bug" style="display:none">
	<a href="#/debug/"><?=$status?></a>
</div>

<div id="heavymetal-inspector" style="display:none">
	<div id="heavymetal-inspector-panel">
		<a class="heavymetal-inspector-close-button" href="#/bug">&#x2716;</a>
		<h1>Heavy Metal</h1>
		<div id="heavymetal-stats">
			<h2>Total Time</h2>
			<p><?php echo round($finaltime,4); ?> seconds.</p>

			<h2>Total Memory</h2>
			<p><?php echo round(memory_get_usage(true) / (1024*1024),4)?> MB</p>

			<h2>Final Size</h2>
			<p><?php echo round($finalsize,4)?> KB</p>
		</div>
		<ul>
	<?php foreach($modules as $key => $module): ?>
			<li id="heavymetal-selector-<?=$key?>" onclick="document.location='#/debug/<?=$key?>';">
				<h2><?=$module->title?></h2>
				<p><?=$module->description?></p>
			</li>
	<?php endforeach; ?>		
		</ul>
	</div>
	
	<div id="heavymetal-inspector-pane" style="display:none">
	<?php foreach($modules as $key => $module): ?>
		<a class="heavymetal-inspector-close-button" href="#/debug/">&#x2716;</a>
		<div id="heavymetal-module-<?=$key?>" style="display:none">
			<h1><?=$module->title?><span><?=$module->description?></span></h1>
			<?= $module->render(); ?>
		</div>
	<?php endforeach; ?>
	</div>
</div>