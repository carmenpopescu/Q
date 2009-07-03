<?php
namespace Q;

require_once 'Q/Exception.php';

/**
 * Place content in a template using to output handler.
 * 
 * @package SiteTemplate
 */
class SiteTemplate
{
	/**
	 * Singleton object
	 * @var Q\SiteTemplate
	 */
	protected static $instance;

	/**
	 * Marker to place data, %s for name
	 * @var string
	 */
	public $marker = '<!--MARK: %s -->';
	
	/**
	 * Template
	 * @var string
	 */
	protected $template;
	
	/**
	 * Cached footer of the template
	 * @var string
	 */
	protected $footer;
	
	
	/**
	 * Queue of current markers
	 * @var array
	 */
	protected $curmarkers=array('content');

	
	/**
	 * Data for each marker
	 * @var array
	 */
	protected $data=array();

	/**
	 * Singleton method
	 * 
	 * @return Authenticate
	 */
	static function i()
	{
		if (!isset(self::$instance)) {
		    if (class_exists('Q\Config') || !Config::i()->exists() || !($dsn = Config::i()->get('sitetemplate' . (isset($name) ? ".{$name}" : '')))) return new SiteTemplate_Mock($name);
	        self::$instance = self::with($dsn);
	        self::$instance->start();
		}
		
		return self::$instance;
	}
	
	/**
	 * Set the options.
	 *
	 * @param string|array $dsn  DSN/driver (string) or array(driver[, arg1, ...])
	 * @return Authenticate
	 */
	public function with($dsn)
	{
	    if (isset($this)) throw new Exception("SiteTemplate instance is already created.");
	    
	    $options = is_string($dsn) ? $dsn : extract_dsn($dsn);
	    return new self($options);
    }
	    
	/**
	 * Check if instance exists.
	 * 
	 * @return boolean
	 */
	public final function exists()
	{
	    return true;
	}
    
	/**
	 * Class constructor
	 * 
	 * @param array $options
	 */
	protected function __construct($options)
	{
	    foreach ($options as $key=>$value) {
	        $refl = new ReflectionProperty($this, $key);
	        if ($refl->isPublic()) $this->$key = $value;
	    }
	}
    
	
	/**
	 * Start output handler
	 */
	public function start()
	{
	    if (in_array(array($this, '__callback'), ob_list_handlers(), true)) throw new Exception("Site template already started");
		ob_start(array($this, '__callback'));
    }

    /**
     * Callback method for ob_start
     * @ignore
     * 
     * @param string $buffer
     * @param int    $flags
     * @return string
     */
    public function __callback($buffer, $flags)
    {
    	if (count($this->curmarkers) != 1) {
    		$this->appendData(end($this->curmarkers), $buffer);
    		$buffer = null;	
    	}
    	
    	if (!empty($this->data[0])) {
    		$buffer = $this->data[0] . $buffer;
	    	$this->data[0] = null;
    	}
    	
		if (!isset($this->footer)) {
			list($header, $this->footer) = preg_split('/' . sprintf($this->marker, $this->curmarkers[0]) . '/i', $this->template, 2);
			$header = preg_replace('/' . str_replace(preg_quote($this->marker, '/'), '%s', '(.*?)') . '/ie', 'isset($this->data[$1]) ? $this->data[$1] : ""', $header);
			$buffer = $header . $buffer;
		}

		if ($flags & PHP_OUTPUT_HANDLER_END) {
			$buffer .= preg_replace('/' . str_replace(preg_quote($this->marker, '/'), '%s', '(.*?)') . '/ie', 'isset($this->data[$1]) ? $this->data[$1] : ""', $this->footer);
		}

		return $buffer;
    }

	/**
	 * Directly set the data for a marker, overwriting existing data.
	 *
	 * @param string $marker
	 * @param string $data
	 */
    public function setData($marker, $data)
	{ 
		$this->data[$marker] = $data;
    }

	/**
	 * Append data for a marker
	 *
	 * @param string $marker
	 * @param string $data
	 */
    public function appendData($marker, $data)
	{
		$this->data[$marker] = (isset($this->data[$marker]) ? $this->data[$marker] : "") . $data;
    }
    

	/**
	 * The next data in the outputbuffer should be placed for this marker.  
	 *
	 * @param string $marker
	 * @param string $data
	 */
    public function mark($marker)
    {
    	$data = ob_get_clean();
    	if ($data) $this->appendData(end($this->curmarkers), $data);

    	array_push($this->curmarkers, $marker);
    }

    /**
     * The next data in the outputbuffer should be placed for the previous marker.
     */
    public function endmark()
    {
    	if (count($this->curmarkers) == 1) throw new Exception("Called endmark more often than mark.");
    	
    	$marker = array_pop($this->curmarkers);
    	$data = ob_get_clean();
		
		if ($data) $this->appendData($marker, $data);
    }
}


/**
 * Mock object to create SiteTemplate instance.
 * @ignore 
 */
class SiteTemplate_Mock
{
	/**
	 * Create a new SiteTemplate interface.
	 *
	 * @param string|array $dsn  DSN/driver (string) or array(driver[, arg1, ...])
	 * @return SiteTemplate
	 */
	static public function with($dsn)
	{
    	$instance = SiteTemplate::with($dsn, $options);
	    return $instance;
    }
    
    
    /**
     * Check if instance exists.
     *
     * @return boolean
     */
    public function exists()
    {
        return false;
    }
    
    /**
     * Magic get method
     *
     * @param string $name
     * 
     * @throws Q\Exception because this means that the instance is used, but does not exist.  
     */
    public function __get($name)
    {
        if (SiteTemplate::$name()->exists()) trigger_error("Illigal of mock object 'Q\SiteTemplate::i()'.", E_USER_ERROR);
        throw new Exception("SiteTemplate interface '{$this->_name}' does not exist.");
    }

    /**
     * Magic set method
     *
     * @param string $name
     * @param mixed  $value
     * 
     * @throws Q\Exception because this means that the instance is used, but does not exist.  
     */
    public function __set($name, $value)
    {
        if (SiteTemplate::$name()->exists()) trigger_error("Illigal of mock object 'Q\SiteTemplate::{$this->_name}'.", E_USER_ERROR);
        throw new Exception("SiteTemplate interface '{$this->_name}' does not exist.");
    }
    
    /**
     * Magic call method
     *
     * @param string $name
     * @param array  $args
     * 
     * @throws Q\Exception because this means that the instance is used, but does not exist.  
     */
    public function __call($name, $args)
    {
        if (SiteTemplate::$name()->exists()) trigger_error("Illigal of mock object 'Q\SiteTemplate::{$this->_name}'.", E_USER_ERROR);
        throw new Exception("SiteTemplate interface '{$this->_name}' does not exist.");
    }
}

if (class_exists('Q\ClassConfig', false)) ClassConfig::applyToClass('Q\SiteTemplate');

?>