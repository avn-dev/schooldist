<?
class Ext_Thebing_Gui2_Style_Transfer_Info extends Ext_Gui2_View_Style_Abstract {


	public function getStyle($mValue, &$oColumn, &$aRowData){

		if(empty($mValue)) {
			$mValue = 0;
		}
				
		// Transfer angefagt
		if($oColumn->select_column == 'transfer_requested') {
			if($mValue > 0) {
				$oDate	= new DateTime($mValue);
				$mValue = $oDate->getTimestamp();
			}
		}
		
		if(
			$mValue > 0 &&
			(
				(
					$aRowData['transfer_updated'] > 0 &&
					$aRowData['transfer_updated'] > $mValue
				) || (
					$aRowData['canceled'] > 0 &&
					$aRowData['canceled'] > $mValue	
				)
			)
		){
			return 'background-color: '.Ext_Thebing_Util::getColor('neutral').''; // gelb
		}elseif($mValue > 0){
			return 'background-color: '.Ext_Thebing_Util::getColor('good').''; // grün
		}else{
			return 'background-color: '.Ext_Thebing_Util::getColor('bad').''; // rot
		}
	}
	

}
