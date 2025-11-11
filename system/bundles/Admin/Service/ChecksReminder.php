<?php

namespace Admin\Service;

class ChecksReminder {

	public static function run(){

		if(!empty(\System::d('system_update_locked_by'))) {

			$sSql = "
				SELECT 
					`id`
				FROM
					`system_global_checks`
				WHERE
					`locked` = 1
			";

			$aResult = \DB::getQueryRows($sSql);

			if(empty($aResult)) {

				$iUserId = (int)\System::d('system_update_locked_by');
				$oUser = \User::getInstance($iUserId);

				$aVariables = [
					'sFirstname' => $oUser->firstname,
					'sLastname' => $oUser->lastname,
					'sProjectName' => \System::d('project_name'),
					'sProjectLink' => ''
				];

				$oEmail = new \Admin\Helper\Email('Admin');
				$bSuccess = $oEmail->send('checks_reminder', [$oUser->email], $aVariables);

			}

		}

	}

}