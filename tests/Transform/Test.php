<?php
use Q\Transform, Q\Fs;

require_once 'TestHelper.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

require_once 'Q/Transform.php';
require_once 'Q/Fs.php';

/**
 * Test factory method
 */
class Transform_Test extends \PHPUnit_Framework_TestCase
{
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        if (isset($this->file) &&file_exists($this->file)) unlink($this->file);
        if (isset($this->tmpfile) && file_exists($this->tmpfile)) unlink($this->tmpfile);
        parent::tearDown();
    }
    
   
    /**
     * Test driver xsl
     */
    public function testDriver_SimpleDSN()
    {
        $transform = Transform::with('xsl');
        $this->assertType('Q\Transform_XSL', $transform);
    }
    
    /**
     * Test driver json with dsn
     */
    public function testDriver_SimpleOptions()
    {
        $transform = Transform::with('from-json:assoc=false');
        $this->assertType('Q\Transform_Unserialize_Json', $transform);
        $this->assertFalse($transform->assoc);
    }
    
    /**
     * Test driver with dsn and multiple options
     */
    public function testDriver_Options()
    {
        $this->file = tempnam(sys_get_temp_dir(), 'Q-testTr-file-'.md5(uniqid()));
        file_put_contents($this->file, "<body>
  Hello i'm ###name###. I was very cool @ ###a###.
</body>");

        
        $transform = Transform::with('from-json:file='.$this->file.';marker=###%s###;');
        $this->assertType('Q\Transform_Unserialize_Json', $transform);
        $this->assertEquals('###%s###', $transform->marker);
        $this->assertEquals("<body>
  Hello i'm ###name###. I was very cool @ ###a###.
</body>", file_get_contents($transform->file));
    }
    
    public function testOptions()
    {
        $transform = Transform::with('xsl', array('test' => 'TESTAREA'));
        $this->assertType('Q\Transform_XSL', $transform);
        
        $refl = new ReflectionProperty($transform, 'test');
        $refl->setAccessible(true);
        $test = $refl->getValue($transform);
        $this->assertEquals('TESTAREA', $test);
    }
    
    /**
     * Test Transform::to()
     */
    public function testTo()
    {
        $transform = Transform::to('xml');
        $this->assertType('Q\Transform_Serialize_XML', $transform);
    }
    /**
     * Test Transform::to()
     */
    public function testTo_EmptyArgument()
    {
        $this->setExpectedException('Q\Exception', "Unable to create Transform object: No driver specified");
        $transform = Transform::to();
    }
    
    /**
     * Test Transform::to()
     */
    public function testTo_Options()
    {
        $transform = Transform::to('php:castObjectToString=true');
        $this->assertType('Q\Transform_Serialize_PHP', $transform);
        $this->assertTrue($transform->castObjectToString);
    }
    
    /**
     * Test Transform::from()
     */
    public function testFrom()
    {
        $transform = Transform::from('ini');
        $this->assertType('Q\Transform_Unserialize_Ini', $transform);
    }

    /**
     * Test Transform::from()
     */
    public function testFrom_Options()
    {
        $transform = Transform::from('ini:test1=testarea1;test2=testarea2');
        $this->assertType('Q\Transform_Unserialize_Ini', $transform);
        $this->assertEquals('testarea1', $transform->test1);
        $this->assertEquals('testarea2', $transform->test2);
    }

    /**
     * Test Transform::compress()
     */
    public function testCompress()
    {
        $transform = Transform::compress('gzip');
        $this->assertType('Q\Transform_Compress_Gzip', $transform);
    }
    
    /**
     * Test Transform::decompress()
     */
    public function testDecompress()
    {
        $transform = Transform::decompress('gzip');
        $this->assertType('Q\Transform_Decompress_Gzip', $transform);
    }
    
    /**
     *  Test Transform::with() -> when using multiple transformers separated by +
     */
    public function testwith_MultipleTransformers()
    {
        $transform = Transform::with('from-xml + to-yaml:secret=bla + from-yaml');
        $this->assertType('Q\Transform_Unserialize_Yaml', $transform);
        $this->assertType('Q\Transform_Serialize_Yaml', $transform->getChainInput());
        $this->assertType('Q\Transform_Unserialize_XML', $transform->getChainInput()->getChainInput());    
    }
}
