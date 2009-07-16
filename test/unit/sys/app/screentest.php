<?php
require_once 'PHPUnit/Framework.php';

// ugg
require_once '../../../lib/MockHeavyMetalTest.php';

uses('system.app.http.http_dispatcher');

/**
 * Tests basic dispatcher functionality
 */
class ScreenTest extends MockHeavyMetalTest
{
    protected function setUp()
    {
    }

    public function testBeforeScreen()
    {
    	$dispatcher=new HTTPDispatcher('/test/screen/before');
    	$data=$dispatcher->call();
    	
    	$this->assertTrue($data['screen']=='before');
    	$this->assertTrue($data['saywhat']=='A string');
    	$this->assertTrue($data['class_after']=='after');
       	$this->assertFalse(isset($data['class_before']));
    }

    public function testAfterScreen()
    {
    	$dispatcher=new HTTPDispatcher('/test/screen/after');
    	$data=$dispatcher->call();
    	
    	$this->assertTrue($data['screen']=='after');
    	$this->assertTrue($data['class_before']=='before');
    	$this->assertTrue($data['class_after']=='after');
    }

    public function testBeforeAfterScreen()
    {
    	$dispatcher=new HTTPDispatcher('/test/screen/both');
    	$data=$dispatcher->call();
    	
    	$this->assertTrue($data['screen']=='both');
       	$this->assertFalse(isset($data['class_before']));
       	$this->assertFalse(isset($data['class_after']));
    }
}