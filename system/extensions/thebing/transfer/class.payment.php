<?php


class Ext_Thebing_Transfer_Payment extends Ext_Thebing_Payment_Provider_Abstract {

	protected $_sTable = 'kolumbus_transfers_payments';

	protected $_sTableAlias = 'ktrpa';

	protected function _getUniqueFields()
	{
		return array(
			'inquiry_transfer_id',
		);
	}

	/**
	 * @return Ext_TS_Inquiry_Journey_Transfer
	 */
	public function getJourneyTransfer() {
		return Ext_TS_Inquiry_Journey_Transfer::getInstance($this->inquiry_transfer_id);
	}

	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		$aSqlParts['select'] .= ",
			`ts_ijt`.`transfer_type` AS `transfer_type`,
			`ts_ijt`.`transfer_date` AS `transfer_date`,
			`ts_ijt`.`transfer_time` AS `transfer_time`,
			`kg`.`short` AS `inquiry_group_short`,
			`kg`.`number`,
			`ts_i`.`inbox` AS `inbox`
		";
		$aSqlParts['select'] .= static::getSqlPart('select');

		$aSqlParts['from'] .= static::getSqlPart('from');
		$aSqlParts['from'] .= " LEFT JOIN
			`ts_inquiries` `ts_i` ON
				`ts_i`.`id` = `ts_ij`.`inquiry_id` LEFT JOIN
			`kolumbus_groups` `kg` ON
				`kg`.`id` = `ts_i`.`group_id`
		";
        
        $aSqlParts['groupby'] .= '`ktrpa`.`id` ASC';
        $aSqlParts['orderby'] = 'NULL'; // da die sortierung nach ID wäre aber wir Gruppieren ist das hier performanter, muss nicht 2 mal sortiert werden
        

	}

	/**
	 * Query Teile auslagern
	 * Diese Methode wird auch von den Groupings verwendet, um suchen zu können
	 * @param $sPart
	 * @return string
	 */
	public static function getSqlPart($sPart) {

		if($sPart === 'select') {

			return ",
				`tc_c`.`firstname` AS `customer_firstname`,
				`tc_c`.`lastname` AS `customer_lastname`,
				`tc_cn`.`number` AS `customer_number`,
				`kid`.`document_number` AS `document_number`
			";

		} elseif($sPart === 'from') {

			$aInvoiceTypes = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice');
			$sInvoiceTypes = '\''.join('\',\'', $aInvoiceTypes).'\'';

			return " LEFT JOIN
				`ts_inquiries_journeys_transfers` `ts_ijt` ON
					`ts_ijt`.`id` = `ktrpa`.`inquiry_transfer_id` LEFT JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`id` = `ts_ijt`.`journey_id` AND
					`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' LEFT JOIN
				/*`ts_inquiries` `ts_i` ON
					`ts_i`.`id` = `ts_ij`.`inquiry_id` LEFT JOIN*/
				`ts_inquiries_to_contacts` `ts_itc` ON
					`ts_itc`.`inquiry_id` = `ts_ij`.`inquiry_id` AND (
						`ts_itc`.`type` = 'booker' OR
						`ts_itc`.`type` = 'traveller'
					) LEFT JOIN
				`tc_contacts` `tc_c` ON
					`tc_c`.`id` = `ts_itc`.`contact_id` LEFT JOIN
				`tc_contacts_numbers` `tc_cn` ON
					`tc_cn`.`contact_id` = `tc_c`.`id` LEFT JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`entity` = '".Ext_TS_Inquiry::class."' AND
					`kid`.`entity_id` = `ts_ij`.`inquiry_id` AND
					`kid`.`type` IN ( $sInvoiceTypes )
			";
		}

	}

}