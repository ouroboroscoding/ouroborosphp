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

		// Get the cache structure
		$oCacheStruct	= $this->getCacheStructure();

		// If the structure isn't valid
		if(!is_a($oCacheStruct, '_CacheStructure')) {
			trigger_error(__METHOD__ . ' Error: getCacheStructure must return an instances of _CacheStructure.', E_USER_ERROR);
		}
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

		// Look up the table and cache details
		//	First create an instance of the calling class
		$sClass	= get_called_class();
		$oClass	= new $sClass(array());

		// Then get the structures for both
		$oTable	= $oClass->getStructure();
		$oCache	= $oClass->getCacheStructure();

		// Get the cache field
		$sCacheField	= $oCache->getField();

		// Look for all keys in the cache
		$aCache	= _MyCache::getMultiple($oCache->getServer(), self::generateKey($oTable->getName(), $value));

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
				$aInstances[$oInstance->get($sCacheField)]	= $oInstance;
			}
		}

		// Clear the cache variable so we can use it again
		$aCache = array();

		// If any instances weren't found
		if(count($aNotFound))
		{
			// Find the raw records
			$aRecords	= self::findByField($sCacheField, $aNotFound, true);

			// Go through each record returned
			foreach($aRecords as $aRecord)
			{
				// Generate the instance
				$oInstance	= new $sClass($aRecord);

				// Get additional data
				$oInstance->initCache();

				// Add it to the list of members to cache
				$aCache[self::generateKey($oTable->getName(), $aRecord[$sCacheField])]	= serialize($oInstance);

				// Add it to the list of instances we need to return
				$aInstances[$aRecord[$sCacheField]]	= $oInstance;
			}

			// Cache the members
			_MyCache::setMultiple($oCache->getServer(), $aCache);

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
	}

	/**
	 * Generate Key
	 *
	 * Generates a cache key or keys based on the table name and field(s)
	 *
	 * @name generateKey
	 * @access protected
	 * @static
	 * @param string $name				The primary part of the key
	 * @param string|string[] $value	The value or values used to make the key or keys
	 * @return string[]
	 */
	protected static function generateKey(/*string*/ $name, /*string|string[]*/ $value)
	{
		// If multiple values were passed
		if(is_array($value))
		{
			$aRet	= array();
			foreach($value as $v) {
				$aRet[] = md5($name . ':' . $v);
			}
			return $aRet;
		}
		// Else if only one value was passed
		else
		{
			return md5($name . ':' . $value);
		}
	}

	/**
	 * Get Cache Structure
	 *
	 * Must be implemented by every child to return a _CacheStructure instance
	 * that matches the cache info for the child
	 *
	 * @name getCacheStructure
	 * @access protected
	 * @abstract
	 * @return _CacheStructure
	 */
	abstract protected function getCacheStructure();

	/**
	 * Init Cache
	 *
	 * Does nothing, meant to be overriden by child classes in order to setup
	 * other parts of the class before the cache is stored
	 *
	 * @name initCache
	 * @access protected
	 * @return void
	 */
	protected function initCache() {}

	/**
	 * Store Cache
	 *
	 * Stores the instance in the cache based on the _CacheStructure
	 *
	 * @name storeCache
	 * @access protected
	 * @return void
	 */
	protected function storeCache()
	{
		/** Get the _TableStructure from the child
		 * @var _TableStructure */
		$oTable	= $this->getCacheStructure();

		// Get the table name
		$sName		= $oTable->getName();

		/** Get the _CacheStructure from the child
		 * @var _CacheStructure */
		$oCache	= $this->getCacheStructure();

		// Get the server and field
		$sServer	= $oCache->getServer();
		$sField		= $oCache->getField();

		// Generate the values part of the key based on the field(s)
		$sValue		= $this->get($sField);

		// Generate the key
		$sKey	= $this->generateKey($sName, $sValue);

		// Store the instance
		_MyCache::set($sServer, $sKey, serialize($this));
	}

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
		$bRet	= $this->updateCache() ? true : $bRet;

		// If we updated anything
		if($bRet)
		{
			// Store the instance in the cache
			$this->storeCache();
		}
	}

	/**
	 * Update Cache
	 *
	 * Does nothing, meant to be overriden by child classes in order to update
	 * other parts of the class before the cache is updated
	 *
	 * @name updateCache
	 * @access protected
	 * @return bool
	 */
	protected function updateCache()
	{
		return false;
	}
}