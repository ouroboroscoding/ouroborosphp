<?php
/**
 * For connecting to, uploading, downloading, etc to/from an FTP server
 *
 * @author chris nasr
 * @copyright fuel for the fire
 * @package core
 * @version 0.2
 * @created 2012-02-20
 */

/**
 * Include required classes
 */
require_once 'classes/Framework.php';

/**
 * FTP class
 * @name FTP
 * @package core
 */
class FTP
{
	/**#@+
	 * Error constants
	 */
	const OK				= 0;
	const ERR_CONFIG		= -1;
	const ERR_CONNECTION	= -2;
	const ERR_LOGIN			= -3;
	const ERR_UPLOAD		= -4;
	const ERR_DOWNLOAD		= -5;
	const ERR_COPY			= -6;
	const ERR_MOVE			= -7;
	const ERR_VALFOLDER		= -8;
	/**#@-*/

	/**
	 * Last error code
	 * @var int
	 * @access private
	 * @static
	 */
	private static $iLastError	= 0;

	/**
	 * Last action run in case of failure
	 * @var int
	 * @access private
	 * @static
	 */
	private static $iLastAction	= 0;

	/**
	 * Actions
	 * Calls a series of FTP actions using one connection
	 * @name actions
	 * @access public
	 * @static
	 * @param string $in_module			The name of the module to connect to
	 * @param array $in_actions			The list of actions to perform
	 * @return bool
	 */
	public static function actions(/*string*/ $in_module, array $in_actions)
	{
		// Get the connection
		if(($rCon = self::connect($in_module)) === false)
		{
			return false;
		}

		// Go through each action
		foreach($in_actions as $i => $aAction)
		{
			$aArgs	= array_merge(array($rCon), array_slice($aAction, 1));
			if(!call_user_func_array(array('FTP', $aAction[0]), $aArgs))
			{
				self::$iLastAction	= $i;
				return false;
			}
		}

		// Close the connection
		ftp_close($rCon);

		// Return ok
		return true;
	}

	/**
	 * Change Directory
	 * Called to change directory, can only be used with actions
	 * @name chdir
	 * @access private
	 * @static
	 * @param resource $in_con			Current connection to FTP
	 * @param string $in_path			Path to change directories to
	 * @return bool
	 */
	private static function chdir(/*resource*/ $in_con, /*string*/ $in_path)
	{
		return ftp_chdir($in_con, $in_path);
	}

	/**
	 * Connect
	 * Connects to FTP and returns resource
	 * @name connect
	 * @access public
	 * @static
	 * @param string $in_module			The module to connect to
	 * @return resource|false
	 */
	public static function connect(/*string*/ $in_module)
	{
		// Get config info for the specified module
		$aConf	= Framework::getConfig('FTP', $in_module);

		// If we can't find the config
		if(is_null($aConf))
		{
			self::$iLastError	= self::ERR_CONFIG;
			return false;
		}

		// Make sure the config has a type
		if(!isset($aConf['type']))	$aConf['type']	= 'ftp';

		// Try to connect
		$rCon	= null;
		if($aConf['type'] == 'sftp')
		{
			// @todo Add SFTP type to FTP class
			//	ssh2_connect
			//	ssh2_sftp
			//	ssh2_scp_recv
			//	ssh2_scp_send
		}
		else
		{
			if(($rCon = ftp_connect($aConf['host'], $aConf['port'])) === false)
			{
				self::$iLastError	= self::ERR_CONNECTION;
				return false;
			}
		}

		// Try to login
		if(!ftp_login($rCon, $aConf['user'], $aConf['pass']))
		{
			ftp_close($rCon);
			self::$iLastError	= self::ERR_LOGIN;
			return false;
		}

		// Set PASV
		ftp_pasv($rCon, true);

		// Return the FTP resource
		return $rCon;
	}

	/**
	 * Copy
	 * Copy a file on the FTP server (works by downloading and reuploading, not recommended for large files)
	 * @name copy
	 * @access public
	 * @static
	 * @param string|resource $in_module	Name of the module to use
	 * @param string $in_source				Remote file to copy
	 * @param string $in_destination		Remote location to copy to
	 * @param string $in_backup				Will check if the source file exists, if not, no sense in copying or throwing a warning
	 * @return bool
	 */
	public static function copy(/*string|resource*/ $in_module, /*string*/ $in_source, /*string*/ $in_destination, /*bool*/ $in_backup = false)
	{
		// Get the connection
		if(is_resource($in_module))	{
			$bClose	= false;
			$rCon	= $in_module;
		} else {
			$bClose	= true;
			$rCon	= self::connect($in_module);
			if($rCon === false)	return false;
		}

		// If the point is making a backup
		if($in_backup)
		{
			// And the source doesn't exist
			if(ftp_size($rCon, $in_source) == -1)
			{
				// Return ok, there's nothing to do
				return true;
			}
		}

		// Since there is no cp in ftp, and exec isn't always allowed, the only
		//	option is to get the current copy and then put it with a new name
		$sTemp	= tempnam(sys_get_temp_dir(), 'ftp');
		if(!ftp_get($rCon, $sTemp, $in_source, FTP_BINARY)	||
			!ftp_put($rCon, $in_destination, $sTemp, FTP_BINARY))
		{
			ftp_close($rCon);
			self::$iLastError	= self::ERR_COPY;
			return false;
		}

		// Unlink the temp file
		unlink($sTemp);

		// Close FTP if necessary
		if($bClose)	ftp_close($rCon);

		// Return ok
		return true;
	}

	/**
	 * Delete
	 * Called to delete a file from an FTP server
	 * @name delete
	 * @access public
	 * @static
	 * @param string|resource $in_module	Name of the module to use
	 * @param string $in_source				Path of the file to delete
	 * @return bool
	 */
	public static function delete(/*string|resource*/ $in_module, /*string*/ $in_source)
	{
		// Get the connection
		if(is_resource($in_module))	{
			$bClose	= false;
			$rCon	= $in_module;
		} else {
			$bClose	= true;
			$rCon	= self::connect($in_module);
			if($rCon === false)	return false;
		}

		if(!ftp_delete($rCon, $in_source))
		{
			ftp_close($rCon);
			self::$iLastError	= self::ERR_COPY;
			return false;
		}

		// Close FTP if necessary
		if($bClose)	ftp_close($rCon);

		// Return ok
		return true;
	}

	/**
	 * Download
	 * Called to download a file from an FTP server
	 * @name download
	 * @access public
	 * @static
	 * @param string|resource $in_module	Name of the module to use
	 * @param string $in_source				Remote file to download
	 * @param string $in_destination		Local location
	 * @param int $in_mode					Mode to download file in FTP_BINARY or FTP_ASCII
	 * @return bool
	 */
	public static function download(/*string|resource*/ $in_module, /*string*/ $in_source, /*string*/ $in_destination, /*int*/ $in_mode = FTP_BINARY)
	{
		// Get the connection
		if(is_resource($in_module))	{
			$bClose	= false;
			$rCon	= $in_module;
		} else {
			$bClose	= true;
			$rCon	= self::connect($in_module);
			if($rCon === false)	return false;
		}

		// Attempt to download the file
		if(!ftp_get($rCon, $in_destination, $in_source, $in_mode))
		{
			ftp_close($rCon);
			self::$iLastError	= self::ERR_DOWNLOAD;
			return false;
		}

		// Close FTP if necessary
		if($bClose)	ftp_close($rCon);

		// Return ok
		return true;
	}

	/**
	 * Last Error
	 * Returns the last error that was raised
	 * @name lastError
	 * @access public
	 * @static
	 * @return int
	 */
	public static function lastError()
	{
		return self::$iLastError;
	}

	/**
	 * Last Action
	 * Returns the last action run before an error occured
	 * @name lastAction
	 * @access public
	 * @static
	 * @return int
	 */
	public static function lastAction()
	{
		return self::$iLastAction;
	}

	/**
	 * Move
	 * Called the move/rename a file on an FTP server
	 * @name move
	 * @access public
	 * @static
	 * @param string|resource $in_module	Name of the module to use
	 * @param string $in_source				Remote file to rename
	 * @param string $in_destination		Remote location of new name
	 * @return bool
	 */
	public static function move(/*string|resource*/ $in_module, /*string*/ $in_source, /*string*/ $in_destination)
	{
		// Get the connection
		if(is_resource($in_module))	{
			$bClose	= false;
			$rCon	= $in_module;
		} else {
			$bClose	= true;
			$rCon	= self::connect($in_module);
			if($rCon === false)	return false;
		}

		// Attempt to move the file
		if(!ftp_rename($rCon, $in_source, $in_destination))
		{
			ftp_close($rCon);
			self::$iLastError	= self::ERR_MOVE;
			return false;
		}

		// Close FTP if necessary
		if($bClose)	ftp_close($rCon);

		// Return ok
		return true;
	}

	/**
	 * Upload
	 * Called to upload a file to an FTP server
	 * @name upload
	 * @access public
	 * @static
	 * @param string $in_module			Name of the module to use
	 * @param string $in_source			Local file to upload
	 * @param string $in_destination	Remote location
	 * @param int $in_mode				Mode to upload file in FTP_BINARY or FTP_ASCII
	 * @return bool
	 */
	public static function upload(/*string|resource*/ $in_module, /*string*/ $in_source, /*string*/ $in_destination, /*int*/ $in_mode = FTP_BINARY)
	{
		// Get the connection
		if(is_resource($in_module))	{
			$bClose	= false;
			$rCon	= $in_module;
		} else {
			$bClose	= true;
			$rCon	= self::connect($in_module);
			if($rCon === false)	return false;
		}

		// Attempt to upload the file
		if(!ftp_put($rCon, $in_destination, $in_source, $in_mode))
		{
			ftp_close($rCon);
			self::$iLastError	= self::ERR_UPLOAD;
			return false;
		}

		// Close FTP if necessary
		if($bClose)	ftp_close($rCon);

		// Return ok
		return true;
	}

	/**
	 * Validate Folder
	 * Checks if a path exists, if it doesn't, it creates it
	 * @name validateFolder
	 * @access private
	 * @static
	 * @param resource $in_con			An open FTP connection
	 * @param string|array $in_path		The path to check for
	 * @return bool
	 */
	private static function validateFolder(/*resource*/ $in_con, /*string|array*/ $in_path)
	{
		// First, get the current path
		$sCurr	= ftp_pwd($in_con);
		if($sCurr === false)
		{
			ftp_close($in_con);
			self::$iLastError	= self::ERR_VALFOLDER;
			return false;
		}

		// Make sure we have an array so this is simpler
		$aParts	= (is_array($in_path)) ? $in_path : explode('/', $in_path);

		// Get the count of folders
		$iCnt	= count($aParts);

		// Go backwards through the list
		$i	= $iCnt;
		for(; $i > 0; --$i)
		{
			// Can we get to this directory
			$b	= @ftp_chdir($in_con, implode('/', array_slice($aParts, 0, $i)));

			// Change back to the main path
			ftp_chdir($in_con, $sCurr);

			// If we can change directories, then this path exists
			if($b)	break;
		}

		// If the spot matches the count then all is well
		if($i === $iCnt)
		{
			return true;
		}

		// Else, start creating directories from this point forward
		for(++$i; $i <= $iCnt; ++$i)
		{
			if(ftp_mkdir($in_con, implode('/', array_slice($aParts, 0, $i))) === false)
			{
				ftp_close($in_con);
				self::$iLastError	= self::ERR_VALFOLDER;
				return false;
			}
		}

		// Return ok
		return true;
	}
}