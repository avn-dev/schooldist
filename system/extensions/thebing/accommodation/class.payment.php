<?php

/**
* @property $id 
* @property $accommodation_id 	
* @property $inquiry_id 	
* @property $customer_id 	
* @property $costcategory_id 	
* @property $inquiry_accommodation_id 	
* @property $grouping_id 	
* @property $timepoint 	
* @property $created 	
* @property $comment 	
* @property $comment_single 	
* @property $payment_note 	
* @property $method_id 	
* @property $active 	
* @property $creator_id 	
* @property $user_id 	
* @property $amount 	
* @property $amount_school 	
* @property $payment_currency_id 	
* @property $school_currency_id 	
* @property $transaction_id 	
* @property $payment_type 	
* @property $date 	
* @property $imported 	
* @property $parent_id 	
* @property $room_id 	
* @property $meal_id 	
* @property $select_type 	
* @property $select_value 	
* @property $current 	
* @property $total 	
* @property $nights 	
* @property $single_amount 	
* @property $cost_type
*/


class Ext_Thebing_Accommodation_Payment extends Ext_Thebing_Payment_Provider_Abstract {

	// Tabellenname
	protected $_sTable = 'kolumbus_accommodations_payments';

	protected $_sTableAlias = 'kap';

	/**
	 * @return array|bool|mixed
	 */
	public function delete() {

		DB::begin('Ext_Thebing_Accommodation_Payment::delete');
		
		$mDelete = parent::delete();

		// Zugehörige Allocation wieder auf nicht abgerechnet setzen
		if($this->allocation_id > 0) {
			$oAllocation = Ext_Thebing_Accommodation_Allocation::getInstance($this->allocation_id);
			$oAllocation->payment_generation_completed = null;
			$oAllocation->save();
		}

		DB::commit('Ext_Thebing_Accommodation_Payment::delete');

		return $mDelete;
	}

	protected function _getUniqueFields()
	{
		return array(
			'accommodation_id',
			'inquiry_accommodation_id',
			'room_id',
			'timepoint',
		);
	}
	
	public function getFromDate()
	{
		$oDatePaymentDateFrom = new WDDate($this->timepoint, WDDate::DB_DATE);
		return $oDatePaymentDateFrom;
	}

	/**
	 * @deprecated
	 */
	public function getUntilDate() {
		
		$mUntil = $this->until;

		if(
			$mUntil !== null &&
			WDDate::isDate($mUntil, WDDate::DB_DATE)
		) {
			$oDatePaymentDateUntil = new WDDate($mUntil, WDDate::DB_DATE);
			return $oDatePaymentDateUntil;
		}

		$oDatePaymentDateFrom = new WDDate($this->timepoint, WDDate::DB_DATE);

		switch($this->select_type){
			case 'week':
				$oDatePaymentDateUntil = $oDatePaymentDateFrom;
				$oDatePaymentDateUntil->add(7, WDDate::DAY);
				break;
			case 'month':
				$aMonthLimits = $oDatePaymentDateFrom->getMonthLimits();
				$oDatePaymentDateUntil = new WDDate($aMonthLimits['end']);
				break;
		}

		return $oDatePaymentDateUntil;
	}
	
	/**
	 * @todo Aus der include.saveeditdialogdata die checkPaymentStatus hier reinbringen
	 * 
	 * @return type 
	 */
	public function validatePayment()
	{
		return array();
	}

	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		$aSqlParts['select'] .= static::getSqlPart('select');

		$aSqlParts['from'] .= static::getSqlPart('from');

		$aSqlParts['groupby'] = "
			`kap`.`id`
		";
	}

	public static function getSqlPart($sPart) {

		if($sPart === 'select') {

			return ",
				-- GUI Suche --
				`tc_c`.`firstname` AS `customer_firstname`,
				`tc_c`.`lastname` AS `customer_lastname`,
				`tc_cn`.`number` AS `customer_number`,
				`kg`.`number` AS `group_number`,
				`kid`.`document_number` AS `document_number`,
				`ts_i`.`inbox` AS `inbox`
			";

		} elseif($sPart === 'from') {

			$aInvoiceTypes = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice');
			$sInvoiceTypes = '\''.join('\',\'', $aInvoiceTypes).'\'';

			return " LEFT JOIN
				`tc_contacts` `tc_c` ON
					`tc_c`.`id` = `kap`.`customer_id` AND
					`kap`.`select_type` != 'month' LEFT JOIN
				`tc_contacts_numbers` `tc_cn` ON
					`tc_cn`.`contact_id` = `tc_c`.`id` LEFT JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`entity` = '".Ext_TS_Inquiry::class."' AND
					`kid`.`entity_id` = `kap`.`inquiry_id` AND
					`kid`.`type` IN ( $sInvoiceTypes ) LEFT JOIN
				`ts_inquiries` `ts_i` ON
					`ts_i`.`id` = `kap`.`inquiry_id` LEFT JOIN
				`kolumbus_groups` `kg` ON
						`kg`.`id` = `ts_i`.`group_id` 
			";
		}

	}
}