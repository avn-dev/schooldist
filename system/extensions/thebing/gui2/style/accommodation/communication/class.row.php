<?php
/*
 * Style Classe für Unterkunfskommunikation
 */
class Ext_Thebing_Gui2_Style_Accommodation_Communication_Row extends Ext_Gui2_View_Style_Abstract {

	public function getStyle($mValue, &$oColumn, &$aRowData){

		$sStorno	= Ext_Thebing_Util::getColor('storno');
	
		$sStyle = '';

		## BG Farbe ##
		// Storniert
		if($aRowData['canceled'] > 0){
			$sStyle .= 'background-color: '.$sStorno.'; ';
		}

		return $sStyle;
	}

}
