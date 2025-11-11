<?php

class Ext_TC_System_Checks_Frontend_Field_IndividualFieldTemplateType extends GlobalChecks {
	
	public function getTitle() {
		return 'Frontend Field Templates';
	}
	
	public function getDescription() {
		return 'Corrects type of frontend additinalservices field templates';
	}
	
	public function executeCheck() {
		
		$bBackup = Ext_TC_Util::backupTable('tc_frontend_templates_templates');
		if(!$bBackup) {
			__pout('Backup error!');
			return false;
		}
		
		$sTransaction = get_called_class();
		
		DB::begin($sTransaction);
		
		try {
			
			$this->updateFieldTypes('fieldtemplate_additionalservice', 'fieldtemplate_additionalservices');
			$this->updateFieldTypes('fieldtemplate_additionalservice_select', 'fieldtemplate_additionalservices_select');
			
		} catch (Exception $ex) {
			__pout($ex);
			DB::rollback($sTransaction);
			return false;
		}
		
		DB::commit($sTransaction);
		
		return true;
	}
	
	/**
	 * @param string $sOldType
	 * @param string $sNewType
	 */
	private function updateFieldTypes($sOldType, $sNewType) {

		$aWrongEntries = $this->getFieldsForType($sOldType);
		$aCorrectEntries = $this->getFieldsForType($sNewType);
		
		foreach($aWrongEntries as $iTemplateId => $sWrongEntryType) {
			// Feld wurde bereits mit dem neuen Typ gespeichert
			if(isset($aCorrectEntries[$iTemplateId])) {
				continue;
			}
					
			$aWhere = [
				'template_id' => $iTemplateId,
				'type' => $sOldType
			];
			DB::updateData('tc_frontend_templates_templates', [ 'type' => $sNewType ], $aWhere);			
		}			
	}
	
	/**
	 * @param string $sType
	 */
	private function getFieldsForType($sType) {
		$sSql = "
			SELECT
				`template_id`,
				`type`
			FROM
				`tc_frontend_templates_templates`
			WHERE
				`type` = :type
		";
		
		return (array) DB::getQueryPairs($sSql, ['type' => $sType]);
	}
}