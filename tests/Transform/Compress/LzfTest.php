<?php
use Q\Transform_Compress_Lzf, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Compress/Lzf.php';
require_once 'Q/Fs/File.php';


/**
 * Transform_Compress_Lzf test case.
 */
class Transform_Compress_LzfTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Transform_Compress_Lzf
	 */
	private $Compress_Lzf;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		$this->Compress_Lzf = new Transform_Compress_Lzf();
		
		$this->file = sys_get_temp_dir() . '/q-compress-gzip_test-' . md5(uniqid());
		
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Compress_Lzf = null;
		if (file_exists($this->file)) unlink($this->file);
		parent::tearDown();
	}
	
	/**
	 * Tests Compress_Lzf->process() with blowfish method
	 */
	public function testCompress()
	{
		$this->assertEquals(lzf_compress("a test string"), $this->Compress_Lzf->process("a test string"));
	}
	
	/**
	 * Tests Compress_Lzf->process() with a file
	 */
	public function testCompress_File()
	{
	    $file = $this->getMock('Q\Fs_File', array('__toString', 'getContents'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->file));       
	    $file->expects($this->once())->method('getContents')->will($this->returnValue("a test string"));
		
		$this->assertEquals(lzf_compress("a test string"), $this->Compress_Lzf->process($file));
	}

    /**
     * Tests Compress_Lzf->process() with a chain
     */
	public function testCompress_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('process'));
        $mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue("a test string"));
        
        $this->Compress_Lzf->chainInput($mock);
        $contents = $this->Compress_Lzf->process('test');

        $this->assertType('Q\Transform_Compress_Lzf', $this->Compress_Lzf);
        $this->assertEquals(lzf_compress("a test string"), $contents);
    }
        
    /**
     * Tests Transform_Compress_Lzf->output()
     */
	public function testOutput() 
    {
        ob_start();
        try{
            $this->Compress_Lzf->output("a test string");
        } catch (Expresion $e) {
            ob_end_clean();
            throw $e;
        }
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_Compress_Lzf', $this->Compress_Lzf);
        $this->assertEquals(lzf_compress("a test string"), $contents);
    }
    
    /**
     * Tests Transform_Compress_Lzf->save()
     */
	public function testSave() 
    {
        $this->Compress_Lzf->save($this->file, "a test string");
        
        $this->assertType('Q\Transform_Compress_Lzf', $this->Compress_Lzf);
        $this->assertEquals(lzf_compress("a test string"), file_get_contents($this->file));
    }    

    /**
     * Tests Compress_Lzf->getReverse()
     */
	public function testGetReverse()
    {
        $reverse = $this->Compress_Lzf->getReverse();
        $this->assertType('Q\Transform_Decompress_Lzf', $reverse);
    }

    /**
     * Tests Compress_Lzf->getReverse() with a chain
     */   	
    public function testGetReverse_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Decompress_Lzf'))->will($this->returnValue('reverse of mock transformer'));
        
        $this->Compress_Lzf->chainInput($mock);
        
        $this->assertEquals('reverse of mock transformer', $this->Compress_Lzf->getReverse());
    }
    
    /**
     * Tests Transform_Compress_Lzf->getReverse() with a chain
     */
	public function testGetReverse_ChainDouble() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Decompress_Lzf'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform1 = new Transform_Compress_Lzf();
        $transform2 = new Transform_Compress_Lzf();
        
        $transform2->chainInput($mock);
        $transform1->chainInput($transform2);
        
        $this->assertEquals('reverse of mock transformer', $transform1->getReverse());
    }

    /**
     * Tests Transform_Compress_Lzf->process() - unsupported level
     */
    public function testProcessException_UnsupportedLevel() 
    {
        $this->setExpectedException('Exception', "Unable to compress data : Unknown encoding level '0'.");
        $this->Compress_Lzf->level = 0;
        $this->Compress_Lzf->process('a test string');
    }

    /**
     * Tests Transform_Compress_Lzf->process() - unsupported workfactor
     */
    public function testProcessException_UnsupportedWorkfactor() 
    {   
        $this->setExpectedException('Exception', "Unable to compress data : Unknown workfactor '-5'.");
        $this->Compress_Lzf->workfactor = -5;
        $this->Compress_Lzf->process('a test string');
    }
}
