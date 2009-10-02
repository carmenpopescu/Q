<?php
namespace Q;

require_once 'Q/Fs/Node.php';

/**
 * Interface of a block device file.
 * 
 * @package Fs
 */
class Fs_Block extends Fs_Node
{
	/**
	 * Class constructor.
	 * 
	 * @param string $path
	 */
	public function __construct($path)
	{
		parent::__construct($path);
		
		if (is_link($path) xor $this instanceof Fs_Symlink) throw new Fs_Exception("File '$path' is " . ($this instanceof Fs_Symlink ? 'not ' : '') . "a symlink.");
		if (file_exists($path) && filetype(realpath($path)) != 'block') throw new Fs_Exception("File '$path' is not a block device, but a " . filetype($path) . ".");
	}
}
