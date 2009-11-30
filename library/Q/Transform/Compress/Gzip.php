<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';
require_once 'Q/Transform/Decompress/Gzip.php';

/**
 * Create a gzip compressed string
 * Options : 
 * method           possible values : 'deflate' to use gzdeflate, 'compress' to use gzcompress and 'encode' to use gzencode. Default is used gzcompress
 * level            The level of compression. Can be given as 0 for no compression up to 9 for maximum compression. Default is 9.
 * encoding_mode    Use with encode. The encoding mode. Can be FORCE_GZIP (the default) or FORCE_DEFLATE.
 *
 * @package Transform
 */
class Transform_Compress_Gzip extends Transform
{
    /**
     * Default extension for file with serialized data.
     * @var string
     */
    public $ext = 'gzip';
    
    /**
     * Type of compression.
     * @var string
     */
    public $method;
    
    /**
     * Level of compression.
     * @var int
     */
    public $level = 9;
        
    /**
     * Encoding mode.
     * @var 
     */
    public $encoding_mode = FORCE_GZIP;
    
    /**
     * Get a transformer that does the reverse action.
     *
     * @param Transformer $chain
     * @return Transformer
     */
    public function getReverse($chain=null)
    {
        $ob = new Transform_Decompress_Gzip($this);
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
        if (!is_int($this->level) || $this->level > 9) throw new Exception("Unable to compress data : Unknown encoding level '{$this->level}'.");
        
        if ($data instanceof Fs_File) $data = $data->getContents();
                
        switch (strtolower($this->method)) {
            case null:          
            case 'compress':    return gzcompress($data, $this->level); 

            case 'encode':      if (!in_array($this->encoding_mode, array(FORCE_GZIP, FORCE_DEFLATE)) ) throw new Exception("Unable to compress data : Unknown encoding mode '{$this->encoding_mode}'.");
                                return gzencode($data, $this->level, $this->encoding_mode);
            
            case 'deflate':     return gzdeflate($data, $this->level);                    

            default:            throw new Exception("Unable to compress data : Unknown compress method '{$this->method}'.");
        }
    }
}
