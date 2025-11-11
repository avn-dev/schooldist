<?php

namespace TcComplaints\Gui2\Icon;

use \Ext_Gui2_View_Icon_Abstract;
use \TcComplaints\Entity\ComplaintHistory as TcComplaints_Entity_ComplaintHistory;
use \TcComplaints\Entity\Complaint as TcComplaints_Entity_Complaint;

class ComplaintHistory extends Ext_Gui2_View_Icon_Abstract {

	/**
	 * Überprüft, ob das Beschwerde-Icon aktiv sein soll, oder nicht.
	 *
	 * @param array $aSelectedIds
	 * @param array $aRowData
	 * @param \Ext_Gui2_Bar_Icon $oElement
	 * @return int
	 */
	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if($oElement->task === 'deleteRow' && !empty($aRowData[0]['id'])) {

			// Man braucht nur einmal eine Instance von einem Beschwerdekommentar zu holen,
			// da alle Kommentare die in $aRowData stehen zu einer Beschwerde gehören.
			$oComplaintHistory = TcComplaints_Entity_ComplaintHistory::getInstance($aRowData[0]['id']);
			$oComplaint = TcComplaints_Entity_Complaint::getInstance($oComplaintHistory->complaint_id);

			// Alle Kommentar zu den Beschwerden holen
			$aComplaintHistory = TcComplaints_Entity_ComplaintHistory::getAllComplaintsCommentsViaComplaint($oComplaint);

			$oComplaintHistoryFirst = reset($aComplaintHistory);
			$oComplaintHistoryEnd = end($aComplaintHistory);

			// Wenn die Ids gleich sind ist der erste Kommentar auch der letzte,
			// dann darf dieser nicht löschbar sein.
			if (
				$oComplaintHistoryFirst->id === $oComplaintHistoryEnd->id ||
				$oComplaintHistoryEnd->id > (int)$aSelectedIds[0]
			) {
				return 0;
			} else {
				return 1;
			}
		} elseif(
			$oElement->action === 'new' ||
			$oElement->action === 'feedback' ||
			$oElement->action === ''
		) {
			return 1;
		} if($oElement->action === 'edit' && !empty($aSelectedIds[0])) {
			return 1;
		}

		return 0;

	}

}