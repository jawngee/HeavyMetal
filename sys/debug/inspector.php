<? if ((REQUEST_TYPE!='html') || (defined('DISABLE_TRACE'))) return; ?>

<?
	$finaltime=microtime(true)-Collector::$start_time;
	$result=ob_get_clean();
	echo $result;
	
	$finalsize=strlen($result)/1024;

	$modules=array();
	
	foreach(Config::$environment_config->collector->modules->items as $key => $module)
		$modules[$key]=instance($module,'DebugModule');
		
	if ($finaltime<0.9)
		$status='<span style="color:green">&#x2714;</span>';
	else if ($finaltime<1.2)
		$status='<span style="color:orange">&#x2714;</span>';
	else
		$status='<span style="color:red">&#x2716;</span>';
			
?>

<style>
	#heavymetal-inspector { position:absolute; left:0px; top:0px; right:0px; bottom: 0px; font-size:12px; padding:0px; margin:0px; }
	#heavymetal-inspector-bug {  position:absolute; right:5px; top:5px; }
	#heavymetal-inspector-bug a { -webkit-border-radius:10px; border:1px solid black; text-align:center; width:17px; height:17px; font-size:14px; text-align:center; text-decoration:none; display:block; color:#000; }

	.heavymetal-inspector-close-button 
	{ 
		background: white;
		position:absolute; top:5px; right:5px;
		height: 12px; width:12px;
		-webkit-border-radius:8px; 
		font-size:10px; color:black; 
		border:1px solid #cacaca; 
		text-align:center; 
		text-decoration:none; 
		display:block; 
	}

	#heavymetal-inspector-panel { position:absolute; top:0px; right:0px; width:200px; color:white; background:rgba(32,32,32,0.75);  }
	#heavymetal-inspector-panel h1 { display:block; color:white; background:#111; padding:5px; font-size:12px;  }
	#heavymetal-inspector-panel h2 { font-size:12px; padding:0px; margin:0px; }
	#heavymetal-inspector-panel p { font-size:12px; padding:0px; margin:0px; color:#CCC; }
	#heavymetal-inspector-panel ul { padding:0px; margin:0px;  }
	#heavymetal-inspector-panel ul li { margin-top:10px; padding:5px; padding-left:10px; cursor:pointer;  }
	#heavymetal-inspector-panel ul li:hover { color:white; background:#333;  }
	#heavymetal-inspector-panel ul li.selected { background: #000; color:#9A9A9A;  }

	
	#heavymetal-inspector-pane { -webkit-box-shadow: rgba(0, 0, 0, 0.496094) 5px -5px 5px; position:absolute; left:0px; top:0px; right:200px; bottom:0px; background:white; font-size:12px; overflow:auto;  }
	
	#heavymetal-inspector-pane h1 { background: #CCC; padding:5px; font-size:16px; margin-bottom:10px; }
	#heavymetal-inspector-pane h1 span { padding-left:10px; font-size:14px; }

	#heavymetal-inspector-pane code { display:block; border:1px dotted #CCC; background :#EAEAEA; padding:5px; margin-bottom:15px; }
	#heavymetal-inspector-pane code.sql { font-size:14px; line-height:18px; }
	
	#heavymetal-stats { color:#000; margin:5px; background:#FAFAFA; padding:5px 0px 5px 5px; }
	#heavymetal-stats h2 { padding:0px; font-size:9px; text-transform:uppercase; color:#666; margin-top:5px; }
	#heavymetal-stats p { color:#111; }
</style> 

<script>
	function toggleInspector()
	{
		Effect.toggle('heavymetal-inspector-bug','appear', { duration: 0.25 });
		Effect.toggle('heavymetal-inspector','appear', { duration: 0.25 });
	}

	var lastModule='';
	
	function showModule(module)
	{
		Element.show('heavymetal-inspector-pane');
		Element.addClassName('heavymetal-selector-'+module,'selected');
		
		if (lastModule!='')
		{
			Element.removeClassName('heavymetal-selector-'+lastModule,'selected');
			Element.hide('heavymetal-module-'+lastModule);
		}

		Element.show('heavymetal-module-'+module);

		lastModule=module;
	}

	function closeLastModule()
	{
		Element.hide('heavymetal-inspector-pane');
		if (lastModule!='')
		{
			Element.removeClassName('heavymetal-selector-'+lastModule,'selected');
			Element.hide('heavymetal-module-'+lastModule);
		}

		lastModule='';
		
	}
</script>

<div id="heavymetal-inspector-bug">
	<a href="#" onclick="toggleInspector();"><?=$status?></a>
</div>

<div id="heavymetal-inspector" style="display:none">
	<div id="heavymetal-inspector-panel">
		<a class="heavymetal-inspector-close-button" href="#" onclick="toggleInspector();">&#x2716;</a>
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
			<li id="heavymetal-selector-<?=$key?>" onclick="showModule('<?=$key?>');">
				<h2><?=$module->title?></h2>
				<p><?=$module->description?></p>
			</li>
	<?php endforeach; ?>		
		</ul>
	</div>
	
	<div id="heavymetal-inspector-pane" style="display:none">
	<?php foreach($modules as $key => $module): ?>
		<a class="heavymetal-inspector-close-button" href="#" onclick="closeLastModule();">&#x2716;</a>
		<div id="heavymetal-module-<?=$key?>" style="display:none">
			<h1><?=$module->title?><span><?=$module->description?></span></h1>
			<?= $module->render(); ?>
		</div>
	<?php endforeach; ?>
	</div>
</div>