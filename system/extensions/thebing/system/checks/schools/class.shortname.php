<?php

class Ext_Thebing_System_Checks_Schools_ShortName extends GlobalChecks {

	public function getTitle() {
		return 'Add short name / nickname to schools';
	}

	public function getDescription() {
		return 'You are required to set these nicknames now. You can always change them in school settings afterwards.';
	}

	public function executeCheck() {
		global $_VARS;

		$aSchools = $this->getSchools();
		$aErrors = [];

		foreach($aSchools as $oSchool) {
			$sField = 'short_'.$oSchool->id;
			if(empty($_VARS[$sField])) {
				$aErrors[] = 'Please input a nickname for '.$oSchool->name.'!';
			}
		}

		if(!empty($aErrors)) {
			echo join('<br>', $aErrors);
			return false;
		}

		foreach($aSchools as $oSchool) {

			$sSql = "
				UPDATE
					`customer_db_2`
				SET
					`short` = :short
				WHERE
					`id` = {$oSchool->id}
			";

			// Als Query ausführen, da save() unerwünschte Fehler schmeißen könnte
			DB::executePreparedQuery($sSql, ['short' => $_VARS['short_'.$oSchool->id]]);

		}

		return true;
	}

	public function printFormContent() {

		$aSchools = $this->getSchools();

		printTableStart();

		foreach($aSchools as $oSchool) {
			printFormText('Nickname for '.$oSchool->name.' (required)', 'short_'.$oSchool->id, $oSchool->short);
		}

		printTableEnd();

		parent::printFormContent();

	}

	/**
	 * @return Ext_Thebing_School[]
	 */
	private function getSchools() {
		return Ext_Thebing_School::getRepository()->findAll();
	}

}
