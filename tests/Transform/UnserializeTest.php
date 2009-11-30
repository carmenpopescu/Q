<?php
use Q\Transform_Unserialize, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Unserialize.php';

/**
 * Transform_Unserialize test case.
 */
class Transform_UnserializeTest extends PHPUnit_Framework_TestCase 
{
	/**
	 * Tests Transform_Unserialize->process()
	 */
	public function testProcess() 
	{
		$transform = new Transform_Unserialize();
		$contents = $transform->process('a:2:{s:1:"a";a:1:{s:1:"c";s:3:"abc";}s:1:"d";s:1:"e";}');

		$this->assertType('Q\Transform_Unserialize', $transform);
		$this->assertEquals(array('a'=>array('c'=>'abc'), 'd'=>'e'), $contents);
	}
        
    /**
     * Tests Transform_Unserialize->process() with a chain
     */
    public function testProcess_Chain() 
    {
    	$mock = $this->getMock('Q\Transform', array('process'));
    	$mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue('a:2:{s:1:"a";a:1:{s:1:"c";s:3:"abc";}s:1:"d";s:1:"e";}'));
    	
        $transform = new Transform_Unserialize();
        $transform->chainInput($mock);
        $contents = $transform->process('test');

        $this->assertType('Q\Transform_Unserialize', $transform);
        $this->assertEquals(array('a'=>array('c'=>'abc'), 'd'=>'e'), $contents);
    }
	
	/**
	 * Tests Transform_Unserialize->output()
	 */
	public function testOutput() 
	{
        $this->setExpectedException('Q\Exception', "Transformation returned a non-scalar value of type 'array'");
	    $transform = new Transform_Unserialize();
        $transform->output('a:2:{s:1:"a";a:1:{s:1:"c";s:3:"abc";}s:1:"d";s:1:"e";}');
	}
	
	/**
	 * Tests Transform_Unserialize->save()
	 */
	public function testSave() 
	{
        $this->setExpectedException('Q\Exception', "Transformation returned a non-scalar value of type 'array'");
	    $transform = new Transform_Unserialize();
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
		$transform->save($this->tmpfile, 'a:2:{s:1:"a";a:1:{s:1:"c";s:3:"abc";}s:1:"d";s:1:"e";}');		
	}

	/**
	 * Tests Transform_Unserialize->getReverse()
	 */
	public function testGetReverse() 
	{
		$transform = new Transform_Unserialize();
        $reverse = $transform->getReverse();

        $this->assertType('Q\Transform_Serialize', $reverse);
	}
        
    /**
     * Tests Transform_Unserialize->getReverse() with a chain
     */
    public function testGetReverse_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Serialize'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform = new Transform_Unserialize();
        $transform->chainInput($mock);
        
        $this->assertEquals('reverse of mock transformer', $transform->getReverse());
    }

    /**
     * Tests Transform_Unserialize->getReverse() with a chain
     */
    public function testGetReverse_ChainDouble() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Serialize'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform1 = new Transform_Unserialize();
        $transform2 = new Transform_Unserialize();
        
        $transform2->chainInput($mock);
        $transform1->chainInput($transform2);
        
        $this->assertEquals('reverse of mock transformer', $transform1->getReverse());
    }
}
