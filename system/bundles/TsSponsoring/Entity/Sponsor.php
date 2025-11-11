<?php

namespace TsSponsoring\Entity;

/**
 * @method static SponsorRepository getRepository()
 */
class Sponsor extends \Ext_TC_Basic {

	use \Ts\Traits\Numberrange;

	protected $_sTable = 'ts_sponsors';
	protected $_sTableAlias = 'ts_s';

	protected $_sPlaceholderClass = \TsSponsoring\Entity\Placeholder\Sponsor::class;

	protected $sNumberrangeClass = \TsSponsoring\Service\SponsorNumberrange::class;

	protected $_aJoinTables = [
		'schools' => [
			'table' => 'ts_sponsors_to_schools',
			'foreign_key_field' => 'school_id',
			'primary_key_field' => 'sponsor_id',
			'class' => 'Ext_Thebing_School'
		],
		'contacts' => [
			'table' => 'ts_sponsors_to_contacts',
			'foreign_key_field' => 'contact_id',
			'primary_key_field' => 'sponsor_id',
			'class' => Sponsor\Contact::class
		]
	];

	protected $_aJoinedObjects = [
		'address'=> [
			'class' => 'Ext_TC_Address',
			'key' => 'address_id',
			'type' => 'parent'
		],
		'payment_conditions'=> [
			'class' => PaymenttermsValidity::class,
			'key' => 'sponsor_id',
			'type' => 'child',
			'check_active' => true,
			'on_delete' => 'cascade'
		]
	];

	/**
	 * @return \Ext_TC_Address
	 */
	public function getAddress() {
		return $this->getJoinedObject('address');
	}

	/**
	 * @return Sponsor\Contact[]
	 */
	public function getContacts() {
		return $this->getJoinTableObjects('contacts');
	}

	public function manipulateSqlParts(&$aSqlParts, $sView = null) {

		$aSqlParts['select'] .= " ,
			`tc_a`.`address`,
			`tc_a`.`address_addon`,
			`tc_a`.`zip`,
			`tc_a`.`city`,
			`tc_a`.`state`,
			`tc_a`.`country_iso`
		";

		$aSqlParts['from'] .= " LEFT JOIN 
			`tc_addresses` `tc_a` ON 
				 `tc_a`.`id` = `ts_s`.`address_id` LEFT JOIN
			 /* Filter */
			`ts_sponsors_to_contacts` `ts_stc` ON
				`ts_stc`.`sponsor_id` = `ts_s`.`id` LEFT JOIN
			`tc_contacts` `tc_c` ON
				`tc_c`.`id` = `ts_stc`.`contact_id` AND
				`tc_c`.`active` = 1 LEFT JOIN
			`tc_contacts_to_emailaddresses` `tc_ctea` ON
				`tc_ctea`.`contact_id` = `tc_c`.`id` LEFT JOIN
			`tc_emailaddresses` `tc_ea` ON
				`tc_ea`.`id` = `tc_ctea`.`emailaddress_id`
		";

		parent::manipulateSqlParts($aSqlParts, $sView);

	}

	/**
	 * @inheritdoc
	 */
	public function save($bLog = true) {

		$mNumber = $this->getNumber();
		if(empty($mNumber)) {
			$this->generateNumber();
		}

		return parent::save($bLog);

	}

	public static function getSponsoringTypes(): array {
		return [
			'course' => \L10N::t('Nur Kurs'),
			'all' => \L10N::t('Alle Leistungen'),
		];
	}
}
