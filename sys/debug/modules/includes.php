<?php
uses('system.debug.module');

class IncludesDebugModule extends DebugModule
{
	public $description="Displays a list of all includes.";
	
	public static $includes=null;
	public static $grouped_includes=null;
	
	public function __construct()
	{
		if (!isset(self::$includes))
		{
			self::$includes=get_included_files();
		
			self::$grouped_includes['Views']=array();
			self::$grouped_includes['Controls']=array();
			self::$grouped_includes['Controller']=array();
			self::$grouped_includes['Model']=array();
			self::$grouped_includes['Config']=array();
			self::$grouped_includes['System']=array();
			self::$grouped_includes['Misc.']=array();

			if (isset(self::$includes))
				foreach(self::$includes as $include)
				{
					if (strpos($include,PATH_APP.'view/')===0)
						self::$grouped_includes['Views'][]=$include;
					else if (strpos($include,PATH_APP.'control/')===0)
						self::$grouped_includes['Controls'][]=$include;
					else if (strpos($include,PATH_APP.'controller/')===0)
						self::$grouped_includes['Controller'][]=$include;
					else if (strpos($include,PATH_APP.'model/')===0)
						self::$grouped_includes['Model'][]=$include;
					else if (strpos($include,PATH_APP.'conf/')===0)
						self::$grouped_includes['Config'][]=$include;
					else if (strpos($include,PATH_SYS)===0)
						self::$grouped_includes['System'][]=$include;
					else
						self::$grouped_includes['Misc.'][]=$include;
				}
		}

		$this->title="Includes (".count(self::$includes).")";	
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
		<? foreach(self::$grouped_includes as $key=>$group)
			if (count($group)>0):
				$total_size=0;
			
		?>
			<tr>
				<td colspan="3"><?=$key ?></td>
			</tr>
		<?
			$count=0;
			$size=0;
			foreach ($group as $include):
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
		<? endif; ?>
			</tbody>
		</table>
<?	
	}
}