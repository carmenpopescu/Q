<?php
use Q\Transform_Decrypt_OpenSSL, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Decrypt/OpenSSL.php';
require_once 'Q/Fs/File.php';

/**
 * Transform_Decrypt_OpenSSL test case.
 */
class Transform_Decrypt_OpenSSLTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Decrypt_OpenSSL
	 */
	private $Decrypt_OpenSSL;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		$this->Decrypt_OpenSSL = new Transform_Decrypt_OpenSSL(array('secret'=>'s3cret'));

        $this->file = sys_get_temp_dir() . '/q-decrypt_test-' . md5(uniqid());		
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Decrypt_OpenSSL = null;
		parent::tearDown();
	}
	
	/**
	 * Tests Decrypt_OpenSSL->process()
	 */
	public function testDecrypt()
	{
	    $encrypted = openssl_encrypt("a test string", 'AES256', 's3cret');
		$this->assertEquals("a test string", $this->Decrypt_OpenSSL->process($encrypted));
	}

	/**
	 * Tests Decrypt_OpenSSL->process() with DES method
	 */
	public function testDecrypt_DES()
	{
	    $this->Decrypt_OpenSSL->method = 'DES';
	    $encrypted = openssl_encrypt("a test string", 'DES', 's3cret');
		$this->assertEquals("a test string", $this->Decrypt_OpenSSL->process($encrypted));
	}	
	
	/**
	 * Tests Decrypt_OpenSSL->process() with a file
	 */
	public function testDecrypt_File()
	{
		$encrypted = openssl_encrypt("a test string", 'AES256', 's3cret');
		
		$file = $this->getMock('Q\Fs_File', array('__toString', 'getContents'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->file));       
		$file->expects($this->once())->method('getContents')->will($this->returnValue($encrypted));
		
		$this->assertEquals("a test string", $this->Decrypt_OpenSSL->process($file));
	}
	
	/**
	 * Tests Decrypt_OpenSSL->process() where decrypt fails
	 */
	public function testDecrypt_NotEncrypted()
	{
		$this->setExpectedException('Q\Transform_Exception', "Failed to decrypt value with AES256 using openssl.");
		$this->Decrypt_OpenSSL->process("not encrypted");
	}

	/**
	 * Tests Decrypt_OpenSSL->process() where decrypt fails because of incorrect secret phrase
	 */
	public function testDecrypt_WrongSecret()
	{
		$this->setExpectedException('Q\Transform_Exception', "Failed to decrypt value with AES256 using openssl.");
		$encrypted = openssl_encrypt("a test string", 'AES256', 'another secret');
		$this->Decrypt_OpenSSL->process($encrypted);
	}
	
    /**
     * Tests Decrypt_OpenSSL->getReverse()
     */
    public function testGetReverse()
    {
        $reverse = $this->Decrypt_OpenSSL->getReverse();        
        $this->assertType('Q\Transform_Crypt_OpenSSL', $reverse);
    }
    
    /**
     * Tests Decrypt_OpenSSL->getReverse() with a chain
     */ 
    public function testGetReverse_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Crypt_OpenSSL'))->will($this->returnValue('reverse of mock transformer'));
        
        $this->Decrypt_OpenSSL->chainInput($mock);
        
        $this->assertEquals('reverse of mock transformer', $this->Decrypt_OpenSSL->getReverse());
    }    

    /**
     * Tests Decrypt_OpenSSL->getReverse() with two chains
     */
    public function testGetReverse_ChainDouble() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Crypt_OpenSSL'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform1 = new Transform_Decrypt_OpenSSL();
        $transform2 = new Transform_Decrypt_OpenSSL();
        
        $transform2->chainInput($mock);
        $transform1->chainInput($transform2);
        
        $this->assertEquals('reverse of mock transformer', $transform1->getReverse());
    }
}
