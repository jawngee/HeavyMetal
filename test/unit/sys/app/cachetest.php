<?php
require_once 'PHPUnit/Framework.php';

// ugg
require_once '../../../lib/MockHeavyMetalTest.php';

uses('sys.app.cache');

/**
 * Tests basic Cache class functionality
 */
class CacheTest extends MockHeavyMetalTest
{
    protected function setUp()
    {
    }

    /**
     * Test the NullCache
     */
    public function testNull()
    {
    	$cache=Cache::GetCache('null');
    	$cache->set('test','data');
    	$this->assertTrue($cache instanceof NullCache);
    	$this->assertTrue($cache->get('test')=='data');
    }

    /**
     * Tests the APCCache
     */
    public function testAPC()
    {
    	$cache=Cache::GetCache('apc');
    	$this->assertTrue($cache instanceof APCCache);
    	if ($cache->enabled())
    	{
    		$cache->set('test','data');
    		$this->assertTrue($cache->get('test')=='data');
    	}
    	else 
    		$this->markTestSkipped('APC is not enabled.');
    }

    /**
     * Tests the MemcachedCache.
     */
    public function testMemcached()
    {
    	$cache=Cache::GetCache('memcached');
    	$this->assertTrue($cache instanceof MemcachedCache);
    	if ($cache->enabled())
    	{
    		$cache->set('test','data');
    		$this->assertTrue($cache->get('test')=='data');
    	}
		else 
			$this->markTestSkipped('Memcached is not enabled.');
    }
 
}

