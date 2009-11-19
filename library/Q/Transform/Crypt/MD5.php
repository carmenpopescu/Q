<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform/Crypt.php';

/**
 * Encryption class for md5 method.
 * 
 * @package Transform_Crypt
 */
class Transform_Crypt_MD5 extends Transform_Crypt
{
    /**
     * Use a salt.
     * @var boolean
     */
    public $useSalt=false;

    /**
     * Default extension for file with serialized data.
     * @var string
     */
    public $ext = '';
    
    /**
     * Class constructor
     * 
     * @param array $options
     */
    public function __construct($options = array()) {        
        if (isset($options))
        parent::__construct($options);
    }
    
	/**
	 * Encrypt value.
	 *
	 * @param string $data
	 * @param string $salt   Salt or crypted hash
	 * @return string
	 */
	public function process($data, $salt=null)
	{
	    if ($this->chainInput) $data = $this->chainInput->process($data);
	    
        if ($data instanceof Fs_File) {
			if (empty($this->secret) && !$this->useSalt) {
			 return md5_file($data);   
			}
			$data = $data->getContents();
		}
		
		$data .= $this->secret;
	    
		if (!$this->useSalt) return md5($data);
		
		$salt = (empty($salt) ? $this->makeSalt() : preg_replace('/\$[\dabcdef]{32}$/', '', $salt));
		return $salt . '$' . md5($salt . $data);
	}
}
