<?php
uses('system.debug.module');
uses('system.debug.modules.includes');

class ModelsDebugModule extends IncludesDebugModule
{
	public $description="Displays a list of all used models.";

	public function __construct()
	{
		parent::__construct();
		
		$this->title="Models (".count(self::$grouped_includes['Model']).")";	
	}

	public function render()
	{
?>
		<table style="" cellspacing="0" cellpadding="5">
			<thead>
				<th>#</th>
				<th>File</th>
				<th>Size</th>
			</thead>
			<tbody>
		<?
			$total_size=0;
			$count=0;
			$size=0;
			foreach (self::$grouped_includes['Model'] as $include):
				if ((strpos($include,'external')==false) && (strpos($include,'debug')==false)):
					$size=filesize($include); 
					$total_size+=$size;
		?>
				<tr>
					<td><?=++$count;?></td>
					<td><a href="txmt://open?url=file://<?=str_replace(PATH_ROOT,DEV_MACHINE_PATH,$include)?>"><?=str_replace(PATH_ROOT,"",$include)?></a></td>
					<td><?=round($size/1024,4)?>k</td>
				</tr>
		<?
				endif;
			endforeach;
		?>
				<tr>
					<td colspan="2" align="right"><strong>Total Size:</strong></td>
					<td><?=round($total_size/1024,4)?>k</td>
				</tr>
			</tbody>
		</table>
<?	
	}
}