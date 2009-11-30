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
	    $compressed = gzcompress("a test string", 9);
		$this->assertEquals(gzuncompress($compressed), $this->Decompress_Gzip->process($compressed));
	}
	
	/**
	 * Tests Decompress_Gzip->process() with inflate method
	 */	
	public function testDecompress_inflate()
	{
        $compressed = gzdeflate("a test string", 9);
	    $this->Decompress_Gzip->method = 'inflate';
		$this->assertEquals(gzinflate($compressed), $this->Decompress_Gzip->process($compressed));
	}
	
    /**
     * Tests Decompress_Gzip->process() with decode method
     */ 	
    public function testDecompress_decode()
    {
        $this->markTestSkipped('Test this after install php 6 because gzdecode was introduced with PHP 6.0.0');
        $compressed = gzencode("a test string", 9, FORCE_GZIP);
        $this->Decompress_Gzip->method = 'decode';
        $this->assertEquals(gzdecode($compressed), $this->Decompress_Gzip->process($compressed));
    }

    /**
     * Tests Decompress_Gzip->process() with specified length
     */ 
    public function testDecompress_withLength()
    {
        $compressed = gzcompress("a test string", 9);
        $this->Decompress_Gzip->length = 15;
        $this->assertEquals(gzuncompress($compressed, 15), $this->Decompress_Gzip->process($compressed));
    }
    
    /**
	 * Tests Decompress_Gzip->process() with a file
	 */
	public function testDecompress_File()
	{
        $compressed = gzcompress("a test string", 9);
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
        $compressed = gzcompress("a test string", 9);
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
        $compressed = gzcompress("a test string", 9);
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
        $compressed = gzcompress("a test string", 9);
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
     * Tests Transform_Decompress_Gzip->process() - unsupported method
     */
	public function testProcessException_UnsupportedMethod() 
    {
        $compressed = gzcompress("a test string", 9);
        $method = "a_method";
        $this->setExpectedException('Exception', "Unable to uncompress data : Unknown uncompress method '{$method}'.");
        $this->Decompress_Gzip->method = $method;
        $this->Decompress_Gzip->process($compressed);
    }
}
