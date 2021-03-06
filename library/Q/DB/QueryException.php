<?php
namespace Q;

require_once 'DB/Exception.php';

/**
 * An execption when executing a query failed.
 * @package DB
 */
class DB_QueryException extends DB_Exception
{
	/**
	 * Error message
	 * @var string
	 */
	protected $error;

	/**
	 * Query statement
	 * @var string
	 */
	protected $statement;
	
	/**
	 * Class constructor 
	 * 
	 * @param string $error      Error message
	 * @param string $statement  Query statement
	 */
	public function __construct($error, $statement)
	{
	    $this->error = $error;
	    $this->statement = $statement;
		parent::construct("Query failed: $error\nQuery: $statement");
	}
	
	/**
	 * Get error message
	 * 
	 * @return string
	 */
	public function getErrorMessage()
	{
		return $this->error;
	}
	
	/**
	 * Get query statement
	 * 
	 * @return string
	 */
	public function getStatement()
	{
		return $this->statement;
	}
}
