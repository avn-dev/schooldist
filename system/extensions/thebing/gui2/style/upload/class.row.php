<?
class Ext_Thebing_Gui2_Style_Upload_Row extends Ext_Gui2_View_Style_Abstract {


	public function getStyle($mValue, &$oColumn, &$aRowData){


		$oFile = Ext_Thebing_Upload_File::getInstance($aRowData['id']);
		$aUsage = $oFile->getUsage();

		$sStyle = '';

		if(!empty($aUsage)){
			$sStyle = 'background-color: '.Ext_Thebing_Util::getColor('lightgreen').'; ';
		}

		return $sStyle;

	}
}
