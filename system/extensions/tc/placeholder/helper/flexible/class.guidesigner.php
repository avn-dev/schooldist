<?php

class Ext_TC_Placeholder_Helper_Flexible_GuiDesigner extends Ext_TC_Placeholder_Helper_Flexible_Abstract {

	/**
	 * Section, für die die Platzhalter geholt werden sollen
	 * @var string 
	 */
	public $sSection = '';
	/**
	 * Default Dependency Klasse
	 * @var string 
	 */
	protected $_sDefaultDependencyClass = '';
	
	/**
	 * liefert die Platzhalter anhand der Section
	 * @return array
	 */
	public function getPlaceholder() {
		
		if($this->sSection == '') {
			return array();
		}
		
		if(empty($this->_aPlaceholderCache[$this->sSection])) {
			$this->_prepareGuiDesignPlaceholder();
		}		
		
		return $this->_aPlaceholderCache[$this->sSection];		
	}
	
	/**
	 * 
	 * @param string $sClass
	 */
	public function setDefaultDependencyClass($sClass) {
		$this->_sDefaultDependencyClass = $sClass;
	}
	
	/**
	 * liefert alle Designs, die für diese Section angelegt wurden
	 * @return array
	 */
	protected function _getGuiDesigns() {		
		$aGuiDesigns = (array) Ext_TC_Gui2_Design::searchBySection($this->sSection, false);
		return $aGuiDesigns;
	}
	
	/**
	 * Objekt des Designs
	 * @param int $iGuiDesign
	 * @return Ext_TC_Gui2_Design
	 */
	protected function _getDesignObject($iGuiDesign) {
		$oDesign = Ext_TC_Factory::executeStatic('Ext_TC_Gui2_Design', 'getInstance', array($iGuiDesign));
		
		$sDefaultDependencyClass = $this->_getDefaultDependencyClass($oDesign);
		
		$this->_sDefaultDependencyClass = $sDefaultDependencyClass;
		
		return $oDesign;
	}
	
	/**
	 * liefert die Default-Dependency-Klasse 
	 * 
	 * @param Ext_TC_Gui2_Design $oDesign
	 * @return string
	 */
	protected function _getDefaultDependencyClass(Ext_TC_Gui2_Design $oDesign) {
		
		$aWDBasicClasses = array(
			'inquiry' => 'Ext_TA_Inquiry',
			'enquiry' => 'Ext_TA_Enquiry',
			'students' => 'Ext_TA_Inquiry'
		);
	
		$sClass = '';
		if(!empty($aWDBasicClasses[$oDesign->section])) {
			$sClass = $aWDBasicClasses[$oDesign->section];
		}
		
		return $sClass;
	}
	
	/**
	 * holt die Platzhalter der Gui-Designs
	 */
	protected function _prepareGuiDesignPlaceholder() {
		
		$aGuiDesigns = $this->_getGuiDesigns();

		if(empty($aGuiDesigns)) {
			return array();
		}

		$aPlaceholder = array();
		
		// Designs durchlaufen
		foreach($aGuiDesigns as $aData) {

			$iGuiDesign = (int) $aData['id'];

			if($iGuiDesign == 0) {
				continue;
			}

			$oDesign = $this->_getDesignObject($iGuiDesign);
			
			// Prüfen, ob das Design an Objekte (z.B. Büros) gebunden ist
			$bIsBoundToObject = $this->_checkGuiDesignForObjects($oDesign);
			if(!$bIsBoundToObject) {
				continue;
			}
						
			// Design-Elemente holen					
			$aTabElements = $oDesign->getTabElements();

			// Elemente der Tabs durchlaufen und schauen, ob Platzhalter angelegt wurden
			foreach($aTabElements as $oTabElement) {
				if($oTabElement->placeholder != '') {

					$aAttribute = $this->_createPlaceholderAttribute($oTabElement);

					$sPlaceholder = $this->_preparePlaceholder($oTabElement->placeholder);
					
					// Platzhalter-Array aufbauen
					$aTempPlaceholder = array(
						$sPlaceholder => $aAttribute
					);

					// Dependency-Klasse holen
					$sDependencyClass = $oTabElement->searchParentDependencyClass();
					
					if(empty($sDependencyClass)) {
						$sDependencyClass = $this->_sDefaultDependencyClass;
					}
					
					$aPlaceholder[$sDependencyClass][] = $aTempPlaceholder;

					// Element Smarty zuweisen
					if($this->bAssignData === true) {	
						$this->_assignVariable('oGuiDesignElement'.$oTabElement->id, $oTabElement);
					}
				}
			}
		}

		$this->_aPlaceholderCache[$this->sSection] = $aPlaceholder;
	}

	/**
	 * baut ein Array auf, welches nachher von der Platzhalterklasse verarbeitet wird
	 * @param Ext_TC_Gui2_Design_Tab_Element $oTabElement
	 * @return array
	 */
	protected function _createPlaceholderAttribute(Ext_TC_Gui2_Design_Tab_Element $oTabElement) {
		$aAttribute = array(
			'label'				=> $oTabElement->getI18NName('i18n', 'name'),
			'type'				=> 'gui_designer',
			'element_id'		=> (int) $oTabElement->id,
			'translate_label'	=> false
		);
		
		// Bei checkboxen Format-Klasse hinzufügen
		if($oTabElement->type == 'checkbox') {
			$aAttribute['format'] = 'Ext_TC_Gui2_Format_YesNo';
		}
		// Bei selects Format-Klasse hinzufügen
		if($oTabElement->type == 'select') {
			$aAttribute['format'] = 'Ext_TC_Placeholder_Helper_Flexible_GuiDesigner_Format_Select';
		}
		
		return $aAttribute;		
	}

	/**
	 * prüft, ob das übergebene Design an die definierten Objekte gebunden ist
	 * @param Ext_TC_Gui2_Design $oGuiDesign
	 * @return boolean
	 */
	protected function _checkGuiDesignForObjects($oGuiDesign) {
		
		if(empty($this->_aObjects)) {
			return true;
		}

		$bIsBoundToObject = true;
		
		// SubObjects holen
		$aGuiDesignObjects = $oGuiDesign->getSubObjects();
		$aGuiDesignObjects = array_flip($aGuiDesignObjects);
		
		// Prüfen, ob Design für Objekt verfügbar ist
		$bContinue = false;
		foreach($this->_aObjects as $iObjectId) {
			if(
				$iObjectId == 0 ||
				(
					$bContinue == false &&
					isset($aGuiDesignObjects[$iObjectId])
				)
			) {
				$bContinue = true;
			}
		}

		// Wenn Design nicht für Objekt gültig ist
		if(
			!empty($aGuiDesignObjects) &&
			$bContinue == false
		) {
			return false;
		}
		
		return $bIsBoundToObject;
	}
	
}
