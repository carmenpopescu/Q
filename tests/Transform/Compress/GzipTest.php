<?php
use Q\Transform_Compress_Gzip, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Compress/Gzip.php';
require_once 'Q/Fs/File.php';


/**
 * Transform_Compress_Gzip test case.
 */
class Transform_Compress_GzipTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Transform_Compress_Gzip
	 */
	private $Compress_Gzip;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		$this->Compress_Gzip = new Transform_Compress_Gzip();
		
		$this->file = sys_get_temp_dir() . '/q-compress-gzip_test-' . md5(uniqid());
		
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Compress_Gzip = null;
		if (file_exists($this->file)) unlink($this->file);
		parent::tearDown();
	}
	
	/**
	 * Tests Compress_Gzip->process() with blowfish method
	 */
	public function testCompress()
	{
		$this->assertEquals(gzcompress("a test string"), $this->Compress_Gzip->process("a test string"));
	}
	
	/**
	 * Tests Compress_Gzip->process() with deflate method
	 */	
	public function testCompress_deflate()
	{
	    $this->Compress_Gzip->mode = FORCE_DEFLATE;
		$this->assertEquals(gzdeflate("a test string"), $this->Compress_Gzip->process("a test string"));
	}
	
    /**
     * Tests Compress_Gzip->process() with encode method and mode FORCE_GZIP
     */ 
    public function testCompress_encode_Gzip()
    {
        $this->Compress_Gzip->headers = true;
        $this->assertEquals(gzencode("a test string"), $this->Compress_Gzip->process("a test string"));
    }
	
    /**
     * Tests Compress_Gzip->process() with encode method and mode FORCE_DEFLATE
     */ 
    public function testCompress_encode_Deflate()
    {
        $this->Compress_Gzip->headers = true;
        $this->Compress_Gzip->mode = FORCE_DEFLATE;
        $this->assertEquals(gzencode("a test string", -1, FORCE_DEFLATE), $this->Compress_Gzip->process("a test string"));
    }
    
    /**
	 * Tests Compress_Gzip->process() with a file
	 */
	public function testCompress_File()
	{
	    $file = $this->getMock('Q\Fs_File', array('__toString', 'getContents'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->file));       
	    $file->expects($this->once())->method('getContents')->will($this->returnValue("a test string"));
		
		$this->assertEquals(gzcompress("a test string"), $this->Compress_Gzip->process($file));
	}

    /**
     * Tests Compress_Gzip->process() with a chain
     */
	public function testCompress_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('process'));
        $mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue("a test string"));
        
        $this->Compress_Gzip->chainInput($mock);
        $contents = $this->Compress_Gzip->process('test');

        $this->assertType('Q\Transform_Compress_Gzip', $this->Compress_Gzip);
        $this->assertEquals(gzcompress("a test string"), $contents);
    }
        
    /**
     * Tests Transform_Compress_Gzip->output()
     */
	public function testOutput() 
    {
        ob_start();
        try{
            $this->Compress_Gzip->output("a test string");
        } catch (Expresion $e) {
            ob_end_clean();
            throw $e;
        }
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_Compress_Gzip', $this->Compress_Gzip);
        $this->assertEquals(gzcompress("a test string"), $contents);
    }
    
    /**
     * Tests Transform_Compress_Gzip->save()
     */
	public function testSave() 
    {
        $this->Compress_Gzip->save($this->file, "a test string");
        
        $this->assertType('Q\Transform_Compress_Gzip', $this->Compress_Gzip);
        $this->assertEquals(gzcompress("a test string"), file_get_contents($this->file));
    }    

    /**
     * Tests Compress_Gzip->getReverse()
     */
	public function testGetReverse()
    {
        $reverse = $this->Compress_Gzip->getReverse();
        $this->assertType('Q\Transform_Decompress_Gzip', $reverse);
        $this->assertEquals(FORCE_UNZIP, $reverse->mode);
        $this->assertSame(false, $reverse->headers);
    }

    /**
     * Tests Compress_Gzip->getReverse() - use reverse method
     */
    public function testGetReverse_useReverseMethod()
    {
        $this->Compress_Gzip->mode = FORCE_DEFLATE;
        $reverse = $this->Compress_Gzip->getReverse();
        $this->assertType('Q\Transform_Decompress_Gzip', $reverse);
        $this->assertEquals(FORCE_INFLATE, $reverse->mode);
    }
    
    /**
     * Tests Compress_Gzip->getReverse() with a chain
     */   	
    public function testGetReverse_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Decompress_Gzip'))->will($this->returnValue('reverse of mock transformer'));
        
        $this->Compress_Gzip->chainInput($mock);
        
        $this->assertEquals('reverse of mock transformer', $this->Compress_Gzip->getReverse());
    }
    
    /**
     * Tests Transform_Compress_Gzip->getReverse() with a chain
     */
	public function testGetReverse_ChainDouble() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Decompress_Gzip'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform1 = new Transform_Compress_Gzip();
        $transform2 = new Transform_Compress_Gzip();
        
        $transform2->chainInput($mock);
        $transform1->chainInput($transform2);
        
        $this->assertEquals('reverse of mock transformer', $transform1->getReverse());
    }

    /**
     * Tests Transform_Compress_Gzip->process() - unsupported mode
     */
	public function testProcessException_UnsupportedMode() 
    {
        $mode = "a_mode";
        $this->setExpectedException('Exception', "Unable to compress data : Unknown encoding mode '{$mode}'.");
        $this->Compress_Gzip->mode = $mode;
        $this->Compress_Gzip->process('a test string');
    }

    /**
     * Tests Transform_Compress_Gzip->process() - unsupported level
     */
    public function testProcessException_UnsupportedLevel() 
    {
        $this->setExpectedException('Exception', "Unable to compress data : Unknown encoding level '13'.");
        $this->Compress_Gzip->level = 13;
        $this->Compress_Gzip->process('a test string');
    }
}
