<?php
use Q\Transform_Crypt_MD5, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Crypt/MD5.php';
require_once 'Q/Fs/File.php';

/**
 * Transform_Crypt_MD5 test case.
 */
class Transform_Crypt_MD5Test extends PHPUnit_Framework_TestCase 
{
    
    protected $tmpfile;
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $this->Crypt_MD5 = new Transform_Crypt_MD5();
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-crypt');
        if (!file_put_contents($this->tmpfile, "a test string")) $this->markTestSkipped('Unable to start test : couldn\'t create file.');
    }
        
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->Crypt_Hash = null;
        if (isset($this->tmpfile) && file_exists($this->tmpfile)) unlink($this->tmpfile);
    }
    
    /**
	 * Tests Transform_Crypt_MD5->process()
	 */
	public function testEncrypt() 
	{
		$contents = $this->Crypt_MD5->process('test');

		$this->assertType('Q\Transform_Crypt_MD5', $this->Crypt_MD5);
		$this->assertEquals(md5('test'), $contents);
	}


    /**
     * Tests Transform_Crypt_MD5->process() with a chain
     */
    public function testEncrypt_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('process'));
        $mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue('md5crypt_testarea'));
        
        $this->Crypt_MD5->chainInput($mock);
        $contents = $this->Crypt_MD5->process('test');

        $this->assertType('Q\Transform_Crypt_MD5', $this->Crypt_MD5);
        $this->assertEquals(md5('md5crypt_testarea'), $contents);
    }
    
    /**
     * Tests Transform_Crypt_MD5->process() using salt
     */
    public function testEncrypt_Salt()
    {
        $this->Crypt_MD5->useSalt = true;
        
        $hash = $this->Crypt_MD5->process("a test string");
        $this->assertRegExp('/^\w{6}\$\w{32}$/', $hash);
        
        $this->assertNotEquals(preg_replace('/\w{32}$/', '', $hash) . md5("a test string"), $hash);
        $this->assertEquals($hash, $this->Crypt_MD5->process("a test string", $hash));      
    }
    
    /**
     * Tests Transform_Crypt_MD5->process() with secret phrase
     */
    public function testEncrypt_Secret()
    {
        $this->assertEquals(md5("a test string" . "s3cret"), Transform::encrypt('md5:secret=s3cret;')->process("a test string"));
    }
    
    /**
     * Tests Transform_Crypt_MD5->process() with a file
     */    
    public function testEncrypt_File()
    {        
        $file = $this->getMock('Q\Fs_File', array('__toString'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));       

        $this->assertEquals(md5_file($this->tmpfile), Transform::encrypt('md5')->process($file));
    }
        
    /**
     * Tests Transform_Crypt_MD5->process() with a file using a secret phrase
     */
    public function testEncrypt_File_Secret()
    {
        $file = $this->getMock('Q\Fs_File', array('__toString', 'getContents'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));       
        $file->expects($this->once())->method('getContents')->will($this->returnValue("a test string"));
        
        $this->assertEquals(md5("a test string" . "s3cret"), Transform::encrypt('md5:secret=s3cret')->process($file));
    }

}
