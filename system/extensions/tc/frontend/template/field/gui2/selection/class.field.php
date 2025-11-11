<?php
/**
 * Selection für »Feld« im Tab Einstellungen
 */
class Ext_TC_Frontend_Template_Field_Gui2_Selection_Field extends Ext_Gui2_View_Selection_Abstract
{

	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveField
	 * @param WDBasic $oWDBasic
	 * @return array
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$sArea		= $oWDBasic->area;
		
		$aOptions	= array();
		
		switch ($sArea) {
			case 'standard':
				

				break;
			case 'checkbox':
				

				break;
			case 'individual':


				break;
		}
		
		

		return $aOptions;

	}

}