<?php
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'Transform/Test.php';
require_once 'Transform/ReplaceTest.php';
require_once 'Transform/XSLTest.php';
require_once 'Transform/PHPTest.php';
require_once 'Transform/Text2HTMLTest.php';
require_once 'Transform/HTML2TextTest.php';
require_once 'Transform/Serialize/XMLTest.php';
require_once 'Transform/Unserialize/XMLTest.php';
require_once 'Transform/Serialize/PHPTest.php';
require_once 'Transform/Unserialize/PHPTest.php';
require_once 'Transform/Serialize/JsonTest.php';
require_once 'Transform/Unserialize/JsonTest.php';
require_once 'Transform/Serialize/YamlTest.php';
require_once 'Transform/Unserialize/YamlTest.php';
require_once 'Transform/Serialize/IniTest.php';
require_once 'Transform/Unserialize/IniTest.php';

require_once 'Transform/Crypt/MD5Test.php';
require_once 'Transform/Crypt/MCryptTest.php';
require_once 'Transform/Crypt/HashTest.php';
require_once 'Transform/Crypt/CRC32Test.php';
require_once 'Transform/Crypt/OpenSSLTest.php';
require_once 'Transform/Crypt/SystemTest.php';

require_once 'Transform/Decrypt/MCryptTest.php';
require_once 'Transform/Decrypt/OpenSSLTest.php';

require_once 'Transform/Serialize/SerialTest.php';
require_once 'Transform/Unserialize/SerialTest.php';

require_once 'Transform/Compress/GzipTest.php';
require_once 'Transform/Decompress/GzipTest.php';
require_once 'Transform/Compress/BzipTest.php';
require_once 'Transform/Decompress/BzipTest.php';
require_once 'Transform/Compress/LzfTest.php';
require_once 'Transform/Decompress/LzfTest.php';


/**
 * Static test suite.
 */
class TransformTest extends PHPUnit_Framework_TestSuite
{
    /**
     * Constructs the test suite handler.
     */
    public function __construct()
    {
        $this->setName('TransformTest');
        $this->addTestSuite('Transform_Test');
        $this->addTestSuite('Transform_ReplaceTest');
        $this->addTestSuite('Transform_XSLTest');
        $this->addTestSuite('Transform_PHPTest');
        $this->addTestSuite('Transform_Text2HTMLTest');
        $this->addTestSuite('Transform_HTML2TextTest');
        $this->addTestSuite('Transform_Unserialize_XMLTest');
        $this->addTestSuite('Transform_Serialize_XMLTest');
        $this->addTestSuite('Transform_Unserialize_PHPTest');
        $this->addTestSuite('Transform_Serialize_PHPTest');
        $this->addTestSuite('Transform_Unserialize_JsonTest');
        $this->addTestSuite('Transform_Serialize_JsonTest');
        $this->addTestSuite('Transform_Unserialize_YamlTest');
        $this->addTestSuite('Transform_Serialize_YamlTest');
        $this->addTestSuite('Transform_Unserialize_IniTest');
        $this->addTestSuite('Transform_Serialize_IniTest');
        $this->addTestSuite('Transform_Crypt_MD5Test');
        $this->addTestSuite('Transform_Crypt_MCryptTest');
        $this->addTestSuite('Transform_Crypt_Hashtest');
        $this->addTestSuite('Transform_Crypt_CRC32Test');
        $this->addTestSuite('Transform_Decrypt_MCryptTest');        
        $this->addTestSuite('Transform_Crypt_OpenSSLTest'); 
        $this->addTestSuite('Transform_Decrypt_OpenSSLTest');        
        $this->addTestSuite('Transform_Crypt_SystemTest');
        $this->addTestSuite('Transform_Serialize_SerialTest');        
        $this->addTestSuite('Transform_Unserialize_SerialTest');
        $this->addTestSuite('Transform_Compress_GzipTest');        
        $this->addTestSuite('Transform_Decompress_GzipTest');
        $this->addTestSuite('Transform_Compress_BzipTest');        
        $this->addTestSuite('Transform_Decompress_BzipTest');
        $this->addTestSuite('Transform_Compress_LzfTest');        
        $this->addTestSuite('Transform_Decompress_LzfTest');
    }
    
    /**
     * Creates the suite.
     */
    public static function suite()
    {
        return new self();
    }
}

