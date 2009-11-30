<?php
use Q\Transform_Decompress_Bzip, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Compress/Bzip.php';
require_once 'Q/Fs/File.php';


/**
 * Transform_Decompress_Bzip test case.
 */
class Transform_Decompress_BzipTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Transform_Decompress_Bzip
	 */
	private $Decompress_Bzip;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		$this->Decompress_Bzip = new Transform_Decompress_Bzip();
		
		$this->file = sys_get_temp_dir() . '/q-compress-gzip_test-' . md5(uniqid());
		
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Decompress_Bzip = null;
		if (file_exists($this->file)) unlink($this->file);
		parent::tearDown();
	}
	
	/**
	 * Tests Decompress_Bzip->process() with blowfish method
	 */
	public function testDecompress()
	{
	    $compressed = bzcompress("a test string");
		$this->assertEquals(bzdecompress($compressed), $this->Decompress_Bzip->process($compressed));
	}
	
    /**
     * Tests Decompress_Bzip->process() - use small
     */ 
    public function testCompress_useSmall()
    {
        $compressed = bzcompress("a test string");
        $this->Decompress_Bzip->small = true;
        $this->assertEquals(bzdecompress($compressed, true), $this->Decompress_Bzip->process($compressed));
    }
	
	/**
	 * Tests Decompress_Bzip->process() with a file
	 */
	public function testCompress_File()
	{
        $compressed = bzcompress("a test string");
	    $file = $this->getMock('Q\Fs_File', array('__toString', 'getContents'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->file));       
	    $file->expects($this->once())->method('getContents')->will($this->returnValue($compressed));
		
		$this->assertEquals(bzdecompress($compressed), $this->Decompress_Bzip->process($file));
	}

    /**
     * Tests Decompress_Bzip->process() with a chain
     */
	public function testCompress_Chain() 
    {
        $compressed = bzcompress("a test string");
        $mock = $this->getMock('Q\Transform', array('process'));
        $mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue($compressed));
        
        $this->Decompress_Bzip->chainInput($mock);
        $contents = $this->Decompress_Bzip->process('test');

        $this->assertType('Q\Transform_Decompress_Bzip', $this->Decompress_Bzip);
        $this->assertEquals(bzdecompress($compressed), $contents);
    }
        
    /**
     * Tests Transform_Decompress_Bzip->output()
     */
	public function testOutput() 
    {
        $compressed = bzcompress("a test string");
        ob_start();
        try{
            $this->Decompress_Bzip->output($compressed);
        } catch (Expresion $e) {
            ob_end_clean();
            throw $e;
        }
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_Decompress_Bzip', $this->Decompress_Bzip);
        $this->assertEquals(bzdecompress($compressed), $contents);
    }
    
    /**
     * Tests Transform_Decompress_Bzip->save()
     */
	public function testSave() 
    {
        $compressed = bzcompress("a test string");
        $this->Decompress_Bzip->save($this->file, $compressed);
        
        $this->assertType('Q\Transform_Decompress_Bzip', $this->Decompress_Bzip);
        $this->assertEquals(bzdecompress($compressed), file_get_contents($this->file));
    }    

    /**
     * Tests Decompress_Bzip->getReverse()
     */
	public function testGetReverse()
    {
        $reverse = $this->Decompress_Bzip->getReverse();
        $this->assertType('Q\Transform_Compress_Bzip', $reverse);
    }

    /**
     * Tests Decompress_Bzip->getReverse() with a chain
     */   	
    public function testGetReverse_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Compress_Bzip'))->will($this->returnValue('reverse of mock transformer'));
        
        $this->Decompress_Bzip->chainInput($mock);
        
        $this->assertEquals('reverse of mock transformer', $this->Decompress_Bzip->getReverse());
    }
    
    /**
     * Tests Transform_Decompress_Bzip->getReverse() with a chain
     */
	public function testGetReverse_ChainDouble() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Compress_Bzip'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform1 = new Transform_Decompress_Bzip();
        $transform2 = new Transform_Decompress_Bzip();
        
        $transform2->chainInput($mock);
        $transform1->chainInput($transform2);
        
        $this->assertEquals('reverse of mock transformer', $transform1->getReverse());
    }
}
