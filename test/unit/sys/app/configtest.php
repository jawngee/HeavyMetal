<?php
require_once 'PHPUnit/Framework.php';

// ugg
require_once '../../../lib/MockHeavyMetalTest.php';

uses('sys.app.config');

/**
 * Tests basic Config class functionality
 */
class ConfigTest extends MockHeavyMetalTest
{
    protected function setUp()
    {
    }

    /**
     * Loads the test environment
     */
    public function testEnvironment()
    {
    	Config::LoadEnvironment('test');
    	$this->assertTrue(Config::$environment=='test');
    }

    /**
     * Test configuration as object properties
     */
    public function testConfig()
    {
    	$config=Config::Get('sample');
    	$this->assertTrue($config->servers->items[0]->host=='http://getheavy.info/');
    }
    
    /**
     * Test configuration as array access
     */
    public function testConfigAsArray()
    {
    	$config=Config::Get('sample');
	
    	$this->assertTrue(count($config['servers'])==3);
    	$this->assertTrue($config['servers'][0]['host']=='http://getheavy.info/');
    }
    
    /**
     * Tests saving the configurationd data.
     */
    public function testConfigSave()
    {
		$config=Config::Get('sample');
		$config->servers->items[0]->host='testing';
		$config->save('saved',Config::FORMAT_YAML);

		$config=Config::Get('saved');
		$this->assertTrue($config->servers->items[0]->host=='testing');
    }
}
