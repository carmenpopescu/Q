<?php
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'Config/Test.php';
require_once 'Config/FileTest.php';
require_once 'Config/DirTest.php';

/**
 * Static test suite.
 */
class ConfigTest extends \PHPUnit_Framework_TestSuite
{
    /**
     * Constructs the test suite handler.
     */
    public function __construct ()
    {
        $this->setName('ConfigTest');
        $this->addTestSuite('Config_Test');
        $this->addTestSuite('Config_FileTest');
        $this->addTestSuite('Config_DirTest');
    }
    /**
     * Creates the suite.
     */
    public static function suite ()
    {
        return new self();
    }
}

