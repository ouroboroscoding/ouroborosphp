<?php
/**
 * Config
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2014-12-02
 */

/**
 * Config class
 *
 * Easy storage for configuration settings
 *
 * @name _Config
 */
class _Config
{
	/**
	 * Values
	 *
	 * All the values in the configuration
	 *
	 * @var array
	 * @access private
	 */
	private static $aValues = array();

	/**
	 * Append
	 *
	 * Adds new values on top of the existing ones. Duplicates will be
	 * overwritten.
	 *
	 * @name append
	 * @access public
	 * @static
	 * @param array $values				The values to add to the config
	 * @return void
	 */
	public static function append(array $values)
	{
		self::appendWorker($values, self::$aValues);
	}

	/**
	 * Append Worker
	 *
	 * The recursive method that does the real work of append
	 *
	 * @name appendWorker
	 * @access private
	 * @static
	 * @param array $values				The values to add to the existing
	 * @param array &$existing			The existing part of the array we are overwriting
	 * @return void
	 */
	private static function appendWorker(array $values, array &$existing)
	{
		foreach($values as $sName => $mValue)
		{
			// If it's an array and it already exists
			if(is_array($mValue) && isset($existing[$sName]) && is_array($existing[$sName]))
			{
				// Recurse
				self::appendWorker($mValue, $existing[$sName]);
			}
			else
			{
				// Add/overwrite it
				$existing[$sName]	= $mValue;
			}
		}
	}

	/**
	 * Init
	 *
	 * Overwrites all values with the array sent
	 *
	 * @name init
	 * @access public
	 * @static
	 * @param array $values				The values to store
	 * @return void
	 */
	public static function init(array $values)
	{
		// Overwrite all values
		self::$aValues	= $values;
	}

	/**
	 * Set
	 *
	 * Overwrites or creates the key by setting it to the value passed
	 *
	 * @name set
	 * @access public
	 * @param string|array $key			The key to write or overwrite
	 * @param mixed $value				The value to set the key to
	 * @return void
	 */
	public static function set(/*string|array*/ $key, /*mixed*/ $value)
	{
		// If the $key is an array
		if(is_array($key)) {
			$iCount = count($key);
		} else {
			// It's a string, so check if we need to split it by colon
			if(strpos($key, ':')) {
				$key	= explode(':', $key);
				$iCount = count($key);
			} else {
				$key	= array($key);
				$iCount = 1;
			}
		}

		// Set the first level
		$mData	= &self::$aValues;

		// Get the setting
		for($i = 0; $i < $iCount; ++$i)
		{
			// If the key doesn't exist
			if(!isset($mData[$key[$i]])) {
				$mData[$key[$i]]	= array();
			}

			$mData	= &$mData[$key[$i]];

			// If we're on the last item
			if($i == ($iCount - 1)) {
				$mData	= $value;
			}
		}
	}

	/**
	 * Get
	 *
	 * Gets a configuration setting and returns it. The more arguments you send,
	 * the deeper the value. Sending no value will return all settings.
	 *
	 * @name get
	 * @access public
	 * @param string|array $key			The key, separated by colons, or as an array, to the config variable
	 * @param mixed $default			The default value to return if the variable isn't found
	 * @return mixed
	 */
	public static function get(/*string|array*/ $key = '', /*mixed*/ $default = null)
	{
		// If the $key is an array
		if(is_array($key)) {
			$iCount = count($key);
		} else if(!empty($key)) {
			// It's a string, so check if we need to split it by colon
			if(strpos($key, ':')) {
				$key	= explode(':', $key);
				$iCount = count($key);
			} else {
				$key	= array($key);
				$iCount = 1;
			}
		} else {
			return self::$aValues;
		}

		// Set the first level
		$mData	= self::$aValues;

		// Get the setting
		for($i = 0; $i < $iCount; ++$i)
		{
			// If the data doesn't exist, return null
			if(!isset($mData[$key[$i]])) return $default;

			// Set the current level
			$mData	= $mData[$key[$i]];
		}

		// Return
		return $mData;
	}
}
