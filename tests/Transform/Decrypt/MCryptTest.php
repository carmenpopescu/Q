<?php
use Q\Transform_Decrypt_MCrypt, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Decrypt/MCrypt.php';
require_once 'Q/Fs/File.php';
/**
 * Decrypt_MCrypt test case.
 */
class Transform_Decrypt_MCryptTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Transform_Decrypt_MCrypt
	 */
	private $Decrypt_MCrypt;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		if (!extension_loaded('mcrypt')) $this->markTestSkipped("mcrypt extension is not available");
		
		parent::setUp();
		$this->Decrypt_MCrypt = new Transform_Decrypt_MCrypt(array('method'=>'blowfish', 'secret'=>'s3cret'));
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Decrypt_MCrypt = null;
		parent::tearDown();
	}
	
	/**
	 * Tests Decrypt_MCrypt->process() with blowfish method
	 */
	public function testEncrypt()
	{
		$this->assertEquals(mcrypt_encrypt('blowfish', 's3cret', "a test string", MCRYPT_MODE_ECB), $this->Decrypt_MCrypt->process("a test string"));
	}
	
	/**
	 * Tests Decrypt_MCrypt->process() with blowfish method
	 */
	public function testDecrypt()
	{
	    $encrypted = mcrypt_encrypt('blowfish', 's3cret', "a test string", MCRYPT_MODE_ECB);
	    $this->assertEquals("a test string", $this->Decrypt_MCrypt->process($encrypted));
	}

	/**
	 * Tests Decrypt_MCrypt->process() with DES method
	 */
	public function testEncrypt_des()
	{
	    $this->Decrypt_MCrypt->method = 'des';
		$this->assertEquals(mcrypt_encrypt('des', 's3cret', "a test string", MCRYPT_MODE_ECB), $this->Decrypt_MCrypt->process("a test string"));
	}
	
	/**
	 * Tests Decrypt_MCrypt->process() with DES method
	 */
	public function testDecrypt_des()
	{
	    $this->Decrypt_MCrypt->method = 'des';
	    $encrypted = mcrypt_encrypt('des', 's3cret', "a test string", MCRYPT_MODE_ECB);
		$this->assertEquals("a test string", $this->Decrypt_MCrypt->process($encrypted));
	}	

	/**
	 * Tests Decrypt_MCrypt->process() with a file
	 */
	public function testEncrypt_File()
	{
		$file = $this->getMock('Q\Fs_File', array('getContents'));
		$file->expects($this->once())->method('getContents')->will($this->returnValue("a test string"));
		
		$this->assertEquals(mcrypt_encrypt('blowfish', 's3cret', "a test string", MCRYPT_MODE_ECB), $this->Decrypt_MCrypt->process($file));
	}
	
	/**
	 * Tests Decrypt_MCrypt->process() with a file
	 */
	public function testDecrypt_File()
	{
		$encrypted = mcrypt_encrypt('blowfish', 's3cret', "a test string", MCRYPT_MODE_ECB);
		
		$file = $this->getMock('Q\Fs_File', array('__toString', 'getContents'));
		$file->expects($this->never())->method('__toString');
		$file->expects($this->once())->method('getContents')->will($this->returnValue($encrypted));
		
		$this->assertEquals("a test string", $this->Decrypt_MCrypt->process($file));
	}
	
	/**
	 * Tests Decrypt_MCrypt->process() where process fails because of incorrect secret phrase
	 */
	public function testDecrypt_WrongSecret()
	{
		$encrypted = mcrypt_encrypt('blowfish', 'another_secret', "a test string", MCRYPT_MODE_ECB);
		$this->assertNotEquals("a test string", $this->Decrypt_MCrypt->process($encrypted));
	}
}
