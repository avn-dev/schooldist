<?php

/**
 *   Selection Klasse für Felder
 */
	
class Ext_TC_Referrer_Selection_Field extends Ext_Gui2_View_Selection_Abstract {

    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
	
		// Liste aller verfügbaren Felder
        $aFieldList = Ext_TC_Factory::executeStatic('Ext_TC_Referrer', 'getFieldList', [true]);

		if($this->oJoinedObject) {			
			
			//Childs anhand von sJoinedObjectKey holen
			$aChilds = (array)$oWDBasic->getJoinedObjectChilds($this->sJoinedObjectKey, true);

			//Childs durchlaufen in temporärers Array schreiben
			foreach($aChilds as $iKey => $oChild) {

				//bereits verwendete keys aus Selection rauswerfen; außer an der
				//Stelle wo es ausgewählt wurde
				if($iKey != $this->iJoinedObjectKey){
					unset($aFieldList[$oChild->field]);
				}
			}

		}
		
		return $aFieldList;

	}
	
}
