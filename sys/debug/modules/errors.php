<?php
uses('system.debug.module');

class ErrorsDebugModule extends DebugModule
{
	public $description="Displays a list of errors.";
	
	public function __construct()
	{
		$this->title="Errors (".count(Collector::$errors).")";	
	}

	public function render()
	{
?>
		<table cellspacing="0" cellpadding="5">
			<thead>
				<th>Type</th>
				<th>File</th>
				<th>Line</th>
				<th>Message</th>
			</thead>
			<tbody>
<?
		foreach (Collector::$errors as $error):
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
<?	
	}
}