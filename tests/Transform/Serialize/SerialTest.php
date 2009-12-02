<?php
use Q\Transform_Serialize_Serial, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Serialize/Serial.php';

/**
 * Transform_Serialize_Serial test case.
 */
class Transform_Serialize_SerialTest extends PHPUnit_Framework_TestCase 
{
	/**
	 * Tests Transform_Serialize_Serial->process()
	 */
	public function testProcess() 
	{
		$transform = new Transform_Serialize_Serial();
		$contents = $transform->process(array('a'=>array('c'=>'abc'), 'd'=>'e'));

		$this->assertType('Q\Transform_Serialize_Serial', $transform);
		$this->assertEquals('a:2:{s:1:"a";a:1:{s:1:"c";s:3:"abc";}s:1:"d";s:1:"e";}', $contents);
	}
        
    /**
     * Tests Transform_Serialize_Serial->process() with a chain
     */
    public function testProcess_Chain() 
    {
    	$mock = $this->getMock('Q\Transform', array('process'));
    	$mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue(array('a'=>array('c'=>'abc'), 'd'=>'e')));
    	
        $transform = new Transform_Serialize_Serial();
        $transform->chainInput($mock);
        $contents = $transform->process('test');

        $this->assertType('Q\Transform_Serialize_Serial', $transform);
        $this->assertEquals('a:2:{s:1:"a";a:1:{s:1:"c";s:3:"abc";}s:1:"d";s:1:"e";}', $contents);
    }
    
    /**
     * Tests Transform_Serialize_Serial->process() with invalid data
     */
    public function testProcess_Exception_InvalidData() 
    {
        $this->setExpectedException('Q\Transform_Exception', "Unable to serialize : incorrect data type.");
    	$transform = new Transform_Serialize_Serial();
        $contents = $transform->process(opendir(dirname(__FILE__)));
    }
	
	/**
	 * Tests Transform_Serialize_Serial->output()
	 */
	public function testOutput() 
	{
		$transform = new Transform_Serialize_Serial();
		ob_start();
		try{
    		$transform->output(array('a'=>array('c'=>'abc'), 'd'=>'e'));
    	} catch (Expresion $e) {
    	    ob_end_clean();
    	    throw $e;
    	}
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_Serialize_Serial', $transform);
        $this->assertEquals('a:2:{s:1:"a";a:1:{s:1:"c";s:3:"abc";}s:1:"d";s:1:"e";}', $contents);
	}
	
	/**
	 * Tests Transform_Serialize_Serial->save()
	 */
	public function testSave() 
	{
		$transform = new Transform_Serialize_Serial();
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
		$transform->save($this->tmpfile, array('a'=>array('c'=>'abc'), 'd'=>'e'));
		
        $this->assertType('Q\Transform_Serialize_Serial', $transform);
		$this->assertEquals('a:2:{s:1:"a";a:1:{s:1:"c";s:3:"abc";}s:1:"d";s:1:"e";}', file_get_contents($this->tmpfile));
	}

	/**
	 * Tests Transform_Serialize_Serial->getReverse()
	 */
	public function testGetReverse() 
	{
		$transform = new Transform_Serialize_Serial();
        $reverse = $transform->getReverse();

        $this->assertType('Q\Transform_Unserialize_Serial', $reverse);
	}
        
    /**
     * Tests Transform_Serialize_Serial->getReverse() with a chain
     */
    public function testGetReverse_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Unserialize_Serial'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform = new Transform_Serialize_Serial();
        $transform->chainInput($mock);
        
        $this->assertEquals('reverse of mock transformer', $transform->getReverse());
    }

    /**
     * Tests Transform_Serialize_Serial->getReverse() with a chain
     */
    public function testGetReverse_ChainDouble() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Unserialize_Serial'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform1 = new Transform_Serialize_Serial();
        $transform2 = new Transform_Serialize_Serial();
        
        $transform2->chainInput($mock);
        $transform1->chainInput($transform2);
        
        $this->assertEquals('reverse of mock transformer', $transform1->getReverse());
    }
}
