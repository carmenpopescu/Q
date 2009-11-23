<?php
use Q\Transform_Crypt_MCrypt, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Crypt/MCrypt.php';
require_once 'Q/Fs/File.php';


/**
 * Transform_Crypt_MCrypt test case.
 */
class Transform_Crypt_MCryptTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Transform_Crypt_Serialize_MCrypt
	 */
	private $Crypt_MCrypt;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		if (!extension_loaded('mcrypt')) $this->markTestSkipped("mcrypt extension is not available");
		
		parent::setUp();
		$this->Crypt_MCrypt = new Transform_Crypt_MCrypt(array('method'=>'blowfish', 'secret'=>'s3cret'));
		
		$this->file = sys_get_temp_dir() . '/q-crypt_test-' . md5(uniqid());
		
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Crypt_MCrypt = null;
		if (file_exists($this->file)) unlink($this->file);
		parent::tearDown();
	}
	
	/**
	 * Tests Crypt_MCrypt->process() with blowfish method
	 */
	public function testEncrypt()
	{
		$this->assertEquals(mcrypt_encrypt('blowfish', 's3cret', "a test string", MCRYPT_MODE_ECB), $this->Crypt_MCrypt->process("a test string"));
	}
	
	/**
	 * Tests Crypt_MCrypt->process() with DES method
	 */
	public function testEncrypt_des()
	{
	    $this->Crypt_MCrypt->method = 'des';
		$this->assertEquals(mcrypt_encrypt('des', 's3cret', "a test string", MCRYPT_MODE_ECB), $this->Crypt_MCrypt->process("a test string"));
	}
	
	/**
	 * Tests Crypt_MCrypt->process() with a file
	 */
	public function testEncrypt_File()
	{
	    $file = $this->getMock('Q\Fs_File', array('__toString', 'getContents'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->file));       
	    $file->expects($this->once())->method('getContents')->will($this->returnValue("a test string"));
		
		$this->assertEquals(mcrypt_encrypt('blowfish', 's3cret', "a test string", MCRYPT_MODE_ECB), $this->Crypt_MCrypt->process($file));
	}

    /**
     * Tests Crypt_MCrypt->process() with a chain
     */
    public function testEncrypt_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('process'));
        $mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue("a test string"));
        
        $this->Crypt_MCrypt->chainInput($mock);
        $contents = $this->Crypt_MCrypt->process('test');

        $this->assertType('Q\Transform_Crypt_MCrypt', $this->Crypt_MCrypt);
        $this->assertEquals(mcrypt_encrypt('blowfish', 's3cret', "a test string", MCRYPT_MODE_ECB), $contents);
    }
    
    /**
     * Tests Transform_Crypt_MCrypt->output()
     */
    public function testOutput() 
    {
        ob_start();
        try{
            $this->Crypt_MCrypt->output("a test string");
        } catch (Expresion $e) {
            ob_end_clean();
            throw $e;
        }
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_Crypt_MCrypt', $this->Crypt_MCrypt);
        $this->assertEquals(mcrypt_encrypt('blowfish', 's3cret', "a test string", MCRYPT_MODE_ECB), $contents);
    }
    
    /**
     * Tests Transform_Crypt_MCrypt->save()
     */
    public function testSave() 
    {
        $this->Crypt_MCrypt->save($this->file, "a test string");
        
        $this->assertType('Q\Transform_Crypt_MCrypt', $this->Crypt_MCrypt);
        $this->assertEquals(mcrypt_encrypt('blowfish', 's3cret', "a test string", MCRYPT_MODE_ECB), file_get_contents($this->file));
    }    

    /**
     * Tests Crypt_MCrypt->getReverse()
     */
	public function testGetReverse()
    {
        $reverse = $this->Crypt_MCrypt->getReverse();
        $this->assertType('Q\Transform_Decrypt_MCrypt', $reverse);
    }

    /**
     * Tests Crypt_MCrypt->getReverse() with a chain
     */   
    public function testGetReverse_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Decrypt_MCrypt'))->will($this->returnValue('reverse of mock transformer'));
        
        $this->Crypt_MCrypt->chainInput($mock);
        
        $this->assertEquals('reverse of mock transformer', $this->Crypt_MCrypt->getReverse());
    }

    /**
     * Tests Transform_Crypt_MCrypt->getReverse() with a chain
     */
    public function testGetReverse_ChainDouble() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Decrypt_MCrypt'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform1 = new Transform_Crypt_MCrypt();
        $transform2 = new Transform_Crypt_MCrypt();
        
        $transform2->chainInput($mock);
        $transform1->chainInput($transform2);
        
        $this->assertEquals('reverse of mock transformer', $transform1->getReverse());
    }

    /**
     * Tests Transform_Crypt_MCrypt->process() -null method
     */
    public function testProcessException_EmptyMethod() 
    {
        $this->setExpectedException('Exception', 'Unable to encrypt: Algoritm not specified.');
        $transform = new Transform_Crypt_MCrypt(array('method'=>null));
        $transform->process('a test string');
    }

    /**
     * Tests Transform_Crypt_MCrypt->process() - unsupported method
     */
    public function testProcessException_UnsupportedMethod() 
    {
        $method = "a_method";
        $this->setExpectedException('Exception', "Unable to encrypt: Algoritm '{$method}' is not supported.");
        $transform = new Transform_Crypt_MCrypt(array('method'=>$method));
        $transform->process('a test string');
    }
}
