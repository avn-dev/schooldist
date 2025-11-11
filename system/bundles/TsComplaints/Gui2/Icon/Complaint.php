<?php

namespace TsComplaints\Gui2\Icon;

use \TcComplaints\Gui2\Icon\Complaint as TcComplaint_Gui2_Icon_Complaint;
use \TcComplaints\Entity\Complaint as TcComplaints_Entity_Complaint;

class Complaint extends TcComplaint_Gui2_Icon_Complaint {

	/**
	 * @var bool
	 */
	private $bUseInboxIconClass = true;

	/**
	 * @param bool $bUseInboxIconClass
	 */
	public function __construct($bUseInboxIconClass = true) {
		$this->bUseInboxIconClass = (bool)$bUseInboxIconClass;
	}

	/**
	 * Überprüft, ob das Beschwerde-Icon aktiv sein soll, oder nicht
	 *
	 * @param array $aSelectedIds
	 * @param array $aRowData
	 * @param \Ext_Gui2_Bar_Icon $oElement
	 * @return int
	 */
	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if(
			!empty($aSelectedIds[0]) && (
				$oElement->action === 'accommodation_complaint' ||
				$oElement->action === 'teacher_complaint' ||
				$oElement->action === 'transfer_complaint'
			)
		) {

			$sArea = str_replace('_complaint', '', $oElement->action);
			$oComplaintRepository = TcComplaints_Entity_Complaint::getRepository();
			$bHasComplaints = $oComplaintRepository->haveComplaint($aSelectedIds[0], $sArea);

			return (int)$bHasComplaints;
		}

		if($this->bUseInboxIconClass) {
			$oIconStatus = new \Ext_Thebing_Gui2_Icon_Inbox();
			return $oIconStatus->getStatus($aSelectedIds, $aRowData, $oElement);
		}

		return parent::getStatus($aSelectedIds, $aRowData, $oElement);

	}

}
