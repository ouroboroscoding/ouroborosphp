<?php
/**
 * MySQL
 * For connecting to, writing to, and querying from a MySQL server/database.
 * Child of SQL class.
 *
 * @author chris nasr
 * @copyright fuel for the fire
 * @package sql
 * @version 0.1
 * @created 2012-06-06
 */

/**
 * Include required classes
 */
$gsPath	= dirname(__FILE__);
require_once $gsPath . '/Arrays.php';
require_once $gsPath . '/SQL.php';

/**
 * MySQL class
 * @name MySQL
 * @package core
 * @subpackage sql
 */
class MySQL extends SQL
{
	/**
	 * Connection variables
	 * @var array
	 * @access private
	 */
	private $aConVars;

	/**
	 * Conenction to MySQL
	 * @var resource
	 * @access private
	 */
	private $rCon;

	/**
	 * The current charset of the connection
	 * @var string
	 * @access private
	 */
	private $sCurrCharset;

	/**
	 * Constructor
	 * Initializes the instance of the object
	 * @name MySQL
	 * @access public
	 * @return MySQL
	 */
	public function __construct()
	{
		$this->aConVars		= array();
		$this->rCon			= null;
		$this->sCurrCharset	= '';
		// @todo update to mysqli
	}

	/**
	 * Connect
	 * Attempts to connect to the DB
	 * @name connect
	 * @access private
	 * @throws SQLConnectionException
	 * @return bool
	 */
	private function connect(/*uint*/ $in_tries = 0)
	{
		// If we're already connected
		if(is_resource($this->rCon))
		{
			// Attempt to disconnect first
			mysql_close($this->rCon);
		}

		// Connect to the server and store the connection
		$this->rCon	= mysql_connect(
			$this->aConVars['host'],
			$this->aConVars['user'],
			$this->aConVars['password']
		);

		// Check if the connection is valid or else throw an exception
		if($this->rCon === false)
		{
			// Clear the connection resource
			$this->rCon	= null;

			// If we haven't run out of attempts
			if($in_tries < $this->aConVars['retries'])
			{
				// Sleep for 100 millisecond (100,000 microsecond)
				usleep(100000);

				// Try again
				return $this->connect(++$in_tries);
			}
			// Else, give up, throw exception
			else
			{
				throw new SQLConnectionException("Can not establish connection to {$this->aConVars['user']}@{$this->aConVars['host']}.\n");
			}
		}

		// Connect to the DB
		$rRes	= mysql_select_db($this->aConVars['db'], $this->rCon);

		// Check selecting the DB didn't fail
		if($rRes === false)
		{
			// If we haven't run out of attempts
			if($in_tries < $this->aConVars['retries'])
			{
				// Clear the connection resource
				$this->rCon	= null;

				// Sleep for 100 millisecond (100,000 microsecond)
				usleep(100000);

				// Try again
				return $this->connect(++$in_tries);
			}
			// Else, give up, throw exception
			else
			{
				$iCode		= mysql_errno($this->rCon);
				$sMsg		= mysql_error($this->rCon);
				$this->rCon	= null;
				throw new SQLConnectionException(self::formatError($iCode, $sMsg, ''), $iCode);
			}
		}

		// If a charset is set, change it now
		if(isset($this->aConVars['charset']))
		{
			mysql_set_charset($this->aConVars['charset'], $this->rCon);
			$this->sCurrCharset	= $this->aConVars['charset'];
		}
		else
		{
			$this->sCurrCharset	= '';
		}
	}

	/**
	 * Exec
	 * Execute one or multiple SQL statements on the server and return the affected rows
	 * <pre>Optional arguments:
	 * charset 'string'        => The charset to use when transfering and retrieving data
	 * select 'string'         => A SELECT statement to run and return the results of instead of affected rows
	 * transaction 'boolean'   => Set to false to stop automatically opening transactions if multiple statements are sent
	 * </pre>
	 * @name exec
	 * @access public
	 * @throws SQLException
	 * @throws SQLConnectionException
	 * @throws SQLDuplicateKeyException
	 * @param array|string $in_sql		Single or multiple SQL statements to run
	 * @param array $in_opts			Optional arguments
	 * @return mixed					Affected rows or an array if 'select' optional argument is set. If select is set, you can still get the affected rows via ['affected_rows']
	 */
	public function exec(/*array|string*/ $in_sql, array $in_opts = array())
	{
		// Check optional arguments
		Arrays::checkOptions($in_opts, array(
			'charset'		=> false,
			'retry'			=> 0,
			'select'		=> false,
			'transaction'	=> true
		));

		// If we aren't connected
		if(is_null($this->rCon))
		{
			// Connect
			$this->connect();
		}

		// Init variables
		$aAffectedRows	= array();

		// If we haven't already done the setup
		if($in_opts['retry'] == 0)
		{
			// Check if the SQL sent was an array of statements
			if(is_array($in_sql))
			{
				$iCount	= count($in_sql);

				// If the count is more than 1
				if($iCount > 1 && $in_opts['transaction'])
				{
					// Shift the array by two
					for($i = $iCount + 1; $i > 1; --$i) $in_sql[$i]	= $in_sql[$i - 2];

					// Start transaction statements
					$in_sql[0]	= 'SET autocommit=0';
					$in_sql[1]	= 'START TRANSACTION';

					// End transaction statements
					$in_sql[]	= 'COMMIT';
					$in_sql[]	= 'SET autocommit=1';

					// Increase count
					$iCount		+= 4;
				}
				else if($iCount == 0)
				{
					trigger_error("Empty SQL array passed to " . __METHOD__, E_USER_NOTICE);
					return null;
				}
			}
			// If not, make it an array of one to simplify the code
			else
			{
				$in_sql	= array($in_sql);
				$iCount	= 1;
			}
		}

		// Set charset if sent
		if($in_opts['charset'])
			$this->setCharset($in_opts['charset']);

		// Go through each statement
		for($i = 0; $i < $iCount; ++$i)
		{
			// Debug info
			Debug::add('sql', $in_sql[$i]);

			// Excute the statement
			$bQuery	= mysql_query($in_sql[$i], $this->rCon);

			// If the query failed
			if($bQuery === false)
			{
				$iErrno	= mysql_errno($this->rCon);	// MySQL error number
				$sError	= mysql_error($this->rCon);	// MySQL error message

				// Check for specific error codes and that we haven't reached the max tries
				if(($iErrno == 2006 || $iErrno == 2013) && // MySQL server has gone away || Lost connection to MySQL server during query
					$in_opts['retry'] < $this->aConVars['retries'])
				{
					++$in_opts['retry'];					// Increment retry count
					usleep(100000);							// Sleep for 100 milliseconds (100,000 microseconds)
					$this->rCon	= null;						// Reset the resource so we connect again
					return $this->exec($in_sql, $in_opts);	// Start the whole process over
				}
				else
				{
					// If we've tried multiple times, add the info to the error string
					if($in_opts['retry'])	$sError	= "!!! Failed query after {$in_opts['retry']} tries. !!!  {$sError}";

					// Throw appropriate Exception
					throw self::prepareException($iErrno, $sError, $in_sql[$i]);
				}
			}

			// Only get affected rows for passed queries, ignore transaction calls
			if($iCount == 1					||	// If there's only one statement
				!$in_opts['transaction']	||	// Or we didn't add transaction statements
				($i > 1 && $i < $iCount - 2))	// Or we're on a valid statement
			{
				$aAffectedRows[]	= mysql_affected_rows($rCon);
			}
		}

		// If we need a final select statement to get MySQL variables and such,
		//	run it now and retrieve the values
		if($in_opts['select'])
		{
			$mRows	= $this->select($in_opts['select']);

			// Add the affected rows if the value is an array
			if(is_array($mRows))
			{
				$mRows['affected_rows']	= (count($aAffectedRows) == 1) ?
											$aAffectedRows[0] :
											$aAffectedRows;
			}

			return $mRows;
		}

		// Return the number of affected rows
		return	(count($aAffectedRows) == 1) ?
					$aAffectedRows[0] :
					$aAffectedRows;
	}

	/**
	 * Format an error string
	 * @name formatError
	 * @access private
	 * @static
	 * @param int $in_code				Error code
	 * @param string $in_message		Error message
	 * @param string $in_sql			The SQL that caused the error
	 * @return string
	 */
	public static function formatError($in_code, $in_message, $in_sql = '')
	{
		// If there are multiple statements, turn them into one long string
		if(is_array($in_sql))	$in_sql	= implode("\n\n", $in_sql);

		// If we're in CLI mode, send in plain text
		if(Framework::isCLI())
		{
			if(!empty($in_sql))
			{
				$sTitle	= 'MySQL Query Error';
				$sSQL	= "SQL:     {$in_sql}\n";
			}
			else
			{
				$sTitle	= 'MySQL Error';
				$sSQL	= '';
			}

			$sRet	= 	"{$sTitle}\n" .
						"Code:    {$in_code}\n" .
						"Message: {$in_message}\n" .
						"{$sSQL}";
		}
		// Else format using HTML
		else
		{
			if(!empty($in_sql))
			{
				$sSQL	= preg_replace('/\b(' . self::RESERVED_WORDS . ')\b/i', '<b>\1</b>', $in_sql);
				$sTitle	= 'MySQL Query Error';
				$sSQL	= "  <dt>sql:</dt>\n" .
							"  <dd><pre>{$sSQL}</pre></dd>\n";
			}
			else
			{
				$sTitle	= 'MySQL Error';
				$sSQL	= '';
			}

			$sRet	= "{$sTitle}:<br />\n"	.
						"<dl class=\"MySQL_Error\">\n" .
						"  <dt>errno:</dt>\n" .
						"  <dd>{$in_code}</dd>\n" .
						"  <dt>error:</dt>\n" .
						"  <dd>{$in_message}</dd>\n" .
						"{$sSQL}" .
						"</dl>";

		}

		return $sRet;
	}

	/**
	 * Initialize
	 * Sets up the MySQL class, should be called when the application starts
	 * @name initialize
	 * @access public
	 * @static
	 * @param array $in_con_vars		The connection variables
	 * @return void
	 */
	public static function initialize(array $in_con_vars)
	{
		// Check config options
		Arrays::checkOptions($in_con_vars, array(
			'host'		=> 'localhost',
			'user'		=> '',
			'password'	=> '',
			'db'		=> 'mysql',
			'retries'	=> 5,
			'charset'	=> 'utf8'
		));

		// Store the config
		$this->aConVars	= $in_con_vars;
	}

	/**
	 * Insert
	 * Execute an INSERT statement and return the new ID if available
	 * <pre>Optional arguments:
	 * charset 'string'        => The charset to use when transfering and retrieving data
	 * </pre>
	 * @name insert
	 * @access public
	 * @throws SQLException
	 * @throws SQLConnectionException
	 * @throws SQLDuplicateKeyException
	 * @param string $in_sql			The INSERT statement
	 * @param array $in_opts			Options arguments
	 * @return uint						Last INSERT ID
	 */
	public function insert(/*string*/ $in_sql, array $in_opts = array())
	{
		// Make sure no one tries to pass an array for sql
		if(!is_string($in_sql))	throw new FWInvalidType(__METHOD__, 'string', $in_sql);

		// Check optional arguments
		Arrays::checkOptions($in_opts, array(
			'charset'	=> false,
			'retry'		=> 0
		));

		// If we aren't connected
		if(is_null($this->rCon))
		{
			// Connect
			$this->connect();
		}

		// Set charset if sent
		if($in_opts['charset'])
			$this->setCharset($in_opts['charset']);

		// Debug info
		Debug::Add('sql', $in_sql);

		// Excute the statement
		$bQuery	= mysql_query($in_sql, $this->rCon);

		// If the query failed
		if($bQuery === false)
		{
			$iErrno	= mysql_errno($this->rCon);	// MySQL error number
			$sError	= mysql_error($this->rCon);	// MySQL error message

			// Check for specific error codes and that we haven't reached the max tries
			if(($iErrno == 2006 || $iErrno == 2013) && // MySQL server has gone away || Lost connection to MySQL server during query
				$in_opts['retry'] < $this->aConVars['retries'])
			{
				++$in_opts['retry'];						// Increment retry count
				usleep(100000);								// Sleep for 100 milliseconds (100,000 microseconds)
				$this->rCon	= null;							// Clear the connection resource
				return self::insert($in_sql, $in_opts);		// Start the whole process over
			}
			else
			{
				// If we've tried multiple times, add the info to the error string
				if($in_opts['retry'])	$sError	= "!!! Failed query after {$in_opts['retry']} tries. !!!  {$sError}";

				// Throw appropriate Exception
				throw self::prepareException($iErrno, $sError, $in_sql);
			}
		}

		// Return the last inserted ID
		return mysql_insert_id($this->rCon);
	}

	/**
	 * Prepare Exception
	 * Gets MySQL error info and returns the appropriate Exception
	 * @name prepareException
	 * @access private
	 * @static
	 * @param int $in_code				Error code (errno)
	 * @param string $in_msg			Error message (error)
	 * @param string $in_sql			The SQL that caused the error
	 * @return SQLException
	 */
	private static function prepareException(/*int*/ $in_code, /*string*/ $in_msg, /*string*/ $in_sql)
	{
		// If the error is a duplicate key violation
		if(1062 == $in_code)
		{
			// Pull out the name and value of the key violation
			if(preg_match('/Duplicate entry \'(.*)\' for key \'(.*)\'/', $in_msg, $aM))
			{
				$sKValue	= $aM[1];
				$sKName		= $aM[2];
			}
			else
			{
				$sKValue	= 'unknown';
				$sKName		= 'unknown';
			}

			// Create and return a duplicate key exception
			return new SQLDuplicateKeyException(
				self::formatError($in_code, $in_msg, $in_sql),
				$in_code,
				$sKValue,
				$sKName
			);
		}
		// Else create and return a regular SQL exception
		else
		{
			return new SQLException(self::formatError($in_code, $in_msg, $in_sql), $in_code);
		}
	}

	/**
	 * Select
	 * Executes a SELECT statement and returns the results based on the select type
	 * <pre>Optional arguments
	 * charset 'string'        => The charset to use when transfering and retrieving data
	 * type 'uint'             => The type of data to return, see SQL::SELECT_*
	 * </pre>
	 * @name select
	 * @access public
	 * @throws SQLException
	 * @throws SQLConnectionException
	 * @param string $in_sql			The SELECT statement
	 * @param uint $in_type				The type of data to return
	 * @return mixed
	 */
	public function select(/*string*/ $in_sql, array $in_opts = array())
	{
		// Make sure no one tries to pass an array for sql
		if(!is_string($in_sql))	throw new FWInvalidType(__METHOD__, 'string', $in_sql);

		// Check optional arguments
		Arrays::checkOptions($in_opts, array(
			'charset'	=> false,
			'retry'		=> 0,
			'type'		=> SQL::SELECT_ALL
		));

		// If we aren't connected
		if(is_null($this->rCon))
		{
			// Connect
			$this->connect();
		}

		// Set charset if sent
		if($in_opts['charset'])
			$this->setCharset($in_opts['charset']);

		// Debug info
		Debug::Add('sql', $in_sql);

		// Call query
		$rSet	= mysql_query($in_sql, $this->rCon);

		// If the query failed
		if($rSet === false)
		{
			$iErrno	= mysql_errno($this->rCon);	// MySQL error number
			$sError	= mysql_error($this->rCon);	// MySQL error message

			// Check for specific error codes and that we haven't reached the max tries
			if(($iErrno == 2006 || $iErrno == 2013) && // MySQL server has gone away || Lost connection to MySQL server during query
				$in_opts['retry'] < $this->aConVars['retries'])
			{
				++$in_opts['retry'];						// Increment retry count
				usleep(100000);								// Sleep for 100 milliseconds (100,000 microseconds)
				$this->rCon	= null;							// Clear the connection resource
				return self::select($in_sql, $in_opts);		// Start the whole process over
			}
			else
			{
				// Throw an exception
				if($in_opts['retry'])	$sError	= "!!! Failed query after {$in_opts['retry']} tries. !!!  {$sError}";
				throw new MySQLException(self::formatError($iErrno, $sError, $in_sql), $iErrno);
			}
		}

		// Figure out what output to return
		switch($in_opts['type'])
		{
			case SQL::SELECT_ALL:		// Get all the data in all the rows
				$aData	= array();
				while($row = mysql_fetch_assoc($rSet))	$aData[]	= $row;
				break;

			case SQL::SELECT_ROW:		// Only get the first row
				if(mysql_num_rows($rSet))	$aData	= mysql_fetch_assoc($rSet);
				else						$aData	= array();
				break;

			case SQL::SELECT_COLUMN:	// Only get the first column
				$aData	= array();
				while($row = mysql_fetch_row($rSet))	$aData[]	= $row[0];
				break;

			case SQL::SELECT_CELL:		// Only get the first field in the first row
				if(mysql_num_rows($rSet))	$aData	= mysql_result($rSet, 0, 0);
				else						$aData 	= null;
				break;

			case SQL::SELECT_MAP:		// Make the first column the key, the second the value
				$aData	= array();
				while($row = mysql_fetch_row($rSet))	$aData[$row[0]]	= $row[1];
				break;
		}

		// Free result
		mysql_free_result($rSet);

		// Return the data
		return $aData;
	}

	/**
	 * Set Charset
	 * Stores the charset to use for the instance
	 * @name setCharset
	 * @access public
	 * @param string $in_charset		The name of the charset
	 * @return void
	 */
	public function setCharset(/*string*/ $in_charset)
	{
		// If the charset is different than the current
		if($in_charset != $this->sCurrCharset)
		{
			// Try to change it
			if(!mysql_set_charset($in_opts['charset']))
			{
				// If it failed, warn the user
				trigger_error("Failed to change charset to {$in_charset}.", E_USER_WARNING);
			}
			else
			{
				// Save the new charset
				$this->sCurrCharset	= $in_charset;
			}
		}
	}

	/**
	 * Wait for Sync
	 * Returns only when this server is in sync (or close enough) to its master. Should never be used in HTTP instances as it can wait for long periods of time
	 * @name waitForSync
	 * @access public
	 * @throws SQLException
	 * @throws SQLConnectionException
	 * @param uint $in_minimun_sbm		The minimum time difference between this server and the master
	 * @return void
	 */
	public function waitForSync(/*uint*/ $in_minimun_sbm = 0)
	{
		for($i = 1; true; ++$i)
		{
			// Get the slave status
			$aRow	= $this->select('SHOW SLAVE STATUS', SQL::SELECT_ROW);

			// Check for a seconds_behind_master field
			if(is_null($aRow) ||   (!isset($aRow['seconds_behind_master']) &&
									!isset($aRow['Seconds_Behind_Master'])))
			{
				throw new SQLException("Failed to get seconds_behind_master.");
			}

			// Store seconds behind master
			$iSBM	= intval(isset($aRow['seconds_behind_master']) ?
								$aRow['seconds_behind_master'] :
								$aRow['Seconds_Behind_Master']);

			// If the slave is synced, exit the loop
			if($iSBM <= $in_minimun_sbm)
			{
				break;
			}
			// Otherwise sleep for a time determined
			//	by the number of iterations
			else
			{
				sleep($i);
			}

			if($i >= 20)
			{
				throw new SQLException("Slave won't sync. {$iSBM} Seconds Behind Master.");
			}
		}
	}
}

?>