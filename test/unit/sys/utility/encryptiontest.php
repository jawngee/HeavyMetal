<?php
require_once 'PHPUnit/Framework.php';

// ugg
require_once '../../../lib/MockHeavyMetalTest.php';

uses('sys.utility.encryption');

/**
 * Tests basic Encryption class functionality
 */
class EncryptionTest extends MockHeavyMetalTest
{
    protected function setUp()
    {
    }

    /**
     * Tests encryption
     */
    public function testEncrypt()
    {
    	$string="this is a test string";
    	
    	$enc=new Encryption();
    	
    	$encrypted=$enc->encode($string);
    	
    	$this->assertTrue($encrypted!=$string);
    	
    	$unencrypted=$enc->decode($encrypted);
    	
    	$this->assertTrue($unencrypted==$string);
    }
}

