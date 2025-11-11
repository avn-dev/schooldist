<?php

/**
 *   Selection Klasse für Objekte
 */

class Ext_TC_Frontend_Extrafield_Selection_SubObjects extends Ext_Gui2_View_Selection_Abstract {

    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
	
		$aSubObjects = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getSubObjects', array(true));
		
		if(
			$this->oJoinedObject
		) {

			//Childs anhand von sJoinedObjectKey holen
			$aChilds = (array)$oWDBasic->getJoinedObjectChilds($this->sJoinedObjectKey, true);
			
			$aCurrentSubObjects = array();
			
			//Childs durchlaufen und Arrays zusammenführen
			foreach($aChilds as $iKey=>$oChild) {
				if($iKey != $this->iJoinedObjectKey) {
					$aCurrentSubObjects = array_merge($aCurrentSubObjects, $oChild->objects);
				}				
			}

			// benutzte Objects rauswerfen
			foreach($aCurrentSubObjects as $iObjectId) {
				unset($aSubObjects[$iObjectId]);
			}

		}
		
		return $aSubObjects;

	}
	
}
