<?php
use Q\Config, Q\Fs;

require_once 'TestHelper.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

require_once 'Q/Config.php';
require_once 'Q/Fs.php';

/**
 * Test factory method
 */
class Config_Test extends \PHPUnit_Framework_TestCase
{
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $this->file = sys_get_temp_dir() . '/q-config_test-' . md5(uniqid());
        if (!file_put_contents($this->file, '<?xml version="1.0" encoding="UTF-8"?>
<settings>
    <grp1>
        <q>abc</q>
        <b>27</b>
    </grp1>
    <grp2>
        <a>original</a>
    </grp2>
</settings>')) $this->markTestSkipped("Could not write to '{$this->file}'.");
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->cleanup($this->file);
    }

    /**
     * Remove tmp files (recursively)
     * 
     * @param string $path
     */
    protected static function cleanup($path)
    {
        foreach (array('', '.orig', '.x', '.y') as $suffix) {
            if (is_dir($path . $suffix) && !is_link($path . $suffix)) {
                static::cleanup($path . $suffix . '/' . basename($path));
                if (!rmdir($path . $suffix)) throw new Exception("Cleanup failed");
            } elseif (file_exists($path . $suffix) || is_link($path . $suffix)) {
                unlink($path . $suffix);
            }
        }
    } 
    
    
    public function testDriverOnly()
    {
        $config = Config::with('xml:'.$this->file);
        
        $refl = new \ReflectionProperty($config, '_ext');
        $refl->setAccessible(true);
        $ext = $refl->getValue($config);
        $this->assertType('Q\Config_File', $config);
        $this->assertEquals('xml', $ext);
    }

    public function testPath()
    {
        $config = Config::with('xml:'.$this->file);

        $refl = new \ReflectionProperty($config, '_path');
        $refl->setAccessible(true);
        $path = $refl->getValue($config);

        $this->assertType('Q\Config_File', $config);
        $this->assertEquals($this->file, (string)$path);
    }
}
