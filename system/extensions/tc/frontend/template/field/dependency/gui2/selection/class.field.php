<?php

class Ext_TC_Frontend_Template_Field_Dependency_Gui2_Selection_Field extends Ext_Gui2_View_Selection_Abstract {
	
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		
        $aSelectFields = $this->getTemplateSelectFields($oWDBasic);
        
        if($this->oJoinedObject) {

			$aFieldDependencies = (array)$oWDBasic->getJoinedObjectChilds($this->sJoinedObjectKey, true);			
			$aCurrentFieldDependencies = array();
			
			foreach($aFieldDependencies as $iKey => $oChild) {
				if($iKey != $this->iJoinedObjectKey) {
					$aCurrentFieldDependencies[] = $oChild->dependency_field_id;
				}				
			}

			foreach($aCurrentFieldDependencies as $iFieldId) {
				unset($aSelectFields[$iFieldId]);
			}

		}
        
        if(!empty($aSelectFields)) {
            $aSelectFields = Ext_TC_Util::addEmptyItem($aSelectFields);
        }
        
        return $aSelectFields;
	}
	
    protected function getTemplateSelectFields(Ext_TC_Basic $oWDBasic) {
        $oRepository = Ext_TC_Frontend_Template_Field::getRepository();
        $aSelectFields = $oRepository->findBy(array('display' => array('select', 'radio'), 'template_id' => $oWDBasic->template_id));

        $aMappings = Ext_TC_Factory::executeStatic('Ext_TC_Frontend_Template_Field_Dependency', 'getEntityFieldMapping', [$oWDBasic]);
        
        $aReturn = array();
        foreach($aSelectFields as $oField) {    
            /* @var $oField Ext_TC_Frontend_Template_Field */
            if(
                $oField->id !== $oWDBasic->id &&
                (
                    $oField->area === 'individual' ||
                    isset($aMappings[$oField->field])
                )
            ) {
                $aReturn[$oField->id] = $oField->label . ' (' . $oField->placeholder . ')';
            }                
        }
        
		asort($aReturn);
		
        return $aReturn;
    }
    
}

