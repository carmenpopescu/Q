<?php
use Q\Transform_Decrypt_MCrypt, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Decrypt/MCrypt.php';
require_once 'Q/Fs/File.php';
/**
 * Decrypt_MCrypt test case.
 */
class Transform_Decrypt_MCryptTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Transform_Decrypt_MCrypt
	 */
	private $Decrypt_MCrypt;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		if (!extension_loaded('mcrypt')) $this->markTestSkipped("mcrypt extension is not available");
		$this->Decrypt_MCrypt = new Transform_Decrypt_MCrypt(array('method'=>'blowfish', 'secret'=>'s3cret'));
        $this->file = sys_get_temp_dir() . '/q-crypt_test-' . md5(uniqid());
		
        parent::setUp();
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Decrypt_MCrypt = null;
        if (file_exists($this->file)) unlink($this->file);
		parent::tearDown();
	}
	/**
	 * Tests Decrypt_MCrypt->process() with blowfish method
	 */
	public function testDecrypt()
	{
	    $encrypted = mcrypt_encrypt('blowfish', 's3cret', "a test string", MCRYPT_MODE_ECB);
	    $this->assertEquals("a test string", $this->Decrypt_MCrypt->process($encrypted));
	}

	/**
	 * Tests Decrypt_MCrypt->process() with DES method
	 */
	public function testDecrypt_des()
	{
	    $this->Decrypt_MCrypt->method = 'des';
	    $encrypted = mcrypt_encrypt('des', 's3cret', "a test string", MCRYPT_MODE_ECB);
		$this->assertEquals("a test string", $this->Decrypt_MCrypt->process($encrypted));
	}	

	/**
	 * Tests Decrypt_MCrypt->process() with a file
	 */
	public function testDecrypt_File()
	{
		$encrypted = mcrypt_encrypt('blowfish', 's3cret', "a test string", MCRYPT_MODE_ECB);
		
		$file = $this->getMock('Q\Fs_File', array('__toString', 'getContents'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->file));       
		$file->expects($this->once())->method('getContents')->will($this->returnValue($encrypted));
		
		$this->assertEquals("a test string", $this->Decrypt_MCrypt->process($file));
	}
	
	/**
	 * Tests Decrypt_MCrypt->process() where process fails because of incorrect secret phrase
	 */
	public function testDecrypt_WrongSecret()
	{
		$encrypted = mcrypt_encrypt('blowfish', 'another_secret', "a test string", MCRYPT_MODE_ECB);
		$this->assertNotEquals("a test string", $this->Decrypt_MCrypt->process($encrypted));
	}

    /**
     * Tests Decrypt_MCrypt->process() with a chain
     */
    public function testDecrypt_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('process'));
        $mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue(mcrypt_encrypt('blowfish', 's3cret', "a test string", MCRYPT_MODE_ECB)));
        $this->Decrypt_MCrypt->chainInput($mock);
        $contents = $this->Decrypt_MCrypt->process('test');

        $this->assertType('Q\Transform_Decrypt_MCrypt', $this->Decrypt_MCrypt);
        $this->assertEquals("a test string", $contents);
    }
    
    /**
     * Tests Transform_Decrypt_MCrypt->output()
     */
    public function testOutput() 
    {
        $encrypted = mcrypt_encrypt('blowfish', 's3cret', "a test string", MCRYPT_MODE_ECB);
        ob_start();
        try{
            $this->Decrypt_MCrypt->output($encrypted);
        } catch (Expresion $e) {
            ob_end_clean();
            throw $e;
        }
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_Decrypt_MCrypt', $this->Decrypt_MCrypt);
        $this->assertEquals("a test string", $contents);
    }
    
    /**
     * Tests Transform_Decrypt_MCrypt->save()
     */
    public function testSave() 
    {
        $encrypted = mcrypt_encrypt('blowfish', 's3cret', "a test string", MCRYPT_MODE_ECB);
        $this->Decrypt_MCrypt->save($this->file, $encrypted);
        
        $this->assertType('Q\Transform_Decrypt_MCrypt', $this->Decrypt_MCrypt);
        $this->assertEquals("a test string", file_get_contents($this->file));
    }    

    /**
     * Tests Decrypt_MCrypt->getReverse()
     */
    public function testGetReverse()
    {
        $reverse = $this->Decrypt_MCrypt->getReverse();
        $this->assertType('Q\Transform_Crypt_MCrypt', $reverse);
    }

    /**
     * Tests Decrypt_MCrypt->getReverse() with a chain
     */   
    public function testGetReverse_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Crypt_MCrypt'))->will($this->returnValue('reverse of mock transformer'));
        
        $this->Decrypt_MCrypt->chainInput($mock);
        
        $this->assertEquals('reverse of mock transformer', $this->Decrypt_MCrypt->getReverse());
    }

    /**
     * Tests Transform_Decrypt_MCrypt->getReverse() with a chain
     */
    public function testGetReverse_ChainDouble() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Crypt_MCrypt'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform1 = new Transform_Decrypt_MCrypt();
        $transform2 = new Transform_Decrypt_MCrypt();
        
        $transform2->chainInput($mock);
        $transform1->chainInput($transform2);
        
        $this->assertEquals('reverse of mock transformer', $transform1->getReverse());
    }
    
    /**
     * Tests Transform_Decrypt_MCrypt->process() -null method
     */
    public function testProcessException_EmptyMethod() 
    {
        $this->setExpectedException('Exception', 'Unable to decrypt: Algoritm not specified.');
        $transform = new Transform_Decrypt_MCrypt(array('method'=>null));
        $transform->process(mcrypt_encrypt('blowfish', 's3cret', "a test string", MCRYPT_MODE_ECB));
    }

    /**
     * Tests Transform_Decrypt_MCrypt->process() - unsupported method
     */
    public function testProcessException_UnsupportedMethod() 
    {
        $method = "a_method";
        $this->setExpectedException('Exception', "Unable to decrypt: Algoritm '{$method}' is not supported.");
        $transform = new Transform_Decrypt_MCrypt(array('method'=>$method));
        $transform->process(mcrypt_encrypt('blowfish', 's3cret', "a test string", MCRYPT_MODE_ECB));
    }
}
