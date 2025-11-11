<?php


class Ext_Thebing_Gui2_Style_Accommodation_Accommodation extends Ext_Gui2_View_Style_Abstract {

	public function getStyle($mValue, &$oColumn, &$aRowData){

		$sStyleKey		= 'background-color';
		$sStyleKey2		= '';
		$sStyle2		= '';

		$iAllocationId			= (int)$aRowData['allocation_id'];
		if(!empty($aRowData['active_accommodation_allocations'])){
			$aActiveAllocations		= explode(',', $aRowData['active_accommodation_allocations']);
		}else{
			$aActiveAllocations		= array();
		}
		

		if(
			$aRowData['inquiry_accommodation_active'] == 0 ||
			$aRowData['inquiry_accommodation_visible'] == 0 ||
			$aRowData['canceled'] > 0
		){
			$sStyleKey	= 'color';
			$sColor		= Ext_Thebing_Util::getColor('red_font');

			$sStyleKey2	= 'font-style';
			$sStyle2	= 'italic';
		}elseif(
			!empty($aRowData['active_accommodation_allocations']) &&
			in_array($iAllocationId,$aActiveAllocations)
		){
			$sColor = Ext_Thebing_Util::getColor('good');
		}else {
			$sColor = Ext_Thebing_Util::getColor('bad');
		}

		$sReturn = $sStyleKey.': '.$sColor.';';

		if(
			!empty($sStyleKey2) &&
			!empty($sStyle2)
		){
			$sReturn .= $sStyleKey2.': '.$sStyle2.';';
		}

		return $sReturn;

	}


}
