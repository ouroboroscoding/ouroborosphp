<?php
/**
 * Common array tasks
 *
 * @author chris nasr
 * @copyright fuel for the fire
 * @package core
 * @version 0.2
 * @created 2012-03-02
 */

/**
 * Arrays class
 * @name Arrays
 * @package core
 */
class Arrays
{
	/**
	 * Check Options
	 * Checks all options are set in an array, if any aren't, they're set to their default
	 * @name checkOptions
	 * @access public
	 * @static
	 * @param array &$in_opts			The array to check for options
	 * @param array $in_pairs			The name(s) of the option (key) to check and its default (value)
	 * @return void
	 */
	public static function checkOptions(array &$in_opts, array $in_pairs)
	{
		foreach($in_pairs as $sName => $mDefault)
		{
			if(!isset($in_opts[$sName]) || empty($in_opts[$sName]))
			{
				$in_opts[$sName]	= $mDefault;
			}
		}
	}
}