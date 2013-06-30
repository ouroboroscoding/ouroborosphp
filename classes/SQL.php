<?php
/**
 * SQL
 * The abstract base class for all SQL Database types
 *
 * @author chris nasr
 * @copyright fuel for the fire
 * @package core
 * @version 0.1
 * @created 2012-06-06
 */

/**
 * SQL class
 * @name SQL
 * @package core
 */
abstract class SQL
{
	/**
	 * SQL Reserved words
	 */
	const RESERVED_WORDS	= 'ADD|ALL|ALTER|ANALYZE|AND|AS|ASC|ASENSITIVE|AUTO_INCREMENT|BDB|BEFORE|BERKELEYDB|BETWEEN|BIGINT|BINARY|BLOB|BOTH|BY|CALL|CASCADE|CASE|CHANGE|CHAR|CHARACTER|CHECK|COLLATE|COLUMN|COLUMNS|CONDITION|CONNECTION|CONSTRAINT|CONTINUE|CREATE|CROSS|CURRENT_DATE|CURRENT_TIME|CURRENT_TIMESTAMP|CURSOR|DATABASE|DATABASES|DAY_HOUR|DAY_MICROSECOND|DAY_MINUTE|DAY_SECOND|DEC|DECIMAL|DECLARE|DEFAULT|DELAYED|DELETE|DESC|DESCRIBE|DETERMINISTIC|DISTINCT|DISTINCTROW|DIV|DOUBLE|DROP|ELSE|ELSEIF|ENCLOSED|ESCAPED|EXISTS|EXIT|EXPLAIN|FALSE|FETCH|FIELDS|FLOAT|FOR|FORCE|FOREIGN|FOUND|FRAC_SECOND|FROM|FULLTEXT|GRANT|GROUP|HAVING|HIGH_PRIORITY|HOUR_MICROSECOND|HOUR_MINUTE|HOUR_SECOND|IF|IGNORE|IN|INDEX|INFILE|INNER|INNODB|INOUT|INSENSITIVE|INSERT|INT|INTEGER|INTERVAL|INTO|IO_THREAD|IS|ITERATE|JOIN|KEY|KEYS|KILL|LEADING|LEAVE|LEFT|LIKE|LIMIT|LINES|LOAD|LOCALTIME|LOCALTIMESTAMP|LOCK|LONG|LONGBLOB|LONGTEXT|LOOP|LOW_PRIORITY|MASTER_SERVER_ID|MATCH|MEDIUMBLOB|MEDIUMINT|MEDIUMTEXT|MIDDLEINT|MINUTE_MICROSECOND|MINUTE_SECOND|MOD|NATURAL|NOT|NO_WRITE_TO_BINLOG|NULL|NUMERIC|ON|OPTIMIZE|OPTION|OPTIONALLY|OR|ORDER|OUT|OUTER|OUTFILE|PRECISION|PRIMARY|PRIVILEGES|PROCEDURE|PURGE|READ|REAL|REFERENCES|REGEXP|RENAME|REPEAT|REPLACE|REQUIRE|RESTRICT|RETURN|REVOKE|RIGHT|RLIKE|SECOND_MICROSECOND|SELECT|SENSITIVE|SEPARATOR|SET|SHOW|SMALLINT|SOME|SONAME|SPATIAL|SPECIFIC|SQL|SQLEXCEPTION|SQLSTATE|SQLWARNING|SQL_BIG_RESULT|SQL_CALC_FOUND_ROWS|SQL_SMALL_RESULT|SQL_TSI_DAY|SQL_TSI_FRAC_SECOND|SQL_TSI_HOUR|SQL_TSI_MINUTE|SQL_TSI_MONTH|SQL_TSI_QUARTER|SQL_TSI_SECOND|SQL_TSI_WEEK|SQL_TSI_YEAR|SSL|STARTING|STRAIGHT_JOIN|STRIPED|TABLE|TABLES|TERMINATED|THEN|TIMESTAMPADD|TIMESTAMPDIFF|TINYBLOB|TINYINT|TINYTEXT|TO|TRAILING|TRUE|TYPE|UNDO|UNION|UNIQUE|UNLOCK|UNSIGNED|UPDATE|USAGE|USE|USER_RESOURCES|USING|UTC_DATE|UTC_TIME|UTC_TIMESTAMP|VALUES|VARBINARY|VARCHAR|VARCHARACTER|VARYING|WHEN|WHERE|WHILE|WITH|WRITE|XOR|YEAR_MONTH|ZEROFILL';

	/**#@+
	 * SQL Select type constants
	 */
	const SELECT_ALL		= 0;
	const SELECT_ROW		= 1;
	const SELECT_COLUMN		= 2;
	const SELECT_CELL		= 3;
	const SELECT_MAP		= 4;
	/**#@-*/

	/**
	 * Instance of SQL children
	 * @var array
	 * @access private
	 * @static
	 */
	private static $saChild	= array();

	/**
	 * Connect
	 * Attempts to connect to the DB
	 * @name connect
	 * @access private
	 * @abstract
	 * @throws SQLConnectionException
	 * @return bool
	 */
	abstract private function connect();

	/**
	 * Escape
	 * Escapes a string for SQL
	 * @name escape
	 * @access public
	 * @static
	 * @param string $in_str			String to escape
	 * @return string
	 */
	public static function escape($in_str)
	{
		return str_replace(
			array("\x00", "\\",   "'",   "\"",   "\x1a"),
			array('\x00', "\\\\", "\\'", "\\\"", '\x1a'),
			$in_str
		);
	}

	/**
	 * Exec
	 * Execute one or multiple SQL statements on the server and return the affected rows
	 * <pre>Optional arguments:
	 * charset 'string'        => The charset to use when transfering and retrieving data
	 * select 'string'         => A SELECT statement to run and return the results of instead of affected rows
	 * </pre>
	 * @name exec
	 * @access public
	 * @abstract
	 * @throws SQLException
	 * @throws SQLConnectionException
	 * @throws SQLDuplicateKeyException
	 * @param array|string $in_sql		Single or multiple SQL statements to run
	 * @param array $in_opts			Optional arguments
	 * @return mixed					Affected rows or an array if 'select' optional argument is set
	 */
	abstract public function exec(/*array|string*/ $in_sql, array $in_opts = array());

	/**
	 * Get
	 * Returns an instance of the specified SQL type
	 * @name get
	 * @access public
	 * @static
	 * @param string $in_type			Type of SQL database connection to create
	 * @param string $in_name			Name of the section in the config
	 * @return SQL
	 */
	public static function get(/*string*/ $in_type, /*string*/ $in_name)
	{
		// Normalize both values
		$in_type	= mb_strtolower($in_type);
		$in_name	= mb_strtolower($in_name);

		// If the instance already exists
		if(isset(self::$saChild[$in_type]) &&
			isset(self::$saChild[$in_type][$in_name]))
		{
			return self::$saChild[$in_type][$in_name];
		}

		$oConfig	= Framework::loadConfig();

		// Figure out which class to load
		switch($in_type)
		{
			case 'mysql':
				// Make sure the section exists
				$aConVars	= $oConfig->get('mysql:'.$in_name);
				if(is_null($aConVars))
				{
					trigger_error(__METHOD__ . ': can not find mysql:' . $in_name . ' section in config.', E_USER_ERROR);
				}

				// If the type hasn't been requested yet
				if(!isset(self::$saChild[$in_type]))
				{
					// Load the class
					Framework::includeClass('MySQL');

					// Create the array
					self::$saChild['mysql']	= array();
				}

				// Create a new instance and init it
				$oMySQL	= new MySQL();
				$oMySQL->initialize($aConVars);

				// Store it and return it
				return self::$saChild['mysql'][$in_name] = $oMySQL;

			default:
				trigger_error(__METHOD__ . ": invalid type ({$in_type}) passed", E_USER_ERROR);
				return null;
		}
	}

	/**
	 * Initialize
	 * Sets up the SQL server instance
	 * @name initialize
	 * @access public
	 * @abstract
	 * @param array $in_config			Configuration array for the SQL server type
	 * @param string $in_name			The name of the instance in the config
	 * @return bool
	 */
	abstract public function initialize(array $in_config, /*string*/ $in_name);

	/**
	 * Insert
	 * Execute an INSERT statement and return the new ID if available
	 * <pre>Optional arguments:
	 * charset 'string'        => The charset to use when transfering and retrieving data
	 * </pre>
	 * @name insert
	 * @access public
	 * @abstract
	 * @throws SQLException
	 * @throws SQLConnectionException
	 * @throws SQLDuplicateKeyException
	 * @param string $in_sql			The INSERT statement
	 * @param array $in_opts			Options arguments
	 * @return uint						Last INSERT ID
	 */
	abstract public function insert(/*string*/ $in_sql, array $in_opts = array());

	/**
	 * Select
	 * Executes a SELECT statement and returns the results based on the select type
	 * <pre>Optional arguments
	 * charset 'string'        => The charset to use when transfering and retrieving data
	 * type 'uint'             => The type of data to return, see SQL::SELECT_*
	 * </pre>
	 * @name select
	 * @access public
	 * @abstract
	 * @throws SQLException
	 * @throws SQLConnectionException
	 * @param string $in_sql			The SELECT statement
	 * @param uint $in_type				The type of data to return
	 * @return mixed
	 */
	abstract public function select(/*string*/ $in_sql, array $in_opts = array());

	/**
	 * Set Charset
	 * Stores the charset to use for the instance
	 * @name setCharset
	 * @access public
	 * @abstract
	 * @param string $in_charset		The name of the charset
	 * @return void
	 */
	abstract public function setCharset(/*string*/ $in_charset);

	/**
	 * Wait for Sync
	 * Returns only when this server is in sync (or close enough) to its master
	 * @name waitForSync
	 * @access public
	 * @abstract
	 * @throws SQLException
	 * @throws SQLConnectionException
	 * @param uint $in_minimun_sbm		The minimum time difference between this server and the master
	 * @return void
	 */
	abstract public function waitForSync(/*uint*/ $in_minimun_sbm = 0);
}

/**
 * SQL exception class
 * @name SQLException
 * @package core
 * @subpackage sql
 */
class SQLException extends Exception
{
	/**
	 * Constructor
	 * Initializes the instance
	 * @name SQLException
	 * @access public
	 * @param string $in_msg
	 * @param int $in_code
	 * @return SQLException
	 */
	public function __construct($in_msg, $in_code = 0)
	{
		// Call the Exception constructor
		parent::__construct($in_msg, $in_code);
	}

	/**
	 * Set Message
	 * Change the message
	 * @name setMessage
	 * @access public
	 * @param string $in_msg			The new message
	 * @return void
	 */
	public function setMessage($in_msg)
	{
		$this->message	= $in_msg;
	}
}

/**
 * SQL connection exception class
 * @name SQLConnectionException
 * @package core
 * @subpackage sql
 */
class SQLConnectionException extends SQLException {}

/**
 * SQL Duplicate Key exception class
 * @name SQLDuplicateKeyException
 * @package core
 * @subpackage MySQL
 */
class SQLDuplicateKeyException extends SQLException
{
	/**
	 * The key's name
	 * @var string
	 * @access private
	 */
	private /*string*/ $key_name;

	/**
	 * The key's value
	 * @var string
	 * @access private
	 */
	private /*string*/ $key_value;

	/**
	 * Constructor
	 * Initializes the instance
	 * @name SQLDuplicateKeyException
	 * @access public
	 * @param string $in_msg			The message
	 * @param int $in_code				The error code
	 * @param string $in_kvalue			The value of the key that already exists
	 * @param string $in_kname			The name of the key being violated
	 * @return SQLDuplicateKeyException
	 */
	public function __construct(/*string*/ $in_msg, /*int*/ $in_code, /*string*/ $in_kvalue, /*string*/ $in_kname)
	{
		// Call the Exception constructor
		parent::__construct($in_msg, $in_code);

		// Store the key value and name
		$this->key_value	= $in_kvalue;
		$this->key_name		= $in_kname;
	}

	/**
	 * Get Key Name
	 * Return the name of the key being violated
	 * @name getKeyName
	 * @access public
	 * @return string
	 */
	public function getKeyName()
	{
		return $this->key_name;
	}

	/**
	 * Get Key Value
	 * Return the value of the key that was duplicated
	 * @name getKeyValue
	 * @access public
	 * @return string
	 */
	public function getKeyValue()
	{
		return $this->key_value;
	}
}