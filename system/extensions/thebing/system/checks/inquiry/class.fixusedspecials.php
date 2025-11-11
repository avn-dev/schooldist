<?php
/**
 * Specials als benutzt markieren, die auch benutzt wurden
 *
 * Ext_Thebing_Inquiry_Special_Position::markPosition() hat zuvor nie funktioniert,
 * 	daher wurden alle Service-Specials niemals als benutzt markiert
 *
 * https://redmine.thebing.com/redmine/issues/6333
 */
class Ext_Thebing_System_Checks_Inquiry_FixUsedSpecials extends GlobalChecks {

	protected $_aLog = array();

	public function getTitle() {
		$sTitle = 'Check used specials';
		return $sTitle;
	}

	public function getDescription() {
		return self::getTitle();
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set('memory_limit', '1024M');

		Util::backupTable('kolumbus_inquiries_positions_specials');
		Util::backupTable('kolumbus_inquiries_documents_versions_items_specials');

		$sSql = "
			SELECT
				`kid`.`inquiry_id`,
				`kidvi`.`id` `item_id`
			FROM
				`kolumbus_inquiries_documents_versions_items` `kidvi` INNER JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kidv`.`id` = `kidvi`.`version_id` INNER JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`id` = `kidv`.`document_id`
			WHERE
				`kidvi`.`type` = 'special'
			GROUP BY
				`kidvi`.`id`
		";

		$aResult = (array)DB::getQueryRows($sSql);

		DB::begin(__CLASS__);

		foreach($aResult as $aData) {
			$oInquiry = Ext_TS_Inquiry::getInstance($aData['inquiry_id']);
			$oItem = Ext_Thebing_Inquiry_Document_Version_Item::getInstance($aData['item_id']);

			Ext_Thebing_Inquiry_Special_Position::markPosition($oInquiry, $oItem);

			$this->logInfo(sprintf('Flagged item %d of inquiry %d', $oItem->id, $oInquiry->id));
		}

		DB::commit(__CLASS__);

		return true;
	}

}
