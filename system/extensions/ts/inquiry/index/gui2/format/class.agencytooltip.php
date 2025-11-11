<?php

class Ext_TS_Inquiry_Index_Gui2_Format_AgencyToolTip extends Ext_Thebing_Gui2_Format_ColumnTitle {

	/**
	 * @param null $oColumn
	 * @param array|null $aResultData
	 * @return array
	 */
	public function getTitle(&$oColumn = null, &$aResultData = null) {

		$aReturn = array();

		if($this->sFlexType != 'list') {
			/*
			 * Die Buchungsliste besitzt zwei Agenturspalten:
			 * - Agency-Short-Name + Tooltip (Agency-Full-Name)
			 * - Agency-Full-Name
			 *
			 * Da im Export standardmäßig das Tooltip ausgegeben wird (wenn eine Tooltip vorhanden ist),
			 * und wir dann 2 x Agency-Full-Name hätten, wird dies hier unterbunden und
			 * der normale Spalteninhalt ausgegeben.
			 */
			$aReturn['content'] = (string)($aResultData[$oColumn->select_column] ?? '');
		} else {
			$aReturn['content'] = (string)($aResultData[$this->_sTitleColumn] ?? '');
			$aReturn['tooltip'] = (bool)$this->_bAsTooltip;
		}

		return $aReturn;
	}

}
