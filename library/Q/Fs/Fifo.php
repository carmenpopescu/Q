<?php
namespace Q;

require_once 'Q/Fs/Item.php';

/**
 * Interface of a fifo file.
 * 
 * @package Fs
 */
class Fs_Fifo extends Fs_Item
{
	/**
	 * Class constructor.
	 * 
	 * @param string $path
	 */
	public function __construct($path)
	{
		if (!file_exists($path)) throw new Fs_Exception("Can't load fifo file '$path'; File doesn't exists."); 
		if (filetype($path) != 'fifo') throw new Fs_Exception("File '$path' is not a fifo file, but a " . filetype($path) . ".");
		 
		parent::__construct($path);
	}
}
