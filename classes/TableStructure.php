<?php
/**
 * Table Structure
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2014-12-02
 */

/**
 * Table Structure class
 *
 * Used by the Table class and all children of the table class to define the
 * structure (fields) of the table
 *
 * @name _TableStructure
 */
class _TableStructure
{
	/**#@+
	 * Validation constants
	 */
	const REGEX_NAME	= '/^[a-zA-Z0-9_]+$/';
	/**#@-*/

	/**
	 * Valid Types
	 *
	 * Holds the list of valid field types
	 *
	 * @var string[]
	 * @access private
	 * @static
	 */
	private static $aValidTypes = array(
		'bool',			// Boolean value
		'float',		// Floating point / Decimal value
		'int',			// Integer value
		'ip',			// IP Address value
		'md5',			// MD5 hash
		'string',		// Any old string value
		'uint'			// Unsigned integer value
	);
	/**#@-*/

	/**
	 * Auto Increment
	 *
	 * Holds the flag that tells us whether the primary key is an auto-increment
	 * field or not
	 *
	 * @var bool
	 * @access private
	 */
	private $bAutoInc;

	/**
	 * Name
	 *
	 * Holds the name of the table that will be manipulated
	 *
	 * @var string
	 * @access private
	 */
	private $sName;

	/**
	 * Primary
	 *
	 * Holds the name of the primary key field in the table
	 *
	 * @var string
	 * @access private
	 */
	private $sPrimary;

	/**
	 * Fields
	 *
	 * Holds the list of fields in the table and their attributes
	 *
	 * @var array
	 * @access private
	 */
	private $aFields;

	/**
	 * Constructor
	 *
	 * Initialises the instance and makes sure the passed values are correct
	 *
	 * @name _TableStructure
	 * @access public
	 * @param string $name				The name of the table
	 * @param array $fields				The list of fields array('field' => 'type', etc)
	 * @param string $primary			The primary key field for this table
	 * @param bool $auto_inc			Wether the primary key is auto_increment or not
	 * @return _TableStructure
	 */
	public function __construct(/*string*/ $name, array $fields, /*string*/ $primary = 'id', /*bool*/ $auto_inc = true)
	{
		// Validate the name
		if(!preg_match(self::REGEX_NAME, $name)) {
			trigger_error(__CLASS__ . ' Error: Invalid characters in the table name "' . $name . '".', E_USER_ERROR);
		}

		// Store it
		$this->sName	= $name;

		// Go through each field and validate it
		foreach($fields as $sField => $sType)
		{
			// Check the name of the field
			if(!preg_match(self::REGEX_NAME, $sField)) {
				trigger_error(__CLASS__ . ' Error: Invalid characters in the field name "' . $name . '.' . $sField . '".', E_USER_ERROR);
			}

			// Check the type of the field
			if(!in_array($sType, self::$aValidTypes)) {
				trigger_error(__CLASS__ . ' Error: Invalid type "' . $sType . '" for the field "' . $name . '.' . $sField . '".', E_USER_ERROR);
			}

			// Store the field
			$this->aFields[$sField] = $sType;
		}

		// Validate the primary field is in the list of fields
		if(!isset($this->aFields[$primary])) {
			trigger_error(__CLASS__ . ' Error: Invalid primary field "' . $primary . '".', E_USER_ERROR);
		}

		// Store the primary
		$this->sPrimary = $primary;

		// Store the auto increment flag
		$this->bAutoInc = $auto_inc;
	}

	/**
	 * Escape Field
	 *
	 * Returns an escape value based on the type of the field
	 *
	 * @name escapeField
	 * @access public
	 * @param string $field				The field the value is for
	 * @param mixed $value				The value to escape
	 * @return string
	 */
	public function escapeField(/*string*/ $field, /*mixed*/ $value)
	{
		// If the field doesn't exist
		if(!isset($this->aFields[$field])) {
			trigger_error(__METHOD__ . ' Error: No such field "' . $field . '".', E_USER_ERROR);
		}

		// Return the value based on the type
		switch($this->aFields[$field])
		{
			case 'bool':
				if(is_bool($value)) return ($value) ? '1' : '0';
				else				return '' . intval($value);

			case 'ip':
			case 'md5':
			case 'string':
				return '\'' . _MySQL::escape($value) . '\'';

			case 'int':
			case 'uint':
				return '' . intval($value);

			case 'float':
				return '' . floatval($value);
		}
	}

	/**
	 * Get Auto Increment
	 *
	 * Returns the flag that lets us know if the primary key is auto increment
	 *
	 * @name getAutoIncrement
	 * @access public
	 * @return bool
	 */
	public function getAutoIncrement()
	{
		return $this->bAutoInc;
	}

	/**
	 * Get Name
	 *
	 * Returns a full table name with the proper syntax
	 *
	 * @name getName
	 * @access public
	 * @return string
	 */
	public function getName()
	{
		return $this->sName;
	}

	/**
	 * Get Fields
	 *
	 * Returns a list of all field names
	 *
	 * @name getFields
	 * @access public
	 * @return array
	 */
	public function getFields()
	{
		return $this->aFields;
	}

	/**
	 * Get Primary
	 *
	 * Return the name of the primary field
	 *
	 * @name getPrimary
	 * @access public
	 * @return string
	 */
	public function getPrimary()
	{
		return $this->sPrimary;
	}

	/**
	 * Validate Field
	 *
	 * Returns true only if the value fits the field type
	 *
	 * @name validateField
	 * @access public
	 * @param string $field				The field the value is for
	 * @param mixed $value				The value to validate
	 * @return bool
	 */
	public function validateField(/*string*/ $field, /*mixed*/ $value)
	{
		// If the field doesn't exist
		if(!isset($this->aFields[$field])) {
			trigger_error(__METHOD__ . ' Error: No such field "' . $field . '".', E_USER_ERROR);
		}

		// Return based on the type
		switch($this->aField[$field])
		{
			case 'bool':	return is_bool($value) || $value == 0 || $value == 1;
			case 'float':	return is_numeric($value);
			case 'int':		return (bool)preg_match('/^\d+$/', $value);
			case 'ip':		return (bool)preg_match('/^\d{1,3}(?:\.\d{1,3}){3}$/', $value);
			case 'md5':		return (bool)preg_match('/^[a-fA-F0-9]{32}$/', $value);
			case 'string':	return is_string($field);
			case 'uint':	return (bool)(0 <= $value && preg_match('/^\d+$/', $value));
		}
	}
}

