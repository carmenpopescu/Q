<?php
use Q\Transform_Decompress_Gzip, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Decompress/Gzip.php';
require_once 'Q/Fs/File.php';

/**
 * Transform_Decompress_Gzip test case.
 */
class Transform_Decompress_GzipTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Transform_Decompress_Gzip
	 */
	private $Decompress_Gzip;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		$this->Decompress_Gzip = new Transform_Decompress_Gzip();
		
		$this->file = sys_get_temp_dir() . '/q-compress-gzip_test-' . md5(uniqid());
		
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Decompress_Gzip = null;
		if (file_exists($this->file)) unlink($this->file);
		parent::tearDown();
	}
	
	/**
	 * Tests Decompress_Gzip->process() with blowfish method
	 */
	public function testDecompress()
	{
	    $compressed = gzcompress("a test string");
		$this->assertEquals(gzuncompress($compressed), $this->Decompress_Gzip->process($compressed));
	}
	
	/**
	 * Tests Decompress_Gzip->process() with inflate method
	 */	
	public function testDecompress_inflate()
	{
        $compressed = gzdeflate("a test string");
	    $this->Decompress_Gzip->mode = FORCE_INFLATE;
		$this->assertEquals(gzinflate($compressed), $this->Decompress_Gzip->process($compressed));
	}
	
    /**
     * Tests Decompress_Gzip->process() with decode method
     */ 	
    public function testDecompress_decode()
    {
        $this->markTestSkipped('Test this after installing php 6 : gzdecode was introduced with PHP 6.0.0');
        $compressed = gzencode("a test string", -1, FORCE_GZIP);
        $this->Decompress_Gzip->headers = true;
        $this->assertEquals(gzdecode($compressed), $this->Decompress_Gzip->process($compressed));
    }

    /**
     * Tests Decompress_Gzip->process() with specified length
     */ 
    public function testDecompress_withLength()
    {
        $compressed = gzcompress("a test string");
        $this->Decompress_Gzip->length = 15;
        $this->assertEquals(gzuncompress($compressed, 15), $this->Decompress_Gzip->process($compressed));
    }
    
    /**
	 * Tests Decompress_Gzip->process() with a file
	 */
	public function testDecompress_File()
	{
        $compressed = gzcompress("a test string");
	    $file = $this->getMock('Q\Fs_File', array('__toString', 'getContents'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->file));       
	    $file->expects($this->once())->method('getContents')->will($this->returnValue($compressed));
		
		$this->assertEquals(gzuncompress($compressed), $this->Decompress_Gzip->process($file));
	}

    /**
     * Tests Decompress_Gzip->process() with a chain
     */
	public function testDecompress_Chain() 
    {
        $compressed = gzcompress("a test string");
        $mock = $this->getMock('Q\Transform', array('process'));
        $mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue($compressed));
        
        $this->Decompress_Gzip->chainInput($mock);
        $contents = $this->Decompress_Gzip->process('test');

        $this->assertType('Q\Transform_Decompress_Gzip', $this->Decompress_Gzip);
        $this->assertEquals(gzuncompress($compressed), $contents);
    }
        
    /**
     * Tests Transform_Decompress_Gzip->output()
     */
	public function testOutput() 
    {
        $compressed = gzcompress("a test string");
        ob_start();
        try{
            $this->Decompress_Gzip->output($compressed);
        } catch (Expresion $e) {
            ob_end_clean();
            throw $e;
        }
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_Decompress_Gzip', $this->Decompress_Gzip);
        $this->assertEquals(gzuncompress($compressed), $contents);
    }
    
    /**
     * Tests Transform_Decompress_Gzip->save()
     */
	public function testSave() 
    {
        $compressed = gzcompress("a test string");
        $this->Decompress_Gzip->save($this->file, $compressed);
        
        $this->assertType('Q\Transform_Decompress_Gzip', $this->Decompress_Gzip);
        $this->assertEquals(gzuncompress($compressed), file_get_contents($this->file));
    }    

    /**
     * Tests Decompress_Gzip->getReverse()
     */
	public function testGetReverse()
    {
        $reverse = $this->Decompress_Gzip->getReverse();
        $this->assertType('Q\Transform_Compress_Gzip', $reverse);
        $this->assertEquals(FORCE_GZIP, $reverse->mode);
    }

    /**
     * Tests Decompress_Gzip->getReverse() use reverse mode
     */
    public function testGetReverse_useReverseMode()
    {
        $this->Decompress_Gzip->mode = FORCE_INFLATE;
        $reverse = $this->Decompress_Gzip->getReverse();
        $this->assertType('Q\Transform_Compress_Gzip', $reverse);
        $this->assertEquals(FORCE_DEFLATE, $reverse->mode);
    }
    
    /**
     * Tests Decompress_Gzip->getReverse() with a chain
     */   	
    public function testGetReverse_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Compress_Gzip'))->will($this->returnValue('reverse of mock transformer'));
        
        $this->Decompress_Gzip->chainInput($mock);
        
        $this->assertEquals('reverse of mock transformer', $this->Decompress_Gzip->getReverse());
    }
    
    /**
     * Tests Transform_Decompress_Gzip->getReverse() with a chain
     */
	public function testGetReverse_ChainDouble() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Compress_Gzip'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform1 = new Transform_Decompress_Gzip();
        $transform2 = new Transform_Decompress_Gzip();
        
        $transform2->chainInput($mock);
        $transform1->chainInput($transform2);
        
        $this->assertEquals('reverse of mock transformer', $transform1->getReverse());
    }

    /**
     * Tests Transform_Decompress_Gzip->process() - unsupported mode
     */
	public function testProcessException_UnsupportedMode() 
    {
        $compressed = gzcompress("a test string");
        $mode = "a_mode";
        $this->setExpectedException('Exception', "Unable to uncompress data : Unknown decoding mode '{$mode}'.");
        $this->Decompress_Gzip->mode = $mode;
        $this->Decompress_Gzip->process($compressed);
    }
}
