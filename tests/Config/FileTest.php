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
        $this->dir = sys_get_temp_dir() . '/q-config_filetest-dir-' . md5(uniqid());
        if (!mkdir($this->dir)) $this->markTestSkipped("Could not create dit '{$this->dir}'");
        
        $this->noextfile = sys_get_temp_dir() . '/q-config_filetest-' . md5(uniqid());
        if (!file_put_contents($this->noextfile, ' test ')) $this->markTestSkipped("Could not create file '{$this->noextfile}'");
        
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

        if (file_exists($this->file)) unlink($this->file);
        if (file_exists($this->noextfile)) unlink($this->noextfile);
        if (file_exists($this->dir)) rmdir($this->dir);
    }


    // Construction tests    
    
    /**
     * Check the results valid for most Config::with() tests
     */
    public function checkWithResult($config)
    {
        
        $this->assertArrayHasKey(0, Config_Mock_Unserialize::$created);
        $mock = Config_Mock_Unserialize::$created[0];        
        $this->assertType('Q\Fs_File', $mock->in);
        $this->assertEquals($this->file, (string)$mock->in);

        $this->assertType('Q\Config_File', $config);
        $this->assertEquals($mock->out, (array)$config);
        $this->assertEquals(1, count(Config_Mock_Unserialize::$created));
        
        $refl_ext = new \ReflectionProperty($config, '_ext');
        $refl_ext->setAccessible(true);
        $this->assertEquals('mock', $refl_ext->getValue($config));

        $refl_path = new \ReflectionProperty($config, '_path');
        $refl_path->setAccessible(true);
        $this->assertEquals($this->file, (string)$refl_path->getValue($config));
        
        $refl_tr = new \ReflectionProperty($config, '_transformer');
        $refl_tr->setAccessible(true);
        $this->assertType('Config_Mock_Unserialize', $refl_tr->getValue($config));
                
    }
    
    /**
     * Check the results valid for most Config::with() tests that have a transformer
     */
    public function checkWithTrResult($config)
    {
        $this->assertType('Q\Config_File', $config);
        
        $refl_ext = new \ReflectionProperty($config, '_ext');
        $refl_ext->setAccessible(true);
        $this->assertEquals('yaml', $refl_ext->getValue($config));

        $refl_path = new \ReflectionProperty($config, '_path');
        $refl_path->setAccessible(true);
        $this->assertEquals($this->file, (string)$refl_path->getValue($config));
        
        $refl_tr = new \ReflectionProperty($config, '_transformer');
        $refl_tr->setAccessible(true);
        $this->assertType('Config_Mock_Unserialize', $refl_tr->getValue($config));        
    }
    
    /**
     * Tests Config::with(): full (standard) DSN
     */
    public function testWith()
    {
        $config = Config::with("file:ext=mock;path={$this->file}");
        $this->checkWithResult($config);
    }    

    /**
     * Tests Config::with() : where driver; argument[0] is mock and argument['path']
     */
    public function testWith_Arg0IsExt()
    {
        $config = Config::with("file:mock;path={$this->file}");
        $this->checkWithResult($config);
    }

    /**
     * Tests Config::with() : where driver, argument[0] is path and argument['ext']
     */
    public function testWith_Arg0IsPath()
    {
        $config = Config::with("file:{$this->file};ext=mock");
        $this->checkWithResult($config);   
    }
    
    /**
     * Tests Config::with() : where driver, argument[0] is ext:path
     */
    public function testWith_Arg0IsExtPath()
    {
        $config = Config::with("file:mock:{$this->file}");
        $this->checkWithResult($config);   
    }
        
    /**
     * Tests Config::with(): where driver is extension and argument[0] is path
     */
    public function testWith_DriverIsExt_Arg0IsPath()
    {
        $config = Config::with("mock:{$this->file}");
        $this->checkWithResult($config);
    }

    /**
     * Tests Config::with() : where driver is path
     */
    public function testWith_DriverIsPath()
    {
        $config = Config::with($this->file);
        $this->checkWithResult($config);
    }

    /**
     * Tests Config::with() : where dsn is driver:mock and options['path']
     */
    public function testWith_DsnIsFileAndExtOptPath()
    {
        $config = Config::with("file:mock", array('path'=>$this->file));
        $this->checkWithResult($config);
    }

    /**
     * Tests Config::with() : where dsn is driver:mock and options[0] is path
     * this is a wrong usage of the Config ; the path will not be set anymore
     */
    public function testWith_DsnIsFileAndExtOpt0Path()
    {
        $config = Config::with("file:mock", array($this->file));

        $this->assertType('Q\Config_File', $config);
        
        $refl_ext = new \ReflectionProperty($config, '_ext');
        $refl_ext->setAccessible(true);
        $this->assertEquals('mock', $refl_ext->getValue($config));

        $refl_path = new \ReflectionProperty($config, '_path');
        $refl_path->setAccessible(true);
        $this->assertEquals(null, (string)$refl_path->getValue($config));
        
        $refl_tr = new \ReflectionProperty($config, '_transformer');
        $refl_tr->setAccessible(true);
        $this->assertType('Config_Mock_Unserialize', $refl_tr->getValue($config));
    }
    
    /**
     * Tests Config::with() : where dsn is driver:path and options['ext']
     */
    public function testWith_DsnIsDriverAndPathOptExt()
    {
        $config = Config::with("file:{$this->file}", array('ext'=>'mock'));
        $this->checkWithResult($config);
    }
    
    /**
     * Tests Config::with() : where dsn is ext and options['path']
     */
    public function testWith_DsnIsExtOptPath()
    {
        $config = Config::with('mock', array('path'=>$this->file));
        $this->checkWithResult($config);
    }
    
    /**
     * Tests Config::with() : where dsn is ext and options[0] is path
     */
    public function testWith_DsnIsExtOpt0Path()
    {
        $config = Config::with('mock', array($this->file));
        $this->checkWithResult($config);
    }
    
    /**
     * Tests Config::with() : where dsn is path and options['ext']
     */
    public function testWith_DsnIsPathOptExt()
    {
        $config = Config::with($this->file, array('ext'=>'mock'));
        $this->checkWithResult($config);
    }

    /**
     * Tests Config::with() : where driver; argument[0] is yaml, argument['path'] and argument['transformer']
     */
    public function testWith_Arg0IsExtAndArgTr()
    {
        $config = Config::with("file:yaml;path={$this->file};transformer=from-mock");
        $this->checkWithTrResult($config);   
    }

    /**
     * Tests Config::with() : where driver, argument[0] is path, argument['ext'] and argument['transformer']
     */
    public function testWith_Arg0IsPathAndArgTr()
    {
        $config = Config::with("file:{$this->file};ext=yaml;transformer=from-mock");
        $this->checkWithTrResult($config);   
    }
    
    /**
     * Tests Config::with() : where driver, argument[0] is ext:path and argument['transformer']
     */
    public function testWith_Arg0IsExtPathAndArgTr()
    {
        $config = Config::with("file:yaml:{$this->file};transformer=from-mock");
        $this->checkWithTrResult($config);   
    }
        
    /**
     * Tests Config::with(): where driver is extension, argument[0] is path and argument['transformer']
     */
    public function testWith_DriverIsExt_Arg0IsPathAndArgTr()
    {
        $config = Config::with("yaml:{$this->file};transformer=from-mock");
        $this->checkWithTrResult($config);
    }
    
    /**
     * Tests Config::with() : where dsn is driver:path and options['ext'] and options['transformer']
     */
    public function testWith_DsnIsDriverAndPathOptExtArgTr()
    {
        $config = Config::with("file:{$this->file}", array('ext'=>'yaml', 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }
    
    /**
     * Tests Config::with() : where dsn is ext and options['path'] and options['transformer']
     */
    public function testWith_DsnIsExtOptPathArgTr()
    {
        $config = Config::with('yaml', array('path'=>$this->file, 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }
    
    /**
     * Tests Config::with() : where dsn is ext and options[0] is path and options['transformer']
     */
    public function testWith_DsnIsExtOpt0PathArgTr()
    {
        $config = Config::with('yaml', array($this->file, 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }
    
    /**
     * Tests Config::with() : where dsn is path and options['ext'] and options['transformer']
     */
    public function testWith_DsnIsPathOptExtArgTr()
    {
        $config = Config::with($this->file, array('ext'=>'yaml', 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }

    /**
     * Tests Config_file::with(): full (standard) DSN
     */
    public function testFileWith()
    {
        $config = Config_File::with("file:ext=mock;path={$this->file}");
        $this->checkWithResult($config);
    }    

    /**
     * Tests Config_File::with() : where driver, argument[0] is path and argument['ext']
     */
    public function testFileWith_Arg0IsPath()
    {
        $config = Config_File::with("file:{$this->file};ext=mock");
        $this->checkWithResult($config);   
    }
           
    /**
     * Tests Config_File::with(): where driver is extension and argument[0] is path
     */
    public function testFileWith_DriverIsExt_Arg0IsPath()
    {
        $config = Config_File::with("mock:{$this->file}");
        $this->checkWithResult($config);
    }

    /**
     * Tests Config_File::with() : where driver is path
     */
    public function testFileWith_DriverIsPath()
    {
        $config = Config_File::with($this->file);
        $this->checkWithResult($config);
    }

    /**
     * Tests Config_File::with() : where dsn is ext and options[0] is path
     */
    public function testFileWith_DsnIsExtOpt0Path()
    {
        $config = Config_File::with("mock", array($this->file));
        $this->checkWithResult($config);
    }
    
    /**
     * Tests Config_File::with() : where dsn is driver:path and options['ext']
     */
    public function testFileWith_DsnIsDriverAndPathOptExt()
    {
        $config = Config_File::with("file:{$this->file}", array('ext'=>'mock'));
        $this->checkWithResult($config);
    }
    
    /**
     * Tests Config_File::with() : where dsn is ext and options['path']
     */
    public function testFileWith_DsnIsExtOptPath()
    {
        $config = Config_File::with('mock', array('path'=>$this->file));
        $this->checkWithResult($config);
    }
    
    /**
     * Tests Config_File::with() : where dsn is path and options['ext']
     */
    public function testFileWith_DsnIsPathOptExt()
    {
        $config = Config_File::with($this->file, array('ext'=>'mock'));
        $this->checkWithResult($config);
    }

    /**
     * Tests Config_File::with() : where driver, argument[0] is path, argument['ext'] and argument['transformer']
     */
    public function testFileWith_Arg0IsPathAndArgTr()
    {
        $config = Config_File::with("file:{$this->file};ext=yaml;transformer=from-mock");
        $this->checkWithTrResult($config);   
    }
           
    /**
     * Tests Config_File::with(): where driver is extension, argument[0] is path and argument['transformer']
     */
    public function testFileWith_DriverIsExt_Arg0IsPathAndArgTr()
    {
        $config = Config_File::with("yaml:{$this->file};transformer=from-mock");
        $this->checkWithTrResult($config);
    }
    
    /**
     * Tests Config_File::with() : where dsn is driver:path and options['ext'] and options['transformer']
     */
    public function testFileWith_DsnIsDriverAndPathOptExtArgTr()
    {
        $config = Config_File::with("file:{$this->file}", array('ext'=>'yaml', 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }
    
    /**
     * Tests Config_File::with() : where dsn is ext and options['path'] and options['transformer']
     */
    public function testFileWith_DsnIsExtOptPathArgTr()
    {
        $config = Config_File::with('yaml', array('path'=>$this->file, 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }
    
    /**
     * Tests Config_File::with() : where dsn is ext and options[0] is path and options['transformer']
     */
    public function testFileWith_DsnIsExtOpt0PathArgTr()
    {
        $config = Config_File::with('yaml', array($this->file, 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }
    
    /**
     * Tests Config_File::with() : where dsn is path and options['ext'] and options['transformer']
     */
    public function testFileWith_DsnIsPathOptExtArgTr()
    {
        $config = Config_File::with($this->file, array('ext'=>'yaml', 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }
    
    /**
     * Tests Config_File : only path
     */
    public function testFile_Path()
    {
        $config = new Config_File($this->file);
        $this->checkWithResult($config);
    }
    
    /**
     * Tests Config_File(): standard
     */
    public function testFile_Path_OptExt()
    {
        $config = new Config_File($this->file, array('ext'=>'mock'));
        $this->checkWithResult($config);
    }    
    
    /**
     * Tests Config_File(): path array with arg[0] is path and arg['ext']
     */
    public function testFile_ArrayPath_Arg0IsPathAndArgExt()
    {
        $config = new Config_File(array($this->file, 'ext'=>'mock'));
        $this->checkWithResult($config);
    }    
    
    /**
     * Tests Config_File(): path array with arg['path'] is path and arg['ext']
     */
    public function testFile_ArrayPath_ArgPathAndArgExt()
    {
        $config = new Config_File(array('path'=>$this->file, 'ext'=>'mock'));
        $this->checkWithResult($config);
    }    
    
    /**
     * Tests Config_File() : path, options['ext'] and options['transformer']
     */
    public function testFile_Path_OptExtAndOptTr()
    {
        $config = new Config_File("{$this->file}", array('ext'=>'yaml', 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }
    
    /**
     * Tests Config_File(): array path with arg[0] is path, arg['ext'] and arg['transformrer'] 
     */
    public function testFile_ArrayPath_Arg0PathArgExtArgTr()
    {
        $config = new Config_File(array("{$this->file}", 'ext'=>'yaml', 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }    
    
    /**
     * Tests Config_File(): path is array with dsn
     */
    public function testFile_ArrayPathWithDsn()
    {
        $config = new Config_File(array("mock:{$this->file}"));
        $this->checkWithResult($config);
    }
    
    /**
     * Tests Config_File(): path array with argument[0] is dsn and argument['transformer']
     */
    public function testFile_ArrayPathWithDsn_OptTr()
    {
        $config = new Config_File(array("yaml:{$this->file}", 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }
    
    /**
     * Tests Config_File(): array path with arg[0] is dsn and arg['transformrer'] 
     */
    public function testFile_DriverIsExt_Arg0IsPathAndArgTr()
    {
        $config = new Config_File(array("yaml:{$this->file}", 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }    
    
    /**
     * Tests Config:with() -> us a driver file and a dir instead of a file 
     */
    public function test_UserDirWithDrFile()
    {
        $this->setExpectedException('Q\Exception', "File '{$this->dir}' is not a regular file, but a directory");
        $config = Config::with("file:mock:{$this->dir}");
    }
        
    /**
     * Tests Config_File : set transformer
     */
	public function test_withTranformer()
    {
        $mock = new Config_Mock_Unserialize();        
        $config = new Config_File(array($this->file, 'transformer'=>$mock));

        $this->assertType('Q\Config_File', $config);

        $this->assertType('Q\Fs_File', $mock->in);
    	$this->assertEquals($this->file, (string)$mock->in);
        
        $this->assertEquals($mock->out, (array)$config);            	
    	$this->assertEquals(1, count(Config_Mock_Unserialize::$created));
    }
    // end construct tests  
    
    /**
     * Test Config_File()
     */
    public function test_Settings() {
        $config = new Config_File();
        $config['abc'] = 10;
        $config->setPath('/tmp/abc.xml');

        $this->assertType('Q\Config_File', $config);
        $this->assertEquals('/tmp/abc.xml', (string)$config->getPath());
        $this->assertEquals(array('abc'=>10), (array)$config);        
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
     * Tests Config_File() : start with an object without the path, set settings, set the path and then save
     */
    public function test_SaveStartEmptyObject()
    {
        $config = new Config_File();
        $config['a'] = 'b';
        $config->setPath('/tmp/abc.mock');

        $config->save();

        $this->assertType('Q\Config_File', $config);
        $this->assertEquals("/tmp/abc.mock", (string)$config->getPath());
        $this->assertTrue(is_file((string)$config->getPath()));
        $this->assertEquals('a:1:{s:1:"a";s:1:"b";}', file_get_contents((string)$config->getPath()));
    }
        
    /**
     *  Test Config_File() : exception when no transformer or ext was provided
     */
    public function test_Construct_NoTransformer() {
        $this->setExpectedException('Q\Exception', "Unable to initialize Config_File object: Transformer is not set.");
        $config = new Config_File($this->noextfile);       
    }

    /**
     *  Test Config_File() : exception -> trying to set path when path already set
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
        $this->setExpectedException('Q\Exception', "Unable to save setting to '{$this->noextfile}': Transformer is not set.");
        $config = new Config_File();
        $config['a'] = 10;
        $config->setPath($this->noextfile);
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
