<?php
use Q\Auth;

require_once 'TestHelper.php';
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'Q/Auth.php';

/**
 * Auth test case.
 * 
 * @todo Test with different checksum options.
 */
class Auth_MainTest extends PHPUnit_Framework_TestCase
{
    /**
     * Q\Auth object
     * @var Auth
     */
    protected $Auth;
    
    /**
     * Original remote address
     * @var string
     */
    protected $remote_addr;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        
        if (isset($_SERVER['REMOTE_ADDR'])) $this->remote_addr = $_SERVER['REMOTE_ADDR'];
        
        $this->Auth->loginRequired = true;
        $this->Auth->checksumPassword = true;
        $this->Auth->checksumClientIp = true;
        $this->Auth->passwordCrypt = 'md5';
        $this->Auth->checksumCrypt = 'md5:secret=s3cret';
        $this->Auth->store = 'env';
        $this->Auth->storeAttemps = 'var';
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->Auth = null;
        
        $_SERVER['REMOTE_ADDR'] = $this->remote_addr;
        parent::tearDown();
    }

    
    /**
     * Tests if singleton is created
     */
    public function testSingleton()
    {
        $this->assertType('Q\Auth', $this->Auth);
    }

    /**
     * Tests Auth->authUser() for result OK
     */
    public function testAuthUser()
    {
        $code = 0;
        $user = $this->Auth->authUser('monkey', 'mark');
        
        $this->assertType('Q\Auth_SimpleUser', $user);
        $this->assertEquals(1, $user->getId(), 'getId()');
        $this->assertEquals('Mark Monkey', $user->getFullname());
        $this->assertEquals('monkey', $user->getUsername());
        $this->assertEquals(md5('mark'), $user->getPassword());
        $this->assertEquals(array('primate'), $user->getRoles());
    }

    /**
     * Tests Auth->authUser() for result UNKNOWN_USER
     */
    public function testAuthUser_UNKNOWN_USER()
    {
        $code = 0;
        $user = $this->Auth->authUser('wolf', 'willem');
        $this->assertEquals($user, Auth::UNKNOWN_USER);
    }

    /**
     * Tests Auth->authUser() for result INCORRECT_PASSWORD
     */
    public function testAuthUser_INCORRECT_PASSWORD()
    {
        $code = 0;
        $user = $this->Auth->authUser('monkey', 'rudolf');
        $this->assertEquals($user, Auth::INCORRECT_PASSWORD);
    }

    
    /**
     * Tests Auth->fetchUser() for result OK
     */
    public function testFetchUser()
    {
        $user = $this->Auth->fetchUser(1);
        
        $this->assertType('Q\Auth_SimpleUser', $user);
        $this->assertEquals(1, $user->getId(), 'getId()');
        $this->assertEquals('Mark Monkey', $user->getFullname());
        $this->assertEquals('monkey', $user->getUsername());
        $this->assertEquals(md5('mark'), $user->getPassword());
        $this->assertEquals(array('primate'), $user->getRoles());
    }

    /**
     * Tests Auth->fetchUser() for result UNKNOWN_USER
     */
    public function testFetchUser_UNKNOWN_USER()
    {
        $user = $this->Auth->fetchUser(9999);
        $this->assertNull($user);
    }

    /**
     * Tests Auth->fetchUser() with active INACTIVE_USER
     */
    public function testFetchUser_INACTIVE_USER()
    {
        $user = $this->Auth->fetchUser(2);
        
        $this->assertType('Q\Auth_SimpleUser', $user);
        $this->assertEquals(2, $user->getId(), 'getId()');
        $this->assertEquals('Ben Baboon', $user->getFullname());
        $this->assertEquals('baboon', $user->getUsername());
        $this->assertEquals(md5('ben'), $user->getPassword());
        $this->assertEquals(array('ape' , 'primate'), $user->getRoles());
        $this->assertFalse($user->isActive(), 'active');
    }

    
    /**
     * Tests Auth->login()
     */
    public function testLogin()
    {
        $this->Auth->login('monkey', 'mark');
        $user = $this->Auth->user();
        
        $this->assertTrue($this->Auth->isLoggedIn());
        $this->assertType('Q\Auth_SimpleUser', $user);
        $this->assertEquals(1, $user->getId(), 'getId()');
        $this->assertEquals('Mark Monkey', $user->getFullname());
        $this->assertEquals('monkey', $user->getUsername());
        $this->assertEquals(md5('mark'), $user->getPassword());
        $this->assertEquals(array('primate'), $user->getRoles());
    }

    /**
     * Tests Auth->authUser() for result UNKNOWN_USER
     */
    public function testLogin_UNKNOWN_USER()
    {
        $this->setExpectedException('Q\Auth_Login_Exception', null, Auth::UNKNOWN_USER);
        $this->Auth->login('wolf', 'willem');
        
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertNull($this->Auth->user()->getId(), 'getId()');
        $this->assertEquals('wolf', $this->Auth->user()->getUsername());
    }

    /**
     * Tests Auth->authUser() for result INCORRECT_PASSWORD
     */
    public function testLogin_INCORRECT_PASSWORD()
    {
        $this->setExpectedException('Q\Auth_Login_Exception', null, Auth::UNKNOWN_USER);
        $this->Auth->login('monkey', 'rudolf');
        
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(1, $this->Auth->user()->getId(), 'getId()');
        $this->assertNull($this->Auth->user()->getUsername(), 'monkey');
    }

    /**
     * Tests Auth->authUser() for result INACTIVE_USER
     */
    public function testLogin_INACTIVE_USER()
    {
        $this->setExpectedException('Q\Auth_Login_Exception', null, Auth::INACTIVE_USER);
        $this->Auth->login('baboon', 'ben');
        
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(2, $this->Auth->user()->getId(), 'getId()');
    }

    /**
     * Tests Auth->authUser() for result getPassword()_EXPIRED
     */
    public function testLogin_PASSWORD_EXPIRED()
    {
        $this->setExpectedException('Q\Auth_Login_Exception', null, Auth::PASSWORD_EXPIRED);
        $this->Auth->login('gorilla', 'george');
        
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(3, $this->Auth->user()->getId(), 'getId()');
    }

    
    /**
     * Tests Auth->logout()
     */
    public function testLogout()
    {
        $this->Auth->login('monkey', 'mark');
        $this->assertTrue($this->Auth->isLoggedIn());
        $this->assertEquals(1, $this->Auth->user()->getId(), 'getId()');
        
        $this->setExpectedException('Q\Auth_Session_Exception');
        $this->Auth->logout();
        
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(1, $this->Auth->user()->getId(), 'getId()');
    }

    
    /**
     * Tests Auth->start() for result OK
     */
    public function testStart()
    {
        $_ENV['Q_AUTH__uid'] = 1;
        $_ENV['Q_AUTH__hash'] = md5(1 . md5('mark') . 's3cret');
        
        $this->Auth->loginRequired = true;
        
        $this->Auth->start();
        $this->assertTrue($this->Auth->isLoggedIn());
        
        $user = $this->Auth->user();
        $this->assertType('Q\Auth_SimpleUser', $user);
        $this->assertEquals(1, $user->getId(), 'getId()');
        $this->assertEquals('Mark Monkey', $user->getFullname());
        $this->assertEquals('monkey', $user->getUsername());
        $this->assertEquals(md5('mark'), $user->getPassword());
        $this->assertEquals(array('primate'), $user->getRoles());
    }

    /**
     * Tests Auth->start() with no session
     */
    public function testStart_NoSession()
    {
        $this->setExpectedException('Q\Auth_Session_Exception');
        $this->Auth->start();
        
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNull($this->Auth->user(), 'user');
    }

    /**
     * Tests Auth->start() without required login
     */
    public function testStart_NoLoginRequired()
    {
        $this->Auth->loginRequired = false;
        $result = $this->Auth->start();
        
        $this->assertEquals(Auth::NO_SESSION, $result, 'result code');
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNull($this->Auth->user(), 'user');
    }

    /**
     * Tests Auth->start() for result OK
     */
    public function testStart_UNKNOWN_USER()
    {
        $_ENV['Q_AUTH__uid'] = 7;
        $_ENV['Q_AUTH__hash'] = md5(7 . 's3cret');

        $this->Auth->checksumPassword = false;
        
        $this->setExpectedException('Q\Auth_Session_Exception', null, Auth::UNKNOWN_USER);
        $this->Auth->start();
        
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNull($this->Auth->user(), 'user');
    }

    /**
     * Tests Auth->start() for result INACTIVE_USER
     */
    public function testStart_INACTIVE_USER()
    {
        $_ENV['Q_AUTH__uid'] = 2;
        $_ENV['Q_AUTH__hash'] = md5(2 . md5('ben') . 's3cret');
        
        $this->setExpectedException('Q\Auth_Session_Exception', null, Auth::INACTIVE_USER);
        $this->Auth->start();
        
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(2, $this->Auth->user()->getId(), 'getId()');
        $this->assertFalse((bool) $this->Auth->user()->active, 'active');
    }

    /**
     * Tests Auth->start() for result INACTIVE_USER
     */
    public function testStart_NoLoginRequired_INACTIVE_USER()
    {
        $_ENV['Q_AUTH__uid'] = 2;
        $_ENV['Q_AUTH__hash'] = md5(2 . md5('ben') . 's3cret');
        
        $this->Auth->loginRequired = false;
        $result = $this->Auth->start();
        
        $this->assertEquals(Auth::INACTIVE_USER, $result, 'result code');
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(2, $this->Auth->user()->getId(), 'getId()');
    }

    /**
     * Tests Auth->start() without required login for result getPassword()_EXPIRED
     */
    public function testStart_PASSWORD_EXPIRED()
    {
        $_ENV['Q_AUTH__uid'] = 3;
        $_ENV['Q_AUTH__hash'] = md5(3 . md5('george') . 's3cret');
        
        $result = $this->Auth->start();
        
        $this->assertEquals(Auth::OK, $result, 'result code');
        $this->assertTrue($this->Auth->isLoggedIn());
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(3, $this->Auth->user()->getId(), 'getId()');
    }

    /**
     * Tests Auth->start() with an incorrect hash
     */
    public function testStart_INVALID_SESSION()
    {
        $_ENV['Q_AUTH__uid'] = 1;
        $_ENV['Q_AUTH__hash'] = "abc";
        
        $this->setExpectedException('Q\Auth_Session_Exception', null, Auth::INVALID_CHECKSUM);
        $this->Auth->start();
        
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(1, $this->Auth->user()->getId(), 'getId()');
    }

    /**
     * Tests Auth->start() with an incorrect hash
     */
    public function testStart_INVALID_SESSION_NoHash()
    {
        $_ENV['Q_AUTH__uid'] = 1;
        
        $this->setExpectedException('Q\Auth_Session_Exception', null, Auth::INVALID_CHECKSUM);
        $this->Auth->start();
        
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(1, $this->Auth->user()->getId(), 'getId()');
    }

    
    /**
     * Tests Auth->isBlocked()
     */
    public function testIsBlocked()
    {
        $this->Auth->loginAttempts = 1;
        
        $this->assertFalse($this->Auth->isBlocked('10.0.0.1', true), '1st attempt');
        $this->assertTrue($this->Auth->isBlocked('10.0.0.1', true), '2nd attempt');
    }

    /**
     * Tests Auth->isBlocked()
     */
    public function testIsBlocked_Unblockable()
    {
        $this->Auth->loginAttempts = 1;
        
        $this->assertEquals(0, $this->Auth->isBlocked('127.0.0.1', true), '1st attempt');
        $this->assertEquals(0, $this->Auth->isBlocked('127.0.0.1', true), '2nd attempt');
        $this->assertEquals(0, $this->Auth->isBlocked('127.0.0.1', true), '3rd attempt');
    }

    /**
     * Tests Auth->isBlocked()
     */
    public function testLogin_Blocked()
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $this->Auth->loginAttempts = 1;
        $this->Auth->isBlocked('10.0.0.1', 5);
        
        $this->setExpectedException('Q\Auth_Login_Exception', null, Auth::HOST_BLOCKED);
        $this->Auth->login('monkey', 'mark');
    }

    
    /**
     * Tests Auth->authz()
     */
    public function testAuthz()
    {
        $this->Auth->login('monkey', 'mark');
        $this->Auth->authz('primate');
    }

    /**
     * Tests Auth->authz() where authorization fails
     */
    public function testAuthz_Fail()
    {
        $this->Auth->login('monkey', 'mark');
        
        $this->setExpectedException('Q\Authz_Exception', "User 'monkey' is not in role 'ape'.");
        $this->Auth->authz('ape');
    }

    /**
     * Tests Auth->authz() with multiple getRoles() where authorization fails
     */
    public function testAuthz_FailMultiple()
    {
        $this->Auth->login('monkey', 'mark');
        
        $this->setExpectedException('Q\Authz_Exception', "User 'monkey' is not in roles 'ape', 'pretty'.");
        $this->Auth->authz('primate', 'ape', 'pretty');
    }

    /**
     * Tests Auth->authz() whith no session
     */
    public function testAuthz_NoSession()
    {
        $this->setExpectedException('Q\Auth_Session_Exception', "User is not logged in.", Auth::NO_SESSION);
        $this->Auth->authz('primate');
    }
    
    /**
     * Tests Auth->authz() whith no session
     */
    public function testAuthz_INACTIVE_USER()
    {
        try {
            $this->Auth->login('baboon', 'ben');
        } catch (Q\Auth_Login_Exception $e) {}
        
        $this->setExpectedException('Q\Auth_Session_Exception', "User is not logged in.", Auth::NO_SESSION);
        $this->Auth->authz('primate');
    }    
}

