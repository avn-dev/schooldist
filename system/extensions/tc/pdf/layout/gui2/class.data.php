<?php

class Ext_TC_Pdf_Layout_Gui2_Data extends Ext_TC_Gui2_Data {

	/**
	 * Gibt die möglichen Elementtypen zurück
	 * @return type 
	 */
	public function getTypes() {

		$aTypes = array(
					'text'	=> $this->_oGui->t('Einzeiliger Text'),
					'html'	=> $this->_oGui->t('Mehrzeiliger Text'),
					'date'	=> $this->_oGui->t('Datum'),
					'img'	=> $this->_oGui->t('Bild'),
					'main_text'	=> $this->_oGui->t('Fließtext')
				);
		
		return $aTypes;
		
	}
	
}