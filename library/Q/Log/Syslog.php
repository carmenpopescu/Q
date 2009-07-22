<?php
namespace Q;

define_syslog_variables();
openlog(!array_key_exists('HOST_NAME', $_SERVER) ? $_SERVER['SCRIPT_NAME'] : "{$_SERVER['HOST_NAME']} [client {$_SERVER['REMOTE_ADDR']}]", LOG_PID | LOG_NDELAY, LOG_USER);

require_once 'Q/Log/Text.php';

/**
 * Log to the system log.
 * 
 * @package Log
 */
class Log_Sapi extends Log
{
    /**
	 * Alias for types.
	 * @var array
	 */
	public $types = array(
		null=>LOG_INFO,
		'emerg'=>LOG_ERROR,
		'alert'=>LOG_ERROR,
		'crit'=>LOG_ERROR,
		'err'=>LOG_ERROR,
		'warn'=>LOG_WARNING,
		'notice'=>LOG_NOTICE,
		'strict'=>LOG_NOTICE,
		'info'=>LOG_INFO,
		'debug'=>LOG_INFO,
	);   

	
	/**
	 * Class constructor
	 */
	public function __construct()
	{
	    parent::__construct();
	}
		
	/**
	 * Write the log entry
	 *
	 * @param string $line
	 * @param string $type
	 */
    protected function writeLine($line, $type)
    {
		$logtype = isset($this->types[$type]) ? $this->types[$type] : $this->types[null];
		syslog($logtype, $line);
    }
}

