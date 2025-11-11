<?php

class Ext_TS_Accounting_Payment_Format_Export extends Ext_Gui2_View_Format_Abstract{

	/**
	 * @param mixed $mValue
	 * @param null|Ext_Gui2_Head $oColumn
	 * @param null|array $aResultData
	 * @return string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$sPath = substr(\Util::getDocumentRoot(), 0, -1).$mValue;
		if(is_file($sPath)) {

			$sOnClick = 'onclick="window.open(\'' . $mValue . '\'); return false"';
			$sIcon = Ext_Thebing_Util::getFileTypeIcon($sPath);
			$sStyle = 'cursor: pointer;';
			return '<i style="' . $sStyle . '" ' . $sOnClick . ' class="fa '.Ext_Thebing_Util::getIcon('csv').'" alt="' . L10N::t('Export Datei öffnen') . '" title="' . L10N::t('Export Datei öffnen') . '"/></i>';
		}

		return '';
	}

	/**
	 * Ausrichtung des Icons in der Spalte
	 *
	 * @param null $oColumn
	 * @return string
	 */
	public function align(&$oColumn = null){
		return 'center';
	}

}
