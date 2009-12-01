<?php
use Q\foo;

require_once 'TestHelper.php';
require_once 'Q/misc.php';


/** @ignore */
interface tst_misc_Q
{}

/** @ignore */
class tst_misc_A
{
    function __toString() { return "Test"; }
}

/** @ignore */
class tst_misc_AQ extends tst_misc_A implements tst_misc_Q
{}


/**
 * Test case for misc functions of Q
 */
class MiscTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test load_class()
     */

    function test_load_class()
    {
        $this->assertTrue(Q\load_class('tst_misc_AQ'));
    }

    /**
     * Test load_class() exception 
     */
    function test_load_class_Exception()
    {
        $this->setExpectedException('Exception', "The class '\\tst_misc_AQ' should be specified without the '\\' at the beginning.");
        Q\load_class('\tst_misc_AQ');
    }
    
    /**
     * Test load_class() security exception 
     */
    function test_load_class_SecurityException()
    {
        $this->setExpectedException('Q\SecurityException', "Illegal load file for class '123tst_misc_AQ': Illigal classname. Is someone trying to hack?");
        Q\load_class('123tst_misc_AQ');
    }

	/**
	 * Test unquote()
	 */
	function test_unquote()
	{
		$this->assertSame('test', Q\unquote('"test"'));
		$this->assertSame('test', Q\unquote('\'test\''));
		$this->assertSame('"test', Q\unquote('"test'));
		$this->assertSame('es', Q\unquote('test', 't'));
		$this->assertSame('test', Q\unquote('$test$', 't$'));
	}

	/**
	 * Test parse_key()
	 */
	function test_parse_key()
	{
	    $array = array();
	    
	    Q\parse_key('test1', 10, $array);
	    $this->assertArrayHasKey('test1', $array);
	    $this->assertSame(10, $array['test1']);
	    
	    Q\parse_key('test2[10]', 'atext', $array);
	    $this->assertArrayHasKey('test2', $array);
	    $this->assertArrayHasKey(10, $array['test2']);
	    $this->assertSame('atext', $array['test2'][10]);

	    Q\parse_key('test2[]', 27, $array);
	    $this->assertArrayHasKey('test2', $array);
	    $this->assertArrayHasKey(11, $array['test2']);
	    $this->assertSame(27, $array['test2'][11]);
	    
	    Q\parse_key('test3["red"][12]', 'qqq', $array);
	    $this->assertArrayHasKey('test3', $array);
	    $this->assertArrayHasKey('red', $array['test3']);
	    $this->assertArrayHasKey(12, $array['test3']['red']);
	    $this->assertSame('qqq', $array['test3']['red'][12]);
	    
	    unset($GLOBALS['__parse_key__Test']);
	    Q\parse_key('__parse_key__Test', 10);
	    $this->assertArrayHasKey('__parse_key__Test', $GLOBALS);
	    $this->assertSame(10, $GLOBALS['__parse_key__Test']);
	}
	
    /**
     * Test split_set()
     */
    function test_split_set()
    {
        $this->assertSame(array('test', 'adam'), Q\split_set(';', 'test;adam'), "Simple");
        $this->assertSame(array('test', 'adam'), Q\split_set(';', 'test;"adam"'), "Quoted");
        $this->assertSame(array('test', '"adam"'), Q\split_set(';', 'test;"adam"', false), "Don't unquote");
        $this->assertSame(array('test', 'adam', 'qqq', 'def'), Q\split_set(';~&^', 'test;adam~"qqq"^def'), "Other seperators");
    }
    
    /**
     * Test split_set_assoc()
     */
    function test_split_set_assoc()
    {
        $this->assertSame(array('a'=>'test', 'x'=>'adam'), Q\split_set(';', 'a=test;x=adam'), "Simple");
        $this->assertSame(array('a'=>'test', 'x'=>'adam;eva'), Q\split_set(';', 'a=test;x="adam;eva"'), "Quoted");
        $this->assertSame(array('a'=>'test', 'x'=>'"adam"'), Q\split_set(';', 'a=test;x="adam"', false), "Don't unquote");
        $this->assertSame(array('a'=>'test', 'x'=>'adam', 'dd'=>'qqq', 'abc'=>'def'), Q\split_set(';~&^', 'a=test;x=adam~dd="qqq"^abc=def'), "Other seperators");
    }

    /**
     * Test split_set_assoc() with both ordered and associated parts
     */
    function test_split_set_assoc_Mixed()
    {
        $this->assertSame(array('a'=>'test', 'abc', 'x'=>'adam'), Q\split_set(';', 'a=test;abc;x=adam'), "Simple");
        $this->assertSame(array('a'=>'test', 'abc;def', 'x'=>'adam;eva', 'qqq', 'def'), Q\split_set(';', 'a=test;"abc;def";x="adam;eva";"qqq";def'), "Quoted");
        $this->assertSame(array('a'=>'test', 'abc', 'x'=>'"adam"', '"qqq"', 'def'), Q\split_set(';', 'a=test;abc;x="adam";"qqq";def', false), "Don't unquote");
        $this->assertSame(array('a'=>'test', 'abc', 'x'=>'adam', 'qqq', 'def'), Q\split_set(';~&^', 'a=test;abc;x=adam~"qqq"^def'), "Other seperators");
    }
    
    /**
     * Test extract_dsn()
     */
    function test_extract_dsn()
    {
        $this->assertSame(array('driver'=>'mysql', 'host'=>'localhost', 'port'=>'3306', 'username'=>'myuser', 'password'=>'mypass'), Q\extract_dsn('mysql:host=localhost;port=3306;username=myuser;password=mypass'), "Simple");
        $this->assertSame(array('driver'=>'mysql', 'host'=>'localhost', 'port'=>'3306', 'username'=>'myuser', 'password'=>'ad;er=dee'), Q\extract_dsn('mysql:host=localhost;port=3306;username=myuser;password="ad;er=dee"'), "Quoted");
    }
    
    /**
     * Test binset()
     */
    function test_binset()
    {
        $this->assertSame(array(1=>3, 2=>7, 4=>5), Q\binset(array(3, 7, 5)));
    }
    
    /**
     * Test split_binset()
     */
    function test_split_binset()
    {
        $this->assertSame(array(2, 8), Q\split_binset(10));
    }
    
    /**
	 * Test array_get_column()
	 */
	function test_array_get_column()
	{
		$this->assertSame(array(9, 2, 6), Q\array_get_column(array(array(0, 9, 8), array(1, 2, 3, 4), array(6, 6, 6)), 1), "Ordered arrays");
		$this->assertSame(array(9, 2, 6), Q\array_get_column(array(array('a'=>0, 'b'=>9, 'c'=>8), array('x'=>1, 'b'=>2, 'z'=>3, 'q'=>4), array('r'=>6, 'e'=>6, 'b'=>6)), 'b'), "Associated arrays");
		$this->assertSame(array('abz'=>9, 'xyz'=>2, 'q'=>6), Q\array_get_column(array('abz'=>array(0, 9, 8), 'xyz'=>array(1, 2, 3, 4), 'q'=>array(6, 6, 6)), 1), "Associated with ordered arrays");
		$this->assertSame(array('abz'=>9, null, 6), Q\array_get_column(array('abz'=>array('a'=>0, 'b'=>9, 'c'=>8), array('x'=>1, 'z'=>3, 'q'=>4), array('r'=>6, 'e'=>6, 'b'=>6)), 'b'), "Mixed arrays");
	}

	/**
	 * Test array_filter_keys()
	 */
	function test_array_filter_keys()
	{
		$this->assertSame(array('a'=>'test', 'x'=>'adam'), Q\array_filter_keys(array('a'=>'test', 'c'=>'su', 'x'=>'adam', '$_dd'=>'do'), array('a', 'b', 'x')));
	}

    /**
     * Test array_merge_recursive()
     */
    function test_array_merge_recursive()
    {
        $this->assertSame(array('a'=>'something', 'c'=>'su', 'x'=>'adam', '$_dd'=>'do', 'b', 'x'), Q\array_merge_recursive(array('a'=>'test', 'c'=>'su', 'x'=>'adam', '$_dd'=>'do'), array('b', 'a'=>'something', 'x')));
    }

    /**
     * Test array_merge_recursive() exception : first argument not an array
     */
    function test_array_merge_recursive_Exception()
    {
        $this->setExpectedException('Exception', "Argument #1 of Q\array_merge_recursive isn't an array.");
        Q\array_merge_recursive('a', array('b', 'a'=>'test', 'x'));
    }
    
    /**
     * Test array_chunk_assoc()
     */
    function test_array_chunk_assoc()
    {
        $this->assertEquals(array('a'=>10, 'b'=>"test", 'c'=>'xyz'), Q\array_chunk_assoc(array('test.a'=>10, 'hallo'=>"abc", "Q rules", 'test.b'=>"test", 'test.c'=>'xyz'), 'test'));
        $this->assertEquals(array('a'=>10, 'b'=>"test", 'c'=>'xyz'), Q\array_chunk_assoc(array('test::a'=>10, 'hallo'=>"abc", "Q rules", 'test::b'=>"test", 'test::c'=>'xyz'), 'test', '::'), "using :: seperator");
        
        $this->assertEquals(array('a'=>10, 'b'=>"test", 'c'=>'xyz'), Q\array_chunk_assoc(array('test'=>array('a'=>10, 'b'=>"test", 'c'=>'xyz'), 'hallo'=>"abc", "Q rules"), 'test'));
        $this->assertEquals(array('a'=>10, 'b'=>"test", 'c'=>'xyz'), Q\array_chunk_assoc(array('test'=>array('a'=>10, 'b'=>"test"), 'hallo'=>"abc", "Q rules", 'test.c'=>'xyz'), 'test'));
    }
    
    /**
     * Test array_combine_assoc()
     */
    function test_array_combine_assoc()
    {
        $this->assertEquals(array('test.a'=>10, 'test.b'=>"test", 'test.c'=>'xyz'), Q\array_combine_assoc(array('a'=>10, 'b'=>"test", 'c'=>'xyz'), 'test'));
        $this->assertEquals(array('test::a'=>10, 'test::b'=>"test", 'test::c'=>'xyz'), Q\array_combine_assoc(array('a'=>10, 'b'=>"test", 'c'=>'xyz'), 'test', '::'));
        
        $this->assertEquals(array('j.a'=>10, 'j.b'=>"test", 'j.c'=>'xyz', 'hallo'=>"abc", "Q rules"), Q\array_combine_assoc(array('j'=>array('a'=>10, 'b'=>"test", 'c'=>'xyz'), 'hallo'=>"abc", "Q rules")));
        $this->assertEquals(array('test.j.a'=>10, 'test.j.b'=>"test", 'test.j.c'=>'xyz', 'test.hallo'=>"abc",'test.0'=>"Q rules"), Q\array_combine_assoc(array('j'=>array('a'=>10, 'b'=>"test", 'c'=>'xyz'), 'hallo'=>"abc", "Q rules"), 'test'));
    }
    
    /**
     * Test refsort()
     */
    function test_refsort()
    {
        $x = Q\refsort(array('p2'=>array('ch1', 'ch3', 'ch4'), 'ch1'=>null, 'ch3'=>null, 'p1'=>array('ch1', 'p2')));
        $this->assertSame(array('ch3'=>null, 'ch1'=>null, 'p2'=>array('ch1', 'ch3', 'ch4'), 'p1'=>array('ch1', 'p2')), $x);
        
        $x = Q\refsort(array('ch3'=>null, 'p2'=>array('ch1', 'ch3', 'ch4'), 'ch1'=>null, 'p1'=>array('ch1', 'p2')));
        $this->assertSame(array('ch1'=>null, 'ch3'=>null, 'p2'=>array('ch1', 'ch3', 'ch4'), 'p1'=>array('ch1', 'p2')), $x);
        
        $x = Q\refsort(array('ch1'=>null, 'ch3'=>null, 'p2'=>array('ch1', 'ch3', 'ch4'), 'p1'=>array('ch1', 'p2')), SORT_DESC);
        $this->assertSame(array('p1'=>array('ch1', 'p2'), 'p2'=>array('ch1', 'ch3', 'ch4'), 'ch1'=>null, 'ch3'=>null), $x);
    }

    /**
     * Test refsort() with cross-reference.
     * Should give warning and not hang.
     */
    function test_refsort_crossreference()
    {
        set_time_limit(5); // Should not deadloop, but..
        $order = array('ch1'=>null, 'ch3'=>array('p2'), 'p2'=>array('ch1', 'ch3', 'ch4'), 'p1'=>array('ch1', 'p2'));
        $x = @Q\refsort($order);
        set_time_limit(0);
        
        $err = error_get_last();
        $this->assertEquals("Unable to sort array because of cross-reference.", $err['message']);
        $this->assertEquals($order, $x);
    }
    
    /**
	 * Test implode_recursive()
	 */
    function test_implode_recursive()
    {
        $this->assertSame("a, b, (1, 2, 3), c, (10, 11, (I, II, III), 12)", Q\implode_recursive(', ', array('a', 'b', array(1, 2, 3), 'c', array(10, 11, array('I', 'II', 'III'), 12))));
    }

    /**
	 * Test implode_recursive()
	 */
    function test_implode_assoc()
    {
        $this->assertSame('a=test, b=another test, k.I=x, k.II=y, k.III="z=10", k.IV.abc=22, k.IV.def=33, c=go test', Q\implode_assoc(', ', array('a'=>"test", 'b'=>"another test", 'k'=>array('I'=>"x", 'II'=>"y", 'III'=>"z=10", 'IV'=>array('abc'=>22, 'def'=>33)), 'c'=>"go test")));
        $this->assertSame('a=test, b=another test, k=(I=x, II=y, III="z=10", IV=(abc=22, def=33)), c=go test', Q\implode_assoc(', ', array('a'=>"test", 'b'=>"another test", 'k'=>array('I'=>"x", 'II'=>"y", 'III'=>"z=10", 'IV'=>array('abc'=>22, 'def'=>33)), 'c'=>"go test"), '%s=%s', '%s=(', ')'));
        $this->assertSame('a:"test" + b:"another test" + k:[I:"x" + II:"y" + III:"z=10" + IV:[abc:"22" + def:"33"]] + c:"go test"', Q\implode_assoc(' + ', array('a'=>"test", 'b'=>"another test", 'k'=>array('I'=>"x", 'II'=>"y", 'III'=>"z=10", 'IV'=>array('abc'=>22, 'def'=>33)), 'c'=>"go test"), '%s:%s', '%s:[', ']', true));
    }
    
	/**
	 * Test array_map_recursive()
	 */
	function test_array_map_recursive()
	{
		$this->assertSame(array('"a"', 'b', array('x', '"y"', 'z'), '"c'), Q\array_map_recursive('stripslashes', array('\"a\"', 'b', array('x', '\"y\"', 'z'), '\"c')));
	}
	
    /**
     * Test running_main()
     */
    function test_running_main()
    {
        $this->assertFalse(Q\running_main());
    }
    
	/**
	 * Test var_give()
	 */
	function test_var_give()
	{
	    $this->assertEquals("10", Q\var_give(10, true));
	    $this->assertEquals("'test'", Q\var_give("test", true));
	    $this->assertEquals("false", Q\var_give(false, true));

	    $this->setExpectedException('Exception', "Won't serialize an object: Trying to serialize " . __CLASS__ . ", class doesn't have a __set_state() method.");
	    $this->assertContains("(" . __CLASS__  . ")", Q\var_give($this, true));
	    $this->assertEquals("(tst_misc_A) Test", Q\var_give(new tst_misc_A("Test"), true));
	    
	    $this->assertEquals("(object) array ( 0 => 10, 'a' => 'test1' )", Q\var_give((object)array(10, 'a'=>'test1'), true));
	    $this->assertEquals("array ( 0 => 10, 'a' => 'test1', 'b' => 'another', 'c' => array ( 0 => 10, 1 => 20, 2 => (tst_misc_A) Test ) )", Q\var_give(array(10, 'a'=>'test1', 'b'=>'another', 'c'=>array(10, 20, new tst_misc_A("Test"))), true));
	}
	
    /**
     * Test var_give() exception : circular reference
     */
    function test_var_give_Exception()
    {
        $this->setExpectedException('Exception', "Skip circular reference");
        $passed = array(10, 20);
        Q\var_give(10, true, false, $passed);
        
    }
	
	/**
	 * Test serialize_trace()
	 */
	function test_serialize_trace()
	{
	    $debug = array(
	      array('function'=>'xyz'),
	      array('file'=>'/var/www/lib.php', 'line'=>334, 'class'=>'TestClass', 'type'=>'->', 'function'=>'DoItRight'),
	      array('file'=>'/var/www/index.php', 'line'=>523, 'args'=>array(222, "left"), 'function'=>'do_something')
        );

        $expect = array(
		  "unknown (unknown): xyz()",
		  "/var/www/lib.php (334): TestClass->DoItRight()",
		  "/var/www/index.php (523): do_something(222, 'left')"
        );

        $this->assertEquals("#0 $expect[0]\n#1 $expect[1]\n#2 $expect[2]", Q\serialize_trace($debug));
        $this->assertEquals("#0 $expect[1]\n#1 $expect[2]", Q\serialize_trace($debug, 1));
        $this->assertEquals("#0 $expect[2]", Q\serialize_trace($debug, array('file'=>'/var/www/lib.php', 'line'=>334)));
	}
}
