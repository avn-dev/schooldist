<?php

class Ext_Thebing_School_Gui2_Selection_SepaColumns extends Ext_Gui2_View_Selection_Abstract {

	/**
	 * Beinhaltet alle Colums die nicht angezeigt werden dürfen
	 * 
	 * @var array
	 */
	private	$aNonVisibleColumns = [
		'file',
		'processed',
		'id',
	];
	
	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveField
	 * @param WDBasic $oWDBasic
	 * @return array
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aBack = [];

		$sExportType = $oWDBasic->sepa_export_per;
		if($sExportType !== '') {

			$oFactory = new Ext_Gui2_Factory('ts_accounting_provider_payment_overview_accommodation');
			$oPaymentOverviewAccommodationGui = $oFactory->createGui();
			$aColumns = $oPaymentOverviewAccommodationGui->getColumnList();

			if($sExportType === 'payment_entry') {

				$oFactory = new Ext_Gui2_Factory('ts_accounting_provider_payment_overview_accommodation_positions');
				$oPaymentOverviewAccommodationPositionsGui = $oFactory->createGui();
				$aSecondColumns = $oPaymentOverviewAccommodationPositionsGui->getColumnList();

				$aColumns = array_merge($aColumns, $aSecondColumns);

			}

			$aColumns = $this->deleteNonVisibleColumns($aColumns);
			
			foreach($aColumns as $oColumn) {
				$aBack[$oColumn->db_column] = $oColumn->title;
			}

			#$aBack['purpose_of_payment'] = 'Verwendungszweck';

		}

		return $aBack;

	}

	/**
	 * Löscht alle nicht zu sehenden Spalten.
	 *
	 * @param array $aRows
	 * @return array
	 */
	private function deleteNonVisibleColumns(array $aRows) {
		
		foreach($aRows as $iKey => $oColumn) {
			if(in_array($oColumn->db_column, $this->aNonVisibleColumns)) {
				unset($aRows[$iKey]);
			}
		}
		
		return $aRows;
	}

}