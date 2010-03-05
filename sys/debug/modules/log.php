<?php
uses('system.debug.module');

class LogDebugModule extends DebugModule
{
	public $template='log';
	public $description="Displays the trace log.";
	
	public function __construct()
	{
		$this->title="Logs (".count(Collector::$log).")";	
	}
	
	public function render()
	{
?>
	<table cellspacing="0" cellpadding="5">
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
		$last_time=Collector::$log[0]["time"];
		foreach (Collector::$log as $entry):
?>
			<tr>
				<td align="right"><?=round(($entry["time"])*1000,2)?> ms</td>
				<td align="right"><?=round((($entry["time"])-$last_time)*1000,2)?> ms</td>
				<td><?=str_replace(PATH_ROOT,"",$entry["backtrace"][0]["file"])?></td>
				<td><?=$entry["backtrace"][0]["line"]?></td>
				<td><?=$entry["category"]?></td>
				<td><?=$entry["message"]?></td>
			</tr>
<?
			$last_time=$entry["time"];
		endforeach;
?>
		</tbody>
	</table>
<?		
	}
}