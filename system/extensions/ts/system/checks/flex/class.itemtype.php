<?php

class Ext_TS_System_Checks_Flex_ItemType extends GlobalChecks {
	
	
	public function getTitle() {
		return 'Repair custom field data';
	}
	
	public function getDescription() {
		return 'Completes the attribution of a value if necessary.';
	}
	
	public function executeCheck() {
		
		set_time_limit(3600);
		ini_set('memory_limit', '2G');
		
		// Backup anlegen
		$mSuccess = Util::backupTable('tc_flex_sections_fields_values');
		
		if(!$mSuccess) {
			__pout("Failed to create backup!"); 
			return false;
		}
		
		$aClasses = ['Ext_Thebing_Inquiry_Group', 'Ext_TS_Enquiry'];
		
		foreach($aClasses as $sClass) {
		
			$oEntity = new $sClass;
			
			$aFlexFields = $oEntity->getFlexibleFields();
			$sEntityFlexType = $oEntity->getEntityFlexType();

			foreach($aFlexFields as $iFlexFieldId=>$aFlexField) {

				$sSql = "
						SELECT
							`kfsfv`.*
						FROM
							`tc_flex_sections_fields_values` AS `kfsfv`
						WHERE
							`kfsfv`.`field_id` = :id AND
							`kfsfv`.`item_type` = ''
						GROUP BY
							`kfsfv`.`field_id`,
							`kfsfv`.`item_id`,
							`kfsfv`.`language_iso`";
				$aSql = [];
				$aSql['id'] = $iFlexFieldId;

				$aResults = (array)\DB::getQueryRows($sSql, $aSql);

				$sSqlCheck = "
						SELECT
							`kfsfv`.*
						FROM
							`tc_flex_sections_fields_values` AS `kfsfv`
						WHERE
							`kfsfv`.`field_id` = :field_id AND
							`kfsfv`.`item_id` = :item_id AND
							`kfsfv`.`item_type` = :item_type AND
							`kfsfv`.`language_iso` = :language_iso
						";
				
				foreach($aResults as $aResult) {
					
					// Prüfen, ob es schon einen Wert mit korrekter Zuweisung gibt
					$aSqlCheck = [
						'field_id' => $aResult['field_id'],
						'item_id' => $aResult['item_id'],
						'item_type' => $sEntityFlexType,
						'language_iso' => $aResult['language_iso'],
					];
					
					$aCheck = \DB::getQueryRow($sSqlCheck, $aSqlCheck);

					// Umschreiben, wenn noch nicht da, sonst löschen
					if(empty($aCheck)) {
						\DB::updateData('tc_flex_sections_fields_values', ['item_type'=>$sEntityFlexType], ['field_id'=>$aResult['field_id'] ,'item_id'=>$aResult['item_id'] ,'item_type'=>'', 'language_iso'=>$aResult['language_iso']]);
					} else {
						\DB::executePreparedQuery("DELETE FROM `tc_flex_sections_fields_values` WHERE `field_id` = :field_id AND `item_id` = :item_id AND `item_type` = '' AND `language_iso` = :language_iso", ['field_id'=>$aResult['field_id'] ,'item_id'=>$aResult['item_id'] , 'language_iso'=>$aResult['language_iso']]);
					}
						
				}
				
			}

		}
		
		return true;
	}

}