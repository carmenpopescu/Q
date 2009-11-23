<?php
use Q\Transform_Crypt_System, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Crypt/System.php';
require_once 'Q/Fs/File.php';

/**
 * Transform_Crypt_System test case.
 */
class Transform_Crypt_SystemTest extends PHPUnit_Framework_TestCase
{
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $this->file = sys_get_temp_dir() . '/q-crypt_test-' . md5(uniqid());
        parent::setUp();
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        if (file_exists($this->file)) unlink($this->file);
        parent::tearDown();
    }    
    
	/**
	 * Tests Crypt_System->process()
	 */
	public function testEncrypt()
	{
	    $crypt = new Transform_Crypt_System();
	    
	    $hash = $crypt->process("a test string");
		$this->assertEquals(crypt("a test string", $hash), $hash);
	}

	/**
	 * Tests Crypt_System->process() using standard DES-based encryption
	 */
	public function testEncrypt_std_des()
	{
	    if (!CRYPT_STD_DES) $this->markTestSkipped("Standard DES-based encryption with crypt() not available.");
	    
	    $crypt = new Transform_Crypt_System('std_des');
	    
	    $this->assertRegExp('/^.{13}$/', $crypt->process("a test string"));
	    $this->assertEquals(crypt("a test string", '12'), $crypt->process("a test string", '12'));
	    
	    $hash = $crypt->process("a test string");
        $this->assertEquals($hash, $crypt->process("a test string", $hash));		
    }

    /**
	 * Tests Crypt_System->process() using extended DES-based encryption
	 */
	public function testEncrypt_ext_des()
	{
	    if (!CRYPT_EXT_DES) $this->markTestSkipped("Extended DES-based encryption with crypt() not available.");
	    
	    $crypt = new Transform_Crypt_System('ext_des');
	    
	    $this->assertRegExp('/^.{20}$/', $crypt->process("a test string"));
	    $this->assertEquals(crypt("a test string", '_23456789'), $crypt->process("a test string", '_23456789'));
	    
	    $hash = $crypt->process("a test string");
        $this->assertEquals($hash, $crypt->process("a test string", $hash));		
    }

	/**
	 * Tests Crypt_System->process() using MD5 encryption
	 */
	public function testEncrypt_md5()
	{
	    if (!CRYPT_MD5) $this->markTestSkipped("MD5-based encryption with crypt() not available.");
	    
	    $crypt = new Transform_Crypt_System('md5');
	    
	    $this->assertRegExp('/^\$1\$.{31}$/', $crypt->process("a test string"));
	    $this->assertEquals(crypt("a test string", '$1$12345678'), $crypt->process("a test string", '$1$12345678'));
	    
	    $hash = $crypt->process("a test string");
	    $this->assertEquals($hash, $crypt->process("a test string", $hash));		
    }

	/**
	 * Tests Crypt_System->process() using Blowfish encryption
	 */
	public function testEncrypt_blowfish()
	{
	    if (!CRYPT_BLOWFISH) $this->markTestSkipped("Blowfish-based encryption with crypt() not available.");
	    
	    $crypt = new Transform_Crypt_System('blowfish');
	    
	    $this->assertRegExp('/^\$2a\$07\$.{53}$/', $crypt->process("a test string"));
	    $this->assertEquals(crypt("a test string", '$2a$07$1234567890123456789012'), $crypt->process("a test string", '$2a$07$1234567890123456789012'));
	    
	    $hash = $crypt->process("a test string");
	    $this->assertEquals($hash, $crypt->process("a test string", $hash), "From Hash");		
    }

	/**
	 * Tests Crypt_System->process() with a file
	 */
	public function testEncrypt_File()
	{
		$crypt = new Transform_Crypt_System();
		
		$file = $this->getMock('Q\Fs_File', array('__toString', 'getContents'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->file));       
		$file->expects($this->once())->method('getContents')->will($this->returnValue("a test string"));
		
	    $hash = $crypt->process($file);
		$this->assertEquals(crypt("a test string", $hash), $hash);
	}

    /**
     * Tests Transform_Crypt_System->process() with a chain
     */
    public function testEncrypt_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('process'));
        $mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue('a test string'));
        
        $crypt = new Transform_Crypt_System();
        $crypt->chainInput($mock);
        $hash = $crypt->process('test');

        $this->assertType('Q\Transform_Crypt_System', $crypt);
        $this->assertEquals(crypt("a test string", $hash), $hash);
    }
    
    /**
     * Tests Transform_Crypt_System->output()
     */
    public function testOutput() 
    {
        $crypt = new Transform_Crypt_System();
        
        ob_start();
        try{
            $crypt->output("a test string");
        } catch (Expresion $e) {
            ob_end_clean();
            throw $e;
        }
        $hash = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_Crypt_System', $crypt);
        $this->assertEquals(crypt("a test string", $hash), $hash);
    }
    
    /**
     * Tests Transform_Crypt_System->save()
     */
    public function testSave() 
    {
        $crypt = new Transform_Crypt_System();
        $crypt->save($this->file, "a test string");
        $hash = file_get_contents($this->file);
        $this->assertType('Q\Transform_Crypt_System', $crypt);
        $this->assertEquals(crypt("a test string", $hash), $hash);
    }

    /**
     * Tests Transform_Crypt_System->getReverse()
     */
    public function testGetReverse() 
    {
        $crypt = new Transform_Crypt_System();
        $this->setExpectedException('Q\Transform_Exception', 'There is no reverse transformation defined.');
        $crypt->getReverse();
    }

    /**
     * Tests Transform_Crypt_System->process() -null method
     */
    public function testProcessException_WrongMethod() 
    {
        $this->setExpectedException('Exception', "Unable to encrypt value: Unknown crypt method 'a_method'");
        $transform = new Transform_Crypt_System(array('method'=>'a_method'));
        $transform->process('a test string');
    }
}
