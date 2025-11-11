<?php

class WDLocale
{
	/**
	 * Given language
	 * 
	 * @var string
	 */
	protected $_sLanguage;


	/**
	 * The XML objects cache array
	 * 
	 * @var array
	 */
	protected static $_aXML;


	/**
	 * The date data cache array
	 * 
	 * @var array
	 */
	protected static $_aDateCache;

	/* ==================================================================================================== */

	/**
	 * The constructor
	 * 
	 * @param string $sLanguage
	 */
	public function __construct($sLanguage, $sDataPart) {

		$this->_sLanguage = $sLanguage;

		switch($sDataPart)
		{
			case 'date':
			{
				if(!isset(self::$_aDateCache[$this->_sLanguage])) {
					
					$sCacheKey = 'wdlocale_date_'.$this->_sLanguage;
					
					// Daten versuchen aus Cache zu holen
					$aDataCache = WDCache::get($sCacheKey);

					// Wenn Cache leer ist, Einträge aus XML holen
					if($aDataCache === null) {

						$this->_parseDateData();

						WDCache::set($sCacheKey, (24*60*60), self::$_aDateCache[$this->_sLanguage]);

					} else {
						self::$_aDateCache[$this->_sLanguage] = $aDataCache;
					}

				}

				break;
			}
		}
		
	}

	/* ==================================================================================================== */

	/**
	 * Get data
	 * 
	 * @return array
	 */
	public function getData()
	{
		return self::$_aDateCache[$this->_sLanguage];
	}

	/**
	 * Gibt einen einzelnen Wert zurück
	 * @param string $sType
	 * @param int $iKey
	 * @return string
	 */
	public function getValue($sType, $iKey) {
		
		$sValue = self::$_aDateCache[$this->_sLanguage][$sType][$iKey];

		return $sValue;
		
	}
	
	/**
	 * Gibt einen Wert, anhand von Sprache, Typ und Key zurück
	 * @param string $sLanguage
	 * @param string $sType
	 * @param int $iKey
	 * @return string
	 */
	public static function get($sLanguage, $sType, $iKey) {
		
		$oLocale = new self($sLanguage, 'date');
		
		$sValue = $oLocale->getValue($sType, $iKey);
		
		return $sValue;
		
	}
	
	/* ==================================================================================================== */

	/**
	 * Load XML data, create intern SimpleXMLElement object
	 */
	protected function _loadXML()
	{
		if(empty(self::$_aXML[$this->_sLanguage]))
		{
			$sPath = Util::getDocumentRoot() . 'system/includes/wdlocale/' . $this->_sLanguage . '.xml';

			if(!is_file($sPath)) {
				if(System::d('debugmode')) {
					throw new Exception('Locale "' . $this->_sLanguage . '" does not exists!');
				} else {
					// Wenn die gewählte Sprache nicht gefunden wurde, dann en als Fallback nutzen
					$sPath = Util::getDocumentRoot() . 'system/includes/wdlocale/en.xml';
				}
			}

			$sContent = file_get_contents($sPath);

			self::$_aXML[$this->_sLanguage] = new SimpleXMLElement($sContent);

		}
	}

	/**
	 * Read XML date data into the date cache array
	 */
	protected function _parseDateData()
	{
		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Read months

		// Falls noch nicht geschehen, wird das XML geladen
		$this->_loadXML();
		
		foreach(self::$_aXML[$this->_sLanguage]->dates->calendar->months->monthWidth as $oMonthData)
		{
			if($oMonthData->attributes()->type == 'abbreviated')
			{
				foreach($oMonthData->month as $oMonth)
				{
					self::$_aDateCache[$this->_sLanguage]['b'][(int)$oMonth->attributes()->type] = (string)$oMonth;
				}
			}
			if($oMonthData->attributes()->type == 'wide')
			{
				foreach($oMonthData->month as $oMonth)
				{
					self::$_aDateCache[$this->_sLanguage]['B'][(int)$oMonth->attributes()->type] = (string)$oMonth;
				}
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Read days

		foreach(self::$_aXML[$this->_sLanguage]->dates->calendar->days->dayWidth as $oDayData)
		{
			if($oDayData->attributes()->type == 'abbreviated')
			{
				foreach($oDayData->day as $oDay)
				{
					self::$_aDateCache[$this->_sLanguage]['a'][] = (string)$oDay;
				}
			}
			if($oDayData->attributes()->type == 'wide')
			{
				foreach($oDayData->day as $oDay)
				{
					self::$_aDateCache[$this->_sLanguage]['A'][] = (string)$oDay;
				}
			}
		}

		self::$_aDateCache[$this->_sLanguage]['a'][7] = self::$_aDateCache[$this->_sLanguage]['a'][0];
		self::$_aDateCache[$this->_sLanguage]['A'][7] = self::$_aDateCache[$this->_sLanguage]['A'][0];

		unset(self::$_aDateCache[$this->_sLanguage]['a'][0], self::$_aDateCache[$this->_sLanguage]['A'][0]);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Read quarter

		foreach(self::$_aXML[$this->_sLanguage]->dates->calendar->quarters->quarterWidth as $oQuarterData)
		{
			if($oQuarterData->attributes()->type == 'abbreviated')
			{
				foreach($oQuarterData->quarter as $oQuarter)
				{
					self::$_aDateCache[$this->_sLanguage]['q'][(int)$oQuarter->attributes()->type] = (string)$oQuarter;
				}
			}
			if($oMonthData->attributes()->type == 'wide')
			{
				foreach($oQuarterData->quarter as $oQuarter)
				{
					self::$_aDateCache[$this->_sLanguage]['Q'][(int)$oQuarter->attributes()->type] = (string)$oQuarter;
				}
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Read date formats

		foreach(self::$_aXML[$this->_sLanguage]->dates->calendar->dateFormats->dateFormatLength as $oDateData)
		{
			$sType = (string)$oDateData->attributes()->type;

			if(
				$sType == 'long'	||
				$sType == 'medium'	||
				$sType == 'short'
			)
			{
				self::$_aDateCache[$this->_sLanguage]['date'][$sType] = (string)$oDateData->dateFormat->pattern;
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Read times

		foreach(self::$_aXML[$this->_sLanguage]->dates->calendar->timeFormats->timeFormatLength as $oTimeData)
		{
			$sType = (string)$oTimeData->attributes()->type;

			if(
				$sType == 'long'	||
				$sType == 'medium'	||
				$sType == 'short'
			)
			{
				self::$_aDateCache[$this->_sLanguage]['time'][$sType] = (string)$oTimeData->timeFormat->pattern;
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Read strings

		foreach(self::$_aXML[$this->_sLanguage]->dates->calendar->fields->field as $oField)
		{
			$sType = (string)$oField->attributes()->type;

			if(
				$sType == 'year'		||
				$sType == 'month'		||
				$sType == 'week'		||
				$sType == 'day'			||
				$sType == 'weekday'		||
				$sType == 'dayperiod'	||
				$sType == 'hour'		||
				$sType == 'minute'		||
				$sType == 'second'
			)
			{
				self::$_aDateCache[$this->_sLanguage]['fields'][$sType] = (string)$oField->displayName;

				if($sType == 'day')
				{
					$aRelatives = array();

					foreach($oField->relative as $oRelative)
					{
						$aRelatives[(int)$oRelative->attributes()->type] = (string)$oRelative;
					}
				}
			}
		}

		if(!empty($aRelatives))
		{
			self::$_aDateCache[$this->_sLanguage]['fields']['day_relatives'] = $aRelatives;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Read AM/PM

		self::$_aDateCache[$this->_sLanguage]['am'] = (string)self::$_aXML[$this->_sLanguage]->dates->calendar->am;
		self::$_aDateCache[$this->_sLanguage]['pm'] = (string)self::$_aXML[$this->_sLanguage]->dates->calendar->pm;
	}
}