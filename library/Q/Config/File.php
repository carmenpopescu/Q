<?php
namespace Q;

require_once 'Q/Config.php';
require_once 'Q/Transform.php';
require_once 'Q/Fs.php';

/**
 * Load and parse config files from a directory.
 * 
 * Options:
 *  path          Path to the directory
 *  ext           Extension  of the files that will be loaded
 *  transformer   Transformer object or driver - to transform data from files specific format to array
 *  
 * {@example 
 * 
 * 1) 
 * $conf = Config::with('yaml:/etc/myapp');     
 * $conf['abc']['10'] = "hello";
 * $conf['abc']['12'] = "Test";
 * $conf->save();
 * }
 *
 * @package Config
 */
class Config_File extends Config
{
    /**
     * Driver in use
     * @var Tranformer
     */
    protected $_transformer;
    
    /**
     * File path
     * @var Fs_Node
     */
    protected $_path;
    
    /**
     * File extension and driver in use
     * @var string
     */
    protected $_ext;
    
    
    /**
     * Class constructor
     * 
     * @param string $path
     * @param array  $options
     */
    public function __construct($path=null, $options=array())
    {
        $options = (is_array($path) ? $path : array('path'=>$path)) + $options;
        $path = null;

        if (isset($options['driver'])) {
            if (isset($options[0])) {
                if (!isset($options['path'])) $options['path'] = $options[0];
                if (!isset($options['ext'])) $options['ext'] = $options['driver'];
                unset($options[0]);
            } else {
                $options[0] = $options['driver'];
            }
        }
        
        if (isset($options[0])) {
            if (strpos($options[0], ':') !== false) {
                list($options['ext'], $options['path']) = explode(':', $options[0], 2);
            } else {
                $key = !isset($options['ext']) && strpos($options[0], '.') === false && strpos($options[0], '/') === false ? 'ext' : 'path';
                if (!isset($options[$key])) $options[$key] = $options[0];
            }
        }
        
        if (isset($options['ext'])) $this->_ext = $options['ext'];
        
        if (isset($options['transformer'])) {
            $this->setTransformer($options['transformer']);
            if (empty($this->_ext)) $this->_ext = $this->_transformer->ext;
        } elseif (!empty($this->_ext)) {            
            $this->_ext = $options['ext'];
            $this->_transformer = Transform::from($this->_ext);
        }
        
        if (isset($options['path'])) $this->setPath($options['path']);
                
        $values = array();
        if (isset($this->_path) && $this->_path instanceof Fs_File && $this->_path->exists()) {
            if (!isset($this->_transformer)) throw new Exception("Unable to initialize Config_File object: Transformer is not set.");
            
            $values = $this->_transformer->process($this->_path);            
            if (empty($values)) $values = array();
        }
        \ArrayObject::__construct(&$values, \ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Set file path.
     * 
     * @param string|Fs_File $path
     * @return Fs_File
     */
    public function setPath($path)
    {
        if (isset($this->_path)) throw new Exception("Unable to set '$path' to Config_File object: Config_File path '{$this->_path}' is already set.");
        
        if (!($path instanceof Fs_File)) $path = Fs::file($path);
        $this->_path = $path;
        
        if (!isset($this->_ext) && isset($this->_path)) $this->_ext = $this->_path->extension();
        if (!isset($this->_transformer) && !empty($this->_ext)) $this->_transformer = Transform::from($this->_ext);
                
        return $this->_path;
    }

    /**
     * Get file path.
     * 
     * @return Fs_File
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * Set transformer
     * 
     * @param Transformer|string $transformer  Transformer or DSN string
     * @return Transformer
     */
    public function setTransformer($transformer)
    {
        if (isset($this->_transformer)) throw new Exception("Unable to set '".($transformer instanceof Transformer ? $transformer->ext : $transformer)."' to Config_File object: Transformer '{$this->_transformer->ext}' is already set.");      

        $this->_transformer = $transformer instanceof Transformer ? $transformer : Transform::with($transformer);       
                
        return $this->_transformer;
    }
    
    /**
     * Save all settings
     */
    public function save($mode=0666, $flags=0) 
    {
        if (!isset($this->_path)) throw new Exception("Unable to save setting: Path is not set");
        if (!isset($this->_transformer)) throw new Exception("Unable to save setting to '{$this->_path}': Transformer is not set.");
        $this->_transformer->getReverse()->save($this->_path, (array)$this, $flags);
    }
}
