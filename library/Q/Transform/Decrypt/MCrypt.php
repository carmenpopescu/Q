<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform/Crypt.php';
require_once 'Q/Transform/Crypt/MCrypt.php';

/**
 * Encrypt using mcrypt.
 * 
 * @package Transform_Crypt
 */
class Transform_Decrypt_MCrypt extends Transform_Crypt
{
    /**
     * Type of encryption
     * @var string
     */
    public $method = 'MCRYPT_RIJNDAEL_256';
    
    /**
     * Block cipher mode
     * @var string
     */
    public $mode = 'ecb';
    
    
    /**
     * Class constructor.
     * 
     * @param array $options
     */
    public function __construct($options=array())
    {
        if (is_object($options)) return parent::__construct($options);
        
        $options = (array)$options;
        if (isset($options[0])) $options['method'] = $options[0];
        unset($options[0]);
        
        parent::__construct($options);
    }

    /**
     * Decrypt encrypted value.
     *
     * @param string|Fs_File $value
     * @return string
     */
    public function process($value)
    {
        if ($this->chainInput) $data = $this->chainInput->process($data);
        
        if (empty($this->method)) throw new Exception("Unable to decrypt: Algoritm not specified.");
        if (!in_array($this->method, mcrypt_list_algorithms())) throw new Exception("Unable to decrypt: Algoritm '$this->method' is not supported.");
        
        if ($value instanceof Fs_File) $value = $value->getContents();
        
        return trim(mcrypt_decrypt($this->method, $this->secret, $value, $this->mode), "\0");
    }
    
    /**
     * Get a transformer that does the reverse action.
     *
     * @param Transformer $chain
     * @return Transformer
     */
    public function getReverse($chain=null)
    {
        $ob = new Transform_Crypt_MCrypt($this);
        if ($chain) $ob->chainInput($chain);
        return $this->chainInput ? $this->chainInput->getReverse($ob) : $ob;
    }
    
}