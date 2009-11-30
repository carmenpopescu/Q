<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';
require_once 'Q/Transform/Compress/Lzf.php';

/**
 * LZF decompression 
 *
 * @package Transform
 */
class Transform_Decompress_Lzf extends Transform
{
    /**
     * Default extension for file with serialized data.
     * @var string
     */
    public $ext = 'lzf';

    /**
     * Get a transformer that does the reverse action.
     *
     * @param Transformer $chain
     * @return Transformer
     */
    public function getReverse($chain=null)
    {
        $ob = new Transform_Compress_Lzf($this);
        if ($chain) $ob->chainInput($chain);
        return $this->chainInput ? $this->chainInput->getReverse($ob) : $ob;
    }
	
	/**
     * Lzf decompress
     *
     * @param mixed    $data
     * @return string
     */
    public function process($data)
    {
        if ($this->chainInput) $data = $this->chainInput->process($data);
        
        if ($data instanceof Fs_File) $data = $data->getContents();
                
        return lzf_decompress($data);        
    }
}
