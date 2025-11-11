<?php

class Ext_Thebing_System_Checks_AccommodationCostsImport extends GlobalChecks {

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		$bTableExists = Ext_Thebing_Util::ifTableExists('kolumbus_accommodations_costs');

		// Wenn diese Tabelle existiert wurde der Check schon ausgeführt
		if($bTableExists) {
			return true;
		}

		try {

			Ext_Thebing_Util::backupTable('kolumbus_costprice_accommodation');
			Ext_Thebing_Util::backupTable('kolumbus_accommodations_costs');
			Ext_Thebing_Util::backupTable('kolumbus_accommodations_costs_nights');
			Ext_Thebing_Util::backupTable('kolumbus_accommodations_costs_nights_periods');

			/**
			 * Tabellen anlegen
			 */

			$sSql = "CREATE TABLE IF NOT EXISTS `kolumbus_accommodations_costs_categories_categories` (
			  `accommodation_category_id` int(11) NOT NULL,
			  `category_id` int(11) NOT NULL,
			  UNIQUE KEY `kaccc_1` (`accommodation_category_id`,`category_id`),
			  KEY `category_id` (`category_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8";
			DB::executeQuery($sSql);
			$sSql = "TRUNCATE TABLE `kolumbus_accommodations_costs_categories_categories`";
			DB::executeQuery($sSql);

			$sSql = "CREATE TABLE IF NOT EXISTS `kolumbus_accommodations_costs` (
			  `id` int(11) NOT NULL auto_increment,
			  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
			  `active` tinyint(1) NOT NULL default '1',
			  `user_id` int(11) NOT NULL,
			  `costcategory_id` int(11) NOT NULL,
			  `accommodation_category_id` int(11) NOT NULL,
			  `roomtype_id` int(11) NOT NULL,
			  `meal_id` int(11) NOT NULL,
			  `saison_id` int(11) NOT NULL,
			  `currency_id` int(11) NOT NULL,
			  `week_id` int(11) NOT NULL,
			  `amount` decimal(16,5) NOT NULL default '0.00000',
			  PRIMARY KEY  (`id`),
			  KEY `roomtype_id` (`roomtype_id`),
			  KEY `meal_id` (`meal_id`),
			  KEY `saison_id` (`saison_id`),
			  KEY `accommodation_id` (`accommodation_category_id`),
			  KEY `costkategorie_id` (`costcategory_id`),
			  KEY `currency_id` (`currency_id`),
			  KEY `week_id` (`week_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
			DB::executeQuery($sSql);
			$sSql = "TRUNCATE TABLE `kolumbus_accommodations_costs`";
			DB::executeQuery($sSql);

			$sSql = "CREATE TABLE IF NOT EXISTS `kolumbus_accommodations_costs_nights` (
			  `id` int(11) NOT NULL auto_increment,
			  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
			  `active` tinyint(1) NOT NULL default '1',
			  `user_id` int(11) NOT NULL,
			  `period_id` int(11) NOT NULL,
			  `roomtype_id` int(11) NOT NULL,
			  `meal_id` int(11) NOT NULL,
			  `saison_id` int(11) NOT NULL,
			  `currency_id` int(11) NOT NULL,
			  `amount` decimal(16,5) NOT NULL default '0.00000',
			  PRIMARY KEY  (`id`),
			  KEY `roomtype_id` (`roomtype_id`),
			  KEY `meal_id` (`meal_id`),
			  KEY `saison_id` (`saison_id`),
			  KEY `accommodation_id` (`period_id`),
			  KEY `currency_id` (`currency_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
			DB::executeQuery($sSql);
			$sSql = "TRUNCATE TABLE `kolumbus_accommodations_costs_nights`";
			DB::executeQuery($sSql);

			$sSql = "CREATE TABLE IF NOT EXISTS `kolumbus_accommodations_costs_nights_periods` (
			  `id` int(11) NOT NULL auto_increment,
			  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
			  `active` tinyint(4) NOT NULL,
			  `user_id` int(11) NOT NULL,
			  `costcategory_id` int(11) NOT NULL,
			  `accommodation_category_id` int(11) NOT NULL,
			  `from` date NOT NULL default '0000-00-00',
			  `until` date NOT NULL default '0000-00-00',
			  PRIMARY KEY  (`id`),
			  KEY `categorie_id` (`costcategory_id`),
			  KEY `active` (`active`),
			  KEY `from` (`from`),
			  KEY `until` (`until`),
			  KEY `accommodation_category_id` (`accommodation_category_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
			DB::executeQuery($sSql);
			$sSql = "TRUNCATE TABLE `kolumbus_accommodations_costs_nights_periods`";
			DB::executeQuery($sSql);

			/**
			 * Daten vorbereiten
			 */
			$sSql = "
				SELECT
					*
				FROM
					`kolumbus_accommodations_costs_weeks`
				WHERE
					`active` = 1
				";
			$aTemps = DB::getQueryRows($sSql);
			$aWeeks = array();
			$aExtraWeeks = array();
			foreach((array)$aTemps as $aTemp) {
				$aWeeks[$aTemp['idSchool']][$aTemp['id']] = $aTemp;
				if($aTemp['extra'] == 1) {
					$aExtraWeeks[$aTemp['id']] = $aTemp['id'];
				}
			}

			$sSql = "
				SELECT
					*
				FROM
					`kolumbus_periods`
				WHERE
					`active` = 1 AND
					`saison_for_accommodationcost` = 1
				";
			$aTemps = DB::getQueryRows($sSql);
			$aSaisons = array();
			foreach((array)$aTemps as $aTemp) {
				$aSaisons[$aTemp['id']] = $aTemp;
			}

			/**
			 * Import
			 */
			$sSql = "
				SELECT
					*
				FROM
					`kolumbus_costprice_accommodation`
				WHERE
					`active` = 1
				";
			$aItems = DB::getQueryRows($sSql);

			foreach((array)$aItems as $aItem) {

				if(
					empty($aItem['costkategorie_id']) ||
					empty($aItem['accommodation_id']) ||
					empty($aItem['roomtype_id']) ||
					empty($aItem['meal_id']) ||
					empty($aItem['saison_id']) ||
					empty($aItem['currency_id'])
				) {
					continue;
				}

				if($aItem['week_id'] > 0) {

					// Extrawoche als Extranacht speichern
					if(isset($aExtraWeeks[$aItem['week_id']])) {
						$aData = array();
						$aData['created'] = $aItem['created'];
						$aData['active'] = 1;
						$aData['costcategory_id'] = $aItem['costkategorie_id'];
						$aData['accommodation_category_id'] = $aItem['accommodation_id'];
						$aData['roomtype_id'] = $aItem['roomtype_id'];
						$aData['meal_id'] = $aItem['meal_id'];
						$aData['saison_id'] = $aItem['saison_id'];
						$aData['currency_id'] = $aItem['currency_id'];
						$aData['week_id'] = -1;
						$aData['amount'] = $aItem['amount'];
						DB::insertData('kolumbus_accommodations_costs', $aData);
					}

					$aData = array();
					$aData['created'] = $aItem['created'];
					$aData['active'] = 1;
					$aData['costcategory_id'] = $aItem['costkategorie_id'];
					$aData['accommodation_category_id'] = $aItem['accommodation_id'];
					$aData['roomtype_id'] = $aItem['roomtype_id'];
					$aData['meal_id'] = $aItem['meal_id'];
					$aData['saison_id'] = $aItem['saison_id'];
					$aData['currency_id'] = $aItem['currency_id'];
					$aData['week_id'] = $aItem['week_id'];
					$aData['amount'] = ($aItem['amount'] * 7);
					DB::insertData('kolumbus_accommodations_costs', $aData);

				} else {

					$aSaison = $aSaisons[$aItem['saison_id']];

					if(empty($aSaison)) {
						continue;
					}

					// Gibt es schon eine Periode
					$sSql = "
						SELECT
							`id`
						FROM
							`kolumbus_accommodations_costs_nights_periods`
						WHERE
							`costcategory_id` = :costcategory_id AND
							`accommodation_category_id` = :accommodation_category_id AND
							`active` = 1
						";
					$aSql = array(
						'costcategory_id'=>(int)$aItem['costkategorie_id'],
						'accommodation_category_id'=>(int)$aItem['accommodation_id']
					);
					$iPeriodId = DB::getQueryOne($sSql, $aSql);

					if(!$iPeriodId) {
						$aData = array();
						$aData['created'] = $aItem['created'];
						$aData['active'] = 1;
						$aData['costcategory_id'] = $aItem['costkategorie_id'];
						$aData['accommodation_category_id'] = $aItem['accommodation_id'];
						$aData['from'] = $aSaison['valid_from'];
						$aData['until'] = $aSaison['valid_until'];
						$iPeriodId = DB::insertData('kolumbus_accommodations_costs_nights_periods', $aData);
					}

					$aData = array();
					$aData['created'] = $aItem['created'];
					$aData['active'] = 1;
					$aData['period_id'] = $iPeriodId;
					$aData['roomtype_id'] = $aItem['roomtype_id'];
					$aData['meal_id'] = $aItem['meal_id'];
					$aData['saison_id'] = $aItem['saison_id'];
					$aData['currency_id'] = $aItem['currency_id'];
					$aData['amount'] = $aItem['amount'];
					DB::insertData('kolumbus_accommodations_costs_nights', $aData);

				}

			}

			$sSql = "DROP TABLE `kolumbus_costprice_accommodation`";
			DB::executeQuery($sSql);

		} catch(Exception $e) {
			__pout($e);
			Ext_Thebing_Util::reportError('Ext_Thebing_System_Checks_AccommodationCostsImport error', $e);
		}

		return true;

	}

	public function getTitle() {
		$sTitle = 'Imports accommodation costs in new structure';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = '...';
		return $sDescription;
	}

}