<?php

class Ext_Thebing_System_Checks_Documents_Items_ActivityPosition extends GlobalChecks {

	public function getTitle() {
		return 'Invoice item maintenance';
	}

	public function getDescription() {
		return 'Prepare for activity line items.';
	}

	public function executeCheck() {

		$aSchoolIds = array_column(Ext_Thebing_Client::getFirstClient()->getSchools(), 'id');

		$aPositionTitles = \Ext_Thebing_School_Positions::getAllPositions();

		foreach($aSchoolIds as $iSchoolId) {

			$sSql = "
				INSERT IGNORE INTO
					`kolumbus_positions_order`
				SET
					`active` = 1,
				    `title` = :title,
				    `school_id` = :school_id,
				    `position_key` = 'activity',
				    `position` = 11
			";

			DB::executePreparedQuery($sSql, [
				'school_id' => $iSchoolId,
				'title' => $aPositionTitles['activity']
			]);

		}

		return true;

	}

}
