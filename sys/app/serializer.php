<?php
uses('system.app.attribute_reader');

/**
 * Provides a directed serialization framework for representing 
 * object structures in XML, JSON or YAML.  It is not meant to 
 * be a complete serialization solution, it's designed to transform
 * an object model to and from it's public representation in
 * whatever format is perferred.
 * 
 * Serialization and deserialization are driven by a yaml file that
 * define an object's properties and their children object.  The
 * serializer will use this configuration to control what is
 * read/written when processing through the object graph.
 */
abstract class Serializer
{
	/**
	 * XML Format
	 */
	const FORMAT_XML	=	'xml';
	
	/**
	 * JSON Format
	 */
	const FORMAT_JSON	=	'json';
	
	/**
	 * YAML Format
	 */
	const FORMAT_YAML	=	'yaml';
	
	/**
	 * Serialization directives config
	 * @var Config
	 */
	protected $node_conf=null;
	
	/**
	 * Root node name
	 * @var string
	 */
	protected $root='';
	
	/**
	 * The object being serialized
	 * @var mixed
	 */
	protected $object=null;
	
	/**
	 * The string being deserialized.
	 * @var string
	 */
	protected $content=null;
	
	/**
	 * An array of nodes
	 * @var array
	 */
	private static $_nodes=array();
	
	/**
	 * Retrieves the node configuration, loading it if it hasn't been already;
	 * 
	 * @param string $nodename Name of the node
	 * @return Config The node's configuration
	 */
	protected static function GetNodeConf($node_uri)
	{
		if (isset(self::$_nodes[$node_uri]))
			return self::$_nodes[$node_uri];
			
		$cache=Cache::GetCache('serialization');
 		$node=$cache->get($node_uri);
 				
		if (!$node)
 		{
	 		$filename=PATH_APP.'map/'.str_replace(".","/",$node_uri);
			$format=null;
	 			
			$data=null;
	 			
			if (file_exists($filename.'.js'))
			{
				$format="js";
				$data=json_decode(file_get_contents($filename.'.js'),true);
	 		}
			else if (file_exists($filename.'.yaml'))
	 		{
	 			$format="yaml";
				$data=syck_load(file_get_contents($filename.'.yaml'));
	 		}
	 		
	 		$map=new Config($data,$filename,$format);
	 		
	 		foreach($map as $key=>$value)
 				self::$_nodes[$key]=$value;

 			if (!isset(self::$_nodes[$node_uri]))
 				throw new Exception("Could not find $node_uri serialization node.");
 		}
 		
 		
 		$node=self::$_nodes[$node_uri];
 		$cache->set($node_uri,$node);
 		
 		return $node;
	}

	/**
	 * Serializes an object.
	 * 
	 * The object must have serializer annotation:
	 * 
	 * <code>
	 * [[ 
	 * serializer: { map: 'path/to/map', node: 'nameofrootnode' } 
	 * ]]
	 * </code>
	 * 
	 * @param Object $object The object to be serialized 
	 * @param string $format The format to serialize to.
	 * @param string $root The name of the root node
	 * @param string $node_uri The uri of the node, eg map.cms.content
	 * @param int $level The current serialization level.
	 * @return string
	 */
	public static function SerializeObject($object, $format=Serializer::FORMAT_JSON, $root=null, $node_uri=null, $level=0)
	{
		$serializer=null;
		
		switch($format)
		{
			case Serializer::FORMAT_XML:
				uses('system.app.serializer.xml_serializer');
				$serializer=new XMLSerializer($root,$node_uri,$level);
				break;
			case Serializer::FORMAT_JSON:
				uses('system.app.serializer.json_serializer');
				$serializer=new JSONSerializer($root,$node_uri,$level);
				break;
			case Serializer::FORMAT_YAML:
				uses('system.app.serializer.yaml_serializer');
				$serializer=new YAMLSerializer($root,$node_uri,$level);
				break;
			default:
				throw new Exception("Unknown serialization format '$format'.");
		}
		
		return $serializer->serialize($object);
	}

	/**
	 * Serializes an object.
	 * 
	 * The object must have serializer annotation:
	 * 
	 * <code>
	 * [[ 
	 * serializer: { config: 'path/to/config', node: 'nameofrootnode' } 
	 * ]]
	 * </code>
	 * 
	 * @param string $content The string to be deserialized. 
	 * @param string $format The format to serialize to.
	 * @param string $node The name of the root node
	 * @param string $config_file The uri of the config file.
	 * @param int $level The current serialization level.
	 * @return string
	 */
	public static function DeserializeObject($content,$format=Serializer::FORMAT_JSON,$node_uri=null)
	{
		$deserializer=null;
		
		switch($format)
		{
			case Serializer::FORMAT_XML:
				uses('system.app.serializer.xml_serializer');
				$deserializer=new XMLSerializer(null,$node_uri);
				break;
			case Serializer::FORMAT_JSON:
				uses('system.app.serializer.json_serializer');
				$deserializer=new JSONSerializer(null,$node_uri);
				break;
			case Serializer::FORMAT_YAML:
				uses('system.app.serializer.yaml_serializer');
				$deserializer=new YAMLSerializer(null,$node_uri);
				break;
			default:
				throw new Exception("Unknown serialization format '$format'.");
		}
		
		return $deserializer->deserialize($content);
	}
	
	/**
	 * Constructor 
	 * 
	 * @param Object $object The object to be serialized 
	 * @param string $node The name of the root node
	 * @param string $node_uri The uri of the node config file.
	 * @param int $level The current serialization level.
	 */
	public function __construct($root=null,$node_uri=null,$level=0,$node_conf=null)
	{
		if ($node_conf)
			$this->node_conf=$node_conf;
		else if ($node_uri)
			$this->node_conf=self::GetNodeConf($node_uri);
			
		if ($this->node_conf)
			$this->root=($root) ? $root: $this->node_conf->node;
		else
			$this->root=$root;
			
		$this->level=$level;
	}

	/**
	 * Performs the actual serialization
	 * 
	 * @return string
	 */
	abstract function do_serialize();
	
	/**
	 * Performs the actual deserialization
	 * 
	 * @return mixed
	 */
	abstract function do_deserialize();
	
	/**
	 * Sets up serialization
	 * 
	 * @param mixed $object The object to serialize
	 */
	protected function setup_serialization($object)
	{
		if (!$this->node_conf)
		{
			$attrs=AttributeReader::ClassAttributes($object);
			if ((!$attrs)||(!$attrs->serializer))
				throw new Exception('Class is missing necessary metadata for serialization.');

			$this->node_config=self::GetNodeConf($attrs->serializer);
			$this->root=$attrs->serializer->node;
		}
	
		$this->object=$object;
		$this->content=$this->do_serialize();
	}
	
	/**
	 * Serializes an object graph
	 * 
	 * @return string The serialized data.
	 */
	public function serialize($object)
	{
		$this->setup_serialization($object);
		return $this->content;
	}
	
	/**
	 * Sets up deserialization
	 * @param string $content Content to deserialize
	 */
	protected function setup_deserialization($content)
	{
		if (!$this->node_conf)
				throw new Exception("No configuration for deserialization.");

		$this->content=$content;
	}

	/**
	 * Deserializes a string to an object graph.
	 * 
	 * @param string $content Content to deserialize
	 * @return mixed The object graph
	 */
	public function deserialize($content)
	{
		$this->setup_deserialization($content);
		$this->object=$this->do_deserialize();
		return $this->object;
	}
}