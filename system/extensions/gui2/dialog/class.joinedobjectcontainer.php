<?php

/**
 * @property string $add_label
 * @property string $remove_label
 * @property bool $no_box_border
 */
class Ext_Gui2_Dialog_JoinedObjectContainer extends Ext_Gui2_Html_Div {

	/**
	 * @var Ext_Gui2_Dialog 
	 */
	protected $_oDialog;
	
	protected $_sJoinedObjectKey = null;
	
	public $aOptions = array();
	
	public $aElements = array();
	
	protected $_bContainerCreated = false;

	public function  __construct($sJoinedObjectKey, &$oDialog) {
		
		$this->_sJoinedObjectKey = $sJoinedObjectKey;
		$this->_oDialog = $oDialog;
		
		$this->aOptions['min'] = 1;
		$this->aOptions['max'] = 255;
		
	}
	
	public function getDialog() {
		return $this->_oDialog;
	}
	
	protected function _createContainer(){
		
		$sJoinedObjectKey = $this->_sJoinedObjectKey;
		$oDialog = $this->_oDialog;
		
		if($this->aOptions['max'] != 1){
			$this->class = 'GUIDialogJoinedObjectContainer joined_object_container_'.$sJoinedObjectKey;
		} else {
			$this->class = 'joined_object_container_'.$sJoinedObjectKey;
		}
		
		if(!empty($this->aOptions['no_box_border'])) {
			$this->class = ' no_border';
		}		
		
		$this->id = 'joinedobjectcontainer_'.$sJoinedObjectKey;
		
		$oRepeatContainer = new Ext_Gui2_Html_Div;
		if($this->aOptions['max'] != 1) {
			$oRepeatContainer->class = "GUIDialogJoinedObjectContainerRow clearfix";
			if (!empty($this->aOptions['row_class'])) {
				$oRepeatContainer->class .= ' '.$this->aOptions['row_class'];
			}
		}
		$oRepeatContainer->id = 'row_joinedobjectcontainer_'.$sJoinedObjectKey.'_0';

		$oHidden = new Ext_Gui2_Html_Input;
		$oHidden->type = 'hidden';
		$oHidden->name = 'save[joined_object_container_hidden][0]['.$sJoinedObjectKey.']';
		$oHidden->id = 'save[joined_object_container_hidden][0]['.$sJoinedObjectKey.']';
		$oHidden->class = 'joined_object_container_hidden';
		$oHidden->value = 1;
		$oRepeatContainer->setElement($oHidden);
		
		$oButton = new Ext_Gui2_Html_Button();
		$oButton->class = 'btn btn-sm btn-gray remove_joinedobjectcontainer';
		$oButton->style = 'display:none;';
		$oButton->id = 'remove_joinedobjectcontainer_'.$sJoinedObjectKey.'_0';
		
		$oIcon = new Ext_Gui2_Html_I();
		$oIcon->class = 'fa fa-minus-circle';
		$oIcon->title = $oIcon->label;

		if(!empty($this->aOptions['remove_label'])) {
			$sDeleteLabel = $this->aOptions['remove_label'];	
		} else {
			$sDeleteLabel = 'Einstellung löschen';
		}

		$oButton->setElement($oIcon);
		$oButton->setElement(' '.$oDialog->oGui->t($sDeleteLabel));
		$oRepeatContainer->setElement($oButton);

		$this->aElements[0] = $oRepeatContainer;

		$oAddButtonContainer = new Ext_Gui2_Html_Div();
		$oAddButtonContainer->class = 'add-btn-container form-group-sm';
		
		// Feld um mehrer Einstellungen auf einmal hinzu zu fügen
		if($this->aOptions['count_field']) {
			$oInput = new Ext_Gui2_Html_Input();
			$oInput->id = 'add_joinedobjectcontainer_field_'.$sJoinedObjectKey;
			$oInput->class = 'GUIDialogJoinedObjectContainerCountField txt form-control input-sm';
			$oInput->value = 1;
			$oAddButtonContainer->setElement($oInput);

		}
		
		$oButton = new Ext_Gui2_Html_Button();
		$oButton->class = 'btn btn-sm btn-primary add_joinedobjectcontainer';
		$oButton->id = 'add_joinedobjectcontainer_'.$sJoinedObjectKey;

		$oIcon = new Ext_Gui2_Html_I();
		$oIcon->class = 'fa fa-plus-circle';
		if($this->aOptions['min'] == $this->aOptions['max']){
			$oButton->style = 'display:none;';
		}
		$oIcon->title = $oIcon->label;

		if(!empty($this->aOptions['add_label'])) {
			$sAddLabel = $this->aOptions['add_label'];	
		} else {
			$sAddLabel = 'Einstellung hinzufügen';
		}
		
		$oButton->setElement($oIcon);
		$oButton->setElement(' '.$oDialog->oGui->t($sAddLabel));
		
		$oAddButtonContainer->setElement($oButton);
		
		$this->aElements[2] = $oAddButtonContainer;
		
		$this->_bContainerCreated = true;
	}

	public function setElement($oElement) {
		
		if(!$this->_bContainerCreated){
			$this->_createContainer();
		}
		
		if(
			$oElement != null
		){
			if(!is_object($oElement)){
				throw new Exception("Sorry, I need a Object as Element");
			} else {
				// Element in den Repeat Container schieben
				$this->aElements[0]->aElements[0]->setElement($oElement);
			}
		}
	}

	public function createRow($sLabel, $sInputType = 'input', $aOptions = array()) {

		$oRow =  $this->_createRow($sLabel, $aOptions, $sInputType);
		return $oRow;

	}

	public function createMultiRow($sLabel, $aOptions) {

		$aOptions['create_multirow'] = true;
		$oRow = $this->_createRow($sLabel, $aOptions);
		return $oRow;

	}

	protected function _createRow($sLabel, $aOptions, $sInputType = 'input') {

		if(!isset($aOptions['db_alias'])) {
			throw new InvalidArgumentException('db_alias is required for all JoinedObjectContainer fields');
		}

		$aOptions['joined_object_key'] = $this->_sJoinedObjectKey;
		$aOptions['joined_object_min'] = $this->getOption('min');
		$aOptions['joined_object_max'] = $this->getOption('max');
		$aOptions['joined_object_no_confirm'] = $this->getOption('no_confirm');

		if($aOptions['create_multirow']) {
			$oRow = $this->_oDialog->createMultiRow($sLabel, $aOptions);
		} else {
			$oRow = $this->_oDialog->createRow($sLabel, $sInputType, $aOptions);
		}

		return $oRow;

	}

	public function createSaveField($sElement, $aOptions, $bSetId = true) {

		$aOptions['joined_object_key'] = $this->_sJoinedObjectKey;
		$aOptions['joined_object_min'] = $this->getOption('min');
		$aOptions['joined_object_max'] = $this->getOption('max');
		$aOptions['joined_object_no_confirm'] = $this->getOption('no_confirm');
		
		$oInput = $this->_oDialog->createSaveField($sElement, $aOptions, $bSetId);

		return $oInput;
	}

	/**
	 * @param $sOption
	 * @return int
	 */
	private function getOption($sOption)
	{
		if(isset($this->aOptions[$sOption])) {
			$mValue = $this->aOptions[$sOption];
		} else {
			switch($sOption) {
				case 'min':
					$mValue = 1;
					break;
				case 'max':
					$mValue = 255;
					break;
				case 'no_confirm':
					$mValue = 0;
					break;
			}
		}

		return $mValue;

	}
	
	/**
	 *
	 * @return Ext_Gui2_Dialog_Container_Save_Handler_Abstract
	 */
	public function getSaveHandler()
	{
		return $this->getOption('save_handler');
	}

}