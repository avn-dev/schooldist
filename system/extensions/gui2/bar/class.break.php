<?php
class Ext_Gui2_Bar_Break extends Ext_Gui2_Config_Basic {
	
	// Konfigurationswerte setzten
	protected $_aConfig = array(
								'element_type'	=> 'break',
								'id'			=> '',
								'html'			=> '',
								'label'			=> '',
								'access'		=> ''
								);
	
	public function __construct(){
		$this->_aConfig['html'] 	= '<div class="divCleaner"></div>';
		$this->_aConfig['id'] 		= 0;
		
	}
	
	/**
	 * Set einen Label vor das Icon
	 * @param $sLabel
	 */

	public function getElementData(){
		return $this->_aConfig;
	}
	
	
}
