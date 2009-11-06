<?php
namespace Q;

require_once 'Q/Config.php';
require_once 'Q/Transform.php';
require_once 'Q/Fs.php';

/**
 * Load and parse config files from a directory.
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
     * @Fs_Node
     */
    protected $_path;
    
    /**
     * File extension and driver in use
     *
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
        if (is_array($path)) {
            $options = $path + $options;
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
        }
        
        if (isset($options[0])) {
            if (strpos($options[0], ':') !== false) {
                list($options['ext'], $options['path']) = explode(':', $options[0], 2);
            } else {
                $key = !isset($options['ext']) && strpos($options[0], '.') === false && strpos($options[0], '/') === false ? 'ext' : 'path';
                if (!isset($options[$key])) $options[$key] = $options[0];
            }
        }
        
        $this->_path = isset($path) ? Fs::file($path) : (isset($options['path']) ? Fs::file($options['path']) : null);
        $this->_ext = isset($options['ext']) ? $options['ext'] : (isset($this->_path) ? $this->_path->extension() : null);
        
        if (isset($options['transformer'])) {
            $this->_transformer = $options['transformer'] instanceof Transformer ? $options['transformer'] : Transform::with($options['transformer']);
            if (!isset($this->_ext)) $this->_ext = $this->_transformer->ext;
        } elseif (!empty($this->_ext)) {
            $this->_transformer = Transform::from($this->_ext);
        }
        
        $values = isset($this->_path) && $this->_path->exists() && isset($this->_transformer) ? $this->_transformer->process($this->_path) : array();
        \ArrayObject::__construct(&$values, \ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Save all settings
     */
    public function save() 
    {
        if (!isset($this->_path)) throw new Exception("Unable to save setting: Path is not set");
        if (!isset($this->_transformer)) throw new Exception("Unable to save setting for '{$this->_path}': Transformer is not set");

        $this->_transformer->getReverse()->save($this->_path, (array)$this);
    }
        
}
