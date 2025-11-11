<?php

class Ext_Gui2_Dialog_Basic {

	public $aElements	= array();
	public $bReadOnly	= false;
	public $bDefaultReadOnly = false;
	public $bSetElement = true;
	public $sJS = '';

	public function __construct() {  }

	public function setModus($sModus = 'readonly'){
		if($sModus == 'readonly'){
			$this->bReadOnly = true;
		} else {
			$this->bReadOnly = false;
		}
	}

	public function setElement($mElement){
		if($this->bSetElement) {
			if($mElement instanceof Ext_Gui2) {
				$this->no_padding = 1;
				$this->no_scrolling = 1;
				$this->bSetElement = false;
				$mElement->load_admin_header = 0;
				$this->aElements = array($mElement);
			} else {
				$this->aElements[] = $mElement;
			}
		}
	}

	public function getElements(){
		return $this->aElements;
	}

	public function generateHTML(){
		$sHTML = '';

		foreach($this->aElements as $mElement){

			if(
				$mElement instanceof Ext_Gui2_Html_Interface ||
				$mElement instanceof Ext_Gui2_Dialog_Basic
			) {

				// Wenn TabArea, dann setze ID vom Tab
				if($mElement instanceof Ext_Gui2_Dialog_TabArea) {

					if($this instanceof Ext_Gui2_Dialog_Tab) {
						$iId = $this->iId;
					} else {
						$iId = 0;
					}

					$mElement->iTabId = $iId;
				}

				$sHTML .= $mElement->generateHTML($this->bReadOnly);

			} elseif($mElement instanceof Ext_Gui2) {

				ob_start();
				$mElement->display(array(), true);
				$sHTML .= ob_get_clean();

			} elseif(is_string($mElement)) {

				$sHTML .= $mElement;

			} elseif(is_object($mElement)) {

				// Achtung, hier können bei falschen Objekten Fehler entstehen!
				// Aktuell springt hier Ext_Calendarsheet rein, wenn es in einem Dialog in einem Tab gesetzt wird. ^dg
				$sHTML .= $mElement->generateHTML();

				if(method_exists($mElement, 'generateJS')) {
					$this->sJS .= $mElement->generateJS();
				}

			} else {

				throw new Exception("Please use Ext_Gui2_Html_Interface or String");
			}

		}
		return $sHTML;
	}

}