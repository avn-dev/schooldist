<?php

class Ext_TC_Communication_Gui2_Page extends Ext_Gui2_Page {
	
	public function display($aOptionalHeaderData = array()) {
		
		$aOptionalHeaderData = array();
		$aOptionalHeaderData['js'] = array(
			'/admin/extensions/tc/js/communication_gui.js'
		);
		$aOptionalHeaderData['css'] = array(
			'/assets/tc/css/communication.css'
		);
		
		parent::display($aOptionalHeaderData);

	}

}
