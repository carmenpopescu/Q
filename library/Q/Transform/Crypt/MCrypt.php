<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform/Crypt.php';
require_once 'Q/Transform/Decrypt/MCrypt.php';

/**
 * Encrypt using mcrypt.
 * 
 * @package Transform_Crypt
 */
class Transform_Crypt_MCrypt extends Transform_Crypt
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
	 * Encrypt value.
	 *
	 * @param string $value
	 * @param string $salt   Not used
	 * @return string
	 */
	public function process($value, $salt=null)
	{
        if ($this->chainInput) $data = $this->chainInput->process($data);
	    
        if (empty($this->method)) throw new Exception("Unable to encrypt: Algoritm not specified.");
		if (!in_array($this->method, mcrypt_list_algorithms())) throw new Exception("Unable to encrypt: Algoritm '$this->method' is not supported.");
		
		if ($value instanceof Fs_File) $value = $value->getContents();
		return mcrypt_encrypt($this->method, $this->secret, $value, $this->mode);
	}

    /**
     * Get a transformer that does the reverse action.
     *
     * @param Transformer $chain
     * @return Transformer
     */
    public function getReverse($chain=null)
    {
        $ob = new Transform_Decrypt_MCrypt($this);
        if ($chain) $ob->chainInput($chain);
        return $this->chainInput ? $this->chainInput->getReverse($ob) : $ob;
    }
}
