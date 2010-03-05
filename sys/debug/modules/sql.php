<?php
uses('system.debug.module');

class SQLDebugModule extends DebugModule
{
	public $template='sql';
	public $description="Displays every database query.";
	
	public function __construct()
	{
		$this->total_time=0;
		foreach (Collector::$queries as $query)
			$this->total_time+=$query["duration"]; 
		
		$this->title="SQL Queries (".count(Collector::$queries).")";
		$this->description=count(Collector::$queries)." queries in ".round($this->total_time,4)." seconds.";	
	}
	
	public function render()
	{
?>
	<script src="/js/cookiejar.js"></script>
	<table cellspacing="0" cellpadding="5">
		<thead>
			<th>Time</th>
			<th>Query</th>
		</thead>
		<tbody>
	<?
		$i=0;
		$total_time=0;
		foreach (Collector::$queries as $query):
			?>
			<tr>
				<td width="75" align="right" valign="top"><?=round($query["duration"],4)?> sec.</td>
				<td>
					<code class="sql" id="heavymetal-query-code-<?=$i?>"><?=$query["query"]?></code>
					<div>
						<a href="javascript:void(0);" onclick="$('heavymetal-sql-backtrace-<?=$i?>').toggle();">Show/Hide Backtrace</a>
					</div>
					<div id="heavymetal-sql-backtrace-<?=$i?>" style="display:none; font-size:14px; padding:5px; line-height:16px;">
	<?
		$b=count($query["backtrace"]);
		foreach($query["backtrace"] as $backtrace):
	?>
						<strong><?=$b--?></strong>:&nbsp;&nbsp;<?=str_replace(PATH_ROOT, "", $backtrace["file"])?> - <?=(isset($backtrace["class"]) ? $backtrace["class"]."->" : "")?><?=$backtrace["function"]?> - line # <?=$backtrace["line"]?><br />
	<?
		endforeach;
	?>
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