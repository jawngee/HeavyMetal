<?php
uses('system.data.model');

class JSONScreen extends Screen
{
	public function after($controller,$metadata,&$data)
	{
		$data=Model::Flatten($data);
		echo json_encode($data);
		die;
	}
}