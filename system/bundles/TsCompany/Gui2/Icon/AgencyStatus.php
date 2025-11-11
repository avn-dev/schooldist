<?php

namespace TsCompany\Gui2\Icon;

class AgencyStatus extends \Ext_Gui2_View_Icon_Abstract {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if(
			$oElement->action == 'new' ||
			$oElement->task == 'export_csv' ||
			$oElement->task == 'export_excel' ||
			$oElement->action == 'import'
		) {
			return true;
		}

		if(count($aSelectedIds) > 0) {

			$aAgencyData = reset($aRowData);
			$oAgency = \Ext_Thebing_Agency::getInstance((int) $aAgencyData['id']);

			// Agenturen die einer Buchung zugewiesen sind dürfen nicht gelöscht werden
			if(
				$oElement->task == 'deleteRow' &&
				\Ext_Thebing_Agency::getRepository()->hasInquiries($oAgency) === true
			) {
				return false;
			}

			return true;
		}

		return false;
	}
}
