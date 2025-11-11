<?php


class Ext_Thebing_Gui2_Style_Accounting_Teacher_Payment extends Ext_Gui2_View_Style_Abstract {


	public function getStyle($mValue, &$oColumn, &$aRowData){

		$sNoSalary = Ext_Thebing_Util::getColor('bad');
		$sGreen = Ext_Thebing_Util::getColor('good');
		$sSubsti = Ext_Thebing_Util::getColor('substitute_teacher');
		$sPartPayment = Ext_Thebing_Util::getColor('neutral');

		if(
			$aRowData['payed_amount'] > 0 && 
			isset($aRowData['inquiry_accommodation_id']) &&
			$aRowData['inquiry_accommodation_id'] <= 0 &&
			isset($aRowData['deleted_allocation']) &&
			$aRowData['deleted_allocation'] > 0
		){
			//wenn bezahlt wurde und die Zuweisung im Matching nicht mehr existiert
			$sStyle .= 'background-color: '.Ext_Thebing_Util::getColor('changed').';';
		}elseif($aRowData['payed_amount'] > 0){
			$sStyle .= 'background-color: '.$sGreen.';';
		} else if(
				$aRowData['imported'] > 0 &&
				$aRowData['amount'] < $aRowData['cost']
		){
			$sStyle .= 'background-color: '.$sPartPayment.';';
		}else if($aRowData['substitute_teacher'] == 1){
			$sStyle .= 'background-color: '.$sSubsti.';';
		}else if($aRowData['salary'] <= 0){
			$sStyle .= 'background-color: '.$sNoSalary.';';
		}

		return $sStyle;

	}


}