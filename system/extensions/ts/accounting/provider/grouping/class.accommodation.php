<?php

use Communication\Interfaces\Model\CommunicationSubObject;

class Ext_TS_Accounting_Provider_Grouping_Accommodation extends Ext_TS_Accounting_Provider_Grouping_Abstract {

	protected $_sTable = 'ts_accommodations_payments_groupings';
	protected $_sTableAlias = 'ts_apg';

	protected $_aJoinedObjects = array(
		'payments' => array(
			'class' => \Ext_Thebing_Accommodation_Payment::class,
			'key' => 'grouping_id',
			'type' => 'child',
			'check_active' => true,
			'on_delete' => 'cascade'
		),
		'accommodation_provider' => array(
			'class' => \Ext_Thebing_Accommodation::class,
			'key' => 'accommodation_id',
			'type' => 'parent',
			'readonly' => true,
		)
	);

	/**
	 * Sagt aus ob dieser Datensatz weiterverarbeitet wurde oder nicht
	 *
	 * @return bool
	 */
	public function isProcessed() {

		if(!empty($this->processed)) {
			return true;
		}

		return false;

	}

	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		$language = Ext_Thebing_School::fetchInterfaceLanguage();

		$aSqlParts['select'] .= ",
			-- GUI Spalten --
			`cdb4`.`ext_33` AS `accommodation_name`,
			`cdb4`.`ext_68` AS `accommodation_bank_account_holder`,
			`cdb4`.`ext_70` AS `accommodation_bank_account_number`,
			`cdb4`.`ext_71` AS `accommodation_bank_account_code`,
			`cdb4`.`ext_69` AS `accommodation_bank_name`,
			`cdb4`.`ext_72` AS `accommodation_bank_address`,
			`cdb4`.`email` AS `accommodation_email`,
			`cdb4`.`bank_account_iban` AS `accommodation_iban`,
			`cdb4`.`bank_account_bic` AS `accommodation_bic`,
			`cdb4`.`socialsecuritynumber` AS `accommodation_social_security_number`,
			`kac`.`name_{$language}` AS `accommodation_category`,
			`kac`.`cost_center`,
			`ts_apgh`.`absolute_path`
		";
		$aSqlParts['select'] .= Ext_Thebing_Accommodation_Payment::getSqlPart('select');

		$oSchool = Ext_Thebing_School::getSchoolFromSession();		
		$aSqlParts['from'] .= " INNER JOIN
			`customer_db_4` `cdb4` ON
				`ts_apg`.`accommodation_id` = `cdb4`.`id` INNER JOIN
			`ts_accommodation_providers_schools` `ts_aps` ON
					`ts_aps`.`accommodation_provider_id` = `cdb4`.`id` AND
					`ts_aps`.`school_id` = ".(int)$oSchool->id." INNER JOIN
			`kolumbus_accommodations_categories` as `kac` ON
					`kac`.`id` = `cdb4`.`default_category_id` LEFT JOIN
			`kolumbus_accommodations_payments` `kap` ON
				`kap`.`grouping_id` = `ts_apg`.`id` LEFT JOIN
			`ts_accommodations_payments_groupings_to_histories` `ts_apgth` ON
				`ts_apgth`.`payment_grouping_id` = `ts_apg`.`id` LEFT JOIN
			`ts_accommodations_payments_groupings_histories` `ts_apgh` ON
				`ts_apgth`.`history_id` = `ts_apgh`.`id`
		";
		$aSqlParts['from'] .= Ext_Thebing_Accommodation_Payment::getSqlPart('from');

		// Nur anzeigen, wenn Schüler auch zur aktuell ausgewählten Schule gehört
		$aSqlParts['from'] .= " INNER JOIN
			`ts_inquiries_journeys` `ts_ij` ON
				`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
				`ts_ij`.`active` = 1 AND
				`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
				`ts_ij`.`school_id` = {$oSchool->id}
		";

		$aSqlParts['groupby'] = "
			`ts_apg`.`id`
		";

	}

	public function getOldPlaceholderObject(SmartyWrapper $oSmarty=null) {
		$oAccommodation = $this->getItem();
		$oPlaceholder = new Ext_TS_Accounting_Provider_Grouping_Accommodation_Placeholder($oAccommodation, $this);
		return $oPlaceholder;
	}

    /**
     * @return Ext_Thebing_Accommodation
     */
	public function getItem() {
		return Ext_Thebing_Accommodation::getInstance($this->accommodation_id);
	}

	public function getType() {
		return 'accommodation';
	}

	public function getNumber(): ?string {
		return $this->number;
	}

	public function generateNumber(): ?string {

		$numberRange = TsAccounting\Service\NumberRange\AccommodationPaymentGrouping::getObject($this);

		if(empty($this->getNumber()) && $numberRange != null) {
			$this->number = $numberRange->generateNumber();
			$this->numberrange_id = $numberRange->id;
			return $this->number;
		}

		return null;
	}

	public function save($bLog = true) {

		if(empty($this->getNumber())) {
			$this->generateNumber();
		}

		return parent::save($bLog);
	}

	public function getAccommodationProvider(): \Ext_Thebing_Accommodation {
		return $this->getJoinedObject('accommodation_provider');
	}

	public function getCommunicationDefaultApplication(): string
	{
		return \TsAccounting\Communication\Application\AccommodationPayments::class;
	}

	public function getCommunicationLabel(\Tc\Service\LanguageAbstract $l10n): string
	{
		return $l10n->translate('Unterkunftsanbieterzahlung');
	}

	public function getCommunicationSubObject(): CommunicationSubObject
	{
		$firstSchoolId = \Illuminate\Support\Arr::first($this->getAccommodationProvider()->schools);
		return Ext_Thebing_School::getInstance($firstSchoolId);
	}
}