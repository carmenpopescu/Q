<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';
require_once 'Q/Transform/Decompress/Gzip.php';

/**
 * Create a gzip compressed string.
 * Options : 
 * headers Set true if you want to get zlib headers.
 * level   The level of compression. Can be given as 0 for no compression up to 9 for maximum compression. Default is -1.
 * mode    The encoding mode. Can be 'inflate' (the default) or 'gzip' in the constructoror or FORCE_GZIP (the default) or FORCE_DEFLATE.
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
     * Class constructor
     * 
     * @param array $options
     */
    public function __construct($options = array())
    {
        if (is_array($options)) {
            if (isset($options['mode']) && $options['mode'] === 'deflate') $options['mode'] = FORCE_DEFLATE;
              elseif (isset($options['mode']) && $options['mode'] === 'gzip') $options['mode'] = FORCE_GZIP;
        }          
        parent::__construct($options);
    }
    

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
        if (!is_int($this->level) || $this->level > 9) throw new Exception("Unable to compress data: Unknown encoding level '{$this->level}'.");
        if ($this->mode !== FORCE_GZIP && $this->mode !== FORCE_DEFLATE) throw new Exception("Unable to compress data: Unknown encoding mode '{$this->mode}'.");
                
        if ($this->chainInput) $data = $this->chainInput->process($data);
        if ($data instanceof Fs_File) $data = $data->getContents();

        if (!$this->headers) return $this->mode == FORCE_DEFLATE ? gzdeflate($data, $this->level) : gzcompress($data, $this->level);
        
        return gzencode($data, $this->level, $this->mode);
    }
}
