<?php
/**
 * Common string tasks
 *
 * @author chris nasr
 * @copyright fuel for the fire
 * @package core
 * @version 0.2
 * @created 2011-04-01
 */

/**
 * Strings class
 * @name Strings
 * @package core
 */
class Strings
{
	/**#@+
	 * Constant values
	 */
	const KEY			= '4BkA6NxhuUo8eKgrqu3J7JyqMUDVBHVs';
	const REGEX_URL		= '/^(?:https?|ftp):\/\/([\w\.\+\-]+(:[\w\.\+\-]+)?@)?[a-z0-9-]+(\.[a-z0-9-]+)+(:[0-9]+)?(\/.*)?/';
	/**#@-*/

	/**
	 * Bytes To Path
	 * Convert an unsigned integer into a path where each folder is a byte. i.e. 58914 = 0,0,230,34 = /000/000/230/034
	 * @name bytesToPath
	 * @access public
	 * @static
	 * @param uint $in_value			The value to convert to a path
	 * @param array $in_opts			Options to the method 'bytes' => int, 'symbol' => char, 'as_array' => bool
	 * @return string|array
	 */
	public static function bytesToPath(/*uint*/ $in_value, /*array*/ $in_opts = null)
	{
		// Default options
		$aOpts	= array(
			'bytes'		=> 4,
			'symbol'	=> '0',
			'as_array'	=> false
		);

		// If options were passed
		if(is_array($in_opts))
		{
			$aOpts	= array_merge($aOpts, $in_opts);
		}

		// If bytes requested is more than four, throw a warning and set it to four
		if($aOpts['bytes'] > 4)
		{
			trigger_error("Strings::bytesToPath() can't display more than 4 bytes of an integer.", E_USER_WARNING);
			$aOpts['bytes']	= 4;
		}

		// Template
		$sTpl	= '%' . $aOpts['symbol'] . '3d';

		// There has to be at least one byte, so get it
		$aFolders	= array(sprintf($sTpl, ($in_value & 0xff)));

		// Now go through the remaining bytes
		$iShift		= 8;
		$iMask		= 255;
		for($i = 1; $i < $aOpts['bytes']; ++$i)
		{
			$aFolders[]	= sprintf($sTpl, ($in_value & ($iMask << ($i * 8))) >> ($i * 8));
		}

		// Reverse the values
		$aFolders	= array_reverse($aFolders);

		// If we only want the parts, return them as is
		if($aOpts['as_array'])	return $aFolders;

		// Create and return the path
		return implode('/', $aFolders);
	}

	/**
	 * Console String
	 * Format a string so it fits on the console
	 * @name consoleString
	 * @param string $in_string					The string to modify
	 * @param string $in_leading				The string to put before each line
	 * @param bool $in_ignore_first_leading		Don't figure out leading for the first line
	 * @param uint $in_len						The length of the line
	 * @return string
	 */
	public static function consoleString(/*string*/ $in_string, /*string*/ $in_leading = '', /*bool*/ $in_ignore_first_leading = false, /*uint*/ $in_len = 80)
	{
		// Return
		$mRet			= array();

		// Get the length of the leading string
		$iLeadLen		= strlen($in_leading);

		// Get the actual length we can use per line
		//	total length - the leading length
		$iPerLineLen	= $in_len - $iLeadLen;

		// Split the string by newline
		$aLines		= explode("\n", $in_string);

		// Go through each line
		$iLineCount	= count($aLines);
		for($i = 0; $i < $iLineCount; ++$i)
		{
			// Get the length of the line
			$iLineLen	= strlen($aLines[$i]);

			// If the line length is greater than the per line length
			if($iLineLen > $iPerLineLen)
			{
				// Get the number of lines we'll get from this one line
				$iTotalLines	= (int)ceil($iLineLen / $iPerLineLen);

				// And split it up
				for($y = 0; $y < $iTotalLines; ++$y)
				{
					$mRet[]	= (($in_ignore_first_leading && $i == 0 && $y == 0) ? '' : $in_leading) . substr($aLines[$i], $iPerLineLen * $y, $iPerLineLen);
				}
			}
			// Else just add the line to the leading
			else
			{
				$mRet[]	= (($in_ignore_first_leading && $i == 0) ? '' : $in_leading) . $aLines[$i];
			}
		}

		// Now add the new lines together and return it as a string
		return implode("\n", $mRet);
	}

	/**
	 * Convert String Date To Time
	 * Converts a string date into a timestamp
	 * @name convertStringDateToTime
	 * @access public
	 * @static
	 * @param string $in_string			Date to convert
	 * @param string $in_format			Format of the date
	 * @return uint
	 */
	public static function convertStringDateToTime($in_string, $in_format = '%Y-%m-%d %H:%M:%S')
	{
		$p	= strptime($in_string, $in_format);
		$p['tm_mon']	= $p['tm_mon'] + 1;
		$p['tm_year']	= $p['tm_year'] + 1900;
		$date_stamp		= mktime($p['tm_hour'], $p['tm_min'], $p['tm_sec'], $p['tm_mon'], $p['tm_mday'], $p['tm_year']);

		return $date_stamp;
	}

	/**
	 * Cut Text
	 * Cut text to a specified length and adds '...' at the end
	 * @name cutText
	 * @access public
	 * @static
	 * @param string $in_string			Text to cut
	 * @param uint $in_nb_chars			Max length of the final string
	 * @return string
	 */
	public static function cutText(/*string*/ $in_string, /*uint*/ $in_nb_chars)
	{
		if(strlen($in_string) > $in_nb_chars)
		{
			preg_match('/^(.{' . $in_nb_chars - 3 . '}.*?)\b/', $in_string, $aM);
			return $aM[1] . '...';
		}
		else
		{
			return $in_string;
		}
	}

	/**
	 * Cut Middle
	 * Cut text to a specified length by cutting out the middle of the string and replacing it with '...'
	 * @name cutMiddle
	 * @access public
	 * @static
	 * @param string $in_string			Text to cut
	 * @param uint $in_nb_chars			Max length of the final string
	 * @return string
	 */
	public static function cutMiddle(/*string*/ $in_string, /*uint*/ $in_nb_chars)
	{
		if(strlen($in_string) > $in_nb_chars)
		{
			// Split the count in half
			$fHalf	= ($in_nb_chars - 3) / 2;
			$iS		= (int)ceil($fHalf);
			$iE		= (int)floor($fHalf);

			preg_match('/^(.{' . $iS . '}).*(.{' . $iE . '})$/', $in_string, $aM);
			return $aM[1] . '...' . $aM[2];
		}
		else
		{
			return $in_string;
		}
	}

	/**
	 * Decrypt
	 * Decrypt a string
	 * @name decrypt
	 * @access public
	 * @static
	 * @param string $in_text			Text to decrypt
	 * @param string $in_key			Key
	 * @return string
	 */
	public static function decrypt(/*string*/ $in_text, /*string*/ $in_key = null)
	{
		$result		= '';
		$in_text	= base64_decode($in_text);

		if(is_null($in_key))
		{
			$in_key	= self::KEY;
		}

		for($i = 0; $i < strlen($in_text); ++$i)
		{
			$char		= substr($in_text, $i, 1);
			$keychar	= substr($in_key, ($i % strlen($in_key)) - 1, 1);
			$char		= chr(ord($char) - ord($keychar));
			$result		.= $char;
		}

		return $result;
	}

	/**
	 * Encrypt
	 * Encrypt a string
	 * @name encrypt
	 * @access public
	 * @static
	 * @param string $in_text			Text to encrypt
	 * @param string $in_key			Key
	 * @return string
	 */
	public static function encrypt(/*string*/ $in_text, /*string*/ $in_key = null)
	{
		// Inti result
		$result	= '';

		// If no key was passed, use the default
		if(is_null($in_key))
		{
			$in_key	= self::KEY;
		}

		for($i = 0; $i < strlen($in_text); ++$i)
		{
			$char		= substr($in_text, $i, 1);
			$keychar	= substr($in_key, ($i % strlen($in_key))-1, 1);
			$char		= chr(ord($char)+ord($keychar));
			$result		.= $char;
		}

		return rtrim(base64_encode($result), '=');
	}

	/**
	 * Generate Password
	 * Generate a random password. By default this function will generate an 8 character password using lowercase and uppercase letters, and digits, no characters will be repeated
	 * @name generatePassword
	 * @access public
	 * @static
	 * @param uint $in_len				Length of the password
	 * @param bool $in_use_upper		Use uppercase characters?
	 * @param bool $in_use_digits		Use digits?
	 * @param bool $in_use_punc			Use punctuation?
	 * @param bool $in_allow_dup		Allow characters to be duplicated?
	 * @return string					Generated password
	 */
	public static function generatePassword(/*uint*/ $in_len = 8, /*bool*/ $in_use_upper = true, /*bool*/ $in_use_digits = true, /*bool*/ $in_use_punc = false, /*bool*/ $in_allow_dup = false)
	{
		// Variables
		$sPassword	= '';
		$sChars		= 'abcdefghijkmnopqrstuvwxyz';	// Lowercase minus trouble character l (el)

		// Check for additional characters
		if($in_use_upper)	$sChars	.= 'ABCDEFGHIJKLMNPQRSTUVQYWZ';	// Uppercase minus trouble character O (oh)
		if($in_use_digits)	$sChars	.= '123456789';					// Digits minus trouble character 0 (zero)
		if($in_use_punc)	$sChars	.= '!@#$%^&*-_+.?';				// Punctuation characters

		// Count the number of characters
		$iCount		= strlen($sChars);

		// Create a [length] of random character
		for($i = 0; $i < $in_len;)
		{
			$sFound		= $sChars[mt_rand(0, $iCount - 1)];

			if($in_allow_dup || !strchr($sPassword, $sFound))
			{
				$sPassword	.= $sFound;
				++$i;
			}
		}

		// Return the generated password
		return $sPassword;
	}

	/**
	 * Get Ordinal
	 * Returns the ordinal of a number
	 * @name getOrdinal
	 * @access public
	 * @static
	 * @param uint $in_num				The number who's ordinal we want
	 * @return string
	 */
	public static function getOrdinal(/*uint*/ $in_num)
	{
		$iSuffix	= (int)substr($in_num, -2);
		if(in_array($iSuffix, array(11, 12, 13)))
		{
			return 'th';
		}
		unset($iSuffix);

		switch((int)substr($in_num, -1))
		{
			case 1:		return 'st';
			case 2:		return 'nd';
			case 3:		return 'rd';
			default:	return 'th';
		}
	}

	/**
	 * Hex 2 Binary
	 * Convert a hex string into binary
	 * @name hex2bin
	 * @access public
	 * @static
	 * @param string $in_hex			String reprenting hex numbers
	 * @return string
	 */
	public static function hex2bin($in_hex)
	{
		$len	= strlen($in_hex);

		if($len % 2 != 0 || preg_match('/[^\da-fA-F]/', $in_hex))	return false;

		$out	= '';
		for($i = 1; $i <= $len/2; $i++)
		{
			$out .= chr(hexdec(substr($in_hex, 2 * $i - 2, 2)));
		}

		return $out;
	}

	/**
	 * Strip Tags
	 * Recursively strips tags from arrays. Uses PHP strip_tags
	 * @name stripTags
	 * @access public
	 * @static
	 * @param mixed $in_str					A string or an array
	 * @return mixed						Same as $in_str
	 * @see strip_tags
	 */
	public static function stripTags($in_str)
	{
		if(is_array($in_str))
		{
			foreach($in_str as $n => $v)
			{
				$in_str[$n]	= self::stripTags($v);
			}

			return $in_str;
		}
		else
		{
			return strip_tags($in_str);
		}
	}

	/**
	 * Validate URL
	 * Validates a URL
	 * @name validateURL
	 * @access public
	 * @static
	 * @param string $in_url			The URL to validate
	 * @return bool
	 */
	public static function validateURL(/*string*/ $in_url)
	{
		return (preg_match(self::REGEX_URL, $in_url)) ? true : false;
	}

	/**
	 * XML Entities
	 * Converts HTML entities to XML entities
	 * @name xmlEntities
	 * @access public
	 * @static
	 * @param string $in_str			String to encode
	 * @return string
	 */
	public static function xmlEntities($in_str)
	{
		$aHTML	= array('&quot;','&amp;','&amp;','&lt;','&gt;','&nbsp;','&iexcl;','&cent;','&pound;','&curren;','&yen;','&brvbar;','&sect;','&uml;','&copy;','&ordf;','&laquo;','&not;','&shy;','&reg;','&macr;','&deg;','&plusmn;','&sup2;','&sup3;','&acute;','&micro;','&para;','&middot;','&cedil;','&sup1;','&ordm;','&raquo;','&frac14;','&frac12;','&frac34;','&iquest;','&Agrave;','&Aacute;','&Acirc;','&Atilde;','&Auml;','&Aring;','&AElig;','&Ccedil;','&Egrave;','&Eacute;','&Ecirc;','&Euml;','&Igrave;','&Iacute;','&Icirc;','&Iuml;','&ETH;','&Ntilde;','&Ograve;','&Oacute;','&Ocirc;','&Otilde;','&Ouml;','&times;','&Oslash;','&Ugrave;','&Uacute;','&Ucirc;','&Uuml;','&Yacute;','&THORN;','&szlig;','&agrave;','&aacute;','&acirc;','&atilde;','&auml;','&aring;','&aelig;','&ccedil;','&egrave;','&eacute;','&ecirc;','&euml;','&igrave;','&iacute;','&icirc;','&iuml;','&eth;','&ntilde;','&ograve;','&oacute;','&ocirc;','&otilde;','&ouml;','&divide;','&oslash;','&ugrave;','&uacute;','&ucirc;','&uuml;','&yacute;','&thorn;','&yuml;');
		$aXML	= array('&#34;','&#38;','&#38;','&#60;','&#62;','&#160;','&#161;','&#162;','&#163;','&#164;','&#165;','&#166;','&#167;','&#168;','&#169;','&#170;','&#171;','&#172;','&#173;','&#174;','&#175;','&#176;','&#177;','&#178;','&#179;','&#180;','&#181;','&#182;','&#183;','&#184;','&#185;','&#186;','&#187;','&#188;','&#189;','&#190;','&#191;','&#192;','&#193;','&#194;','&#195;','&#196;','&#197;','&#198;','&#199;','&#200;','&#201;','&#202;','&#203;','&#204;','&#205;','&#206;','&#207;','&#208;','&#209;','&#210;','&#211;','&#212;','&#213;','&#214;','&#215;','&#216;','&#217;','&#218;','&#219;','&#220;','&#221;','&#222;','&#223;','&#224;','&#225;','&#226;','&#227;','&#228;','&#229;','&#230;','&#231;','&#232;','&#233;','&#234;','&#235;','&#236;','&#237;','&#238;','&#239;','&#240;','&#241;','&#242;','&#243;','&#244;','&#245;','&#246;','&#247;','&#248;','&#249;','&#250;','&#251;','&#252;','&#253;','&#254;','&#255;');

		$in_str	= str_replace($aHTML, $aXML, $in_str);

		return str_ireplace($aHTML, $aXML, $in_str);
	}
}
