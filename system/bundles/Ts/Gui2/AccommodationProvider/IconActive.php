<?php

namespace Ts\Gui2\AccommodationProvider;

class IconActive extends \TsComplaints\Gui2\Icon\Complaint {
	
	public function __construct($bUseInboxIconClass = true) {
		parent::__construct(false);
	}
	
	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {
		
		if(
			$oElement->action == 'new' ||
			$oElement->task == 'export_csv' ||
			$oElement->task == 'export_excel' ||
			$oElement->action == 'import'
		) {
			return true;
		}

		// Nur bei Kommunikation dürfen mehrer Einträge ausgewählt sein
		if(
			count($aSelectedIds) > 1 &&
			$oElement->action !== 'communication' &&
			$oElement->task !== 'deleteRow'
		) {
			return 0;
		}
		
		$iStatus = parent::getStatus($aSelectedIds, $aRowData, $oElement);
		
		return $iStatus;
	}
	
}
