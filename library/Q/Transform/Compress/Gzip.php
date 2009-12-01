<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';
require_once 'Q/Transform/Decompress/Gzip.php';

/**
 * Create a gzip compressed string
 * Options : 
 * headers Set true if you want to get zlib headers
 * level   The level of compression. Can be given as 0 for no compression up to 9 for maximum compression. Default is -1.
 * mode    The encoding mode. Can be FORCE_GZIP (the default) or FORCE_DEFLATE.
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
     * Level of compression.
     * @var int
     */
    public $level = -1;
        
    /**
     * Encoding mode; FORCE_GZIP or FORCE_DEFLATE.
     * @var int 
     */
    public $mode = FORCE_GZIP;
    
    /**
     * Include headers
     * @var boolean
     */
    public $headers = false;
    
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
        if (!is_int($this->level) || $this->level > 9) throw new Exception("Unable to compress data : Unknown encoding level '{$this->level}'.");
        if (!is_bool($this->headers)) throw new Exception("Unable to compress data : Unknown header value '{'this->header'}'.");
        if (!in_array($this->mode, array(FORCE_GZIP, FORCE_DEFLATE))) throw new Exception("Unable to compress data : Unknown encoding mode '{$this->mode}'.");
        
        if ($this->chainInput) $data = $this->chainInput->process($data);
        if ($data instanceof Fs_File) $data = $data->getContents();

        if ($this->headers == false) {
            switch (strtoupper($this->mode)) {
                case FORCE_GZIP:    return gzcompress($data, $this->level);
                case FORCE_DEFLATE: return gzdeflate($data, $this->level);
            }            
        }
        return gzencode($data, $this->level, $this->mode);
    }
}
