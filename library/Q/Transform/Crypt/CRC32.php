<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform/Crypt.php';

/**
 * Encryption class for CRC32 method.
 * 
 * @package Transform_Crypt
 */
class Transform_Crypt_CRC32 extends Transform_Crypt
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
	 * Encrypt value.
	 *
	 * @param string $value
	 * @param string $salt   Salt or crypted hash
	 * @return string
	 */
	public function process($value, $salt=null)
	{
        if ($this->chainInput) $value = $this->chainInput->process($value);
	    
        if ($value instanceof Fs_File) $value = $value->getContents();
		
	    $value .= $this->secret;
		if (!$this->useSalt) return sprintf('%08x', crc32($value));
		
		$salt = (empty($salt) ? $this->makeSalt() : preg_replace('/\$[\dabcdef]{8}$/', '', $salt));
		return $salt . '$' . sprintf('%08x', crc32($salt . $value));
	}
}
