<?php
use Q\Transform_Compress_Bzip, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Compress/Bzip.php';
require_once 'Q/Fs/File.php';


/**
 * Transform_Compress_Bzip test case.
 */
class Transform_Compress_BzipTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Transform_Compress_Bzip
	 */
	private $Compress_Bzip;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		$this->Compress_Bzip = new Transform_Compress_Bzip();
		
		$this->file = sys_get_temp_dir() . '/q-compress-gzip_test-' . md5(uniqid());
		
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Compress_Bzip = null;
		if (file_exists($this->file)) unlink($this->file);
		parent::tearDown();
	}
	
	/**
	 * Tests Compress_Bzip->process() with blowfish method
	 */
	public function testCompress()
	{
		$this->assertEquals(bzcompress("a test string"), $this->Compress_Bzip->process("a test string"));
	}
	
    /**
     * Tests Compress_Bzip->process() - use workfactor
     */ 
    public function testCompress_useWorkfactor()
    {
        $this->Compress_Bzip->workfactor = 100;
        $this->assertEquals(bzcompress("a test string", 4, 100), $this->Compress_Bzip->process("a test string"));
    }
	
	/**
	 * Tests Compress_Bzip->process() with a file
	 */
	public function testCompress_File()
	{
	    $file = $this->getMock('Q\Fs_File', array('__toString', 'getContents'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->file));       
	    $file->expects($this->once())->method('getContents')->will($this->returnValue("a test string"));
		
		$this->assertEquals(bzcompress("a test string"), $this->Compress_Bzip->process($file));
	}

    /**
     * Tests Compress_Bzip->process() with a chain
     */
	public function testCompress_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('process'));
        $mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue("a test string"));
        
        $this->Compress_Bzip->chainInput($mock);
        $contents = $this->Compress_Bzip->process('test');

        $this->assertType('Q\Transform_Compress_Bzip', $this->Compress_Bzip);
        $this->assertEquals(bzcompress("a test string"), $contents);
    }
        
    /**
     * Tests Transform_Compress_Bzip->output()
     */
	public function testOutput() 
    {
        ob_start();
        try{
            $this->Compress_Bzip->output("a test string");
        } catch (Expresion $e) {
            ob_end_clean();
            throw $e;
        }
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_Compress_Bzip', $this->Compress_Bzip);
        $this->assertEquals(bzcompress("a test string"), $contents);
    }
    
    /**
     * Tests Transform_Compress_Bzip->save()
     */
	public function testSave() 
    {
        $this->Compress_Bzip->save($this->file, "a test string");
        
        $this->assertType('Q\Transform_Compress_Bzip', $this->Compress_Bzip);
        $this->assertEquals(bzcompress("a test string"), file_get_contents($this->file));
    }    

    /**
     * Tests Compress_Bzip->getReverse()
     */
	public function testGetReverse()
    {
        $reverse = $this->Compress_Bzip->getReverse();
        $this->assertType('Q\Transform_Decompress_Bzip', $reverse);
    }

    /**
     * Tests Compress_Bzip->getReverse() with a chain
     */   	
    public function testGetReverse_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Decompress_Bzip'))->will($this->returnValue('reverse of mock transformer'));
        
        $this->Compress_Bzip->chainInput($mock);
        
        $this->assertEquals('reverse of mock transformer', $this->Compress_Bzip->getReverse());
    }
    
    /**
     * Tests Transform_Compress_Bzip->getReverse() with a chain
     */
	public function testGetReverse_ChainDouble() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Decompress_Bzip'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform1 = new Transform_Compress_Bzip();
        $transform2 = new Transform_Compress_Bzip();
        
        $transform2->chainInput($mock);
        $transform1->chainInput($transform2);
        
        $this->assertEquals('reverse of mock transformer', $transform1->getReverse());
    }

    /**
     * Tests Transform_Compress_Bzip->process() - unsupported level
     */
    public function testProcessException_UnsupportedLevel() 
    {
        $this->setExpectedException('Exception', "Unable to compress data : Unknown encoding level '0'.");
        $this->Compress_Bzip->level = 0;
        $this->Compress_Bzip->process('a test string');
    }

    /**
     * Tests Transform_Compress_Bzip->process() - unsupported workfactor
     */
    public function testProcessException_UnsupportedWorkfactor() 
    {   
        $this->setExpectedException('Exception', "Unable to compress data : Unknown workfactor '-5'.");
        $this->Compress_Bzip->workfactor = -5;
        $this->Compress_Bzip->process('a test string');
    }
}
