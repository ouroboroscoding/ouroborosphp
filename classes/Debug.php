<?php
/**
 * Debug class, used to keep debugging info
 *
 * @author chris nasr
 * @copyright fuel for the fire
 * @package core
 * @version 0.1
 * @created 2012-06-01
 */

/**
 * Debug class
 * @name Debug
 * @package core
 */
class Debug
{
	/**
	 * Holds the lines of the debug information by type
	 * @var array
	 * @access private
	 * @static
	 */
	private static $saLines	= null;

	/**
	 * Initialize
	 * Sets up the class, if you don't call this no debugging info will be recorded
	 * @name initialize
	 * @access public
	 * @static
	 * @param mixed $in_types			A single type or a list of types, character case is ignored
	 * @return void
	 */
	public static function initialize(/*mixed*/ $in_types)
	{
		// If the type is not an array
		if(!is_array($in_types))
		{
			// Trim the value
			$in_types	= trim($in_types);

			// If it's empty, quit
			if(empty($in_types))	return;

			// Make it into an array
			$in_types	= array($in_types);
		}

		// If the error is empty, quit
		if(empty($in_types))	return;

		// Init the lines array
		self::$saLines	= array();

		// Go through each item in the array
		foreach($in_types as $mType)
		{
			// First, lowercase the string
			$mType	= mb_strtolower($mType);

			// There's no array for it yet
			if(!isset(self::$saLines[$mType]))
			{
				// Create it
				self::$saLines[$mType]	= array();
			}
		}
	}

	/**
	 * Add
	 * Add debug info to the list
	 * @name add
	 * @access public
	 * @static
	 * @param string $in_type			Type of list to add it to
	 * @param string $in_debug_info		The info to store
	 * @return void
	 */
	public static function add(/*string*/ $in_type, /*string*/ $in_debug_info)
	{
		// If the array isn't initialized, quit
		if(is_null(self::$saLines))	return;

		// If the type isn't set, quit
		$in_type	= mb_strtolower($in_type);
		if(!isset(self::$saLines[$in_type]))	return;

		// Get the time
		list($sMSec, $sSec)	= microtime();

		// Add the line with the timestamp
		self::$saLines[$in_type][]	= strftime('%Y-%m-%d %H:%M:%S ' . $sMSec, (int)$sSec) . ': ' . $in_debug_info;
	}

	/**
	 * Display
	 * Display the results of all the aquired debugging
	 * @name display
	 * @access public
	 * @static
	 * @param unknown_type $in_html
	 */
	public static function display(/*bool*/ $in_html = false)
	{
		// If the array isn't initialized, quit
		if(is_null(self::$saLines))	return;

		// Go through each type and print it out
		foreach(self::$saLines as $sType => $aLines)
		{
			// Display the info
			echo '<b>', mb_strtoupper($sType), '</b><br />', implode('<br />', $aLines), '<br /><br />';
		}
	}
}