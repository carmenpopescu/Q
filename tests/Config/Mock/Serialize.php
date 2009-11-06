<?php
use Q\Transform;

require_once 'Q/Transform.php';
require_once('Unserialize.php');

/**
 * Mock object for unserialize transformer used in Config unit tests.
 * 
 * @ignore
 */
class Config_Mock_Serialize extends Transform
{
    /**
     * Created transform mock objects
     * @var array
     */
    static public $created = array();
    
    /**
     * Input data process
     * @var mixed
     */
    public $in;
    
    /**
     * Return data
     * @var array
     */
    public $out;
    
    /**
     * Default extension for file with serialized data.
     * @var string
     */
    public $ext = 'mock';
    
    /**
     * Return the reverse object
     */
    public $reverse;
    
    /**
     * Class constructor
     */
    public function __construct($options=array())
    {
        self::$created[] = $this;
    }
    
    /**
     * Transform
     * 
     * @param mixed $data
     * @return array
     */
    public function process($data)
    {
        $this->in = (array)$data;
        $this->out = serialize((array)$data);
        return $this->out;
    }

    /**
     * Get a transformer that does the reverse action.
     * 
     * @param Transformer $chain
     * @return Transformer
     */
    public function getReverse($chain=null)
    {
        $ob = new Config_Mock_Unserialize($this);
        if ($chain) $ob->chainInput($chain);

        $this->reverse = $ob;
        
        return $this->chainInput ? $this->chainInput->getReverse($ob) : $ob;  
    }
}