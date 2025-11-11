<?php

class Ext_TS_Accounting_Bookingstack_History_Format_Export extends Ext_Gui2_View_Format_Abstract{

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$sPath = Util::getDocumentRoot().'storage/'.$mValue;
		$mValue = '/'.$mValue;

		if(is_file($sPath)) {

			$sOnClick = 'onclick="window.open(\'/storage/download' . $mValue . '\'); return false"';
			return '<i class="fa fa-file-excel-o" style="cursor: pointer;" '.$sOnClick.' title="'.L10N::t('Export Datei öffnen').'"></i>';
		}

		return '';
	}

	public function align(&$oColumn = null) {
		return 'center';
	}

}
