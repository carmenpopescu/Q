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
    
    /**
     *  Test Config_File() : exception when no transformer sau ext was provided
     */
    public function test_Construct_NoTransformer() {
        $file = sys_get_temp_dir() . '/q-config_filetest-' . md5(uniqid()) . '';        
        if (!file_put_contents($file, ' ')) $this->markTestSkipped("Could not create file '{$file}'");
        
        $this->setExpectedException('Q\Exception', "Unable to initialize Config_File object: Transformer is not set.");
        $config = new Config_File($file);
       
        if (file_exists($file)) unlink($file);
    }

    /**
     *  Test Config_File() : exception ->path already set
     */
    public function test_setPath_PathAlreadySet() {
        $this->setExpectedException('Q\Exception', "Unable to set 'a_path' to Config_File object: Config_File path '{$this->file}' is already set.");
        $config = new Config_File($this->file);
       
        $config->setPath('a_path');
    }

    /**
     *  Test Config_File() : exception -> unable to save - path is not set
     */
    public function test_save_PathNotSet() {
        $this->setExpectedException('Q\Exception', "Unable to save setting: Path is not set");
        $config = new Config_File();
        $config['a'] = 10;
        $config->save();
    }

    /**
     *  Test Config_File() : exception -> unable to save - path is not set
     */
    public function test_NoTransformerForSave() {
        $this->setExpectedException('Q\Exception', "Unable to save setting to '{$this->file}': Transformer is not set.");
        $config = new Config_File();
        $config['a'] = 10;
        $config->setPath($this->file);
        $config->save();
    }

    /**
     *  Test Config_File() : exception -> unable to set transfromer - transformer already set
     */
    public function test_setTransformerException() {
        $config = new Config_File($this->file);
        $this->setExpectedException('Q\Exception', "Unable to set 'mock' to Config_File object: Transformer 'mock' is already set.");
        $config->setTransformer('mock');
    }
    
}
