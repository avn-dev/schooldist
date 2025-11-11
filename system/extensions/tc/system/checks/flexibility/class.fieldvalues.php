<?php

class Ext_TC_System_Checks_Flexibility_FieldValues extends GlobalChecks {
	
	protected $_aFieldCache = array();
	
	public function getTitle() {
		return 'Individual Fields';
	}
		
	public function getDescription() {
		return 'Prepares the database structure of individual field values';
	}
	
	public function executeCheck() {
		
		$bBackup = Ext_TC_Util::backupTable('tc_flex_sections_fields_values');
		
		if(!$bBackup) {
			__pout('Backup fail');
			return false;
		}
		
		DB::begin('Ext_TC_System_Checks_Flexibility_FieldValues');
		
		try {
			
			$aFieldValues = $this->getFieldsValuesWithoutType();
			
			foreach($aFieldValues as $aFieldValue) {
				$aField = $this->getField($aFieldValue['field_id']);
				$sType = null;
				
				switch ($aField['gui_design_usage']) {
					case 'enquiry':
						$sType = 'enquiry';
						break;
					case 'inquiry':
						$sType = 'inquiry';
						break;
					case 'enquiry_inquiry':						
						$aInquiry = $this->searchInquiry($aFieldValue['item_id']);
						if(empty($aInquiry)) {
							$aEnquiry = $this->searchEnquiry($aFieldValue['item_id']);
							if(!empty($aEnquiry)) {
								$sType = 'enquiry';
							}							
						} else {
							$sType = 'inquiry';
						}
						break;
					case 'student_inquiry':
						$aInquiryContact = $this->searchInquiryContact($aFieldValue['item_id']);
						if(empty($aInquiryContact)) {
							$aEnquiryContact = $this->searchEnquiryContact($aFieldValue['item_id']);
							if(!empty($aEnquiryContact)) {
								$sType = 'enquiry';
							}							
						} else {
							$sType = 'inquiry';
						}
						break;
				}
				
				if($sType !== null) {
					DB::updateData('tc_flex_sections_fields_values', array('item_type' => $sType), array(
						'field_id' => $aFieldValue['field_id'],
						'item_id' => $aFieldValue['item_id'],
						'language_iso' => $aFieldValue['language_iso']
					));
				}
				
			}
			
		} catch (Exception $ex) {
			__pout($ex);
			DB::rollback('Ext_TC_System_Checks_Flexibility_FieldValues');
			return false;
		}
		
		DB::commit('Ext_TC_System_Checks_Flexibility_FieldValues');
		
		return true;
	}
	
	/**
	 * Liefert alle Felder vom Typ "Gui Designer" bei denen der item_type noch nicht gesetzt
	 * wurde
	 * 
	 * @return array
	 */
	protected function getFieldsValuesWithoutType() {
		
		$sSql = "
			SELECT
				`tc_fsfv`.*
			FROM
				`tc_flex_sections_fields_values` `tc_fsfv` INNER JOIN
				`tc_flex_sections_fields` `tc_fsf` ON
					`tc_fsf`.`id` = `tc_fsfv`.`field_id` INNER JOIN
				`tc_flex_sections` `tc_fs` ON
					`tc_fs`.`id` = `tc_fsf`.`section_id` AND
					`tc_fs`.`type` = :type
			WHERE
				`tc_fsfv`.`item_type` = ''
		";
		
		$aData = (array) DB::getQueryData($sSql, array('type' => 'gui_designer'));
		
		return $aData;
	}
	
	/**
	 * Liefert die Daten zu einem Feld anhand einer Id
	 * 
	 * @param int $iFieldId
	 * @return array
	 */
	protected function getField($iFieldId) {
		
		if(empty($this->_aFieldCache[$iFieldId])) {		
			$sSql = "
				SELECT
					*
				FROM
					`tc_flex_sections_fields`
				WHERE
					`id` = :field_id AND
					`active` = 1
				LIMIT 1
			";

			$aEntry = (array) DB::getQueryData($sSql, array('field_id' => (int) $iFieldId));

			$sGuiDesignUsage = $this->getGuiDesignUsage($iFieldId);

			if(!empty($aEntry)) {
				$aField = reset($aEntry);
				$aField['gui_design_usage'] = $sGuiDesignUsage;

				$this->_aFieldCache[$iFieldId] = $aField;
			}
		}
		
		return $this->_aFieldCache[$iFieldId];
	}
	
	/**
	 * Liefert aus den WDBasic-Attributen die Verwendung eines Feldes für den GUI-Designer
	 * 
	 * @param int $iFieldId
	 * @return string
	 */
	protected function getGuiDesignUsage($iFieldId) {
		$sSql = "
			SELECT
				`wd_av`.`value`
			FROM
				`wdbasic_attributes` `wd_a` INNER JOIN
				`wdbasic_attributes_varchar` `wd_av` ON
					`wd_av`.`attribute_id` = `wd_a`.`id`
			WHERE
				`wd_a`.`class_id` = :field_id AND
				`wd_a`.`name` = :name AND
				`wd_a`.`table` = :table
		";
		
		$sGuiDesignUsage = DB::getQueryOne($sSql, array(
			'field_id' => $iFieldId,
			'name' => 'gui_designer_usage',
			'table' => 'tc_flex_sections_fields'
		));
		
		return $sGuiDesignUsage;
	}
	
	/**
	 * Liefert eine Inquiry zu einer Id
	 * 
	 * @param int $iId
	 * @return array
	 */
	protected function searchInquiry($iId) {
		return $this->getTableEntry('ta_inquiries', $iId);
	}
	
	/**
	 * Liefert eine Enquiry zu einer Id
	 * 
	 * @param int $iId
	 * @return array
	 */
	protected function searchEnquiry($iId) {
		return $this->getTableEntry('tc_enquiries', $iId);
	}
	
	/**
	 * Liefert einen Inquiry-Contact zu einer Id
	 * 
	 * @param int $iId
	 * @return array
	 */
	protected function searchInquiryContact($iId) {
		return $this->getTableEntry('ta_inquiries_to_contacts', $iId);
	}
	
	/**
	 * Liefert einen Enquiry-Contact zu einer Id
	 * 
	 * @param int $iId
	 * @return array
	 */
	protected function searchEnquiryContact($iId) {
		return $this->getTableEntry('ta_enquiries_to_contacts', $iId);
	}
	
	/**
	 * Liefert einen Eintrag aus einer Tabelle anhand der Id
	 * 
	 * @param string $sTable
	 * @param int $iId
	 * @return array
	 */
	protected function getTableEntry($sTable, $iId) {
		
		$sSql = "
			SELECT
				`id`
			FROM
				#table
			WHERE
				`id` = :id
			LIMIT 1
		";
		
		$aEntry = (array) DB::getQueryOne($sSql, array('table' => $sTable, 'id' => (int) $iId));
		
		return $aEntry;
	}
}

