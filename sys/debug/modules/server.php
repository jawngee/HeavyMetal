<?php
uses('system.debug.module');

class ServerDebugModule extends DebugModule
{
	public $title='Server';
	public $description="Displays server variables.";
	
	public function __construct()
	{
	}
	
	public function render()
	{
	
?>
	<table cellspacing="0" cellpadding="5">
		<thead>
			<th>Variable</th>
			<th>Value</th>
		</thead>
		<tbody>
<?
		foreach($_SERVER as $key => $value):
?>
			<tr>
				<td><?=$key?></td>
				<td style="text-align:left;"><?=$_SERVER[$key]?></td>
			</tr>
<?
		endforeach;
?>
		</tbody>
	</table>

<?	

	}
}