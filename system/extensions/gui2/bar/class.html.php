<?php
class Ext_Gui2_Bar_Html extends Ext_Gui2_Config_Basic {
	
	// Konfigurationswerte setzten
	protected $_aConfig = array(
								'element_type'	=> 'html',
								'id'			=> '',
								'html'			=> '',
								'label'			=> '',
								'access'		=> '' // recht
								);
	
	public function __construct($sHtml, $sId = ''){
		$this->_aConfig['html'] 	= $sHtml;
		$this->_aConfig['id'] 		= $sId;
		
	}
	
	/**
	 * Set einen Label vor das Icon
	 * @return array
	 */

	public function getElementData(){
		return $this->_aConfig;
	}
	
	
}
