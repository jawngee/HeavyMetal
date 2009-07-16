<?php
class AfterScreen extends Screen
{
	public function after(&$controller,&$metadata,&$data)
	{
		$data['screen']='after';
	}
}