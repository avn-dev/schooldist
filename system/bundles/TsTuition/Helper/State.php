<?php

namespace TsTuition\Helper;

class State {

	const KEY_STRING = 0;

	const KEY_BINARY = 1;

	const BINARY_MAPPING = [
		\Ext_TS_Inquiry_TuitionIndex::STATE_NEW => 'N', // 1
		\Ext_TS_Inquiry_TuitionIndex::STATE_CONTINUOUS => 'C', // 2
		\Ext_TS_Inquiry_TuitionIndex::STATE_LAST => 'L', // 4
		\Ext_TS_Inquiry_TuitionIndex::STATE_VACATION => 'V', // 8
		\Ext_TS_Inquiry_TuitionIndex::STATE_VACATION_RETURN => 'VR', // 18
		\Ext_TS_Inquiry_TuitionIndex::STATE_CLASS_CHANGE => 'CC' // 32
	];

	/**
	 * @var int
	 */
	private $iType;

	/**
	 * @var \Tc\Service\LanguageAbstract
	 */
	private $oLanguage;

	/**
	 * @param int $iType KEY_STRING / KEY_BINARY
	 * @param \Tc\Service\LanguageAbstract $oLanguage
	 */
	public function __construct($iType, \Tc\Service\LanguageAbstract $oLanguage) {
		$this->iType = $iType;
		$this->oLanguage = $oLanguage;
	}

	/**
	 * @param bool $bCourseStates
	 * @return string[]
	 */
	public function getOptions($bCourseStates = true, $bAbbreviation = false) {

		$aOptions = [];
		$aTranslations = $this->getTranslations();

		foreach(self::BINARY_MAPPING as $iState => $sState) {

			$sLabel = $bAbbreviation ? $sState.' - '.$aTranslations[$iState] : $aTranslations[$iState];
			if($this->iType === self::KEY_STRING) {
				$aOptions[$sState] = $sLabel;
			} elseif($this->iType === self::KEY_BINARY) {
				// TODO Momentan sorgt KEY_BINARY dafür, dass sich nicht nur der Schlüssel ändert
				$aOptions[$iState] = $sLabel;
			}

		}

		if(!$bCourseStates) {
			// Klassenwechsel gibt es bei Buchungen nicht
			unset($aOptions[\Ext_TS_Inquiry_TuitionIndex::STATE_CLASS_CHANGE]);
			unset($aOptions[self::BINARY_MAPPING[\Ext_TS_Inquiry_TuitionIndex::STATE_CLASS_CHANGE]]);
		}

		return $aOptions;

	}

	/**
	 * @return array
	 */
	private function getTranslations() {
		return [
			\Ext_TS_Inquiry_TuitionIndex::STATE_NEW => $this->oLanguage->translate('Neu'),
			\Ext_TS_Inquiry_TuitionIndex::STATE_CONTINUOUS => $this->oLanguage->translate('Fortführend'),
			\Ext_TS_Inquiry_TuitionIndex::STATE_LAST => $this->oLanguage->translate('Letzte Woche'),
			\Ext_TS_Inquiry_TuitionIndex::STATE_VACATION => $this->oLanguage->translate('Urlauber'),
			\Ext_TS_Inquiry_TuitionIndex::STATE_VACATION_RETURN => $this->oLanguage->translate('Urlaubs-Rückkehrer'),
			\Ext_TS_Inquiry_TuitionIndex::STATE_CLASS_CHANGE => $this->oLanguage->translate('Klassenwechsel'),
		];
	}

}