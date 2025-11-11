<?
class Ext_TC_Upload_Gui2_Row extends Ext_Gui2_View_Style_Abstract {

	public function getStyle($mValue, &$oColumn, &$aRowData){

		$oFile = Ext_TC_Upload::getInstance($aRowData['id']);
		$aUsage = $oFile->getUsage();

		$sStyle = '';

		if(!empty($aUsage)){
			$sStyle = 'background-color: '.Ext_TC_Util::getColor('lightgreen').'; ';
		}

		return $sStyle;

	}
}
