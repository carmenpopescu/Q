<?php
namespace Q;

/**
 * A lock may be used to provide exclusive access to resource.
 * 
 * @package Lock
 */
class Lock
{
    /**
     * Lock name
     * @var string
     */
    protected $name;

    /**
     * Timeout on a lock (in seconds).
     * @var int
     */
    public $timeout;
    
    /**
     * Cache object that hold key
     * @var Cache
     */
    public $cache;
    
    /**
     * Cached info
     * @var array
     */
    protected $info;
    
    
    /**
     * Acquire or refresh a lock.
     * 
     * @param string $key  Key that should fit the lock
     * @return boolean
     */
    public function acquire($key=null)
    {
        if ($this->getKey() && $key != $this->getKey()) return false;

        if (!isset($key)) $key = md5(microtime());
        $info = array('timestamp'=>strftime('%Y-%m-%d %T'), 'check'=>$key);
        if (class_exists('Q\Authenticate', false) && Authenticate::i()->isLoggedIn()) $info['user'] = Authenticate::i()->user->getInfo();

        $this->cache->save('lock:' . $this->name, $info, $this->timeout);
        $this->info = $info;
        
        return true;
    }
    
    /**
     * Release a lock.
     * 
     * @param string $key  Key that should fit the lock
     * @return boolean
     */
    public function release($key)
    {
        if ($key != $this->getKey()) return false;
        if (!empty($this->info)) $this->cache->remove('lock:' . $this->name);
    }

    /**
     * Get the key that fits this lock.
     *
     * @return string
     */
    public function getKey()
    {
        if (!isset($this->info)) $this->info = (array)$this->cache->get("lock:" . $this->name);
        return isset($this->info['key']) ? $this->info['key'] : null;  
    }
    
    
    /**
     * Return lock as XML.
     * 
     * @return string
     */
    public function asXml()
    {
        return "<lock name=\"{$this->name}\" timeout=\"{$this->timeout}\">" .
          (isset($info['user_fullname']) ? '<user>' . htmlspecialchars($info['user_fullname'], ENT_COMPAT, 'UTF-8') . '</user>' : null) .
          "<timestamp>{$info['timestamp']}</timestamp>" .
          '</lock>';        
    }
    
    /**
     * Get string representation for key.
     *
     * @return string
     */
    public function __asString()
    {
        $key = $this->getKey();
        return $this->name . ($key ? ":$key" : '');
    }
}
?>