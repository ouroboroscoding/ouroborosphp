<?php
/**
 * Table
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2014-12-02
 */

/**
 * Table class
 *
 * Methods to modify and work with MySQL table records
 *
 * @name _Table
 * @abstract
 */
abstract class _Table
{
	/**
	 * Changed
	 *
	 * List of fields that have been updated since initialisation or since the
	 * last update/insert
	 *
	 * @var bool[]
	 * @access protected
	 */
	protected $aChanged;

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
	 * Constructor
	 *
	 * Initialises the instance and makes sure the structure is valid
	 *
	 * @name _Table
	 * @access public
	 * @param array $record				The record as an associative array
	 * @return _Table
	 */
	public function __construct(array $record)
	{
		// Get the table structure
		$oTableStructure	= $this->tableStructure();

		// If the structure isn't valid
		if(!is_a($oTableStructure, '_TableStructure')) {
			trigger_error(__CLASS__ . ' Error: getStructure must return an instances of _TableStructure.', E_USER_ERROR);
		}

		// @todo Validate fields and primary key using the table structure
		$this->aRecord	= $record;
	}

	/**
	 * Clear Changed
	 *
	 * Resets the changed flags on every field
	 *
	 * @name clearChanged
	 * @access protected
	 * @return void
	 */
	protected function clearChanged()
	{
		// Reset the array
		$this->aChanged = array();
	}

	/**
	 * Count
	 *
	 * Returns the count of records in the table. If fields are passed then the
	 * count will be based on the WHERE generated from those fields and values.
	 * e.g. array('id' => 13, 'name' => 'chris')
	 * @name count
	 * @param array $fields				A hash of fields to possible values
	 * @return uint
	 */
	public static function count(array $fields = array())
	{
		// Look up the table details
		//	First create an instance of the calling class
		$sClass	= get_called_class();
		$oClass	= new $sClass(array());

		/** Get the structure and details
		 * @var _TableStructure */
		$oStruct	= $oClass->tableStructure();
		$sName		= $oStruct->getName();
		$aFields	= $oStruct->getFields();
		$sPrimary	= $oStruct->getPrimary();

		// Build the list of SELECT fields
		$sSelect	= '`' . implode('`, `', array_keys($aFields)) . '`';

		// Go through each value
		$aWhere		= array();
		foreach($fields as $sField => $mValue)
		{
			// First make sure the field exists
			if(!isset($aFields[$sField])) {
				trigger_error(__METHOD__ . ' Error: Invalid field passed in $values argument "' . $sField . '".', E_USER_ERROR);
			}

			// If the value is an array of values
			if(is_array($mValue))
			{
				// Build the list of values
				$aValues	= array();
				foreach($mValue as $mVal) {
					$aValues[]	= $oStruct->escapeField($sField, $mVal);
				}
				$aWhere[]	= "`{$sField}` IN (" . implode(',', $aValues) . ')';
			}
			// Else if there's just one value
			else
			{
				$aWhere[]	= "`{$sField}` = " . $oStruct->escapeField($sField, $mValue);
			}
		}

		// Build the query
		$sSQL	= "SELECT COUNT(*) FROM `{$sName}`" .
					' WHERE ' . implode(' AND ', $aWhere);

		// Get and return the count
		return _MySQL::select($sSQL, _MySQL::SELECT_CELL);
	}

	/**
	 * Delete
	 *
	 * Delete the record from the DB
	 *
	 * @name delete
	 * @access public
	 * @return void
	 */
	public function delete()
	{
		/** Get the structure from the calling class
		 * @var _TableStructure */
		$oInfo		= $this->tableStructure();

		// Get the full table name, fields, and primary key
		$sName		= $oInfo->getName();
		$sPrimary	= $oInfo->getPrimary();

		// Generate SQL
		$sSQL	= "DELETE FROM `{$sName}`" .
					" WHERE `{$sPrimary}` = '{$this->aRecord[$sPrimary]}'";

		// Update the record
		_MySQL::exec($sSQL);

		// Remove the primary value from the record
		unset($this->aRecord[$sPrimary]);

		// Remove all changed states
		$this->clearChanged();
	}

	/**
	 * Find
	 *
	 * Searches for a record(s) by its primary key(s)
	 *
	 * @name find
	 * @access public
	 * @static
	 * @param mixed|mixed[] $key		A primary key or a list of primary keys
	 * @param bool $raw					If true arrays of records will be returned instead of _Table instances
	 * @return Table|Table[]|array|array[]
	 */
	public static function find(/*mixed|mixed[]*/ $key, /*bool*/ $raw = false)
	{
		// Check for multiple records versus a single record
		if(is_array($key)) {
			$bSingle	= false;
		} else {
			$bSingle	= true;
			$key		= array($key);
		}

		// Look up the table details
		//	First create an instance of the calling class
		$sClass	= get_called_class();
		$oClass	= new $sClass(array());

		/** Get the structure and details
		 * @var _TableStructure */
		$oStruct	= $oClass->tableStructure();
		$sName		= $oStruct->getName();
		$aFields	= $oStruct->getFields();
		$sPrimary	= $oStruct->getPrimary();

		// Build the list of SELECT fields
		$sSelect	= '`' . implode('`, `', array_keys($aFields)) . '`';

		// Build the list of keys
		$aKeys	= array();
		foreach($key as $mKey) {
			$aKeys[]	= $oStruct->escapeField($sPrimary, $mKey);
		}
		$sKeys	= implode(',', $aKeys);

		// Build the query
		$sSQL	= "SELECT {$sSelect} FROM `{$sName}`" .
					" WHERE `{$sPrimary}` IN ({$sKeys})";

		// Get the records
		$aRecords	= _MySQL::select($sSQL, _MySQL::SELECT_ALL);

		// If only raw records were requested
		if($raw) {
			return $bSingle ? $aRecords[0] : $aRecords;
		}

		// Else create the instances
		$aInstances	= array();
		foreach($aRecords as $aRecord) {
			$aInstances[$aRecord[$sPrimary]]	= new $sClass($aRecord);
		}

		// Return the instance or instances
		return $bSingle ? array_pop($aInstances) : $aInstances;
	}

	/**
	 * Find By Field
	 *
	 * Looks up records by a specific field
	 *
	 * @name findByField
	 * @access public
	 * @static
	 * @param string $field				The field to search
	 * @param mixed|mixed[] $value		The value or values to search by
	 * @param bool $raw					If true arrays of records will be returned instead of _Table instances
	 * @return array
	 */
	public static function findByField(/*string*/ $field, /*mixed|mixed[]*/ $value, /*bool*/ $raw = false)
	{
		// Check for multiple values versus a single value
		if(is_array($value))
		{
			// If the list if empty
			if(empty($value)) {
				return null;
			}

			$bSingle	= false;
		}
		else
		{
			$bSingle	= true;
			$value		= array($value);
		}

		// Look up the table details
		//	First create an instance of the calling class
		$sClass	= get_called_class();
		$oClass	= new $sClass(array());

		/** Get the structure and details
		 * @var _TableStructure */
		$oStruct	= $oClass->tableStructure();
		$sName		= $oStruct->getName();
		$aFields	= $oStruct->getFields();
		$sPrimary	= $oStruct->getPrimary();

		// If the field is not valid
		if(!isset($aFields[$field])) {
			trigger_error(__METHOD__ . ' Error: Invalid $field argument passed "' . $field . '".', E_USER_ERROR);
		}

		// Build the list of SELECT fields
		$sSelect	= '`' . implode('`, `', array_keys($aFields)) . '`';

		// Build the list of values
		$aValues	= array();
		foreach($value as $mValue) {
			$aValues[]	= $oStruct->escapeField($field, $mValue);
		}
		$sValues	= implode(',', $aValues);

		// Build the query
		$sSQL	= "SELECT {$sSelect} FROM `{$sName}`" .
					" WHERE `{$field}` IN ({$sValues})";

		// Get the records
		$aRecords	= _MySQL::select($sSQL, _MySQL::SELECT_ALL);

		// If only raw records were requested
		if($raw) {
			return $bSingle ? $aRecords[0] : $aRecords;
		}

		// Else create the instances
		$aInstances	= array();
		foreach($aRecords as $aRecord)
		{
			// If there's a single value
			if($bSingle)
			{
				$aInstances[$aRecord[$sPrimary]]	= new $sClass($aRecord);
			}
			// Else if there's multiple values
			else
			{
				// If the field already exists
				if(isset($aInstances[$aRecord[$field]]))
				{
					// If it's not already an array
					if(!is_array($aInstances[$aRecord[$field]])) {
						$aInstances[$aRecord[$field]]	= array($aInstances[$aRecord[$field]]);
					}

					// Append the instance
					$aInstances[$aRecord[$field]][$aRecord[$sPrimary]]	= new $sClass($aRecord);
				}
				// Else just store it under the field
				else
				{
					$aInstances[$aRecord[$field]]	= new $sClass($aRecord);
				}
			}
		}

		// Return the instance or instances
		return $aInstances;
	}

	/**
	 * Find By Fields
	 *
	 * Looks up records by a specific set of fields and values
	 *
	 * @name findByFields
	 * @access public
	 * @static
	 * @param array $values				A hash of fields to values
	 * @param string|string[]			The field or fields to order by
	 * @param bool $raw					If true arrays of records will be returned instead of _Table instances
	 * @return array
	 */
	public static function findByFields(array $values, /*string|string[]*/ $orderby = null, /*bool*/ $raw = false)
	{
		// Look up the table details
		//	First create an instance of the calling class
		$sClass	= get_called_class();
		$oClass	= new $sClass(array());

		/** Get the structure and details
		 * @var _TableStructure */
		$oStruct	= $oClass->tableStructure();
		$sName		= $oStruct->getName();
		$aFields	= $oStruct->getFields();
		$sPrimary	= $oStruct->getPrimary();

		// Build the list of SELECT fields
		$sSelect	= '`' . implode('`, `', array_keys($aFields)) . '`';

		// Go through each value
		$aWhere		= array();
		foreach($values as $sField => $mValue)
		{
			// First make sure the field exists
			if(!isset($aFields[$sField])) {
				trigger_error(__METHOD__ . ' Error: Invalid field passed in $values argument "' . $sField . '".', E_USER_ERROR);
			}

			// If the value is an array of values
			if(is_array($mValue))
			{
				// Build the list of values
				$aValues	= array();
				foreach($mValue as $mVal) {
					$aValues[]	= $oStruct->escapeField($sField, $mVal);
				}
				$aWhere[]	= "`{$sField}` IN (" . implode(',', $aValues) . ')';
			}
			// Else if there's just one value
			else
			{
				$aWhere[]	= "`{$sField}` = " . $oStruct->escapeField($sField, $mValue);
			}
		}

		// If the order by argument isn't set
		if(is_null($orderby))
		{
			$sOrderBy	= "`{$sPrimary}`";
		}
		// Else, check it
		else
		{
			// If there's only a single order by field
			if(!is_array($orderby)) {
				$orderby	= array($orderby);
			}

			// Go through each field
			$aOrderBy	= array();
			foreach($orderby as $sField)
			{
				// Make sure the field exists
				if(!isset($aFields[$sField])) {
					trigger_error(__METHOD__ . ' Error: Invalid field passed in $orderby argument "' . $sField . '".', E_USER_ERROR);
				}

				$aOrderBy[]	= "`{$sField}`";
			}
			$sOrderBy	= implode(', ', $aOrderBy);
		}

		// Build the query
		$sSQL	= "SELECT {$sSelect} FROM `{$sName}`" .
					' WHERE ' . implode(' AND ', $aWhere) .
					' ORDER BY ' . $sOrderBy;

		// Get the records
		$aRecords	= _MySQL::select($sSQL, _MySQL::SELECT_ALL);

		// If only raw records were requested
		if($raw) {
			return $aRecords;
		}

		// Else create the instances
		$aInstances	= array();
		foreach($aRecords as $aRecord) {
			$aInstances[$aRecord[$sPrimary]]	= new $sClass($aRecord);
		}

		// Return the instance or instances
		return $aInstances;
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
	 * Get Field or Default
	 *
	 * Returns the value of a field or whatever default is passed to it
	 *
	 * @name getFieldOrDefault
	 * @access public
	 * @return mixed
	 */
	public function getFieldOrDefault(/*string*/ $field, /*mixed*/ $default)
	{
		// If the value exists and isn't null/false/0
		return (isset($this->aRecord[$field]) && $this->aRecord[$field]) ?
				$this->aRecord[$field] :
				$default;
	}

	/**
	 * Table Structure
	 *
	 * Must be implemented by every child to return a _TableStructure instance
	 * that matches the table info for the child
	 *
	 * @name tableStructure
	 * @access protected
	 * @abstract
	 * @return _TableStructure
	 */
	abstract protected function tableStructure();

	/**
	 * Insert
	 *
	 * Inserts a new record with the instance's data and retrieves and stores
	 * the primary key
	 *
	 * @name insert
	 * @access public
	 * @param bool $ignore				If set ignore duplicate key errors
	 * @return void
	 */
	public function insert(/*bool*/ $ignore = false)
	{
		/** Get the structure from the calling class
		 * @var _TableStructure */
		$oInfo		= $this->tableStructure();

		// Get the full table name, fields, and primary key
		$sName		= $oInfo->getName();
		$aFields	= $oInfo->getFields();
		$sPrimary	= $oInfo->getPrimary();
		$bAutoInc	= $oInfo->getAutoIncrement();

		// Create the string of all fields and values but the primary
		$aTemp	  = array(array(), array());
		foreach($aFields as $sField => $sType)
		{
			if(($sField != $sPrimary || !$bAutoInc) && isset($this->aRecord[$sField]))
			{
				$aTemp[0][]		= '`' . $sField . '`';
				$aTemp[1][]		= $oInfo->escapeField($sField, $this->aRecord[$sField]);
			}
		}
		$sFields	= implode(',', $aTemp[0]);
		$sValues	= implode(',', $aTemp[1]);

		// Cleanup
		unset($aTemp[1], $aTemp[0], $aTemp);

		// Should we ignore duplicate keys?
		$sIgnore	= ($ignore) ? 'IGNORE ' : '';

		// Generate the SQL
		$sSQL	= "INSERT {$sIgnore}INTO `{$sName}` ({$sFields})" .
					" VALUES ({$sValues})";

		// If the primary key does not auto increment don't worry about storing
		//	the new ID
		if($bAutoInc)	$this->aRecord[$sPrimary] = _MySQL::insert($sSQL);
		else			_MySQL::exec($sSQL);

		// Clear changed fields
		$this->aChanged = array();
	}

	/**
	 * Make Column
	 *
	 * Creates a new array from one field of each record
	 *
	 * @name makeColumn
	 * @access public
	 * @static
	 * @param _Table[] $records			Array of _Table instances
	 * @param string $field				The field to make the column from
	 * @return array
	 */
	public static function makeColumn(array $records, /*string*/ $field)
	{
		// The new array
		$aColumn	= array();

		// Go through the passed in array and pull out the field from each
		foreach($records as $o) {
			$aColumn[]	= $o->get($field);
		}

		// Return the new column
		return $aColumn;
	}

	/**
	 * Make Hash
	 *
	 * Creates a new array from two fields of each record
	 *
	 * @name makeHash
	 * @access public
	 * @static
	 * @param _Table[] $records			Array of _Table instances
	 * @param string $hash				The field that will be used as the hash
	 * @param string $value				The field that will be used as the value
	 * @return array
	 */
	public static function makeHash(array $tables, /*string*/ $hash, /*string*/ $value)
	{
		// The new array
		$aHash	= array();

		// Go through the passed in array and pull out the fields from each
		foreach($tables as $o) {
			$aHash[$o->get($hash)]	  = $o->get($value);
		}

		// Return the new hash
		return $aHash;
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
			$this->aChanged[$field] = true;
		}

		// Set the field
		$this->aRecord[$field]	= $value;
	}

	/**
	 * To Array
	 *
	 * Returns the raw record data as an array
	 *
	 * @name toArray
	 * @access public
	 * @return array
	 */
	public function toArray()
	{
		return $this->aRecord;
	}

	/**
	 * Update
	 *
	 * Updates an existing record in the DB with the instance's data
	 *
	 * @name update
	 * @access public
	 * @throws Exception
	 * @param bool $force				If set, all fields are updated regardless of the changed fields array
	 * @return bool
	 */
	public function update(/*bool*/ $force = false)
	{
		/** Get the structure from the calling class
		 * @var _TableStructure */
		$oInfo		= $this->tableStructure();

		// Get the full table name, fields, and primary key
		$sName		= $oInfo->getName();
		$aFields	= $oInfo->getFields();
		$sPrimary	= $oInfo->getPrimary();

		// If there's no ID
		if(!isset($this->aRecord[$sPrimary]) || empty($this->aRecord[$sPrimary])) {
			throw new Exception('Can not update ' . get_called_class() . ' record with no primary key');
		}

		// Create the string of all fields and values but the primary
		$aTemp	  = array();
		foreach($aFields as $sField => $sType)
		{
			if($sField != $sPrimary && ($force || isset($this->aChanged[$sField])))
			{
				$aTemp[]	 = '`' . $sField . '` = ' .
								$oInfo->escapeField($sField, $this->aRecord[$sField]);
			}
		}
		$sFields	= implode(', ', $aTemp);

		// If there's nothing to update
		if(empty($sFields)) {
			return false;
		}

		// Generate SQL
		$sSQL	= "UPDATE `{$sName}` SET" .
					" {$sFields}" .
					" WHERE `{$sPrimary}` = '{$this->aRecord[$sPrimary]}'";

		// Update the record
		_MySQL::exec($sSQL);

		// Clear changed fields
		$this->aChanged = array();

		// Return that we updated
		return true;
	}
}
