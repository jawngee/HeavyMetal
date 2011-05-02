<?
/**
 * SQS Request wrapper
 * Very simple wrapper around the SQS API.  Other PHP implementations were grossly
 * lacking or java engineered.
 */

uses('system.cloud.provider.amazon.aws_request');

/**
 * Represents a sqs api request.
 */
class SNSRequest extends AWSRequest
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
		
		$this->endpoint='http://sns.us-east-1.amazonaws.com/';

		$this->parameters['Version'] = '2010-03-31';
	}

}
