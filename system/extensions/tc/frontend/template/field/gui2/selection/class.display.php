<?php
/**
 * Selection für »Darstellung« im Tab Einstellungen
 */
class Ext_TC_Frontend_Template_Field_Gui2_Selection_Display extends Ext_Gui2_View_Selection_Abstract
{
	
	private static $aInputTypesCache = null;
	
	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveField
	 * @param WDBasic $oWDBasic
	 * @return array
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aOptions = self::getInputTypes();

		return $aOptions;

	}
	
	public static function getInputTypes() {
		
		if(self::$aInputTypesCache === null) {
		
			self::$aInputTypesCache = array(
				'birthdate_date' => L10N::t('Geburtsdatum - Datumsfeld'),
				'birthdate_select' => L10N::t('Geburtsdatum - Selectfelder'), 
				'location_select' => L10N::t('Kursort nach Land gruppiert'), 
				'checkbox' =>  L10N::t('Checkboxen'),
				'checkbox_text' => L10N::t('Checkbox mit Text'),
				'date' => L10N::t('Datumsfeld'),
				'time' => L10N::t('Uhrzeit'),
				'input' => L10N::t('einzeiliges Eingabefeld'),
				'textarea' => L10N::t('mehrzeiliges Eingabefeld'),
				'referrer' => L10N::t('Wie sind die auf uns Aufmerksam geworden'),
				'select' => L10N::t('Select'),
				'select_grouped' => L10N::t('Gruppiertes Select'),
				'select_grouped_date' => L10N::t('Gruppiertes Datums-Select'),
				'radio' => L10N::t('Radio-Buttons'),
				'phone' => L10N::t('Telefonnummer'),
				'additionalservices' => L10N::t('Checkboxen'),
				'additionalservices_select' => L10N::t('Select'),
				'additionalservices_quantity' => L10N::t('Anzahl'),
				'catalogues' => L10N::t('Katalogauswahl'),
				'reference_number' => L10N::t('einzeiliges Eingabefeld'),
				'multiselect' => L10N::t('Mehrfachauswahl')
			);
			
		}

		return self::$aInputTypesCache;
	}
	
}