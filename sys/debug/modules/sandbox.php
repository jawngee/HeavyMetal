<?php
uses('system.debug.module');

class SandboxDebugModule extends DebugModule
{
	public $description="Allows you to run framework code.";
	
	public function __construct()
	{
		$this->title="Sandbox";	
	}
	
	public function render()
	{
?>
		<div style="padding:10px;">
			<form id='debug_sandbox_editor' name='debug_sandbox_editor' method="post" action="/work/tools/sandbox">
				<textarea name="code" style="width:99%; height:360px; font-family: Monaco, Courier New; font-size:12px; background: black; border:0px none; color:white;"></textarea>
				<input type="submit" value="Execute" onclick="new Ajax.Updater('sandbox-code-container','/work/tools/sandbox', { method:'post', parameters:$('debug_sandbox_editor').serialize(true)}); return false;" />
			</form>
		</div>
		<div id="sandbox-code-container" style="padding:10px;"></div>
<?
	}
}