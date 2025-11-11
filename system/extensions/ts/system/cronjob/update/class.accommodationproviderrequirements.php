<?php

class Ext_TS_System_Cronjob_Update_AccommodationProviderRequirements extends Ext_Thebing_System_Server_Update {

	public function executeUpdate() {

		/** @var Ext_Thebing_Accommodation[] $aAccommodations */
		$aAccommodations = \Ext_Thebing_Accommodation::getRepository()->findAll();

		foreach($aAccommodations as $oAccommodation) {

			$oAccommodation->updateRequirementStatus();

		}

	}

	protected function _getExecuteHour() {
		return 1;
	}

}