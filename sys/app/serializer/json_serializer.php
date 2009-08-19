<?php
uses('system.app.serializer.array_serializer');

/**
 * Serializes/Deserializes in JSON format.
 */
class JSONSerializer extends ArraySerializer
{
	/**
	 * @see sys/app/driver/serializer/ArraySerializer#get_serializer()
	 */
	protected function get_serializer($conf)
	{
		return new JSONSerializer(null,null,0,$conf);
	}

	/**
	 * @see sys/app/driver/serializer/ArraySerializer#do_serialize()
	 */
	public function do_serialize()
	{
		$result=parent::do_serialize();
		return json_encode($result);
	}
		
	/**
	 * @see sys/app/Serializer#do_deserialize()
	 */
	public function do_deserialize()
	{
		$dom=json_decode($this->content,true);
		return $this->deserialize_array($dom);
	}
}