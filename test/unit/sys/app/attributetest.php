<?php
require_once 'PHPUnit/Framework.php';

// ugg
require_once '../../../lib/MockHeavyMetalTest.php';

uses('sys.app.attribute_reader');
uses('app.lib.attribute_test_class');
uses('app.lib.attribute_inherit_class');


/**
 * Tests basic attribute reading functionality
 */
class AttributeTest extends MockHeavyMetalTest
{
    protected function setUp()
    {
    }

    /**
     * Test the attribute reading for a class.
     */
    public function testClass()
    {
    	$class=new AttributeTestClass();
    	
    	$meta=AttributeReader::ClassAttributes($class);
    	
    	$this->assertTrue($meta->type=='model');
    	
    	// test subobjects
    	$this->assertTrue($meta->info->class=='AttributedTest');
    	
    	// test booleans
       	$this->assertTrue($meta->info->enabled);
       	
       	// test arrays
       	$this->assertTrue($meta->keywords[0]=='key1');
       	$this->assertTrue(count($meta->keywords)==3);

       	// test iteration
        foreach($meta->keywords as $key)
        	$this->assertTrue(strpos($key,'key')!==false);
    }

    /**
     * Test the attribute reading for a method.
     */
    public function testMethod()
    {
    	$class=new AttributeTestClass();
    	
    	$cmeta=AttributeReader::ClassAttributes($class);
    	$meta=AttributeReader::MethodAttributes($class,'method');
    	
    	$cmeta->merge($meta);
    	
    	$this->assertTrue($meta->name=='method');
       	
       	// test object arrays
       	$this->assertTrue($meta->args[0]->type=='string');
       	$this->assertTrue(count($meta->args)==2);

       	// test iteration
        foreach($meta->args as $arg)
        	$this->assertTrue(strpos($arg->name,'arg')!==false);
    }

    /**
     * Test the attribute reading for a property.
     */
    public function testProperty()
    {
    	$class=new AttributeTestClass();
    	
    	$meta=AttributeReader::PropertyAttributes($class,'property');
    	
    	$this->assertTrue($meta->name=='property');
       	
       	// test object arrays
       	$this->assertTrue($meta->validators[0]->type=='date');
       	$this->assertTrue(count($meta->validators)==2);

       	// test iteration
        foreach($meta->validators as $validator)
        	$this->assertTrue(strpos($validator->name,'valid')!==false);
    }

    /**
     * Test the attribute reading for merging attributes.
     */
    public function testMerging()
    {
    	$class=new AttributeTestClass();
    	
    	$cmeta=AttributeReader::ClassAttributes($class);
    	$meta=AttributeReader::MethodAttributes($class,'method');

    	$cmeta=$cmeta->merge($meta);
    	
    	$this->assertTrue($cmeta->type=='model');
    	
    	// test subobjects
    	$this->assertTrue($cmeta->info->class=='AttributedTest');
    	
    	// test booleans
       	$this->assertTrue($cmeta->info->enabled);
       	
       	// test arrays
       	$this->assertTrue($cmeta->keywords[0]=='key1');
       	$this->assertTrue(count($cmeta->keywords)==3);

       	// test iteration
        foreach($cmeta->keywords as $key)
        	$this->assertTrue(strpos($key,'key')!==false);
    	
    	
    	$this->assertTrue($cmeta->name=='method');
       	
       	// test object arrays
       	$this->assertTrue($cmeta->args[0]->type=='string');
       	$this->assertTrue(count($cmeta->args)==2);

       	// test iteration
        foreach($cmeta->args as $arg)
        	$this->assertTrue(strpos($arg->name,'arg')!==false);
    }
    
    /**
     * Test the attribute reading meta attributes inheritance.
     */
    public function testInheritance()
    {
    	$class=new AttributeChildClass();
    	
    	$classmeta=AttributeReader::ClassAttributes($class);
    	$this->assertTrue($classmeta->grand=='123');
    	$this->assertTrue($classmeta->parent=='456');
       	$this->assertTrue($classmeta->child=='789');
       	
    	$methodmeta=AttributeReader::MethodAttributes($class,'method');
    	$this->assertTrue($methodmeta->grand=='ABC');
    	$this->assertTrue($methodmeta->parent=='DEF');
       	$this->assertTrue($methodmeta->child=='GHI');
       	
	    $propmeta=AttributeReader::PropertyAttributes($class,'property');
    	$this->assertTrue($propmeta->grand=='abc');
    	$this->assertTrue($propmeta->parent=='def');
       	$this->assertTrue($propmeta->child=='ghi');
    }    
}