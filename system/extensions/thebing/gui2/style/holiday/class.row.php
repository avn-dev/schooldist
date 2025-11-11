<?
class Ext_Thebing_Gui2_Style_Holiday_Row extends Ext_Gui2_View_Style_Abstract {


	public function getStyle($mValue, &$oColumn, &$aRowData){


		$oPublicHoliday = Ext_Thebing_Holiday_Holiday::getInstance($aRowData['id']);

		if($oPublicHoliday->annual == 1){
			$sStyle = 'background-color: '.Ext_Thebing_Util::getColor('good', 60).'; ';
		}

		return $sStyle;

	}
}
