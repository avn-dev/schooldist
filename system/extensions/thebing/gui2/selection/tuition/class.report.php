<?php

class Ext_Thebing_Gui2_Selection_Tuition_Report extends Ext_Gui2_View_Selection_Abstract {

	static public function getAllOptions() {
		
		$options = array(
			0 => '',
			1 => L10N::t('Klasse', Ext_Thebing_Tuition_Report_Gui2::$_sDescription),
			2 => L10N::t('Lehrer', Ext_Thebing_Tuition_Report_Gui2::$_sDescription),
			3 => L10N::t('Schüler', Ext_Thebing_Tuition_Report_Gui2::$_sDescription),
			4 => L10N::t('Block', Ext_Thebing_Tuition_Report_Gui2::$_sDescription),
			5 => L10N::t('Tag', Ext_Thebing_Tuition_Report_Gui2::$_sDescription)
		);
		
		return $options;
	}
	
	/**
	 * Get the options
	 *
	 * @param array $aSelectedIds
	 * @param array $aSaveField
	 * @param object $oWDBasic
	 * @return array
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
	{
		
		$aOptions = self::getAllOptions();

		switch($oWDBasic->group_by)
		{
			case 1: // Klasse
				unset($aOptions[0], $aOptions[1], $aOptions[4]);
				break;
			case 9: // Block
				unset($aOptions[0], $aOptions[1], $aOptions[4], $aOptions[5]);
				break;
			case 2: // Lehrer
				unset($aOptions[0], $aOptions[2], $aOptions[4], $aOptions[5]);
				break;
			case 3: // Raum
				unset($aOptions[0], $aOptions[1], $aOptions[3], $aOptions[5]);
				break;
			case 4: // Kurs
				unset($aOptions[0], $aOptions[3], $aOptions[4], $aOptions[5]);
				break;
			case 5: // Gebäude
			case 6: // Etage
			case 7: // Schüler
				unset($aOptions[0], $aOptions[1], $aOptions[2], $aOptions[5]);
				break;
			case 8: // Niveau
				unset($aOptions[0], $aOptions[3], $aOptions[4], $aOptions[5]);
				break;
		}

		return $aOptions;
	}

}
