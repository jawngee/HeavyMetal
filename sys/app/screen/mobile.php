<?php
uses('system.data.model');

class MobileScreen extends Screen
{
	public function after($controller,$metadata,&$data)
	{
		if ($metadata->or_ajax=='true')
			$ismobile=Request::is_ajax();
		else 
			$ismobile=((Dispatcher::RequestType()=='mobile') && (Request::is_ajax()));
			
		if ($ismobile)
		{
			if ($metadata->key)
				$data=$data[$metadata->key];
				
			$data=Model::Flatten($data);
			echo json_encode($data);
			die;
		}
	}
}