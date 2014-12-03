<?php
/**
 * Error
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2014-12-02
 */

/**
 * Error class
 *
 * Holds error messages as they are added for later use
 *
 * @name _Error
 */
class _Error
{
	/**
	 * Errors
	 *
	 * An array of strings holding each error that is added
	 *
	 * @var string[]
	 * @access private
	 * @static
	 */
	private static $mErrors = array();

	/**
	 * Add
	 *
	 * Adds an error to the list
	 *
	 * @name add
	 * @access public
	 * @static
	 * @return void
	 */
	public static function add(/*mixed*/ $error)
	{
		self::$mErrors[]	= $error;
	}

	/**
	 * Dump
	 *
	 * Prints out all errors stored
	 *
	 * @name dump
	 * @access public
	 * @static
	 * @return void
	 */
	public static function dump()
	{
		foreach(self::$mErrors as $mError)
		{
			// If the value is a string
			if(is_string($mError)) {
				echo $mError, "\n";
			} else {
				var_dump($mError);
				echo "\n";
			}
		}
	}

	/**
	 * Get All
	 *
	 * Returns the list of errors stored during the life of the script
	 *
	 * @name getAll
	 * @access public
	 * @static
	 * @return array
	 */
	public static function getAll()
	{
		return self::$mErrors;
	}

	/**
	 * Get Last
	 *
	 * Returns the last error stored
	 *
	 * @name getLast
	 * @access public
	 * @static
	 * @return mixed
	 */
	public static function getLast()
	{
		return end(self::$mErrors);
	}
}
