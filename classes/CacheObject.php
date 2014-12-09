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
		// Delete the instance
		_MyCache::delete($this->getServer(), $this->generateKey());
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

		// Look for all keys in the cache
		$aCache	= _MyCache::getMultiple(static::getServer(), static::generateKey($value));

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
	 * Generates single or multiple keys when called statically. When called
	 * from an instance that instance should be used to create a single key.
	 *
	 * @name generateKey
	 * @access protected
	 * @abstract
	 * @param string|string[] $value			Passed when called statically. Passing multiple values results in multiple keys
	 * @return string
	 */
	abstract protected function generateKey(/*string|string[]*/ $value);

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
		// Store the instance
		_MyCache::set($this->getServer(), $this->generateKey(), json_encode($this->aRecord));

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