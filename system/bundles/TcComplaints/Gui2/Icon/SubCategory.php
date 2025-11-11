<?php

namespace TcComplaints\Gui2\Icon;

use \Ext_Gui2_View_Icon_Abstract;
use TcComplaints\Entity\SubCategory as TcComplaints_Entity_SubCategory;
use TcComplaints\Entity\Complaint as TcComplaints_Entity_Complaint;

class SubCategory extends Ext_Gui2_View_Icon_Abstract {

	/**
	 * Überprüft, ob das Delete-Icon in der Unterkategorie aktiv sein soll, oder nicht.
	 *
	 * @param array $aSelectedIds
	 * @param array $aRowData
	 * @param \Ext_Gui2_Bar_Icon $oElement
	 * @return int
	 */
	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if($oElement->task === 'deleteRow' && !empty($aSelectedIds[0])) {

			$oSubCategory = TcComplaints_Entity_SubCategory::getInstance($aSelectedIds[0]);
			$oComplaintRepository = TcComplaints_Entity_Complaint::getRepository();
			$aComplaints = $oComplaintRepository->getAllComplaintsPerSubCategoryId($oSubCategory);

			// Erste Beschwerde wird kontrolliert
			$oComplaint = reset($aComplaints);

			// Wenn die Beschwerde die gleiche Kategorie Id hat, dann darf die Kategorie nicht gelöscht werden!
			if((int)$oComplaint->sub_category_id === $oSubCategory->getId()) {
				return 0;
			} else {
				return 1;
			}
		} elseif(
			$oElement->action === 'new' ||
			(
				$oElement->action === '' &&
				$oElement->task !== 'deleteRow'
			)

		) {
			return 1;
		} elseif($oElement->action === 'edit' && !empty($aSelectedIds[0])) {
			return 1;
		}

		return 0;

	}

}