<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform/Crypt.php';

/**
 * Create hash.
 * 
 * @package Transform_Crypt
 */
class Transform_Crypt_Hash extends Transform_Crypt 
{
    /**
     * Use a salt.
     * @var boolean
     */
    public $useSalt=false;
    	
	/**
	 * Hashing algoritm.
	 * @var string
	 */
	public $method = 'md5';
	
	/**
	 * Class constructor.
	 * 
	 * @param array $options
	 */
	public function __construct($options = array())
	{
	    $options = (array)$options;
	    if (isset($options[0])) $options['method'] = $options[0];
		unset($options[0]);
	    
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
        if ($this->chainInput) $data = $this->chainInput->process($data);
	    
        if (empty($this->method)) throw new Exception("Unable to encrypt; Hashing algoritm not specified.");
        if (!in_array($this->method, hash_algos())) throw new Exception("Unable to encrypt; Algoritm '$this->method' is not supported.");

        if ($value instanceof Fs_File) {
			if (empty($this->secret) && !$this->useSalt) return hash_file($this->method, $value);
			$value = $value->getContents();
		}
		
		$value .= $this->secret;
		if (!$this->useSalt) return hash($this->method, $value);
		
		$salt = (empty($salt) ? $this->makeSalt() : preg_replace('/\$[\dabcdef]{32}$/', '', $salt));
		return $salt . '$' . hash($this->method, $salt . $value);
	}
}
