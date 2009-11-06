<?php
use Q\Config, Q\Config_File, Q\Fs, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Config/File.php';
require_once 'Config/Mock/Unserialize.php';

class Config_FileTest extends \PHPUnit_Framework_TestCase
{
    protected $file;
    
    /**
     * Prepares the environment before running a test.
     */
    public function setUp()
    {
        Transform::$drivers['from-mock'] = 'Config_Mock_Unserialize';
        Transform::$drivers['to-mock'] = 'Config_Mock_Serialize';        
        $this->file = sys_get_temp_dir() . '/q-config_filetest-' . md5(uniqid()) . '.mock';        
        if (!file_put_contents($this->file, ' test ')) $this->markTestSkipped("Could not create file '{$this->file}'");
    }

    /**
     * Cleans up the environment after running a test.
     */
    public function tearDown()
    {
        Config_Mock_Unserialize:$created = array();
        unset(Transform::$drivers['from-mock']);
        unlink($this->file);
    }

    /**
     * Tests Config_File : set transformer
     */
	public function test_withTranformer()
    {
        $mock = new Config_Mock_Unserialize();        
        $config = new Config_File(array($this->file, 'transformer'=>$mock));
    	
        $this->assertType('Q\Fs_File', $mock->in);
    	$this->assertEquals($this->file, (string)$mock->in);
        
        $this->assertType('Q\Config_File', $config);
        $this->assertEquals($mock->out, (array)$config);            	
    	$this->assertEquals(1, count(Config_Mock_Unserialize::$created));
    }
    
    /**
     * Tests Config_File : send only filename param
     */
    public function test_onFilename()
    {
        $config = new Config_File($this->file);
        
        $this->assertArrayHasKey(0, Config_Mock_Unserialize::$created);
        $mock = Config_Mock_Unserialize::$created[0];        
        $this->assertType('Q\Fs_File', $mock->in);
        $this->assertEquals($this->file, (string)$mock->in);

        $this->assertType('Q\Config_File', $config);
        $this->assertEquals($mock->out, (array)$config);
        
        $this->assertEquals(1, count(Config_Mock_Unserialize::$created));
    }

    /**
     * Tests Config_File() : save
     */
    public function test_Save() 
    {
        $config = new Config_File($this->file);
        $config['a'] = 20;
        $config->save();
        
        $this->assertArrayHasKey(0, Config_Mock_Unserialize::$created);
        $mock = Config_Mock_Unserialize::$created[0];        
        $this->assertType('Q\Fs_File', $mock->in);
        $this->assertEquals($this->file, (string)$mock->in);

        $this->assertType('Q\Config_File', $config);
        $this->assertEquals($mock->reverse->out, serialize((array)$config));
        
        $this->assertEquals(1, count(Config_Mock_Unserialize::$created));        
    }
}
