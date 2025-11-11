<?php

namespace Form\Migrations;

class AddPages extends \GlobalChecks {

	public function getTitle() {
		return 'Add pages';
	}
	
	public function getDescription() {
		return 'Form fields can be allocated to different pages.';
	}
	
	/**
	 * @return boolean
	 */
	public function executeCheck() {

		$aBackup = [];
		$aBackup[] = \Util::backupTable('form_pages');
		$aBackup[] = \Util::backupTable('form_options');

		if(in_array(false, $aBackup)) {
			throw new \RuntimeException('Backup failed!');
		}

		$sSql = "
			SELECT
				*
			FROM
				`form_pages`
				";
		$aPages = \DB::getQueryRows($sSql);
		
		// Wenn diese Tabelle leer ist, darf der Check ausgeführt werden.
		if(!empty($aPages)) {
			return true;
		}

		$this->updateDataTables();
		$this->updatePages();
		$this->updateConditions();
		$this->updateChecks();

		return true;
	}
	
	public function updateDataTables() {
		
		$oDb = \DB::getDefaultConnection();
		$bDataExists = $oDb->checkTable('form_data');

		if($bDataExists === true) {

			set_time_limit(3600);
			ini_set('memory_limit', '2G');

			$aForms = \Form\Entity\Init::getRepository()->findAll();

			foreach($aForms as $oForm) {

				// Struktur
				$oForm->updateStructure();

				// Daten übernehmen
				$sSql = "
					SELECT 
						*
					FROM
						`form_data`
					WHERE
						`form_id` = :form_id
				";
				$aSql = [
					'form_id' => $oForm->id
				];
				$aEntries = (array)\DB::getQueryRows($sSql, $aSql);

				$aOptions = (array)$oForm->getJoinedObjectChilds('options');

				foreach($aEntries as $aEntry) {

					$aInsert = [
						'id' => $aEntry['id'],
						'date' => date('Y-m-d H:i:s', $aEntry['date']),
						'ip' => $aEntry['ip'],
						'data' => $aEntry['data'],
						'done' => $aEntry['done']
					];

					foreach($aOptions as $oOption) {
						if(!empty($aEntry['field_'.$oOption->id])) {
							$aInsert['field_'.$oOption->id] = $aEntry['field_'.$oOption->id];
						}
					}

					\DB::insertData('form_data_'.$oForm->id, $aInsert);
				}

			}

			$sSql = "RENAME TABLE `form_data` TO #backup";
			$aSql = [
				'backup' => '__'.date('Ymd').'_form_data'
			];

			\DB::executePreparedQuery($sSql, $aSql);

		}
	}
	
	private function updateConditions() {
		
		$sSql = "
			SELECT
				*
			FROM
				`form_options`
			WHERE
				`display_condition` != '' OR
				`display_conditions` != ''
		";
		$aOptionsWithConditions = \DB::getQueryRows($sSql);
		
		foreach($aOptionsWithConditions as $aOption) {
			
			$aDisplayConditions = [];
			if(!empty($aOption['display_conditions'])) {
				$aDisplayConditions = \Util::decodeSerializeOrJson($aOption['display_conditions']);
			}

			if(empty($aOption['display_value'])) {
				$aValues = [''];
			} else {
				$aValues = (array)\Util::decodeSerializeOrJson($aOption['display_value']);
			}

			foreach($aValues as $sValue) {

				$aNewCondition = [];

				if(!empty($aDisplayConditions)) {
					$aNewCondition['operator'] = 'OR';
				}

				$aNewCondition['field'] = $aOption['display_condition'];
				
				if(empty($sValue)) {
					$aNewCondition['mode'] = 3;
				} else {
					$aNewCondition['mode'] = 2;	
				}

				$aNewCondition['value'] = $sValue;

				$aDisplayConditions[] = $aNewCondition;

			}

			foreach($aDisplayConditions as $iKey=>$aDisplayCondition) {

				$aDisplayCondition['option_id'] = $aOption['id'];
				$aDisplayCondition['position'] = $iKey;
				
				if(empty($aDisplayCondition['mode'])) {
					if(empty($aDisplayCondition['value'])) {
						$aDisplayCondition['mode'] = 3;
					} else {
						$aDisplayCondition['mode'] = 2;
					}
				}
				
				\DB::insertData('form_options_conditions', $aDisplayCondition);
			}

		}

		\DB::executeQuery("ALTER TABLE `form_options` DROP `display_condition`");
		\DB::executeQuery("ALTER TABLE `form_options` DROP `display_conditions`");
		\DB::executeQuery("ALTER TABLE `form_options` DROP `display_value`");

	}
	
	private function updatePages() {
		
		$sSql = "
			SELECT
				*
			FROM
				`form_init`
				";
		$aForms = (array)\DB::getQueryRows($sSql);

		foreach($aForms as $aForm) {

			$aPage = [
				'id' => $aForm['id'],
				'form_id' => $aForm['id'],
				'name' => 'Seite 1',
				'position' => 1
			];

			\DB::insertData('form_pages', $aPage);

			$sSql = "
				UPDATE
					`form_options`
				SET
					`changed` = `changed`,
					`page_id` = :page_id
				WHERE
					`form_id` = :form_id
					";
			$aSql = [
				'page_id' => $aForm['id'],
				'form_id' => $aForm['id']
			];

			\DB::executePreparedQuery($sSql, $aSql);

		}
	}
	
	private function updateChecks() {
		
		$sSql = "
			SELECT
				*
			FROM
				`form_options`
			WHERE
				`check` != ''
		";
		$aOptionsWithChecks = \DB::getQueryRows($sSql);
		
		foreach($aOptionsWithChecks as $aOption) {
			
			if($aOption['check'] === '|') {
				
				$aUpdate = [
					'check' => ''
				];

				\DB::updateData(form_options, $aUpdate, ['id' => $aOption['id']]);

			} else {

				$aCheck = explode('|', $aOption['check']);

				if(count($aCheck) > 1) {
					$aUpdate = [
						'check' => (int)$aCheck[0],
						'validation' => (string)$aCheck[1]
					];

					\DB::updateData(form_options, $aUpdate, ['id' => $aOption['id']]);
				}
				
			}

		}
			
	}

}