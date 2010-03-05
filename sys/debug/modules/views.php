<?php
uses('system.debug.module');

class ViewsDebugModule extends IncludesDebugModule
{
	public $description="Displays a list of all views.";

	public function __construct()
	{
		parent::__construct();
		
		$this->title="Views (".count(Collector::$views).")";	
	}

	public function render()
	{
?>
		<style>div.codesample code { font-size:11px; }</style>
		<div style="padding:5px;">
<?
			$count=0;
			foreach (Collector::$views as $include):
				$count++;
?>
					<div style="margin-bottom:10px; font-size:14px;">
						<a href="txmt://open?url=file://<?=str_replace(PATH_ROOT,DEV_MACHINE_PATH,$include['view'])?>"><?=str_replace(PATH_ROOT,"",$include['view'])?></a>
						<a href="javascript:void(0)" onclick="$('heavymetal-view-code-<?=$count?>').toggle()">[View Source]</a>
						<div id='heavymetal-view-code-<?=$count?>' class="codesample" style="display:none; margin-top:10px;">
						<?=highlight_string($include['content'])?>
						</div>
					</div>
<?
			endforeach;
?>
		</div>
<?	
	}
}