<?php
uses('sys.external.PEAR.HTTP.Request');

class APIClient
{
	var $conf;
	public function __construct()
	{
		$meta=AttributeReader::ClassAttributes($this);
		$apiconf=Config::Get('api');
		$this->conf=$apiconf->{$meta->api};
		if (!$this->conf)
			throw new BadRequestException('Missing api info');
	}
	
	public function call($method,$args)
	{
		$req=new HTTP_Request($this->conf->endpoint.$method);
		$req->setMethod('POST');
		$args['app_id']=$this->conf->app_id;
		foreach($args as $key=>$value)
			$req->addPostData($key, $value);
			
		$sig=sign($args,$this->conf->key);
		$req->addPostData('signature',$sig['signature']);
		$req->addPostData('time',$sig['time']);

		$req->sendRequest();
		
		if ($req->getResponseCode()!=200)
			throw new BadRequestException($req->getResponseBody());
		
		return json_decode($req->getResponseBody());
	}
}