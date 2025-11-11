<?php 

class Ext_Thebing_System_Checks_TeacherAbsence extends GlobalChecks {

	public function isNeeded(){
		global $user_data;

		return true;
		
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		$sSql = "CREATE TABLE IF NOT EXISTS `kolumbus_absence_categories` (
		  `id` int(11) NOT NULL auto_increment,
		  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
		  `active` tinyint(1) NOT NULL default '1',
		  `user_id` int(11) NOT NULL,
		  `client_id` int(11) NOT NULL,
		  `name` varchar(100) NOT NULL,
		  `color` varchar(10) NOT NULL,
		  PRIMARY KEY  (`id`),
		  KEY `active` (`active`),
		  KEY `user_id` (`user_id`),
		  KEY `client_id` (`client_id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
		DB::executeQuery($sSql);

		$sSql = "CREATE TABLE IF NOT EXISTS `kolumbus_absence` (
		  `id` int(11) NOT NULL auto_increment,
		  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
		  `active` tinyint(1) NOT NULL,
		  `user_id` int(11) NOT NULL,
		  `school_id` int(11) NOT NULL,
		  `item` varchar(40) NOT NULL,
		  `item_id` int(11) NOT NULL,
		  `from` date NOT NULL,
		  `until` date NOT NULL,
		  `category_id` int(11) NOT NULL,
		  `comment` text NOT NULL,
		  PRIMARY KEY  (`id`),
		  KEY `active` (`active`),
		  KEY `user_id` (`user_id`),
		  KEY `school_id` (`school_id`),
		  KEY `item` (`item`),
		  KEY `item_id` (`item_id`),
		  KEY `from` (`from`),
		  KEY `until` (`until`),
		  KEY `category_id` (`category_id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
		DB::executeQuery($sSql);

		Ext_Thebing_Util::backupTable('ts_teachers');
		Ext_Thebing_Util::backupTable('kolumbus_absence_categories');
		Ext_Thebing_Util::backupTable('kolumbus_teacher_dates');

		$sSql = "TRUNCATE TABLE `kolumbus_absence_categories`";
		DB::executeQuery($sSql);

		$sSql = "SELECT
					`ktd`.*,
					DATE(FROM_UNIXTIME(`day`)) `date`
				FROM
					`kolumbus_teacher_dates` `ktd` JOIN
					`ts_teachers` `kt` ON
						`ktd`.`idTeacher` = `kt`.`id` AND
						`kt`.`active` = 1
				WHERE
					1
				ORDER BY
					`ktd`.`idTeacher`,
					`ktd`.`day`
					";
		$aDates = DB::getQueryRows($sSql);

		$aEntries = array();
		$aSchools = array();
		$aClients = array();
		$iIndex = 0;
		$iLastTeacher = 0;
		$oLastDate = false;
		foreach((array)$aDates as $aDate) {

			if($iLastTeacher != $aDate['idTeacher']) {
				$oLastDate = false;
			}

			$oDate = new WDDate($aDate['date'], WDDate::DB_DATE);

			if($oLastDate) {
				$iDiff = $oLastDate->getDiff(WDDate::DAY, $oDate);
				if($iDiff < -1) {
					$iIndex++;
				}
			} else {
				$iIndex++;
			}
			if(!isset($aEntries[$iIndex])) {
				$aEntries[$iIndex] = $aDate;
				$aEntries[$iIndex]['from'] = $oDate->get(WDDate::DB_DATE);
			}
			$aEntries[$iIndex]['until'] = $oDate->get(WDDate::DB_DATE);

			$aSchools[$aDate['idSchool']] = $aDate['idSchool'];
			$aClients[$aDate['idClient']] = $aDate['idClient'];

			$iLastTeacher = $aDate['idTeacher'];
			$oLastDate = $oDate;

		}

		// Standardkategorie anlegen
		foreach((array)$aClients as $iClient=>$iValue) {

			$oCategory = new Ext_Thebing_Absence_Category();
			$oCategory->active = 1;
			$oCategory->color = '#2E97E0';
			$oCategory->name = 'Standard';
			$oCategory->client_id = (int)$iClient;
			$oCategory->save();

			$aClients[$iClient] = $oCategory->id;

		}

		foreach((array)$aEntries as $aEntry) {

			$aInsert = array();
			$aInsert['created'] = date('YmdHis');
			$aInsert['active'] = 1;
			$aInsert['school_id'] = (int)$aEntry['idSchool'];
			$aInsert['item'] = 'teacher';
			$aInsert['item_id'] = (int)$aEntry['idTeacher'];
			$aInsert['from'] = $aEntry['from'];
			$aInsert['until'] = $aEntry['until'];
			$aInsert['category_id'] = (int)$aClients[$aEntry['idClient']];

			DB::insertData('kolumbus_absence', $aInsert);

		}

		$sSql = "DROP TABLE `kolumbus_teacher_dates`";
		DB::executeQuery($sSql);

		return true;
		
	}
	
	
	
}
