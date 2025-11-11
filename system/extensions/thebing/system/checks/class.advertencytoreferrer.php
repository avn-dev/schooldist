<?php

class Ext_Thebing_System_Checks_AdvertencyToReferrer extends GlobalChecks {

	public function getTitle() {
		return 'All schools: How did you hear about us? (Referrer)';
	}

	public function getDescription() {
		return 'Make referrer options available for all schools.';
	}

	public function executeCheck() {

		$aTables = DB::listTables();
		if(in_array('kolumbus_hearaboutus', $aTables)) {

			Util::backupTable('kolumbus_hearaboutus');

			DB::executeQuery("TRUNCATE `tc_referrers`");
			DB::executeQuery("TRUNCATE `tc_referrers_i18n`");
			DB::executeQuery('TRUNCATE `ts_referrers_to_schools`');

			$sSql = "
				SELECT
					*
				FROM
					`kolumbus_hearaboutus`
			";

			$aReferrers = (array)DB::getQueryRows($sSql);
			foreach($aReferrers as $aReferrer) {

				if(empty($aReferrer['creator_id'])) {
					$aReferrer['creator_id'] = $aReferrer['user_id'];
				}
				
				// Eintrag anlegen
				$sSql = "
					INSERT INTO
						`tc_referrers`
					SET
						`id` = :id,
						`changed` = :changed,
						`created` = :created,
						`active` = :active,
						`creator_id` = :creator_id,
						`editor_id` = :user_id,
						`position` = :position
				";

				DB::executePreparedQuery($sSql, $aReferrer);

				// Schule ergänzen
				$sSql = "
					INSERT INTO
						`ts_referrers_to_schools`
					SET
						`referrer_id` = :id,
						`school_id` = :school_id
				";

				DB::executePreparedQuery($sSql, $aReferrer);

				// Sprachfelder in I18N-Tabelle importieren
				foreach($aReferrer as $sField => $sValue) {
					if(
						strpos($sField, 'name_') !== false &&
						!empty($sValue)
					) {

						list(, $sIso) = explode('_', $sField, 2);

						$sSql = "
							INSERT INTO
								`tc_referrers_i18n`
							SET
								`referrer_id` = :id,
								`language_iso` = :iso,
								`name` = :name
						";

						DB::executePreparedQuery($sSql, [
							'id' => $aReferrer['id'],
							'iso' => $sIso,
							'name' => $sValue
						]);

					}
				}

			}

			DB::executeQuery("DROP TABLE `kolumbus_hearaboutus`");

		}

		return true;

	}

}
