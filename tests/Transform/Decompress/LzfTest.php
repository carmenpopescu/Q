<?php
use Q\Transform_Decompress_Lzf, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Compress/Lzf.php';
require_once 'Q/Fs/File.php';


/**
 * Transform_Decompress_Lzf test case.
 */
class Transform_Decompress_LzfTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Transform_Decompress_Lzf
	 */
	private $Decompress_Lzf;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		$this->Decompress_Lzf = new Transform_Decompress_Lzf();
		
		$this->file = sys_get_temp_dir() . '/q-compress-gzip_test-' . md5(uniqid());
		
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Decompress_Lzf = null;
		if (file_exists($this->file)) unlink($this->file);
		parent::tearDown();
	}
	
	/**
	 * Tests Decompress_Lzf->process() with blowfish method
	 */
	public function testDecompress()
	{
	    $compressed = lzf_compress("a test string");
		$this->assertEquals(lzf_decompress($compressed), $this->Decompress_Lzf->process($compressed));
	}
	
	/**
	 * Tests Decompress_Lzf->process() with a file
	 */
	public function testCompress_File()
	{
        $compressed = lzf_compress("a test string");
	    $file = $this->getMock('Q\Fs_File', array('__toString', 'getContents'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->file));       
	    $file->expects($this->once())->method('getContents')->will($this->returnValue($compressed));
		
		$this->assertEquals(lzf_decompress($compressed), $this->Decompress_Lzf->process($file));
	}

    /**
     * Tests Decompress_Lzf->process() with a chain
     */
	public function testCompress_Chain() 
    {
        $compressed = lzf_compress("a test string");
        $mock = $this->getMock('Q\Transform', array('process'));
        $mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue($compressed));
        
        $this->Decompress_Lzf->chainInput($mock);
        $contents = $this->Decompress_Lzf->process('test');

        $this->assertType('Q\Transform_Decompress_Lzf', $this->Decompress_Lzf);
        $this->assertEquals(lzf_decompress($compressed), $contents);
    }
        
    /**
     * Tests Transform_Decompress_Lzf->output()
     */
	public function testOutput() 
    {
        $compressed = lzf_compress("a test string");
        ob_start();
        try{
            $this->Decompress_Lzf->output($compressed);
        } catch (Expresion $e) {
            ob_end_clean();
            throw $e;
        }
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_Decompress_Lzf', $this->Decompress_Lzf);
        $this->assertEquals(lzf_decompress($compressed), $contents);
    }
    
    /**
     * Tests Transform_Decompress_Lzf->save()
     */
	public function testSave() 
    {
        $compressed = lzf_compress("a test string");
        $this->Decompress_Lzf->save($this->file, $compressed);
        
        $this->assertType('Q\Transform_Decompress_Lzf', $this->Decompress_Lzf);
        $this->assertEquals(lzf_decompress($compressed), file_get_contents($this->file));
    }    

    /**
     * Tests Decompress_Lzf->getReverse()
     */
	public function testGetReverse()
    {
        $reverse = $this->Decompress_Lzf->getReverse();
        $this->assertType('Q\Transform_Compress_Lzf', $reverse);
    }

    /**
     * Tests Decompress_Lzf->getReverse() with a chain
     */   	
    public function testGetReverse_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Compress_Lzf'))->will($this->returnValue('reverse of mock transformer'));
        
        $this->Decompress_Lzf->chainInput($mock);
        
        $this->assertEquals('reverse of mock transformer', $this->Decompress_Lzf->getReverse());
    }
    
    /**
     * Tests Transform_Decompress_Lzf->getReverse() with a chain
     */
	public function testGetReverse_ChainDouble() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Compress_Lzf'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform1 = new Transform_Decompress_Lzf();
        $transform2 = new Transform_Decompress_Lzf();
        
        $transform2->chainInput($mock);
        $transform1->chainInput($transform2);
        
        $this->assertEquals('reverse of mock transformer', $transform1->getReverse());
    }
}
