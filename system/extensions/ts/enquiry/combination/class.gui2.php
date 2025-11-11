<?php

class Ext_TS_Enquiry_Combination_Gui2 extends Ext_Thebing_Gui2 {

	public function __construct($sHash = '', $sDataClass = 'Ext_Thebing_Gui2_Data', $sViewClass = '') {
		parent::__construct($sHash, $sDataClass, $sViewClass);
		$this->gui_description = Ext_TS_Enquiry::TRANSLATION_PATH;
		$this->gui_title = $this->t('Kombinationen');
	}
	
}