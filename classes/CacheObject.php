<?php
/**
 * Cache Object
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2014-12-05
 */

/**
 * Cache Object class
 *
 * Generic base object used to store name/value pairs under md5 keys in the
 * cache
 *
 * @name _CacheObject
 * @abstract
 */
abstract class _CacheObject
{
	/**
	 * Record
	 *
	 * The actual data in the record stored by field name
	 *
	 * @var mixed[]
	 * @access protected
	 */
	protected $aRecord;

	/**
	 * Changed
	 *
	 * Triggered if anything in the record has been changed
	 *
	 * @var bool
	 * @access protected
	 */
	protected $bChanged;

	/**
	 * Constructor
	 *
	 * Initialises the instance and makes sure the structure is valid
	 *
	 * @name _CacheObject
	 * @access public
	 * @param array $record				The record related to this instance
	 * @return _CacheObject
	 */
	public function __construct(array $record)
	{
		// Get the cache structure
		$oCacheStruct	= $this->cacheStructure();

		// If the structure isn't valid
		if(!is_a($oCacheStruct, '_CacheStructure')) {
			trigger_error(__METHOD__ . ' Error: cacheStructure must return an instances of _CacheStructure.', E_USER_ERROR);
		}

		// Store the record and clear the changed state
		$this->aRecord	= $record;
		$this->bChanged	= false;
	}

	/**
	 * Delete
	 *
	 * Delete the key associated with this object
	 *
	 * @name delete
	 * @access public
	 * @return void
	 */
	public function delete()
	{
		/** Get the _CacheStructure from the child
		 * @var _CacheStructure */
		$oCache	= $this->cacheStructure();

		// Generate the values part of the key based on the field(s)
		$sValue		= $this->get($oCache->getField());

		// Generate the key
		$sKey	= $this->generateKey($oCache->getName(), $sValue);

		// Store the instance
		_MyCache::delete($oCache->getServer(), $sKey);
	}

	/**
	 * Find
	 *
	 * Finds instances of the child class in the cache
	 *
	 * @name find
	 * @param string|string[]			The value or values to search for in the cache
	 * @return _CacheObject|_CacheObject[]
	 */
	public static function find(/*string|string[]*/ $value)
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

		// Look up the cache structure
		//	First create an instance of the calling class
		$sClass	= get_called_class();
		$oClass	= new $sClass(array());

		// Then get the cache structure from it
		$oCache	= $oClass->cacheStructure();

		// Get the cache field
		$sCacheField	= $oCache->getField();

		// Look for all keys in the cache
		$aCache	= _MyCache::getMultiple($oCache->getServer(), self::generateKey($oCache->getName(), $value));

		// Go through each instance and store it
		foreach($aCache as $i => $mInstance) {
			$aInstances[$value[$i]]	= $mInstance == false ? false : new $sClass(json_decode($mInstance, true));
		}

		// Return the instance(s)
		return ($bSingle) ? $aInstances[$value[0]] : $aInstances;
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
	 * Get
	 *
	 * Returns the value of a field in the record
	 *
	 * @name get
	 * @access public
	 * @param string $field				The name of the field whose value we want
	 * @return mixed
	 */
	public function get(/*string*/ $field)
	{
		// Return the value if it exists, else null
		return isset($this->aRecord[$field]) ?
				$this->aRecord[$field] :
				null;
	}

	/**
	 * Insert
	 *
	 * Stores the object in the cache regardless of any update flags
	 *
	 * @name insert
	 * @access public
	 * @return void
	 */
	public function insert()
	{
		// Store the object in the cache
		$this->store();
	}

	/**
	 * Set
	 *
	 * Sets the value of a field in the record
	 *
	 * @name set
	 * @access public
	 * @param string $field				The name of the field to set
	 * @param mixed $value				The value to set the field to
	 * @return void
	 */
	public function set(/*string*/ $field, /*mixed*/ $value)
	{
		// If the values are different
		if(!isset($this->aRecord[$field]) || $this->aRecord[$field] !== $value) {
			// Set the changed flag
			$this->bChanged	= true;
		}

		// Set the field
		$this->aRecord[$field]	= $value;
	}

	/**
	 * Store
	 *
	 * Store the record in the cache
	 *
	 * @name store
	 * @access private
	 * @return void
	 */
	private function store()
	{
		/** Get the _CacheStructure from the child
		 * @var _CacheStructure */
		$oCache	= $this->cacheStructure();

		// Generate the values part of the key based on the field(s)
		$sValue		= $this->get($oCache->getField());

		// Generate the key
		$sKey	= $this->generateKey($oCache->getName(), $sValue);

		// Store the instance
		_MyCache::set($oCache->getServer(), $sKey, json_encode($this->aRecord));

		// Reset the changed flag
		$this->bChanged	= false;
	}

	/**
	 * Update
	 *
	 * Stores the object in the cache if any data has changed
	 *
	 * @name update
	 * @access public
	 * @return void
	 */
	public function update()
	{
		// If the changed flag is set
		if($this->bChanged) {
			// Store the object
			$this->store();
		}
	}
}