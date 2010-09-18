<?php
uses('system.debug.module');

class SessionDebugModule extends DebugModule
{
	public $title='Session';
	public $description="Displays the current session.";
	
	public function __construct()
	{
	}
	
	public function render()
	{
?>
		<table cellspacing="0" cellpadding="5">
			<thead>
				<th>Key</th>
				<th>Value</th>
			</thead>
			<tbody>
<?
			foreach (Session::Get()->data as $key => $value):
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
<?		
	}
}