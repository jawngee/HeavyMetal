<?php
uses('system.app.serializer');

/**
 * Abstract class for serializers whose libraries work with associative arrays.  This class will
 * serialize an object graph into an associative array.
 */
abstract class ArraySerializer extends Serializer
{
	/**
	 * Fetches an instance of a serializer
	 * 
	 * @param $value
	 * @return unknown_type
	 */
	protected function get_serializer($conf)
	{
		return new ArraySerializer(null,null,0,$conf);
	}
	
	/**
	 * Serializes the objects of an array into an associative array
	 * @param array $array The array to serialize
	 * @param Config $conf The configuration to use.
	 * @return array The serialized object array
	 */
	protected function serialize_array($array,$conf,$itemconf)
	{
		$arr=array();
		
		foreach($array as $value)
		{
			$serializer=$this->get_serializer($itemconf);
			$serializer->setup_serialization($value);
			
			if ($conf->key)
				$arr[$value->{$conf->key}]=$serializer->serialize_object($value,false,$itemconf);
			else
				$arr[]=$serializer->serialize_object($value,false,$itemconf);
		}

		return $arr;
	}
	
	/**
	 * Serializes an object into an associative array
	 * 
	 * @param mixed $object The object to serialize
	 * @param bool $root Is the root of the tree
	 * @return array The object graph serialized into an associative array
	 */
	protected function serialize_object($object,$root=false,$node_conf=null)
	{
		$node_conf=($node_conf) ? $node_conf : $this->node_conf;
		$ele=array();
		
		if ($node_conf->attributes)
    		foreach($node_conf->attributes->items as $a)
	    		if ($object->{$a->name})
		    		$ele[$a->name]=$object->{$a->name};

		if ($node_conf->content)
			foreach($node_conf->content->items as $child)
			{
				$v=$object->{$child->name};
				if (($v)||(is_array($v)))
				{
					switch($child->type)
					{
						case 'html':
							$ele[$child->name]=htmlentities($v);
							break;
						case 'string':
							$ele[$child->name]=$v;
							break;
						case 'hash':
							$cc=self::GetNodeConf($child->contains);
							$ele[$child->name]=$this->serialize_array($v,$child,$cc);
							break;
						case 'array':
							$cc=self::GetNodeConf($child->contains);
							$ele[$child->name]=$this->serialize_array($v,$child,$cc);
							break;
						default:
							$cc=self::GetNodeConf($child->type);
							$serializer=$this->get_serializer($cc);
							$serializer->setup_serialization($v);
							$res=$serializer->serialize_object($v,false);
							$ele[$child->name]=$res;
							break;
					}
				}
			}
			

		if ($root)
			return array($this->root => $ele);
		else
			return $ele;
	}
	
	/**
	 * @see sys/app/Serializer#do_serialize()
	 */
	public function do_serialize()
	{
		$result=$this->serialize_object($this->object,true);
		return $result;
	}
	
	/**
	 * Deserializes an element of an array into an object
	 * 
	 * @param string $node The name of the node being deserialized
	 * @param mixed $ele The array element being deserialized 
	 * @return mixed The deserialized object
	 */
	private function deserialize_element($node,$ele,$conf)
	{
		$m=create_class($conf->class);

		if ($conf->attributes)
		{
    		foreach($conf->attributes->items as $a)
    		{
    			$an=$a->name;
    			if (isset($ele[$an]))
    				$m->{$an}=$ele[$an];
    		}
		}
		
		foreach($conf->content->items as $child)
		{
				switch($child->type)
				{
					case 'html':
						$m->{$child->name}=html_entity_decode($ele[$child->name]);
						break;
					case 'string':
						$m->{$child->name}=$ele[$child->name];
						break;
					case 'array':
						$cc=self::GetNodeConf($child->contains);
						
						if (!$m->{$child->name})
							$m->{$child->name}=array();
							
						foreach($ele[$child->name] as $childele)
						{
							$parsed=$this->deserialize_element($child->contains,$childele,$cc);
							$m->{$child->name}[]=$parsed;
						}

						break;
					case 'hash':
						$cc=self::GetNodeConf($child->contains);
						if (!$m->{$child->name})
							$m->{$child->name}=array();
							
						foreach($ele[$child->name] as $key=>$childele)
						{
							$parsed=$this->deserialize_element($child->contains,$childele,$cc);
							$m->{$child->name}[$parsed->{$child->key}]=$parsed;
						}
						break;
					default:
						$cc=self::GetNodeConf($child->type);
						$m->{$child->name}=$this->deserialize_element($child->name,$ele[$child->name],$cc);
						break;
				}
		}
		
		return $m;
	}

	/**
	 * Deserializes an associative array into an object grpah
	 * 
	 * @param array $dom The array to deserialize
	 * @return mixed The root object of the graph.
	 */
	protected function deserialize_array($dom)
	{
		// just parse the first root element.
		foreach($dom as $key => $element)
			return $this->deserialize_element($key,$element,$this->node_conf);
	}
}