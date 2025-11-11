<?php

class Ext_TC_NumberRange_Format_Object extends Ext_Gui2_View_Format_Abstract {

	public function getTitle(&$oColumn = null, &$aResultData = null) {

		$mValue = $aResultData[$oColumn->select_column];

		$aReturn = array();
		$aReturn['content'] = (string)$this->format($mValue, $oColumn, $aResultData, true);
		$aReturn['tooltip'] = true;

		return $aReturn;

	}

	public function format($mValue, &$oColumn = null, &$aResultData = null, $bFull = false) {

		$oWDBasic = $this->oGui->getWDBasic($aResultData['id']);
		$aFinalObjects = array();

		if($oWDBasic){

			$aObjects = $oWDBasic->objects;

			foreach((array)$aObjects as $iObject){
				$oObject = Ext_TC_Factory::getInstance('Ext_TC_SubObject', $iObject);
				if(
					$oObject &&
					method_exists($oObject, 'getName')
				){
					$aFinalObjects[$oObject->id] = $oObject->getName();
				}
			}

		}

		$oTooltip = new Ext_Gui2_View_Format_ToolTip('dummy');

		if(!$bFull){
			$sBack = reset($aFinalObjects);
		} else {
			$sBack = implode('<br/>', $aFinalObjects);
		}

		return $sBack;

	}


}
