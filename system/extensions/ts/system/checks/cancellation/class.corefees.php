<?php

class Ext_TS_System_Checks_Cancellation_CoreFees extends \GlobalChecks {

	public function getTitle() {
		return 'Cancellation fees';
	}

	public function getDescription() {
		return 'Prepare new cancellation fees structure';
	}

	public function executeCheck()
	{
		$existing = Ext_TC_Cancellationconditions_Group::query()->first();
		if ($existing !== null) {
			// Bereits durchgelaufen
			return true;
		}

		$backup = [
			\Util::backupTable('wdbasic_attributes'),
			\Util::backupTable('kolumbus_cancellation_groups'),
			\Util::backupTable('kolumbus_cancellation_fees'),
			\Util::backupTable('kolumbus_cancellation_fees_dynamic')
		];

		if (in_array(false, $backup)) {
			__pout('Backup error');
			return false;
		}

		\DB::begin(__METHOD__);

		try {

			// Einträge 1:1 übernehmen, IDs bleiben gleich

			$groups = \DB::getQueryData('SELECT * FROM `kolumbus_cancellation_groups` WHERE 1');

			foreach ($groups as $group) {
				$newGroup = \Illuminate\Support\Arr::only($group, ['id', 'changed', 'created', 'active', 'creator_id', 'name']);
				$newGroup['editor_id'] = $group['user_id'];
				\DB::insertData('tc_cancellation_conditions_groups', $newGroup);
			}

			$fees = \DB::getQueryData('SELECT * FROM `kolumbus_cancellation_fees` WHERE 1');

			foreach ($fees as $fee) {
				$newFee = \Illuminate\Support\Arr::only($fee, ['id', 'changed', 'created', 'active', 'creator_id', 'group_id', 'name', 'days', 'minimum_value']);
				$newFee['editor_id'] = $fee['user_id'];
				$newFee['currency_iso'] = ($fee['currency_id'] > 0)
					? Ext_Thebing_Currency::getInstance($fee['currency_id'])->getIso()
					: '';
				\DB::insertData('tc_cancellation_conditions_fees', $newFee);
			}

			$feesDynamic = \DB::getQueryData('SELECT * FROM `kolumbus_cancellation_fees_dynamic` WHERE 1');

			foreach ($feesDynamic as $index => $dynamic) {
				$newDynamic = \Illuminate\Support\Arr::only($dynamic, ['id', 'cancellation_fee_id', 'amount', 'kind', 'type', 'tax_category_id']);
				$newDynamic['position'] = $index;

				if (in_array($dynamic['type'], ['all', 'all_split'])) {
					// Alles (gesamt) und Alles (einzeln) bleibt wie bisher
					$newDynamic['type'] = $dynamic['type'];
					$newDynamic['selection'] = null;
				} else {
					// Alles andere als differenzierte Auswahl anlegen und das MS füllen
					$newDynamic['type'] = 'selection';
					if (is_numeric($dynamic['type'])) {
						// Zusatzleistungen standen bisher nur als ID in der Datenbank und erhalten ab sofort auch einen Präfix
						$selection = 'additional_cost_'.$dynamic['type'];
					} else {
						$selection = $dynamic['type'];
					}

					$newDynamic['selection'] = json_encode(\Illuminate\Support\Arr::wrap($selection));
				}

				\DB::insertData('tc_cancellation_conditions_fees_dynamic', $newDynamic);
			}

			// Attributes umschreiben

			\DB::updateData('wdbasic_attributes', ['entity' => 'tc_cancellation_conditions_groups'], ['entity' => 'kolumbus_cancellation_groups']);
			\DB::updateData('wdbasic_attributes', ['entity' => 'tc_cancellation_conditions_fees'], ['entity' => 'kolumbus_cancellation_fees']);
			\DB::updateData('wdbasic_attributes', ['entity' => 'tc_cancellation_conditions_fees_dynamic'], ['entity' => 'kolumbus_cancellation_fees_dynamic']);

			// Alte Struktur löschen

			\DB::executeQuery('DROP TABLE `kolumbus_cancellation_groups`');
			\DB::executeQuery('DROP TABLE `kolumbus_cancellation_fees`');
			\DB::executeQuery('DROP TABLE `kolumbus_cancellation_fees_dynamic`');

		} catch (\Error $e) {
			\DB::rollback(__METHOD__);
			__pout($e);
			return false;
		}

		\DB::commit(__METHOD__);

		return true;
	}

}