<?php 

class Ext_TC_Positiongroup_Gui2_Data extends Ext_TC_Gui2_Data {
	
	protected static $sL10NDescription = 'Thebing Core » Positiongroups';
	
	/**
	 * Gibt ein Array mit den Positionstypen zurück
	 *
	 * @param string $sLanguage
	 * @return array
	 */
	public static function getPositionTypes($sLanguage=null) {

		if($sLanguage === null) {
			$sLanguage = Ext_TC_System::getInterfaceLanguage();
		}

		$aPositionTypes = array(
			'course' => Ext_TC_L10N::t('Kurskosten', $sLanguage, self::$sL10NDescription),
			'accommodation' => Ext_TC_L10N::t('Unterkunftskosten', $sLanguage, self::$sL10NDescription),
			'transfer' => Ext_TC_L10N::t('Transferkosten', $sLanguage, self::$sL10NDescription),
			'additionalservice_course' => Ext_TC_L10N::t('Kursbezogene Zusatzkosten (Schule)', $sLanguage, self::$sL10NDescription),
			'additionalservice_accommodation' => Ext_TC_L10N::t('Unterkunftsbezogene Zusatzkosten (Schule)', $sLanguage, self::$sL10NDescription),
			'additionalservice' => Ext_TC_L10N::t('Sonstige Zusatzkosten (Schule)', $sLanguage, self::$sL10NDescription),
			'additionalservice_general' => Ext_TC_L10N::t('Generelle Zusatzkosten', $sLanguage, self::$sL10NDescription),
			'prepay' => Ext_TC_L10N::t('Anzahlungen', $sLanguage, self::$sL10NDescription),
			'provision' => Ext_TC_L10N::t('Provision', $sLanguage, self::$sL10NDescription),
		);

		return $aPositionTypes;
	}

}