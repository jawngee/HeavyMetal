<?php
uses('system.data.model');

/**
 * This screen for before requests will convert the incoming json into data and attach it to the controller.
 *
 * The screen for after requests will convert the data returned from the controller into json and send it to the client.
 *
 * @throws BadRequestException
 *
 */
class JSONScreen extends Screen
{
    public function before($controller,$metadata,&$data,&$args)
    {;
        $json=trim(file_get_contents('php://input'));
        $jdata=json_decode($json,true);
        if ((!$jdata) && ($metadata->required))
            throw new BadRequestException('Missing json payload');

        $controller->json=$jdata;
    }

	public function after($controller,$metadata,&$data)
	{
		$data=Model::Flatten($data);
        echo json_encode($data);
		die;
	}
}