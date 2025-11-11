<?php
class Ext_Gui2_Bar_Labelgroup extends Ext_Gui2_Config_Basic {
	
	// Konfigurationswerte setzten
	protected $_aConfig = array(
		'element_type'	=> 'label_group',
		'id'			=> '',
		'html'			=> '',
		'label'			=> '',
		'access'		=> '', // recht
		'visibility'	=> true,
		'style'			=> '',
		'hide_empty'	=> true
	);

	public function __construct($sLabel, $sStyle = '') {
		$this->_aConfig['label'] = $sLabel;
		$this->_aConfig['style'] = $sStyle;
		$this->_aConfig['id'] = 0;
	}
	
	/**
	 * Set einen Label vor das Icon
	 * @param $sLabel
	 */

	public function getElementData() {

		// Wenn ID ein leerer ist, dann soll eine zufällige erstellt und zugewiesen werden.
		if(empty($this->_aConfig['id'])){
			$this->_aConfig['id'] = Util::generateRandomString(8);
		}
		$sVisibility = '';
		if (!$this->_aConfig['visibility']) {
			$sVisibility = 'visibility: hidden; ';
		}

		$this->_aConfig['html'] = '<label id="' . $this->_aConfig['id'] . '" class="divToolbarLabelGroup" style="' . $sVisibility . $this->_aConfig['style'] . '">' . $this->_aConfig['label'] . '</label>';

		return $this->_aConfig;
	}
}
