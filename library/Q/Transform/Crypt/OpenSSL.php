<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform/Crypt.php';
require_once 'Q/Transform/Decrypt/OpenSSL.php';

/**
 * Crypt using password based encryption with OpenSSL.
 * 
 * This class doesn't do seal/open (no public/private keys). The same secret phrase needs to be used for both
 * encryption and decryption.
 * 
 * @package Transform_Crypt
 */
class Transform_Crypt_OpenSSL extends Transform_Crypt
{
    /**
     * Encryption method.
     * @var string
     */
    public $method = 'AES256';
    
	/**
	 * Class constructor.
	 * 
	 * @param array $options  Values for public properties
	 */
	public function __construct($options=array())
	{
		if (!extension_loaded('openssl')) throw new Exception("Unable to encrypt: OpenSSL extension is not available.");

		if (is_object($options)) return parent::__construct($options);
		
	    $options = (array)$options;
	    
	    if (isset($options[0])) {
	        $this->method = $options[0];
	        unset($options[0]);
	    }
	    
	    parent::__construct($options);
	}
    
	/**
	 * Encrypt value.
	 *
	 * @param string $value
	 * @param string $salt   Not used
	 * @return string
	 */    
    public function process($value, $salt=null)
    {
        if ($this->chainInput) $value = $this->chainInput->process($value);
        
        if ($value instanceof Fs_File) $value = $value->getContents();
    	
        if (empty($this->secret)) trigger_error("Secret key is not set for OpenSSL password encryption. This is not secure.", E_USER_NOTICE);
        return openssl_encrypt($value, $this->method, $this->secret);
    }

    /**
     * Get a transformer that does the reverse action.
     *
     * @param Transformer $chain
     * @return Transformer
     */
    public function getReverse($chain=null)
    {
        $ob = new Transform_Decrypt_OpenSSL($this);
        if ($chain) $ob->chainInput($chain);
        return $this->chainInput ? $this->chainInput->getReverse($ob) : $ob;
    }
}
