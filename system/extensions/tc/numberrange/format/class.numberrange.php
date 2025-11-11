<?php

class Ext_TC_NumberRange_Format_Numberrange extends Ext_Gui2_View_Format_Abstract {

	public function getTitle(&$oColumn = null, &$aResultData = null) {

		$mValue = $aResultData[$oColumn->select_column];

		$aReturn = array();
		$aReturn['content'] = (string)$this->format($mValue, $oColumn, $aResultData, true);
		$aReturn['tooltip'] = true;

		return $aReturn;

	}

	public function format($mValue, &$oColumn = null, &$aResultData = null, $bFull = false) {

		$oWDBasic = $this->oGui->getWDBasic($aResultData['id']);
		$aFinalNumberranges = array();

		if($oWDBasic){

			$aSets = $oWDBasic->getJoinedObjectChilds('sets');

			foreach((array)$aSets as $oSet){
				$iNumberrange = $oSet->numberrange_id;
				$oNumberrange = Ext_TC_NumberRange::getInstance($iNumberrange);
				$aFinalNumberranges[$oNumberrange->id] = $oNumberrange->name;
			}

		}

		$oTooltip = new Ext_Gui2_View_Format_ToolTip('dummy');

		if(!$bFull){
			$sBack = reset($aFinalNumberranges);
		} else {
			$sBack = implode('<br/>', $aFinalNumberranges);
		}

		return $sBack;

	}


}
