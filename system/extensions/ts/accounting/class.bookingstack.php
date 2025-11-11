<?php

/**
 * @property string id
 * @property string changed
 * @property string created
 * @property string creator_id
 * @property string editor_id
 * @property string company_id
 * @property string school_id
 * @property string inbox_id
 * @property string document_number
 * @property string document_date
 * @property string stack_description
 * @property string position_description
 * @property string amount
 * @property string currency_iso
 * @property string account_number_income
 * @property string account_number_expense
 * @property string cost_center
 * @property string posting_key
 * @property string booking_date
 * @property string service_from
 * @property string service_until
 * @property string address_type
 * @property string address_type_id
 * @property string address_firstname
 * @property string address_lastname
 * @property string payment_id
 * @property string document_id
 * @property string automatic_account_income
 * @property string automatic_account_expense
 * @property string double_accounting
 * @property string address_type_object_name
 * @property string addressee_type_object_name
 * @property string qb_number
 * @property string $tax
 * @property string $tax_key
 * @property string $debit_credit
 * @property string $account_type
 * @property date $earliest_commencement
 * @property int $agency_id
 */
class Ext_TS_Accounting_BookingStack extends Ext_Thebing_Basic {

	/**
	 * @var string
	 */
	protected $_sTable = 'ts_booking_stacks';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'ts_bs';

	/**
	 * @var string
	 */
	protected $_sEditorIdColumn = 'editor_id';

	/**
	 * @var array
	 */
	protected $_aFormat = array(
		'document_date' => array(
			'validate' => 'DATE',
		),
		'booking_date' => array(
			'validate' => 'DATE',
		),
		'service_from' => array(
			'validate' => 'DATE',
		),
		'service_until' => array(
			'validate' => 'DATE',
		),
		'company_id' => array(
			'validate' => 'INT_POSITIVE',
		),
		'school_id' => array(
			'validate' => 'INT_POSITIVE',
		),
		'inbox_id' => array(
			'validate' => 'INT_NOTNEGATIVE',
		),
		/*'document_id' => array(
			'validate' => 'INT_POSITIVE',
		),*/
		'amount' => array(
			'validate' => 'FLOAT',
		),
		'automatic_account_income' => array(
			'validate' => 'INT',
		),
		'automatic_account_expense' => array(
			'validate' => 'INT',
		),
		'double_accounting' => array(
			'validate' => 'INT_NOTNEGATIVE',
		),
	);

	/**
	 * @inheritdoc
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		$aSqlParts['select'] .= ",
			`kid`.`type` `document_type`,
			CONCAT(`tc_c`.`lastname`, ', ', `tc_c`.`firstname`) `customer_name`,
			`tc_c`.`nationality`,
			`tc_cn`.`number` `customer_number`,
			`ts_cn`.`number` `agency_number`,
			IF (`ts_bs`.`address_type` = 'agency', `ts_cn`.`number`, `tc_cn`.`number`) `address_number`,
			IF (`ts_bs`.`address_type` = 'agency', `ts_com`.`ext_1`, CONCAT(`tc_c`.`lastname`, ', ', `tc_c`.`firstname`)) `address_full_name`,
			1 `quantity`,
			`ts_dvp`.`date` `due_date`,
			`ts_pc`.`comment` `payment_condition_comment`
		";

		$aSqlParts['from'] .= " LEFT JOIN
			`kolumbus_inquiries_documents` `kid` ON
				`kid`.`id` = `ts_bs`.`document_id` LEFT JOIN (
					`ts_inquiries_to_contacts` `ts_itc` INNER JOIN
					`tc_contacts` `tc_c`
				) ON
					`kid`.`entity` = '".Ext_TS_Inquiry::class."' AND
					`kid`.`entity_id` != 0 AND
					`ts_itc`.`inquiry_id` = `kid`.`entity_id` AND
					`ts_itc`.`type` = 'traveller' AND
					`tc_c`.`id` = `ts_itc`.`contact_id` LEFT JOIN
			`kolumbus_inquiries_documents_versions` `kidv` ON
				`kidv`.`id` = `kid`.`latest_version` LEFT JOIN
			`ts_payment_conditions` `ts_pc` ON
				`ts_pc`.`id` = `kidv`.`payment_condition_id`LEFT JOIN
			`tc_contacts_numbers` `tc_cn` ON
				`tc_cn`.`contact_id` = `tc_c`.`id` LEFT JOIN
			`ts_documents_versions_paymentterms` `ts_dvp` ON
				`ts_dvp`.`version_id` = `kid`.`latest_version` AND
				`ts_dvp`.`type` = 'final' LEFT JOIN
			`ts_companies` `ts_com` ON
				`ts_com`.`id` = `ts_bs`.`agency_id` LEFT JOIN
			`ts_companies_numbers` `ts_cn` ON
				`ts_cn`.`company_id` = `ts_bs`.`agency_id`
		";

		$aSqlParts['groupby'] .= "
			`ts_bs`.`id`
		";

	}
	
	/**
	 * @return array
	 */
	public function getCurrencyListFromEntries() {

		$aCurrencyList = array();

		$sSql = "
			SELECT
				`currency_iso`
			FROM
				#table
			WHERE
			    `currency_iso` != ''
			GROUP BY
				`currency_iso`
		";
		
		$aSql = array(
			'table' => $this->_sTable,
		);

		$aCol = (array)DB::getQueryCol($sSql, $aSql);

		foreach($aCol as $sIso) {
			$oCurrency = Ext_TC_Currency::getInstance($sIso);
			$aCurrencyList[$sIso] = $oCurrency->getSign();
		}
		
		return $aCurrencyList;
	}
    
    /**
     * @return Ext_Thebing_Inquiry_Document 
     */
    public function getDocument() {

        $oDocument = null;
        if($this->document_id > 0) {
            $oDocument = Ext_Thebing_Inquiry_Document::getInstance($this->document_id);
        }

        return $oDocument;
    }
    
    /**
     * @return \TsAccounting\Entity\Company
     */
    public function getCompany() {

        $oCompany = null;
        if($this->company_id > 0) {
            $oCompany = \TsAccounting\Entity\Company::getInstance($this->company_id);
        }

        return $oCompany;
    }

	/**
	 * Gibt zurück ob dem Bookingstack Informationen fehlen
	 *
	 * @return bool
	 */
	public function hasMissingInformations() {

		$oCompany = $this->getCompany();

		if(
			$oCompany &&
			$oCompany->hasSingleAccounting()
		) {
			$bMissingInfos = !$this->hasAccountNumberIncome();
		} else {
			$bMissingInfos = !$this->hasAccountNumberIncome() || !$this->hasAccountNumberExpense();
		}

		return $bMissingInfos;
	}

	/**
	 * Gibt zurück ob das Feld 'account_number_income' gefüllt ist
	 *
	 * @return bool
	 */
	public function hasAccountNumberIncome() {

		$sAccountNumberIncome = $this->account_number_income;
		$bRetVal = !empty($sAccountNumberIncome);

		return $bRetVal;
	}

	/**
	 * Gibt zurück ob das Feld 'account_number_expense' gefüllt ist
	 *
	 * @return bool
	 */
	public function hasAccountNumberExpense() {

		$sAccountNumberExpense = $this->account_number_expense;
		$bRetVal = !empty($sAccountNumberExpense);

		return $bRetVal;
	}

	public function isPaymentEntry(): bool {
		return !empty($this->payment_id);
	}

	protected function _getSqlForList($bCheckValid = true)
	{
		// TODO sollte das nicht standardmäßig rein?
		$sql = parent::_getSqlForList($bCheckValid);
		$sql .= " LIMIT 10000";
		return $sql;
	}
}
