<?php

class Ext_TS_Numberrange_Accommodation extends Ext_TS_NumberRange {

	protected $_sNumberTable = 'ts_accommodations_numbers';
	protected $_sNumberField = 'number';

	/**
	 * Liefert das Numberrange-Objekt, welches für diese Klasse benutzt wird
	 * Dieses wird zentral bei den Nummernkreisen eingestellt.
	 * @static
	 * @return Ext_TC_NumberRange|null
	 */
	public static function getObject(\WDBasic $oEntity) {

		$oConfig = \Factory::getInstance('Ext_TC_Config');
		$mAccommodationNumberRange = $oConfig->getValue('ts_accommodations_numbers');
		$mReturn = null;

		// Auf Ziffer prüfen
		if(is_numeric($mAccommodationNumberRange)) {
			$oNumberRange = self::getInstance($mAccommodationNumberRange);
			if($oNumberRange->id != 0) {
				$mReturn = $oNumberRange;
			}
		}

		return $mReturn;

	}

	public static function checkPossibility(\Ext_Thebing_Accommodation $oEntity, string $sNumber): bool {

		$oExisting = Ext_Thebing_Accommodation::getRepository()
			->findByNumber($sNumber);

		if(
			$oExisting &&
			$oExisting->getId() !== $oEntity->getId()
		) {
			return false;
		}

		return true;
	}

}
