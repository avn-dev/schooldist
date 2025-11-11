<?php


class Ext_TS_Frontend_Combination_Login_Student_School extends Ext_TS_Frontend_Combination_Login_Student_Abstract {

	protected function _setData(){
		
		$oInquiry = $this->_getInquiry();
		
		if($oInquiry instanceof Ext_TS_Inquiry){
			$oSchool = $oInquiry->getSchool();

			$oFormatCountry = new Ext_Thebing_Gui2_Format_Country();
			
			$this->_assign('sSchoolAddress',			$oSchool->address);
			$this->_assign('sSchoolAddressAdditional',	$oSchool->address_addon);
			$this->_assign('sSchoolZip',				$oSchool->zip);
			$this->_assign('sSchoolCity',				$oSchool->city);
			$this->_assign('sSchoolCountry',			$oFormatCountry->format($oSchool->country_id));
			$this->_assign('sSchoolUrl',				$oSchool->url);
			$this->_assign('sSchoolPhone1',				$oSchool->phone_1);
			$this->_assign('sSchoolPhone2',				$oSchool->phone_2);
			$this->_assign('sSchoolFax',				$oSchool->fax);
			$this->_assign('sSchoolMail',				$oSchool->email);
			
			

		}
		
		$this->_setTask('showSchoolData');
	}
}
?>
