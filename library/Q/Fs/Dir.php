<?php
namespace Q;

require_once 'Q/Fs/Node.php';

/**
 * Interface of a directory.
 * 
 * @package Fs
 */
class Fs_Dir extends Fs_Node
{
	/**
	 * Directory handles for traversing.
	 * 
	 * Resources are stored statically by object hash and not in object, because this will cause
	 * the == operator to work as expected.
	 * 
	 * @var array
	 */
	static private $handles;
	
	
	/**
	 * Class constructor.
	 * 
	 * @param string $path
	 */
	public function __construct($path)
	{
		parent::__construct($path);
		
		if (is_link($path) xor $this instanceof Fs_Symlink) throw new Fs_Exception("File '$path' is " . ($this instanceof Fs_Symlink ? 'not ' : '') . "a symlink.");
		if (file_exists($path) && !is_dir($path)) throw new Fs_Exception("File '$path' is not a directory, but a " . filetype($path) . "."); 
	}
	
	/**
	 * Class destructor; Clean up handle.
	 */
	public function __destruct()
	{
		unset(self::$handles[spl_object_hash($this)]);
	}
	
	/**
	 * Get directory resource.
	 * 
	 * @return resource
	 */
	protected function getHandle()
	{
		$id = spl_object_hash($this);
		if (isset(self::$handles[$id])) return self::$handles[$id];
			
		$resource = opendir($this->_path);
		if (!$resource) throw new Fs_Exception("Unable to traverse through directory '{$this->_path}'; Failed to read directory.");
		
		self::$handles[$id] = (object)array('resource'=>$resource);
		return self::$handles[$id];
	}
	
	/**
	 * Interator; Returns the current file object.
	 * 
	 * @return Fs_Node
	 */
	public function current()
	{
		$handle = $this->getHandle(); 
		while (!isset($handle->current) || $handle->current == '.' || $handle->current == '..') $handle->current = readdir($handle->resource);
		
		if ($handle->current === false) return false;
		return Fs::get("{$this->_path}/" . $handle->current);
	}
	
	/**
	 * Interator; Returns the current filename.
	 * 
	 * @return string
	 */
	public function key()
 	{
 		return $this->getHandle()->current;
 	}
 	
	/**
	 * Interator; Move forward to next item.
	 */
 	public function next()
 	{
 		$handle = $this->getHandle();
 		$handle->current = readdir($handle->resource);
 	}
 	
	/**
	 * Interator; Rewind to the first item.
	 */
 	public function rewind()
 	{
 		$handle = $this->getHandle();
 		$handle->current = rewinddir($handle->resource);
		while (!isset($handle->current) || $handle->current == '.' || $handle->current == '..') $handle->current = readdir($handle->resource);
 	}
 	
	/**
	 * Interator; Check if there is a current item after calls to rewind() or next(). 
	 */
 	public function valid()
 	{
 		return $this->getHandle()->current !== false;
 	}
 	
 	/**
 	 * Countable; Count files in directory
 	 * @return int
 	 */
 	public function count()
 	{
 		$files = scandir($this->_path);
 		return count($files) - (array_search('..', $files, true) !== false ? 2 : 1);
 	}
 	
 	
 	/**
 	 * Find files matching a pattern, relative to this directory.
 	 * @see http://www.php.net/glob
 	 * 
 	 * @param string $pattern
 	 * @param int    $flags    GLOB_% options as binary set
 	 * @return Fs_Node[]
 	 */
 	public function glob($pattern, $flags=0)
 	{
 		if ($pattern[0] != '/') $pattern = "{$this->_path}/$pattern";
 		return Fs::glob($pattern, $flags);
 	}

 	
	/**
	 * Tells whether the dir is writable.
	 * 
	 * @param int $flags  FS::% options
	 * @return boolean
	 */
	public function isWritable($flags=0)
	{
		return (($this instanceof Fs_Symlink && $flags && Fs::NO_DEREFERENCE) ?
		  (bool)(($this->getAttribute('mode', Fs::NO_DEREFERENCE) >> $this->modeBitShift()) & 4) :
		  is_writable($this->_path)) ||
		 ($flags & Fs::RECURSIVE && !$this->exists() && $this->up()->isWritable($flags & ~Fs::NO_DEREFERENCE));		
	}
 	
 	
 	/**
 	 * Check if file in directory exists (or is broken link).
 	 * 
 	 * @param string $name
 	 * @return Fs_Node
 	 */
 	public function has($name)
 	{
 		if ($name[0] == '/') throw new Exception("Unable to get '$name' for '{$this->_path}': Expecting a relative path.");
 		return Fs::has("{$this->_path}/$name");
 	}
 	
	/**
 	 * Get file in directory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Node
 	 */
 	public function get($name)
 	{
 		if ($name[0] == '/') throw new Exception("Unable to get '$name' for '{$this->_path}': Expecting a relative path.");
 		return Fs::get("{$this->_path}/$name");
 	}
 	
 	/**
 	 * Get file in directory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Node
 	 */
 	public function file($name)
 	{
 		if ($name[0] == '/') throw new Exception("Unable to get '$name' for '{$this->_path}': Expecting a relative path.");
 		return Fs::file("{$this->_path}/$name");
 	}
 	
 	/**
 	 * Get subdirectory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Node
 	 */
 	public function dir($name)
 	{
 		if ($name[0] == '/') throw new Exception("Unable to get '$name' for '{$this->_path}': Expecting a relative path.");
 		return Fs::dir("{$this->_path}/$name");
 	}
 	
 	/**
 	 * Get block device in directory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Block
 	 */
 	public function block($name)
 	{
 		if ($name[0] == '/') throw new Exception("Unable to get '$name' for '{$this->_path}': Expecting a relative path.");
 		return Fs::block("{$this->_path}/$name");
 	}
 	
 	/**
 	 * Get char device in directory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Char
 	 */
 	public function char($name)
 	{
 		if ($name[0] == '/') throw new Exception("Unable to get '$name' for '{$this->_path}': Expecting a relative path.");
 		return Fs::char("{$this->_path}/$name");
 	}
 	
 	/**
 	 * Get fifo in directory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Fifo
 	 */
 	public function fifo($name)
 	{
 		if ($name[0] == '/') throw new Exception("Unable to get '$name' for '{$this->_path}': Expecting a relative path.");
 		return Fs::fifo("{$this->_path}/$name");
 	}
 	
 	/**
 	 * Get socket in directory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Socket
 	 */
 	public function socket($name)
 	{
 		if ($name[0] == '/') throw new Exception("Unable to get '$name' for '{$this->_path}': Expecting a relative path.");
 		return Fs::socket("{$this->_path}/$name");
 	}
 	
 	
 	/**
 	 * Create this directory.
 	 * Use Fs::PRESERVE to simply return if file already exists
 	 * 
 	 * @param int $mode   File permissions, umask applies
 	 * @param int $flags  Fs::% options
 	 * @throws Fs_Exception if mkdir fails
 	 */
 	public function create($mode=0777, $flags=0)
 	{
 		if ($this->exists()) {
 			if ($flags & Fs::PRESERVE) return;
 			throw new Fs_Exception("Unable to create directory '{$this->_path}': File already exists");
 		}
 		
 		if (!@mkdir($this->_path, $mode, $flags & Fs::RECURSIVE)) throw new Fs_Exception("Failed to create directory '{$this->_path}'", error_get_last());
 		$this->clearStatCache();
 	}
 	
 	/**
	 * Copy or rename/move this file.
	 * 
	 * @param callback $fn     Function name; copy or rename
	 * @param Fs_Dir   $dir
	 * @param string   $name
	 * @param int      $flags  Fs::% options as binary set
	 * @return Fs_Node
	 */
	protected function doCopyRename($fn, $dir, $name, $flags)
	{
		if (($fn == 'move' && ~$flags & Fs::MERGE) || $this instanceof Fs_Symlink) return parent::doCopyRename($fn, $dir, $name, $flags);  
		
		if (empty($name) || $name == '.' || $name == '..' || strpos('/', $name) !== false) throw new SecurityException("Unable to $fn '{$this->_path}' to '$dir/$name'; Invalid filename '$name'.");
		
		if (!($dir instanceof Fs_Dir)) $dir = new static($dir);
		if (!$dir->exists() && ~$flags & Fs::RECURSIVE) throw new Fs_Exception("Unable to " . ($fn == 'rename' ? 'move' : $fn) . " '{$this->_path}' to '$dir/': Directory does not exist");

		$files = @scandir($this->_path);
		if ($files === false) throw new Fs_Exception("Failed to read directory to $fn '{$this->_path}' to '$dir/$name'", error_get_last());
		
		if ($dir->has($name) && (~$flags & Fs::MERGE || !($dir->$name instanceof Fs_Dir))) {
			$dest = $dir->$name;
			if ($dest instanceof Fs_Dir && !($dest instanceof Fs_Symlink) && count($dest) != 0) throw new Fs_Exception("Unable to $fn '{$this->_path}' to '{$dest->_path}': Target is a non-empty directory");
			if (~$flags & Fs::OVERWRITE) throw new Fs_Exception("Unable to $fn '{$this->_path}' to '{$dest->_path}': Target already exists");
			$dest->delete();
		}
		
		$dest = Fs::dir("$dir/$name");
		$dest->create($this->getAttribute('mode'), Fs::RECURSIVE | Fs::PRESERVE);
		
		foreach ($files as $file) {
			if ($file == '.' || $file == '..') continue;
			
			try {
				if (is_dir("{$this->_path}/$file)")) {
					$this->$file->doCopyRename($fn, $dest, $file, $flags);
				} else {
					if ($dest->has($file)) {
						if ($flags & Fs::UPDATE == Fs::UPDATE && $dest->$file['ctime'] >= $this->$file['ctime']) continue;
						if (~$flags & Fs::OVERWRITE) throw new Fs_Exception("Unable to $fn '{$this->_path}/$file' to '{$dest->_path}/$file': Target already exists");
					}
					
					$fn("{$this->_path}/$file", "{$dest->_path}/$file");
				}
			} catch (Fs_Exception $e) {
				trigger_error($e->getMessage(), E_USER_WARNING);
			}
		}
		
		if ($fn == 'rename') rmdir($this->_path);
		return new static("$dir/$name");
	}
 	
	/**
	 * Delete the directory (and possibly the contents).
	 * 
	 * @param int $flags  Fs::% options as binary set
	 */
	public function delete($flags=0)
	{
		if (!$this->exists()) return;
		
		$exceptions = null;
		
		if ($flags & Fs::RECURSIVE) {
			$files = scandir($this->_path);
			
			foreach ($files as $file) {
				if ($file == '.' || $file == '..') continue;
				
				try {
					if (is_dir("{$this->_path}/$file")) {
						$this->$file->delete($flags);
					} else {
						if (!@unlink("{$this->_path}/$file")) throw new Fs_Exception("Failed to delete '{$this->_path}/$file'", error_get_last());
					}
				} catch (Fs_Exception $e) {
					$exceptions[] = $e;
				}
			}
		}
		
		if (!@rmdir($this->_path)) throw new Fs_Exception("Failed to delete '{$this->_path}'", error_get_last(), $exceptions);
	}
}
