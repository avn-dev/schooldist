<?php

/**
 * @TODO Diese Klasse hat NICHTS mit den Zusatzgebühren zu tun, wird aber bei den Steuerzuweisungen für diese verwendet
 */
class Ext_Thebing_School_Cost extends Ext_Thebing_School {
	
	protected $_iSaisonId = 0;
	protected $_iCurrencyId = 0;

	/**
	 * @param int $iSeasonId
	 * @param int $iCurrencyId
	 */
	public function setSeasonIdAndCurrencyId($iSeasonId, $iCurrencyId) {
		$this->_iSaisonId = $iSeasonId;
		$this->_iCurrencyId = $iCurrencyId;
	}

	public function getName($sLang = ''){
		
		if(empty($sLang)) {
			$oSchool = Ext_Thebing_School::getInstance((int)$this->getSchoolId());
			$sLang = $oSchool->getInterfaceLanguage();
		}

		$sName = $this->_aData['name_'.$sLang];

		return $sName;

	}
	
}