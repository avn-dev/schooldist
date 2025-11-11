<?php

class Ext_TS_System_Checks_Payment_PaymentConditions2 extends GlobalChecks {

	private $aDueTypesMapping = [
		0 => 'document_date',
		1 => 'course_start_date',
		2 => 'course_start_date_month_end'
	];

	private $aPaymentConditionMapping = [];

	public function getTitle() {
		return 'Migrate payment conditions to new structure';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {

		$aTables = DB::listTables();
		if(!in_array('ts_agencies_payments_groups', $aTables)) {
			return true;
		}

		Util::backupTable('ts_payment_conditions');
		Util::backupTable('ts_payment_conditions_amounts');
		Util::backupTable('ts_payment_conditions_percents');
		Util::backupTable('ts_agencies_payments_groups');
		Util::backupTable('ts_agencies_payments_groups_to_payment_conditions');
		Util::backupTable('kolumbus_agencies_payments_groups_assignments');

		$this->migratePaymentConditions();

		$this->migrateAgencyPaymentConditions();

		DB::executeQuery("DROP TABLE ts_payment_conditions_amounts");
		DB::executeQuery("DROP TABLE ts_payment_conditions_percents");
		DB::executeQuery("DROP TABLE ts_agencies_payments_groups");
		DB::executeQuery("DROP TABLE ts_agencies_payments_groups_to_payment_conditions");
		DB::executeQuery("DROP TABLE kolumbus_agencies_payments_groups_assignments");

		DB::executeQuery("
			ALTER TABLE ts_payment_conditions
				DROP `status`,
				DROP first_due_days,
				DROP first_due_direction,
				DROP first_due_status,
				DROP final_due_days,
				DROP final_due_direction,
				DROP final_due_status
		");

		return true;

	}

	public function migratePaymentConditions() {

		DB::executeQuery("TRUNCATE ts_payment_conditions_settings");
		DB::executeQuery("TRUNCATE ts_payment_conditions_settings_amounts");

		DB::begin(__METHOD__);

		$aPaymentConditionKeys = [];

		$sSql = "
			SELECT
				id,
				payment_condition_id
			FROM
				customer_db_2
			WHERE
				active = 1
		";
		$aSchoolPaymentConditionIds = DB::getQueryPairs($sSql);

		$sSql = "
			SELECT
				ts_pc.*,
				ts_apg.id agency_condition_id,
				ts_apg.name agency_condition_name,
				ts_apg.comment agency_condition_comment,
				ts_apg.active agency_condition_active,
				cdb2.id school_id,
				cdb2.short school_name
			FROM
				ts_payment_conditions ts_pc LEFT JOIN
				ts_agencies_payments_groups_to_payment_conditions ts_apgtpc ON
					ts_apgtpc.payment_condition_id = ts_pc.id LEFT JOIN
				ts_agencies_payments_groups ts_apg ON
					ts_apg.id = ts_apgtpc.group_id INNER JOIN
				customer_db_2 cdb2 ON
					cdb2.payment_condition_id = ts_pc.id OR
					cdb2.id = ts_apgtpc.school_id
			WHERE
				ts_pc.active = 1
			GROUP BY
				ts_pc.id
			ORDER BY
				ts_apg.position, school_id
		";

		$aResult = (array)DB::getQueryRows($sSql);
		foreach($aResult as $iRow => $aRow) {

			$aUpdateData = [
				'active' => 1,
				'comment' => $aRow['agency_condition_comment'],
				'position' => ++$iRow
			];

			$aUpdateData['name'] = $aRow['school_name'];
			if(!empty($aRow['agency_condition_id'])) {
				$aUpdateData['name'] = 'Agency - '.$aRow['agency_condition_name'].' - '.$aUpdateData['name'];

				// Agency Payment Condition hat gelöschte Schule oder ist selbst gelöscht (hier ist nichts kaskadiert)
				if(
					empty($aRow['agency_condition_active']) ||
					!isset($aSchoolPaymentConditionIds[$aRow['school_id']])
				) {
					$aUpdateData['active'] = 0;
					$this->logInfo('Agency payment condition deleted', ['row' => $aRow, 'school_pc_ids' => $aSchoolPaymentConditionIds]);
				}
			} else {
				$aUpdateData['name'] = 'School - '.$aUpdateData['name'];

				// Payment Conditions von gelöschten Schulen werden nicht weiter benötigt
				// Da wegen der vorherigen bescheuerten Struktur eine Payment Condition durchs Klonen mehrfach zugewiesen sein kann,
				// muss das umständlich per GROUP BY und vorherigem Query geprüft werden
				if(!in_array($aRow['id'], $aSchoolPaymentConditionIds)) {
					$aUpdateData['active'] = 0;
					$this->logInfo('School payment condition deleted', ['row' => $aRow, 'school_pc_ids' => $aSchoolPaymentConditionIds]);
				}
			}

			// Amount / Percent von Anzahlung
			$aDepositChilds = [];
			if(!empty($aRow['status'])) {

				if((int)$aRow['status'] === 1) {
					$sSql = "SELECT * FROM ts_payment_conditions_amounts WHERE payment_condition_id = :id AND active = 1";
					$aDepositChilds = (array)DB::getQueryRows($sSql, ['id' => $aRow['id']]);
				} else {
					$sSql = "SELECT * FROM ts_payment_conditions_percents WHERE payment_condition_id = :id AND active = 1";
					$aDepositChilds = (array)DB::getQueryRows($sSql, ['id' => $aRow['id']]);
				}

			}

			$sMergeKey = $this->buildPaymentConditionKey($aRow, $aDepositChilds);

			// Wenn bereits vorhanden: Löschen (aber keine Bedingungen der Schule direkt)
			if(
				!empty($aRow['agency_condition_id']) &&
				isset($aPaymentConditionKeys[$sMergeKey])
			) {
				$aUpdateData['active'] = 0;

				$this->aPaymentConditionMapping[$aRow['id']] = $aPaymentConditionKeys[$sMergeKey];

				DB::executePreparedQuery("UPDATE ts_payment_conditions SET `name` = CONCAT(`name`, ' / ', :school_name) WHERE id = :id", [
					'id' => $aPaymentConditionKeys[$sMergeKey],
					'school_name' => $aRow['school_name']
				]);

				$this->logInfo('Merged payment_condition '.$aRow['id'].' into '.$aPaymentConditionKeys[$sMergeKey]);

				// Schule umschreiben
				//if(empty($aRow['agency_condition_id'])) {
				//	DB::updateData('customer_db_2', ['payment_condition_id' => $aPaymentConditionKeys[$sMergeKey]], ['id' => $aRow['school_id']]);
				//	$this->logInfo('Set school '.$aRow['school_id'].' payment_condition_id from '.$aRow['id'].' to '.$aPaymentConditionKeys[$sMergeKey]);
				//}
			}

			//$aUpdateData['comment'] = $sMergeKey."\n\n".$aUpdateData['comment'];

			DB::updateData('ts_payment_conditions', $aUpdateData, ['id' => $aRow['id']]);

			if(empty($aUpdateData['active'])) {
				continue;
			}

			$iPosition = 1;

			// Amount / Percent von Anzahlung
			if(!empty($aRow['status'])) {

				$iSettingId = DB::insertData('ts_payment_conditions_settings', [
					'payment_condition_id' => $aRow['id'],
					'type' => 'deposit',
					'due_days' => $aRow['first_due_days'],
					'due_direction' => $aRow['first_due_direction'] == 1 ? 'after' : 'before',
					'due_type' => $this->aDueTypesMapping[$aRow['first_due_status']],
					'position' => $iPosition++
				]);

				// Eingestellte Beträge übernehmen
				if((int)$aRow['status'] === 1) {
					foreach($aDepositChilds as $aAmount) {
						DB::insertData('ts_payment_conditions_settings_amounts', [
							'setting_id' => $iSettingId,
							'setting' => 'amount',
							'type' => 'currency',
							'type_id' => $aAmount['currency_id'],
							'amount' => $aAmount['amount']
						]);
					}
				} else {
					foreach($aDepositChilds as $aPercent) {
						DB::insertData('ts_payment_conditions_settings_amounts', [
							'setting_id' => $iSettingId,
							'setting' => 'percent',
							'type' => $aPercent['type'],
							'type_id' => $aPercent['type_id'],
							'amount' => $aPercent['amount_percent']
						]);
					}
				}

			}

			// Restzahlung (gibt es immer)
			DB::insertData('ts_payment_conditions_settings', [
				'payment_condition_id' => $aRow['id'],
				'type' => 'final',
				'due_days' => $aRow['final_due_days'],
				'due_direction' => $aRow['final_due_direction'] == 1 ? 'after' : 'before',
				'due_type' => $this->aDueTypesMapping[$aRow['final_due_status']],
				'position' => $iPosition
			]);

			if(!empty($aRow['agency_condition_id'])) {
				$aPaymentConditionKeys[$sMergeKey] = $aRow['id'];
			}

		}

		DB::commit(__METHOD__);

	}

	/**
	 * Key für Payment Condition zusammenbauen, damit man gleiche Conditions mergen kann
	 *
	 * @param array $aRow
	 * @param array $aDepositChilds
	 * @return string
	 */
	private function buildPaymentConditionKey(array $aRow, array $aDepositChilds) {

		$aCurrencyIds = array_keys(Ext_Thebing_Data_Currency::getCurrencyList());
		$aPercentTypeSort = ['all', 'course', 'accommodation', 'insurance', 'additionalcourse', 'additionalaccommodation'];

		// Werte wurden mal gespeichert, sind aber nicht aktiviert
		if(empty($aRow['status'])) {
			$aRow['first_due_days'] = 0;
			$aRow['first_due_direction'] = 0;
			$aRow['first_due_status'] = 0;
		}

		$aKey[] = $aRow['status'];
		$aKey[] = $aRow['first_due_days'];
		$aKey[] = $aRow['first_due_direction'];
		$aKey[] = $aRow['first_due_status'];
		$aKey[] = $aRow['final_due_days'];
		$aKey[] = $aRow['final_due_direction'];
		$aKey[] = $aRow['final_due_status'];

		if((int)$aRow['status'] === 1) {

			usort($aDepositChilds, function($aAmount1, $aAmount2) use($aCurrencyIds) {
			    $iPos1 = array_search($aAmount1['currency_id'], $aCurrencyIds);
				$iPos2 = array_search($aAmount2['currency_id'], $aCurrencyIds);
			    return $iPos1 - $iPos2;
			});

			$aKey2 = [];
			foreach($aDepositChilds as $aAmount) {
				$aKey2[] = $aAmount['currency_id'].'|'.$aAmount['amount'];
			}

			$aKey[] = join('-', $aKey2);

		} else {

			usort($aDepositChilds, function($aPercent1, $aPercent2) use($aPercentTypeSort) {
			    $iPos1 = array_search($aPercent1['type'], $aPercentTypeSort);
				$iPos2 = array_search($aPercent2['type'], $aPercentTypeSort);
			    return $iPos1 - $iPos2;
			});

			$aKey2 = [];
			foreach($aDepositChilds as $aAmount) {
				$aKey2[] = $aAmount['amount_percent'].'|'.$aAmount['type'].'|'.$aAmount['type_id'];
			}

			$aKey[] = join('-', $aKey2);

		}

		return join('_', $aKey);

	}

	private function migrateAgencyPaymentConditions() {

		DB::executeQuery("TRUNCATE ts_agencies_payment_conditions_validity");

		DB::begin(__METHOD__);

		$sSql = "
			SELECT
				kapga.*,
				GROUP_CONCAT(CONCAT(ts_apgtpc.payment_condition_id, '_', ts_apgtpc.school_id)) school_ids
			FROM
				kolumbus_agencies_payments_groups_assignments kapga LEFT JOIN
				ts_agencies_payments_groups_to_payment_conditions ts_apgtpc ON
					ts_apgtpc.group_id = kapga.group_id INNER JOIN
				ts_payment_conditions ts_pc ON
					ts_pc.id = ts_apgtpc.payment_condition_id AND
					ts_pc.active = 1 INNER JOIN
				customer_db_2 cdb2 ON
					cdb2.id = ts_apgtpc.school_id AND
					cdb2.active = 1
			WHERE
				kapga.active = 1
			GROUP BY
				kapga.id
		";

		$aResult = (array)DB::getQueryRows($sSql);

		foreach($aResult as $aRow) {

			$aSchoolsPerCondition = explode(',', $aRow['school_ids']);

			// Keine Ahnung, welchen Sinn das hatte
			if(!empty($aRow['description'])) {
				$aRow['comment'] = $aRow['description']."\n".$aRow['comment'];
			}

			// In der Agentur-Bezahlbedingungen konnten pro Schule Bezahlbedigungen festgelegt werden (ts_pc)
			// ts_pc ist jetzt All Schools und die Zuweisungen zu Schulen wird zukünfig über die Validiy gemacht
			// Somit gibt es auch eine Einstellung für All Schools, wo zuvor IMMER die Bedingungen pro Schule gepflegt werden mussten
			foreach($aSchoolsPerCondition as $sTmp) {

				list($iPaymentConditionId, $iSchoolId) = explode('_', $sTmp, 2);

				if(isset($this->aPaymentConditionMapping[$iPaymentConditionId])) {
					$iPaymentConditionId = $this->aPaymentConditionMapping[$iPaymentConditionId];
				}

				DB::insertData('ts_agencies_payment_conditions_validity', [
					'created' => $aRow['created'],
					'changed' => $aRow['changed'],
					'active' => $aRow['active'],
					'creator_id' => (int)$aRow['creator_id'],
					'editor_id' => (int)$aRow['user_id'],
					'agency_id' => $aRow['agency_id'],
					'payment_condition_id' => $iPaymentConditionId,
					'school_id' => count($aSchoolsPerCondition) > 1 ? $iSchoolId : null,
					'valid_from' => $aRow['valid_from'],
					'valid_until' => $aRow['valid_until'],
					'comment' => $aRow['comment'],
				]);

			}
		}

		DB::commit(__METHOD__);

	}

}
