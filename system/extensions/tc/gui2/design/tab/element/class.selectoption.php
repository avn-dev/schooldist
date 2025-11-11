<?php

class Ext_TC_Gui2_Design_Tab_Element_Selectoption extends Ext_TC_Basic {
	
	protected $_sTable = 'tc_gui2_designs_tabs_elements_selectoptions';
	
	protected $_aJoinTables = array(
		'i18n' => array(
			'table' => 'tc_gui2_designs_tabs_elements_selectoptions_i18n',
			'foreign_key_field' => array('language_iso', 'name'),
			'primary_key_field' => 'option_id',
			'i18n' => true
		)
		
	);
	
	public function getName($sLanguage = ''){
		
		$aData = $this->i18n;

		if($sLanguage == '') {
			$sLanguage = Ext_TC_System::getInterfaceLanguage();
		}
		
		foreach((array)$aData as $aLanguage) {
			
			if($aLanguage['language_iso'] == $sLanguage) {
				$sName = $aLanguage['name'];
			}
			
		}
		
		return $sName;
	}
	
}
?>
