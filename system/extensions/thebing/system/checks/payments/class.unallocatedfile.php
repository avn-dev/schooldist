<?php

class Ext_Thebing_System_Checks_Payments_UnallocatedFile extends GlobalChecks {

	public function getTitle() {
		return 'Update unallocated payments structure';
	}

	public function getDescription() {
		return self::getTitle();
	}

	public function executeCheck() {

        $columnsExists = \DB::getDefaultConnection()->checkField('ts_inquires_payments_unallocated', 'file', true);
        if(!$columnsExists) {
            // Check wurde bereits ausgeführt
            return true;
        }

		Util::backupTable('ts_inquires_payments_unallocated');

		$sql = "
			SELECT
				`id`,
			    `file`
			FROM
				`ts_inquires_payments_unallocated`
			WHERE
				`file` != ''
		";

		$result = (array)DB::getQueryRows($sql);

		foreach ($result as $row) {
			DB::updateData('ts_inquires_payments_unallocated', [
				'additional_info' => json_encode(['type' => 'flywire_sync', 'file' => $row['file']])
			], ['id' => $row['id']]);
		}

		DB::executeQuery("ALTER TABLE `ts_inquires_payments_unallocated` DROP `file`");

		return true;

	}
}
