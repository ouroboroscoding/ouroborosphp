<?php
/**
 * Cache Table
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2014-12-05
 */

/**
 * Cache Table class
 *
 * Extends the table class to add basic caching functionality
 *
 * @name _CacheTable
 * @extends _Table
 * @abstract
 */
abstract class _CacheTable extends _Table
{
	/**
	 * Constructor
	 *
	 * Initialises the instance and makes sure the structure is valid
	 *
	 * @name _CacheTable
	 * @access public
	 * @param array $record				The record as an associative array
	 * @return _CacheTable
	 */
	public function __construct(array $record)
	{
		// Call the parent constructor
		parent::__construct($record);
	}

	/**
	 * Cache Store
	 *
	 * Stores the instance in the cache based on the _CacheStructure
	 *
	 * @name cacheStore
	 * @access private
	 * @return void
	 */
	private function cacheStore()
	{
		// Store the instance
		_MyCache::set($this->getServer(), $this->generateKey(), serialize($this));
	}

	/**
	 * Cache Update
	 *
	 * Does nothing, meant to be overriden by child classes in order to update
	 * other parts of the class before the cache is updated
	 *
	 * @name cacheUpdate
	 * @access protected
	 * @return bool
	 */
	protected function cacheUpdate()
	{
		return false;
	}

	/**
	 * Find In Cache
	 *
	 * Finds instances of the child class in the cache
	 *
	 * @name findInCache
	 * @param string|string[]			The value or values to search for in the cache
	 * @return _CacheTable|_CacheTables[]
	 */
	public static function findInCache(/*string|string[]*/ $value)
	{
		// Check for multiple values versus a single value
		if(is_array($value)) {
			$bSingle	= false;
		} else {
			$bSingle	= true;
			$value		= array($value);
		}

		// Init the local variables
		$aNotFound	= array();
		$aInstances	= array();

		// Remove any possible gaps from the list of values
		$value	= array_values($value);

		// If the list of values is empty
		if(empty($value)) {
			return null;
		}

		// Get the called class and generate an empty one
		$sClass	= get_called_class();
		$oClass	= new $sClass(array());

		// Look for all keys in the cache
		$aCache	= _MyCache::getMultiple($oClass->getServer(), $oClass->generateKey($value));

		// Go through each instance and verify it was found
		foreach($aCache as $i => $mInstance)
		{
			// If the key wasn't found, store the value
			if(false === $mInstance)
			{
				$aNotFound[]	= $value[$i];
			}
			// Else store the instance under its value
			else
			{
				$oInstance	= unserialize($mInstance);
				$aInstances[$value[$i]]	= $oInstance;
			}
		}

		// Clear the cache variable so we can use it again
		$aCache = array();

		// If any instances weren't found
		if(count($aNotFound))
		{
			// Find the instances
			$aObjects	= $oClass->getMissing($aNotFound);

			// Go through each record returned
			foreach($aObjects as $sValue => $oObject)
			{
				// Add it to the list of members to cache
				$aCache[$oObject->generateKey()]	= serialize($oObject);

				// Add it to the list of instances we need to return
				$aInstances[$sValue]	= $oObject;
			}

			// Cache the members
			_MyCache::setMultiple($oClass->getServer(), $aCache);

			// Make sure we set all the values just in case one wasn't found
			foreach($value as $v) {
				if(!isset($aInstances[$v])) {
					$aInstances[$v]	= null;
					trigger_error(__METHOD__ . ' Warning: ' . $v . ' was not found', E_USER_WARNING);
				}
			}
		}

		// If we are returning one
		if($bSingle) {
			return $aInstances[$value[0]];
		}

		// Make sure we return everything in the same order that it was
		//	requested
		$aReturn	= array();
		foreach($value as $v) {
			$aReturn[$v]	= $aInstances[$v];
		}

		// Return the instances
		return $aReturn;
	}

	/**
	 * Generate Key
	 *
	 * Generates single or multiple keys when called with passed values. When
	 * called with no value the instance used should create a single key.
	 *
	 * @name generateKey
	 * @access protected
	 * @abstract
	 * @param string|string[] $value			Passed when called statically. Passing multiple values results in multiple keys
	 * @return string
	 */
	abstract protected function generateKey(/*string|string[]*/ $value);

	/**
	 * Get Missing
	 *
	 * Is passed the values of all missing instances and should return a list of
	 * instances hashed by their value. Used by findInCache to cache records
	 * that could not be found
	 *
	 * @name getMissing
	 * @access protected
	 * @abstract
	 * @param string[] $values			The list of values that did not return a result
	 * @return _CacheTable[]
	 */
	abstract protected function getMissing(array $values);

	/**
	 * Get Server
	 *
	 * Should return the server to use to store keys
	 *
	 * @name getServer
	 * @access protected
	 * @abstract
	 * @return string
	 */
	abstract protected function getServer();

	/**
	 * Update
	 *
	 * Overrides _Table::update in order to store the cache
	 *
	 * @name update
	 * @see _Table::update
	 * @return bool
	 */
	public function update(/*bool*/ $force = false)
	{
		// Call the parent update
		$bRet	= parent::update($force);

		// Let the child update other aspects of itself
		$bRet	= $this->cacheUpdate() ? true : $bRet;

		// If we updated anything
		if($bRet)
		{
			// Store the instance in the cache
			$this->cacheStore();
		}
	}
}