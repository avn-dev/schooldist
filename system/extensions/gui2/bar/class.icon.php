<?php
/**
 * @param string $element_type
 * @param string $img
 * @param string $task
 * @param string $action
 * @param array $additional
 * @param string $title
 * @param string $label
 * @param string $id
 * @param string $class
 * @param int $active aktiv ( ob es überhaupt angezeigt werden soll )
 * @param int $visible   Sichtbar (alpha/normal)
 * @param string $request_data  request daten
 * @param int $multipleId  darf das icon bei mehrfach selection gehen?
 * @param string $dialog_title  platzhalter #db_select_feld#
 * @param Ext_Gui2_Dialog $dialog_data 
 * @param int $dbl_click_element  Wenn 1, wird diese Aktion gestartet wenn man einen Double Click macht
 * @param string $info_text  Wenn 1, wird diese Aktion gestartet wenn man einen Double Click macht
 * @param array|string $access recht für das icon
 * @param string $confirm_message
 * @param string $options_serialized
 * @param string $style
 */
class Ext_Gui2_Bar_Icon extends Ext_Gui2_Config_Basic implements Ext_Gui2_Html_Interface {
	
	// Konfigurationswerte setzten
	protected $_aConfig = array(
								'element_type'	=> 'icon',
								'img'			=> '',
								'task'			=> '',
								'action'		=> '',
								'additional'	=> array(),
								'title'			=> '',
								'label'			=> '',
								'id'			=> '',
								'class'			=> '',
								'active'		=> 0,			// aktiv ( ob es überhaupt angezeigt werden soll )
								'visible'		=> 1,			// Sichtbar (alpha/normal)
								'request_data'	=> '',			// request daten
								'multipleId'	=> 0,			// darf das icon bei mehrfach selection gehen?
								'dialog_title'	=> '',			// platzhalter #db_select_feld#
								'dialog_data'	=> null,		// Ext_Gui2_Dialog Object
								'dbl_click_element'	=> 0,		// Wenn 1, wird diese Aktion gestartet wenn man einen Double Click macht
								'info_text'		=> 0 ,			// Wenn 1, wird diese Aktion gestartet wenn man einen Double Click macht
								'access'		=> '',			// recht für das icon
//								'readonly_access'	=> '',		// recht um den dialog lesen zu dürfen
								'confirm' => '',				// Ticket #5755
								'confirm_message' => '',
								'options_serialized' => '',
								'style' => ''
								);
	
	public function __construct($sImg, $sTask = '', $sTitle = ''){
		$this->_aConfig['img'] 		= $sImg;
		$this->_aConfig['task'] 	= $sTask;
		$this->_aConfig['title'] 	= $sTitle;
	}
	
	/**
	 * Set einen Label vor das Icon
	 * @param $sLabel
	 */
	public function setLabel($sLabel){
		$this->setConfig('label', $sLabel);
	}
	
	public function getHtml(){
		$oHtml = new Ext_Gui2_Icon_Html($this);
		return $oHtml->generateHtml();
	}
	
	public function getElementData(){
		return $this->_aConfig;
	}

	public function getKey() {
		$sKey = $this->_aConfig['action'];
		if(!empty($this->_aConfig['additional'])) {
			$sKey .= '_'.$this->_aConfig['additional'];
		}
		return $sKey;
	}
	
	/**
	 * generate an Icon with label
	 * @param type $bReadOnly
	 * @return type 
	 */
	public function generateHTML($bReadOnly = false){
		
		$oBarElement = new Ext_Gui2_Html_Div();
		$oBarElement->class = 'guiBarElement guiBarLink divSingleIcon '.$this->class;
		$oBarElement->style = $this->style;
		$oBarElement->id = $this->id;
		
		$oIconDiv = new Ext_Gui2_Html_Div();
		$oIconDiv->class = 'divToolbarIcon w16';

		$oIcon = Ext_Gui2_Html::getIconObject($this->img);
		$oIcon->title = $this->title;

		$oLabelDiv = new Ext_Gui2_Html_Div();
		$oLabelDiv->class = 'divToolbarLabel';
		
		if($this->label != ""){
			$oLabelDiv->setElement($this->label);	
		}
		$oIconDiv->setElement($oIcon);
		$oBarElement->setElement($oIconDiv);
		$oBarElement->setElement($oLabelDiv);
			
		return $oBarElement->generateHTML();
		
	}
	
	/**
	 * Do nothing!
	 * is a required method of the interface but have no use in this klass
	 */
	public function setElement($oElement){
		
	}

}