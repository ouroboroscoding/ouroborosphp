<?php
/**
 * Represents a simple date of the format YYYY-MM-DD
 *
 * @author chris nasr
 * @copyright fuel for the fire
 * @package core
 * @version 0.1
 * @created 2011-04-13
 */

/**
 * Date class
 * @name Date
 * @package core
 */
class Date
{
	/**
	 * Year of the date
	 * @var string
	 * @access private
	 */
	private $sY;

	/**
	 * Month of the date
	 * @var string
	 * @access private
	 */
	private $sM;

	/**
	 * Day of the date
	 * @var string
	 * @access private
	 */
	private $sD;

	/**
	 * Constructor
	 * can take a variety of arguments:
	 * <pre>
	 * No arguments   => today's date
	 * One Date       => copy Date
	 * One int        => convert timestamp
	 * One string     => YYYY-MM-DD or YYYYMMDD
	 * Two strings    => Date string followed by date format
	 * Three strings  => Year followed by month followed by day
	 * </pre>
	 * @name Date
	 * @access public
	 * @return Date
	 */
	public function __construct()
	{
		// Check how many arguments we got
		$iArg	= func_num_args();

		switch($iArg)
		{
			// No arguments, set to today
			case 0:
				$this->sY	= date('Y');
				$this->sM	= date('m');
				$this->sD	= date('d');
				break;

			// One argument, could be another Date, a timestamp or a string Y-m-d
			case 1:
				$mDate	= func_get_arg(0);
				if($mDate instanceof Date)
				{
					$this->sY	= $mDate->sY;
					$this->sM	= $mDate->sM;
					$this->sD	= $mDate->sD;
				}
				else if(is_int($mDate))
				{
					$this->sY	= date('Y', $mDate);
					$this->sM	= date('m', $mDate);
					$this->sD	= date('d', $mDate);
				}
				else if(is_string($mDate))
				{
					if(strpos($mDate, '-') !== false)
					{
						list($sY, $sM, $sD)	= explode('-', $mDate);
					}
					else
					{
						$sY	= substr($mDate, 0, 4);
						$sM	= substr($mDate, 4, 2);
						$sD	= substr($mDate, 6, 2);
					}

					if(is_numeric($sY) && is_numeric($sM) && is_numeric($sD))
					{
						$iTime	= mktime(0, 0, 0, $sM, $sD, $sY);
						$this->sY	= date('Y', $iTime);
						$this->sM	= date('m', $iTime);
						$this->sD	= date('d', $iTime);
					}
				}
				else
				{
					$aBT	= debug_backtrace();
					trigger_error('Invalid argument for creating ' . __CLASS__ . ' in ' . $aBT[0]['file'] . ' on line ' . $aBT[0]['line'], E_USER_WARNING);
				}
				break;

			// Two arguments, date string and format string
			case 2:
				$aDate	= date_parse_from_format(func_get_arg(1), func_get_arg(0));
				$iTime	= mktime(0, 0, 0, $aDate['month'], $aDate['day'], $aDate['year']);
				$this->sY	= date('Y', $iTime);
				$this->sM	= date('m', $iTime);
				$this->sD	= date('d', $iTime);
				break;

			// Three arguments, year, month and day
			case 3:
				$mY	= func_get_arg(0);
				$mM	= func_get_arg(1);
				$mD	= func_get_arg(2);

				if(is_numeric($mY) && is_numeric($mM) && is_numeric($mD))
				{
					$iTime		= mktime(0, 0, 0, $mM, $mD, $mY);
					$this->sY	= date('Y', $iTime);
					$this->sM	= date('m', $iTime);
					$this->sD	= date('d', $iTime);
				}
				else
				{
					$aBT	= debug_backtrace();
					trigger_error('Invalid arguments for creating ' . __CLASS__ . ' in ' . $aBT[0]['file'] . ' on line ' . $aBT[0]['line'], E_USER_WARNING);
				}
				break;

			// wtfbbq?!
			default:
				$aBT	= debug_backtrace();
				trigger_error('Wrong number of arguments for creating ' . __CLASS__ . ' in ' . $aBT[0]['file'] . ' on line ' . $aBT[0]['line'], E_USER_WARNING);
				break;
		}
	}

	/**
	 * __call
	 * Allows getting year, month or day via y(), Y(), year(), Year(), YEAR(), etc...
	 * @name __call
	 * @access public
	 * @param string $in_name			Should start with y, m or d
	 * @param array $in_arguments		Should not be used
	 * @return string
	 */
	public function __call($in_name, array $in_arguments)
	{
		$sWhich	= strtolower($in_name{0});

		switch($sWhich)
		{
			case 'y':	return $this->sY;	break;
			case 'm':	return $this->sM;	break;
			case 'd':	return $this->sD;	break;
			default:
				$aBT	= debug_backtrace();
				trigger_error('Undefined method:  ' . __CLASS__ . '::' . $in_name . ' in ' . $aBT[0]['file'] . ' on line ' . $aBT[0]['line'], E_USER_ERROR);
		}
	}

	/**
	 * __toString
	 * Return string representation of class
	 * @name __toString
	 * @access public
	 * @return string
	 */
	public function __toString()
	{
		return $this->sY . '-' . $this->sM . '-' . $this->sD;
	}

	/**
	 * As Timestamp
	 * Return the date as a gmt timestamp
	 * @name asTimestamp
	 * @access public
	 * @return uint
	 */
	public function asTimestamp()
	{
		return mktime(0, 0, 0, $this->sM, $this->sD, $this->sY);
	}

	/**
	 * As String
	 * Return the date as a string Y-m-d
	 * @name asString
	 * @access public
	 * @param string $in_separator		Choose the separator, defaults to -
	 * @return string
	 */
	public function asString($in_separator = '-')
	{
		return $this->sY . $in_separator . $this->sM . $in_separator . $this->sD;
	}

	/**
	 * Decrement
	 * Decrement the date by day(s)
	 * @name decrement
	 * @access public
	 * @param int $in_by				Number of days to decrement by
	 * @return this
	 */
	public function decrement(/*int*/ $in_by = 1)
	{
		// Increment the day
		$this->sD	-= $in_by;

		// Let PHP figure out the rest
		$iTime		= mktime(0, 0, 0, $this->sM, $this->sD, $this->sY);

		// Store the new values
		$this->sY	= date('Y', $iTime);
		$this->sM	= date('m', $iTime);
		$this->sD	= date('d', $iTime);
	}

	/**
	 * Format
	 * Returns the date as a formatted string. Works exactly the same as PHP's strftime
	 * @name format
	 * @access public
	 * @param string $in_format			The format string (see PHP's strftime)
	 * @return string
	 * @see strftime
	 */
	public function format(/*string*/ $in_format)
	{
		return strftime($in_format, mktime(0, 0, 0, $this->sM, $this->sD, $this->sY));
	}

	/**
	 * Get Payment Period
	 * Get the payment period this date is in
	 * @name getPeriod
	 * @access public
	 * @return PaymentPeriod
	 */
	public function getPeriod()
	{
		return new PaymentPeriod($this->sY, (($this->sM * 2) - ($this->sD < 16 ? 1 : 0)));
	}

	/**
	 * Greater Than
	 * Is the date greater than another date
	 * @name greaterThan
	 * @access public
	 * @param Date $in_date				Date to compare to
	 * @return bool
	 */
	public function greaterThan(Date $in_date)
	{
		if($this->sY . $this->sM . $this->sD > $in_date->sY . $in_date->sM . $in_date->sD)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * In
	 * Is this date between two other dates?
	 * @name in
	 * @access public
	 * @param Date $in_start			The start Date
	 * @param Date $in_end				The end Date
	 * @param bool $in_inclusive		Are we checking inclusively?
	 * @return bool
	 */
	public function in(Date $in_start, Date $in_end, /*bool*/$in_inclusive = true)
	{
		// Inclusive?
		if($in_inclusive)
		{
			if(($this->sY . $this->sM . $this->sD) >= ($in_start->sY . $in_start->sM . $in_start->sD) &&
				($this->sY . $this->sM . $this->sD) <= ($in_end->sY . $in_end->sM . $in_end->sD))
			{
				return true;
			}
		}
		else
		{
			if(($this->sY . $this->sM . $this->sD) > ($in_start->sY . $in_start->sM . $in_start->sD) &&
				($this->sY . $this->sM . $this->sD) < ($in_end->sY . $in_end->sM . $in_end->sD))
			{
				return true;
			}
		}

		// Guess not
		return false;
	}

	/**
	 * Increment
	 * Increment the date by day(s)
	 * @name increment
	 * @access public
	 * @param int $in_by				Number of days to increment by
	 * @return this
	 */
	public function increment(/*int*/ $in_by = 1)
	{
		// Increment the day
		$this->sD	+= $in_by;

		// Let PHP figure out the rest
		$iTime		= mktime(0, 0, 0, $this->sM, $this->sD, $this->sY);

		// Store the new values
		$this->sY	= date('Y', $iTime);
		$this->sM	= date('m', $iTime);
		$this->sD	= date('d', $iTime);
	}

	/**
	 * Less Than
	 * Is the date lass than another date
	 * @name lessThan
	 * @access public
	 * @param Date $in_date				Date to compare to
	 * @return bool
	 */
	public function lessThan(Date $in_date)
	{
		if($this->sY . $this->sM . $this->sD < $in_date->sY . $in_date->sM . $in_date->sD)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
}