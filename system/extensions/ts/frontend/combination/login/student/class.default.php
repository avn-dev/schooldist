<?php


class Ext_TS_Frontend_Combination_Login_Student_Default extends Ext_TS_Frontend_Combination_Login_Student_Abstract {

	
	protected function _setData(){

		$oInquiry = $this->_getInquiry();

		$this->_setTask('showIndexData');
				
		if($oInquiry instanceof Ext_TS_Inquiry){
			$oSchool = $oInquiry->getSchool();
			$oCustomer = $oInquiry->getCustomer();
			$sLanguage = $this->_getLanguage();

			
			$this->_assign('sFirstname', $oCustomer->firstname);
			$this->_assign('sSurname', $oCustomer->lastname);
			
			$iFirstCourseStart = $oInquiry->getFirstCourseStart(true);
			
			$iStartCounter = 0;

			if($iFirstCourseStart > 0){

				$oDateStart = new WDDate($iFirstCourseStart);
		
				$iDiff = $oDateStart->getDiff(WDDate::DAY, time(), WDDate::TIMESTAMP);
				
				if($iDiff > 0){
					$iStartCounter = (int)$iDiff;
				}
			}
			
			
			$this->_assign('iCourseCounter', $iStartCounter);
			
			$aCourses = $oInquiry->getCourses(true);
			$aAccommodations = $oInquiry->getAccommodations(true);
			$aTransfers = $oInquiry->getTransfers();
	
			$sTable = '<table>';

			foreach((array)$aCourses as $oCourse){
				$sTable .= '<tr>';
				$sTable .= '<td style="width: 30px;">';
				$sTable .= '</td>';
				$sTable .= '<td>';
				$sTable .= $oCourse->getInfo($oSchool->id, $sLanguage);
				$sTable .= '</td>';
				$sTable .= '</tr>';
			}
			
			foreach((array)$aAccommodations as $oAccommodation){
				$sTable .= '<tr>';
				$sTable .= '<td style="width: 30px;">';
				$sTable .= '<img src="../icef_login/32_ac_accommodation.png"/>';
				$sTable .= '</td>';
				$sTable .= '<td>';
				$sTable .= $oAccommodation->getInfo($oSchool->id, $sLanguage);
				$sTable .= '</td>';
				$sTable .= '</tr>';
			}
			
			foreach((array)$aTransfers as $oTransfer){
				$sTable .= '<tr>';
				$sTable .= '<td style="width: 30px;">';
				$sTable .= '<img src="../icef_login/pickup.png"/>';
				$sTable .= '</td>';
				$sTable .= '<td>';
				$sTable .= $oTransfer->getName(null, 1, $sLanguage);
				$sTable .= '</td>';
				$sTable .= '</tr>';
			}
			
			$sTable .= '</table>';

			$this->_assign('sBookingOverview', $sTable);
			
		}

	}

	
}
?>
