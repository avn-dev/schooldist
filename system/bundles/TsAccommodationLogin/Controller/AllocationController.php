<?php

namespace TsAccommodationLogin\Controller;

class AllocationController extends InterfaceController {

	public function profilePicture($allocationId) {
		
		$allocation = \Ext_Thebing_Accommodation_Allocation::getInstance($allocationId);
  
		$provider = $allocation->getAccommodationProvider();
		
		if($this->_oAccess->id != $provider->id) {
			die();
		}
		
		$inquiry = $allocation->getInquiry();
		$student = $inquiry->getTraveller();
					
		$aInfo = [
			1 => 1,
			2 => $student->getPhoto()
		];
		
		$oImageBuilder = new \imgBuilder();
		$oImageBuilder->strImagePath = \Util::getDocumentRoot()."storage/";
		$oImageBuilder->strTargetPath = \Util::getDocumentRoot()."storage/tmp/";
		$oImageBuilder->strTargetUrl = "/storage/tmp/";
		
		$oImageBuilder->buildImage($aInfo, true);

	}

}
