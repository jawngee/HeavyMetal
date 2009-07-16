<?php
require_once 'PHPUnit/Framework.php';
require_once dirname(dirname(dirname(__FILE__).'..').'..').'/sys/sys.php';

// init heavymetal with a custom app path for testing
init();

/**
 * Base class for HeavyMetal tests
 */
class HeavyMetalTest extends PHPUnit_Framework_TestCase
{
	public function testNothing()
	{
		$this->assertTrue(true);
	}
}