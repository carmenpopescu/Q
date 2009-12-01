<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';
require_once 'Q/Transform/Compress/Gzip.php';

define('FORCE_UNZIP', FORCE_GZIP);
define('FORCE_INFLATE', FORCE_DEFLATE);

/**
 * Uncompress a compressed string
 * Options : 
 * headers  Set true to use decode method.
 * length   Use with inflate. The maximum length of data to decode. Default is 0.
 * mode    The decoding mode. Can be FORCE_UNZIP (the default) or FORCE_INFLATE.
 *
 * @package Transform
 */
class Transform_Decompress_Gzip extends Transform
{
    /**
     * Default extension for file with serialized data.
     * @var string
     */
    public $ext = 'gzip';

    /**
     * Maximum length of data to decode.
     * @var int
     */
    public $length = 0;
    
    /**
     * Decoding mode; FORCE_UNZIP or FORCE_INFLATE.
     * @var int 
     */
    public $mode = FORCE_UNZIP;
    
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
        $ob = new Transform_Compress_Gzip($this);
        if ($chain) $ob->chainInput($chain);
        return $this->chainInput ? $this->chainInput->getReverse($ob) : $ob;
    }
	
	/**
     * Uncompress a compressed string
     *
     * @param mixed    $data
     * @return string
     */
    public function process($data, $length=null)
    {
        if (!is_bool($this->headers)) throw new Exception("Unable to unccompress data : Unknown header value '{'this->header'}'.");
        if (!in_array($this->mode, array(FORCE_UNZIP, FORCE_INFLATE))) throw new Exception("Unable to uncompress data : Unknown decoding mode '{$this->mode}'.");
        
        if ($this->chainInput) $data = $this->chainInput->process($data);
        if ($data instanceof Fs_File) $data = $data->getContents();

        if ($this->headers == false) {
            switch (strtoupper($this->mode)) {
                case FORCE_UNZIP:    return gzuncompress($data, $this->length);
                case FORCE_INFLATE: return gzinflate($data, $this->length);
            }            
        }
        return gzdecode($data, $this->length);        
    }
}
