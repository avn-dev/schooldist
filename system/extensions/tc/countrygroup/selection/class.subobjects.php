<?php

/**
 *   Selection Klasse für Objekte der Ländergruppen
 */

class Ext_TC_Countrygroup_Selection_SubObjects extends Ext_Gui2_View_Selection_Abstract {

    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
	
		$aSubObjects = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getSubObjects', array(true));
		
		if(
			$this->oJoinedObject
		) {

			$aChilds = (array)$oWDBasic->getJoinedObjectChilds($this->sJoinedObjectKey, true);
			
			$aCurrentSubObjects = array();
			
			foreach($aChilds as $iKey=>$oChild) {
				if($iKey != $this->iJoinedObjectKey) {
					$aCurrentSubObjects = array_merge($aCurrentSubObjects, $oChild->objects);
				}				
			}

			foreach($aCurrentSubObjects as $iObjectId) {
				unset($aSubObjects[$iObjectId]);
			}

		}
		
		return $aSubObjects;

	}
	
}
