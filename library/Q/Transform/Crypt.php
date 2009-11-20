<?php
namespace Q;

require_once 'Q/Transform.php';

/**
 * Encryption/decryption interface.
 * 
 * If a Crypt class implements the Decrypt interface, it can do encryption and decryption. Otherwise it can only do encryption. 
 *
 * @package Transform_Crypt
 */
abstract class Transform_Crypt extends Transform
{
    /**
     * Secret phrase.
     * This phrase is used as password to encrypt/decrypt or to create secure hash.
     * 
     * @var string
     */
    public $secret;
    	
	/**
	 * Create a random salt
	 *
	 * @param int $lenght
	 * @return string
	 */
	static public function makeSalt($length=6)
	{
		$salt='';
		while (strlen($salt) < $length) $salt .= sprintf('%x', rand(0, 15));
		return $salt;
	}
}
