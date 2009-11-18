<?php
namespace Q;

require_once 'Q/Config/File.php';

/**
 * Load all config from a dir.
 * 
 * Options:
 *  path          Path to the directory
 *  ext           Extension  of the files that will be loaded
 *  transformer   Transformer object or driver - to transform data from files specific format to array
 *  loadall       Specify to for eager load
 *  
 * @package Config
 */
class Config_Dir extends Config_File
{
    /**
     * Class constructor
     * 
     * @param string|array $path     Path (string) or options (array)
     * @param array        $options
     */
    public function __construct($path=null, $options=array())
    {
        parent::__construct($path, $options);
        if (!empty($options['loadall'])) $this->loadAll();
    }
    
    /**
     * Set directory path
     * 
     * @param string|Fs_Dir $path
     * @return Fs_Dir
     */
    public function setPath($path=null)
    {
        if (isset($this->_path)) throw new Exception("Unable to set '$path' to Config_Dir object: Config_Dir path '{$this->_path}' is already set.");
        
        $this->_path = (isset($path) ? Fs::dir($path) : null);
        
        foreach ($this as $key=>$config) {
            $config->setPath($config instanceof Config_Dir ? $this->_path->dir($key) : $this->_path->file("$key.{$config->_ext}"));
        }
        
        return $this->_path;
    }
    
    
    /**
     * ArrayAccess; Whether or not an offset exists. 
     * 
     * @param string $key
     * @return boolean
     */
    public function offsetExists($key)
    {
        return parent::offsetExists($key) || is_dir("{$this->_path}/{$key}") || isset($this->_ext) ? file_exists("{$this->_path}/{$key}.{$this->_ext}") : glob("{$this->_path}/{$key}.*");
    }
    
    /**
     * ArrayAccess; Assigns a value to the specified offset. 
     * 
     * @param string            $key
     * @param Config_File|array $config
     */
    public function offsetSet($key, $config)
    {
        if (is_scalar($config) || is_resource($config)) throw new Exception("Unable to set '$key' to '$config' for Config_Dir '{$this->_path}': Creating a section requires setting an array or Config_File object.");
        
       if ($config instanceof Config_File) {
            if (!empty($config->_ext) && !empty($this->_ext) && $config->_ext != $this->_ext) throw new Exception("Unable to create section '$key': Extension specified for Config_Dir '{$this->_path}' and extension specified for Config_File object setting are different.");
            if (empty($config->_ext) && empty($this->_ext)) throw new Exception("Unable to create section '$key': No extension specified for Config_Dir '{$this->_path}' or for the Config_File object setting.");
            if (empty($config->_ext)) $config->_ext = $this->_ext;
            
            if (isset($this->_transformer)) $config->setTransformer($this->_transformer);
            if (isset($this->_path)) $config->setPath($config instanceof Config_Dir ? $this->_path->dir($key) : $this->_path->file("$key.{$config->_ext}"));

       } else {
            if (!$this->_ext) throw new Exception("Unable to create section '$key': No extension specified for Config_Dir '{$this->_path}', creating a section requires setting a Config_File object."); 

            $options = array();
            if ($this->_transformer) $options['transformer'] = $this->_transformer;

            $value = $config;
            $config = new Config_File(isset($this->_path) ? array('path'=>$this->_path->file("$key.{$this->_ext}")) : null, $options);
            $config->exchangeArray((array)$value);
       }
       
       parent::offsetSet($key, $config);
    }
    
    /**
     * ArrayAccess; Returns the value at specified offset, loading the section if needed.
     * 
     * @param string $key
     * @return Config_File
     */
    public function offsetGet($key)
    {
        if (parent::offsetExists($key)) return parent::offsetGet($key);
        $dirname = "{$this->_path}/{$key}";
        $filename = "{$dirname}.{$this->_ext}";

        $options = array();
        if ($this->_transformer) $options['transformer'] = $this->_transformer;
        if (is_dir($dirname)) {
            parent::offsetSet($key, new Config_Dir(Fs::dir($dirname), $options));
        } elseif (file_exists($filename)) {
            parent::offsetSet($key, new Config_File(Fs::file($filename), $options));
        } else {
            trigger_error("Configuration section '$key' doesn't exist for '{$this->_path}'", E_WARNING);
            return null;
        }
        
        return parent::offsetGet($key);
    }
    
    /**
     * ArrayAccess; Unsets an offset.
     * 
     * @param string $key
     */
    public function offsetUnset($key)
    {
        parent::offsetSet($key, null);
    }
    
    
    /**
     * Load all settings (eager load).
     * (fluent interface)
     * 
     * @return Config_Dir
     */
    protected function loadAll()
    {
        if (!isset($this->_path)) throw new Exception("Unable to create Config object: Path not specified.");
        
        $options = array();
        if ($this->_transformer) $options['transformer'] = $this->_transformer;
        if ($this->_ext) $options['ext'] = $this->_ext;
        $options['loadall'] = true;
        
        foreach ($this->_path as $key=>$file) {  
            if ($file instanceof Fs_Dir) {
                if (!isset($this[$file->filename()])) parent::offsetSet($file->filename(), new Config_Dir($file, $options));
            } elseif ($file instanceof Fs_File) {
                if (isset($this->_ext) && substr($file, -strlen($this->_ext)) != $this->_ext) continue;
                if (!isset($this[$file->filename()])) parent::offsetSet($file->filename(), new Config_File($file, $options));
            }
        }
        
        return $this;
    }
    
    /**
     * Save all settings
     * 
     * @param int $mode   File permissions, umask applies
     * @param int $flags  Fs::% options
     */
    public function save($mode=0777, $flags=Fs::RECURSIVE) {
        if (!isset($this->_path)) throw new Exception("Unable to save setting: Path not specified.");    
        
        if (!$this->_path->exists()) $this->_path->create($mode, $flags);
        
        foreach ($this as $key=>$config) {
            if (!isset($config)) $this->_path->$key->remove(Fs::RECURSIVE);
              else $config->save($mode, $flags);
        }
    }
}
