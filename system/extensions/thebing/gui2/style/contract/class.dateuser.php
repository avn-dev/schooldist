<?
class Ext_Thebing_Gui2_Style_Contract_DateUser extends Ext_Gui2_View_Style_Abstract {

	public function getStyle($mValue, &$oColumn, &$aRowData){

		$sColor = 'none';

		// Wenn bestätigt/verschickt wurde wird die Spalte grün angezeigt
		if($mValue > 0) {
			$sColor = Ext_Thebing_Util::getColor('good');
		}

		$sField = 'last_'.$oColumn->db_column.'_version_id'; // last_sent_version_id, last_confirmed_version_id

		if(
			!empty($aRowData[$sField]) &&
			$aRowData[$sField] != $aRowData['id']
		) {
			$sColor = Ext_Thebing_Util::getColor('neutral');
		}

		return 'background-color: '.$sColor;
	}
}
