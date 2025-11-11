<?php

class Ext_TS_Document_Gui2_Icon_Active extends Ext_Gui2_View_Icon_Abstract {

	/**
	 * @inheritdoc
	 */
	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		// Bei NCG wurden doch tatsächlich docx importiert, die beim Print-Dialog logischerweise nicht funktionieren
		if($oElement->action === 'print_invoice') {
			foreach($aRowData as $aRow) {
				if(strpos($aRow['pdf_path_original'], '.pdf') === false) {
					return false;
				}
			}
		} elseif($oElement->action === 'convertProformaDocument') {
			if(empty($aSelectedIds)) {
				return false;
			}
			foreach ($aRowData as $aRow) {
				$oInquiry = Ext_TS_Inquiry::getInstance($aRow['inquiry_id']);
				if (
					!$oInquiry->isConfirmed() ||
					$oInquiry->hasDraft()
				) {
					return false;
				}
			}


			// Eine Gruppe ODER einzelne Rechnungen #13366
			$mGroupId = false;
			foreach($aRowData as $aRow) {
				if($mGroupId === false) {
					$mGroupId = $aRow['group_id'];
				}
				if($mGroupId !== $aRow['group_id']) {
					return false;
				}
			}

		} else if ($oElement->action === 'communication') {
			if (empty($aSelectedIds)) {
				return false;
			}
		}

		return parent::getStatus($aSelectedIds, $aRowData, $oElement);

	}

}
