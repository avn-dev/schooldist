<?php

namespace TcComplaints\Gui2\Icon;

use \Ext_Gui2_View_Icon_Abstract;
use TcComplaints\Entity\Category as TcComplaints_Entity_Category;
use TcComplaints\Entity\Complaint as TcComplaints_Entity_Complaint;

class Category extends Ext_Gui2_View_Icon_Abstract {

	/**
	 * Überprüft, ob das Delete-Icon in der Kategorie aktiv sein soll, oder nicht
	 *
	 * @param array $aSelectedIds
	 * @param array $aRowData
	 * @param \Ext_Gui2_Bar_Icon $oElement
	 * @return int
	 */
	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if($oElement->task === 'deleteRow' && !empty($aSelectedIds[0])) {

			$oCategory = TcComplaints_Entity_Category::getInstance($aSelectedIds[0]);
			$oComplaintRepository = TcComplaints_Entity_Complaint::getRepository();
			$aComplaints = $oComplaintRepository->getAllComplaintsPerCategoryId($oCategory);

			// Man muss nur die erste Beschwerde gecheckt werden
			$oComplaint = reset($aComplaints);

			// Wenn die Beschwerde die gleiche Kategorie Id hat, dann darf die Kategorie nicht gelöscht werden!
			if ((int)$oComplaint->category_id === $oCategory->getId()) {
				return 0;
			} else {
				return 1;
			}
			// Neuer Eintrag
		} elseif(
			$oElement->action === 'new' &&
			$oElement->task === 'openDialog'
		) {
			return 1;
		// Editieren - Löschen - Deaktivieren - Duplizieren
		} elseif(
			!empty($aSelectedIds) &&
			(
				(
					$oElement->task === 'openDialog' &&
					$oElement->action === 'edit'
				) ||
				(
					$oElement->task === 'deleteRow' &&
					$oElement->action === ''
				) ||
				(
					$oElement->task === 'openDialog' &&
					$oElement->action === 'edit' &&
					$oElement->additional === 'deactivate'
				) ||
				(
					$oElement->task === 'createCopy'
				)
			)
		) {
			return 1;
		}

		return 0;

	}

}