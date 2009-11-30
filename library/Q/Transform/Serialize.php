<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';
require_once 'Q/Transform/Unserialize.php';

/**
 * Serialize data.
 *
 * @package Transform
 */
class Transform_Serialize extends Transform
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
        $ob = new Transform_Unserialize($this);
        if ($chain) $ob->chainInput($chain);
        return $this->chainInput ? $this->chainInput->getReverse($ob) : $ob;
    }
	
	/**
     * Serialize data and return result.
     *
     * @param mixed $data
     * @return string
     */
    public function process($data)
    {
        if ($this->chainInput) $data = $this->chainInput->process($data);

        if ($data instanceof Fs_Node) $data = file_get_contents($data);        
        if(is_resource($data)) throw new Transform_Exception("Unable to serialize : incorrect data type.");

        return serialize($data);
    }
}
