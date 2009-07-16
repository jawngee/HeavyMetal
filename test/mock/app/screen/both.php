<?php
class BothScreen extends Screen
{
	public function before($controller,$metadata,&$data)
	{
		$data['screen']='before';
	}
	
	public function after($controller,$metadata,&$data)
	{
		if ($data['screen']=='before')
			$data['screen']='both';
	}
}