<?php
require_once 'PHPUnit/Framework.php';

// ugg
require_once '../../../lib/MockHeavyMetalTest.php';

uses('system.app.http.http_dispatcher');

/**
 * Tests basic dispatcher functionality
 */
class DispatcherTest extends MockHeavyMetalTest
{
    protected function setUp()
    {
    	global $_REQUEST, $_GET, $_POST;

    	$_GET['string']='Something';
    	$_GET['number']=1234;
    	
    	$_POST['string']='Something';
    	$_POST['number']=1234;
    }

    /**
     * Tests calling a controller via Dispatcher
     */
    public function testCall()
    {
    	$dispatcher=new HTTPDispatcher('/test/call');
    	$data=$dispatcher->call();
    	
    	$this->assertTrue($data['message']=='hello world');
    }

    /**
     * Tests dispatching a controller and rendering a view.
     */
    public function testDispatch()
    {
    	$dispatcher=new HTTPDispatcher('/test/dispatch');
    	$output=$dispatcher->dispatch();
    	$this->assertTrue($output=='hello world');
    }

    /**
     * Tests routing
     */
    public function testRoute()
    {
    	$dispatcher=new HTTPDispatcher('/test/random/route/thing');
    	$data=$dispatcher->call();
    	$this->assertTrue($data['what']=='random');
    	$this->assertTrue($data['where']=='thing');
    }

    private function __test_verb($which)
    {
    	$_REQUEST['real_method']=$which;

    	$dispatcher=new HTTPDispatcher('/test/reqmethod');
    	$data=$dispatcher->call();
    	$this->assertTrue($data['method']==$which);
       	$this->assertTrue($data['string']=='Something');
    	$this->assertTrue($data['number']==1234);
    }

    /**
     * Test POST
     */
    public function testPostVerb()
    {
    	$this->__test_verb('POST');
    }

    /**
     * Test GET
     */
    public function testGetVerb()
    {
    	$this->__test_verb('GET');
    }

    /**
     * Test PUT
     */
    public function testPutVerb()
    {
    	$this->__test_verb('PUT');
    }

    /**
     * Test DELETE
     */
    public function testDeleteVerb()
    {
    	$this->__test_verb('DELETE');
    }

    /**
     * Tests using custom verbs
     */
    public function testCustomVerb()
    {
    	$this->__test_verb('HELLOKITTY');
    }

    /**
     * Tests that ignored methods can't be called.
     */
    public function testIgnored()
    {
    	try
    	{
    		$dispatcher=new HTTPDispatcher('/test/setup');
	    	$output=$dispatcher->dispatch();
    		$this->assertTrue(false);
    	}
    	catch(IgnoredMethodCalledException $ex)
    	{
    		$this->assertTrue(true);
    	}
    }

    /**
     * Tests rendering iphone views.
     */
    public function testIPhoneView()
    {
    	$_SERVER['HTTP_USER_AGENT']='something iPhone whee';
    	$dispatcher=new HTTPDispatcher('/test/dispatch');
	    $output=$dispatcher->dispatch();
    	$this->assertTrue($output=='iphone says hello world');
    }

    /**
     * Tests rendering ajax views
     */
    public function testAjaxView()
    {
    	$_SERVER['HTTP_X_REQUESTED_WITH']='prototype?';
    	$dispatcher=new HTTPDispatcher('/test/dispatch');
	    $output=$dispatcher->dispatch();
    	$this->assertTrue($output=='ajax says hello world');
    }
}