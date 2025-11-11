<?php

class Ext_TC_NumberRange_Format_Application extends Ext_Gui2_View_Format_Abstract {
//
//	public function getTitle(&$oColumn = null, &$aResultData = null) {
//
//		$mValue = $aResultData[$oColumn->select_column];
//
//		$aReturn = array();
//		$aReturn['content'] = (string)$this->format($mValue, $oColumn, $aResultData, true);
//		$aReturn['tooltip'] = true;
//
//		return $aReturn;
//
//	}

	public function format($mValue, &$oColumn = null, &$aResultData = null, $bFull = false) {

		$oWDBasic = $this->oGui->getWDBasic($aResultData['id']);
		$aFinalApplications = array();

		if($oWDBasic){

			$aSets = $oWDBasic->getJoinedObjectChilds('sets');

			$aApplciationNames = Ext_TC_Factory::executeStatic('Ext_TC_NumberRange_Gui2_Data', 'getApplications');
			$aApplciationNames = $aApplciationNames[$oWDBasic->category];

			foreach((array)$aSets as $oSet){

				$aApplications = $oSet->applications;
				foreach((array)$aApplications as $sKey => $sApplication){
					$aFinalApplications[$sApplication] = $aApplciationNames[$sApplication];
				}

			}

		}

//		if(!$bFull){
//			$sBack = reset($aFinalApplications);
//		} else {
			$sBack = implode(', ', $aFinalApplications);
//		}
		//__uout($sBack, 'koopmann');
		return $sBack;

	}


}
