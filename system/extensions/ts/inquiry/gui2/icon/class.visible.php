<?php


class Ext_TS_Inquiry_Gui2_Icon_Visible extends Ext_Gui2_View_Icon_Abstract{

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if(
			$oElement->action === 'communication' &&
			$oElement->additional === 'booking'
		) {
			// Icon soll doch immer sichtbar sein, wegen den Notizen
//			if(Ext_Thebing_Client::hasStudentApp()) {
				return 1;
//			}
			
//			return 0;
			
		} elseif(empty($aRowData)) {
			if(
				$oElement->action == 'confirm-arrival-undo' ||
				$oElement->action == 'confirm-departure-undo'
			) {
				return 0;
			} else {
				return 1;
			}
		} else {
			foreach($aRowData as $aRow) {

				if(
					(
						$oElement->action == 'confirm-arrival' &&
						!empty($aRow['checkin'])
					) ||
					(
						$oElement->action == 'confirm-departure' &&
						!empty($aRow['checkout'])
					) ||
					(
						$oElement->action == 'confirm-arrival-undo' &&
						empty($aRow['checkin'])
					) ||
					(
						$oElement->action == 'confirm-departure-undo' &&
						empty($aRow['checkout'])
					)
				) {
					return 0;
				}

			}
		}

		if ($oElement->action == 'change_inbox') {
			$aInboxlist = Ext_Thebing_Client::getInstance()->getInboxList(false, false, true);
			if (count($aInboxlist) > 1) {
				return true;
			} else {
				return false;
			}
		}

		if($oElement->action == 'booking') {

			// Wenn einer der ausgewählten Einträge nicht bestätigt ist, dann Icon anzeigen
			if(!empty($aSelectedIds)) {
				foreach ($aRowData as $oRow) {
					if(!$oRow['confirmed_original']) {
						return 1;
					}
				}
			}

			// Wenn Auto-Confirm aktiv ist, und keiner der gewählten Einträge unbestätigt ist, dann Icon immer ausblenden
			if((int)System::d('booking_auto_confirm') > 0) {
				return 0;
			}
		}

		return parent::getStatus($aSelectedIds, $aRowData, $oElement);
	}

}