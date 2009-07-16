<?php
class BeforeScreen extends Screen
{
	public function before($controller,$metadata,&$data)
	{
		$data['screen']='before';
		$data['saywhat']=$metadata->string;
	}
}