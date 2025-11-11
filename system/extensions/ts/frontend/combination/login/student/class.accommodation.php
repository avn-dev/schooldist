<?php


class Ext_TS_Frontend_Combination_Login_Student_Accommodation extends Ext_TS_Frontend_Combination_Login_Student_Abstract {

	protected function _setData(){
		
		$oInquiry	= $this->_getInquiry();
		
		if(is_object($oInquiry)){
			
			$sLanguage = $this->_getLanguage();
			
			$oSchool = $oInquiry->getSchool();
			

			$aAllocations = $oInquiry->getAllocations();
			
			if(!empty($aAllocations)){
				$oAllocation = reset($aAllocations);
				
				
				$oProvider = $oAllocation->getAccommodationProvider();
				
				$this->_assign('sAccommodationName', $oProvider->name);
				$this->_assign('sAccommodationStreet', $oProvider->street);
				$this->_assign('sAccommodationZip', $oProvider->zip);
				$this->_assign('sAccommodationCity', $oProvider->city);
				
				$this->_assign('sAccommodationPhone', $oProvider->phone);
				$this->_assign('sAccommodationMail', $oProvider->email);
				$this->_assign('sAccommodationDescription', $oProvider->getFamilyDescription($sLanguage));
				
				// Dokumente
				$aUploads = $oProvider->getUploadedPDFs($sLanguage, false);
				
				$sTable = '<table>';
				foreach((array)$aUploads as $oUpload){
					
					$sFile = '../storage/accommodation/'.$oUpload->filename;
			
					$sTable .= '<tr>';
					$sTable .= '<td style="width: 30px;">';
					$sTable .= '<a type="application/pdf" target="_blank" href="'.$sFile.'"><img style="margin-top: 2px;" src="../icef_login/page_white_acrobat.png"/></a>';
					$sTable .= '</td>';
					$sTable .= '<td><a type="application/pdf" target="_blank" href="'.$sFile.'">';
					$sTable .= htmlspecialchars($oUpload->description);
					$sTable .= '</a></td>';
					$sTable .= '</tr>';
				}
				
				// Bilder
				$aUploads = $oProvider->getUploadedImages(false);
				
				foreach((array)$aUploads as $oUpload){
					
					$sFile = '../storage/accommodation/'.$oUpload->filename;
			
					$sTable .= '<tr>';
					$sTable .= '<td style="width: 30px;">';
					$sTable .= '<a type="application/pdf" target="_blank" href="'.$sFile.'"><img style="margin-top: 2px;" src="../icef_login/jpg.png"/></a>';
					$sTable .= '</td>';
					$sTable .= '<td><a type="application/pdf" target="_blank" href="'.$sFile.'">';
					$sTable .= htmlspecialchars($oUpload->description);
					$sTable .= '</a></td>';
					$sTable .= '</tr>';
				}
				$sTable .= '</table>';
				
				$this->_assign('sAccommodationDocuments', $sTable);
				
			}
		}
		
		
		$this->_setTask('showAccommodationData');
	}
	
}
?>
