<?php

namespace TsAccounting\Gui2\Selection\Company;

class Columns extends \Ext_Gui2_View_Selection_Abstract
{

	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveField
	 * @param \WDBasic $oWDBasic
	 * @return array
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
	{
		$aReturn = [];

		$oFactory = new \Ext_Gui2_Factory('ts_booking_stack');
		$oBookingstackGui = $oFactory->createGui();

		// Immer alle Columns der GUI holen
		$aColumns = $oBookingstackGui->getColumnList();

		foreach ($aColumns as $oColumn) {
			$aReturn[$oColumn->db_column] = $oColumn->title;
		}

		// Leere Spalten, damit man im Export Lücken füllen kann
		$aReturn['empty'] = \L10N::t('Leere Spalte', \TsAccounting\Gui2\Data\Company::L10N_PATH);
		$aReturn['static'] = \L10N::t('Statischer Inhalt', \TsAccounting\Gui2\Data\Company::L10N_PATH);

		return $aReturn;
	}

}