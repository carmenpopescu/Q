<?php
use Q\Transform_Unserialize_Serial, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Unserialize/Serial.php';

/**
 * Transform_Unserialize_Serial test case.
 */
class Transform_Unserialize_SerialTest extends PHPUnit_Framework_TestCase 
{
	/**
	 * Tests Transform_Unserialize_Serial->process()
	 */
	public function testProcess() 
	{
		$transform = new Transform_Unserialize_Serial();
		$contents = $transform->process('a:2:{s:1:"a";a:1:{s:1:"c";s:3:"abc";}s:1:"d";s:1:"e";}');

		$this->assertType('Q\Transform_Unserialize_Serial', $transform);
		$this->assertEquals(array('a'=>array('c'=>'abc'), 'd'=>'e'), $contents);
	}
        
    /**
     * Tests Transform_Unserialize_Serial->process() with a chain
     */
    public function testProcess_Chain() 
    {
    	$mock = $this->getMock('Q\Transform', array('process'));
    	$mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue('a:2:{s:1:"a";a:1:{s:1:"c";s:3:"abc";}s:1:"d";s:1:"e";}'));
    	
        $transform = new Transform_Unserialize_Serial();
        $transform->chainInput($mock);
        $contents = $transform->process('test');

        $this->assertType('Q\Transform_Unserialize_Serial', $transform);
        $this->assertEquals(array('a'=>array('c'=>'abc'), 'd'=>'e'), $contents);
    }
	
	/**
	 * Tests Transform_Unserialize_Serial->output()
	 */
	public function testOutput() 
	{
        $this->setExpectedException('Q\Exception', "Transformation returned a non-scalar value of type 'array'");
	    $transform = new Transform_Unserialize_Serial();
        $transform->output('a:2:{s:1:"a";a:1:{s:1:"c";s:3:"abc";}s:1:"d";s:1:"e";}');
	}
	
	/**
	 * Tests Transform_Unserialize_Serial->save()
	 */
	public function testSave() 
	{
        $this->setExpectedException('Q\Exception', "Transformation returned a non-scalar value of type 'array'");
	    $transform = new Transform_Unserialize_Serial();
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
		$transform->save($this->tmpfile, 'a:2:{s:1:"a";a:1:{s:1:"c";s:3:"abc";}s:1:"d";s:1:"e";}');		
	}

	/**
	 * Tests Transform_Unserialize_Serial->getReverse()
	 */
	public function testGetReverse() 
	{
		$transform = new Transform_Unserialize_Serial();
        $reverse = $transform->getReverse();

        $this->assertType('Q\Transform_Serialize_Serial', $reverse);
	}
        
    /**
     * Tests Transform_Unserialize_Serial->getReverse() with a chain
     */
    public function testGetReverse_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Serialize_Serial'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform = new Transform_Unserialize_Serial();
        $transform->chainInput($mock);
        
        $this->assertEquals('reverse of mock transformer', $transform->getReverse());
    }

    /**
     * Tests Transform_Unserialize_Serial->getReverse() with a chain
     */
    public function testGetReverse_ChainDouble() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Serialize_Serial'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform1 = new Transform_Unserialize_Serial();
        $transform2 = new Transform_Unserialize_Serial();
        
        $transform2->chainInput($mock);
        $transform1->chainInput($transform2);
        
        $this->assertEquals('reverse of mock transformer', $transform1->getReverse());
    }
}
