<?php

/**
 * Speichert zu den Dokumentpositionen den Betrag (net, discount, vat), der durch Specials abgezogen wurde
 * 
 * @author Mark Koopmann
 */
class Ext_Thebing_System_Checks_Documents_Items_IndexSpecials extends GlobalChecks
{
	// Kein Backup notwendig, da keine Änderung an vorhandenen Daten
	public $bBackupTable = true;

	public function getTitle() {
		return 'Create index of special offer items';
	}

	public function getDescription() {
		return 'To improve the speed of the statistics.';
	}

	public function executeCheck() {

		set_time_limit(7200);
		ini_set('memory_limit', '2048M');

		if($this->bBackupTable) {
			Util::backupTable('kolumbus_inquiries_documents_versions_items');
		}

		$oDb = DB::getDefaultConnection();

//		DB::addField('kolumbus_inquiries_documents_versions_items', 'index_special_amount_gross', 'DECIMAL(15,5) NULL', 'additional_info');
//		DB::addField('kolumbus_inquiries_documents_versions_items', 'index_special_amount_net', 'DECIMAL(15,5) NULL', 'index_special_amount_gross');
//		DB::addField('kolumbus_inquiries_documents_versions_items', 'index_special_amount_gross_vat', 'DECIMAL(15,5) NULL', 'index_special_amount_net');
//
//		if(!$oDb->checkField('kolumbus_inquiries_documents_versions_items', 'index_special_amount_vat')) {
//			DB::addField('kolumbus_inquiries_documents_versions_items', 'index_special_amount_net_vat', 'DECIMAL(15,5) NULL', 'index_special_amount_gross_vat');
//		} else {
//			// Alte Spalte index_special_amount_vat umbenennen
//			DB::executeQuery('ALTER TABLE `kolumbus_inquiries_documents_versions_items` CHANGE `index_special_amount_vat` `index_special_amount_net_vat` DECIMAL(15,5)');
//		}

		$sSql = "
			SELECT
				`kidvi`.`id`,
				`kidvi2`.`onPdf`,
				SUM(
					`kidvi2`.`amount` -
					`kidvi2`.`amount` / 100 * `kidvi2`.`amount_discount` -
					-- Bei Steuern inklusive steht die Steuer mit im Item, daher entfernen
					IF(
						`kidv`.`tax` = 1,
						`kidvi2`.`amount` - (`kidvi2`.`amount` / (`kidvi2`.`tax` / 100 + 1)),
						0
					)
				) `amount_gross`,
				SUM(
					`kidvi2`.`amount_net` -
					`kidvi2`.`amount_net` / 100 * `kidvi2`.`amount_discount` -
					-- Bei Steuern inklusive steht die Steuer mit im Item, daher entfernen
					IF(
						`kidv`.`tax` = 1,
						`kidvi2`.`amount_net` - (`kidvi2`.`amount_net` / (`kidvi2`.`tax` / 100 + 1)),
						0
					)
				) `amount_net`,
				SUM(
					`kidvi2`.`amount` * (`kidvi2`.`tax` / 100)
				) `amount_gross_vat`,
				SUM(
					`kidvi2`.`amount_net` * (`kidvi2`.`tax` / 100)
				) `amount_net_vat`
			FROM
				`kolumbus_inquiries_documents_versions_items` `kidvi` INNER JOIN
				`kolumbus_inquiries_documents_versions_items` `kidvi2` ON
					`kidvi2`.`type` = 'special' AND
					`kidvi2`.`parent_id` = `kidvi`.`id` AND
					`kidvi2`.`parent_type` = 'item_id' AND
					`kidvi2`.`active` = 1 /*AND
					`kidvi2`.`onPdf` = 1*/ LEFT JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kidvi`.`version_id` = `kidv`.`id`
			WHERE
				`kidvi`.`active` = 1 AND
				`kidvi`.`type` != 'special'
			GROUP BY
				`kidvi`.`id`
			ORDER BY
				`kidvi`.`id` DESC
			";

		$oCollection = $oDb->getCollection($sSql);

		foreach($oCollection as $aItem) {

			if(!$aItem['onPdf']) {
				$aItem['amount_gross'] = null;
				$aItem['amount_net'] = null;
				$aItem['amount_gross_vat'] = null;
				$aItem['amount_net_vat'] = null;
			}

			$aData = array(
				'index_special_amount_gross' => $aItem['amount_gross'],
				'index_special_amount_net' => $aItem['amount_net'],
				'index_special_amount_gross_vat' => $aItem['amount_gross_vat'],
				'index_special_amount_net_vat' => $aItem['amount_net_vat']
			);

			DB::updateData('kolumbus_inquiries_documents_versions_items', $aData, '`id` = '.(int)$aItem['id']);

		}

		// Alte Spalte muss nicht zwangsläufig existieren, da diese im Check erst später ergänzt wurde
//		if($oDb->checkField('kolumbus_inquiries_documents_versions_items', 'index_special_amount_processed', true)) {
//			DB::executeQuery("ALTER TABLE `kolumbus_inquiries_documents_versions_items` DROP `index_special_amount_processed`;");
//		}

		return true;
		
	}

}