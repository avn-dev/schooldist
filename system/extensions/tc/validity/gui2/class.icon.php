<?php

class Ext_TC_Validity_Gui2_Icon extends Ext_Gui2_View_Icon_Abstract {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if(
			$oElement->task == 'deleteRow' &&
			$oElement->action == ''
		) {
			if(
				count($aSelectedIds) > 0 &&
				(
					$aRowData[0]['valid_until'] == '0000-00-00' ||
					(
						$this->_oGui->getOption('validity_show_valid_until') === true &&
						$this->checkIsLastEntry(reset($aSelectedIds))
					)					
				)
			) {
				return 1;
			}

			return 0;

		} elseif(
			$oElement->task == 'openDialog' &&
			$oElement->action == 'edit'
		) {

			if(
				count($aSelectedIds) == 1 &&
				(
					$aRowData[0]['valid_until'] == '0000-00-00' ||
					(
						$this->_oGui->getOption('validity_show_valid_until') === true &&
						$this->checkIsLastEntry(reset($aSelectedIds))
					)
				)
			) {
				return 1;
			}

			return 0;
		}

		return 1;

	}

	/**
	 * Prüft ob der ausgewählte Eintrag der zuletzt erstellte Eintrag ist
	 * 
	 * @param int $iSelectedId
	 * @return boolean
	 */
	protected function checkIsLastEntry($iSelectedId) {
		
		$oWDBasic = $this->_oGui->getWDBasic($iSelectedId);
		/* @var $oWDBasic \Ext_TC_Validity */		
		$iLastValidity = $oWDBasic->getLatestEntry(true, null);
		/* @var $oLastValidity \Ext_TC_Validity */	

		if($oWDBasic->getId() == $iLastValidity) {
			return true;
		}
		
		return false;
	}
	
}