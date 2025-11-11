<?php

class Ext_TS_Enquiry_Gui2_Icon_Active extends Ext_Gui2_View_Icon_Abstract {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		// Aus irgendeinem Grund funktioniert das normale Verhalten mit der Mehrfachauswahl nicht mehr
		if(
			$oElement->action === 'new' ||
			$oElement->task === 'export_csv' ||
			$oElement->task === 'export_excel' ||
			$oElement->action === 'import'
		) {
			return true;
		}

		if (empty($aSelectedIds)) {
			return false;
		}

		// Umgewandelte Anfragen oder welche, für die ein Angebot erstellt wurde, dürfen nicht mehr gelöscht oder umgewandelt werden
		if (
			(
				$oElement->task === 'deleteRow' ||
				$oElement->action === 'convert_enquiry_to_inquiry'
			) && (
				collect($aRowData)->some(function (array $aRow) {
					return
						in_array('offer_created', $aRow['invoice_status']) ||
						in_array('enquiry_converted', $aRow['invoice_status']);
				})
			)
		) {
			return false;
		}

		return true;

	}

}
