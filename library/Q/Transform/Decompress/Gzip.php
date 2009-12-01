<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';
require_once 'Q/Transform/Compress/Gzip.php';

/**
 * Uncompress a compressed string
 * Options : 
 * method   possible values : 'inflate' to use gzinflate, 'uncompress' to use gzybcompress and 'decode' to use gzdecode. Default is used gzuncompress
 * length   Use with inflate. The maximum length of data to decode. Default is 0.
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
     * Type of compression.
     * @var string
     */
    public $method;

    /**
     * Maximum length of data to decode.
     * @var int
     */
    public $length = 0;
    
    /**
     * Revers methods associated with the available methods 
     * @var array
     */
    protected $reverse_method = array(
        'uncompress' => 'compress',
        'decode'   => 'encode',
        'inflate'  => 'deflate'
    );
    
    /**
     * Get a transformer that does the reverse action.
     *
     * @param Transformer $chain
     * @return Transformer
     */
    public function getReverse($chain=null)
    {
        $ob = new Transform_Compress_Gzip($this);
        if (isset($this->method) && array_key_exists($this->method, $this->reverse_method)) $ob->method = $this->reverse_method[$this->method];
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
        if ($this->chainInput) $data = $this->chainInput->process($data);

        if ($data instanceof Fs_File) $data = $data->getContents();
        
        switch (strtolower($this->method)) {
            case null:          
            case 'uncompress':  return gzuncompress($data, $this->length); 

            case 'decode':      return gzdecode($data, $this->length);
            
            case 'inflate':     return gzinflate($data, $this->length);                    

            default:            throw new Exception("Unable to uncompress data : Unknown uncompress method '{$this->method}'.");
        }
        
    }
}
