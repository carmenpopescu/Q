<?
namespace Q;

require_once 'Q/Cache.php';

/**
 * Light weight cache system to save variables on file.
 * 
 * Options:
 *   app             Key to differentiate applications
 *   path | 0        Path of cache files
 *   lifetime        Time until cache expires (seconds).
 *	 gc_probability  Probability the garbage collector will clean up old files
 *	 memorycaching   Don't read cache file each time
 *  
 * @package Cache
 */
class Cache_File extends Cache
{
	/**
	 * Class constructor
	 * 
	 * @param $options  Configuration options
	 */	
	public function __construct($options=array())
	{
	    if (empty($options['path'])) {
	        if (!empty($options['cachedir'])) $options['path'] = $options['cachedir'];
	          elseif (!empty($options[0])) $options['path'] = $options[0];
	          else $options['cachedir'] = (function_exists('sys_get_temp_dir') ? sys_get_temp_dir() : '/tmp') . '/cache.' . $this->options['key'];
	    }
	    $options['path'] = preg_replace('/\{\$(.*?)\}/e', "isset(\$_SERVER['\$1']) ? \$_SERVER['\$1'] : \$_ENV['\$1']", $options['path']);
		if (!is_dir($options['path']) && (file_exists($options['path']) || !mkdir($options['path'], 0770, true))) throw new Exception("Unable to create Cache of type 'file'. Directory '{$this->options['cachedir']}' does not exists and could not be created.");
		
		if ($optionss['gc_probability'] >= 1 || mt_rand(1, 1 / $options['gc_probability']) == 1) $this->clean(); 

		parent::__construct($options);
	}
	
	
	/**
	 * Get a filepath for a cache id
	 *
	 * @param string $id  Cache id
	 * @return string
	 */
	protected function getPath($id)
	{
	    return $this->options['path'] . '/cache.' . preg_replace('~[/\?<>\\\\:*|"]~', '', $id);
	}
	
	
	/**
	 * Return data from cache or false if it doens't exist.
	 * 
	 * @param string $id  Cache id
	 * @return mixed
	 */
	protected function doGet($id)
	{
		$file = $this->getPath($id);
		if (!file_exists($file)) return null;
		
		$fp = fopen($file, "r");
	    if (!flock($fp, LOCK_SH)) return null;
	    
	    $contents = fread($fp, filesize($file));
	    flock($fp, LOCK_UN);
		
		return unserialize($contents);
	}
	
	/**
	 * Save data into cache
	 * 
	 * @param string $id    Cache id
	 * @param mixed  $data  Data to put in the cache
	 */
	protected function doSave($id, $data)
	{
		$file = $this->getPath($id);
		if (!$this->options['overwrite'] && file_exists($file) && filemtime($file) < time()-$this->options['lifetime']) return;
		
		$contents = serialize($data);
		
		$fp = fopen($file, "w");
	    if (!flock($fp, LOCK_EX)) return;
	    
	    fwrite($fp, $contents);
	    flock($fp, LOCK_UN);
	}

	/**
	 * Remove data from cache
	 * 
	 * @param string $id  Cache id
	 */
	protected function doRemove($id)
	{
		$file = $this->getPath($id);
		if (file_exists($file)) unlink($file);
	}
	
	/**
	 * Remove old/all data from cache.
	 * 
	 * @param boolean $all  Remove all data, don't check mtime
	 */
	protected function doClean($all=false)
	{
		$files = glob(escapeshellarg($this->options['path']) . '/cache.*');
		
		if ($all) {
		    array_walk($files, 'unlink');
		} else {
		    $old = time() - $this->options['lifetime'];
    		foreach ($files as $file) {
    			if (filemtime($file) < $old) unlink($file);
    		}
		}
	}
}
?>