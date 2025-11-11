<?php

class Ext_Thebing_System_Checks_Accommodation_SetPublished extends GlobalChecks {

	/**
	 * @return string
	 */
	public function getTitle() {
		return 'Set Accommodation Published';
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return 'Set true to accommodation published';
	}

	/**
	 * @return bool
	 */
	public function executeCheck() {

		try {
			Ext_Thebing_Util::backupTable('kolumbus_accommodations_uploads');
			// Alle Uploads zur Kommunikation freigeben
			$sSQL = "UPDATE `kolumbus_accommodations_uploads` SET `kolumbus_accommodations_uploads`.`published` = 1;";
			DB::executeQuery($sSQL);
			return true;
		}
		catch(Exception $ex) {
			return false;
		}

	}

} 