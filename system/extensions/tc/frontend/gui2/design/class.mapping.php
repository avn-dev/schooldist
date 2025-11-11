<?php
class Ext_TC_Frontend_Gui2_Design_Mapping extends Ext_TC_Frontend_Flexible_Mapping {
	
	public function __construct($sSection, $iOffice) {
		
		$iDesign = Ext_TA_Gui2_Design::searchBySectionAndOffice($sSection, $iOffice);

		$aMappingFields = array();
		
		$oDesign = Ext_TA_Gui2_Design::getInstance($iDesign);
		$aElements = $oDesign->getTabElements();

		foreach($aElements as $oElement){

			/* @var $oElement Ext_TC_Gui2_Design_Tab_Element */
			
			if(
				$oElement->id > 0 && 
				(
					$oElement->special_type == "" ||
					$oElement->type == 'flexibility'
				)
			) {					
				if($oElement->type == 'flexibility') {
					$oFlexField = Ext_TC_Flexibility::getInstance($oElement->special_type);
					if(
						$oFlexField->type == 3 ||
						$oFlexField->i18n				
					) {
						// Überschriften und I18N-Felder rausfiltern
						continue;
					}
				}

				$oSelection = new Ext_TC_Frontend_Gui2_Design_Selection($oElement);
				$oField = $this->createField(array('Type' => $oElement->type));
				
				$aInputTypes = $this->_getInputTypes($oElement);
				
				if(
					isset($aInputTypes['checkbox']) ||
					isset($aInputTypes['checkbox_text'])
				) {
					$oFormat = new Ext_TC_Gui2_Format_YesNo();
					$oField->addFormat($oFormat);
				}
				
				$oField->addConfig('label', $oElement->getName());
				$oField->addConfig('allowed_input_types', $aInputTypes);
				$oField->setSelection($oSelection);
				$this->addField('individual.'.$oElement->id, $oField);

			}
		}
		
		return $aMappingFields;		
	}
	
	protected function _getInputTypes($oElement){
		
		$aTypes = Ext_TC_Frontend_Template_Field_Gui2_Selection_Display::getInputTypes();
		
		// #5421 - Bei den individuellen Feldern müssen HTML-Felder zu Textarea-Felder umgewandelt werden
		if($oElement->type == 'html') {
			$aFieldTypes = ['textarea'];
		} else if($oElement->type == 'flexibility') {
			// Individuelle Felder
			$oField = Ext_TC_Flexibility::getInstance($oElement->special_type);
			$aFieldTypes = $this->getFlexibilityInputTypes($oField);
		} else {
			$aFieldTypes = [$oElement->type];
		}
		
		$aBack = array();
		foreach ($aFieldTypes as $sType) {
			$aBack[$sType] = $aTypes[$sType];

			if($sType == 'select') {
				$aBack['radio'] = $aTypes['radio'];
				$aBack['select_grouped'] = $aTypes['select_grouped'];
			} else if($sType == 'checkbox') {
				$aBack['checkbox_text'] = $aTypes['checkbox_text'];
			}
		}

		return $aBack;
	}
	
}
