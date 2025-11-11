<?php

abstract class Ext_TC_System_Checks_Gui2_ChangeFlexFieldSection extends GlobalChecks {
	/**
	 * Mapping der Section die verschoben werden sollen
	 * 
	 * array(
	 *		'alte section' => 'neue section'
	 * )
	 * 
	 * @var array 
	 */
	protected $aSectionMoveMapping = [];
	
	/**
	 * @return string
	 */
	public function getTitle() {
		return 'Flex Fields';
	}
	
	/**
	 * @return string
	 */
	public function getDescription() {
		return 'Moves flex fields to another section';
	}
	
	/**
	 * @return boolean
	 */
	public function executeCheck() {

		if(empty($this->aSectionMoveMapping)) {
			return true;
		}
		
		set_time_limit(3600);
		ini_set('memory_limit', '2048M');
		
		$bBackup = Util::backupTable('tc_flex_sections_fields');
		if($bBackup == false) {
			__pout('Backup error!');
			return false;
		}
		
		$sTransactionPoint = get_called_class();
		
		DB::begin($sTransactionPoint);
		
		try {
			
			foreach($this->aSectionMoveMapping as $sFieldSectionFrom => $sFieldSectionTo) {
				
				$aWrongSectionFields = $this->getFieldsForSection($sFieldSectionFrom);

				foreach($aWrongSectionFields as $aField) {
					$this->changeSection($aField['id'], $sFieldSectionTo);
				}
				
			}
			
		} catch (Exception $ex) {
			__pout($e);
			DB::rollback($sTransactionPoint);
			return false;
		}
		
		DB::commit($sTransactionPoint);
		
		return true;
	}
		
	/**
	 * Liefert alle individuellen Felder zu einer Section
	 * 
	 * @param string $sSection
	 * @return array
	 */
	protected function getFieldsForSection($sSection) {

		$sSql = "
			SELECT
				`tc_fsf`.*
			FROM
				`tc_flex_sections_fields` `tc_fsf` INNER JOIN
				`tc_flex_sections` `tc_fs` ON
					`tc_fs`.`id` = `tc_fsf`.`section_id` AND
					`tc_fs`.`type` = :section AND
					`tc_fs`.`active` = 1
			WHERE
				`tc_fsf`.`active` = 1
			GROUP BY
				`tc_fsf`.`id`
		";
		
		return (array) DB::getQueryData($sSql, ['section' => $sSection]);
	}
	
	/**
	 * Updated die Section eines individuellen Feldes
	 * 
	 * @param int $iFieldId
	 * @param string $sNewSection
	 */
	protected function changeSection($iFieldId, $sNewSection) {
		
		$iSectionId = $this->getSectionId($sNewSection);
		
		if($iSectionId > 0) {
			DB::updateData('tc_flex_sections_fields', ['section_id' => $iSectionId], ' `id`='.$iFieldId);
		}
	}
	
	/**
	 * Sucht die ID der Section anhand des Keys der Section
	 * 
	 * @param string $sSection
	 * @return int
	 */
	protected function getSectionId($sSection) {
		
		$sSql = "
			SELECT
				`tc_fs`.`id`
			FROM
				`tc_flex_sections` `tc_fs`
			WHERE
				`tc_fs`.`type` = :section AND
				`tc_fs`.`active` = 1
		";
		
		return (int) DB::getQueryOne($sSql, ['section' => $sSection]);
	}
	
}

