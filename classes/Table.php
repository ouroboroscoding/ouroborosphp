<?php
/**
 * Table
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2014-12-02
 */

/**
 * Required Classes
 */
require_once 'ouroboros/classes/TableStructure.php';

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
		$oTableStructure	= $this->getStructure();

		// If the structure isn't valid
		if(!($oTableStructure instanceof _TableStructure)) {
			trigger_error('Table Error: getStructure must return an instances of _TableStructure.', E_USER_ERROR);
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
		$oInfo		= $this->getStructure();

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
	 * Get Structure
	 *
	 * Must be implemented by every child to return a _TableStructure instance
	 * that matches the table info for the child
	 *
	 * @name getStructure
	 * @access public
	 * @abstract
	 * @return _TableStructure
	 */
	abstract public function getStructure();

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
		$oInfo		= $this->getStructure();

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
	 * @return void
	 */
	public function update(/*bool*/ $force = false)
	{
		/** Get the structure from the calling class
		 * @var _TableStructure */
		$oInfo		= $this->getStructure();

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

		// If there's fields to update
		if(!empty($sFields))
		{
			// Generate SQL
			$sSQL	= "UPDATE `{$sName}` SET" .
						" {$sFields}" .
						" WHERE `{$sPrimary}` = '{$this->aRecord[$sPrimary]}'";

			// Update the record
			_MySQL::exec($sSQL);

			// Clear changed fields
			$this->aChanged = array();
		}
	}
}
