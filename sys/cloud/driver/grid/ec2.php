<?

uses('system.cloud.amazon.ec2_request');

/**
 * Message queue driver for SQS
 */
class EC2Driver extends GridManager 
{
	private $id=null;
	private $secret=null;

	/**
	 * Constructor
	 *
	 * @param string $id Amazon ID
	 * @param string $secret Amazon Secret
	 */
	public function __construct($id,$secret)
	{
		$this->id=$id;
		$this->secret=$secret;
	}
	
	/**
	 * Describe running instances
	 *
	 * @return unknown
	 */
	public function describe_instances()
	{
		$req=new EC2Request('DescribeInstances',$this->id,$this->secret);
		$response=$req->send();

		$result=array();
		foreach($response->reservationSet->item as $item)
			$result[]=$item;
		
		return $result;
	}
}