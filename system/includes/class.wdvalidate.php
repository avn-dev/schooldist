<?php

use Sirprize\PostalCodeValidator\Validator as ZipValidator;

/**
 * Klasse zum validieren von Daten
 * @property string $value
 * @property string $check
 * @property string $parameter
 */
class WDValidate {

	protected $_sValue;
	protected $_sCheck;
	protected $_sParameter;

	public function value($value)
	{
		$this->_sValue = $value;
		return $this;
	}

	public function on(string $check)
	{
		$this->_sCheck = $check;
		return $this;
	}

	public function __set($sName, $sValue) {

		switch($sName) {
			case 'value':
				$this->_sValue = $sValue;
				break;
			case 'check':
				$this->_sCheck = $sValue;
				break;
			case 'parameter':
				$this->_sParameter = $sValue;
				break;
			default:
				throw new Exception('Unknown name');
		}

	}

	public function __get($sName) {
		
		switch($sName) {
			case 'value':
				return $this->_sValue;
			default:
				throw new Exception('Unknown name');
		}
		
	}
	
	public function execute() {
		switch($this->_sCheck){
			case 'MAIL':
				$bCheck = Util::checkEmailMx($this->_sValue);
				break;
			case 'IN_ARRAY':
				$bCheck = in_array($this->_sValue, (array)$this->_sParameter);
				break;
			case 'REGEX':
				$bCheck = preg_match("/^".$this->_sParameter."$/", $this->_sValue);
				break;
			case 'ALNUM':
			case '_ALNUM':
				$bWhiteSpaces = "";
				if(substr($this->_sCheck, 0, 1) == '_'){
					$bWhiteSpaces = "\s";
				}

				$bCheck = preg_match("/^[\p{L}\p{N}\-\/".$bWhiteSpaces."]+$/i", $this->_sValue);
				break;
			case 'TEXT':
				$bCheck = preg_match("/^.+$/i", $this->_sValue);
				break;
			case 'NUMERIC':
				$bCheck = is_numeric($this->_sValue);
				break;
			case 'INT':
				$bCheck = preg_match("/^\-?[0-9]*$/", $this->_sValue);
				break;
			case 'INT_POSITIVE':
				$bCheck = preg_match("/^[1-9]+[0-9]*$/", $this->_sValue);
				break;
			case 'CTYPT_DIGIT':
				$bCheck = ctype_digit($this->_sValue);
				break;
			case 'INT_NOTNEGATIVE':
				$bCheck = preg_match("/^[0-9]*$/", $this->_sValue);
				break;
			case 'FLOAT':
				$bCheck = preg_match("/^\-?[0-9]*\.?[0-9]*$/", $this->_sValue);
				break;
			case 'FLOAT_POSITIVE':
				$bCheck = preg_match("/^([1-9]+[0-9]*\.?[0-9]*|[0-9]*\.?[0-9]*[1-9]+[0-9]*)$/", $this->_sValue);
				break;
			case 'FLOAT_NOTNEGATIVE':
				$bCheck = preg_match("/^[0-9]*\.?[0-9]*$/", $this->_sValue);
				break;
			case 'DATE':
				$bCheck = WDDate::isDate($this->_sValue, WDDate::DB_DATE);
				break;
			case 'DATE_TIME': 
				$bCheck = WDDate::isDate($this->_sValue, WDDate::DB_DATETIME);
				break;
			case 'DATE_FUTURE':
				$bCheck = WDDate::isDate($this->_sValue, WDDate::DB_DATE);
				if($bCheck){
					$oDate = new WDDate(date('Y-m-d'), WDDate::DB_DATE);
					$oDateTemp = new WDDate($this->_sValue, WDDate::DB_DATE);
					if($oDate->get(WDDate::TIMESTAMP) > $oDateTemp->get(WDDate::TIMESTAMP)){
						$bCheck = false;
					}
				}
				break;
			case 'DATE_PAST':
				$bCheck = WDDate::isDate($this->_sValue, WDDate::DB_DATE);
				if($bCheck){
					$oDate = new WDDate(date('Y-m-d'), WDDate::DB_DATE);
					$oDateTemp = new WDDate($this->_sValue, WDDate::DB_DATE);
					if($oDate->get(WDDate::TIMESTAMP) < $oDateTemp->get(WDDate::TIMESTAMP)){
						$bCheck = false;
					}
				}
				break;
			case 'TIME':
				// Matches: 02:04 | 16:56 | 23:59
				$bCheck = preg_match('/^([0-1][0-9]|[2][0-3]):([0-5][0-9])(:([0-5][0-9]))?$/', $this->_sValue);
				break;
			case 'PHONE':
				$bCheck = preg_match("/^\+?[0-9\/\.\-]+$/i", $this->_sValue);
				break;
			case 'PHONE_ITU':

				$sCountryIso = strtoupper($this->_sParameter);
				$this->_sValue = $this->formatPhonenumber($this->_sValue, $sCountryIso);

				$bCheck = preg_match("/^\+(?:[0-9] ?){6,14}[0-9]$/i", $this->_sValue);
				
				break;
			case 'HEX_COLOR':
				$bCheck = preg_match("/^#[0-9A-F]{6}$/i", $this->_sValue);
				break;
			case 'ZIP':			
				$bCheck = true;

				$oValidator = new ZipValidator();
				$sCountryIso = strtoupper($this->_sParameter);

				if($oValidator->hasCountry($sCountryIso)) {
					$bCheck = $oValidator->isValid($sCountryIso, $this->_sValue);
				}

				break;
			case 'IBAN':
				
					$oIban = new \Iban\Validation\Iban($this->_sValue);
					$oValidator = new \Iban\Validation\Validator();

					$bCheck = $oValidator->validate($oIban);

//					if (!$bCheck) {
//						foreach ($oValidator->getViolations() as $violation) {
//							__out($violation);
//						}
//					}
					
				break;
			case 'URL':
					$bCheck = Util::checkUrl($this->_sValue);
				break;
			default: 
				throw new Exception('Unknown validator "'.$this->_sCheck.'"!');
		}

		return (bool)$bCheck;
	}
	
	/**
	 * @todo Auslagern in eigene oder andere Klasse
	 * @param string $sCountryIso
	 * @param string $sNumber
	 * @return string
	 */
	public function formatPhonenumber($sNumber, $sCountryIso) {

		if(empty($sCountryIso)) {
			System::wd()->executeHook('tc_phone_number_default_country_iso_hook', $sCountryIso);
		}

		if(!empty($sCountryIso)) {

			$oPhonenumberUtil = libphonenumber\PhoneNumberUtil::getInstance();

			try {

				$oPhonenumber = $oPhonenumberUtil->parse($sNumber, $sCountryIso);

				$sCountryCallingCode = $oPhonenumber->getCountryCode();
				$sRegionCode = $oPhonenumberUtil->getRegionCodeForCountryCode($sCountryCallingCode);

				if(\libphonenumber\PhoneNumberUtil::REGION_CODE_FOR_NON_GEO_ENTITY === $sRegionCode) {
					$oMetadata = $oPhonenumberUtil->getMetadataForNonGeographicalRegion($sCountryCallingCode);
				} else {
					$oMetadata = $oPhonenumberUtil->getMetadataForRegion($sRegionCode);
				}

				$aIntlNumberFormats = $oMetadata->intlNumberFormats();

				if(count($aIntlNumberFormats) == 0) {
					$aAvailableFormats = $oMetadata->numberFormats();
				} else {
					$aAvailableFormats = $aIntlNumberFormats;
				}

				/*
				 * Die internationalen Format können abweichen.
				 * Um immer dasselbe Format zu bekommen, wird der String entsprechend manipuliert
				 */
				foreach($aAvailableFormats as $oNumberFormat) {
					if(strpos($oNumberFormat->getFormat(), '-') !== false) {
						$sNewFormat = str_replace('-', ' ', $oNumberFormat->getFormat());
						$sNewFormat = str_replace('/', ' ', $sNewFormat);
						$sNewFormat = str_replace('(', '', $sNewFormat);
						$sNewFormat = str_replace(')', '', $sNewFormat);

						$oNumberFormat->setFormat($sNewFormat);
					}
				}

				$aData = [
					'aAvailableFormats'  => $aAvailableFormats,
					'iPhoneNumberFormat' => \libphonenumber\PhoneNumberFormat::INTERNATIONAL
				];

				System::wd()->executeHook('tc_phone_number_format_hook', $aData);


				$sFormattedNumber = $oPhonenumberUtil->formatByPattern($oPhonenumber, $aData['iPhoneNumberFormat'], $aData['aAvailableFormats']);

				if(!empty($sFormattedNumber)) {
					return $sFormattedNumber;
				}

			} catch (Exception $e) {
				// No valid phone number
			}

		}

		return $sNumber;
	}

	/**
	 * Beschreibung der Validierungstypen
	 *
	 * @return array
	 */
	public static function getValidationDescriptions() {
		$aTypes = array();
		$aTypes['MAIL']					= 'E-Mail';
		$aTypes['IN_ARRAY']				= 'Wert ist in Array vorhanden';
		$aTypes['REGEX']				= 'Regulärer Ausdruck';
		$aTypes['TEXT']					= 'Text';
		$aTypes['NUMERIC']				= 'Nummerische Zahl';
		$aTypes['INT_POSITIVE']			= 'Positive Ganzzahl';
		$aTypes['CTYPT_DIGIT']			= 'Darf nur aus Ziffern bestehen';
		$aTypes['INT_NOTNEGATIVE']		= 'Positive Ganzzahl inkl. (0)';
		$aTypes['FLOAT']				= 'Fließkommazahl';
		$aTypes['FLOAT_POSITIVE']		= 'Positive Fließkommazahl';
		$aTypes['FLOAT_NOTNEGATIVE']	= 'Positive Fließkommazahl inkl. (0.00)';
		$aTypes['DATE']					= 'DB Datum (YYYY-MM-DD)';
		$aTypes['DATE_TIME']			= 'DB Datum und Zeit (YYYY-MM-DD HH:MM:SS)';
		$aTypes['TIME']					= 'Zeit (HH:MM:SS)';
		$aTypes['PHONE']				= 'Telefon';
		$aTypes['PHONE_ITU']			= 'Telefon (+49 221 123456789)';
		$aTypes['DATE_FUTURE']			= 'Zukunftsdatum';
		$aTypes['DATE_PAST']			= 'Vergangenheitsdatum';
		$aTypes['IBAN'] = 'IBAN';

		return $aTypes;
	}

}
