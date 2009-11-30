<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';
require_once 'Q/Transform/Compress/Bzip.php';

/**
 * Decompresses bzip2 encoded data
 * Options : 
 * small    If TRUE, an alternative decompression algorithm will be used which uses less memory (the maximum memory requirement drops to around 2300K) but works at roughly half the speed.
 *
 * @package Transform
 */
class Transform_Decompress_Bzip extends Transform
{
    /**
     * Default extension for file with serialized data.
     * @var string
     */
    public $ext = 'bzip';
    
    /**
     * Alternative decompression algorithm which uses less memory.
     * @var boolen
     */
    public $small = 0;
        
    /**
     * Get a transformer that does the reverse action.
     *
     * @param Transformer $chain
     * @return Transformer
     */
    public function getReverse($chain=null)
    {
        $ob = new Transform_Compress_Bzip($this);
        if ($chain) $ob->chainInput($chain);
        return $this->chainInput ? $this->chainInput->getReverse($ob) : $ob;
    }
	
	/**
     * Compress a string
     *
     * @param mixed    $data
     * @return string
     */
    public function process($data)
    {
        if ($this->chainInput) $data = $this->chainInput->process($data);
        
        if ($data instanceof Fs_File) $data = $data->getContents();
                
        return bzdecompress($data, $this->small);        
    }
}
