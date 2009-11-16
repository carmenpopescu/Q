<?php
use Q\Config, Q\Config_Dir, Q\Config_File;

require_once 'TestHelper.php';
require_once 'Q/Config/Dir.php';
require_once 'Q/Config/File.php';
require_once 'Config/Mock/Unserialize.php';

/**
 * Test for Config_Dir
 */
class Config_DirTest extends \PHPUnit_Framework_TestCase
{
    protected $dir;
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $this->dir = sys_get_temp_dir() . '/q-config_dirtest-' . md5(uniqid());
        
        mkdir($this->dir);
        mkdir("{$this->dir}/dir1");
        touch("{$this->dir}/file1.mock");
        touch("{$this->dir}/file2.mock");
        touch("{$this->dir}/dir1/file3.mock");
        touch("{$this->dir}/dir1/file4.mock");
        
        Q\Transform::$drivers['from-mock'] = 'Config_Mock_Unserialize';
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        unlink("{$this->dir}/file1.mock");
        unlink("{$this->dir}/file2.mock");
        unlink("{$this->dir}/dir1/file3.mock");
        unlink("{$this->dir}/dir1/file4.mock");
        rmdir("{$this->dir}/dir1");
        rmdir($this->dir);
        
        Config_Mock_Unserialize:$created = array();
        unset(Q\Transform::$drivers['from-mock']);
    }
    
    
    // Construction tests    
    
    /**
     * Check the results valid for most Config::with() tests
     */
    public function checkWithResult($config)
    {
        $this->assertType('Q\Config_Dir', $config);
        
        $refl_ext = new \ReflectionProperty($config, '_ext');
        $refl_ext->setAccessible(true);
        $this->assertEquals('mock', $refl_ext->getValue($config));

        $refl_path = new \ReflectionProperty($config, '_path');
        $refl_path->setAccessible(true);
        $this->assertEquals($this->dir, (string)$refl_path->getValue($config));
        
        $refl_tr = new \ReflectionProperty($config, '_transformer');
        $refl_tr->setAccessible(true);
        $this->assertType('Config_Mock_Unserialize', $refl_tr->getValue($config));        
    }
    
    /**
     * Check the results valid for most Config::with() tests that have a transformer
     */
    public function checkWithTrResult($config)
    {
        $this->assertType('Q\Config_Dir', $config);
        
        $refl_ext = new \ReflectionProperty($config, '_ext');
        $refl_ext->setAccessible(true);
        $this->assertEquals('yaml', $refl_ext->getValue($config));

        $refl_path = new \ReflectionProperty($config, '_path');
        $refl_path->setAccessible(true);
        $this->assertEquals($this->dir, (string)$refl_path->getValue($config));
        
        $refl_tr = new \ReflectionProperty($config, '_transformer');
        $refl_tr->setAccessible(true);
        $this->assertType('Config_Mock_Unserialize', $refl_tr->getValue($config));        
    }
    
    /**
     * Tests Config::with(): full (standard) DSN
     */
    public function testWith()
    {
        $config = Config::with("dir:ext=mock;path={$this->dir}");
        $this->checkWithResult($config);
    }    

    /**
     * Tests Config::with() : where driver; argument[0] is mock and argument['path']
     */
    public function testWith_Arg0IsExt()
    {
        $config = Config::with("dir:mock;path={$this->dir}");
        $this->checkWithResult($config);
    }

    /**
     * Tests Config::with() : where driver, argument[0] is path and argument['ext']
     */
    public function testWith_Arg0IsPath()
    {
        $config = Config::with("dir:{$this->dir};ext=mock");
        $this->checkWithResult($config);   
    }
    
    /**
     * Tests Config::with() : where driver, argument[0] is ext:path
     */
    public function testWith_Arg0IsExtPath()
    {
        $config = Config::with("dir:mock:{$this->dir}");
        $this->checkWithResult($config);   
    }
        
    /**
     * Tests Config::with(): where driver is extension and argument[0] is path
     */
    public function testWith_DriverIsExt_Arg0IsPath()
    {
        $config = Config::with("mock:{$this->dir}");
        $this->checkWithResult($config);
    }

    /**
     * Tests Config::with() : where driver is path
     */
    public function testWith_DriverIsPath()
    {
        $config = Config::with($this->dir);
        
        $this->assertType('Q\Config_Dir', $config);

        $refl_path = new \ReflectionProperty($config, '_path');
        $refl_path->setAccessible(true);
        $this->assertEquals($this->dir, (string)$refl_path->getValue($config));

        $refl_tr = new \ReflectionProperty($config, '_transformer');
        $refl_tr->setAccessible(true);
        $this->assertEquals(null, $refl_tr->getValue($config));        
                
        $refl_ext = new \ReflectionProperty($config, '_ext');
        $refl_ext->setAccessible(true);
        $this->assertEquals(null, (string)$refl_ext->getValue($config));
    }

    /**
     * Tests Config::with() : where dsn is driver:mock and options['path']
     */
    public function testWith_DsnIsDirAndExtOptPath()
    {
        $config = Config::with("dir:mock", array('path'=>$this->dir));
        $this->checkWithResult($config);
    }

    /**
     * Tests Config::with() : where dsn is driver:mock and options[0] is path
     */
    public function testWith_DsnIsDirAndExtOpt0Path()
    {
        $config = Config::with("dir:mock", array($this->dir));
        $this->assertType('Q\Config_Dir', $config);
        
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
        $config = Config::with("dir:{$this->dir}", array('ext'=>'mock'));
        $this->checkWithResult($config);
    }
    
    /**
     * Tests Config::with() : where dsn is ext and options['path']
     */
    public function testWith_DsnIsExtOptPath()
    {
        $config = Config::with('mock', array('path'=>$this->dir));
        $this->checkWithResult($config);
    }
    
    /**
     * Tests Config::with() : where dsn is ext and options[0] is path
     */
    public function testWith_DsnIsExtOpt0Path()
    {
        $config = Config::with('mock', array($this->dir));
        $this->checkWithResult($config);
    }
    
    /**
     * Tests Config::with() : where dsn is path and options['ext']
     */
    public function testWith_DsnIsPathOptExt()
    {
        $config = Config::with($this->dir, array('ext'=>'mock'));
        $this->checkWithResult($config);
    }

    /**
     * Tests Config::with() : where driver; argument[0] is yaml, argument['path'] and argument['transformer']
     */
    public function testWith_Arg0IsExtAndArgTr()
    {
        $config = Config::with("dir:yaml;path={$this->dir};transformer=from-mock");
        $this->checkWithTrResult($config);   
    }

    /**
     * Tests Config::with() : where driver, argument[0] is path, argument['ext'] and argument['transformer']
     */
    public function testWith_Arg0IsPathAndArgTr()
    {
        $config = Config::with("dir:{$this->dir};ext=yaml;transformer=from-mock");
        $this->checkWithTrResult($config);   
    }
    
    /**
     * Tests Config::with() : where driver, argument[0] is ext:path and argument['transformer']
     */
    public function testWith_Arg0IsExtPathAndArgTr()
    {
        $config = Config::with("dir:yaml:{$this->dir};transformer=from-mock");
        $this->checkWithTrResult($config);   
    }
        
    /**
     * Tests Config::with(): where driver is extension, argument[0] is path and argument['transformer']
     */
    public function testWith_DriverIsExt_Arg0IsPathAndArgTr()
    {
        $config = Config::with("yaml:{$this->dir};transformer=from-mock");
        $this->checkWithTrResult($config);
    }
    
    /**
     * Tests Config::with() : where dsn is driver:path and options['ext'] and options['transformer']
     */
    public function testWith_DsnIsDriverAndPathOptExtArgTr()
    {
        $config = Config::with("dir:{$this->dir}", array('ext'=>'yaml', 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }
    
    /**
     * Tests Config::with() : where dsn is ext and options['path'] and options['transformer']
     */
    public function testWith_DsnIsExtOptPathArgTr()
    {
        $config = Config::with('yaml', array('path'=>$this->dir, 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }
    
    /**
     * Tests Config::with() : where dsn is ext and options[0] is path and options['transformer']
     */
    public function testWith_DsnIsExtOpt0PathArgTr()
    {
        $config = Config::with('yaml', array($this->dir, 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }
    
    /**
     * Tests Config::with() : where dsn is path and options['ext'] and options['transformer']
     */
    public function testWith_DsnIsPathOptExtArgTr()
    {
        $config = Config::with($this->dir, array('ext'=>'yaml', 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }

    
    /**
     * Tests Config_Dir::with(): full (standard) DSN
     */
    public function testDirWith()
    {
        $config = Config_Dir::with("dir:ext=mock;path={$this->dir}");
        $this->checkWithResult($config);
    }    

    /**
     * Tests Config_Dir::with() : where driver, argument[0] is path and argument['ext']
     */
    public function testDirWith_Arg0IsPath()
    {
        $config = Config_Dir::with("dir:{$this->dir};ext=mock");
        $this->checkWithResult($config);   
    }
           
    /**
     * Tests Config_Dir::with(): where driver is extension and argument[0] is path
     */
    public function testDirWith_DriverIsExt_Arg0IsPath()
    {
        $config = Config_Dir::with("mock:{$this->dir}");
        $this->checkWithResult($config);
    }

    /**
     * Tests Config_Dir::with() : where driver is path
     */
    public function testDirWith_DriverIsPath()
    {
        $config = Config_Dir::with($this->dir);
        
        $this->assertType('Q\Config_Dir', $config);

        $refl_path = new \ReflectionProperty($config, '_path');
        $refl_path->setAccessible(true);
        $this->assertEquals($this->dir, (string)$refl_path->getValue($config));

        $refl_tr = new \ReflectionProperty($config, '_transformer');
        $refl_tr->setAccessible(true);
        $this->assertEquals(null, $refl_tr->getValue($config));        
                
        $refl_ext = new \ReflectionProperty($config, '_ext');
        $refl_ext->setAccessible(true);
        $this->assertEquals(null, (string)$refl_ext->getValue($config));
    }

    /**
     * Tests Config_Dir::with() : where dsn is ext and options[0] is path
     */
    public function testDirWith_DsnIsExtOpt0Path()
    {
        $config = Config_Dir::with("mock", array($this->dir));
        $this->checkWithResult($config);
    }
    
    /**
     * Tests Config_Dir::with() : where dsn is driver:path and options['ext']
     */
    public function testDirWith_DsnIsDriverAndPathOptExt()
    {
        $config = Config_Dir::with("dir:{$this->dir}", array('ext'=>'mock'));
        $this->checkWithResult($config);
    }
    
    /**
     * Tests Config_Dir::with() : where dsn is ext and options['path']
     */
    public function testDirWith_DsnIsExtOptPath()
    {
        $config = Config_Dir::with('mock', array('path'=>$this->dir));
        $this->checkWithResult($config);
    }
    
    /**
     * Tests Config_Dir::with() : where dsn is path and options['ext']
     */
    public function testDirWith_DsnIsPathOptExt()
    {
        $config = Config_Dir::with($this->dir, array('ext'=>'mock'));
        $this->checkWithResult($config);
    }

    /**
     * Tests Config_Dir::with() : where driver, argument[0] is path, argument['ext'] and argument['transformer']
     */
    public function testDirWith_Arg0IsPathAndArgTr()
    {
        $config = Config_Dir::with("dir:{$this->dir};ext=yaml;transformer=from-mock");
        $this->checkWithTrResult($config);   
    }
           
    /**
     * Tests Config_Dir::with(): where driver is extension, argument[0] is path and argument['transformer']
     */
    public function testDirWith_DriverIsExt_Arg0IsPathAndArgTr()
    {
        $config = Config_Dir::with("yaml:{$this->dir};transformer=from-mock");
        $this->checkWithTrResult($config);
    }
    
    /**
     * Tests Config_Dir::with() : where dsn is driver:path and options['ext'] and options['transformer']
     */
    public function testDirWith_DsnIsDriverAndPathOptExtArgTr()
    {
        $config = Config_Dir::with("dir:{$this->dir}", array('ext'=>'yaml', 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }
    
    /**
     * Tests Config_Dir::with() : where dsn is ext and options['path'] and options['transformer']
     */
    public function testDirWith_DsnIsExtOptPathArgTr()
    {
        $config = Config_Dir::with('yaml', array('path'=>$this->dir, 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }
    
    /**
     * Tests Config_Dir::with() : where dsn is ext and options[0] is path and options['transformer']
     */
    public function testDirWith_DsnIsExtOpt0PathArgTr()
    {
        $config = Config_Dir::with('yaml', array($this->dir, 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }
    
    /**
     * Tests Config_Dir::with() : where dsn is path and options['ext'] and options['transformer']
     */
    public function testDirWith_DsnIsPathOptExtArgTr()
    {
        $config = Config_Dir::with($this->dir, array('ext'=>'yaml', 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }
    
    /**
     * Tests Config_Dir : only path
     */
    public function testDir_Path()
    {
        $config = new Config_Dir($this->dir);
        
        $this->assertType('Q\Config_Dir', $config);

        $refl_path = new \ReflectionProperty($config, '_path');
        $refl_path->setAccessible(true);
        $this->assertEquals($this->dir, (string)$refl_path->getValue($config));

        $refl_tr = new \ReflectionProperty($config, '_transformer');
        $refl_tr->setAccessible(true);
        $this->assertEquals(null, $refl_tr->getValue($config));        
                
        $refl_ext = new \ReflectionProperty($config, '_ext');
        $refl_ext->setAccessible(true);
        $this->assertEquals(null, (string)$refl_ext->getValue($config));
    }
    
    /**
     * Tests Config_Dir(): standard
     */
    public function testDir_Path_OptExt()
    {
        $config = new Config_Dir($this->dir, array('ext'=>'mock'));
        $this->checkWithResult($config);
    }    
    
    /**
     * Tests Config_Dir(): path array with arg[0] is path and arg['ext']
     */
    public function testDir_ArrayPath_Arg0IsPathAndArgExt()
    {
        $config = new Config_Dir(array($this->dir, 'ext'=>'mock'));
        $this->checkWithResult($config);
    }    
    
    /**
     * Tests Config_Dir(): path array with arg['path'] is path and arg['ext']
     */
    public function testDir_ArrayPath_ArgPathAndArgExt()
    {
        $config = new Config_Dir(array('path'=>$this->dir, 'ext'=>'mock'));
        $this->checkWithResult($config);
    }    
    
    /**
     * Tests Config_Dir() : path, options['ext'] and options['transformer']
     */
    public function testDir_Path_OptExtAndOptTr()
    {
        $config = new Config_Dir("{$this->dir}", array('ext'=>'yaml', 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }
    
    /**
     * Tests Config_Dir(): array path with arg[0] is path, arg['ext'] and arg['transformrer'] 
     */
    public function testDir_ArrayPath_Arg0PathArgExtArgTr()
    {
        $config = new Config_Dir(array("{$this->dir}", 'ext'=>'yaml', 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }    
    
    /**
     * Tests Config_Dir(): path is array with dsn
     */
    public function testDir_ArrayPathWithDsn()
    {
        $config = new Config_Dir(array("mock:{$this->dir}"));
        $this->checkWithResult($config);
    }
    
    /**
     * Tests Config_Dir(): path array with argument[0] is dsn and argument['transformer']
     */
    public function testDir_ArrayPathWithDsn_OptTr()
    {
        $config = new Config_Dir(array("yaml:{$this->dir}", 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }
    
    /**
     * Tests Config_Dir(): array path with arg[0] is dsn and arg['transformrer'] 
     */
    public function testDir_DriverIsExt_Arg0IsPathAndArgTr()
    {
        $config = new Config_Dir(array("yaml:{$this->dir}", 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }    
    
    
    // Method tests
    
    /**
     * Tests Config_Dir(): eager load 
     */
    public function testDir_EagerLoad()
    {
/*        
        $config = new Config_Dir($this->dir, array('transformer'=>'from-mock', 'ext'=>'mock','loadall' => true));
//        var_dump((array)$config['dir1']);

        $this->assertEquals('myuser', $config['file1']['db']['user']);
        $values = (array)$config;
        
        $this->assertEquals('xml', $config['dir1']['file3']['output']);
*/
    }    
    
    /**
     * Tests Config_Dir(): eager load 
     */
    public function testDir_lazyLoad()
    {
        $config = new Config_Dir($this->dir, array('transformer'=>'from-mock', 'ext'=>'mock'));

        $this->assertType('Q\Config_Dir', $config);
        $this->assertEquals(array(), (array)$config);
        
        $config['file1']['db']['user'] = 'this is a test';
        $this->assertType('Q\Config_File', $config['file1']);
        $this->assertEquals(array("db"=>array("host"=>"localhost", "dbname"=>"test","user"=>"this is a test","pwd"=>"mypwd"),"output"=>"xml","input"=>"json"), (array)$config['file1']);
        
        $this->assertType('Q\Config_Dir', $config['dir1']);
        $this->assertEquals(array(), (array)$config['dir1']);
    }    

    /**
     * Tests Config_Dir() : check if the path is setted correct an object
     */
    public function testDir_getPath() {
        $config = Config::with($this->dir);
        
        $config['abc'] = new Config_File(array('ext'=>'mock'));        
        $this->assertType('Q\Config_File', $config['abc']);
        $this->assertEquals("{$this->dir}/abc.mock", (string)$config['abc']->getPath());
        
        $config['dir_test'] = new Config_Dir(array('ext'=>'mock'));        
        $this->assertType('Q\Config_Dir', $config['dir_test']);
        $this->assertEquals("{$this->dir}/dir_test", (string)$config['dir_test']->getPath());
        
    }

    /**
     * Tests Config_Dir() : save
     */
    public function testDir_Save() {
        $config = Config::with($this->dir);
        $config['abc'] = new Config_File(array('ext'=>'mock'));
        $config['abc']['a'] = 20;
        $config['def'] = new Config_File(array('ext'=>'mock'));
        $config['def']['test'] = 'testarea';
        $config['def']['alfa']=array('beta'=>'gama');
        $config->save();  
        
        $this->assertArrayHasKey(0, Config_Mock_Unserialize::$created);
        $mock_abc = Config_Mock_Unserialize::$created[0];        
        $mock_def = Config_Mock_Unserialize::$created[1];        
        $this->assertEquals((array)$config['abc'], $mock_abc->reverse->in);
        $this->assertEquals((array)$config['def'], $mock_def->reverse->in);
        
        $this->assertType('Q\Config_File', $config);
        $this->assertEquals($mock_abc->reverse->out, serialize((array)$config['abc']));
        $this->assertEquals($mock_def->reverse->out, serialize((array)$config['def']));
        $this->assertEquals(2, count(Config_Mock_Unserialize::$created));        

        unlink($this->dir."/abc.mock");
        unlink($this->dir."/def.mock");
    }


    /**
     * Tests Config_Dir() : save
     */
    public function testDir_SaveUseConfDirObj() {
        $config = Config::with('yaml:'.sys_get_temp_dir().'/abc');
        $config->def = new Config_Dir();
        $config->save();

        $this->assertType('Q\Config_Dir', $config);
        $this->assertEquals(sys_get_temp_dir()."/abc", (string)$config->getPath());
        $this->assertTrue(is_dir((string)$config->getPath()));
        
        $this->assertType('Q\Config_Dir', $config->def);
        $this->assertTrue(is_dir((string)$config->def->getPath()));
        
        if (file_exists(sys_get_temp_dir().'/abc/def')) rmdir(sys_get_temp_dir().'/abc/def');   
        if (file_exists(sys_get_temp_dir().'/abc')) rmdir(sys_get_temp_dir().'/abc');   
    }

    /**
     * Tests Config_Dir() : save
     */
    public function testDir_SaveUseArray() {
        $config = Config::with('mock:'.sys_get_temp_dir().'/def');
        $config['xyz'] = array('a'=>'b');
        $config->save();

        $this->assertType('Q\Config_Dir', $config);
        $this->assertEquals(sys_get_temp_dir()."/def", (string)$config->getPath());
        $this->assertTrue(is_dir((string)$config->getPath()));
        
        $this->assertType('Q\Config_File', $config['xyz']);
        $this->assertEquals(sys_get_temp_dir()."/def/xyz.mock", (string)$config['xyz']->getPath());
        $this->assertTrue(is_file((string)$config['xyz']->getPath()));
        $this->assertEquals('a:1:{s:1:"a";s:1:"b";}', file_get_contents((string)$config['xyz']->getPath()));
        
        if (file_exists(sys_get_temp_dir().'/def/xyz.mock')) unlink(sys_get_temp_dir().'/def/xyz.mock');   
        if (file_exists(sys_get_temp_dir().'/def')) rmdir(sys_get_temp_dir().'/def');   
    }


    /**
     * Tests Config_Dir() : save -> create Config_Dir object and add as node value another Config_Dir object that at the begining has no path setted
     */
    public function testDir_SaveStartWithEmptyPath() {
        $conf = new Config_Dir(array('ext'=>'mock'));
        $conf->abc = new Config_File();
        $conf->dd = array();
        $conf->dd['test'] = 'testarea';
        $conf->abc['ssw'] = 20;

        $config = Config::with(sys_get_temp_dir().'/abc/def');
        $config->xy = $conf;

        $config->save();
        
        $this->assertType('Q\Config_Dir', $config);
        $this->assertEquals(sys_get_temp_dir()."/abc/def", (string)$config->getPath());
        
        $this->assertType('Q\Config_Dir', $config->xy);
        $this->assertEquals(sys_get_temp_dir()."/abc/def/xy", (string)$config->xy->getPath());
        
        $this->assertType('Q\Config_File', $config->xy->abc);
        $this->assertEquals(sys_get_temp_dir()."/abc/def/xy/abc.mock", (string)$config->xy->abc->getPath());
        $this->assertTrue(is_file((string)$config['xy']['abc']->getPath()));
        $this->assertEquals('a:1:{s:3:"ssw";i:20;}', file_get_contents((string)$config['xy']['abc']->getPath()));
                
        $this->assertType('Q\Config_File', $config['xy']['dd']);
        $this->assertEquals(sys_get_temp_dir()."/abc/def/xy/dd.mock", (string)$config['xy']['dd']->getPath());
        $this->assertEquals('a:1:{s:4:"test";s:8:"testarea";}', file_get_contents((string)$config['xy']['dd']->getPath()));
        
        if (file_exists(sys_get_temp_dir().'/abc/def/xy/abc.mock')) unlink(sys_get_temp_dir().'/abc/def/xy/abc.mock');   
        if (file_exists(sys_get_temp_dir().'/abc/def/xy/dd.mock')) unlink(sys_get_temp_dir().'/abc/def/xy/dd.mock');   
        if (file_exists(sys_get_temp_dir().'/abc/def/xy')) rmdir(sys_get_temp_dir().'/abc/def/xy');   
        if (file_exists(sys_get_temp_dir().'/abc/def')) rmdir(sys_get_temp_dir().'/abc/def');   
        if (file_exists(sys_get_temp_dir().'/abc')) rmdir(sys_get_temp_dir().'/abc');   
    }
    
    /**
     *  Test Config_Dir() : exception ->path already set
     */
    public function test_setPathException_PathAlreadySet() {
        $this->setExpectedException('Q\Exception', "Unable to set 'a_path' to Config_Dir object: Config_Dir path '{$this->dir}' is already set.");
        $config = new Config_Dir($this->dir);
       
        $config->setPath('a_path');
    }

    /**
     *  Test Config_Dir() : exception -> different transformer for dir and file
     */
    public function test_offsetSetException_differentTransformers() {
        $this->setExpectedException('Q\Exception', "Unable to create section 'abc': Extension specified for Config_Dir '{$this->dir}' and extension specified for Config_File object setting are different.");
        $config = new Config_Dir($this->dir, array('ext'=>'mock'));    
        $config['abc'] = new Config_File('yaml:path.yaml');
    }

    /**
     *  Test Config_Dir() : exception -> trying to set wrong type variable
     */
    public function test_offsetSetException_WrongValueType() {
        $this->setExpectedException('Q\Exception', "Unable to set 'a' to '30' for Config_Dir '{$this->dir}': Creating a section requires setting an array or Config_File object.");
        $config = new Config_Dir($this->dir, array('ext'=>'mock'));    
        $config['a'] = 30;
    }
    
    /**
     *  Test Config_Dir() : exception -> no ext specified for dir and file
     */
    public function test_offsetSetException_NoExt() {
        $this->setExpectedException('Q\Exception', "Unable to create section 'test': No extension specified for Config_Dir '{$this->dir}' or for the Config_File object setting.");
        $config = new Config_Dir($this->dir);    
        $config['test'] = new Config_File();
    }

    /**
     *  Test Config_Dir() : exception -> no ext specified for dir
     */
    public function test_offsetSetException_NoExtForDir() {
        $this->setExpectedException('Q\Exception', "Unable to create section 'test': No extension specified for Config_Dir '{$this->dir}', creating a section requires setting a Config_File object.");
        $config = new Config_Dir($this->dir);    
        $config['test'] = array();
    }

    /**
     *  Test Config_Dir() : exception -> unable to setPath -> no Fs_Node
     */
    public function test_offsetSetException_wrongPathType() {
        $this->setExpectedException('Q\Exception', "Unable to set path for the Config_File children of Config_Dir object : The path of Config_Dir is not a Fs_Node.");
        $config = new Config_Dir(array('ext'=>'mock'));
        $config->setChildrenPath(new Config_Dir(), 'test', 'testarea');
    }

    /**
     *  Test Config_Dir() : exception -> loadAll - no path specified
     */
    public function test_loadAllException_noPath() {
        $this->setExpectedException('Q\Exception', "Unable to create Config object: Path not specified.");
        $config = new Config_Dir(array('ext'=>'mock'), array('loadall'=> 'true'));
    }

    /**
     *  Test Config_Dir() : exception -> loadAll - no path specified
     */
    public function test_saveException_noPath() {
        $this->setExpectedException('Q\Exception', "Unable to save setting: Path not specified.");
        $config = new Config_Dir();
        $config->save();
    }
    
}
