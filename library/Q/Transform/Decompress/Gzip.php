<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';
require_once 'Q/Transform/Compress/Gzip.php';

/**
 * Uncompress a compressed string.
 * 
 * Options : 
 * headers  Set true to use decode method.
 * length   Use with inflate. The maximum length of data to decode. Default is 0.
 * mode    The decoding mode. Can be 'deflate' (the default) or 'gzip' in the constructor or FORCE_GZIP (the default) or FORCE_DEFLATE.
 *
 * @package Transform
 */
class Transform_Decompress_Gzip extends Transform
{
    /**
     * Default extension for file with serialized data.
     * @var string
     */
    public $ext = 'gz';

    /**
     * Maximum length of data to decode.
     * @var int
     */
    public $length = 0;
    
    /**
     * Decoding mode; FORCE_GZIP or FORCE_DEFLATE.
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
            if (isset($options['mode']) && $options['mode'] === 'inflate') $options['mode'] = FORCE_DEFLATE;
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
        $ob = new Transform_Compress_Gzip($this);
        if ($chain) $ob->chainInput($chain);
        return $this->chainInput ? $this->chainInput->getReverse($ob) : $ob;
    }
	
	/**
     * Uncompress a compressed string
     *
     * @param mixed  $data
     * @return string
     */
    public function process($data)
    {
        if ($this->mode !== FORCE_GZIP && $this->mode !== FORCE_DEFLATE) throw new Exception("Unable to uncompress data: Unknown decoding mode '{$this->mode}'.");
        
        if ($this->chainInput) $data = $this->chainInput->process($data);
        if ($data instanceof Fs_File) $data = $data->getContents();

        if (!$this->headers) return $this->mode == FORCE_DEFLATE ? gzinflate($data, $this->length) : gzuncompress($data, $this->length);
        
        if (!function_exists('gzdecode')) throw new Exception("Unable to uncompress data with headers: function 'gzdecode' is not available.");
        return gzdecode($data, $this->length);
    }
}
