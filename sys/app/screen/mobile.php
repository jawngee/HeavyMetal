<?php
class MobileScreen extends Screen
{
	private function flatten_models(&$data)
	{
		foreach($data as $key => $value)
			if ($value instanceof Model)
			{
				$data[$key]=$value->to_array();
			}
			else if (is_array($value))
				$this->flatten_models($value);	
	}
	
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
				
			$this->flatten_models($data);
			echo json_encode($data);
			die;
		}
	}
}