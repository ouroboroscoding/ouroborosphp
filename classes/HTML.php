<?php
/**
 * HTML Class
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2014-12-02
 */

/**
 * HTML class
 *
 * Methods to ease generating common HTML
 *
 * @name _HTML
 */
class _HTML
{
	/**
	 * Options
	 *
	 * Creates a list of <option> elements
	 *
	 * @name options
	 * @access public
	 * @static
	 * @param array $options			value => text pairs
	 * @param mixed $value				The selected value if there is one
	 * @param bool $assoc				If true, array is assumed to be associative, else only values are used
	 * @return string
	 */
	public static function options(array $options, /*mixed*/ $value = null, /*bool*/ $assoc = true)
	{
		$aOpts	= array();

		// If we are using an associative array
		if($assoc)
		{
			foreach($options as $mValue => $mText)
			{
				$sSelected	= (!is_null($value) && $mValue == $value) ?
								' selected="selected"' :
								'';

				$aOpts[]	= "<option value=\"{$mValue}\"{$sSelected}>{$mText}</option>";
			}
		}
		else
		{
			foreach($options as $mValue)
			{
				$sSelected	= (!is_null($value) && $mValue == $value) ?
								' selected="selected"' :
								'';

				$aOpts[]	= "<option{$sSelected}>{$mValue}</option>";
			}
		}

		$sOpts	= implode('', $aOpts);
		unset($aOpts);

		return $sOpts;
	}

	/**
	 * Table Rows
	 *
	 * Creates a list of <tr> elements with <td>s
	 *
	 * @name tableRows
	 * @access public
	 * @static
	 * @param array $rows				An array of arrays
	 * @param array $rules				Rules for adding attributes and formatting
	 * @param string $default			Text to display if there's no data
	 * @return string
	 */
	public static function tableRows(array $rows, array $order, array $rules = array(), /*string*/ $default = 'No rows')
	{
		// If there are rows
		if(count($rows))
		{
			// Go through each one
			$aRows	= array();
			foreach($rows as $aRow)
			{
				// If there are TR rules
				$aTRAttrs	 = array();
				if(isset($rules['tr']))
				{
					// For each rule
					foreach($rules['tr'] as $sRule)
					{
						// Split up the rule
						$aRule	= explode('|', $sRule);

						// Check the type
						switch($aRule[0])
						{
							// Add attribute
							case 'attr':
								$aTRAttrs[] = ' ' . $aRule[1] . '="' . (($aRule[2]{0} == '@') ? $aRow[substr($aRule[2], 1)] : $aRule[2]) . '"';
								break;

							// Invalid rule
							default:
								trigger_error(__METHOD__ . ' Error: Invalid rule for "' . $sType . '".', E_USER_NOTICE);
								break;
						}
					}
				}

				// Go through each column (field)
				$aColumns	 = array();
				foreach($order as $mField)
				{
					// Check the field exists or is being passed
					if(is_array($mField) || isset($aRow[$mField]))
					{
						// If the field name is an array
						if(is_array($mField)) {
							$sField			= $mField[0];
							$aRow[$sField]	= $mField[1];
						} else {
							$sField = $mField;
						}

						// If there are any TD rules
						$aTDAttrs	 = array();
						if(isset($rules['td:' . $sField]))
						{
							// Add each rule
							foreach($rules['td:' . $sField] as $sRule)
							{
								// Split up the rule
								$aRule	= explode('|', $sRule);

								// Check if we need to replace the last part of the rule
								if(isset($aRule[2]) && $aRule[2]{0} == '@') {
									$aRule[2]	= $aRow[substr($aRule[2], 1)];
								}

								// Check the type
								switch($aRule[0])
								{
									// Add attribute
									case 'attr':
										// Check if the 3rd part has multiple values
										$aAttrs			= explode(':', $aRule[2]);
										if(count($aAttrs) > 1) {
											foreach($aAttrs as $sTemp) {
												list($sValue, $sText)	= explode('=', $sTemp);
												if($aRow[$sField] == $sValue) {
													$aTDAttrs[]		= ' ' . $aRule[1] . '="' . $sText . '"';
												}
											}
										} else {
											$aTDAttrs[]		= ' ' . $aRule[1] . '="' . $aRule[2] . '"';
										}
										break;

									// Convert timestamp into date string
									case 'date':
										$aRow[$sField]	= strftime($aRule[1], $aRow[$sField]);
										break;

									// Replace a specific string with the value of the field
									case 'insert':
										$aRow[$sField]	= str_replace($aRule[1], $aRow[$sField], $aRule[2]);
										break;

									// Replace using regular expressions
									case 'regex':
										$aRow[$sField]	= preg_replace($aRule[1], $aRule[2], $aRow[$sField]);
										break;

									// Replace one word/char/string with another
									case 'replace':
										$aRow[$sField]	= str_replace($aRule[1], $aRule[2], $aRow[$sField]);
										break;

									case 'time':
										$iHours = floor($aRow[$sField] / 3600);
										$iMins	= floor(($aRow[$sField] - ($iHours * 3600)) / 60);
										if($iHours && $iMins < 10)	$iMins	= '0' . $iMins;
										$iSecs	= floor($aRow[$sField] % 60);
										if($iMins && $iSecs < 10)	$iSecs	= '0' . $iSecs;
										$aRow[$sField]	= ($iHours ? $iHours . ':' : '') .
															($iMins ? $iMins . ':' : '') .
															$iSecs;
										break;

									// Invalid rule
									default:
										trigger_error(__METHOD__ . ' Error: Invalid rule for "' . $sType . '".', E_USER_NOTICE);
										break;
								}
							}
						}

						// Create the column
						$aColumns[] = '<td' . implode('', $aTDAttrs) . '>' . $aRow[$sField] . '</td>';
					}
					else
					{
						trigger_error(__METHOD__ . ' Error: Invalid or missing field passed "' . ((is_array($mField)) ? $mField[0] : $mField) . '".', E_USER_WARNING);
					}
				}

				// Create the row
				$aRows[]	= '<tr' .implode('', $aTRAttrs) . '>' . implode('', $aColumns) . '</tr>';
			}
			$sRows	= implode('', $aRows);
			unset($aRows);
		}
		else
		{
			$sRows	= '<tr><td colspan="' . count($order) . '">' . $default . '</td></tr>';
		}

		return $sRows;
	}
}