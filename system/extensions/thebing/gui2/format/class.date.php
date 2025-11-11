<?php

class Ext_Thebing_Gui2_Format_Date extends Ext_Gui2_View_Format_Date {

	/**
	 * @var bool
	 */
	protected $_mType = false;

	/**
	 * @var int|mixed|string
	 */
	protected $_iSchoolId = 0;

	protected $bConvertToNull = false;

	/**
	 * @param bool $mType
	 * @param int $iSchoolForFormat
	 * @param bool $sDatePart
	 */
	public function __construct($mType=false, $iSchoolForFormat = 0, $sDatePart=false) {

		if ($mType === 'convert_null') {
			$this->bConvertToNull = true;
			$mType = false;
		}

		$this->_mType = $mType;

		if($iSchoolForFormat <= 0) {
			$oSchool = Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
			$iSchoolForFormat = $oSchool->id;
		}
		$this->_iSchoolId = $iSchoolForFormat;

		if($sDatePart) {
			$this->sWDDatePart = $sDatePart;
		}

	}

	/**
	 * @param $sOption
	 * @return mixed|string
	 */
	public function __get($sOption) {

		// für JS muss das Format umgewandelt werden
		if($sOption == 'format_js') {

			$iSchool = $this->_iSchoolId;
			$oSchool = Ext_Thebing_School::getInstance($iSchool);

			$sFormatSchool = $oSchool->date_format_long;

			if($sFormatSchool == "" || is_numeric($sFormatSchool)) {
				$sFormatSchool = "%x";
			}

			$this->format = $sFormatSchool;

			$sFormat = parent::__get($sOption);

			return $sFormat;

		}

		return $this->aOption[$sOption];
	}

	/**
	 * @param mixed $mValue
	 * @return int|string
	 */
	public function formatByValue($mValue) {

		$oColumn = null;
		$aResultData = array('school_id' => (int)$this->_iSchoolId);

		return $this->format($mValue, $oColumn, $aResultData); 
	}

	/**
	 * @param mixed $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return int|string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		// Kompatibilität
		if($mValue instanceof DateTimeInterface) {
			$mValue = $mValue->getTimestamp();
		}

		if(empty($aResultData['school_id'])){
			$aResultData['school_id'] = $this->_iSchoolId;
		}
		
		$this->format = Ext_Thebing_Format::getDateFormat($aResultData['school_id'], $this->_mType);

		$mValue = $this->getFormattedValue($mValue);

		return $mValue;
	}

	/**
	 * @param mixed $mValue
	 * @return int|string
	 */
	protected function getFormattedValue($mValue) {

		if(
			$mValue === '0000-00-00' ||
			$mValue === '0000-00-00 00:00:00' ||
			$mValue === NULL ||
			$mValue === '' ||
			$mValue === false ||
			$mValue === '0'
		) {
			return '';
		}

		try {

			$bIsUnixTimestamp = false;

			// Wenn Nummeric => unix timestamp
			if(is_numeric($mValue)) {
				$bIsUnixTimestamp = true;
			}

			// Wenn (mysql) Timestamp dann rechne es in Unix Timestamp um!
			if(!$bIsUnixTimestamp) {

				try {

                    // Auf datetime umgestellt damit 0000-00-00T00:00:00Z auch klappt!
					
					if(preg_match('/(([0-9]){4}\-([0-9]){2}\-([0-9]){2})T(([0-9]){2}\:([0-9]){2}\:([0-9]){2})/', $mValue) === 1) {
						$mValue = substr($mValue, 0, 10).' '.substr($mValue, 11, 8);
					}

					$oDate = new DateTime($mValue);

					// setTimezone() setzt NUR die Timezone im Objekt, und konvertiert nichts
					$oDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));

					$mValue = self::strftime($this->format, $oDate->getTimestamp());

				} catch(Exception $e) {
					$mValue = $mValue;
				}

			} else {
				$mValue = self::strftime($this->format, $mValue);
			}

		} catch(Exception $e) {
			$mValue = $mValue;
		}
		
		return $mValue;
	}

	/**
	 * @param string $sFormat
	 * @param int $iTimestamp
	 * @return string
	 */
	public static function strftime($sFormat, $iTimestamp) {

		// strftime bietet kein Ordinalzeichen
		if(strpos($sFormat, '%O') !== false) {
			$sFormat = str_replace('%O', date('S', $iTimestamp), $sFormat);
		}

		return strftime($sFormat, $iTimestamp);

	}

	/**
	 * Wandelt den wert wieder in den ursprungswert um
	 *
	 * @param mixed $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return mixed
	 */
	public function convert($mValue, &$oColumn = null, &$aResultData = null) {

		if(empty($aResultData['school_id'])) {
			$aResultData['school_id'] = $this->_iSchoolId;
		}

		$this->format = Ext_Thebing_Format::getDateFormat($aResultData['school_id'], $this->_mType);

		// Ordinalsuffix entfernen
		if(strpos($this->format, '%O') !== false) {
			// Hier wird davon ausgegangen, dass der Ordinalsuffix immer hinter dem Tag steht (mit oder ohne Whitespace)
			$this->format = str_replace('%O', '', $this->format);
			$mValue = preg_replace('/([0-9]{1,2})\s?(st|nd|rd|th)/', '$1', $mValue, -1, $c);
		}

		$mValue = parent::convert($mValue, $oColumn, $aResultData);

		if (
			empty($mValue) &&
			$this->bConvertToNull
		) {
			return null;
		}

		return $mValue;
	}

}
