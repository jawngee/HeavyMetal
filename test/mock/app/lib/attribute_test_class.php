<?php
/**
 * [[
 * type: 'model'
 * info: { class: "AttributedTest", description: "test", enabled: true }
 * before:
 *   acl:
 *     object: "nice"
 *     permissions: [ "read", "write", "delete" ] 
 * keywords:
 *   - "key1"
 *   - "key2"
 *   - "key3"
 * ]]
 *
 */
class AttributeTestClass
{
	/**
	 * [[
	 * name: "property"
	 * type: "string"
	 * validators:
	 *   - { name: "valid", type: "date", message: "nice1" }
	 *   - { name: "valid", type: "notnull", message: "required" }
	 * ]]
	 * 
	 * @var unknown_type
	 */
	var $property="what";
	
	/**
	 * [[
	 * name: "method"
	 * returns: "void"
	 * run: "before"
	 * before:
	 *   acl:
	 *     object: "fag"
	 *     permissions: [ "read", "write" ] 
	 * args:
	 *   - { type: "string", name: "arg1", null: true }  
	 *   - { type: "int", name: "arg2", null: 'false' }  
	 * ]]
	 */
	public function method()
	{
		
	}
}