<?php

class Ext_TS_System_Checks_Tuition_Attendance_UniqueAllocation extends GlobalChecks
{
	public function getTitle()
	{
		return 'Attendance Maintenance';
	}

	public function getDescription()
	{
		return 'Check attendance entries.';
	}

	public function executeCheck()
	{
		Util::backupTable('kolumbus_tuition_attendance');

		DB::begin(__CLASS__);

		// Müll entfernen (sollte gar nicht existieren)
		DB::executeQuery("DELETE FROM kolumbus_tuition_attendance WHERE allocation_id = 0");

		// Alle gelöschten Einträge entfernen, die einen aktiven Eintrag mit gleicher Zuweisung haben
		$sql = "
			SELECT
				a.id id1,
				b.id id2
			FROM
				kolumbus_tuition_attendance a INNER JOIN
				kolumbus_tuition_attendance b ON
					b.allocation_id = a.allocation_id AND
					b.active = 1
			WHERE
			    a.active = 0
		";
		foreach ((array)DB::getQueryRows($sql) as $row) {
			DB::executePreparedQuery("DELETE FROM kolumbus_tuition_attendance WHERE id = :id1", $row);
			$this->logInfo(sprintf('Deleted deleted attendance entry %d, keeping %d (active 1)', $row['id1'], $row['id2']));
		}

		$this->deleteDoubleAllocations(0);

		// Komische Fälle bei NYLC: Mehrere Einträge mit gleicher allocation_id und active 1 (immer aufeinander folgend)
		$this->deleteDoubleAllocations(1);

		DB::commit(__CLASS__);

		DB::executeQuery("ALTER TABLE kolumbus_tuition_attendance ADD UNIQUE(allocation_id)");

		return true;
	}

	private function deleteDoubleAllocations(int $active)
	{
		$sql = "
			SELECT
				a.id id1,
				b.id id2
			FROM
				kolumbus_tuition_attendance a INNER JOIN
				kolumbus_tuition_attendance b ON
					b.allocation_id = a.allocation_id AND
					b.id != a.id AND
					b.active = :active
			WHERE
				a.active = :active
			ORDER BY
				a.id
		";

		$deletedIds = [];
		foreach ((array)DB::getQueryRows($sql, ['active' => $active]) as $row) {
			if (in_array($row['id1'], $deletedIds) || in_array($row['id2'], $deletedIds)) {
				continue;
			}

			DB::executePreparedQuery("DELETE FROM kolumbus_tuition_attendance WHERE id = :id2", $row);
			$this->logInfo(sprintf('Deleted double attendance entry %d, keeping %d (active 1)', $row['id2'], $row['id1']));
			$deletedIds[] = $row['id2'];
		}
	}
}