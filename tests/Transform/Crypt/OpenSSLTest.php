<?php
use Q\Transform_Crypt_OpenSSL, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Crypt/OpenSSL.php';
require_once 'Q/Fs/File.php';

/**
 * Transform_Crypt_OpenSSL test case.
 */
class Transform_Crypt_OpenSSLTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Crypt_OpenSSL
	 */
	private $Crypt_OpenSSL;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		$this->Crypt_OpenSSL = new Transform_Crypt_OpenSSL(array('secret'=>'s3cret'));

        $this->file = sys_get_temp_dir() . '/q-crypt_test-' . md5(uniqid());
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Crypt_OpenSSL = null;
		if (file_exists($this->file)) unlink($this->file);
		parent::tearDown();
	}
	
	/**
	 * Tests Crypt_OpenSSL->process()
	 */
	public function testEncrypt()
	{
		$this->assertEquals(openssl_encrypt("a test string", 'AES256', 's3cret'), $this->Crypt_OpenSSL->process("a test string"));
	}
	
    /**
     * Tests Crypt_OpenSSL->process() - no secret var provided
     */
    public function testProcessNotice_NoSecret()
    {
        $this->setExpectedException('Exception','Secret key is not set for OpenSSL password encryption. This is not secure.');
        $transformer = new Transform_Crypt_OpenSSL();
        $transformer->process("a test string");
    }
	
	/**
	 * Tests Crypt_OpenSSL->process() with DES method
	 */
	public function testEncrypt_DES()
	{
	    $this->Crypt_OpenSSL->method = 'DES';
		$this->assertEquals(openssl_encrypt("a test string", 'DES', 's3cret'), $this->Crypt_OpenSSL->process("a test string"));
	}

	/**
	 * Tests Crypt_OpenSSL->process() with a file
	 */
	public function testEncrypt_File()
	{
		$file = $this->getMock('Q\Fs_File', array('__toString', 'getContents'), array(), '', false);
		$file->expects($this->any())->method('__toString')->will($this->returnValue($this->file));       
        $file->expects($this->once())->method('getContents')->will($this->returnValue("a test string"));
		
		$this->assertEquals(openssl_encrypt("a test string", 'AES256', 's3cret'), $this->Crypt_OpenSSL->process($file));
	}

    /**
     * Tests Transform_Crypt_OpenSSL->process() with a chain
     */
    public function testEncrypt_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('process'));
        $mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue('a test string'));
        
        $this->Crypt_OpenSSL->chainInput($mock);
        $contents = $this->Crypt_OpenSSL->process('test');

        $this->assertType('Q\Transform_Crypt_OpenSSL', $this->Crypt_OpenSSL);
        $this->assertEquals(openssl_encrypt("a test string", 'AES256', 's3cret'), $contents);
    }
    
    /**
     * Tests Transform_Crypt_OpenSSL->output()
     */
    public function testOutput() 
    {
        ob_start();
        try{
            $this->Crypt_OpenSSL->output("a test string");
        } catch (Expresion $e) {
            ob_end_clean();
            throw $e;
        }
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_Crypt_OpenSSL', $this->Crypt_OpenSSL);
        $this->assertEquals(openssl_encrypt("a test string", 'AES256', 's3cret'), $contents);
    }
    
    /**
     * Tests Transform_Crypt_OpenSSL->save()
     */
    public function testSave() 
    {
        $this->Crypt_OpenSSL->save($this->file, "a test string");
        
        $this->assertType('Q\Transform_Crypt_OpenSSL', $this->Crypt_OpenSSL);
        $this->assertEquals(openssl_encrypt("a test string", 'AES256', 's3cret'), file_get_contents($this->file));
    }    
	
    /**
     * Tests Crypt_OpenSSL->getReverse()
     */
    public function testGetReverse()
    {
        $reverse = $this->Crypt_OpenSSL->getReverse();        
        $this->assertType('Q\Transform_Decrypt_OpenSSL', $reverse);
    }
    
    /**
     * Tests Crypt_OpenSSL->getReverse() with a chain
     */ 
    public function testGetReverse_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Decrypt_OpenSSL'))->will($this->returnValue('reverse of mock transformer'));
        
        $this->Crypt_OpenSSL->chainInput($mock);
        
        $this->assertEquals('reverse of mock transformer', $this->Crypt_OpenSSL->getReverse());
    }    

    /**
     * Tests Crypt_OpenSSL->getReverse() with two chains
     */
    public function testGetReverse_ChainDouble() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Decrypt_OpenSSL'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform1 = new Transform_Crypt_OpenSSL();
        $transform2 = new Transform_Crypt_OpenSSL();
        
        $transform2->chainInput($mock);
        $transform1->chainInput($transform2);
        
        $this->assertEquals('reverse of mock transformer', $transform1->getReverse());
    }

    /**
     * Tests Transform_Crypt_OpenSSL->process() - no secret string specified
     */
    public function testProcessException_NoSecretString() 
    {
        $this->setExpectedException('Exception', 'Secret key is not set for OpenSSL password encryption. This is not secure.');
        $transform = new Transform_Crypt_OpenSSL();
        $transform->process('something');
    }
}
