<?php
uses('system.app.serializer');

/**
 * Serializes an object graph to XML
 */
class XMLSerializer extends Serializer
{
	/**
	 * Serializes an array to XML
	 * 
	 * @param array $array The array to serialize
	 * @param Config $conf The configuration for the array.
	 * @return string The serialized array
	 */
	private function serialize_array($array,$conf,$itemconf,$level)
	{
		$element="<{$conf->name}>\n";
		$level++;
		
		foreach($array as $value)
			$element.=str_pad("",$level,"\t").$this->serialize_object($value,$itemconf->node,$itemconf,$level+1);

		$level--;
		
		$element.=str_pad("",$level,"\t")."</{$conf->name}>";
		
		return $element;
	}
	
	/**
	 * Serializes an object to xml
	 * @param mixed $object The object to serialize
	 * @return string
	 */
	private function serialize_object($object,$root=null,$node_conf=null,$level=null)
	{
		$root=($root) ? $root : $this->root;
		$node_conf=($node_conf) ? $node_conf : $this->node_conf;
		$level=($level) ? $level : $this->level;
		
		$element="<{$root}";
		$attr='';
		if ($node_conf->attributes)
    		foreach($node_conf->attributes->items as $a)
    			if ($object->{$a->name})
    				$attr.=" {$a->name}='".$object->{$a->name}."'";
		$element.=$attr;

		
		if ((!$node_conf->content) || (count($node_conf->content->items)==0))
			return $element.=' />';
		
		// serialize content
		$children='';
		foreach($node_conf->content->items as $child)
		{
			$v=$object->{$child->name};
			if (($v) || (is_array($v)))
			{
				switch($child->type)
				{
					case 'html':
						$value=htmlentities($v);
						break;
					case 'string':
						$value="<{$child->name}>$v</{$child->name}>";
						break;
					case 'hash':
					case 'array':
						$childconf=self::GetNodeConf($child->contains);
						$value=$this->serialize_array($v,$child,$childconf,$level);
						break;
					default:
						$childconf=self::GetNodeConf($child->type);
						$value=$this->serialize_object($v,$child->name,$childconf,$level+1);
						break;
				}

				if (trim($value,"\t"))
					$children.=str_pad("",$level+1,"\t").$value."\n";
			}
		}
			
		if($children)
			$element.=">\n".$children.str_pad("",$level,"\t")."</{$root}>";
		else
			$element.=" />";
		
		return $element;
	}
	
	/**
	 * @see sys/app/Serializer#do_serialize()
	 */
	public function do_serialize()
	{
		return $this->serialize_object($this->object);
	}

	/**
	 * Deserialize an SimpleXMLElement
	 * @param SimpleXMLElement $ele The element to deserialize
	 * @return mixed
	 */
	private function deserialize_element($ele,$conf)
	{
		$m=create_class($conf->class);
		
		$node_conf=($conf) ? $conf : $this->node_conf;
		
		if ($node_conf->attributes)
    		foreach($conf->attributes->items as $a)
    		{
    			$an=$a->name;
    			if ($ele[$an])
    				$m->{$an}=(String)$ele[$an];
    		}
		
		if ($conf->content)
			foreach($conf->content->items as $child)
			{
					switch($child->type)
					{
						case 'html':
							$m->{$child->name}=xss_clean(trim((string)$ele));
							break;
						case 'string':
							$m->{$child->name}=xss_clean((string)$ele->{$child->name});
							break;
						case 'array':
							if (!$m->{$child->name})
								$m->{$child->name}=array();
							
							$children=$ele->{$child->name}->children();
							$childconf=self::GetNodeConf($child->contains);
							foreach($children as $childele)
							{
								$parsed=$this->deserialize_element($childele,$childconf);
								$m->{$child->name}[]=$parsed;
							}
							break;
						case 'hash':
							$children=$ele->{$child->name}->children();
							$childconf=self::GetNodeConf($child->contains);
							
							if (!$m->{$child->name})
								$m->{$child->name}=array();
								
							foreach($children as $childele)
							{
								$parsed=$this->deserialize_element($childele,$childconf);
								$m->{$child->name}[$parsed->{$child->key}]=$parsed;
							}
							break;
						default:
							$childconf=self::GetNodeConf($child->type);
							$m->{$child->name}=$this->deserialize_element($ele->{$child->name},$childconf);
							break;
					}
			}
			
		return $m;
	}
	
	/**
	 * @see sys/app/Serializer#do_deserialize()
	 */
	public function do_deserialize()
	{
		$dom=simplexml_load_string($this->content);
		$result=$this->deserialize_element($dom,$this->node_conf);
		return $result;
	}
}