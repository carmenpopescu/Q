<?php
namespace Q;

require_once 'Q/Crypt.php';

/**
 * Don't do any encryption at all.
 * 
 * @package Crypt
 */
class Crypt_None extends Crypt
{
	/**
	 * Returns value.
	 *
	 * @param string $value
	 * @param string $salt   Not used
	 * @return string
	 */
	public function encrypt($value, $salt=null)
	{
		return $value;
	}
}

