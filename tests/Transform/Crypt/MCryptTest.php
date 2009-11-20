<?php
use Q\Transform_Crypt_MCrypt, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Crypt/MCrypt.php';
require_once 'Q/Fs/File.php';


/**
 * Transform_Crypt_MCrypt test case.
 */
class Transform_Crypt_MCryptTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Transform_Crypt_Serialize_MCrypt
	 */
	private $Crypt_MCrypt;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		if (!extension_loaded('mcrypt')) $this->markTestSkipped("mcrypt extension is not available");
		
		parent::setUp();
		$this->Crypt_MCrypt = new Transform_Crypt_MCrypt(array('method'=>'blowfish', 'secret'=>'s3cret'));
		
		$this->file = sys_get_temp_dir() . '/q-crypt_test-' . md5(uniqid());
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Crypt_MCrypt = null;
		parent::tearDown();
	}
	
	/**
	 * Tests Crypt_MCrypt->process() with blowfish method
	 */
	public function testEncrypt()
	{
		$this->assertEquals(mcrypt_encrypt('blowfish', 's3cret', "a test string", MCRYPT_MODE_ECB), $this->Crypt_MCrypt->process("a test string"));
	}
	
	/**
	 * Tests Crypt_MCrypt->process() with DES method
	 */
	public function testEncrypt_des()
	{
	    $this->Crypt_MCrypt->method = 'des';
		$this->assertEquals(mcrypt_encrypt('des', 's3cret', "a test string", MCRYPT_MODE_ECB), $this->Crypt_MCrypt->process("a test string"));
	}
	
	/**
	 * Tests Crypt_MCrypt->process() with a file
	 */
	public function testEncrypt_File()
	{
	    $file = $this->getMock('Q\Fs_File', array('__toString', 'getContents'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->file));       
	    $file->expects($this->once())->method('getContents')->will($this->returnValue("a test string"));
		
		$this->assertEquals(mcrypt_encrypt('blowfish', 's3cret', "a test string", MCRYPT_MODE_ECB), $this->Crypt_MCrypt->process($file));
	}
	
    /**
     * Tests Crypt_MCrypt->getReverse()
     */
	public function testGetReverse()
    {
        $reverse = $this->Crypt_MCrypt->getReverse();
        $this->assertType('Q\Transform_Decrypt_MCrypt', $reverse);
    }

    /**
     * Tests Crypt_MCrypt->getReverse() with a chain
     */   
    public function testGetReverse_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Decrypt_MCrypt'))->will($this->returnValue('reverse of mock transformer'));
        
        $this->Crypt_MCrypt->chainInput($mock);
        
        $this->assertEquals('reverse of mock transformer', $this->Crypt_MCrypt->getReverse());
    }
}
