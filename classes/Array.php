<?php
/**
 * Array
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2014-12-02
 */

/**
 * Array class
 *
 * Common functions for handling arrays
 *
 * @name _Array
 */
class _Array
{
	/**
	 * Check Options
	 *
	 * Checks all options are set in an array, if any aren't, they're set to their default
	 *
	 * @name CheckOptions
	 * @access public
	 * @static
	 * @param array &$opts			The array to check for options
	 * @param array $pairs			The name(s) of the option (key) to check and its default (value)
	 * @return void
	 */
	public static function checkOptions(array &$opts, array $pairs)
	{
		foreach($pairs as $sName => $mDefault) {
			if(!isset($opts[$sName])) {
				$opts[$sName]	 = $mDefault;
			}
		}
	}

	/**
	 * Make Column
	 *
	 * Goes through an array and creates a new array from only the element
	 * passed
	 *
	 * @name makeColumn
	 * @access public
	 * @static
	 * @param array[] $array			An array of arrays
	 * @param string $element			The element to pull from each array
	 * @return array
	 */
	public static function makeColumn(array $array, /*string*/ $element)
	{
		// The new array
		$aColumn	= array();

		// Go through the passed in array
		foreach($array as $a)
		{
			// If the element doesn't exist, fail
			if(!isset($a[$element])) {
				trigger_error(__METHOD__ . ' Error: Failed to make column, not all arrays contain "' . $element . '".', E_USER_ERROR);
			}

			// Else, add it to the list
			$aColumn[]	= $a[$element];
		}

		// Return the new column
		return $aColumn;
	}

	/**
	 * Make Hash
	 *
	 * Goes through an array and creates a new hash from the key and value
	 * fields passed
	 *
	 * @name makeHash
	 * @access public
	 * @static
	 * @param array[] $array			An array of arrays
	 * @param string $key				The element to use as the key
	 * @param string $value				The element to use as the value
	 * @return array
	 */
	public static function makeHash(array $array, /*string*/ $key, /*string*/ $value)
	{
		// The new array
		$aHash	= array();

		// Go through the passed in array
		foreach($array as $a)
		{
			// If the key doesn't exist, fail
			if(!isset($a[$key])) {
				trigger_error(__METHOD__ . ' Error: Failed to make hash, not all arrays contain "' . $key . '".', E_USER_ERROR);
			}

			// If the value doesn't exist, fail
			if(!isset($a[$value])) {
				trigger_error(__METHOD__ . ' Error: Failed to make hash, not all arrays contain "' . $value . '".', E_USER_ERROR);
			}

			// Else, add it to the list
			$aHash[$a[$key]]	= $a[$value];
		}

		// Return the new hash
		return $aHash;
	}
}