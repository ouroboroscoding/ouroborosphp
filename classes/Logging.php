<?php
/**
 * Logging
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2014-12-02
 */

/**
 * Logging class
 *
 * Handles logging data and backing it up. Uses configuration values in
 * 'logging'
 *
 * @name _Logging
 */
class _Logging
{
	/**
	 * Config
	 *
	 * Holds the config data
	 *
	 * @var array
	 * @access private
	 * @static
	 */
	private static $aConf	= null;

	/**
	 * Backup
	 *
	 * Goes through each log file and backs it up, deleting the oldest if it's
	 * over the max number
	 *
	 * @name backup
	 * @access public
	 * @static
	 * @param string $log				The full filepath to the log file
	 * @param uint $count				Used by the method when calling itself, should not be set externally
	 * @return void
	 */
	private static function backup(/*string*/ $log, $count = 0)
	{
		// Get the full filename based on the count
		$file	= ($count) ?				// If there's a count
					($log . '.' . $count) : // Add it to the filename
					$log;					// Else use the filename as is

		// If the file exists
		if(file_exists($file))
		{
			// If we hit the max number of files
			if(self::$aConf['max_backups'] == $count)
			{
				// Delete the file and return
				unlink($file);
				return;
			}

			// Increment the count
			++$count;

			// Look for the next file
			self::backup($log, $count);

			// Then rename this file
			rename($file, $log . '.' . $count);
		}
	}

	/**
	 * Log
	 *
	 * Takes any type of data and logs it to a file with the given name
	 *
	 * @name log
	 * @access public
	 * @static
	 * @param string $name				The name of the log file
	 * @param mixed $data				The data to log
	 * @param bool $date				If true a date and time will be prepended to all logs
	 * @return void
	 */
	public static function log(/*string*/ $name, /*mixed*/ $data, /*bool*/ $date = true)
	{
		// Init local
		$bExists	= false;

		// If we haven't gotten the config yet
		if(is_null(self::$aConf)) {
			self::parseConfig();
		}

		// Create the full filename
		$sLog	= self::$aConf['path'] . '/' . $name;

		// Check if the file already exists
		if(file_exists($sLog) && ($aStat = stat($sLog)))
		{
			// Update the exists flag
			$bExists	= true;

			// Check if the file is over the size limit
			if($aStat['size'] > self::$aConf['max_size'])
			{
				// Create backups
				self::backup($sLog);
			}
		}

		// Check what kind of data it is
		switch(gettype($data))
		{
			case 'array':
				$data	= json_encode($data);
				break;

			case 'object':
				$data	= print_f($data, true);
				break;
		}

		// If we want the date and time
		if($date) {
			$data	= date('r') . ': ' . $data;
		}

		// Store the data in the file
		file_put_contents($sLog, $data . "\n", FILE_APPEND);

		// If the file hadn't existed yet
		if(!$bExists) {
			chmod($sLog, 0666);
		}
	}

	/**
	 * Parse Config
	 *
	 * Loads the config and makes sure all the values are valid
	 *
	 * @name parseConfig
	 * @access private
	 * @static
	 * @return void
	 */
	private static function parseConfig()
	{
		// Look for the config
		self::$aConf	= _Config::get('logging', array(
			'max_backups'	=> 5,
			'max_size'		=> '10M',
			'path'			=> 'log'
		));

		// If the max backups value isn't set
		if(!isset(self::$aConf['max_backups'])) {
			self::$aConf['max_backups'] = 5;
		}

		// Check the max backups value
		if(!is_numeric(self::$aConf['max_backups']) ||
			self::$aConf['max_backups'] < 0) {
			throw new Exception('Logging max_backups is not valid.');
		}

		// If the max size value isn't set
		if(!isset(self::$aConf['max_size'])) {
			self::$aConf['max_size']	= 10485760; // 10 megabytes
		}

		// Check the size
		if(preg_match('/^([0-9]+)(M|K|B)?$/i', self::$aConf['max_size'], $aM))
		{
			// Default to bytes
			self::$aConf['max_size']	= $aM[1];

			// Check for a size modifier
			if(isset($aM[2]))
			{
				switch($aM[2])
				{
					// Megabytes
					case 'M':
					case 'm':
						self::$aConf['max_size']	*= 1048576;
						break;

					// Kilobytes
					case 'K':
					case 'k':
						self::$aConf['max_size']	*= 1024;
						break;
				}
			}
		}
		else
		{
			throw new Exception('Logging max_size is not valid.');
		}

		// If the path is not set
		if(!isset(self::$aConf['path'])) {
			self::$aConf['path']		= 'log';
		}

		// Store the real path of the folder
		self::$aConf['path']  = realpath(self::$aConf['path']);

		// Check the path exists
		if(!is_dir(self::$aConf['path'])) {
			throw new Exception('Logging path does not exist.');
		}

		// Check the path is writable
		if(!is_writable(self::$aConf['path'])) {
			throw new Exception('Logging path is not writable.');
		}
	}
}
