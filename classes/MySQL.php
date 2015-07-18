<?php
/**
 * MySQL
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2014-12-02
 */

/**
 * MySQL Class
 *
 * Handles all connections and queries to MySQL databases. Uses configuration
 * values in 'mysql'
 *
 * @name _MySQL
 */
class _MySQL
{
	/**#@+
	 * Select constants
	 * @var uint
	 */
	const SELECT_ALL		= 0;
	const SELECT_ROW		= 1;
	const SELECT_COLUMN		= 2;
	const SELECT_CELL		= 3;
	const SELECT_HASH		= 4;
	CONST SELECT_HASHROWS	= 5;
	/**#@-*/

	/**
	 * Connections
	 *
	 * Holds the mysqli instances for different servers
	 *
	 * @var mysqli[]
	 * @access private
	 * @static
	 */
	private static $aCons	= array();

	/**
	 * Log
	 *
	 * Set to an array if logging is turned on
	 *
	 * @var false|array
	 * @access private
	 * @static
	 */
	private static $aLog	= false;

	/**
	 * Constructor
	 *
	 * Private so this class can never be instantiated
	 *
	 * @name _MySQL
	 * @access private
	 * @return _MySQL
	 */
	private function __constructor() {}

	/**
	 * Clear Connection
	 *
	 * Resets the connection variable so we try to connect again
	 *
	 * @name clearConnection
	 * @access private
	 * @param string $type				The type of connection
	 * @return void
	 */
	private static function clearConnection(/*string*/ $type)
	{
		// If the variable exists
		if(isset(self::$aCons[$type]))
		{
			// Close it and unset it
			self::$aCons[$type]->close();
			unset(self::$aCons[$type]);
		}
	}

	/**
	 * Escape
	 *
	 * Escapes a string so it's ready to be put in an SQL statement
	 *
	 * @name escape
	 * @access public
	 * @static
	 * @param string $text				The text to escape
	 * @param string $type				Type of connection to use to escape the string
	 * @return string
	 */
	public static function escape(/*string*/ $text, /*string*/ $type = 'write')
	{
		// Get the write server connection
		$oCon	= self::fetchConnection($type);

		// And escpae the string and return it
		return $oCon->real_escape_string($text);
	}

	/**
	 * Exec
	 *
	 * Execute a particular SQL statement and return the number of affected rows
	 * INSERT, UPDATE, ALTER, etc
	 *
	 * @name exec
	 * @access public
	 * @static
	 * @throws _MySQL_Exception
	 * @param string $sql				SQL statement to run
	 * @param string $select			Alternate SQL statement to run and return
	 * @return uint
	 */
	public static function exec(/*string*/ $sql, /*string*/ $select = null)
	{
		// Get the write server connection
		$oCon	= self::fetchConnection('write');

		// If logging is on
		if(false !== self::$aLog) {
			self::$aLog[]	= array(
				'timestamp'	=> date('Y-m-d H:i:s'),
				'type'		=> 'EXEC',
				'sql'		=> $sql
			);
		}

		// If the query fails, return false
		if(!$oCon->real_query($sql)) {
			// If we lost the MySQL connection
			if($oCon->errno == 2006) {
				// Clear it and rerun the query after pausing for a second
				sleep(1);
				self::clearConnection('write');
				return self::exec($sql, $select);
			} else {
				throw new _MySQL_Exception(__METHOD__ . ' (' . $oCon->errno . '): ' . $oCon->error . "\n{$sql}", $oCon->errno);
			}
		}

		// Check if a select statement was passed
		if(!is_null($select))	return self::select($select, self::SELECT_CELL);

		// If there was no select, return affected rows
		return $oCon->affected_rows;
	}

	/**
	 * Fetch Connection
	 *
	 * Returns a connection to the given server, if there isn't one, it creates
	 * the instance and connects before returning
	 *
	 * @name fetchConnection
	 * @access private
	 * @static
	 * @throws _MySQL_Exception
	 * @param string $type				The type of connection
	 * @return mysqli
	 */
	private static function fetchConnection(/*string*/ $type, /*uint*/ $count = 0)
	{
		// If we already have the connection
		if(isset(self::$aCons[$type])) {
			// Return it
			return self::$aCons[$type];
		}

		// Store the type in the conf
		$aConf	= $type;

		// While the conf is a string (which will always be true the first time)
		while(is_string($aConf))
		{
			// Get the config info
			$aConf	= _Config::get(array('mysql', $aConf), array(
				'host'		=> 'localhost',
				'user'		=> 'root',
				'passwd'	=> '',
				'dbname'	=> 'db',
				'port'		=> '3306',
				'charset'	=> 'utf8'
			));
		}

		// Create a new instance of mysqli
		$oMySQLI	= new mysqli(
			$aConf['host'],
			$aConf['user'],
			$aConf['passwd'],
			$aConf['dbname'],
			$aConf['port']
		);

		// Check the connection was ok
		if($oMySQLI->connect_errno) {
			if(++$count == 5) {
				throw new _MySQL_Exception('Failed to connect to MySQL server: (' . $oMySQLI->connect_errno . ') ' . $oMySQLI->connect_error, $oMySQLI->connect_errno);
			} else {
				sleep(1);
				return self::fetchConnection($type, $count);
			}
		}

		// Change the charset
		if(!$oMySQLI->set_charset($aConf['charset'])) {
			throw new _MySQL_Exception('Failed to change charset: (' . $oMySQLI->errno . ') ' . $oMySQLI->error);
		}

		// Return the instance
		return (self::$aCons[$type] = $oMySQLI);
	}

	/**
	 * Insert
	 *
	 * Insert a row into a table and return the new ID
	 *
	 * @name insert
	 * @access public
	 * @static
	 * @throws _MySQL_Exception
	 * @param string $sql				SQL statement to run
	 * @return int
	 */
	public static function insert(/*string*/ $sql)
	{
		// Get the write server connection
		$oCon	= self::fetchConnection('write');

		// If logging is on
		if(false !== self::$aLog) {
			self::$aLog[]	= array(
				'timestamp'	=> date('Y-m-d H:i:s'),
				'type'		=> 'EXEC',
				'sql'		=> $sql
			);
		}

		// If the query fails, return false
		if(!$oCon->real_query($sql)) {
			// If we lost the MySQL connection
			if($oCon->errno == 2006) {
				// Clear it and rerun the query after pausing for a second
				sleep(1);
				self::clearConnection('write');
				return self::insert($sql);
			} else {
				throw new _MySQL_Exception(__METHOD__ . ' (' . $oCon->errno . '): ' . $oCon->error . "\n{$sql}", $oCon->errno);
			}
		}

		// Return the last inserted ID
		return $oCon->insert_id;
	}

	/**
	 * Log Display
	 *
	 * Displays all the logged SQL calls
	 *
	 * @name logDisplay
	 * @access public
	 * @static
	 * @return void
	 */
	public static function logDisplay()
	{
		// Check for Web
		if(!_OS::isCLI()) {
			echo '<pre>';
		}

		// Print the log
		var_dump(self::$aLog);

		// Check for Web
		if(!_OS::isCLI()) {
			echo '</pre>';
		}
	}

	/**
	 * Log On
	 *
	 * Turns on logging of all MySQL calls
	 *
	 * @name logOn
	 * @access public
	 * @static
	 * @return void
	 */
	public static function logOn()
	{
		// Set the static variable to an array
		self::$aLog	= array();
	}

	/**
	 * Select
	 *
	 * Select rows, columns, maps and single cells
	 *
	 * @name select
	 * @access public
	 * @static
	 * @throws _MySQL_Exception
	 * @param string $sql				SQL statement to run
	 * @param uint $return				Return type
	 * @return mixed
	 */
	public static function select(/*string*/ $sql, /*uint*/ $return = self::SELECT_ALL)
	{
		// Get the read server connection
		$oCon	= self::fetchConnection('read');

		// If logging is on
		if(false !== self::$aLog) {
			self::$aLog[]	= array(
				'timestamp'	=> date('Y-m-d H:i:s'),
				'type'		=> 'EXEC',
				'sql'		=> $sql
			);
		}

		// If the query fails, return false
		if(!$oCon->real_query($sql)) {
			// If we lost the MySQL connection
			if($oCon->errno == 2006) {
				// Clear it and rerun the query after pausing for a second
				sleep(1);
				self::clearConnection('read');
				return self::select($sql, $return);
			} else {
				throw new _MySQL_Exception(__METHOD__ . ' (' . $oCon->errno . '): ' . $oCon->error . "\n{$sql}", $oCon->errno);
			}
		}

		// Check if there are any rows
		$oResult	= $oCon->use_result();
		if(!$oResult)	return null;

		// Figure out what output to return
		switch($return)
		{
			case self::SELECT_ALL:		// Get all the data in all the rows
				$aData	= array();
				while($aRow = $oResult->fetch_assoc())	$aData[]	= $aRow;
				break;

			case self::SELECT_ROW:		// Only get the first row
				$aData	= null;
				if($aRow = $oResult->fetch_assoc())		$aData		= $aRow;
				break;

			case self::SELECT_COLUMN:	// Only get the first column
				$aData	= array();
				while($aRow = $oResult->fetch_row())	$aData[]	= $aRow[0];
				break;

			case self::SELECT_CELL:		// Only get the first field in the first row
				$aData	= null;
				if($aRow = $oResult->fetch_row())		$aData		= $aRow[0];
				break;

			case self::SELECT_HASH:		// Make the first column the key, the second the value
				$aData	= array();
				while($aRow = $oResult->fetch_row())	$aData[$aRow[0]]	= $aRow[1];
				break;

			case self::SELECT_HASHROWS: // Make the first column the key, the second the row
				$aData	= array();
				// Fetch the first field
				$oField = $oResult->fetch_field();
				while($aRow = $oResult->fetch_assoc())	  $aData[$aRow[$oField->name]]	= $aRow;
				break;
		}

		// Free the memory
		$oResult->free();

		// Return the result
		return $aData;
	}
}

/**
 * MySQL Exception class
 *
 * Is used for any exceptions thrown from inside _MySQL
 *
 * @name _MySQL_Exception
 * @extends Exception
 */
class _MySQL_Exception extends Exception {}
