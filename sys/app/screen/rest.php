<?php
uses('sys.app.serializer');

class RestScreen extends Screen
{
	public function before($controller,$metadata,&$data,&$args)
	{
		$body=@file_get_contents('php://input');
		if (!$body)
			throw new BadRequestException();
			
		$site=null;
		
		$ctype=array_shift(explode(';',$_SERVER['CONTENT_TYPE']));
		switch($ctype)
		{
			case 'application/xml':
			case 'text/xml':
				$site= Serializer::DeserializeObject($body,Serializer::FORMAT_XML,$metadata->map);
				break;
			case 'application/json':
			case 'text/json':
				$site= Serializer::DeserializeObject($body,Serializer::FORMAT_JSON,$metadata->map);
				break;
			case 'text/yaml':
				$site= Serializer::DeserializeObject($body,Serializer::FORMAT_YAML,$metadata->map);
				break;
		}
		
		$args[]=$site;
	}
	
	public function after($controller,$metadata,&$data)
	{
		if (($metadata->map) && ($metadata->item) && (isset($data[$metadata->item])))
		{
			switch($_SERVER['HTTP_ACCEPT'])
			{
				case 'application/xml':
				case 'text/xml':
					content_type('application/xml');
					echo Serializer::SerializeObject($data[$metadata->item],Serializer::FORMAT_XML,null,$metadata->map);
					die;
					break;
				case 'application/json':
				case 'text/json':
					content_type('text/json');
					$response=Serializer::SerializeObject($data[$metadata->item],Serializer::FORMAT_JSON,null,$metadata->map);
					header("X-JSON:$response");
					echo $response;
					die;
					break;
				case 'text/yaml':
					content_type('text/yaml');
					echo Serializer::SerializeObject($data[$metadata->item],Serializer::FORMAT_YAML,null,$metadata->map);
					die;
					break;
			}
		}
	}
}