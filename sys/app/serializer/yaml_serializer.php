<?php
uses('system.app.serializer.array_serializer');

/**
 * Serializes/Deserializes in YAML format.
 */
class YAMLSerializer extends ArraySerializer
{
	/**
	 * @see sys/app/driver/serializer/ArraySerializer#get_serializer()
	 */
	protected function get_serializer($conf)
	{
		return new YAMLSerializer(null,null,0,$conf);
	}

	/**
	 * @see sys/app/driver/serializer/ArraySerializer#do_serialize()
	 */
	public function do_serialize()
	{
		$result=parent::do_serialize();
		$syck=syck_dump($result);
		
		$syck=preg_replace('#"([^"]+)":#m','$1:',$syck);
		return $syck;
	}

	/**
	 * @see sys/app/Serializer#do_deserialize()
	 */
	public function do_deserialize()
	{
		$dom=syck_load($this->content);
		return $this->deserialize_array($dom);
	}
}