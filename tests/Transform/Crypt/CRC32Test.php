<?php
use Q\Transform_Crypt_CRC32, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Crypt/CRC32.php';
require_once 'Q/Fs/File.php';

/**
 * Transform_Crypt_CRC32 test case.
 */
class Transform_Crypt_CRC32Test extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Crypt_CRC32
	 */
	private $Crypt_CRC32;

	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		$this->Crypt_CRC32 = new Transform_Crypt_CRC32();
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-crypt');
        if (!file_put_contents($this->tmpfile, "a test string")) $this->markTestSkipped('Unable to start test : couldn\'t create file.');
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Crypt_CRC32 = null;
        if (isset($this->tmpfile) && file_exists($this->tmpfile)) unlink($this->tmpfile);
		parent::tearDown();
	}
	
	/**
	 * Tests Crypt_CRC32->process()
	 */
	public function testEncrypt()
	{
		$this->assertEquals(sprintf('%08x', crc32("a test string")), $this->Crypt_CRC32->process("a test string"));
	}

	/**
	 * Tests Crypt_CRC32->process()
	 */
	public function testEncrypt_Salt()
	{
	    $this->Crypt_CRC32->useSalt = true;
	    
		$hash = $this->Crypt_CRC32->process("a test string");
		$this->assertRegExp('/^\w{6}\$\w{8}$/', $hash);
		
		$this->assertNotEquals(preg_replace('/^\w{6}\$/', '', $hash), $this->Crypt_CRC32->process("a test string"));
        $this->assertEquals($hash, $this->Crypt_CRC32->process("a test string", $hash));		
	}
	
	/**
	 * Tests Crypt_CRC32->process() with secret phrase
	 */
	public function testEncrypt_Secret()
	{
	    $this->Crypt_CRC32->secret = "s3cret";
		$this->assertEquals(sprintf('%08x', crc32("a test string" . "s3cret")), $this->Crypt_CRC32->process("a test string"));
	}
	
	/**
	 * Tests Crypt_CRC32->process() with a file
	 */
	public function testEncrypt_File()
	{
		$file = $this->getMock('Q\Fs_File', array('__toString', 'getContents'), array(), '', false);
		$file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));       
		$file->expects($this->once())->method('getContents')->will($this->returnValue("a test string"));
		
		$this->assertEquals(sprintf('%08x', crc32("a test string")), $this->Crypt_CRC32->process($file));
	}
}
