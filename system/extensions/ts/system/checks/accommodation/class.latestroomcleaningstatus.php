<?php

class Ext_TS_System_Checks_Accommodation_LatestRoomCleaningStatus extends GlobalChecks {

	/**
	 * @return string
	 */
	public function getTitle() {
		return 'Accommodation';
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return 'Update accommodation room cleaning status';
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set('memory_limit', '1G');

		$sql = "SELECT * FROM `ts_accommodation_cleaning_status` WHERE `active` = 1 LIMIT 1";
		$row = \DB::getQueryRow($sql);

		if (empty($row)) {
			// Putzplan wird gar nicht benutzt
			return true;
		}

		$backup = \Util::backupTable('ts_accommodation_rooms_latest_cleaning_status');
		if (!$backup) {
			__pout('Backup failed!');
			return false;
		}

		$roomIdsWithStatus = \DB::getQueryCol("SELECT DISTINCT `room_id` FROM `ts_accommodation_cleaning_status`");

		/* @var \Ext_Thebing_Accommodation_Room[] $rooms */
		$rooms = \Ext_Thebing_Accommodation_Room::getRepository()->findAll();

		\DB::begin(__METHOD__);

		try {

			foreach ($rooms as $room) {

				if (!in_array($room->getId(), $roomIdsWithStatus)) {
					continue;
				}

				$beds = $room->getNumberOfBeds();

				\DB::executePreparedQuery("DELETE FROM `ts_accommodation_rooms_latest_cleaning_status` WHERE `room_id` = :room_id", ['room_id' => $room->getId()]);

				for ($bed = 1; $bed <= $beds; ++$bed) {

					$latestStatus = \TsAccommodation\Entity\Cleaning\Status::getRepository()
						->getLastStatus($room->getId(), $bed);

					if ($latestStatus) {
						\DB::insertData('ts_accommodation_rooms_latest_cleaning_status', ['room_id' => $room->getId(), 'bed' => $bed, 'status' => $latestStatus->status]);
					}

				}

			}

		} catch (\Throwable $e) {
			\DB::rollback(__METHOD__);
			return false;
		}

		\DB::commit(__METHOD__);

		return true;
	}

}
