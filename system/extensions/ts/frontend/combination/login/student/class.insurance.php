<?php


class Ext_TS_Frontend_Combination_Login_Student_Insurance extends Ext_TS_Frontend_Combination_Login_Student_Abstract {

	protected function _setData(){
		
		$oInquiry		= $this->_getInquiry();
		$oSchool		= $oInquiry->getSchool();
		$oCustomer		= $this->getCustomer();
		
		$oFormDate = new Ext_Thebing_Gui2_Format_Date(false, $oSchool->id);
		
		$aInsurances = $oInquiry->getInsurancesList();

		$aInsurancesDD = Ext_Thebing_Insurances_Gui2_Insurance::getInsurancesListForInbox(true, $oSchool->id);

		$aTemp = $aTypes = array();
		foreach((array)$aInsurancesDD as $aInsurance) {
			$aTemp[$aInsurance['id']]	= $aInsurance['title'];
			$aTypes[$aInsurance['id']]	= $aInsurance['payment'];
		}

		
		//Form
		$oForm = new Ext_TS_Frontend_Combination_Login_Student_Form($this);
		
		foreach((array)$aInsurances as $iKey => $aInsurance) {
			$oForm->addRow('input', 'Insurance', $aTemp[$aInsurance['insurance_id']], array('readonly' => true));
			$oForm->addRow('input', 'From', $oFormDate->format($aInsurance['from']), array('readonly' => true));
			$oForm->addRow('input', 'End', $oFormDate->format($aInsurance['until']), array('readonly' => true));
			$oForm->addLine();
		}
		
		$this->_assign('sInsuranceDetails', (string)$oForm);

		
		
		
		$this->_setTask('showInsuranceData');
	}
}
?>
