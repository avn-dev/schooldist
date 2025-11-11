<?php

class Ext_Thebing_System_Checks_Fixteachercosts extends GlobalChecks {

	/**
	 * @return string
	 */
	public function getTitle() {
		$sTitle = 'Correction of the teacher costs';
		return $sTitle;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		$sDescription = '...';
		return $sDescription;
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	public function executeCheck() {

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		if(!Ext_Thebing_Util::backupTable('kolumbus_costprice_teacher')) {
			throw new RuntimeException('Backup failed!');
		}

		try {

			DB::begin(__CLASS__);

			/*
			 * Folgende Dinge müssen erledigt werden:
			 *
			 * 1. Doppelte Einträge müssen gefunden werden
			 * 2. Alle doppelten Einträge löschen (die höchste Id ist der aktuellste Eintrag)
			 * 3. Unique Index hinzufügen
			 * 4. Ungenutzte Felder löschen
			 */

			// 1. Doppelte Einträge müssen gefunden werden
			$sSql = "
				SELECT
					`course_id`,
					`costkategorie_id`,
					`school_id`,
					`saison_id`,
					`currency_id`,
					COUNT(*) `count`,
					MAX(`id`) `max_id`
				FROM
					`kolumbus_costprice_teacher`
				GROUP BY
					`course_id`,
					`costkategorie_id`,
					`school_id`,
					`saison_id`,
					`currency_id`
				HAVING
					`count` > 1
			";
			$aResult = (array)DB::getQueryData($sSql);

			foreach($aResult as $aRow) {

				// 2. Alle doppelten Einträge löschen (die höchste Id ist der aktuellste Eintrag)
				$sSql = "
					DELETE FROM
						`kolumbus_costprice_teacher`
					WHERE
						`course_id` = :course_id AND
						`costkategorie_id` = :costkategorie_id AND
						`school_id` = :school_id AND
						`saison_id` = :saison_id AND
						`currency_id` = :currency_id AND
						`id` != :id
				";
				DB::executePreparedQuery($sSql, array(
					'course_id' => $aRow['course_id'],
					'costkategorie_id' => $aRow['costkategorie_id'] ,
					'school_id' => $aRow['school_id'],
					'saison_id' => $aRow['saison_id'],
					'currency_id' => $aRow['currency_id'],
					'id' => $aRow['max_id']
				));

			}

			// 3. Unique Index hinzufügen
			DB::executeQuery("ALTER TABLE `kolumbus_costprice_teacher` ADD UNIQUE `unique1` (`course_id`, `costkategorie_id`, `school_id`, `saison_id`, `currency_id`)");

			// 4. Ungenutzte Felder löschen
			DB::executeQuery("ALTER TABLE `kolumbus_costprice_teacher` DROP `active`;");
			DB::executeQuery("ALTER TABLE `kolumbus_costprice_teacher` DROP `price_for_holiday`;");

			DB::commit(__CLASS__);

		} catch(Exception $ex) {

			DB::rollback(__CLASS__);
			throw $ex;

		}

		return true;
	}

}
