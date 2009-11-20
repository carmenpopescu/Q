<?php
use Q\Transform_Crypt_Hash, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Crypt/Hash.php';
require_once 'Q/Fs/File.php';

/**
 * Transform_Crypt_Hash test case.
 */
class Transform_Crypt_HashTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Crypt_Hash
	 */
	private $Crypt_Hash;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		$this->Crypt_Hash = new Transform_Crypt_Hash('md5');
		
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-crypt');
        if (!file_put_contents($this->tmpfile, "a test string")) $this->markTestSkipped('Unable to start test : couldn\'t create file.');
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Crypt_Hash = null;
        if (isset($this->tmpfile) && file_exists($this->tmpfile)) unlink($this->tmpfile);
		parent::tearDown();
	}
	
	/**
	 * Tests Crypt_Hash->process() with whirlpool algoritm
	 */
	public function testEncrypt_Whirlpool()
	{
		$this->Crypt_Hash->method = 'whirlpool';
		$this->assertEquals(hash('whirlpool', "a test string"), $this->Crypt_Hash->process("a test string"));
	}

	/**
	 * Tests Crypt_Hash->process()
	 */
	public function testEncrypt_Salt()
	{
	    $this->Crypt_Hash->useSalt = true;
	    
		$hash = $this->Crypt_Hash->process("a test string");
		$this->assertRegExp('/^\w{6}\$\w{32}$/', $hash);
		
		$this->assertNotEquals(preg_replace('/\w{32}$/', '', $hash) . hash('md5', "a test string"), $hash);
        $this->assertEquals($hash, $this->Crypt_Hash->process("a test string", $hash));		
	}
	
	/**
	 * Tests Crypt_DoubleHash->process() with secret phrase
	 */
	public function testEncrypt_Secret()
	{
	    $this->Crypt_Hash->secret = "s3cret";
		$this->assertEquals(hash('md5', "a test string" . "s3cret"), $this->Crypt_Hash->process("a test string"));
	}
	
	/**
	 * Tests Crypt_Hash->process() with a file
	 */
	public function testEncrypt_File()
	{		
		$file = $this->getMock('Q\Fs_File', array('__toString'), array(), '', false);
		$file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));

		$this->assertEquals(hash_file('md5', $this->tmpfile), $this->Crypt_Hash->process($file));
	}
	
	/**
	 * Tests Crypt_Hash->process() with a file using a secret phrase
	 */
	public function testEncrypt_File_Secret()
	{
	    $file = $this->getMock('Q\Fs_File', array('__toString', 'getContents'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));
		$file->expects($this->once())->method('getContents')->will($this->returnValue("a test string"));
		
		$this->Crypt_Hash->secret = "s3cret";
		$this->assertEquals(hash('md5', "a test string" . "s3cret"), $this->Crypt_Hash->process($file));
	}

    /**
     * Tests Crypt_Hash->process() with a chain
     */
    public function testEncrypt_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('process'));
        $mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue("a test string"));
        
        $this->Crypt_Hash->chainInput($mock);
        $contents = $this->Crypt_Hash->process('test');

        $this->assertType('Q\Transform_Crypt_Hash', $this->Crypt_Hash);
        $this->assertEquals(hash('md5', "a test string"), $contents);
    }

    
    /**
     * Tests Transform_Crypt_Hash->output()
     */
    public function testOutput() 
    {
        ob_start();
        try{
            $this->Crypt_Hash->output("a test string");
        } catch (Expresion $e) {
            ob_end_clean();
            throw $e;
        }
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_Crypt_Hash', $this->Crypt_Hash);
        $this->assertEquals(hash('md5', "a test string"), $contents);
    }

    /**
     * Tests Transform_Crypt_Hash->save()
     */
    public function testSave() 
    {
        $this->Crypt_Hash->save($this->tmpfile, "a test string");
        
        $this->assertType('Q\Transform_Crypt_Hash', $this->Crypt_Hash);
        $this->assertEquals(hash('md5', "a test string"), file_get_contents($this->tmpfile));
    }    

    /**
     * Tests Transform_Crypt_Hash->getReverse()
     */
    public function testGetReverse() 
    {
        $this->setExpectedException('Q\Transform_Exception', 'There is no reverse transformation defined.');
        $this->Crypt_Hash->getReverse();
    }

    /**
     * Tests Transform_Crypt_Hash->process() -null method
     */
    public function testProcessException_EmptyMethod() 
    {
        $this->setExpectedException('Exception', 'Unable to encrypt: Hashing algoritm not specified.');
        $transform = new Transform_Crypt_Hash(array('method'=>null));
        $transform->process('a test string');
    }

    /**
     * Tests Transform_Crypt_Hash->process() - unsupported method
     */
    public function testProcessException_UnsupportedMethod() 
    {
        $method = "a_method";
        $this->setExpectedException('Exception', "Unable to encrypt: Algoritm '{$method}' is not supported.");
        $transform = new Transform_Crypt_Hash(array('method'=>$method));
        $transform->process('a test string');
    }
}
