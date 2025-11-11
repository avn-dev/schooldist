<?php

class Ext_Gui2_Page_View extends MVC_View {
	
	/**
	 * View ausgeben 
	 */
	public function render() {
		global $page_data;

		header('Content-type: text/html', true, $this->getHTTPCode());
				
		$oGui = $this->get('gui');

		$page_data['htmltitle'] = $oGui->gui_title;
		
		$oGui->display();
		
	}
	
}