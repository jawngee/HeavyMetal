<?php
class ClassScreen extends Screen
{
	public function before(&$controller,&$metadata,&$data)
	{
		$data['class_before']='before';
	}

	public function after(&$controller,&$metadata,&$data)
	{
		$data['class_after']='after';
	}
}