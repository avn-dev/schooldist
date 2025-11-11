<?php

class Ext_TS_System_Checks_Activity_BlockTravellerUnique extends GlobalChecks {

	public function getTitle() {
		return 'Activity block allocations database check';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {

		Util::backupTable('ts_activities_blocks_travellers');

		$allocation = [];

		$sql = "	
			SELECT
				t1.id id1,
				t2.id id2
			FROM
				ts_activities_blocks_travellers t1 INNER JOIN
				ts_activities_blocks_travellers t2 ON
					t2.block_id = t1.block_id AND
					t2.traveller_id = t1.traveller_id AND
					t2.journey_activity_id = t1.journey_activity_id AND
					t2.week = t1.week AND
					t2.id != t1.id
			ORDER BY
				t1.active DESC,
				t1.id
		";

		$rows = (array)DB::getQueryRows($sql);

		$this->logInfo('Duplicate blocks', $rows);

		foreach ($rows as $row) {

			if ($allocation[$row['id1']]) {
				$allocation[$row['id1']][] = $row['id2'];
				$this->logInfo(sprintf('Merge %d into %d', $row['id2'], $row['id1']));
				continue;
			}

			if ($allocation[$row['id2']]) {
				$allocation[$row['id2']][] = $row['id1'];
				$this->logInfo(sprintf('Merge %d into %d', $row['id1'], $row['id2']));
				continue;
			}

			foreach ($allocation as $allocateTo => $ids) {
				if (in_array($row['id2'], $ids)) {
					$allocation[$allocateTo][] = $row['id2'];
					$this->logInfo(sprintf('Merge %d into %d', $row['id2'], $allocateTo));
					continue 2;
				}
			}

			$allocation[$row['id1']] = [$row['id2']];

		}

		$this->logInfo('Allocations', $rows);

		foreach ($allocation as $ids) {
			$ids = array_unique($ids);
			foreach ($ids as $id) {
				$this->logInfo('Delete '.$id);
				DB::executePreparedQuery("DELETE FROM ts_activities_blocks_travellers WHERE id = :id", ['id' => $id]);
			}
		}

		DB::executeQuery(" ALTER TABLE `ts_activities_blocks_travellers` ADD UNIQUE(`block_id`, `traveller_id`, `journey_activity_id`, `week`) ");

		return true;

	}

}
