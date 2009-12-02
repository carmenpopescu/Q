<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';
require_once 'Q/Transform/Decompress/Bzip.php';

/**
 * Compress a string into bzip2 encoded data
 * Options : 
 * level            Blocksize used during compression - a number from 1 to 9 with 9 giving the best compression. Default is 4.
 * workfactor       Controls how the compression phase behaves when presented with worst case, highly repetitive, input data. The value can be between 0 and 250 with 0 being a special case. Default is 0.
 *
 * @package Transform
 */
class Transform_Compress_Bzip extends Transform
{
    /**
     * Default extension for file with serialized data.
     * @var string
     */
    public $ext = 'bz';
    
    /**
     * Blocksize used during compression.
     * @var int
     */
    public $level = 4;
        
    /**
     * Controls how the compression phase behaves.
     * @var int
     */
    public $workfactor = 0;
    
    /**
     * Get a transformer that does the reverse action.
     *
     * @param Transformer $chain
     * @return Transformer
     */
    public function getReverse($chain=null)
    {
        $ob = new Transform_Decompress_Bzip($this);
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
        
        if (!is_int($this->level) || $this->level < 1 || $this->level > 9) throw new Exception("Unable to compress data : Unknown encoding level '{$this->level}'.");
        if (!is_int($this->workfactor) || $this->workfactor < 0 || $this->workfactor > 250) throw new Exception("Unable to compress data : Unknown workfactor '{$this->workfactor}'.");
        
        return bzcompress($data, $this->level, $this->workfactor);        
    }
}
