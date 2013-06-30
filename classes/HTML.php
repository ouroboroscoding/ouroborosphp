<?php
/**
 * HTML class
 * Used to generate things like <options> and <tables>
 *
 * @author chris nasr
 * @copyright fuel for the fire
 * @package core
 * @version 0.1
 * @created 2012-04-21
 */

/**
 * HTML class
 * @name HTML
 * @package core
 */
class HTML
{
	/**
	 * Options
	 * Creates a list of <option> elements
	 * @name options
	 * @access public
	 * @static
	 * @param array $in_options			value => text pairs
	 * @param mixed $in_value			the selected value
	 * @return string
	 */
	public static function options(array $in_options, /*mixed*/ $in_value = null)
	{
		$aOpts	= array();
		foreach($in_options as $mValue => $mText)
		{
			$sSelected	= (!is_null($in_value) && $mValue == $in_value) ?
							' selected="selected"' :
							'';

			$aOpts[]	= "<option value=\"{$mValue}\"{$sSelected}>{$mText}</option>";
		}
		$sOpts	= implode('', $aOpts);
		unset($aOpts);

		return $sOpts;
	}
}