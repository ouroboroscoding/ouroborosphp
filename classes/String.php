<?php
/**
 * String
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2014-12-02
 */

/**
 * String class
 *
 * Various methods that manipulate strings
 *
 * @name _String
 */
class _String
{
	/**
	 * Invalid UTF-8 regular expression constant
	 */
	const INVALID_UTF8	= '/([\xC0-\xC1]|[\xF5-\xFF]|\xE0[\x80-\x9F]|\xF0[\x80-\x8F]|[\xC2-\xDF](?![\x80-\xBF])|[\xE0-\xEF](?![\x80-\xBF]{2})| [\xF0-\xF4](?![\x80-\xBF]{3})|(?<=[\x0-\x7F\xF5-\xFF])[\x80-\xBF]|(?<![\xC2-\xDF]|[\xE0-\xEF]|[\xE0-\xEF][\x80-\xBF]|[\xF0-\xF4]|[\xF0-\xF4][\x80-\xBF]|[\xF0-\xF4][\x80-\xBF]{2})[\x80-\xBF]|(?<=[\xE0-\xEF])[\x80-\xBF](?![\x80-\xBF])|(?<=[\xF0-\xF4])[\x80-\xBF](?![\x80-\xBF]{2})|(?<=[\xF0-\xF4][\x80-\xBF])[\x80-\xBF](?![\x80-\xBF]))/x';
	const HEX_CHARS		= '/^[a-fA-F0-9]+$/';

	/**
	 * Cut
	 *
	 * Cuts a text to a specific length without cutting words in half
	 *
	 * @name cut
	 * @access public
	 * @static
	 * @param string $text				The text to cut
	 * @param uint $max_chars			The maximum number of characters to show
	 * @param string $ellipsis			The text to use as an ellipsis
	 * @return string
	 */
	public static function cut(/*string*/ $text, /*uint*/ $max_chars, /*string*/ $ellipsis = '...')
	{
		if(strlen($text) > $max_chars)
		{
			preg_match('/^(.{0,' . ($max_chars - strlen($ellipsis)) . '})\b/', $text, $aM);
			return trim($aM[1]) . $ellipsis;
		}
		else
		{
			return $text;
		}
	}

	/**
	 * Float to Ratio
	 *
	 * Converts a floating point number into the smallest possible ratio
	 *
	 * @name floatToRatio
	 * @access public
	 * @static
	 * @param float $value				The value to convert to a ratio
	 * @return string
	 */
	public static function floatToRatio(/*float*/ $value)
	{
		// Init least common denominator and other possible denominators
		$iLeastCommon	= 48;
		$aDenominators	= array(2, 3, 4, 8, 16, 24, 48);

		// Get the whole and decimal parts of the value
		$iWhole		= floor($value);
		$fRemainder = $value - $iWhole;

		// Round off the remainder
		$iDecimal	= round($fRemainder * $iLeastCommon ) / $iLeastCommon;

		// If there's no remainder, return the whole number
		if($iDecimal == 0) {
			return $iWhole;
		}

		if($iDecimal == 1) {
			return $iWhole + 1;
		}

		foreach($aDenominators as $d)
		{
			if($iDecimal * $d == floor($iDecimal * $d))
			{
				$denom	= $d;
				break;
			}
		}

		return ($iWhole == 0 ? 0 : $denom) + ($iDecimal * $denom) . ':' . $denom;
	}

	/**
	 * Is Hexadecimal
	 *
	 * Returns true if the text passed represents a hexadecimal number
	 *
	 * @name isHex
	 * @param string text				The text to check
	 * @return bool
	 */
	public static function isHex($text)
	{
		return (preg_match(self::HEX_CHARS, text) !== false);
	}

	/**
	 * Normalize
	 *
	 * Replaces all special alpha characters with their ascii equivalent
	 *
	 * @name normalize
	 * @access public
	 * @static
	 * @param string $text				The text to normalize
	 * @return string
	 */
	public static function normalize(/*string*/ $text)
	{
		return strtr($text, array(
			'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A',
			'Æ'=>'A', 'Ć'=>'C', 'Č'=>'C', 'Ç'=>'C', 'Đ'=>'Dj', 'È'=>'E',
			'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
			'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O',
			'Ö'=>'O', 'Ø'=>'O', 'Ŕ'=>'R', 'Š'=>'S', 'Ù'=>'U', 'Ú'=>'U',
			'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Ž'=>'Z',
			'Þ'=>'B', 'ß'=>'Ss',
			'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a',
			'æ'=>'a', 'ć'=>'c', 'č'=>'c', 'ç'=>'c', 'đ'=>'dj', 'è'=>'e',
			'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
			'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o',
			'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ŕ'=>'r', 'š'=>'s', 'ù'=>'u',
			'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'ÿ'=>'y', 'ž'=>'z',
			'þ'=>'b',
		));
	}

	/**
	 * Random
	 *
	 * Generates a random string. By default this function will generate an 8
	 * character string using lowercase letters with possible repeating
	 * characters
	 *
	 * @name random
	 * @access public
	 * @static
	 * @param uint $len					Length of the password
	 * @param bool $allow_duplicates	Allow characters to be duplicated?
	 * @param array $char_opts			Character options: 'custom' or ['upper' and/or 'digits' and/or 'punctuation']
	 * @return string					Generated string
	 */
	public static function random(/*uint*/ $len = 8, /*bool*/ $allow_duplicates = true, array $char_opts = array())
	{
		// If the 'custom' option was set, use only those characters
		if(isset($char_opts['custom']))
		{
			$sChars	= $char_opts['custom'];
		}
		// Else, look for other options
		else
		{
			$sChars	= 'abcdefghijkmnopqrstuvwxyz';	// Lowercase minus trouble character l (el)

			// Check for additional characters
			if(isset($char_opts['upper']) && $char_opts['upper']) {
				$sChars += 'ABCDEFGHJKLMNPQRSTUVWXYZ';	// Uppercase minus trouble character O (oh) and L (el)
			} else if(isset($char_opts['digits']) && $char_opts['digits']) {
				$sChars += '123456789';					// Digits minus trouble character 0 (zero)
			} else if(isset($char_opts['punctuation']) && $char_opts['punctuation']) {
				$sChars += '!@#$%^&*-_+.?';				// Punctuation characters
			}
		}

		// Init the return variable
		$sText	= '';

		// Count the number of characters we can use
		$iCount	= strlen($sChars);

		// Create a [length] of random character
		for($i = 0; $i < $len;)
		{
			$sFound		= $sChars[mt_rand(0, $iCount - 1)];

			if($allow_duplicates || !strchr($sText, $sFound))
			{
				$sText	.= $sFound;
				++$i;
			}
		}

		// Return the generated string
		return $sText;
	}

	/**
	 * XML Entities
	 *
	 * Replaces reserved XML characters with their entity equivalent
	 *
	 * @name xmlentities
	 * @access public
	 * @static
	 * @param string $text				The text to parse
	 * @return string
	 */
	public static function xmlentities(/*string*/ $text)
	{
		return strtr(
			$text,
			array(
				'&'		=> '&amp;',
				'<'		=> '&lt;',
				'>'		=> '&gt;',
				'\''	=> '&apos;',
				'"'		=> '&quot;'
			)
		);
	}
}
