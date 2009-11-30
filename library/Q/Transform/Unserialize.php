<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';
require_once 'Q/Transform/Serialize.php';

/**
 * Unserialize data.
 *
 * @package Transform
 */
class Transform_Unserialize extends Transform
{
    /**
     * Default extension for file with serialized data.
     * @var string
     */
    public $ext;
    
    /**
     * Get a transformer that does the reverse action.
     *
     * @param Transformer $chain
     * @return Transformer
     */
    public function getReverse($chain=null)
    {
        $ob = new Transform_Serialize($this);
        if ($chain) $ob->chainInput($chain);
        return $this->chainInput ? $this->chainInput->getReverse($ob) : $ob;
    }
	
	/**
     * Unserialize data and return result.
     *
     * @param mixed $data
     * @return string
     */
    public function process($data)
    {
        if ($this->chainInput) $data = $this->chainInput->process($data);
        return unserialize($data);
    }
}
