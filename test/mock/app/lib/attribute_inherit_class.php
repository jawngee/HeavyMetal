<?php
/**
 * [[
 * grand: '123'
 * parent: '123'
 * child: '123'
 * ]]
 *
 */
class AttributeGrandParentClass
{
	/**
	 * [[
	 * grand: 'abc'
	 * parent: 'abc'
	 * child: 'abc'
	 * ]]
	 * 
	 * @var unknown_type
	 */
	var $property="what";
	
	/**
	 * [[
	 * grand: 'ABC'
	 * parent: 'ABC'
	 * child: 'ABC'
	 * ]]
	 */
	public function method()
	{
		
	}
}

/**
 * [[
 * parent: '456'
 * child: '456'
 * ]]
 *
 */
class AttributeParentClass extends AttributeGrandParentClass
{
	/**
	 * [[
	 * parent: "def"
	 * child: "def"
	 * ]]
	 * 
	 * @var unknown_type
	 */
	var $property="what";
	
	/**
	 * [[
	 * parent: "DEF"
	 * child: "DEF"
	 * ]]
	 */
	public function method()
	{
		
	}
}

/**
 * [[
 * child: '789'
 * ]]
 *
 */
class AttributeChildClass extends AttributeParentClass
{
	/**
	 * [[
	 * child: "ghi"
	 * ]]
	 * 
	 * @var unknown_type
	 */
	var $property="what";
	
	/**
	 * [[
	 * child: "GHI"
	 * ]]
	 */
	public function method()
	{
		
	}
}
