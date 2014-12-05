<?php
/**
 * Cache Structure
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2014-12-05
 */

/**
 * Cache Structure class
 *
 * Used by the _CacheTable class and all children of the class to define the
 * structure (fields) of the cache
 *
 * @name _CacheStructure
 */
class _CacheStructure
{
	/**#@+
	 * Validation constants
	 */
	const REGEX_SERVER	= '/^[a-zA-Z0-9_-]+$/';
	const REGEX_FIELD	= '/^[a-zA-Z0-9_]+$/';
	/**#@-*/

	/**
	 * Server
	 *
	 * The server the keys are stored on
	 *
	 * @var string
	 * @access private
	 */
	private $sServer;

	/**
	 * Field
	 *
	 * Holds the name of the field or fields used to make the cache key
	 *
	 * @var string
	 * @access private
	 */
	private $sField;

	/**
	 * Constructor
	 *
	 * Initialises the instance and makes sure the passed values are correct
	 *
	 * @name _CacheStructure
	 * @access public
	 * @param string $server			The name of the server the cache is on
	 * @param string $field				The field to use to make the cache key
	 * @return _CacheStructure
	 */
	public function __construct(/*string*/ $server, /*string|strings*/ $field)
	{
		// Validate the server
		if(!preg_match(self::REGEX_SERVER, $server)) {
			trigger_error(__METHOD__ . ' Error: Invalid characters in the cache server', E_USER_ERROR);
		}

		// Validate the field
		if(!preg_match(self::REGEX_FIELD, $field)) {
			trigger_error(__METHOD__ . ' Error: Invalid characters in the cache field', E_USER_ERROR);
		}

		// Store the variables
		$this->sServer	= $server;
		$this->sField	= $field;
	}

	/**
	 * Get Server
	 *
	 * Returns the cache server
	 *
	 * @name getServer
	 * @access public
	 * @return string
	 */
	public function getServer()
	{
		return $this->sServer;
	}

	/**
	 * Get Field
	 *
	 * Returns the field
	 *
	 * @name getField
	 * @access public
	 * @return string
	 */
	public function getField()
	{
		return $this->sField;
	}
}
