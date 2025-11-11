<?php

class Ext_TS_System_Checks_Inquiry_Journey_TransferMode extends GlobalChecks {

	public function getTitle() {
		return 'Migration of booking transfer mode';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {

		$fields = DB::describeTable('ts_inquiries', true);
		if (!isset($fields['tsp_transfer'])) {
			return true;
		}

		Util::backupTable('ts_inquiries');

		$sql = "
			SELECT
				ts_i.id inquiry_id,
				ts_i.tsp_transfer transfer_mode,
			    ts_i.tsp_comment transfer_comment,
			    ts_ij.id journey_id
			FROM
				ts_inquiries ts_i LEFT JOIN
				ts_inquiries_journeys ts_ij ON
					ts_ij.inquiry_id = ts_i.id
			WHERE
			    tsp_transfer != '' AND
			    tsp_transfer != 'no'
		";

		$rows = (array)DB::getQueryRows($sql);
		foreach ($rows as $row) {

			if (empty($row['journey_id'])) {
				$this->logError(sprintf('No journey for inquiry %d', $row['inquiry_id']));
				continue;
			}

			$row['journey_transfer_mode'] = $this->calculateTransferMode($row['transfer_mode']);
			if (empty($row['journey_transfer_mode'])) {
				$this->logError(sprintf('Unknown transfer_mode: %s (%d)', $row['transfer_mode'], $row['inquiry_id']));
				continue;
			}

			$sql = "
				UPDATE
					ts_inquiries_journeys
				SET
					transfer_mode = :journey_transfer_mode,
				    `changed` = `changed`
				WHERE
					id = :journey_id
			";

			$this->logInfo(sprintf('Set journey transfer_mode to %s (%d)', $row['transfer_mode'], $row['journey_id']));

			DB::executePreparedQuery($sql, $row);

			$this->migrateTransferComment((int)$row['journey_id'], $row['transfer_comment']);

		}

		DB::executeQuery(" ALTER TABLE ts_inquiries DROP tsp_transfer ");
		DB::executeQuery(" ALTER TABLE ts_inquiries DROP tsp_comment ");

		return true;

	}

	public function calculateTransferMode(string $mode): int {

		switch ($mode) {
			case 'arrival':
				return \Ext_TS_Inquiry_Journey::TRANSFER_MODE_ARRIVAL;
			case 'departure':
				return \Ext_TS_Inquiry_Journey::TRANSFER_MODE_DEPARTURE;
			case 'arr_dep':
				return \Ext_TS_Inquiry_Journey::TRANSFER_MODE_BOTH;
		}

		return 0;

	}

	public function migrateTransferComment(int $journeyId, string $comment) {

		if (empty($comment)) {
			return;
		}

		$sql = "
			REPLACE INTO
				`wdbasic_attributes`
			SET
				`entity` = :entity,
			    `entity_id` = :entity_id,
			    `key` = :key,
			    `value` = :value
		";

		DB::executePreparedQuery($sql, [
			'entity' => 'ts_inquiries_journeys',
			'entity_id' => $journeyId,
			'key' => 'transfer_comment',
			'value' => $comment
		]);

	}

}
