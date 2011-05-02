<?
/**
 * SimpleDB Request wrapper
 * Very simple wrapper around the SimpleDB API.  Other PHP implementations were grossly
 * lacking or java engineered.
 */

uses('system.cloud.provider.amazon.aws_request');

/**
 * Represents a simple db api request.
 */
class SimpleDBRequest extends AWSRequest 
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
	
		$this->parameters['Version'] = '2007-11-07';      
		
		if (!$this->endpoint)
			$this->endpoint='https://sdb.amazonaws.com/';
	}
	
	
	/**
	 * Adds an attribute to the parameter list.
	 *
	 * @param int $index Attribute index.
	 * @param string $name Attribute name
	 * @param mixed $value Attribute value
	 * @param bool $can_replace Action allows attribute replacing
	 * @param bool $replace Replace attributes.
	 */
	private function put_attribute($index, $name, $value, $can_replace=true, $replace=true, $name_only=false)
	{
		if ($name_only)
			$this->parameters["AttributeName" . "."  . ($index)] =  $name;
		else
		{
			$this->parameters["Attribute" . "."  . ($index) . "." . "Name"] =  $name;

			if ($value)
		        $this->parameters["Attribute" . "."  . ($index) . "." . "Value"] =  $value;

			if ($can_replace)
       			$this->parameters["Attribute" . "."  . ($index) . "." . "Replace"] =  $replace ? "true" : "false";
		}
	}
	
	/**
	 * Adds attributes to the parameter list.
	 *
	 * @param array $attributes Key-value array of attributes.
	 * @param bool $can_replace Action allows attribute replacing
	 * @param bool $replace Replace attributes.
	 */
	public function put_attributes($attributes,$can_replace=true, $replace=true, $name_only=false)
	{
		$attributeIndex=0;
		
       foreach ($attributes as $name => $value) 
       {
       		if (is_array($value))
       		{
       			foreach($value as $val)
	       			$this->put_attribute($attributeIndex,$name,$val,$can_replace,$replace,$name_only);
	       			
   	   			$attributeIndex++;
       		}
       		else 
       		{
	       		if ($value)
	       		{
    	   			$this->put_attribute($attributeIndex,$name,$value,$can_replace,$replace,$name_only);
		            $attributeIndex++;
	       		}
    	   			
       		}
        }
	}
}
