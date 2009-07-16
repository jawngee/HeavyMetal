<?php
require_once 'PHPUnit/Framework.php';

require_once realpath(dirname(__FILE__).'/../../').'/sys/sys.php';

// init heavymetal with a custom app path for testing
init(ascend_path(dirname(__FILE__),1).'/mock/');

/**
 * Base class for HeavyMetal tests
 */
class MockHeavyMetalTest extends PHPUnit_Framework_TestCase
{
	public function testNothing()
	{
		$this->assertTrue(true);
	}
}