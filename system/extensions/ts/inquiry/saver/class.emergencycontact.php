<?php

class Ext_TS_Inquiry_Saver_EmergencyContact extends Ext_TS_Inquiry_Saver_Abstract {
    
    /**
     * @var Ext_TS_Inquiry_Contact_Emergency
     */
    protected $_oObject;
 
    public function setObjects(Ext_TS_Inquiry $inquiry, $sAlias = '') {

		$contacts = [];
		
		$aSaveData = $this->getRequestSaveValues();
		foreach($aSaveData as $sColumn => $aAliases) {

			if(isset($aAliases[$sAlias])) {
				
				foreach($aAliases[$sAlias] as $key=>$joinedObjects) {

					if(!isset($contacts[$key])) {
						$contacts[$key] = [];
					}
					
					$contacts[$key][$sColumn] = $joinedObjects['other_contacts'];
					
				}

			}
		}
		
		$deleteContacts = $inquiry->getJoinedObjectChilds('other_contacts');

		foreach($contacts as $contactKey=>$contactData) {
			
			$contactObject = $inquiry->getJoinedObjectChild('other_contacts', $contactKey);

			foreach($contactData as $dataKey=>$dataValue) {
				$contactObject->$dataKey = $this->prepareSaveValue($dataValue, $dataKey);
			}
			
			if(
				$contactObject->exist() &&
				isset($deleteContacts[$contactObject->getId()])
			) {
				unset($deleteContacts[$contactObject->getId()]);
			}
			
		}
		
		if(!empty($deleteContacts)) {
			foreach($deleteContacts as $deleteContact) {
				$inquiry->deleteJoinedObjectChild('other_contacts', $deleteContact);
			}
		}
		
    }
    
}