<?php
namespace Q;

require_once 'Q/Config.php';

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
     * Class constructor
     * 
     * @param array $options
     */
    public function __construct($options=array())
    {
        if (!is_array($options)) $options = (array)$options;
        
        if (!isset($options['path'])) {
            if (!isset($options[0])) throw new Config_Exception("Unable to load files for config: No option 'path' supplied.");
            $options['path'] = $options[0];
            unset($options[0]);
        }
        $this->_path = Fs::file($options['path']);
        
        if (isset($options['transformer'])) {
            $this->_transformer = $options['transformer'] instanceof Transformer ? $options['transformer'] : Transform::with($options['transformer']);
        } else {
            $options['driver'] = isset($options['driver']) ? $options['driver'] : $this->_path->extension();
            $this->_transformer = Transform::from($options['driver']);
        }
        if (isset($options['driver'])) $this->_ext = $options['driver'];
        
        if (!isset($this->_transformer)) throw new Config_Exception("Unable to load files for config: No transformer available.");
        
        \ArrayObject::__construct($this->_transformer->process($this->_path));
    }
}
