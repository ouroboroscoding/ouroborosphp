<?php
/**
 * Simple Table base class that always assumes the class has a primary ID called `id` that auto increments
 *
 * @author chris nasr
 * @copyright fuel for the fire
 * @package core
 * @version 0.4
 * @created 2012-03-02
 */

/**
 * Include required classes
 */
$gsPath	= dirname(__FILE__);
require_once $gsPath . '/Arrays.php';
require_once $gsPath . '/SQL.php';
require_once $gsPath . '/Strings.php';

/**
 * SimpleTable class
 * @name SimpleTable
 * @package core
 * @abstract
 */
abstract class SimpleTable
{
	/**#@+
	 * Table field type constants
	 */
	const FIELD_BOOLEAN		= 0;
	const FIELD_STRING		= 1;
	const FIELD_INTEGER		= 2;
	const FIELD_DECIMAL		= 3;
	/**#@-*/

	/**#@+
	 * Table field validate constants
	 */
	const VALIDATE_EMPTY	= 0;
	const VALIDATE_BOOLEAN	= 1;
	const VALIDATE_EMAIL	= 2;
	const VALIDATE_KEY		= 3;
	const VALIDATE_LIST		= 4;
	const VALIDATE_NUMERIC	= 5;
	const VALIDATE_MINMAX	= 6;
	const VALIDATE_REGEX	= 7;
	const VALIDATE_URL		= 8;
	const VALIDATE_NOT_EMPTY= 9;
	/**#@-*/

	/**
	 * The ID of the record
	 * @var int
	 * @access protected
	 */
	protected $iID;

	/**
	 * The actual data
	 * @var array
	 * @access protected
	 */
	protected $aRecord;

	/**
	 * What's been changed since it was loaded
	 * @var array
	 * @access protected
	 */
	protected $aChangedFields;

	/**
	 * Constructor
	 * @name Table
	 * @access public
	 * @throws Exception
	 * @param uint $in_id				Record ID
	 * @param array $in_record			The record minus the ID
	 * @return Table
	 */
	public function __construct(/*uint*/ $in_id, array $in_record)
	{
		// Check the ID
		if(!is_numeric($in_id))
		{
			throw new Exception('Can not construct instance of ' . get_class($this) . ' because ID is not a valid integer.');
		}

		// Store the ID
		$this->iID	= intval($in_id);

		// If the list of fields is empty, call the child and get the info
		$aInfo		= $this->getTableInfo();

		// Go through each expected field
		foreach($aInfo['fields'] as $sField => $aFieldData)
		{
			// Check the field has a type set
			if(!isset($aFieldData['type']))
			{
				throw new Exception("Can not contruct instance of " . get_class($this) . " without a type for '{$sField}'.");
			}

			// Check if the field was passed in the array
			if(isset($in_record[$sField]))
			{
				switch($aFieldData['type'])
				{
					case self::FIELD_BOOLEAN:
						$this->aRecord[$sField]	= ($in_record[$sField]) ? true : false;
						break;
					case self::FIELD_DECIMAL:
						$this->aRecord[$sField]	= floatval($in_record[$sField]);
						break;
					case self::FIELD_INTEGER:
						$this->aRecord[$sField]	= intval($in_record[$sField]);
						break;
					default:
						$this->aRecord[$sField]	= $in_record[$sField];
						break;
				}
			}
			// Else check if it has a default value
			else if(isset($aFieldData['default']))
			{
				$this->aRecord[$sField]	= $aFieldData['default'];
			}
			// Else check if the default value has to be the current time
			else if(isset($aFieldData['current_timestamp']))
			{
				$this->aRecord[$sField]	= time();
			}
			// Else check if it's an auto_increment, in which case it's zero
			else if(isset($aFieldData['auto_increment']) && $aFieldData['auto_increment'])
			{
				$this->aRecord[$sField]	= 0;
			}
			// If it wasn't passed and there's no default, then throw an exception
			else
			{
				throw new Exception("Can not construct instance of " . get_class($this) . " without a value for '{$sField}'.");
			}
		}
	}

	/**
	 * Return all records in the table
	 * <pre>Optional arguments:
	 * order      => The field to order by
	 * return     => 'class' => instance, 'raw' => array, 'ids' => keys
	 * </pre>
	 * @name all
	 * @access public
	 * @static
	 * @param array $in_opts			Optional arguments to this method
	 * @return array					Of derived child instances
	 */
	public static function all(/*array*/ $in_opts = null)
	{
		// Check options
		if(is_null($in_opts))	$in_opts	= array();
		Arrays::checkOptions($in_opts, array(
			'order'		=> false,
			'return'	=> 'class'
		));

		// Get the child class' name and fields
		$sClass		= get_called_class();
		$aInfo		= $sClass::getTableInfo();

		// Fields
		$sFields	= ($in_opts['return'] == 'ids') ? '`id`' : '*';

		// Order
		$sOrder	= '';
		if($in_opts != 'ids')
		{
			if($in_opts['order'])
			{
				$sOrder	= "ORDER BY {$in_opts['order']}\n";
			}
			else if(isset($aInfo['order']))
			{
				if(is_array($aInfo['order']))
				{
					$sOrder	= 'ORDER BY `' . implode('`, `', $aInfo['order']) . "`\n";
				}
				else
				{
					$sOrder	= "ORDER BY `{$aInfo['order']}`\n";
				}
			}
		}

		// Generate the SQL
		$sSQL	= "SELECT {$sFields}\n" .
				"FROM {$aInfo['name']}\n" .
				"{$sOrder}";

		// Type
		$iType	= $in_opts['return'] == 'ids' ? MySQL::SELECT_COLUMN : MySQL::SELECT_ALL;

		// Get the SQL instance
		$sServer	= (is_array($aInfo['sql_server'])) ?
						$aInfo['sql_server']['read'] :
						$aInfo['sql_server'];
		$oSQL		= SQL::get($aInfo['sql_type'], $sServer);

		// Request data from DB
		$aRows	= $oSQL->select($sSQL, array(
			'charset'	=> $aInfo['charset'],
			'type'		=> iType
		));

		// If the raw data was requested
		if($in_opts['return'] != 'class')
		{
			return $aRows;
		}
		else
		{
			// Create new child instances
			$aChildren	= array();
			foreach($aRows as $aRow)
			{
				$iID	= $aRow['id'];
				unset($aRow['id']);
				$aChildren[]	= new $sClass($iID, $aRow);
			}

			// Return child instances
			return $aChildren;
		}
	}

	/**
	 * Return the record as JSON
	 * @name asJSON
	 * @access public
	 * @param bool $in_as_array			Return as array instead of
	 * @return string
	 */
	public function asJSON(/*bool*/ $in_as_array = false)
	{
		if($in_as_array)
		{
			return json_encode(array_merge(array($this->iID), array_values($this->aRecord)));
		}
		else
		{
			return json_encode(array_merge(array('id' => $this->iID), $this->aRecord));
		}
	}

	/**
	 * Clears the changes array
	 * @name clearChanged
	 * @access public
	 * @final
	 * @return void
	 */
	final protected function clearChanged()
	{
		$this->aChangedFields	= array();
	}

	/**
	 * Count
	 * Returns the total number of records in the table or by WHERE clause if field/value pairs are passed
	 * @name count
	 * @access public
	 * @param array $in_pairs			Field/Value pairs
	 * @return uint
	 */
	public static function count(/*array*/ $in_pairs = array())
	{
		// Get the child class' name and fields
		$sClass		= get_called_class();
		$aInfo		= $sClass::getTableInfo();

		// Generate SQL
		$sSQL	= "SELECT COUNT(*) FROM " . $aInfo['name'];

		// If any field/value pairs sent
		if(count($in_pairs))
		{
			// Generate lines for each field passed
			$aWhere	= array();
			foreach($in_pairs as $sField => $mValue)
			{
				// Check the field is a string and exists
				if($sField != 'id' && !isset($aInfo['fields'][$sField]))
				{
					trigger_error("\"{$sField}\" is not a valid field name in {$sClass}.", E_USER_ERROR);
				}

				// If there are multiple values
				if(is_array($mValue))
				{
					$aVals	= array();
					foreach($mValue as $mVal)
					{
						$aVals[]	= self::escapeValue($mVal, (($sField == 'id') ? self::FIELD_INTEGER : $aInfo['fields'][$sField]['type']));
					}
					$aWhere[]	= '`' . $sField . '` IN (' . implode(',', $aVals) . ')';
					unset($aVals);
				}
				else
				{
					$aWhere[]	= '`' . $sField . '` = ' . self::escapeValue($mValue, (($sField == 'id') ? self::FIELD_INTEGER : $aInfo['fields'][$sField]['type']));
				}
			}

			// Attach there WHERE clauses to the SQL
			$sSQL	.= "\nWHERE " . implode(" AND\n", $aWhere);
		}

		// Get the SQL instance
		$sServer	= (is_array($aInfo['sql_server'])) ?
						$aInfo['sql_server']['read'] :
						$aInfo['sql_server'];
		$oSQL		= SQL::get($aInfo['sql_type'], $sServer);

		// Request data from DB
		return $oSQL->select($sSQL, array(
			'charset'	=> $aInfo['charset'],
			'type'		=> SQL::SELECT_CELL
		));
	}

	/**
	 * Deletes a record from the DB
	 * @name delete
	 * @access public
	 * @return void
	 */
	public function delete()
	{
		// Get table info
		$aInfo		= $this->getTableInfo();

		// Generate SQL
		$sSQL	= "DELETE FROM {$aInfo['name']} WHERE `id` = '{$this->iID}'";

		// Get the SQL instance
		$sServer	= (is_array($aInfo['sql_server'])) ?
						$aInfo['sql_server']['write'] :
						$aInfo['sql_server'];
		$oSQL		= SQL::get($aInfo['sql_type'], $sServer);

		// Delete from DB
		$oSQL->exec($sSQL, array(
			'charset'	=> $aInfo['charset']
		));
	}

	/**
	 * Escapes a value based on it's type, then returns it ready to be inserted into an SQL statement
	 * @name escapeValue
	 * @access public
	 * @static
	 * @throws Exception
	 * @param mixed $in_value			The value to be escaped
	 * @param uint $in_type				The type TFT_* of the value
	 * @return string
	 */
	public static function escapeValue(/*mixed*/ $in_value, /*uint*/ $in_type = self::FIELD_STRING)
	{
		// If the value isn't a string type, but the variable is a string, and
		//	it starts with an ampersand, then what we want is a MySQL variable,
		//	so do nothing and send the string as is.
		if($in_type != self::FIELD_STRING	&&
			is_string($in_value)			&&
			strlen($in_value) > 1			&&
			$in_value{0} === '@')
		{
			return $in_value;
		}

		switch($in_type)
		{
			case self::FIELD_BOOLEAN:
				return '\'' . (($in_value) ? '1' : '0') . '\'';

			case self::FIELD_INTEGER:
				return '\'' . intval($in_value) . '\'';

			case self::FIELD_DECIMAL:
				return '\'' . floatval($in_value) . '\'';

			case self::FIELD_STRING:
				return '\'' . MySQL::escape($in_value) . '\'';

			default:
				throw new Exception("Invalid field type \"{$in_type}\" passed to " . __METHOD__);
		}
	}

	/**
	 * Check if a record actually exists in the DB
	 * @name exists
	 * @access public
	 * @return bool
	 */
	public function exists()
	{
		// Get the child classes name
		$sClass		= get_called_class();

		// Return if the record exists or not
		return $sClass::find($this->iID, array('exists' => true));
	}

	/**
	 * Find a record or records based on the primary field/key
	 * <pre>Optional arguments:
	 * extend_fields 'string'  => Attach this string to the list of fields
	 * group 'string'          => The GROUP BY clause
	 * join 'array'            => 'table' => name of the table, 'fields' => string or array of fields to add, 'where' statement to join the tables
	 * order 'string'          => The ORDER BY clause
	 * return 'string'         => The type of data to return, 'class' => instance, 'raw' => array, 'exists' => bool
	 * </pre>
	 * @name find
	 * @access public
	 * @param array|uint $in_id			ID(s) of the record(s)
	 * @param array $in_opts			Optional arguments to this method
	 * @return bool|array|Table			Boolean if 'exists' option is true, otherwise Array or single instance of derived table
	 */
	public static function find(/*array|uint*/ $in_id, /*array*/ $in_opts = null)
	{
		// Check if the ID is actually IDs
		$bMultiple	= is_array($in_id);

		// Check options
		if(!is_array($in_opts))	$in_opts	= array();
		Arrays::checkOptions($in_opts, array(
			'extend_fields'	=> false,
			'group'			=> false,
			'join'			=> false,
			'order'			=> false,
			'return'		=> 'class'
		));

		// Get the child class' name and fields
		$sClass		= get_called_class();
		$aInfo		= $sClass::getTableInfo();

		// Check for the join option, if it exists, is it multiple?
		if($in_opts['join'] !== false && count($in_opts['join']) > 0)
		{
			$bJoin	= true;
			if(!isset($in_opts['join'][0]))
			{
				$in_opts['join']	= array($in_opts['join']);
			}
		}
		else
		{
			$bJoin	= false;
		}

		// Figure out the tables
		if($bJoin)
		{
			$aTables	= array($aInfo['name']);
			foreach($in_opts['join'] as $aJoin)
			{
				if(is_array($aJoin['table']))
				{
					$aTables[]	= "`{$aJoin['table'][0]}` as `{$aJoin['table'][1]}`";
				}
				else
				{
					$aTables[]	= "`{$aJoin['table']}`";
				}
			}
			$sTable		= implode(',', $aTables);
			unset($aTables);
		}
		else
		{
			$sTable		= $aInfo['name'];
		}

		// Figure out the fields
		//	If we just want to check if it/they exist
		if($in_opts['return'] == 'exists')
		{
			$sFields	= 'COUNT(*)';
		}
		// If there's a table to join with
		else if($bJoin)
		{
			// Create the field and it's alias for each passed
			$aFields	= array("{$aInfo['name']}.*");
			foreach($in_opts['join'] as $aJoin)
			{
				$sJoinTable	= is_array($aJoin['table']) ? $aJoin['table'][1] : $aJoin['table'];
				foreach($aJoin['fields'] as $sField)
				{
					$aFields[]	= "`{$sJoinTable}`.`{$sField}` as `{$sJoinTable}_{$sField}`";
				}
			}

			// Add all the fields together
			$sFields	= implode(', ', $aFields);
			unset($aFields);
		}
		else
		{
			$sFields	= "*";
		}

		// If we have an extended list of fields
		if($in_opts['extend_fields'] !== false)
		{
			$sFields	.= ', ' . $in_opts['extend_fields'];
		}

		// Figure out the Where clauses
		$aWhere		= array();
		if($bMultiple)
		{
			if($in_opts['return'] == 'exists')
			{
				$in_id	= array_unique($in_id);
				$iCount	= count($in_id);
			}

			// Escape each value
			foreach($in_id as $mKey => $iID)
			{
				$in_id[$mKey]	= '\'' . intval($iID) . '\'';
			}

			$sIDs		= implode(',', $in_id);
			$aWhere[]	= "{$aInfo['name']}.`id` IN ({$sIDs})";

			unset($in_id, $sIDs);
		}
		else
		{
			$in_id		= intval($in_id);
			$aWhere[]	= "{$aInfo['name']}.`id` = '{$in_id}'";
		}

		// If we need to join another table, add the WHERE clauses
		if($bJoin)
		{
			foreach($in_opts['join'] as $aJoin)
			{
				$aWhere[]	= "{$aJoin['where']}";
			}
		}

		// Combine WHERE clauses
		$sWhere	= implode("\nAND ", $aWhere);
		unset($aWhere);

		// Initialise the Order By and Group By variables
		$sGroup	= '';
		$sOrder	= '';

		// If we're not just checking for existance
		if($in_opts['return'] != 'exists')
		{
			// If there's a group option set
			if($in_opts['group'])
			{
				$sGroup	= 'GROUP BY ' . $in_opts['group'] . "\n";
			}

			// If there's an order option set
			if($in_opts['order'])
			{
				$sOrder	= 'ORDER BY ' . $in_opts['order'] . "\n";
			}
			// Or an order set in the info array
			else if(isset($aInfo['order']))
			{
				if(is_array($aInfo['order']))
				{
					$sOrder	= 'ORDER BY `' . implode('`, `', $aInfo['order']) . "`\n";
				}
				else
				{
					$sOrder	= "ORDER BY `{$aInfo['order']}`\n";
				}
			}
		}

		// Generate SQL
		$sSQL	= "SELECT {$sFields} FROM {$sTable}\n" .
					"WHERE {$sWhere}\n" .
					"{$sGroup}" .
					"{$sOrder}";

		// Figure out type of query
		if($in_opts['return'] == 'exists')
		{
			$iType	= MySQL::SELECT_CELL;
		}
		else if($bMultiple)
		{
			$iType	= MySQL::SELECT_ALL;
		}
		else
		{
			$iType	= MySQL::SELECT_ROW;
		}

		// Get the SQL instance
		$sServer	= (is_array($aInfo['sql_server'])) ?
						$aInfo['sql_server']['read'] :
						$aInfo['sql_server'];
		$oSQL		= SQL::get($aInfo['sql_type'], $sServer);

		// Fetch the record(s)
		$aRows	= $oSQL->select($sSQL, array(
			'charset'	=> $aInfo['charset'],
			'type'		=> $iType
		));

		// If we only want to know if the record(s) exist
		if($in_opts['return'] == 'exists')
		{
			if($bMultiple)
			{
				return ($iCount == $aRows);
			}
			else
			{
				return ($aRows == 1) ? true : false;
			}
		}

		// If there's no records
		if(!count($aRows))
		{
			return null;
		}

		// If we only want the raw records
		if($in_opts['return'] == 'raw')
		{
			return $aRows;
		}

		// If there's multiple records
		if($bMultiple)
		{
			// Create instances for each
			$aChildren	= array();
			foreach($aRows as $aRow)
			{
				$iID			= $aRow['id'];
				unset($aRow['id']);
				$aChildren[]	= new $sClass($iID, $aRow);
			}

			// Return the children
			return $aChildren;
		}
		// Else one record
		else
		{
			// Create instance and return
			$iID	= $aRows['id'];
			unset($aRows['id']);
			return new $sClass($iID, $aRows);
		}
	}

	/**
	 * Find records by a specified set of fields
	 * <pre>Optional arguments:
	 * extend_fields 'string'  => Attach this string to the list of fields
	 * group 'string'          => The GROUP BY clause
	 * join 'array'            => 'table' => name of the table, 'fields' => string or array of fields to add, 'where' statement to join the tables
	 * order 'string'          => The ORDER BY clause
	 * return 'string'         => The type of data to return, 'class' => instance, 'raw' => array, 'ids' => keys
	 * </pre>
	 * @name findByFields
	 * @access public
	 * @static
	 * @param array $in_pairs			Name of field to the value to check
	 * @param array $in_opts			Additional options
	 * @return array					Array of instances or raw data
	 */
	public static function findByFields(array $in_pairs, array $in_opts = array())
	{
		// Check options
		if(!is_array($in_opts))	$in_opts	= array();
		Arrays::checkOptions($in_opts, array(
			'extend_fields'	=> false,
			'group'			=> false,
			'join'			=> false,
			'order'			=> false,
			'return'		=> 'class'
		));

		// Get the child class' name and fields
		$sClass		= get_called_class();
		$aInfo		= $sClass::getTableInfo();

		// Check for the join option, if it exists, is it multiple?
		if($in_opts['join'] !== false)
		{
			$bJoin	= true;
			if(!isset($in_opts['join'][0]))
			{
				$in_opts['join']	= array($in_opts['join']);
			}
		}
		else
		{
			$bJoin	= false;
		}

		// Figure out the table
		if($bJoin)
		{
			$aTables	= array($aInfo['name']);
			foreach($in_opts['join'] as $aJoin)
			{
				if(is_array($aJoin['table']))
				{
					$aTables[]	= "`{$aJoin['table'][0]}` as `{$aJoin['table'][1]}`";
				}
				else
				{
					$aTables[]	= "`{$aJoin['table']}`";
				}
			}
			$sTable		= implode(',', $aTables);
			unset($aTables);
		}
		else
		{
			$sTable		= $aInfo['name'];
		}

		// Figure out the fields
		if($in_opts['return'] == 'ids')
		{
			$sFields	= '`id`';
		}
		// If there's a table to join with
		else if($bJoin)
		{
			// Create the field and it's alias for each passed
			$aFields	= array("{$aInfo['name']}.*");
			foreach($in_opts['join'] as $aJoin)
			{
				$sJoinTable	= is_array($aJoin['table']) ? $aJoin['table'][1] : $aJoin['table'];
				foreach($aJoin['fields'] as $sField)
				{
					$aFields[]	= "`{$sJoinTable}`.`{$sField}` as `{$sJoinTable}_{$sField}`";
				}
			}

			// Add all the fields together
			$sFields	= implode(', ', $aFields);
			unset($aFields);
		}
		else
		{
			$sFields	= "*";
		}

		// If we have an extended list of fields
		if($in_opts['extend_fields'] !== false)
		{
			$sFields	.= ', ' . $in_opts['extend_fields'];
		}

		// Generate lines for each field passed
		$aWhere	= array();
		foreach($in_pairs as $sField => $mValue)
		{
			// Check the field is a string and exists
			if($sField != 'id' && !isset($aInfo['fields'][$sField]))
			{
				trigger_error("\"{$sField}\" is not a valid field name in {$sClass}.", E_USER_ERROR);
			}

			// If there are multiple values
			if(is_array($mValue))
			{
				$aVals	= array();
				foreach($mValue as $mVal)
				{
					$aVals[]	= self::escapeValue($mVal, (($sField == 'id') ? self::FIELD_INTEGER : $aInfo['fields'][$sField]['type']));
				}
				$aWhere[]	= '`' . $sField . '` IN (' . implode(',', $aVals) . ')';
				unset($aVals);
			}
			else
			{
				$aWhere[]	= '`' . $sField . '` = ' . self::escapeValue($mValue, (($sField == 'id') ? self::FIELD_INTEGER : $aInfo['fields'][$sField]['type']));
			}
		}

		// If we need to join another table, add the WHERE clauses
		if($bJoin)
		{
			foreach($in_opts['join'] as $aJoin)
			{
				$aWhere[]	= "{$aJoin['where']}";
			}
		}

		// Generate the Where part of the query
		$sWhere	= implode("\nAND ", $aWhere);
		unset($aWhere);

		// If there's a group option set
		$sGroup	= '';
		if($in_opts['group'])
		{
			$sGroup	= 'GROUP BY ' . $in_opts['group'] . "\n";
		}

		// If there's an order by option
		$sOrder	= '';
		if($in_opts['order'])
		{
			$sOrder	= 'ORDER BY ' . $in_opts['order'] . "\n";
		}
		// Or an order by entry in the info array and the return value isn't ids or the order is by ids
		else if(isset($aInfo['order']) &&
				($in_opts['return'] != 'ids' || $aInfo['order'] == 'id'))
		{
			if(is_array($aInfo['order']))
			{
				$sOrder	= 'ORDER BY `' . implode('`, `', $aInfo['order']) . "`\n";
			}
			else
			{
				$sOrder	= "ORDER BY `{$aInfo['order']}`\n";
			}
		}

		// Generate SQL
		$sSQL	= "SELECT {$sFields} FROM {$sTable}\n" .
					"WHERE {$sWhere}\n" .
					"{$sGroup}" .
					"{$sOrder}";

		// Figure out type of query
		if($in_opts['return'] == 'ids')
		{
			$iType	= MySQL::SELECT_COLUMN;
		}
		else
		{
			$iType	= MySQL::SELECT_ALL;
		}

		// Get the SQL instance
		$sServer	= (is_array($aInfo['sql_server'])) ?
						$aInfo['sql_server']['read'] :
						$aInfo['sql_server'];
		$oSQL		= SQL::get($aInfo['sql_type'], $sServer);

		// Fetch the record(s)
		$aRows	= $oSQL->select($sSQL, array(
			'charset'	=> $aInfo['charset'],
			'type'		=> $iType
		));

		// If there's no records
		if(!count($aRows))
		{
			return array();
		}

		// If we only want the raw records
		if($in_opts['return'] != 'class')
		{
			return $aRows;
		}

		// Create instances for each
		$aChildren	= array();
		foreach($aRows as $aRow)
		{
			$iID			= $aRow['id'];
			unset($aRow['id']);
			$aChildren[]	= new $sClass($iID, $aRow);
		}

		// Return the children
		return $aChildren;
	}

	/**
	 * Get the value of a field in the record
	 * @name getField
	 * @access public
	 * @param string $in_field
	 * @return mixed
	 */
	public function getField(/*string*/ $in_field)
	{
		// Check if the field exists
		return (isset($this->aRecord[$in_field])) ?	// Check if the field is a valid one
				$this->aRecord[$in_field] :			//	return it from the reocrd if it is
				null;								//	else return null
	}

	/**
	 * Returns the ID of the record
	 * @name getID
	 * @access public
	 * @return int
	 */
	public function getID()
	{
		return $this->iID;
	}

	/**
	 * Get a map of one field to another
	 * @name getMap
	 * @access public
	 * @param string $in_index			Name of the index field
	 * @param string $in_value			Name of the value field
	 * @param string $in_where			Optional WHERE clause
	 * @return array
	 */
	public static function getMap(/*string*/ $in_index, /*string*/ $in_value, /*string*/ $in_where = null)
	{
		// Get class name and table fields
		$sClass		= get_called_class();
		$aInfo		= $sClass::getTableInfo();

		// If a where clause was passed
		$sWhere	= (is_null($in_where)) ? '' : "WHERE {$in_where}\n";

		// Custom SQL
		$sSQL	= "SELECT `{$in_index}`, `{$in_value}`\n" .
					"FROM {$aInfo['name']}\n" .
					"{$sWhere}" .
					"ORDER BY `{$in_value}`";

		// Get the SQL instance
		$sServer	= (is_array($aInfo['sql_server'])) ?
						$aInfo['sql_server']['read'] :
						$aInfo['sql_server'];
		$oSQL		= SQL::get($aInfo['sql_type'], $sServer);

		// Get the data as a map
		$aMap	= $oSQL->select($sSQL, array(
			'charset'	=> $aInfo['charset'],
			'type'		=> MySQL::SELECT_MAP
		));

		// Return it
		return $aMap;
	}

	/**
	 * Get the possible values for an enum or set field
	 * @name getPossibleValues
	 * @access public
	 * @static
	 * @throws Exception
	 * @throws FWInvalidType
	 * @param string $in_field			Name of the field
	 * @return array					List of possible values
	 */
	public static function getPossibleValues($in_field)
	{
		// Get the class name and info
		$sClass	= get_called_class();
		$aInfo	= call_user_func(array($sClass, 'getTableInfo'));

		// Validate and escape arguments
		if(!is_string($in_field))	throw new FWInvalidType(__METHOD__, 'string', $in_field);
		$in_field	= Strings::escapeString($in_field);

		// Create SQL statement
		$sql	= "SHOW COLUMNS FROM {$aInfo['name']} LIKE '{$in_field}'";

		// Get the SQL instance
		$sServer	= (is_array($aInfo['sql_server'])) ?
						$aInfo['sql_server']['read'] :
						$aInfo['sql_server'];
		$oSQL		= SQL::get($aInfo['sql_type'], $sServer);

		// Call query
		$aRow	= $oSQL->select($sSQL, array(
			'charset'	=> $aInfo['charset'],
			'type'		=> MySQL::SELECT_ROW
		));

		// Check for a result
		if(!isset($aRow['Type']) && !isset($aRow['type']))
		{
			throw new Exception("No field `{$in_field}`.");
		}

		// Store result
		$sType	= isset($row['Type']) ? $aRow['Type'] : $aRow['type'];

		// Pull out a string of values
		preg_match('/(?:set|enum)\((.*?)\)/', $sType, $aM);

		// Pull out each value
		preg_match_all('/\'(.*?)\'(?:, )?/', $aM[1], $aM);

		// Return the values
		return $aM[1];
	}

	/**
	 * Get the name, module, fields and charset for the table
	 * @name getTableInfo
	 * @access protected
	 * @abstract
	 * @static
	 * @return array
	 */
	abstract protected static function getTableInfo();

	/**
	 * Insert a new record into the DB
	 * @name insert
	 * @access public
	 * @throws SQLDuplicateKeyException
	 * @param bool $in_ignore			Ignore duplicate key errors
	 * @param bool $in_return_sql		Return the generated SQL instead of executing it
	 * @return void
	 */
	public function insert($in_ignore = false, $in_return_sql = false)
	{
		// Local variables
		$sClass		= get_class($this);
		$aInfo		= $this->getTableInfo();
		$aFields	= array();
		$aValues	= array();
		$bAutoIncr	= isset($aInfo['auto_increment']) ? $aInfo['auto_increment'] : true;

		// If this isn't an auto_increment id then it must be passed
		if(!$bAutoIncr)
		{
			$aFields[]	= '`id`';
			$aValues[]	= $this->iID;
		}

		// Go through each field and check if we need to insert it
		foreach($aInfo['fields'] as $sField => $aFieldData)
		{
			// Add the field to the list
			$aFields[]	= '`' . $sField . '`';

			// Escape and build the value string based on the field type
			$aValues[]	= self::escapeValue($this->aRecord[$sField], $aFieldData['type']);
		}

		// If we have at least one field
		if(count($aFields))
		{
			$sIgnore	= ($in_ignore) ? 'IGNORE ' : '';

			// Implode the fields
			$sFields	= implode(',', $aFields);

			// Implode the values
			$sValues	= implode(',', $aValues);

			// Create INSERT statement
			$sSQL	= "INSERT {$sIgnore}INTO {$aInfo['name']} ({$sFields})\n" .
						"VALUES ({$sValues})\n";

			// Update the DB
			if($in_return_sql)
			{
				return $sSQL;
			}

			// Get the SQL instance
			$sServer	= (is_array($aInfo['sql_server'])) ?
							$aInfo['sql_server']['write'] :
							$aInfo['sql_server'];
			$oSQL		= SQL::get($aInfo['sql_type'], $sServer);

			// Insert the record and store the new ID
			$iLastInsertID	= (int)$oSQL->insert($sSQL, array(
				'charset'	=> $aInfo['charset']
			));

			// If this is an auto_increment table, store the new ID
			if($bAutoIncr)
			{
				$this->iID	= (int)$iLastInsertID;
			}
			unset($iLastInsertID);

			// Clear the changes array
			$this->clearChanged();
		}
	}

	/**
	 * Set Field
	 * Set a value in a record
	 * @name setField
	 * @access public
	 * @throws FWInvalidValue
	 * @param string $in_field
	 * @param mixed $in_value
	 * @return void
	 */
	public function setField(/*string*/ $in_field, /*mixed*/ $in_value)
	{
		// Get the table info from the child
		$tableInfo	= $this->getTableInfo();

		// Check the field is a valid one
		if(isset($tableInfo['fields'][$in_field]))
		{
			if($in_value != $this->aRecord[$in_field])
			{
				$this->aChangedFields[$in_field]	= true;
				$this->aRecord[$in_field]			= $in_value;
			}
		}
		// Else throw an exception
		else
		{
			throw new FWInvalidValue(__METHOD__, 'valid field name', $in_field);
		}
	}

	/**
	 * Set Fields
	 * Set a list of fields
	 * @name setFields
	 * @access public
	 * @param array $in_pairs			Name(field) / Value pairs
	 * @return void
	 */
	public function setFields(array $in_pairs)
	{
		// Get the table info from the child
		$tableInfo	= $this->getTableInfo();

		// Go through each pair
		foreach($in_pairs as $sField => $mValue)
		{
			// Check the field is a valid one
			if(isset($tableInfo['fields'][$sField]))
			{
				if($mValue != $this->aRecord[$sField])
				{
					$this->aChangedFields[$sField]	= true;
					$this->aRecord[$sField]			= $mValue;
				}
			}
		}
	}

	/**
	 * Returns the table data as a map of names to values
	 * @name toArray
	 * @access public
	 * @return array
	 */
	public function toArray(array $in_exclude = array())
	{
		// If there are no exceptions, return the record
		if(count($in_exclude) == 0)
		{
			return array_merge(array('id' => $this->iID), $this->aRecord);
		}
		// Else we need to remove some
		else
		{
			// Go through each field and only add the ones that aren't excluded
			$aRet	= !in_array('id', $in_exclude) ?
						array('id' => $this->iID) :
						array();

			foreach($this->aRecord as $sName => $sValue)
			{
				if(!in_array($sName, $in_exclude))
				{
					$aRet[$sName]	= $sValue;
				}
			}

			return $aRet;
		}
	}

	/**
	 * Update a record in the DB
	 * @name update
	 * @access public
	 * @param bool $in_force			Force update for all fields
	 * @return void
	 */
	public function update($in_force = false)
	{
		// Local variables
		$sClass		= get_class($this);
		$aInfo		= $this->getTableInfo();
		$aSets		= array();

		// Go through each field and see if it's been changed
		foreach($aInfo['fields'] as $sField => $aFieldData)
		{
			// Check if the field has been changed
			if($in_force || isset($this->aChangedFields[$sField]))
			{
				// Escape and build the string based on the field type
				$aSets[]	= " `{$sField}` = " .
								self::escapeValue($this->aRecord[$sField], $aFieldData['type']);
			}
		}

		// If at least one field has been changed
		if(count($aSets))
		{
			// Implode the sets
			$sSet	= implode(",\n", $aSets);

			// Create UPDATE statement
			$sSQL	= "UPDATE {$aInfo['name']} SET\n" .
						"{$sSet}\n" .
						"WHERE `id` = '{$this->iID}'";

			// Get the SQL instance
			$sServer	= (is_array($aInfo['sql_server'])) ?
							$aInfo['sql_server']['write'] :
							$aInfo['sql_server'];
			$oSQL		= SQL::get($aInfo['sql_type'], $sServer);

			// Update the DB
			$oSQL->exec($sSQL, array(
				'charset'	=> $aInfo['charset']
			));

			// Clear the changes array
			$this->clearChanged();
		}
	}

	/**
	 * Checks if a field has a proper value
	 * @name validateField
	 * @access public
	 * @static
	 * @param string $in_name			The name of the field
	 * @param mixed $in_value			The value
	 * @param mixed $in_validate		The validation array
	 * @return bool
	 */
	public static function validateField(/*string*/ $in_name, /*mixed*/ $in_value, /*array*/ $in_validate = null)
	{
		if(is_null($in_validate))
		{
			// Get the child classes name
			$sClass		= get_called_class();

			// Local variables
			$aInfo		= $sClass::getTableInfo();

			// If it's not set
			if(!isset($aInfo['fields'][$in_name]['validate'])	||
				!is_array($aInfo['fields'][$in_name]['validate']))
			{
				return false;
			}

			// Else store it
			$in_validate	= $aInfo['fields'][$in_name]['validate'];
		}

		// Check if the first value is EMPTY
		if($in_validate[0] == self::VALIDATE_EMPTY)
		{
			// Check if the value is empty
			if(empty($in_value))
			{
				return true;
			}
			// If not, shift the validation array and keep going
			else
			{
				$in_validate	= array_slice($in_validate, 1);
			}
		}

		// The first value tells us the type of validation
		switch($in_validate[0])
		{
			case self::VALIDATE_BOOLEAN:
				return (is_bool($in_value) || $in_value == 0 || $in_value == 1);

			case self::VALIDATE_EMAIL:
				return (Strings::validateEmail($in_value) == Strings::OK);

			case self::VALIDATE_LIST:
				return in_array($in_value, array_slice($in_validate, 1));

			case self::VALIDATE_NUMERIC:
				return is_numeric($in_value);

			case self::VALIDATE_MINMAX:
				return ($in_value >= $in_validate[1] && $in_value <= $in_validate[2]);

			case self::VALIDATE_REGEX:
				return (bool)preg_match($in_validate[1], $in_value);

			case self::VALIDATE_URL:
				return Strings::validateURL($in_value);

			case self::VALIDATE_KEY:
				if(!class_exists($in_validate[1]))
				{
					Framework::getInstance()->includeClass($in_validate[1], 'tables');
				}
				return call_user_func(array($in_validate[1], 'find'), $in_value, array('return' => 'exists'));

			case self::VALIDATE_NOT_EMPTY:
				$sTemp	= trim($in_value);
				return (empty($sTemp)) ? false : true;
		}

		// Return false by default so no one uses this function without
		//	setting a value
		return false;
	}

	/**
	 * Validate the current values of all fields
	 * @name validateFields
	 * @access public
	 * @return true|array				True if all fields are valid, else a list of bad fields
	 */
	public function validateFields()
	{
		// Errors
		$aErrors	= array();

		// Get the list of fields in this class
		$sClass		= get_class($this);
		$aInfo		= $this->getTableInfo();

		// Go through each expected field
		foreach($aInfo['fields'] as $sField => $aFieldData)
		{
			// If there's no validate field, skip it
			if(!isset($aFieldData['validate']))
			{
				if(in_array($aFieldData['type'], array(self::FIELD_DECIMAL, self::FIELD_INTEGER)))
				{
					$aFieldData['validate']	= array(self::VALIDATE_NUMERIC);
				}
				else if($aFieldData['type'] == self::FIELD_BOOLEAN)
				{
					$aFieldData['validate']	= array(self::VALIDATE_BOOLEAN);
				}
				else
				{
					continue;
				}
			}

			// Else, check it
			if(!$sClass::validateField($sField, $this->aRecord[$sField], $aFieldData['validate']))
			{
				$aErrors[]	= $sField;
			}
		}

		// If there are errors return them, else return true
		return (count($aErrors)) ? $aErrors : true;
	}
}
