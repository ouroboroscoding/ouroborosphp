<?php
/**
 * OS
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2014-12-02
 */

/**
 * OS Class
 *
 * Methods used for finding out info about the OS or dictating it's behaviour
 *
 * @name _OS
 */
class _OS
{
	/**
	 * Is CLI
	 *
	 * Holds whether we are in CLI mode or not
	 *
	 * @var bool
	 * @access private
	 * @static
	 */
	private static $bIsCLI = null;

	/**
	 * Is Developer
	 *
	 * Holds whether the current client is designated as a developer
	 *
	 * @var bool
	 * @access private
	 * @static
	 */
	private static $bIsDeveloper = null;

	/**
	 * Is Windows
	 *
	 * Holds whether the app is being run on Windows or not
	 *
	 * @var bool
	 * @access private
	 * @static
	 */
	private static $bIsWindows = null;

	/**
	 * Bytes to Human
	 *
	 * Returns a value in bytes as a human readable string
	 *
	 * @name bytesToHuman
	 * @access public
	 * @static
	 * @param uint $size				The number of bytes
	 * @param string $unit				If you want to force the unit type
	 * @return string
	 */
	public static function bytesToHuman($size, $unit = '')
	{
		if( (!$unit && $size >= 1<<30) || $unit == "GB")
			return number_format($size/(1<<30),2)."GB";

		if( (!$unit && $size >= 1<<20) || $unit == "MB")
			return number_format($size/(1<<20),2)."MB";

		if( (!$unit && $size >= 1<<10) || $unit == "KB")
			return number_format($size/(1<<10),2)."KB";

		return number_format($size)." bytes";
	}

	/**
	 * Convert $argv to $_REQUEST
	 *
	 * Turns each '--arg=val' into $_REQUEST[arg] = val
	 *
	 * @name convertArgvToRequest
	 * @access public
	 * @static
	 * @return void
	 */
	public static function convertArgvToRequest()
	{
		// Pull in global argument variables
		global $argc, $argv;

		// Go through each argument
		for($i = 1; $i < $argc; ++$i)
		{
			// If the value is in the right format
			if(preg_match('/--([^=]+)(?:=(.+))?/', $argv[$i], $aM))
			{
				// Store it by name and value
				$_REQUEST[$aM[1]]	= (empty($aM[2])) ? true : $aM[2];
			}
			// Else add it to the unknowns
			else
			{
				$_REQUEST['?'][]	= $argv[$i];
			}
		}
	}

	/**
	 * Delete Folder
	 *
	 * Recursively deletes all files in the folder and then deletes the folder
	 * itself
	 *
	 * @name deleteFolder
	 * @access public
	 * @static
	 * @param string $path				The path to completely delete
	 * @return bool
	 */
	public static function deleteFolder(/*string*/ $path)
	{
		// If the path is a valid folder
		if(is_dir($path))
		{
			// Create an instance of DirectoryIterator with the given path
			$oDI	= new DirectoryIterator($path);

			// Go through each file
			foreach($oDI as $oFile)
			{
				// If the file is a dot file, skip it
				if($oFile->isDot()) continue;

				// If the file is a directory, recurse
				if($oFile->isDir()) {
					if(!self::deleteFolder($oFile->getPathname())) {
						return false;
					}
				}
				// Else try to unlink the file, if we failed, return false
				else if(!unlink($oFile->getPathname())) {
					return false;
				}
			}

			// Free memory
			unset($oDI);

			// Delete the folder and return the result
			return rmdir($path);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Get Client IP
	 *
	 * Returns the IP of the client when connecting via webserver
	 *
	 * @name getClientIP
	 * @access public
	 * @static
	 * @return string
	 */
	public static function getClientIP()
	{
		if(isset($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP) !== false) {
			$sIP	= $_SERVER['HTTP_CLIENT_IP'];
		} else if(isset($_SERVER['HTTP_X_CLIENTIP']) && filter_var($_SERVER['HTTP_X_CLIENTIP'], FILTER_VALIDATE_IP) !== false) {
			$sIP	= $_SERVER['HTTP_X_CLIENTIP'];
		} else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP) !== false) {
			$sIP	= $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else if(isset($_SERVER['HTTP_X_RN_XFF']) && filter_var($_SERVER['HTTP_X_RN_XFF'], FILTER_VALIDATE_IP) !== false) {
			$sIP	= $_SERVER['HTTP_X_RN_XFF'];
		} else if(isset($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP) !== false) {
			$sIP	= $_SERVER['REMOTE_ADDR'];
		} else {
			trigger_error('No client IP exists.', E_USER_WARNING);
			$sIP	= '0.0.0.0';
		}

		// If there's multiple IPs
		if(strpos($sIP, ','))
		{
			$aIPs	= explode(',', $sIP);
			$sIP	= trim(end($aIPs));
		}

		// Return the IP
		return $sIP;
	}

	/**
	 * Is CLI
	 *
	 * Returns true if we are in the command line interface and not being called
	 * via a webserver
	 *
	 * @name isCLI
	 * @access public
	 * @static
	 * @return bool
	 */
	public static function isCLI()
	{
		if(!is_null(self::$bIsCLI)) {
			return self::$bIsCLI;
		}

		return self::$bIsCLI = (php_sapi_name() == 'cli' || (isset($argc) && isset($argv)));
	}

	/**
	 * Is Developer
	 *
	 * Returns true if the current client is a developer
	 *
	 * @name isDeveloper
	 * @access public
	 * @static
	 * @return bool
	 */
	public static function isDeveloper()
	{
		if(!is_null(self::$bIsDeveloper)) {
			return self::$bIsDeveloper;
		}

		return self::$bIsDeveloper = in_array(self::getClientIP(), _Config::get(array('developer', 'ips')));
	}

	/**
	 * Is Windows
	 *
	 * Return whether the current OS is a version of Windows or not
	 *
	 * @name isWindows
	 * @access public
	 * @static
	 * @return bool
	 */
	public static function isWindows()
	{
		// Check if we've looked this up already
		if(is_null(self::$bIsWindows))
		{
			// We haven't so check if php_uname has windows in it
			if(stripos(php_uname('s'), 'windows') !== false)
			{
				// It does, save the result
				self::$bIsWindows = true;
			}
			else
			{
				// It doesn't, save the result
				self::$bIsWindows = false;
			}
		}

		// Return the result
		return self::$bIsWindows;
	}

	/**
	 * Lock
	 *
	 * Tries to create a lock file with the current processes PID. It will fail
	 * if the file exists as well as the process in it, or if the file can not
	 * be written to
	 *
	 * @name lock
	 * @access public
	 * @static
	 * @param string $name				The name of the process
	 * @param string $opts				Options array('retry' => int, 'interval' => int, 'path' => string)
	 * @return bool
	 */
	public static function lock(/*string*/ $name, array $opts = array())
	{
		// Set number of retries
		$iRetry = isset($opts['retry']) ? $opts['retry'] : 0;

		// Set interval of retry
		$iInterval	= isset($opts['interval']) ? $opts['interval'] : 10;

		// If the path wasn't passed as an option, then use the current users
		//	home folder.
		if(!isset($opts['path']))
		{
			// Windows
			if(isset($_SERVER['HOMEDRIVE']))
			{
				$opts['path']	= $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
			}
			// Linux/UNIX
			else
			{
				$opts['path']	= $_SERVER['HOME'];
			}
		}

		// Generate the full path to the file
		$sFile	= $opts['path'] . DIRECTORY_SEPARATOR . $name . '.pid';

		// We assume failure unless we can break out of the loop
		$bSuccess	= false;

		// And we loop for the sake of retries, but we start -1 so the value of
		//	'retry' is not simply 'try'
		for($i = -1; $i < $iRetry; ++$i)
		{
			// Sleep only if this is a retry
			if($i != -1)
			{
				sleep($iInterval);
			}

			// If the file already exists
			if(file_exists($sFile))
			{
				// Pull the PID out of it
				$iPID	= trim(file_get_contents($sFile));

				// If it's running
				if(self::processExists($iPID))
					continue;
			}

			// Either the file didn't exist or the process didn't, so store the
			//	current PID into the file
			if(file_put_contents($sFile, getmypid() . "\n") === false)
			{
				// If it failed, trigger an error, we can't allow the script to run
				trigger_error("Can't store PID file: {$sFile}", E_USER_ERROR);
				continue;
			}

			// If we got this far we must have gotten a lock, so we can set our
			//	success flag to true and break out of the loop
			$bSuccess	= true;
			break;
		}

		return $bSuccess;
	}

	/**
	 * Notify
	 *
	 * E-mails developer
	 *
	 * @name notify
	 * @access public
	 * @return void
	 */
	public static function notify(/*string*/ $subject, /*string*/ $message)
	{
		mail(implode(',', _Config::get('developers:emails')), php_uname('n') . ' ' . $subject, $message);
	}

	/**
	 * Process Exists
	 *
	 * Returns whether a process is running (true) or not (false)
	 *
	 * @name processExists
	 * @access public
	 * @static
	 * @param uint $pid					Process ID to look up
	 * @return bool
	 */
	public static function processExists(/*uint*/ $pid)
	{
		// Windows has no ps command, so we do something special
		if(self::isWindows())
		{
			$sProcs = shell_exec('tasklist.exe');

			if(stripos($sProcs, "{$pid} Console") !== false)
			{
				return true;
			}
		}
		// Linux (hopefully)
		else
		{
			// Init
			$iReturn = $NOTUSED = null;

			// And run ps to see if it's running
			exec('ps ' . $pid, $NOTUSED, $iReturn);

			// If the return value is 0 then ps found the process(es)
			if($iReturn == 0)
			{
				return true;
			}
		}

		// Return false since we couldn't find it
		return false;
	}

	/**
	 * Process Kill
	 *
	 * Attempts to kill a process running on the system
	 *
	 * @name processKill
	 * @access public
	 * @static
	 * @param uint $pid					The ID of the process we want to terminate
	 * @return bool
	 */
	public static function processKill(/*uint*/ $pid)
	{
		// If it's windows
		if(self::isWindows())
		{
			// Fail
			trigger_error('Can not call ' . __METHOD__ . ' in Windows.', E_USER_ERROR);
		}

		// Terminate
		$sSE	= shell_exec('kill -9 ' . $pid);

		// Return based on the result from shell_exec
		return !is_null($sSE);
	}
}
