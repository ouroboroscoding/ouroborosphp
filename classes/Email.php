<?php
/**
 * Email class, handles all e-mail related tasks
 *
 * @author chris nasr
 * @copyright fuel for the fire
 * @package core
 * @version 0.1
 * @created 2012-05-31
 */

/**
 * Email class
 * @name Email
 * @package core
 */
class Email
{
	/**#@+
	 * Constants
	 */
	const REGEX_FORMAT	= '/^[a-zA-Z0-9\._%+-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*\.[a-zA-Z]{2,4}$/';
	/**#@-*/

	/**
	 * Send
	 * Send an e-mail to a user or a list of users
	 * @name send
	 * @access public
	 * @static
	 * @param string|array $in_to		The address(es) to send the e-mail to
	 * @param string $in_subject		The subject of the e-mail
	 * @param string $in_body			The body of the e-mail
	 * @param array $in_conf			'host', 'from_name', 'from_email'
	 * @return bool
	 */
	public static function send(/*string|array*/ $in_to, /*string*/ $in_subject, /*string*/ $in_body, /*array*/ $in_conf = null)
	{
		// If the PHPMailer class isn't found, include it
		if(!class_exists('PHPMailer'))
		{
			require dirname(__FILE__) . '/../3rdparty/PHPMailer.php';
		}

		// If the config wa not passed, set some defaults
		if(is_null($in_conf))
		{
			// If we're in cli mode
			if(isset($_SEVER['user']))
			{
				$sEmail	= $_SEVER['user'] . '@' . php_uname('n');
			}
			// Else http mode
			else
			{
				$sEmail	= $_SERVER['SERVER_ADMIN'];
			}

			$in_conf	= array(
				'host'			=> 'localhost',
				'from_email'	=> $sEmail
			);
		}

		// Setup the php mailer
		$oMail				= new PHPMailer();
		$oMail->From		= $in_conf['from_email'];
		$oMail->FromName	= isset($in_conf['from_name']) ? $in_conf['from_name'] : '';
		$oMail->Subject		= $in_subject;
		$oMail->MsgHTML($in_body);
		$oMail->setHost($in_conf['host']);
		$oMail->IsSendmail();

		// If the to param is an array
		if(is_array($in_to))
		{
			foreach($in_to as $sEmail)
			{
				$oMail->AddAddress($sEmail);
			}
		}
		// Else if it's a comma separated string
		else if(mb_strpos($in_to, ',') !== false)
		{
			foreach(mb_split('/,/', $in_to) as $sEmail)
			{
				$oMail->AddAddress($sEmail);
			}
		}
		// Else it's just one e-mail
		else
		{
			$oMail->AddAddress($in_to);
		}

		return $oMail->Send();
	}

	/**
	 * Validate Address
	 * Checks if an e-mail address is properly format, and optionally, if the domain has an MX record
	 * @name validateAddress
	 * @access public
	 * @static
	 * @param string $in_email			The address to validate
	 * @param bool $in_check_mx			Set to true to check for MX records on the domain
	 * @return bool
	 */
	public static function validateAddress(/*string*/ $in_email, /*bool*/$in_check_mx = false)
	{
		// Check the format is ok
		if(preg_match(self::REGEX_EMAIL, $in_email) !== 1)
		{
			return false;
		}

		// Check the domain has a valid MX record
		if($in_check_mx)
		{
			getmxrr($sDomain, $aDNS);

			if(count($aDNS) == 0)
			{
				return false;
			}
		}

		return true;
	}
}