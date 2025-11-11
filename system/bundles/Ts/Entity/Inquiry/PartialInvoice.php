<?php

namespace Ts\Entity\Inquiry;

class PartialInvoice extends \Ext_Thebing_Basic {
	
	protected $_sTable = 'ts_inquiries_partial_invoices';
	protected $_sTableAlias = 'ts_ipi';
	
	protected $_sSortColumn = 'date';
	
	protected $_aJoinedObjects = [
		'inquiry' => [
			'class'	=> '\Ext_TS_Inquiry',
			'key' => 'inquiry_id',
			'check_active' => true,
			'type' => 'parent',
		],
	];

	public function manipulateSqlParts(&$aSqlParts, $sView = null): void
	{
		$aSqlParts['select'] .= ", 
			`tc_cn`.`number` `customer_number`,
			`tc_c`.`id` `customer_id`,
			`tc_c`.`firstname` `customer_firstname`,
			`tc_c`.`lastname` `customer_lastname`,
			`ts_i`.`currency_id`,
			`ts_i`.`partial_invoices_terms`,
			`ts_i`.`number` `booking_number`,
			`tc_e`.`email`,
			GROUP_CONCAT(DISTINCT `ts_ijc`.`course_id` ORDER BY `ts_ijc`.`from`) `courses`,
			`kidv`.`path` `file`,
			(
				SELECT 
					CONCAT(COUNT(IF(`ts_ipi_sub`.`converted` IS NOT NULL, 1, NULL)), ' / ', COUNT(`ts_ipi_sub`.`id`) , '|', COALESCE(GROUP_CONCAT(DISTINCT `ts_ipi_sub`.`document_id`), ''))
				FROM 
					`ts_inquiries_partial_invoices` `ts_ipi_sub`
				WHERE 
					`ts_ipi`.`inquiry_id` = `ts_ipi_sub`.`inquiry_id`
			) `partial_invoices_info`
		";

		$aSqlParts['from'] .= " JOIN
			`ts_inquiries` `ts_i` ON
				`ts_ipi`.`inquiry_id` = `ts_i`.`id` JOIN
			`ts_inquiries_to_contacts` `ts_i_to_c` ON
				`ts_i_to_c`.`inquiry_id` = `ts_i`.`id` AND
				`ts_i_to_c`.`type` = 'traveller' LEFT JOIN
			`tc_contacts` `tc_c` ON
				`tc_c`.`id` = `ts_i_to_c`.`contact_id` AND
				`tc_c`.`active` = 1	JOIN
			`tc_contacts_numbers` `tc_cn` ON
				`tc_cn`.`contact_id` = `tc_c`.`id` LEFT JOIN
			`tc_contacts_to_emailaddresses` `tc_cte` ON
				`tc_cte`.`contact_id` = `tc_c`.`id` LEFT JOIN
			`tc_emailaddresses` `tc_e` ON
				`tc_cte`.`emailaddress_id` = `tc_e`.`id` LEFT JOIN
			`ts_inquiries_journeys` `ts_ij` ON
				`ts_ij`.`inquiry_id` = `ts_i`.`id` LEFT JOIN
			`ts_inquiries_journeys_courses` `ts_ijc` ON
				`ts_ij`.`id` = `ts_ijc`.`journey_id` LEFT JOIN
			`kolumbus_inquiries_documents` `kid` ON
				`kid`.`id` = `ts_ipi`.`document_id` AND
				`kid`.`active` = 1 LEFT JOIN
			`kolumbus_inquiries_documents_versions` `kidv` ON
				`kid`.`latest_version` = `kidv`.`id` AND
				`kidv`.`active` = 1
			";

		$aSqlParts['groupby'] = '`ts_ipi`.`id`';

	}
	
	public function isNext() {
		
		$sSql = "
			SELECT 
				`id`
			FROM 
				`ts_inquiries_partial_invoices`
			WHERE
				`inquiry_id` = :inquiry_id AND
				`converted` IS NULL
			ORDER BY
				`date`
			LIMIT 1
			";
		$iInvoiceId = \DB::getQueryOne($sSql, ['inquiry_id'=>$this->inquiry_id]);

		if($this->id == $iInvoiceId) {
			return true;
		}
		
		return false;
	}
	
	public function isLatestConverted() {
		
		$sSql = "
			SELECT 
				`id`
			FROM 
				`ts_inquiries_partial_invoices`
			WHERE
				`inquiry_id` = :inquiry_id AND
				`converted` IS NOT NULL
			ORDER BY
				`date` DESC
			LIMIT 1
			";
		$iInvoiceId = \DB::getQueryOne($sSql, ['inquiry_id'=>$this->inquiry_id]);

		if($this->id == $iInvoiceId) {
			return true;
		}
		
		return false;
	}
	
	public function getAdditional() {
		return json_decode($this->additional, true);
	}
	
	public function getSetting() {
		
		$additional = $this->getAdditional();
		if(!empty($additional['setting_id'])) {
			$setting = \Ext_TS_Payment_Condition_Setting::getInstance($additional['setting_id']);
		} else {
			$setting = new \Ext_TS_Payment_Condition_Setting;
			$setting->type = $this->type;
			$setting->amounts = [
				[
					'setting' => 'amount',
					'type' => 'currency',
					'type_id' => $this->getJoinedObject('inquiry')->currency_id,
					'amount' => $this->amount
				]
			];
		}
		
		return $setting;
	}

	public function getRow() {
		
		$row = new \Ext_TS_Document_PaymentCondition_Row();
		$row->sType = 'deposit';
		$row->dDate = new \DateTime($this->date);
		$row->fAmount = $this->amount;
		$row->iSettingId = $this->id;
		$row->aSettingData = json_decode($this->additional, true);

		return $row;
	}
	
}
