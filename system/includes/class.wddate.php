<?php

if(!defined('IS_64_BIT_SYSTEM'))
{
	if(1 << 32 === 1)
	{
		define('IS_64_BIT_SYSTEM', false);
	}
	else
	{
		define('IS_64_BIT_SYSTEM', true);
	}
}

/**
 * Needs the PHP-BCMath extension and PHP 5 >= 5.2.0
 *
 * @deprecated
 */
class WDDate
{
	/* ================================================================================= DATE CONSTANTS === */

	const MERIDIAN		= 'MERIDIAN';		// -	// AM or PM

	const SECOND		= 'SECOND';			// S	// 2 digits, 00-59
	const MINUTE		= 'MINUTE';			// I	// 2 digits, 00-59
	const HOUR			= 'HOUR';			// H	// 2 digits, 00-23

	const HOUR_MERIDIAN	= 'HOUR_MERIDIAN';	// -	// 2 digits, 01-12

	const WEEK			= 'WEEK';			// -	// The number of week of year, 1-53
	const WEEKDAY		= 'WEEKDAY';		// W	// The number of day of week, 1-7 (1 = monday, 7 = sunday), DAY_OF_WEEK wrapper

	const DAY			= 'DAY';			// D	// 2 digits, 01-31
	const DAY_OF_YEAR	= 'DAY_OF_YEAR';	// -	// The number of day of year, 1-366
	const DAY_OF_WEEK	= 'DAY_OF_WEEK';	// W	// The number of day of week, 1-7 (1 = monday, 7 = sunday), WEEKDAY wrapper

	const MONTH			= 'MONTH';			// M	// 2 digits, 01-12
	const MONTH_DAYS	= 'MONTH_DAYS';		// -	// The number of days of month, 28-31

	const QUARTER		= 'QUARTER';		// -	// 1 digit, 1-4

	const YEAR			= 'YEAR';			// Y	// 4 gidits, 1600-2400
	const YEAR_DAYS		= 'YEAR_DAYS';		// -	// The number of days of year, 365-366

	const TIMESTAMP		= 'TIMESTAMP';		// -	// The timestamp, integer

	const TIMES			= 'TIMES';			// -	// Default times, HH:MM:SS
	const DATES			= 'DATES';			// -	// Default dates, DD.MM.YYYY

	const LOCAL_DATES	= 'LOCAL_DATES';	// -	// The local date format 1970 - 2069

	const STRFTIME		= 'STRFTIME';		// -	// strftime format

	const DB_DATETIME	= 'DB_DATETIME';	// -	// DB datetime, YYYY-MM-DD HH:MM:SS
	const DB_TIMESTAMP	= 'DB_TIMESTAMP';	// -	// DB timestamp, YYYY-MM-DD HH:MM:SS
	const DB_TIME		= 'DB_TIME';		// -	// DB time, HH:MM:SS
	const DB_DATE		= 'DB_DATE';		// -	// DB date, YYYY-MM-DD

	const PERIOD_EQUAL				=  0;	// see self::comparePeriod() method
	const PERIOD_INNER				= -1;	// see self::comparePeriod() method
	const PERIOD_OUTER				=  1;	// see self::comparePeriod() method
	const PERIOD_INTERSECT_START	= -2;	// see self::comparePeriod() method
	const PERIOD_INTERSECT_END		=  2;	// see self::comparePeriod() method
	const PERIOD_CONTACT_START		= -3;	// see self::comparePeriod() method
	const PERIOD_CONTACT_END		=  3;	// see self::comparePeriod() method
	const PERIOD_BEFORE				= -4;	// see self::comparePeriod() method
	const PERIOD_AFTER				=  4;	// see self::comparePeriod() method

	/* ====================================================================================== VARIABLES === */

	/**
	 * The intern timestamp
	 * MUSS DER ERSTE WERT SEIN, damit man Objekte direkt miteinander vergleichen kann
	 * $oDate1 > $oDate2
	 *
	 * @var int
	 */
	protected $_iTS = null;

	
	/**
	 * The empty date flag
	 * 
	 * @var bool
	 */
	protected $_bEmpty = false;


	/**
	 * The locat GMT in seconds
	 *
	 * @var int
	 */
	protected $_iGMT = 0;


	/**
	 * The date and time parts
	 *
	 * @var array
	 */
	protected $_aParts = array();


	/**
	 * The DateTime object
	 * 
	 * @var object DateTime
	 */
	protected static $_oGMT;


	/**
	 * The local date format
	 * 
	 * @var array
	 */
	public static $aLocalFormat = array();


	/**
	 * The months table
	 *
	 * @var array
	 */
	private static $_aMonths = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	
	
	/**
	 * Scale 
	 *
	 * @var int
	 */
	protected static $_iScale = 0;


	/**
	 * The default shorts of date parts
	 *
	 * @var array
	 */
	private static $_aShorts = array(
		'S'	=> array(
			'len' => 2,
			'con' => 'SECOND'
		),
		'I'	=> array(
			'len' => 2,
			'con' => 'MINUTE'
		),
		'H'	=> array(
			'len' => 2,
			'con' => 'HOUR'
		),
		'W'	=> array(
			'len' => 1,
			'con' => 'WEEKDAY'
		),
		'D'	=> array(
			'len' => 2,
			'con' => 'DAY'
		),
		'M'	=> array(
			'len' => 2,
			'con' => 'MONTH'
		),
		'Q'	=> array(
			'len' => 1,
			'con' => 'QUARTER'
		),
		'Y'	=> array(
			'len' => 4,
			'con' => 'YEAR'
		)
	);


	/**
     * The years table for more calculation speed (1965 - 1600)
     * 
     * @var array
     */
	private static $_aYearsPrev = array(
		1600 => -11676096000,	1650 => -10098172800,	1700 => -8520336000,
		1750 => -6942499200,	1800 => -5364662400,	1850 => -3786825600,
		1900 => -2208988800,	1910 => -1893456000,	1920 => -1577923200,
		1930 => -1262304000,	1940 => -946771200,		1950 => -631152000,
		1955 => -473385600,		1960 => -315619200,		1965 => -157766400
	);


	/**
     * The years table for more calculation speed (1975 - 2400)
     * 
     * @var array
     */
	private static $_aYearsNext = array(
		1975 => 157766400,		1980 => 315532800,		1985 => 473385600,
		1990 => 631152000,		1995 => 788918400,		2000 => 946684800,
		2005 => 1104537600,		2010 => 1262304000,		2015 => 1420070400,
		2020 => 1577836800,		2025 => 1735689600,		2030 => 1893456000,
		2035 => 2051222400,		2040 => 2208988800,		2045 => 2366841600,
		2050 => 2524608000,		2060 => 2840140800,		2070 => 3155760000,
		2080 => 3471292800,		2090 => 3786912000,		2100 => 4102444800,
		2150 => 5680281600,		2200 => 7258118400,		2250 => 8835955200,
		2300 => 10413792000,	2350 => 11991628800,	2400 => 13569465600
	);

	/* ========================================================================================== MAGIC === */

	/**
	 * The constructor
	 *
	 * @deprecated
	 * @param mixed $mDate
	 * @param string $sPart
	 * @param string $sFormat
	 * @throws Exception
	 */
	public function __construct($mDate = null, $sPart = null, $sFormat = null)
	{
		global $system_data;

		if(is_null(self::$_oGMT))
		{
			self::$_oGMT = new DateTime(null, new DateTimeZone(date_default_timezone_get()));
		}

		// Set default local date format
		if(empty(self::$aLocalFormat))
		{
			self::_setLocalFormat();
		}

		if(is_string($sPart))
		{
			$mValue	= $mDate;
			$mDate	= null;

			if($sPart === self::DB_TIMESTAMP && is_numeric($mValue))
			{
				$sPart = self::TIMESTAMP;
			}
		}

		if(is_null($mDate))
		{
			$this->_iTS	= time();
		}
		else if(is_numeric($mDate))
		{
			$this->_iTS	= $mDate;
		}
		else if($mDate instanceof self)
		{
			$this->_iTS	= $mDate->get(self::TIMESTAMP);
		}
		else
		{
			if(\System::d('debugmode')) {
				throw new Exception('Invalid date part!');
			}
		}

		$this->_loadParts();

		if(is_string($sPart))
		{
			$this->set($mValue, $sPart, $sFormat);
		}
	}

	/* ========================================================================================= PUBLIC === */

	/**
	 * Add the part of date or time
	 *
	 * @param int $mValue
	 * @param string $sPart
	 * @return object
	 */
	public function add($mValue, $sPart)
	{
		global $system_data;

		self::checkInput($mValue, $sPart, false);

		if($this->_bEmpty)
		{
			return false;
		}

		$iOldGMT = $this->_iGMT;

		switch($sPart)
		{
			case self::SECOND:
			case self::MINUTE:
			case self::HOUR:
			case self::WEEK:
			case self::DAY:
			case self::MONTH:
			case self::QUARTER:
			case self::YEAR:
			{
				$this->_calculate((int)$mValue, $sPart);
				break;
			}
			default: 
				if(\System::d('debugmode'))
				{
					throw new Exception('Unknown part "' . $sPart . '" given!');
				}
		}

		$this->_correctDST($iOldGMT);

		return $this;
	}


	/**
	 * Compare date parts of this object and $mInput
	 * Gibt 0 zurück wenn beides gleich ist
	 * 
	 * @param mixed $mInput
	 * @param string $sPart
	 * @return int like strcmp()
	 */
	public function compare($mInput, $sPart = null)
	{
		if($this->_bEmpty)
		{
			return false;
		}

		if($mInput instanceof self) {
			$oTmp = $mInput;
		} else {
			$oTmp = new self($this);
			$oTmp->set($mInput, $sPart);
			
			if($oTmp->get($sPart) === false) {
				return false;
			}
		}

		if($sPart === null) {
			$sPart = self::DB_DATETIME;
		}

		switch($sPart)
		{
			case self::SECOND:
			case self::MINUTE:
			case self::HOUR:
			case self::HOUR_MERIDIAN:
			case self::WEEK:
			case self::WEEKDAY:
			case self::DAY:
			case self::MONTH:
			case self::MONTH_DAYS:
			case self::QUARTER:
			case self::YEAR:
			case self::YEAR_DAYS:
			case self::TIMESTAMP:
			case self::DAY_OF_YEAR:
			case self::DAY_OF_WEEK:
			case self::MERIDIAN:
			{
				$sPart1 = (string)$this->get($sPart);
				$sPart2 = (string)$oTmp->get($sPart);

				break;
			}
			case self::TIMES:
			case self::DB_TIME:
			{
				$sPart1 = (string)$this->get('HIS');
				$sPart2 = (string)$oTmp->get('HIS');

				break;
			}
			case self::DATES:
			case self::DB_DATE:
			{
				$sPart1 = (string)$this->get('YMD');
				$sPart2 = (string)$oTmp->get('YMD');

				break;
			}
			case self::LOCAL_DATES:
			{
				$aParts = explode(self::$aLocalFormat[3], $mInput);

				$iDay = $iMonth = $iYear = 0;

				foreach(self::$aLocalFormat as $iKey => $sPart)
				{
					if(strpos($sPart, 'D') !== false)
					{
						$iDay = $aParts[$iKey];
						continue;
					}
					if(strpos($sPart, 'M') !== false)
					{
						$iMonth = $aParts[$iKey];
						continue;
					}
					if(strpos($sPart, 'YYYY') !== false)
					{
						$iYear = $aParts[$iKey];
						continue;
					}
					else if(strpos($sPart, 'YY') !== false)
					{
						if((int)$sPart < 70)
						{
							$iYear = (int)$aParts[$iKey] + 2000;
						}
						else
						{
							$iYear = (int)$aParts[$iKey] + 1900;
						}
						continue;
					}
				}

				$sPart1 = (string)$this->get('YMD');
				$sPart2 = (string)$iYear . (string)$iMonth . (string)$iDay;

				break;
			}
			case self::DB_TIMESTAMP:
			case self::DB_DATETIME:
			{
				$sPart1 = (string)$this->get('YMDHIS');

				$sPart2 = (string)$oTmp->get('YMDHIS');

				break;
			}
		}
		
		$iCompare = strcmp((string)$sPart1, (string)$sPart2);
		
		if($iCompare < 0)
		{
			$iCompare = -1;
		}
		
		if($iCompare > 0)
		{
			$iCompare = 1;
		}

		return $iCompare;
	}


	/**
	 * Explode format to replace with strftime, replace %X by given format in $sX
	 * 
	 * @param string $sFormat
	 * @param string $sX
	 * @return array
	 */
	public static function createStrftimeParts($sFormat, $sX)
	{
		$aParts = array();

		$bContinueNext = false;

		$iLength = strlen($sFormat);

		for($i = 0; $i < $iLength; ++$i)
		{
			if($bContinueNext)
			{
				$bContinueNext = false;

				continue;
			}

			if(
				$sFormat[$i] === '%' &&
				$sFormat[$i - 1] !== '%'
			)
			{
				$sTemp = $sFormat[$i] . $sFormat[$i + 1];

				// Replace %X
				if($sTemp === '%X')
				{
					// Redesign the format
					$sFormat = substr($sFormat, 0, $i) . $sX . substr($sFormat, $i + 2);

					// Go one sign back
					--$i;

					// Reset the length
					$iLength = strlen($sFormat);

					continue;
				}

				$aParts[$i] = $sTemp;

				$bContinueNext = true;

				continue;
			}

			for($n = $i; $n < $iLength; ++$n)
			{
				$aParts[$i] .= $sFormat[$n];

				if($sFormat[$n + 1] === '%')
				{
					$i = $n;

					break;
				}
			}
		}

		return $aParts;
	}


	/**
	 * Return a part of date and/or time
	 *
	 * @param string $sPart
	 * @return mixed
	 */
	public function get($sPart, $sFormat = null)
	{
		global $system_data;

		if($this->_bEmpty)
		{
			return false;
		}

		if(is_string($sPart) && strlen($sPart) === 1)
		{
			if(!isset(self::$_aShorts[$sPart]))
			{
				if(\System::d('debugmode'))
				{
					throw new Exception('Unknown date part!');
				}
			}

			return $this->get(self::$_aShorts[$sPart]['con']);
		}

		if(isset($this->_aParts[$sPart]))
		{
			return $this->_aParts[$sPart];
		}

		switch($sPart)
		{
			case self::WEEKDAY:
			{
				return $this->_aParts[self::DAY_OF_WEEK];
			}
			case self::TIMES:
			case self::DB_TIME:
				return 
					$this->_aParts[self::HOUR] . ':' .
					$this->_aParts[self::MINUTE] . ':' .
					$this->_aParts[self::SECOND];
			case self::DB_DATE:
				return 
					$this->_aParts[self::YEAR] . '-' .
					$this->_aParts[self::MONTH] . '-' .
					$this->_aParts[self::DAY];
			case self::DATES:
				return 
					$this->_aParts[self::DAY] . '.' .
					$this->_aParts[self::MONTH] . '.' .
					$this->_aParts[self::YEAR];
			case self::DB_DATETIME:
				return 
					$this->_aParts[self::YEAR] . '-' .
					$this->_aParts[self::MONTH] . '-' .
					$this->_aParts[self::DAY] . ' ' .
					$this->_aParts[self::HOUR] . ':' .
					$this->_aParts[self::MINUTE] . ':' .
					$this->_aParts[self::SECOND];
			case self::DB_TIMESTAMP:
			{
				if($this->_aParts[self::YEAR] < 1970 || $this->_aParts[self::YEAR] > 2037)
				{
					return '0000-00-00 00:00:00';
				}
				else
				{
					return 
						$this->_aParts[self::YEAR] . '-' .
						$this->_aParts[self::MONTH] . '-' .
						$this->_aParts[self::DAY] . ' ' .
						$this->_aParts[self::HOUR] . ':' .
						$this->_aParts[self::MINUTE] . ':' .
						$this->_aParts[self::SECOND];
				}
			}
			case self::STRFTIME:
			{
				if(is_null($sFormat)) {
					$sFormat = '%x';
				}

				$iTempYear = $this->_aParts[self::YEAR];
				
				$bIsLeapYear = self::_isLeapYear($iTempYear);
				
				// TODO! Wichtig, in manchen Ländern wurde/wird ab und zu die Sommer/Winterzeit abgeschaft!
				// die strftime achtet darauf aber nicht!
				$bBackReplace = false;
				if(
					$iTempYear < 1970 ||
					$iTempYear > 2037
				) {
					if($bIsLeapYear){
						$iReplacementYear = 1992;
					} else {
						$iReplacementYear = 1990;
					}
					
					$iCheckPart = substr($iReplacementYear, 2);
					if(strpos($iTempYear, $iCheckPart) !== false) {
						$iReplacementYear += 4;
					}
					
					$this->set($iReplacementYear, self::YEAR);
					$bBackReplace = true;
				}
				
				$iTimestamp = $this->get(self::TIMESTAMP);
				$sDateTime = strftime($sFormat, $iTimestamp);
				if($bBackReplace) {
					$sDateTime = str_replace($this->_aParts[self::YEAR], $iTempYear, $sDateTime);
					$sDateTime = str_replace(substr($this->_aParts[self::YEAR], 2), substr($iTempYear, 2), $sDateTime);

					$this->set($iTempYear, self::YEAR);
				}
				
				return $sDateTime;

				break;
			}
			case self::LOCAL_DATES:
			{
				$aDateParts	= self::$aLocalFormat;
				$sSpacer	= $aDateParts[3];

				unset($aDateParts[3]);

				$aReturn = array();

				foreach($aDateParts as $sDatePart)
				{
					// Day
					if(strpos($sDatePart, 'DD') !== false)
					{
						$aReturn[] = $this->_aParts[self::DAY];
						continue;
					}
					else if(strpos($sDatePart, 'D') !== false)
					{
						$aReturn[] = (int)$this->_aParts[self::DAY];
						continue;
					}

					// Month
					if(strpos($sDatePart, 'MM') !== false)
					{
						$aReturn[] = $this->_aParts[self::MONTH];
						continue;
					}
					else if(strpos($sDatePart, 'M') !== false)
					{
						$aReturn[] = (int)$this->_aParts[self::MONTH];
						continue;
					}

					// Year
					if(strpos($sDatePart, 'YYYY') !== false)
					{
						$aReturn[] = $this->_aParts[self::YEAR];
						continue;
					}
					else if(strpos($sDatePart, 'YY') !== false)
					{
						$aReturn[] = substr($this->_aParts[self::YEAR], 2);
						continue;
					}
				}

				return implode($sSpacer, $aReturn);
			}
			default:
			{
				$aStringParts = str_split($sPart);

				foreach($aStringParts as $mValue)
				{
					if(isset(self::$_aShorts[$mValue]))
					{
						$mReplace = $this->get(self::$_aShorts[$mValue]['con']);
		
						$sPart = str_replace($mValue, $mReplace, $sPart);
					}
				}
			}
		}

		return $sPart;
	}


	/**
	 * Get difference in years of two dates
	 * @param object $oDate
	 * @return int
	 */
	public function getAge($oDate = null)
	{
		global $system_data;

		if(empty($oDate))
		{
			$oDate = new self();
		}
		else if(!$oDate instanceof self)
		{
			if(\System::d('debugmode'))
			{
				throw new Exception('Given variable is not an instance of Date.');
			}
		}

		$iAge = ($oDate->get(self::YEAR) - $this->get(self::YEAR));

		if(((int)$oDate->get(self::MONTH) . $oDate->get(self::DAY)) < ((int)$this->get(self::MONTH) . $this->get(self::DAY)))
		{
			return --$iAge;
		}

		return $iAge;
	}


	/**
	 * Return the limits of current day
	 * 
	 * @return array
	 */
	public function getDayLimits()
	{
		if($this->_bEmpty)
		{
			return false;
		}

		$aReturn = array();

		$oDate = new self($this);
		$oDate->set('00:00:00', self::TIMES);

		$aReturn['start'] = $oDate->get(self::TIMESTAMP);

		$oDate->set('23:59:59', self::TIMES);

		$aReturn['end'] = $oDate->get(self::TIMESTAMP);

		return $aReturn;
	}


	/**
	 * Calculate the difference of two dates
	 * 
	 * @param string $sPart
	 * @param object $oDate
	 * @return int
	 */
	public function getDiff($sUnit, $mDate = null, $sPart = null) {
		global $system_data;
		
		$oTemp = new self($mDate, $sPart);

		if(
			$this->isEmpty() ||
			$oTemp->isEmpty()
		) {
			return false;
		}

		switch($sUnit)
		{
			case self::SECOND:
			case self::TIMESTAMP:
			{
				return bcsub($oTemp->get(self::TIMESTAMP), $this->get(self::TIMESTAMP), self::$_iScale);
			}
			case self::MINUTE:
			{
				return bcdiv(bcsub($oTemp->get(self::TIMESTAMP), $this->get(self::TIMESTAMP), self::$_iScale), 60, self::$_iScale);
			}
			case self::HOUR:
			case self::DAY:
			case self::WEEK:
			case self::MONTH:
			case self::QUARTER:
			case self::YEAR:
			{
				$iDiff = 0;

				if($this->get(self::TIMESTAMP) < $oTemp->get(self::TIMESTAMP))
				{
					while($this->get(self::TIMESTAMP) < $oTemp->get(self::TIMESTAMP))
					{
						$oTemp->sub(1, $sUnit);

						if($this->get(self::TIMESTAMP) <= $oTemp->get(self::TIMESTAMP))
						{
							--$iDiff;
						}
					}
				}
				else if($this->get(self::TIMESTAMP) > $oTemp->get(self::TIMESTAMP))
				{
					while($this->get(self::TIMESTAMP) > $oTemp->get(self::TIMESTAMP))
					{
						$oTemp->add(1, $sUnit);

						if($this->get(self::TIMESTAMP) >= $oTemp->get(self::TIMESTAMP))
						{
							++$iDiff;
						}
					}
				}

				return $iDiff;
			}
			case self::WEEKDAY:
			case self::DAY_OF_WEEK:
			case self::MONTH_DAYS:
			case self::YEAR_DAYS:
			{
				return bcsub($oTemp->get($sUnit), $this->get($sUnit), self::$_iScale);
			}
			default: 
				if(\System::d('debugmode')) {
					throw new Exception('Unknown part "' . $sPart . '" given!');
				}
		}
	}


	/**
	 * Return the start and end timestamps of every day of current month
	 * 
	 * @param string $sByKey
	 * @return array
	 */
	public function getMonthDays($sByKey = null)
	{
		if($this->_bEmpty)
		{
			return false;
		}

		$aReturn = array();

		$oDate = new self($this);

		for($i = 1; $i <= $oDate->get(self::MONTH_DAYS); ++$i)
		{
			$aDay['start'] = $oDate
				->set($i, self::DAY)
				->set('00:00:00', self::TIMES)
				->get(self::TIMESTAMP);

			$aDay['end'] = $oDate
				->set('23:59:59', self::TIMES)
				->get(self::TIMESTAMP);

			if(!is_string($sByKey))
			{
				$aReturn[] = $aDay;
			}
			else
			{
				$aReturn[$oDate->get($sByKey)] = $aDay;
			}
		}

		return $aReturn;
	}


	/**
	 * Return the limits of current month
	 * 
	 * @return array
	 */
	public function getMonthLimits()
	{
		if($this->_bEmpty)
		{
			return false;
		}

		$aReturn = array();

		$oDate = new self($this);
		$oDate->set(1, self::DAY);
		$oDate->set('00:00:00', self::TIMES);

		$aReturn['start'] = $oDate->get(self::TIMESTAMP);

		$oDate->set($this->_aParts['MONTH_DAYS'], self::DAY);
		$oDate->set('23:59:59', self::TIMES);

		$aReturn['end'] = $oDate->get(self::TIMESTAMP);

		return $aReturn;
	}


	/**
	 * Return the limits of current quarter
	 * 
	 * @return array
	 */
	public function getQuarterLimits()
	{
		if($this->_bEmpty)
		{
			return false;
		}

		$aReturn = array();

		$oDate = new self($this);
		$oDate->set(($this->get(self::QUARTER) * 3 - 2), self::MONTH);
		$oDate->set(1, self::DAY);
		$oDate->set('00:00:00', self::TIMES);

		$aReturn['start'] = $oDate->get(self::TIMESTAMP);

		$oDate->set(($this->get(self::QUARTER) * 3), self::MONTH);
		$oDate->set($oDate->get(self::MONTH_DAYS), self::DAY);
		$oDate->set('23:59:59', self::TIMES);

		$aReturn['end'] = $oDate->get(self::TIMESTAMP);

		return $aReturn;
	}


	/**
	 * Return the start and end timestamps of every day of current week
	 * 
	 * @param string $sByKey
	 * @return array
	 */
	public function getWeekDays($sByKey = null)
	{
		if($this->_bEmpty)
		{
			return false;
		}

		$aReturn = array();

		$oDate = new self($this);

		for($i = 1; $i <= 7; ++$i)
		{
			$aDay['start'] = $oDate
				->set($i, self::WEEKDAY)
				->set('00:00:00', self::TIMES)
				->get(self::TIMESTAMP);

			$aDay['end'] = $oDate
				->set('23:59:59', self::TIMES)
				->get(self::TIMESTAMP);

			if(!is_string($sByKey))
			{
				$aReturn[] = $aDay;
			}
			else
			{
				$aReturn[$oDate->get($sByKey)] = $aDay;
			}
		}

		return $aReturn;
	}


	/**
	 * Return the limits of current week
	 * 
	 * @return array
	 */
	public function getWeekLimits()
	{
		if($this->_bEmpty)
		{
			return false;
		}

		$aReturn = array();

		$oDate = new self($this);
		$oDate->set(1, self::WEEKDAY);
		$oDate->set('00:00:00', self::TIMES);

		$aReturn['start'] = $oDate->get(self::TIMESTAMP);

		$oDate->set(7, self::WEEKDAY);
		$oDate->set('23:59:59', self::TIMES);

		$aReturn['end'] = $oDate->get(self::TIMESTAMP);

		return $aReturn;
	}


	/**
	 * Return the limits of current year
	 * 
	 * @return array
	 */
	public function getYearLimits()
	{
		if($this->_bEmpty)
		{
			return false;
		}

		$aReturn = array();

		$oDate = new self($this);
		$oDate->set(1, self::DAY);
		$oDate->set(1, self::MONTH);
		$oDate->set('00:00:00', self::TIMES);

		$aReturn['start'] = $oDate->get(self::TIMESTAMP);

		$oDate->set(31, self::DAY);
		$oDate->set(12, self::MONTH);
		$oDate->set('23:59:59', self::TIMES);

		$aReturn['end'] = $oDate->get(self::TIMESTAMP);

		return $aReturn;
	}


	/**
	 * Compare the equivalence of date or time parts
	 * 
	 * @param mixed $mInput
	 * @param string $sPart
	 * @return bool
	 */
	public function is($mInput, $sPart)
	{
		if($this->_bEmpty)
		{
			return false;
		}

		if($this->compare($mInput, $sPart) === 0)
		{
			return true;
		}

		return false;
	}

	/**
	 * Gibt true zurück wenn kein korrekter Zeitpunkt gesetzt werden konnte
	 * @return boolean
	 */
	public function isEmpty() {
		return $this->_bEmpty;
	}
			
	/**
	 * Compare the date/time part from this object with two other date/time parts
	 *
	 * @param string $sPart
	 * @param mixed $mDate1
	 * @param mixed $mDate2
	 * @return bool
	 */
	public function isBetween($sPart, $mDate1, $mDate2)
	{
		if(is_string($sPart) && strlen($sPart) === 1)
		{
			if(!isset(self::$_aShorts[$sPart]))
			{
				throw new Exception('Unknown date part!');
			}

			return $this->isBetween(self::$_aShorts[$sPart]['con'], $mDate1, $mDate2);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		if(!($mDate1 instanceof self))
		{
			$mTemp = new self();

			$mDate1 = $mTemp->set($mDate1, $sPart);
		}
		if(!($mDate2 instanceof self))
		{
			$mTemp = new self();

			$mDate2 = $mTemp->set($mDate2, $sPart);
		}
        
        if(
           $mDate1 === false ||
           $mDate2 === false
        ){
            return false;
        }
        
		// Empty check of both dates on DB date parts
		if(!$mDate1->get(self::DAY) || !$mDate2->get(self::DAY))
		{
			throw new Exception('One or both of given dates is/are empty! (1: "' . $mDate1 . '", 2: "' . $mDate2 . '")');
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		switch($sPart)
		{
			case self::SECOND:
			case self::MINUTE:
			case self::HOUR:
			case self::HOUR_MERIDIAN:
			case self::DAY:
			case self::DAY_OF_WEEK:
			case self::DAY_OF_YEAR:
			case self::WEEK:
			case self::WEEKDAY:
			case self::MONTH:
			case self::QUARTER:
			case self::YEAR:
			{
				$i1 = $mDate1->get($sPart);
				$i2 = $mDate2->get($sPart);
				$iT = $this->get($sPart);

				break;
			}
			case self::TIMES:
			case self::DB_TIME:
			{
				$i1 = str_replace(':', '', $mDate1->get(self::TIMES));
				$i2 = str_replace(':', '', $mDate2->get(self::TIMES));
				$iT = str_replace(':', '', $this->get(self::TIMES));

				break;
			}
			case self::DATES:
			case self::DB_DATE:
			{
				$i1 = str_replace('-', '', $mDate1->get(self::DB_DATE));
				$i2 = str_replace('-', '', $mDate2->get(self::DB_DATE));
				$iT = str_replace('-', '', $this->get(self::DB_DATE));

				break;
			}
			case self::TIMESTAMP:
			case self::DB_DATETIME:
			case self::DB_TIMESTAMP:
			{
				$i1 = $mDate1->get(self::TIMESTAMP);
				$i2 = $mDate2->get(self::TIMESTAMP);
				$iT = $this->get(self::TIMESTAMP);

				break;
			}
			default:
				throw new Exception('Unsupported part "' . $sPart . '" given!');
		}

		if(IS_64_BIT_SYSTEM)
		{
			if(
				($iT < $i1 && $i2 < $iT) ||
				($iT > $i1 && $i2 > $iT) ||
				($iT === $i1 || $iT === $i2)
			)
			{
				return true;
			}
		}
		else
		{
			if(
				(bccomp($i1, $iT, self::$_iScale) === 1 && bccomp($iT, $i2, self::$_iScale) === 1) ||
				(bccomp($iT, $i1, self::$_iScale) === 1 && bccomp($i2, $iT, self::$_iScale) === 1) ||
				(bccomp($iT, $i1, self::$_iScale) === 0 || bccomp($iT, $i2, self::$_iScale) === 0)
			)
			{
				return true;
			}
		}

		return false;
	}


	public static function checkInput($mValue, $sPart, $bCheckDate=true, $sFormat = null) {
		global $system_data;

		if(
			!is_scalar($mValue) ||
			(
				$bCheckDate &&
				!self::isDate($mValue, $sPart, $sFormat)
			)
		) {
			if(\System::d('debugmode')) {
				throw new Exception('Invalid value of part "' . $sPart . '" given! ('.$mValue.')');
			}
		}

	}


	/**
	 * Compare the intersection of two periods
	 * 
	 * @param object $oDateStart1
	 * @param object $oDateEnd1
	 * @param object $oDateStart2
	 * @param object $oDateEnd2
	 * @return int
	 */
	public static function comparePeriod($oDateStart1, $oDateEnd1, $oDateStart2, $oDateEnd2)
	{
		global $system_data;

		$iStart1	= $oDateStart1->get(WDDate::TIMESTAMP);
		$iEnd1		= $oDateEnd1->get(WDDate::TIMESTAMP);
		$iStart2	= $oDateStart2->get(WDDate::TIMESTAMP);
		$iEnd2		= $oDateEnd2->get(WDDate::TIMESTAMP);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Check errors

		if(
			bccomp($iStart1, $iEnd1, self::$_iScale) === 1 ||
			bccomp($iStart2, $iEnd2, self::$_iScale) === 1
		)
		{
			if(\System::d('debugmode'))
			{
				throw new Exception('Left operand (start) cannot be larger than right operand (end)!');
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		if(
			bccomp($iStart1, $iStart2, self::$_iScale) === 0 &&
			bccomp($iEnd1, $iEnd2, self::$_iScale) === 0
		)
		{
			/*
				---|<--------->|--- 1
				---|<--------->|--- 2
			*/
			return self::PERIOD_EQUAL;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		if(
			(
				bccomp($iStart1, $iStart2, self::$_iScale) === -1 &&
				bccomp($iEnd1, $iEnd2, self::$_iScale) === -1 &&
				bccomp($iStart2, $iEnd1, self::$_iScale) === -1
			) ||
			(
				bccomp($iEnd1, $iStart2, self::$_iScale) === 0 &&
				bccomp($iEnd1, $iEnd2, self::$_iScale) === -1
			)
		)
		{
			/*
				-|<--------->|----- 1
				-----|<--------->|- 2

				OR

				-|<----->|--------- 1
				         |
				---------|<----->|- 2
			*/
			return self::PERIOD_INTERSECT_END;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		if(
			(
				bccomp($iStart1, $iStart2, self::$_iScale) === 1 &&
				bccomp($iEnd1, $iEnd2, self::$_iScale) === 1 &&
				bccomp($iStart1, $iEnd2, self::$_iScale) === -1 
			) ||
			(
				bccomp($iStart1, $iStart2, self::$_iScale) === 1 &&
				bccomp($iStart1, $iEnd2, self::$_iScale) === 0
			)
		)
		{
			/*
				-----|<--------->|- 1
				-|<--------->|----- 2

				OR

				---------|<----->|- 1
				         |
				-|<----->|--------- 2
			*/
			return self::PERIOD_INTERSECT_START;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		if(bccomp(bcadd($iEnd1, 1, self::$_iScale), $iStart2, self::$_iScale) === 0)
		{
			/*
				---|<--->|--------- 1
				         |
				 		  |
				----------|<--->|--- 2
			*/
			return self::PERIOD_CONTACT_END;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		if(bccomp(bcadd($iEnd2, 1, self::$_iScale), $iStart1, self::$_iScale) === 0)
		{
			/*
				----------|<--->|--- 1
				          |
				         |
				---|<--->|--------- 2
			*/
			return self::PERIOD_CONTACT_START;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		if(bccomp($iEnd1, $iStart2, self::$_iScale) === -1)
		{
			/*
				-|<--->|----------- 1
				-----------|<--->|- 2
			*/
			return self::PERIOD_AFTER;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		if(bccomp($iStart1, $iEnd2, self::$_iScale) === 1)
		{
			/*
				-----------|<--->|- 1
				-|<--->|----------- 2
			*/
			return self::PERIOD_BEFORE;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		if(
			(
				bccomp($iStart1, $iStart2, self::$_iScale) === -1 &&
				bccomp($iEnd1, $iEnd2, self::$_iScale) === 1
			) ||
			(
				bccomp($iStart1, $iStart2, self::$_iScale) === -1 &&
				bccomp($iEnd1, $iEnd2, self::$_iScale) === 0
			) ||
			(
				bccomp($iStart1, $iStart2, self::$_iScale) === 0 &&
				bccomp($iEnd1, $iEnd2, self::$_iScale) === 1
			)
		)
		{
			/*
				---|<--------->|--- 1
				------|<--->|------ 2

			    OR

				---|<--------->|------ 1
				-------|<----->|--- 2

				OR

				---|<--------->|------ 1
				---|<------>|--- 2
			*/
			return self::PERIOD_INNER;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		if(
			(
				bccomp($iStart1, $iStart2, self::$_iScale) === 1 &&
				bccomp($iEnd1, $iEnd2, self::$_iScale) === -1
			) ||
			(
				bccomp($iStart1, $iStart2, self::$_iScale) === 0 &&
				bccomp($iEnd1, $iEnd2, self::$_iScale) === -1
			) ||
			(
				bccomp($iStart1, $iStart2, self::$_iScale) === 1 &&
				bccomp($iEnd1, $iEnd2, self::$_iScale) === 0
			)
		)
		{
			/*
				------|<--->|------ 1
				---|<--------->|--- 2

				OR

				---|<--->|------ 1
				---|<--------->|--- 2

				OR

				---------|<--->|------ 1
				---|<--------->|--- 2
			*/
			return self::PERIOD_OUTER;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		return false;
	}

	/**
	 * Validate a date and/or time parts
	 *
	 * @deprecated
	 * @see \Core\Helper\DateTime::isDate()
	 *
	 * @param mixed	$mInput
	 * @param string $sFormat
	 * @return bool
	 */
	public static function isDate($mInput, $sPart, $sFormat = null)
	{
		if(!is_string($mInput) && !is_numeric($mInput))
		{
			return false;
		}

		$sTime = '(([0-1][0-9])|(2[0-3])):([0-5][0-9]):([0-5][0-9])';
		$sDate = '([0-9]{4})-([0-9]{2})-([0-9]{2})';

		switch($sPart)
		{
			case self::SECOND:
			case self::MINUTE:	return ($mInput < 0		|| $mInput > 59)	? false : true;
			case self::HOUR:	return ($mInput < 0		|| $mInput > 23)	? false : true;
			case self::WEEKDAY:	return ($mInput < 1		|| $mInput > 7)		? false : true;
			case self::WEEK:	return ($mInput < 1		|| $mInput > 53)	? false : true;
			case self::DAY:		return ($mInput < 1		|| $mInput > 31)	? false : true;
			case self::MONTH:	return ($mInput < 1		|| $mInput > 12)	? false : true;
			case self::QUARTER:	return ($mInput < 1		|| $mInput > 4)		? false : true;
			case self::YEAR:	return ($mInput < 1600	|| $mInput > 2400)	? false : true;
			case self::TIMESTAMP:
			{
				if(!is_numeric($mInput))
				{
					return false;
				}

				$iMax = self::$_aYearsNext[2400];
				$iMin = self::$_aYearsPrev[1600];

				if(IS_64_BIT_SYSTEM)
				{
					if($mInput < $iMin || $iMax < $mInput || $iMax === $mInput)
					{
						return false;
					}
				}
				else
				{
					if(
						bccomp($mInput, $iMin, self::$_iScale) === -1 ||	// $mValue is lower like $iMin
						bccomp($iMax, $mInput, self::$_iScale) === -1 ||	// $iMax is lower like $mValue
						bccomp($iMax, $mInput, self::$_iScale) === 0		// $iMax is like $mValue
					)
					{
						return false;
					}
				}

				return true;
			}
			case self::TIMES:
			case self::DB_TIME:
			{
				return (bool)preg_match("/^" . $sTime . "$/", $mInput);
			}
			case self::DATES:
			{
				// 01.01.1960 - 31.12.2399
				if(preg_match("/^((0[1-9])|([1-3][0-9])).((0[1-9])|(1[0-2])).((1[6-9][0-9]{2})|(2[0-3][0-9]{2}))$/", $mInput))
				{
					return self::_isValidDate(explode('.', $mInput));
				}

				return false;
			}
			case self::DB_DATETIME:
			case self::DB_TIMESTAMP:
			{
				if(preg_match("/^" . $sDate . " " . $sTime . "$/", $mInput))
				{
					$aTemp = explode(' ', $mInput);

					if($aTemp[0] === '0000-00-00')
					{
						return true;
					}

					$mTemp = explode('-', $aTemp[0]);

					if($sPart === self::DB_TIMESTAMP)
					{
						if($mTemp[0] < 1970 || $mTemp[0] > 2037)
						{
							return false;
						}
					}

					$mTemp = array_reverse($mTemp);
					$mTemp = implode('.', $mTemp);

					return self::isDate($mTemp, self::DATES);
				}

				return false;
			}
			case self::DB_DATE:
			{
				if($mInput === '0000-00-00')
				{
					return true;
				}

				if(preg_match("/^" . $sDate . "$/", $mInput))
				{
					$mTemp = explode('-', $mInput);
					$mTemp = array_reverse($mTemp);
					$mTemp = implode('.', $mTemp);

					return self::isDate($mTemp, self::DATES);
				}

				return false;
			}
			case self::STRFTIME:
			{
				$aBack = strptime($mInput, $sFormat);

				if($aBack === false) {
					return false;
				} else {

					$mCheck1 = true;
					$mCheck2 = true;
					if($aBack['tm_mday'] > 0) {
						$mTemp = sprintf('%02d.%02d.%04d',  $aBack['tm_mday'], ($aBack['tm_mon'] + 1), ($aBack['tm_year'] + 1900));
						$mCheck1 = self::isDate($mTemp, self::DATES);
					} else {
						$mCheck1 = self::isDate(($aBack['tm_mon'] + 1), self::MONTH);
						$mCheck2 = self::isDate(($aBack['tm_year'] + 1900), self::YEAR);
					}

					$mCheck3 = self::isDate($aBack['tm_hour'], self::HOUR);
					$mCheck4 = self::isDate($aBack['tm_min'], self::MINUTE);
					$mCheck5 = self::isDate($aBack['tm_sec'], self::SECOND);

					if($mCheck1 && $mCheck2 && $mCheck3 && $mCheck4 && $mCheck5) {
						return true;
					} else {
						return false;
					}

				}

				break;
			}
			case self::LOCAL_DATES:
			{
				// Set default local date format
				if(empty(self::$aLocalFormat))
				{
					self::_setLocalFormat();
				}

				$aParts = explode(self::$aLocalFormat[3], $mInput);

				$iDay = $iMonth = $iYear = 0;

				foreach(self::$aLocalFormat as $iKey => $sPart)
				{
					if(strpos($sPart, 'D') !== false)
					{
						$iDay = $aParts[$iKey];
						continue;
					}
					if(strpos($sPart, 'M') !== false)
					{
						$iMonth = $aParts[$iKey];
						continue;
					}
					if(strpos($sPart, 'YYYY') !== false)
					{
						$iYear = $aParts[$iKey];
						continue;
					}
					else if(strpos($sPart, 'YY') !== false)
					{
						if((int)$sPart < 69)
						{
							$iYear = (int)$aParts[$iKey] + 2000;
						}
						else
						{
							$iYear = (int)$aParts[$iKey] + 1900;
						}
						continue;
					}
				}

				return self::isDate($iDay . '.' . $iMonth . '.' . $iYear, self::DATES);
			}
			default:
			{
				return self::_simpleCheck($mInput, $sPart);
			}
		}
	}


	/**
	 * Create a timestamp
	 * 
	 * @param int $iHour
	 * @param int $iMinute
	 * @param int $iSecond
	 * @param int $iMonth
	 * @param int $iDay
	 * @param int $iYear
	 * @return int
	 */
	public function makeTS($iHour, $iMinute, $iSecond, $iMonth, $iDay, $iYear)
	{
		return bcadd($this->_mktime($iHour, $iMinute, $iSecond, $iMonth, $iDay, $iYear), $this->_iGMT, self::$_iScale);
	}


	/**
	 * Set a part of date and/or time
	 *
	 * @param mixed $mValue
	 * @param string $sPart
	 */
	public function set($mValue, $sPart, $sFormat = null)
	{
		global $system_data;

		self::checkInput($mValue, $sPart, true, $sFormat);

		$iOldGMT = $this->_iGMT;

		if(is_string($sPart) && strlen($sPart) === 1)
		{
			if(!array_key_exists($sPart, self::$_aShorts))
			{
				if(\System::d('debugmode'))
				{
					throw new Exception('Unknown date part!');
				}
			}

			return $this->set($mValue, self::$_aShorts[$sPart]['con']);
		}

		switch($sPart)
		{
			case self::SECOND:
			case self::MINUTE:
			case self::HOUR:
			case self::WEEK:
			case self::WEEKDAY:
			case self::DAY:
			case self::MONTH:
			case self::QUARTER:
			case self::YEAR:
			case self::DAY_OF_YEAR:
			case self::DAY_OF_WEEK:	$mValue = sprintf('%d', intval($mValue)); break;
			case self::TIMESTAMP:	$mValue = sprintf('%d', doubleval($mValue)); break;
			default:				$mValue = strval($mValue); break;
		}

		switch($sPart)
		{
			case self::SECOND:		$iDiv = $this->_aParts[$sPart] - $mValue;				break;
			case self::MINUTE:		$iDiv = ($this->_aParts[$sPart] - $mValue) * 60;		break;
			case self::HOUR:		$iDiv = ($this->_aParts[$sPart] - $mValue) * 3600;		break;
			case self::WEEKDAY:		$sPart = self::DAY_OF_WEEK;
			case self::DAY_OF_WEEK:
			case self::DAY_OF_YEAR:
			case self::DAY:			$iDiv = ($this->_aParts[$sPart] - $mValue) * 86400;		break;
			case self::WEEK:		$iDiv = ($this->_aParts[$sPart] - $mValue) * 604800;	break;
			case self::TIMESTAMP:
			{
				$this->_iTS = $mValue;

				$this->_bEmpty = false;

				return $this->_loadParts();
			}
			case self::QUARTER:
			{
				if($mValue > $this->_aParts[$sPart])
				{
					return $this->add(($mValue - $this->_aParts[$sPart]), self::QUARTER);
				}
				else
				{
					return $this->sub(($this->_aParts[$sPart] - $mValue), self::QUARTER);
				}

				break;
			}
			case self::MONTH:
			{
				if((int)$mValue === 2)
				{
					if($this->_aParts[self::DAY] > 29 && self::_isLeapYear($this->_aParts[self::YEAR]))
					{
						$this->_aParts[self::DAY] = 29;
					}
					else if($this->_aParts[self::DAY] > 28 && !self::_isLeapYear($this->_aParts[self::YEAR]))
					{
						$this->_aParts[self::DAY] = 28;
					}
				}

				$this->_iTS = $this->_mktime(
					$this->_aParts[self::HOUR],
					$this->_aParts[self::MINUTE],
					$this->_aParts[self::SECOND],
					$mValue,
					$this->_aParts[self::DAY],
					$this->_aParts[self::YEAR]
				);

				break;
			}
			case self::YEAR:
			{
				if((int)$this->_aParts[self::MONTH] === 2)
				{
					if($this->_aParts[self::DAY] > 29 && self::_isLeapYear($mValue))
					{
						$this->_aParts[self::DAY] = 29;
					}
					else if($this->_aParts[self::DAY] > 28 && !self::_isLeapYear($mValue))
					{
						$this->_aParts[self::DAY] = 28;
					}
				}

				$this->_iTS = $this->_mktime(
					$this->_aParts[self::HOUR],
					$this->_aParts[self::MINUTE],
					$this->_aParts[self::SECOND],
					$this->_aParts[self::MONTH],
					$this->_aParts[self::DAY],
					$mValue
				);

				if($mValue >= 1600 && $mValue < 2400)
				{
					$this->_bEmpty = false;
				}
				else
				{
					$this->_bEmpty = true;

					return false;
				}

				break;
			}
			case self::TIMES:
			case self::DB_TIME:
			{
				$aParts = explode(':', $mValue);

				$this->_iTS = $this->_mktime(
					$aParts[0],
					$aParts[1],
					$aParts[2],
					$this->_aParts[self::MONTH],
					$this->_aParts[self::DAY],
					$this->_aParts[self::YEAR]
				);

				break;
			}
			case self::DATES:
			{
				$aParts = explode('.', $mValue);

				$this->_iTS = $this->_mktime(
					$this->_aParts[self::HOUR],
					$this->_aParts[self::MINUTE],
					$this->_aParts[self::SECOND],
					$aParts[1],
					$aParts[0],
					$aParts[2]
				);

				if($aParts[2] >= 1600 && $aParts[2] < 2400)
				{
					$this->_bEmpty = false;
				}
				else
				{
					$this->_bEmpty = true;

					return false;
				}

				break;
			}
			case self::DB_DATE:
			{
				if($mValue === '0000-00-00')
				{
					$this->_bEmpty = true;

					return false;
				}

				$aParts = explode('-', $mValue);

				$this->_iTS = $this->_mktime(
					0,
					0,
					0,
					$aParts[1],
					$aParts[2],
					$aParts[0]
				);

				if($aParts[0] >= 1600 && $aParts[0] < 2400)
				{
					$this->_bEmpty = false;
				}
				else
				{
					$this->_bEmpty = true;

					return false;
				}

				break;
			}
			case self::DB_TIMESTAMP:
			case self::DB_DATETIME:
			{
				if($mValue === '0000-00-00 00:00:00')
				{
					$this->_bEmpty = true;

					return false;
				}

				$aSub = explode(' ', $mValue);
				$aPartsD = explode('-', $aSub[0]);
				$aPartsT = explode(':', $aSub[1]);

				$this->_iTS = $this->_mktime(
					$aPartsT[0],
					$aPartsT[1],
					$aPartsT[2],
					$aPartsD[1],
					$aPartsD[2],
					$aPartsD[0]
				);

				if($aPartsD[0] >= 1600 && $aPartsD[0] < 2400)
				{
					$this->_bEmpty = false;
				}
				else
				{
					$this->_bEmpty = true;

					return false;
				}

				break;
			}
			case self::MERIDIAN:
			{
				if($mValue === 'AM' && $this->get(self::MERIDIAN) === 'PM')
				{
					return $this->sub(12, self::HOUR);
				}
				else if($mValue === 'PM' && $this->get(self::MERIDIAN) === 'AM')
				{
					return $this->add(12, self::HOUR);
				}

				return $this;
			}
			case self::STRFTIME:
			{
				$aBack = strptime($mValue, $sFormat);

				if($aBack === false) {
					$this->_bEmpty = true;
				} else {
					$this->_iTS = $this->_mktime(
							$aBack['tm_hour'],
							$aBack['tm_min'],
							$aBack['tm_sec'],
							$aBack['tm_mon'] + 1,
							$aBack['tm_mday'],
							$aBack['tm_year'] + 1900
					);
				}

				break;
			}
			case self::LOCAL_DATES:
			{
				$aParts = explode(self::$aLocalFormat[3], $mValue);

				$iDay = $iMonth = $iYear = 0;

				foreach(self::$aLocalFormat as $iKey => $sPart)
				{
					if(strpos($sPart, 'D') !== false)
					{
						$iDay = $aParts[$iKey];
						continue;
					}
					if(strpos($sPart, 'M') !== false)
					{
						$iMonth = $aParts[$iKey];
						continue;
					}
					if(strpos($sPart, 'YYYY') !== false)
					{
						$iYear = $aParts[$iKey];
						continue;
					}
					else if(strpos($sPart, 'YY') !== false)
					{
						if((int)$sPart < 70)
						{
							$iYear = (int)$aParts[$iKey] + 2000;
						}
						else
						{
							$iYear = (int)$aParts[$iKey] + 1900;
						}
						continue;
					}
				}

				$this->_iTS = $this->_mktime(
					$this->_aParts[self::HOUR],
					$this->_aParts[self::MINUTE],
					$this->_aParts[self::SECOND],
					$iMonth,
					$iDay,
					$iYear
				);

				break;
			}
		}

		if(isset($iDiv))
		{
			$this->_iTS = bcsub($this->_iTS, $iDiv, self::$_iScale);
		}

		$this->_correctDST($iOldGMT);

		return $this;
	}


	/**
	 * Modified strftime function
	 * 
	 * @param string $sFormat
	 * @param int $iTimestamp
	 * @param string $sLanguage
	 * @return string
	 */
	public static function strftime($sFormat, $iTimestamp = null, $sLanguage='en')
	{
		if(is_null($iTimestamp))
		{
			$iTimestamp = time();
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Check

		if(
			strpos($sFormat, '%b') !== false ||
			strpos($sFormat, '%B') !== false ||
			strpos($sFormat, '%a') !== false ||
			strpos($sFormat, '%A') !== false ||
			strpos($sFormat, '%x') !== false ||
			strpos($sFormat, '%X') !== false ||
			strpos($sFormat, '%p') !== false
		)
		{
			// Do nothing
		}
		else
		{
			return strftime($sFormat, $iTimestamp);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Prepare data

		$oLocal = new WDLocale($sLanguage, 'date');

		$aData = $oLocal->getData();

		// Explode format to an array
		$aParts = self::createStrftimeParts($sFormat, $aData['time']['medium']);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Replace data

		$sReturn = '';

		foreach($aParts as $sPart)
		{
			switch($sPart)
			{
				case '%b': // Month (short)
				{
					$sReturn .= $aData['b'][(int)date('m', $iTimestamp)];

					break;
				}
				case '%B': // Month
				{
					$sReturn .= $aData['B'][(int)date('m', $iTimestamp)];

					break;
				}
				case '%a': // Weekday (short)
				{
					$iWeekDay = (int)date('w', $iTimestamp);

					if($iWeekDay === 0) {
						$iWeekDay = 7;
					}

					$sReturn .= $aData['a'][$iWeekDay];

					break;
				}
				case '%A': // Weekday
				{
					$iWeekDay = (int)date('w', $iTimestamp);

					if($iWeekDay === 0) {
						$iWeekDay = 7;
					}

					$sReturn .= $aData['A'][$iWeekDay];

					break;
				}
				case '%x': // Date
				{
					$sReturn .= strftime($aData['date']['medium'], $iTimestamp);

					break;
				}
				case '%p': // AM or PM
				{
					$sReturn .= $aData[date('a', $iTimestamp)];

					break;
				}
				default:
				{
					$sReturn .= $sPart;
				}
			}
		}

		// Replace another formats
		$sReturn = strftime($sReturn, $iTimestamp);

		return $sReturn;
	}


	/**
	 * Sub the part of date or time
	 *
	 * @param int $mValue
	 * @param string $sPart
	 * @return object
	 */
	public function sub($mValue, $sPart) {

		if($this->_bEmpty)
		{
			return false;
		}

		$mValue = '-' . $mValue;

		return $this->add($mValue, $sPart);
	}

	/* ====================================================================================== PROTECTED === */

	/**
	 * Check the date and/or time part after spliting of the input
	 * 
	 * @var mixed $mInput
	 * @var string $sFormat
	 * @return bool
	 */
	protected static function _simpleCheck($mInput, $sFormat)
	{
		$aFormat = str_split($sFormat);

		foreach($aFormat as $mSign)
		{
			if(isset(self::$_aShorts[$mSign]))
			{
				$iLength = self::$_aShorts[$mSign]['len'];

				$mPart = substr($mInput, 0, $iLength);

				if
				(
					$iLength !== strlen($mPart) ||
					!self::isDate($mPart, self::$_aShorts[$mSign]['con'])
				)
				{
					return false;
				}
			}
			else
			{
				$iLength = 1;

				$mPart = substr($mInput, 0, $iLength);

				if((string)$mPart !== (string)$mSign)
				{
					return false;
				}
			}

			$mInput = substr($mInput, $iLength);
		}

		if($mInput !== false)
		{
			return false;
		}

		return true;
	}


	/**
	 * Load the date and time parts
	 * 
	 * @param bool $bPreload
	 * @return array
	 */
	protected function _loadParts($bPreload = true)
	{
		global $system_data;

		// number_format, um eine dezimaldarstellung der Zahl zu erzwingen
		$iTimestamp	= number_format($this->_iTS, 0, '.', '');

		// The GMT correction
		$iTimestamp	= bcadd($iTimestamp, $this->_iGMT, self::$_iScale);

		$iYear = 1970;

		$iTempstamp = 0;

		$bOneSecondCorrecture = false;

		// Timestamp is lower like 0
		if(
			 IS_64_BIT_SYSTEM && $iTimestamp < 0 ||
			!IS_64_BIT_SYSTEM && bccomp($iTimestamp, 0, self::$_iScale) === -1
		)
		{
			// Make one second correcture on: ($iTimestamp % 3600) === 0
			if(strpos(bcdiv($iTimestamp, '3600', 4), '.0000') !== false)
			{
				$bOneSecondCorrecture = true;

				$iTimestamp = bcadd($iTimestamp, 1, self::$_iScale);
			}

			// Iterate the years table, increasing speed
			foreach(self::$_aYearsPrev as $iKey => $iValue)
			{
				if(
					 IS_64_BIT_SYSTEM && $iTimestamp < $iValue ||
					!IS_64_BIT_SYSTEM && bccomp($iTimestamp, $iValue, self::$_iScale) === -1
				)
				{
					if(bccomp($iTimestamp, $iValue, self::$_iScale) === -1)
					{
						$iYear = $iKey;
						$iTempstamp = $iValue;
					}
					else
					{
						break;
					}
				}
			}

			if(!IS_64_BIT_SYSTEM)
			{
				$iTimestamp = bcsub($iTimestamp, $iTempstamp, self::$_iScale);

				$iTimestamp = bcmul($iTimestamp, -1);
				$iDays		= bcdiv($iTimestamp, 86400, self::$_iScale);
				$iTemp		= bcmod($iTimestamp, 86400);
			}
			else
			{
				$iTimestamp = -1 * ($iTimestamp - $iTempstamp);

				$iDays = (int)($iTimestamp / 86400);
				$iTemp = (int)($iTimestamp % 86400);
			}

			// Iterate full years
			while(1)
			{
				$bIsLeapYear = self::_isLeapYear(--$iYear);

				if(($bIsLeapYear && $iDays < 366) || (!$bIsLeapYear && $iDays < 365))
				{
					break;
				}

				$iDays = bcsub($iDays, ($bIsLeapYear ? 366 : 365), self::$_iScale);
			}

			$iDayOfYear = ($bIsLeapYear ? 366 : 365);

			// Get the month data
			for($i = 11; $i >= 0; --$i)
			{
				$iMonthDays = self::$_aMonths[$i];

				if($bIsLeapYear && $i === 1)
				{
					++$iMonthDays;
				}

				if($iDays >= $iMonthDays)
				{
					$iDays		-= $iMonthDays;
					$iDayOfYear	-= $iMonthDays;
				}
				else
				{
					break;
				}
			}

			$iMonth		= $i + 1;
			$iDay		= $iMonthDays - $iDays;
			$iDayOfYear	-= $iDays;

			if($iTemp !== 0)
			{
				$iTemp = 86400 - $iTemp;
			}
		}
		else
		{
			// Iterate the years table, increasing speed
			foreach(self::$_aYearsNext as $iKey => $iValue)
			{
				if(
					 IS_64_BIT_SYSTEM && $iTimestamp > $iValue ||
					!IS_64_BIT_SYSTEM && bccomp($iTimestamp, $iValue, self::$_iScale) === 1
				)
				{
					$iYear = $iKey;
					$iTempstamp = $iValue;
				}
				else
				{
					break;
				}
			}

			if(!IS_64_BIT_SYSTEM)
			{
				$iTimestamp = bcsub($iTimestamp, $iTempstamp, self::$_iScale);

				$iDays = bcdiv($iTimestamp, 86400, self::$_iScale) + 1;
				$iTemp = bcmod($iTimestamp, 86400);
			}
			else
			{
				$iTimestamp -= $iTempstamp;

				$iDays = (int)($iTimestamp / 86400) + 1;
				$iTemp = (int)($iTimestamp % 86400);
			}

			// Iterate full years
			while(1)
			{
				$bIsLeapYear = self::_isLeapYear($iYear);

				if(($bIsLeapYear && $iDays <= 366) || (!$bIsLeapYear && $iDays <= 365))
				{
					break;
				}

				$iDays = bcsub($iDays, ($bIsLeapYear ? 366 : 365), self::$_iScale);

				++$iYear;
			}

			$iDayOfYear	= 0;

			// Get the month data
			for($i = 0; $i < 12; ++$i)
			{
				$iMonthDays = self::$_aMonths[$i];

				if($bIsLeapYear && $i === 1)
				{
					++$iMonthDays;
				}

				if($iDays > $iMonthDays)
				{
					$iDays		-= $iMonthDays;
					$iDayOfYear	+= $iMonthDays;
				}
				else
				{
					break;
				}
			}

			$iMonth		= $i + 1;
			$iDay		= $iDays;
			$iDayOfYear	+= $iDay;
		}

		$iHours		= (int)($iTemp / 3600);
		$iTemp		= $iTemp % 3600;
		$iMinutes	= (int)($iTemp / 60);
		$iTemp		= $iTemp % 60;
		$iSeconds	= $iTemp;

		// Remove the one second correcture
		if($bOneSecondCorrecture)
		{
			--$iSeconds;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Check GMT

		$iOldGMT = $this->_iGMT;

		if($bPreload === true)
		{
			$this->_setGMTTimestamp($this->_iTS);

			if($iOldGMT === $this->_iGMT)
			{
				$bPreload = false;
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		if($bPreload === true)
		{
			return $this->_loadParts(false);
		}
		else
		{
			if($iYear < 1600 || $iYear >= 2400)
			{
				if(\System::d('debugmode')) {
					throw new Exception('Please use dates between 1600 and 2400');
				}
			}

			$iQuarter = (int)($iMonth / 3);

			if(($iMonth % 3) > 0)
			{
				$iQuarter = (int)($iMonth / 3) + 1;
			}

			if($iHours < 12)
			{
				$sMeridian = 'AM';

				if($iHours === 0)
				{
					$iMeridian = 12;
				}
				else
				{
					$iMeridian = $iHours;
				}
			}
			else
			{
				$sMeridian = 'PM';

				if($iHours === 12)
				{
					$iMeridian = $iHours;
				}
				else
				{
					$iMeridian = $iHours - 12;
				}
			}

			$this->_aParts = array(
				self::DAY			=> str_pad($iDay, 2, '0', STR_PAD_LEFT),
				self::MONTH			=> str_pad($iMonth, 2, '0', STR_PAD_LEFT),
				self::YEAR			=> str_pad($iYear, 4, '0', STR_PAD_LEFT),

				self::HOUR			=> str_pad($iHours, 2, '0', STR_PAD_LEFT),
				self::MINUTE		=> str_pad($iMinutes, 2, '0', STR_PAD_LEFT),
				self::SECOND		=> str_pad($iSeconds, 2, '0', STR_PAD_LEFT),

				self::QUARTER		=> $iQuarter,

				self::TIMESTAMP		=> $this->_iTS,

				self::MONTH_DAYS	=> $iMonthDays,
				self::WEEK			=> $this->_getWeek($iYear, $iDay, $iDayOfYear),
				self::DAY_OF_WEEK	=> $this->_getWeekDay($iYear, $iMonth, $iDay),
				self::DAY_OF_YEAR	=> $iDayOfYear,
				self::YEAR_DAYS		=> ($bIsLeapYear ? 366 : 365),

				self::MERIDIAN		=> $sMeridian,
				self::HOUR_MERIDIAN	=> str_pad($iMeridian, 2, '0', STR_PAD_LEFT)
			);

			/* ================================================== */

			return $this;
		}
	}

	/* ======================================================================================== PRIVATE === */

	/**
	 * Calculate the intern $_iTS
	 *
	 * @param int $mValue
	 * @param string $sPart
	 * @return object
	 */
	private function _calculate($mValue, $sPart)
	{
		if(IS_64_BIT_SYSTEM)
		{
			switch($sPart)
			{
				case self::SECOND:
				case self::TIMESTAMP:	$this->_iTS += $mValue;				break;
				case self::MINUTE:		$this->_iTS += $mValue * 60;		break;
				case self::HOUR:		$this->_iTS += $mValue * 3600;		break;
				case self::DAY:			$this->_iTS += $mValue * 86400;		break;
				case self::WEEK:		$this->_iTS += $mValue * 604800;	break;
			}
		}
		else
		{
			switch($sPart)
			{
				case self::SECOND:
				case self::TIMESTAMP:	$this->_iTS = bcadd($this->_iTS, $mValue, self::$_iScale);			break;
				case self::MINUTE:		$this->_iTS = bcadd($this->_iTS, $mValue * 60, self::$_iScale);		break;
				case self::HOUR:		$this->_iTS = bcadd($this->_iTS, $mValue * 3600, self::$_iScale);	break;
				case self::DAY:			$this->_iTS = bcadd($this->_iTS, $mValue * 86400, self::$_iScale);	break;
				case self::WEEK:		$this->_iTS = bcadd($this->_iTS, $mValue * 604800, self::$_iScale);	break;
			}
		}

		switch($sPart)
		{
			case self::QUARTER:
			{
				$mValue *= 3;
			}
			case self::MONTH:
			{
				// Add/sub full years
				$this->_aParts[self::YEAR] += (int)($mValue / 12);

				if($mValue < 0)
				{
					$this->_aParts[self::MONTH] -= (($mValue * -1) % 12);

					if($this->_aParts[self::MONTH] <= 0)
					{
						$this->_aParts[self::MONTH] += 12;
						--$this->_aParts[self::YEAR];
					}
				}
				else
				{
					$this->_aParts[self::MONTH] += ($mValue % 12);

					if($this->_aParts[self::MONTH] > 12)
					{
						$this->_aParts[self::MONTH] -= 12;
						++$this->_aParts[self::YEAR];
					}
				}

				// Correction of day
				if($this->_aParts[self::DAY] > self::$_aMonths[$this->_aParts[self::MONTH]-1])
				{
					$this->_aParts[self::DAY] = self::$_aMonths[$this->_aParts[self::MONTH]-1];

					if((int)$this->_aParts[self::MONTH] === 2 && self::_isLeapYear($this->_aParts[self::YEAR]))
					{
						++$this->_aParts[self::DAY];
					}
				}

				$this->_iTS = $this->_mktime(
					$this->_aParts[self::HOUR],
					$this->_aParts[self::MINUTE],
					$this->_aParts[self::SECOND],
					$this->_aParts[self::MONTH],
					$this->_aParts[self::DAY],
					$this->_aParts[self::YEAR]
				);

				break;
			}
			case self::YEAR:
			{
				$this->_aParts[self::YEAR] += $mValue;

				// Correction of day
				if((int)$this->_aParts[self::MONTH] === 2 && (int)$this->_aParts[self::DAY] === 29)
				{
					if(!self::_isLeapYear($this->_aParts[self::YEAR]))
					{
						$this->_aParts[self::DAY] = 28;
					}
				}

				$this->_iTS = $this->_mktime(
					$this->_aParts[self::HOUR],
					$this->_aParts[self::MINUTE],
					$this->_aParts[self::SECOND],
					$this->_aParts[self::MONTH],
					$this->_aParts[self::DAY],
					$this->_aParts[self::YEAR]
				);

				break;
			}
		}

		return $this;
	}


	/**
	 * Correct the DST time difference
	 * 
	 * @param int $iOldGMT
	 */
	private function _correctDST($iOldGMT)
	{
		
		$this->_loadParts();

		if($iOldGMT > $this->_iGMT)
		{
			$this->_iTS = bcadd($this->_iTS, ($iOldGMT - $this->_iGMT), self::$_iScale);
			$this->_loadParts();
		}
		if($iOldGMT < $this->_iGMT)
		{
			$this->_iTS = bcsub($this->_iTS, ($this->_iGMT - $iOldGMT), self::$_iScale);
			$this->_loadParts();
		}

	}


	/**
	 * Make timestamp
	 * 
	 * @param int $iHour
	 * @param int $iMinute
	 * @param int $iSecond
	 * @param int $iMonth
	 * @param int $iDay
	 * @param int $iYear
	 * @return int
	 */
	private function _mktime($iHour, $iMinute, $iSecond, $iMonth, $iDay, $iYear)
	{
		$iHour		= (int)$iHour * 3600;
		$iMinute	= (int)$iMinute * 60;
		$iSecond	= (int)$iSecond;
		$iDay		= (int)$iDay;
		$iMonth		= (int)$iMonth - 1;
		$iYear		= (int)$iYear;

		$iReturn	= 0;
		$iSpeed		= 0;
		$iLast		= 1970;

		if($iYear >= 1970)
		{
			// Iterate the years table, increasing speed
			foreach(self::$_aYearsNext as $iKey => $iValue)
			{
				if($iKey >= $iYear || $iYear > 2400)
				{
					break;
				}

				$iLast = $iKey;
			}

			$iSpeed = self::$_aYearsNext[$iLast];

			for($i = $iLast; $i <= $iYear; ++$i)
			{
				$bLeapYear = self::_isLeapYear($i);

				if($i < $iYear)
				{
					$iReturn += 365;

					if($bLeapYear)
					{
						++$iReturn;
					}
				}
				else
				{
					for($n = 0; $n < $iMonth; ++$n)
					{
						$iReturn += self::$_aMonths[$n];

						if($bLeapYear && $n === 1)
						{
							++$iReturn;
						}
					}
				}
			}

			$iReturn += $iDay - 1;

			$iReturn = bcmul($iReturn, 86400, self::$_iScale);
			$iReturn = bcadd($iReturn, $iSpeed, self::$_iScale);
			$iReturn = bcadd($iReturn, ($iHour + $iMinute + $iSecond), self::$_iScale);
		}
		else
		{
			// Iterate the years table, increasing speed
			foreach(self::$_aYearsPrev as $iKey => $iValue)
			{
				if($iKey <= $iYear || $iYear < 1600)
				{
					break;
				}

				$iLast = $iKey;
			}

			$iSpeed = self::$_aYearsPrev[$iLast];

			for($i = ($iLast - 1); $i >= $iYear; --$i)
			{
				$bLeapYear = self::_isLeapYear($i);

				if($i > $iYear)
				{
					$iReturn += 365;

					if($bLeapYear)
					{
						++$iReturn;
					}
				}
				else
				{
					for($n = 11; $n >= $iMonth; --$n)
					{
						$iReturn += self::$_aMonths[$n];

						if($bLeapYear && $n === 1)
						{
							++$iReturn;
						}
					}
				}
			}

			$iReturn -= $iDay;

			$iD = bcmul($iReturn, 86400, self::$_iScale);
			$iD = bcadd($iD, $iSpeed, self::$_iScale);

			$iReturn = '-' . bcadd($iD, (86400 - $iHour - $iMinute - $iSecond), self::$_iScale);

			// Gregorian correcture ($iReturn is lower like 15. October 1582)
			if(
				 IS_64_BIT_SYSTEM && $iReturn < -12219292800 ||
				!IS_64_BIT_SYSTEM && bccomp($iReturn, '-12219292800') === -1
			)
			{
				$iReturn = bcsub($iReturn, '864000', self::$_iScale);
			}
		}

		$iReturn = bcsub($iReturn, $this->_iGMT, self::$_iScale);

		return $iReturn;
	}


	/**
	 * Set the default local date format
	 * 
	 * @return string
	 */
	private static function _setLocalFormat()
	{
		$iTempTS = mktime(3, 4, 5, 1, 2, 1999); // 02.01.1999 03:04:05
		$sTempDate = strftime('%x', $iTempTS);

		// Get the spacer
		for($i = 0; $i < strlen($sTempDate); ++$i)
		{
			if(!is_numeric($sSpacer = $sTempDate[$i]))
			{
				break;
			}
		}

		$aParts = explode($sSpacer, $sTempDate);

		foreach($aParts as $iKey => $sPart)
		{
			if((int)$sPart === 2)
			{
				$aParts[$iKey] = str_pad('D', strlen($sPart), 'D', STR_PAD_LEFT);
			}
			if((int)$sPart === 1)
			{
				$aParts[$iKey] = str_pad('M', strlen($sPart), 'M', STR_PAD_LEFT);
			}
			if((int)$sPart === 1999)
			{
				$aParts[$iKey] = 'YYYY';
			}
			else if((int)$sPart === 99)
			{
				$aParts[$iKey] = 'YY';
			}
		}

		$aParts[] = $sSpacer;

		self::$aLocalFormat = $aParts;
	}


	/**
	 * Get the number of week: 1 - 53
	 * 
	 * @param int $iYear
	 * @param int $iDay
	 * @param int $iDayOfYear
	 * @return int
	 */
	private function _getWeek($iYear, $iDay, $iDayOfYear)
	{
		$iWeekday = $this->_getWeekDay($iYear, 1, 1) - 1;

		if($iWeekday > 3)
		{
			$iWeekday -= 7;
		}

		$iWeek = (int)(($iDayOfYear - 1 + $iWeekday) / 7) + 1;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Special cases with correctures

		if($iWeek === 1)
		{
			$iWeekday *= -1;

			if($iWeekday >= $iDay)
			{
				$iDayOfYear = 365;

				if(self::_isLeapYear($iYear - 1))
				{
					$iDayOfYear = 366;
				}

				$iWeekday = $this->_getWeekDay($iYear - 1, 1, 1) - 1;

				if($iWeekday > 3)
				{
					$iWeekday -= 7;
				}

				$iWeek = (int)(($iDayOfYear - 1 + $iWeekday) / 7) + 1;
			}
		}
		else if($iWeek === 53 && $iWeekday !== 3)
		{
			 if($iWeekday !== 2 || !self::_isLeapYear($iYear))
			 {
			 	$iWeek = 1;
			 }
		}

		return $iWeek;
	}


	/**
	 * Get the day of week, 1-7 (1 = monday, 7 = sunday)
	 *
	 * @param int $iYear
	 * @param int $iMonth
	 * @param int $iDay
	 * @return int
	 */
	private function _getWeekDay($iYear, $iMonth, $iDay)
	{
		if($iMonth <= 2)
		{
			$iMonth += 12;
			--$iYear;
		}

		$iJ = (int)($iYear/100);
		$iK = $iYear % 100;

		// Get the day by Zeller's congruence
		$iWeekDay = ($iDay + (int)(($iMonth + 1) * 26 / 10) + $iK + (int)($iK / 4) + (int)($iJ / 4) + 5 * $iJ) % 7;

		return $iWeekDay <= 1 ? ($iWeekDay + 8 - 2) : --$iWeekDay;
	}


	/**
	 * Validate the year of an leap year
	 * 
	 * @param $iYear
	 * @return bool
	 */
	private static function _isLeapYear($iYear)
	{
		if(($iYear % 4) !== 0)
		{
			return false;
		}
		else if(($iYear % 400) === 0)
		{
			return true;
		}
		else if(($iYear % 100) === 0)
		{
			return false;
		}

		return true;
	}


	/**
	 * Validate a date
	 * 
	 * @param $aParts
	 * @return bool
	 */
	private static function _isValidDate($aParts)
	{
		if($aParts[0] < 1 || $aParts[0] > 31 || $aParts[1] > 12 || $aParts[1] < 1 || $aParts[2] >= 2400 || $aParts[2] < 1600)
		{
			return false;
		}

		// Check month days
		if
		(
			(int)$aParts[0] > 30 &&
			(
				(int)$aParts[1] === 4 ||
				(int)$aParts[1] === 6 ||
				(int)$aParts[1] === 9 ||
				(int)$aParts[1] === 11
			)
		)
		{
			return false;
		}

		// Check February
		if
		(
			( self::_isLeapYear($aParts[2]) && (int)$aParts[1] === 02 && (int)$aParts[0] > 29) ||
			(!self::_isLeapYear($aParts[2]) && (int)$aParts[1] === 02 && (int)$aParts[0] > 28)
		)
		{
			return false;
		}

		return true;
	}

	private function _setGMTTimestamp($iTimestamp) {
		self::$_oGMT->setTimestamp($iTimestamp);
		$this->_iGMT = self::$_oGMT->getOffset();
	}
}
