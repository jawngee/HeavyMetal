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
		<table cellspacing="0" cellpadding="5">
		<tbody>
<?
			$count=0;
			foreach (Collector::$views as $include):
				$count++;
?>
			<tr>
				<td>
					<a href="txmt://open?url=file://<?=str_replace(PATH_ROOT,DEV_MACHINE_PATH,$include['view'].EXT)?>"><?=str_replace(PATH_ROOT,"",$include['view'])?></a>
					<a href="javascript:void(0)" onclick="toggleCode('#heavymetal-view-code-<?=$count?>');">[View Source]</a>
					<div id='heavymetal-view-code-<?=$count?>' class="codesample" style="display:none; margin-top:10px;">
					<?=highlight_string($include['content'])?>
					</div>
				</td>
			</tr>
<?
			endforeach;
?>
		</tbody>
		</table>
<?	
	}
}