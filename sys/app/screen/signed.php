<?php

/**
 * Makes sure the request is signed.  The 'apps.conf' configuration file controls app_id and the secret key to
 * compute the hash.
 *
 * @throws BadRequestException
 *
 */
class SignedScreen extends Screen
{
	public function before($controller,$metadata,&$data)
	{
        if (!$controller->request->input->exists('app_id'))
            throw new BadRequestException("Missing app id.");

        $conf=Config::Get('apps');
		$app=$conf->{$controller->request->input->app_id};

        if (!$app->signed)
             return;

		if (!$controller->request->input->exists('app_id','signature','time'))
			throw new BadRequestException("Missing signature.");

		$signature=str_replace(' ', '+',$controller->request->input->signature);
		$vals=array();
		foreach($controller->request->input as $key => $val)
            if (!in_array($key,array('signature','time')))
    			$vals[$key]=$val;

  		$sig=sign($vals,$app->key,$controller->request->input->time);
  		if ($sig['signature']!=$signature)
			throw new BadRequestException("Invalid signature.");
	}
}