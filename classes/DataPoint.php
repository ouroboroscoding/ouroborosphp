<?php
/**
 * Data Point
 *
 * Holds data types and data point classes
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2014-12-02
 */

/**
 * Type enum
 *
 * @name _EDataType
 * @extends SplEnum
 */
class _EDataType extends SplEnum
{
	const __default		= self::STRING;
	const BOOL			= 1;
	const DATE			= 2;
	const DATETIME		= 3;
	const DECIMAL		= 4;
	const INT			= 5;
	const IP			= 6;
	const MD5			= 7;
	const STRING		= 8;
	const TIME			= 9;
	const TIMESTAMP		= 10;
	const UINT			= 11;

	public static $toName	= array(
		self::BOOL		=> 'BOOL',
		self::DATE		=> 'DATE',
		self::DATETIME	=> 'DATETIME',
		self::DECIMAL	=> 'DECIMAL',
		self::INT		=> 'INT',
		self::IP		=> 'IP',
		self::MD5		=> 'MD5',
		self::STRING	=> 'STRING',
		self::TIME		=> 'TIME',
		self::TIMESTAMP	=> 'TIMESTAMP',
		self::UINT		=> 'UINT'
	);
}

/**
 * Data Point class
 *
 * @name _DataPoint
 */
class _DataPoint
{
	/**
	 * Options
	 *
	 * Holds the optional values associated with specific data types
	 *
	 * @var mixed[string]
	 * @access protected
	 */
	protected $aOptions	= array();

	/**
	 * To Regex
	 *
	 * Holds a hash of data types to their respective regular expressions used
	 * for validation
	 */
	protected $aToRegex = array(
		_EDataType::DATE		=> '/^\d{4}-(?:0[1-9]|1[0-2])-(?:0[1-9]|[12]\d|3[01])$/',
		_EDataType::DATETIME	=> '/^\d{4}-(?:0[1-9]|1[0-2])-(?:0[1-9]|[12]\d|3[01]) (?:[01]\d|2[0-3])(:[0-5]\d){2}$/',
		_EDataType::INT			=> '/^[+-]?\d+$/',
		_EDataType::IP			=> '/^(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[1-9])(?:\.(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])){2}\.(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[1-9])$/',
		_EDataType::MD5			=> '/^[a-fA-F0-9]{32}$/',
		_EDataType::TIME		=> '/^(?:[01]\d|2[0-3])(:[0-5]\d){2}$/'
	);

	/**
	 * Type
	 *
	 * Holds the current data type of the instance
	 *
	 * @var _EDataType
	 * @access protected
	 */
	protected $eType	= null;

	/**
	 * Constructor
	 *
	 * Creates and initialises the instance
	 *
	 * @name DataPoint
	 * @access public
	 * @param _EDataType $type			The type of data point
	 * @param array $options			Optional data used to validate values
	 * @return _DataPoint
	 */
	public function __construct(/*uint*/ $type, array $options = array())
	{
		// If the type is not valid
		try {
			new _EDataType($type);
		} catch(UnexpectedValueException $e) {
			trigger_error(__METHOD__ . ' first argument ($type) must be a valid _EDataType', E_USER_ERROR);
		}

		// Store the type
		$this->eType	= $type;

		// If we are creating a DATE, DATETIME, IP or TIME data point
		if(in_array($type, array(_EDataType::DATE, _EDataType::DATETIME, _EDataType::IP, _EDataType::TIME)))
		{
			// Is the minimum set?
			if(isset($options['min']))
			{
				// Is it valid?
				if(!preg_match(self::$aToRegex[$type], $options['min'])) {
					trigger_error(__METHOD__ . ' optional argument "min" must be a valid value based on the type: ' . _EDataType::$toName[$type], E_USER_ERROR);
				}

				// Store it
				$this->aOptions['min']	= $options['min'];
			}

			// Is the max set?
			if(isset($options['max']))
			{
				// Is it valid?
				if(!preg_match(self::$aToRegex[$type], $options['max'])) {
					trigger_error(__METHOD__ . ' optional argument "max" must be a valid value based on the type: ' + _EDataType::$toName[$type], E_USER_ERROR);
				}

				// Store it
				$this->aOptions['max']	= $options['max'];
			}
		}
		// Else if we are creating a signed, unsigned integer (timestamp is just
		//	an unsigned integer), or string lengths
		else if(in_array($type, array(_EDataType::INT, _EDataType::STRING, _EDataType::TIMESTAMP, _EDataType::UINT)))
		{
			// Is the minimum set?
			if(isset($options['min']))
			{
				// Is it a valid int?
				if(is_numeric($options['min']))
				{
					// Convert it regardless of int or string
					$options['min']	= intval($options['min']);

					// If we are not dealing with a signed integer
					if($type != _EDataType::INT)
					{
						// Value must be above 0
						if($options['min'] < 0) {
							trigger_error(__METHOD__ . ' optional argument "min" must be greater than 0 (zero) for unsigned integers', E_USER_ERROR);
						}
					}

					// Store it
					$this->aOptions['min']	= $options['min'];
				}
				// Else it's invalid
				else {
					trigger_error(__METHOD__ . ' optional argument "min" must be a valid value based on the type: ' . _EDataType::$toName[$type], E_USER_ERROR);
				}
			}

			// Is the maximum set?
			if(isset($options['max']))
			{
				// Is it a valid int?
				if(is_numeric($options['max']))
				{
					// Convert it regardless of int or string
					$options['max']	= intval($options['max']);

					// If we are not dealing with a signed integer
					if($type != _EDataType::INT)
					{
						// Value must be above 0
						if($options['max'] < 0) {
							trigger_error(__METHOD__ . ' optional argument "max" must be greater than 0 (zero) for unsigned integers', E_USER_ERROR);
						}
					}

					// Store it
					$this->aOptions['max']	= $options['max'];
				}
				// Else it's not valid
				else {
					trigger_error(__METHOD__ . ' optional argument "max" must be a valid value based on the type: ' . _EDataType::$toName[$type], E_USER_ERROR);
				}
			}

			// If we are dealing with a string and the regex is set
			if($type == _EDataType::STRING && isset($options['regex']))
			{
				// If it's not a valid string
				if(!is_string($options['regex'])) {
					trigger_error(__METHOD__ . ' optional argument "regex" must be a valid string.', E_USER_ERROR);
				}

				// Else store it
				$this->aOptions['regex']	= $options['regex'];
			}
		}
		// Else if we are creating a floating point value
		else if($type == _EDataType::DECIMAL)
		{
			// Is the minimum set?
			if(isset($options['min']))
			{
				// If it's an invalid floating point value
				if(!is_float($options['min'])) {
					trigger_error(__METHOD__ . ' optional argument "min" must be a valid value based on the type: ' . _EDataType::$toName[$type], E_USER_ERROR);
				}

				// Else store it
				$this->aOptions['min']	= floatval($options['min']);
			}

			// Is the maximum set?
			if(isset($options['max']))
			{
				// If it's an invalid floating point value
				if(!is_float($options['max'])) {
					trigger_error(__METHOD__ . ' optional argument "max" must be a valid value based on the type: ' . _EDataType::$toName[$type], E_USER_ERROR);
				}

				// Else store it
				$this->aOptions['max']	= floatval($options['max']);
			}
		}

		// If we have a min and a max make sure they make sense together
		if(isset($this->aOptions['min']) && isset($this->aOptions['max']))
		{
			// If the type is an IP
			if($type == _EDataType::IP)
			{
				// If the min is above the max, we have a problem
				if($this->ip_cmp($this->$aOptions['min'], $this->aOptions['max']) == 1) {
					trigger_error(__METHOD__ . ' min exceeds max: min => ' . $this->aOptions['min'] . ', max => ' + $this->aOptions['max'], E_USER_ERROR);
				}
			}
			// Else any other data type
			else
			{
				// If the min is above the max, we have a problem
				if($this->aOptions['min'] > $this->aOptions['max']) {
					trigger_error(__METHOD__ . ' min exceeds max: min => ' . $this->aOptions['min'] . ', max => ' . $this->aOptions['max'], E_USER_ERROR);
				}
			}
		}
	}

	/**
	 * IP Compare
	 *
	 * Compares two IPs and returns a status based on which is greater
	 * If x is less than y: -1
	 * If x is equal to y: 0
	 * If x is greater than y: 1
	 *
	 * @name ip_cmp
	 * @access protected
	 * @static
	 * @param string $x				A string representing an IP address
	 * @param string $y				A string representing an IP address
	 * @return int					-1, 0, 1
	 */
	protected static function ip_cmp(/*string*/ $x, /*string*/ $y)
	{
		// If they are the same, just return immediately
		if($x == y) {
			return 0;
		}

		// If they are not the same, split each IP into 4 parts
		$aX	= array_map('intval', explode('.', $x));
		$aY	= array_map('intval', explode('.', $y));

		// Go through each part from left to right until we find the
		//	difference
		for($i = 3; $i > -1; --$i)
		{
			// If the part of x is greater than the part of y
			if($aX[$i] > $aY[$i]) {
				return 1;
			}

			// Else if the part of x is less than the part of y
			else if($aX[$i] < $aY[$i]) {
				return -1;
			}
		}
	}

	/**
	 * Type
	 *
	 * Returns the type for the given instance
	 *
	 * @name type
	 * @access public
	 * @return _EDataType
	 */
	public function type()
	{
		return $this->type;
	}

	/**
	 * Valid
	 *
	 * Returns true if the given value fits the current instance
	 *
	 * @name valid
	 * @access public
	 * @param mixed val					The value to validate
	 * @return bool
	 */
	public function valid(/*mixed*/ $val)
	{
		// If we are validating a DATE, DATETIME, IP or TIME data point
		if(in_array($this->type, array(EDataType.DATE, EDataType.DATETIME, EDataType.IP, EDataType.MD5, EDataType.TIME)))
		{
			// If there's no match
			if(!preg_match(self::$aToRegex[$type], val)) {
				return false;
			}
		}
		// Else if we are validating some sort of integer
		else if(in_array($this->type, array(EDataType.INT, EDataType.TIMESTAMP, EDataType.UINT)))
		{
			// If the type is a bool, fail immediately
			if(is_bool($val)) {
				return false;
			}

			// If it not an int or a string representing an int
			if(!is_numeric(val)) {
				return false;
			}

			// If it's not signed
			if($this->type != _EDataType::INT)
			{
				// If the value is below 0
				if(val < 0) {
					return false;
				}
			}
		}
		// Else if we are validating a bool
		else if($this->type == _EDataType::BOOL)
		{
			// If it's already a bool
			if(is_bool(val)) {
				return true;
			}

			// If it's an int or long at 0 or 1
			if(is_numeric(val) && ($val == 0 or $val == 1)) {
				return true;
			}

			// If it's a string
			if(is_string($val))
			{
				// If it's t, T, 1, f, F, or 0
				return in_array($val, array('true', 'TRUE', 't', 'T', '1', 'false', 'FALSE', 'f', 'F', '0'));
			}

			// Else it's no valid type
			return false;
		}
		// Else if we are validating a floating point value
		else if($this->type == _EDataType::DECIMAL)
		{
			// If the type is a bool, fail immediately
			if(is_bool($val)) {
				return false;
			}

			// If it's not a float
			if(!is_float($val)) {
				return false;
			}
		}
		// Else if we are validating a string value
		else if($this->type == _EDataType::STRING)
		{
			// If the value is not some form of string
			if(!is_string($val)) {
				return false;
			}

			// If we have a regex
			if(isset($this->aOptions['regex']))
			{
				// If it doesn't match the regex
				if(!preg_match('/' . $this->aOptions['regex'] . '/', $val)) {
					return false;
				}
			}

			// If there's a minimum length and we don't reach it
			if(isset($this->aOptions['min']) && strlen($val) < $this->aOptions['min']) {
				return false;
			}

			// If there's a maximum length and we surpass it
			if(isset($this->aOptions['max']) && strlen($val) > $this->aOptions['max']) {
				return false;
			}

			// Return ok
			return true;
		}

		# If there is a minimum value (for non bools and strings)
		if(isset($this->aOptions['min']))
		{
			// If we are checking an IP
			if($this->type == EDataType.IP)
			{
				// If the IP is less than the minimum
				if($this->ip_cmp($val, $this->aOptions['min']) == -1) {
					return false;
				}
			}
			// Else
			else
			{
				// If the value is less than the minimum
				if($val < $this->aOptions['min']) {
					return false;
				}
			}
		}

		// If there is a maximum value (for non bools and strings)
		if(isset($this->aOptions['max']))
		{
			// If we are checking an IP
			if($this->type == EDataType.IP)
			{
				// If the IP is greater than the maximum
				if($this->ip_cmp($val, $this->aOptions['max']) == 1) {
					return false;
				}
			}
			// Else
			else
			{
				// If the value is greater than the maximum
				if($val > $this->aOptions['max']) {
					return false;
				}
			}
		}

		// Value has no issues
		return true;
	}
}
