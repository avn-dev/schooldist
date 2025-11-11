<?php

/**
 * @property $no_padding
 * @property $no_scrolling
 * @property $dependency_visibility
 * @property $class
 * @property $access
 * @property $hidden
 */
class Ext_Gui2_Dialog_Tab extends Ext_Gui2_Dialog_Basic {

	public $sTitle = '';
	public $mAccess = '';
	public $aOptions = array();

	/**
	 * Wird beim Generieren gesetzt
	 *
	 * @var int
	 */
	public $iId = 0;
	
	/**
	 * @var Ext_Gui2
	 */
	public $oGui;

	/**
	 * @var Ext_Gui2_Dialog
	 */
	public $oDialog;
	
	public function  __construct($sTitle, $bReadOnly = false) {
		$this->sTitle = $sTitle;
		$this->bReadOnly = $bReadOnly;
		$this->bDefaultReadOnly = $bReadOnly;
	}
	
	public function __set($sName, $mValue) {
		switch($sName) {
			case 'access':
				$this->mAccess = $mValue;
				break;
			case 'class':
			case 'class_btn':
			case 'no_padding':
			case 'no_scrolling':
			case 'hidden':
			case 'dependency_visibility':
				$this->aOptions[$sName] = $mValue;
				break;
		}
	}
	
	public function __get($sName) {
		$mValue = null;
		switch($sName) {
			case 'access':
				$mValue = $this->mAccess;
				break;
			case 'class':
			case 'class_btn':
			case 'no_padding':
			case 'no_scrolling':
			case 'hidden':
			case 'dependency_visibility':
				$mValue = $this->aOptions[$sName] ?? null;
				break;
		}
		
		return $mValue;
		
	}

	/**
	 * @param Ext_Gui2_Dialog $oDialog
	 */
	public function setDialog(Ext_Gui2_Dialog $oDialog) {
		$this->oDialog = $oDialog;
	}
	
	/**
	 * @return Ext_Gui2_Dialog
	 */
	public function getDialog() {
		return $this->oDialog;
	}
	
	/**
	 * Setzt ein Element in den Dialog-Tab
	 * Spezielles Verhalten bei GUIs
	 * @param mixed $oElement 
	 */
	public function setElement($oElement) {

		if(
			$oElement != null &&
			$this->bSetElement
		) {

			if($oElement instanceof Ext_Gui2) {

				$this->no_padding = 1;
				$this->no_scrolling = 1;
				$this->bSetElement = false;

				$oElement->load_admin_header = 0;

				if($this->oGui instanceof Ext_Gui2) {
					$sParentHash = $oElement->parent_hash;
					if(empty($sParentHash)) {
						$oElement->setParent($this->oGui);
					}
				}
				$this->aElements = array($oElement);

			} else {
				$this->aElements[] = $oElement;
			}

		}

	}

	public function createRow($sLabel, $sInputType = 'input', $aOptions = array()) {

		$oRow = $this->oDialog->createRow($sLabel, $sInputType, $aOptions);
		
		return $oRow;
	}
	
}