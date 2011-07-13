<?php

/**
 * Insures that the inputs for a controller method exist, otherwise throws a bad request exception.
 *
 * @throws BadRequestException
 *
 */
class RequireScreen extends Screen
{
	public function before($controller,$metadata,&$data)
	{
        if (!$controller->request->input->exists($metadata))
            throw new BadRequestException("Missing required inputs");
 	}
}