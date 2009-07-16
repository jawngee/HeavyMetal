<?php
class IndexController extends Controller
{
	public function dispatch()
	{
		return array('message'=>'hello world');
	}
	
	public function route($what,$where)
	{
		return array(
			'what' => $what,
			'where' => $where
		);
	}
}