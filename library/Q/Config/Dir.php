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
    public function setPath($path=null) {
        if (isset($this->_path)) throw new Exception("Unable to set '$path' to Config_Dir object: Config_Dir path '{$this->_path}' is already set.");
        $this->_path = (isset($path) ? Fs::dir($path) : null);
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
     * @param Config_File|array $value
     */
    public function offsetSet($key, $value)
    {
        if (is_scalar($value) || is_resource($value)) throw new Exception("Unable to set '$key' to '$value' for Config_Dir '{$this->_path}': Creating a section requires setting an array or Config_File object.");
        
       if ($value instanceof Config_File) {
            $config = $value;

            if (!empty($config->_ext) && !empty($this->_ext) && $config->_ext != $this->_ext) throw new Exception("Unable to create section '$key': Extension specified for Config_Dir '{$this->_path}' and extension specified for Config_File object setting are different.");
            if (empty($config->_ext) && empty($this->_ext)) throw new Exception("Unable to create section '$key': No extension specified for Config_Dir '{$this->_path}' or for the Config_File object setting.");
            if (empty($config->_ext)) $config->_ext = $this->_ext;
            
            if (isset($this->_transformer)) $config->_transformer = $this->_transformer;
            if (isset($this->_path)) $this->setChildrenPath($this->_path, $key, $config);//$config->setPath($config instanceof Config_Dir ? $this->_path->dir($key) : $this->_path->file("$key.{$config->_ext}"));

       } else {
            if (!$this->_ext) throw new Exception("Unable to create section '$key': No extension specified for Config_Dir '{$this->_path}', creating a section requires setting a Config_File object."); 

            $options = array();
            if ($this->_transformer) $options['transformer'] = $this->_transformer;

            $config = new Config_File(isset($this->_path) ? array('path'=>$this->_path->file("$key.{$this->_ext}")) : null, $options);
            $config->exchangeArray((array)$value);
       }
       
       parent::offsetSet($key, $config);
    }
    
    /**
     * Set path recursively for the Config_File children of the Config_dir object
     * 
     * @param Fs_Node     $path     Path of the current object
     * @param string      $key
     * @param Config_File $config
     */
    public function setChildrenPath($path, $key, $config) {
        if (!($path instanceof Fs_Node)) throw new Exception ("Unable to set path for the Config_File children of Config_Dir object : The path of Config_Dir is not a Fs_Node.");
        
        if (isset($config->_path)) unset ($config->_path);
        if ($config instanceof Config_Dir) {
            $config->setPath($path->dir($key));
            foreach ((array)$config as $k=>$value) {  
                if (isset($value->_path)) unset($value->_path);
                if ($value instanceof Config_Dir) {
                    $config->setChildrenPath($config->_path, $k, $value);
                } elseif ($value instanceof Config_File){
                    $value->setPath($config->_path->file("$k.{$value->_ext}"));
                } 
            }            
        }elseif ($config instanceof Config_File) {
            $config->setPath($this->_path->file("$key.{$config->_ext}"));   
        }
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
