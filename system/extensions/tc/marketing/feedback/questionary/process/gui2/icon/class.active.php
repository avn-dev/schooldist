<?php

class Ext_TC_Marketing_Feedback_Questionary_Process_Gui2_Icon_Active extends Ext_Gui2_View_Icon_Active {

	/**
	 * @param array $aSelectedIds
	 * @param array $aRowData
	 * @param $oElement
	 * @return int
	 */
	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if($oElement->action === 'edit') {
			$aSelectedIds = (array)$aSelectedIds;
			$iQuestionaryProcessId = reset($aSelectedIds);
			$iFlag = 1;

			$oQuestionaryProcess = Ext_TC_Factory::getInstance('Ext_TC_Marketing_Feedback_Questionary_Process', $iQuestionaryProcessId);

			$sAnswered = $oQuestionaryProcess->answered;
			if(empty($sAnswered)) {
				$iFlag = 0;
			}

			return $iFlag;
		} else {
			return parent::getStatus($aSelectedIds, $aRowData, $oElement);
		}

	}

}