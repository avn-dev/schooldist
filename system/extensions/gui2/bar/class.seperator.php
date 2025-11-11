<?php

class Ext_Gui2_Bar_Seperator extends Ext_Gui2_Config_Basic {

	/**
	 * @var array
	 */
	protected $_aConfig = array(
		'element_type'	=> 'label_group',
		'id' => '',
		'html' => '',
		'label' => '',
		'access' => '',
		'visible' => true
	);

	/**
	 * @param string $sType
	 * @param array $aOptions
	 */
	public function __construct($sType='sub', $aOptions = array()) {
		
		$sClass = '';
		$sStyle = '';
		
		if(isset($aOptions['class'])) {
			$sClass = $aOptions['class'];
		}
		
		if(isset($aOptions['style'])) {
			$sStyle = $aOptions['style'];
		}
		
		if($sType == 'sub') {
			$this->_aConfig['html'] = '<div class="divToolbarSeparator '.$sClass.'" style="'.$sStyle.'"> <span class="hidden">::</span> </div>';
		} else {
			$this->_aConfig['html'] = '<div class="divToolbarSeparator '.$sClass.'" style="'.$sStyle.'"><div></div></div>';
		}
		$this->_aConfig['id'] = 0;

	}

	/**
	 * Set einen Label vor das Icon
	 *
	 * @return array
	 */
	public function getElementData(){
		return $this->_aConfig;
	}

}
