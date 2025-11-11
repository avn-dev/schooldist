<?php

use \TcComplaints\Entity\Complaint as TcComplaints_Entity_Complaint;

class Ext_Thebing_Pickup_Company_Gui2_Icon_Active extends Ext_Thebing_Pickup_Service_Gui2_Icon_Active {

	/**
	 * @param array $aSelectedIds
	 * @param array $aRowData
	 * @param object $oElement
	 * @return int
	 */
	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if($oElement->action == 'transfer_complaint') {

			$sArea = str_replace('_complaint', '', $oElement->action);
			/** @var \TcComplaints\Entity\ComplaintRepository $oComplaintRepository */
			$oComplaintRepository = TcComplaints_Entity_Complaint::getRepository();
			$bHasComplaint = $oComplaintRepository->haveComplaint($aSelectedIds[0], $sArea);

			if(!$bHasComplaint) {
				return 0;
			}

		}

		return parent::getStatus($aSelectedIds, $aRowData, $oElement);
	}


	/**
	 * Holt den aktuellen Transferanbieter
	 *
	 * @param array $aRowData
	 * @return Ext_Thebing_Pickup_Company
	 */
	protected function _getObject($aRowData) {

		$iTransferCompanyId = (int) $aRowData['id'];
		$oTransferCompany = Ext_Thebing_Pickup_Company::getInstance($iTransferCompanyId);
		
		return $oTransferCompany;
	}
	
	/**
	 * Holt alle Journey-Transfers für das jeweilige Objektes
	 *
	 * @param Ext_Thebing_Pickup_Company $oTransferCompany
	 * @return array
	 */
	protected function _getJourneyTransfers($oTransferCompany) {		
		$aJourneyTransfers = $oTransferCompany->getInquiryJourneyTransfers();
		return $aJourneyTransfers;
	}
	
}