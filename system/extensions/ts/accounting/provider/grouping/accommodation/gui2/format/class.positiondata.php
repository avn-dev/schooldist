<?php

class Ext_TS_Accounting_Provider_Grouping_Accommodation_Gui2_Format_PositionData extends Ext_TS_Accounting_Provider_Grouping_Gui2_Format_PositionData {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		$sReturn = '';
		$oPayment = Ext_Thebing_Accommodation_Payment::getInstance($aResultData['id']);
		$oDummy = null;

		if(
			$oColumn->db_column === 'customer_id' ||
			$oColumn->db_column === 'customer_number'
		) {

			if($aResultData['select_type'] !== 'month') {
				$oContact = Ext_TS_Inquiry_Contact_Traveller::getInstance($aResultData['customer_id']);

				if($oColumn->db_column === 'customer_id') {
					$oFormat = new Ext_TC_Gui2_Format_Name();
					$oDummy = null;
					$aData = array(
						'lastname' => $oContact->lastname,
						'firstname' => $oContact->firstname
					);

					$sReturn = $oFormat->format(null, $oDummy, $aData);
				} else {
					$sReturn = $oContact->getCustomerNumber();
				}
			}

		} elseif(
			$oColumn->db_column === 'comment' ||
			$oColumn->db_column === 'payed_additional_comment'
		) {
			$sReturn = $this->_getCommentData($oColumn->db_column, $oPayment);
		} elseif($oColumn->db_column === 'group_id') {
			$oInquiry = Ext_TS_Inquiry::getInstance($aResultData['inquiry_id']);
			$oGroup = $oInquiry->getGroup();
			if($oGroup->id > 0) {
				$sReturn = $oGroup->getShortName();
			}
		}

		return $sReturn;
	}

	public function getTitle(&$oColumn = null, &$aResultData = null) {
		$aReturn = array();

		if($oColumn->db_column === 'group_id') {
			$oInquiry = Ext_TS_Inquiry::getInstance($aResultData['inquiry_id']);
			$oGroup = $oInquiry->getGroup();
			if($oGroup->id > 0) {
				$aReturn['content'] = $oGroup->getName();
				$aReturn['tooltip'] = true;
			}
		}

		return $aReturn;
	}
}