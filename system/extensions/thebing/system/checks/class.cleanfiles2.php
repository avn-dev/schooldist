<?php

class Ext_Thebing_System_Checks_CleanFiles2 extends Ext_Thebing_System_ThebingCheck {

	public function getTitle() {
		$sTitle = 'Clean files';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Removes unneeded files and directories.';
		return $sDescription;
	}

	public function isNeeded() {
		return true;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '512M');

		$aFiles = array(
			'/media/secure/import/',
			'/media/secure/passport/',
			'/media/secure/studentcards/'
		);

		// Kunden IDs holen
		$sSql = "
				SELECT
					*
				FROM
					`customer_db_1`
				WHERE
					`active` = 1";
		$aCustomers = DB::getQueryRows($sSql);

		$aTypes = array('jpg', 'tif', 'gif', 'png', 'pdf');

		// Passports
		foreach((array)$aCustomers as $aCustomer) {

			$iCustomerId = $aCustomer['id'];
			$iClientId = $aCustomer['office'];
			$iSchoolId = $aCustomer['ext_31'];

			foreach((array)$aTypes as $sType) {
				$sSource = \Util::getDocumentRoot().'storage/passport/passport_'.$iCustomerId.'.'.$sType;
				if(is_file($sSource)) {
					$sTargetDir = \Util::getDocumentRoot().'storage/clients/client_'.$iClientId.'/school_'.$iSchoolId.'/passport/';

					Util::checkDir($sTargetDir);

					$sTarget = $sTargetDir.'passport_'.$iCustomerId.'.'.$sType;

					rename($sSource, $sTarget);
					chmod($sTarget, 0777);

					break;

				}
			}

		}

		// Photos
		foreach((array)$aCustomers as $aCustomer) {

			$iCustomerId = $aCustomer['id'];
			$iClientId = $aCustomer['office'];
			$iSchoolId = $aCustomer['ext_31'];

			foreach((array)$aTypes as $sType) {
				$sSource = \Util::getDocumentRoot().'storage/studentcards/photo_'.$iCustomerId.'.'.$sType;
				if(is_file($sSource)) {
					$sTargetDir = \Util::getDocumentRoot().'storage/clients/client_'.$iClientId.'/school_'.$iSchoolId.'/studentcards/';

					Util::checkDir($sTargetDir);

					$sTarget = $sTargetDir.'photo_'.$iCustomerId.'.'.$sType;

					rename($sSource, $sTarget);
					chmod($sTarget, 0777);

					break;

				}
			}

		}

		foreach((array)$aFiles as $sFile) {
			$sFile = \Util::getDocumentRoot().$sFile;
			Ext_Thebing_Util::recursiveDelete($sFile);
		}

		return true;

	}

}
