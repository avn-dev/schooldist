<?php

class Ext_TC_Frontend_Template_Field_Dependency_Gui2_Selection_Value extends Ext_Gui2_View_Selection_Abstract {
	
    /**
     * 
     * @param type $aSelectedIds
     * @param type $aSaveField
     * @param Ext_TC_Frontend_Template_Field $oWDBasic
     * @return type
     */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		
        $aReturn = array();
        
        if(
            $this->oJoinedObject &&
            $this->oJoinedObject->dependency_field_id > 0           
        ) {
        
            $oParentField = $this->oJoinedObject->getJoinedObject('dependency_field');
            /* @var $oParentField Ext_TC_Frontend_Template_Field */
            
            if($oParentField->area === 'standard') {
                $oTemplate = $oWDBasic->getJoinedObject('frontend_template');
                $aReturn = $this->getStandardFieldOptions($oTemplate, $oParentField);                
            } else if($oParentField->area === 'individual') {
                $aReturn = $this->getIndividualFieldOptions($oParentField);  
            }
            
        }
          
        return $aReturn;
	}

    protected function getStandardFieldOptions($oTemplate, $oParentField) {
        $aMappings = Ext_TC_Factory::executeStatic('Ext_TC_Frontend_Template_Field_Dependency', 'getEntityFieldMapping', [$oParentField]);
        $aOptions = array();    
				
        if(isset($aMappings[$oParentField->field])) {
            $oEntity = new $aMappings[$oParentField->field]['class']();		
            $oForm = new Ext_TC_Frontend_Form($oTemplate, $oEntity);

            $oFormField = $oForm->getField($oParentField->placeholder);

            if($oFormField instanceof Ext_TC_Frontend_Form_Field_Select) {
                $aOptions = $oFormField->getOptions();
            }

			if(isset($aOptions[0])) {
				unset($aOptions[0]);
			}			
        }
        
        return $aOptions;
    }
    
    protected function getIndividualFieldOptions($oParentField) {

        $iGuiDesignElement = reset($oParentField->gui_design_elements);
    
        $oTabElement = Ext_TC_Gui2_Design_Tab_Element::getInstance($iGuiDesignElement);
        
        $aOptions = Ext_TC_Flexibility::getOptions($oTabElement->special_type);
        
        return $aOptions;
    }
    
}

