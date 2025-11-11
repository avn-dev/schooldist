<?php

class Ext_TC_Flexible_Gui2_Format_Option extends Ext_TC_Gui2_Format {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$sDbColumn = $oColumn->db_column;
		
		$iColumn = $sDbColumn;

		$iColumn = str_replace('flex_', '', $iColumn);
		
		$iColumn = str_replace('_original', '', $iColumn);
	
		if(empty($this->_sLanguage)) {
			$sLang = System::getInterfaceLanguage();
		} else {
			$sLang = $this->_sLanguage;
		}

		$iColumn = str_replace('_'.$sLang, '', $iColumn);

		$aOptions = Ext_TC_Flexibility::getOptions($iColumn, $sLang);

		if(isset($aOptions[$mValue])) {
			return $aOptions[$mValue];
		} else {
			return null;
		}
	}

}