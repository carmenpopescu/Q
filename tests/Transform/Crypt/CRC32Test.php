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

    /**
     * Tests Crypt_CRC32->process() with a chain
     */
    public function testEncrypt_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('process'));
        $mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue("a test string"));
        
        $this->Crypt_CRC32->chainInput($mock);
        $contents = $this->Crypt_CRC32->process('test');

        $this->assertType('Q\Transform_Crypt_CRC32', $this->Crypt_CRC32);
        $this->assertEquals(sprintf('%08x', crc32("a test string")), $contents);
    }
    
    /**
     * Tests Transform_Crypt_CRC32->output()
     */
    public function testOutput() 
    {
        ob_start();
        try{
            $this->Crypt_CRC32->output("a test string");
        } catch (Expresion $e) {
            ob_end_clean();
            throw $e;
        }
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_Crypt_CRC32', $this->Crypt_CRC32);
        $this->assertEquals(sprintf('%08x', crc32("a test string")), $contents);
    }

    /**
     * Tests Transform_Crypt_CRC32->save()
     */
    public function testSave() 
    {
        $this->Crypt_CRC32->save($this->tmpfile, "a test string");
        
        $this->assertType('Q\Transform_Crypt_CRC32', $this->Crypt_CRC32);
        $this->assertEquals(sprintf('%08x', crc32("a test string")), file_get_contents($this->tmpfile));
    }    

    /**
     * Tests Transform_Crypt_CRC32->getReverse()
     */
    public function testGetReverse() 
    {
        $this->setExpectedException('Q\Transform_Exception', 'There is no reverse transformation defined.');
        $this->Crypt_CRC32->getReverse();
    }   
}
