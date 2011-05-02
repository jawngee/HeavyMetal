<?
/**
 * EC2 Request wrapper
 * Very simple wrapper around the EC2 API.  Other PHP implementations were grossly
 * lacking or java engineered.
 */

uses('system.cloud.provider.amazon.aws_request');

/**
 * Represents a sqs api request.
 */
class EC2Request extends AWSRequest 
{
	/**
	 * Constructor
	 *
	 * @param string $id The Amazon account ID
	 * @param string $secret The Amazon account secret
	 * @param string $action The API action
	 * @param string $endpoint The URL at amazon to call
	 */
	public function __construct($action,$id=null,$secret=null,$endpoint=null)
	{
		parent::__construct($action,$id,$secret,$endpoint);
		
		if (!$this->endpoint)
			$this->endpoint='https://ec2.amazonaws.com/';

		$this->parameters['Version'] = '2008-08-08';      
	}
}
