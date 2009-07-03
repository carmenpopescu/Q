<?
namespace Q;

require_once 'Q/Cache.php';

/**
 * Black hole for Cache light weight cache system.
 *
 * @package Cache
 */
class Cache_None extends Cache
{
	/**
	 * Test if a cache is available and (if yes) return it
	 * 
	 * @param string $id  Cache id
	 * @return mixed
	 */
	protected function doGet($id)
	{
		return false;
	}
	
	/**
	 * Save data into cache
	 * 
	 * @param string  $id         Cache id
	 * @param mixed   $data       Data to put in the cache
	 */
	protected function doSave($id, $data) { }

	/**
	 * Remove data from cache
	 * 
	 * @param string $id  Cache id
	 */
	protected function doRemove($id) { }
	
	/**
	 * Remove old/all data from cache
	 * 
	 * @param boolean $all
	 */
	protected function doClean($all=false) { }
}
?>