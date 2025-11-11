<?php

namespace TsAccounting\Entity;

use TsAccounting\Entity\Company\AccountAllocation;
use TsAccounting\Entity\Company\Combination;

/**
 *
 * @property int $id
 * @property string $changed (TIMESTAMP)
 * @property string $created (TIMESTAMP)
 * @property int $active
 * @property int $creator_id
 * @property int $editor_id
 * @property int $position
 * @property string $name
 * @property string $currency_iso
 * @property string $accounting_type
 * @property string $interface
 * @property string $posting_key_positive
 * @property string $posting_key_negative
 * @property string $qb_number_format
 * @property int $invoice_item_description_changeable
 * @property int $create_claim_debt
 * @property string $export_file_extension (ENUM)
 * @property string $export_delimiter
 * @property string $export_charset
 * @property string $export_linebreak
 * @property int $customer_account_type "Kundeneinstellungen - Kontotyp" ( 1 = individuell, 2 = sammelkonto)
 * @property int $customer_account_use_number "Kundeneinstellungen - Kundennummer als Kontonummer verwenden?"
 * @property int $customer_account_numberrange_id "Kundeneinstellungen - Nummernkreis"
 * @property int $agency_account_type "Agentureinstellungen - Kontotyp" ( 1 = individuell, 2 = sammelkonto)
 * @property int $agency_account_booking_type "Agentureinstellungen - Verbuchungsart" ( 1 = aktiv, 2 = aktiv & passiv)
 * @property int $agency_active_account_use_number "Agentureinstellungen(aktiv) - Agenturnummer als Kontonummer verwenden?"
 * @property int $agency_active_account_numberrange_id "Agentureinstellungen(aktiv) - Nummernkreis"
 * @property int $agency_activepassive_account_use_number "Agentureinstellungen(aktiv&passiv) - Agenturnummer als Kontonummer verwenden?"
 * @property int $agency_activepassive_account_numberrange_id "Agentureinstellungen(aktiv&passiv) - Nummernkreis"
 * @property int $service_account_book_credit_as_reduction "Leistungseinstellungen - Gutschrift als Reduktion bei der Verbuchung?"
 * @property int $service_income_account_course "Leistungseinstellungen(Erträge) - Verbuchung von Kursen" (1 = pro Kurs, 2 = pro Kategorie, 3 = einmalig)
 * @property int $service_income_account_additional_course "Leistungseinstellungen(Erträge) - Verbuchung von z.Kursgebühren" (1 = pro Gebühr, 2 = pro Kurs, 3 = einmalig)
 * @property int $service_income_account_accommodation "Leistungseinstellungen(Erträge) - Verbuchung von Unterkünften" (2 = pro Kategorie, 3 = einmalig)
 * @property int $service_income_account_additional_accommodation "Leistungseinstellungen(Erträge) - Verbuchung von z.Unterkunftsgebühren" (1 = pro Gebühr, 2 = pro Unterkunft, 3 = einmalig)
 * @property int $service_income_account_additional_general "Leistungseinstellungen(Erträge) - Verbuchung von generellen Gebühren" (1 = pro Gebühr, 3 = einmalig)
 * @property int $service_income_account_insurance "Leistungseinstellungen(Erträge) - Verbuchung von Versicherungen" (1 = pro Versicherung, 3 = einmalig)
 * @property int $service_income_account_cancellation "Leistungseinstellungen(Erträge) - Verbuchung von Stornierungen" (1 = pro Gebühr, 2 = Ursprungskonto, 3 = einmalig)
 * @property int $service_income_account_currency "Leistungseinstellungen(Erträge) - Verbuchung je Währung" (1 = pro Währung, 3 = einmalig)
 * @property int $service_expense_cn_account_course "Leistungseinstellungen(Aufwände CN) - Provisionen von Kursen" (1 = pro Kurs, 2 = pro Kategorie, 3 = einmalig)
 * @property int $service_expense_cn_account_additional_course "Leistungseinstellungen(Aufwände CN) - Provisionen von z.Kursgebühren" (1 = pro Gebühr, 2 = pro Kurs, 3 = einmalig)
 * @property int $service_expense_cn_account_accommodation "Leistungseinstellungen(Aufwände CN) - Provisionen von Unterkünften" (2 = pro Kategorie, 3 = einmalig)
 * @property int $service_expense_cn_account_additional_accommodation "Leistungseinstellungen(Aufwände CN) - Provisionen von z.Unterkunftsgebühren" (1 = pro Gebühr, 2 = pro Unterkunft, 3 = einmalig)
 * @property int $service_expense_cn_account_additional_general "Leistungseinstellungen(Aufwände CN) - Provisionen von generellen Gebühren" (1 = pro Gebühr, 3 = einmalig)
 * @property int $service_expense_cn_account_insurance "Leistungseinstellungen(Aufwände CN) - Provisionen von Versicherungen" (1 = pro Versicherung, 3 = einmalig)
 * @property int $service_expense_account_cancellation "Leistungseinstellungen(Aufwände) - Verbuchung von Stornierungen" (1 = pro Gebühr, 2 = Ursprungskonto, 3 = einmalig)
 * @property int $service_expense_account_currency "Leistungseinstellungen(Aufwände) - Verbuchung je Währung" (1 = pro Währung, 3 = einmalig)
 * @property string $cost_center "Kostenstelle" (Dieser Wert kommt aus der WDBasic_Attributes-Tabelle)
 * @property string $accounting_records 'deferred_income', 'single', 'line_item'
 * @property int $automatic_release
 * @property int $automatic_document_release_after
 * @property int $automatic_payment_release_after
 * @property string $export_filename
 * @property array $account_allocations
 * @property array $columns_export
 * @property array $columns_export_full
 */
class Company extends \Ext_Thebing_Basic
{
	const int NO_CLAIM_DEBT_POSITIONS = 0;
	const int SEPARATE_CLAIM_DEBT_POSITIONS = 1;
	const int SINGLE_CLAIM_DEBT_POSITION = 2;

	/**
	 * @var string
	 */
	protected $_sTable = 'ts_accounting_companies';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'ts_com';

	/**
	 * @var string
	 */
	protected $_sEditorIdColumn = 'editor_id';

	/**
	 *
	 * @var \TsAccounting\Entity\Company\AccountAllocation|null
	 */
	protected $_oAllocation = null;

	/**
	 * @var array
	 */
	protected $_aFormat = array(
		'name' => array(
			'required' => true,
		),
	);

	protected static $aSearchCache = [];

	/**
	 * @var array
	 */
	protected $_aAttributes = array(
		// Kundeneinstellungen
		'customer_account_type' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'customer_account_use_number' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'customer_account_numberrange_id' => array(
			'class' => 'WDBasic_Attribute_Type_Int',
		),
		// Agentureinstellungen
		'agency_account_type' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'agency_account_booking_type' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		// Agentur Aktiv-Einstellungen
		'agency_active_account_use_number' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'agency_active_account_numberrange_id' => array(
			'class' => 'WDBasic_Attribute_Type_Int',
		),
		// Agentur Aktiv/Passiv-Einstellungen
		'agency_activepassive_account_use_number' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'agency_activepassive_account_numberrange_id' => array(
			'class' => 'WDBasic_Attribute_Type_Int',
		),
		// Leistungseinstellungen
		'service_account_book_reverse_sign' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		// Leistungseinstellungen
		'service_account_book_credit_as_reduction' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		// Erträge
		'service_income_account_course' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'service_income_account_additional_course' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'service_income_account_accommodation' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'service_income_account_additional_accommodation' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'service_income_account_additional_general' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'service_income_account_insurance' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'service_income_account_activity' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'service_income_account_cancellation' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'service_income_account_currency' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		// Aufwände
		'service_expense_cn_account_course' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'service_expense_cn_account_additional_course' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'service_expense_cn_account_accommodation' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'service_expense_cn_account_additional_accommodation' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'service_expense_cn_account_additional_general' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'service_expense_cn_account_insurance' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'service_expense_cn_account_activity' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'service_expense_net_account_course' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'service_expense_net_account_additional_course' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'service_expense_net_account_accommodation' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'service_expense_net_account_additional_accommodation' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'service_expense_net_account_additional_general' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'service_expense_net_account_insurance' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'service_expense_net_account_activity' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'service_expense_account_cancellation' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'service_expense_net_account_cancellation' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'service_expense_account_currency' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'service_expense_net_account_currency' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'cost_center' => [
			'class' => 'WDBasic_Attribute_Type_Varchar'
		],
		//
		'service_clearing_account_currency' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		),
		'export_filename' => [
			'class' => 'WDBasic_Attribute_Type_Varchar'
		],
		'additional_booking_record_for_discount' => [
			'type' => 'text',
		],
		'fixed_expense_claim_debt_account_number' => [
			'type' => 'text',
		],
		'payment_entries_split' => [
			'type' => 'int'
		],
		'courses_by_category' => [
			'type' => 'int'
		]
	);

	/**
	 * @var array
	 */
	protected $_aJoinedObjects = array(
		'combinations' => array(
			'class' => Combination::class,
			'key' => 'company_id',
			'check_active' => true,
			'type' => 'child',
			'on_delete' => 'cascade',
			'bidirectional' => true,
		),
	);

	protected $_sPlaceholderClass = \TsAccounting\Service\Placeholder\Company::class;

	/**
	 * @var array
	 */
	protected $_aJoinTables = array(
		'account_allocations' => array(
			'table' => 'ts_accounting_companies_account_allocations',
			'primary_key_field' => 'company_id',
			'autoload' => false,
		),
		'columns_export' => [
			'table' => 'ts_accounting_companies_columns_export',
			'primary_key_field' => 'company_id',
			'foreign_key_field' => 'column',
			'sort_column' => 'position',
			'autoload' => false,
			'readonly' => true
		],
		'columns_export_full' => [
			'table' => 'ts_accounting_companies_columns_export',
			'primary_key_field' => 'company_id',
			'sort_column' => 'position',
			'autoload' => false
		]
	);

	/**
	 * Mapping aller Abhängigkeiten
	 *
	 * @var array
	 */
	protected $_aAttributesDependencies = array(
		'customer_account_type' => array(
			array(
				'db_column' => 'accounting_type',
				'on_values' => 'double',
			)
		),
		'customer_account_use_number' => array(
//			array(
//				'db_column' => 'accounting_type',
//				'on_values'	=> 'double',
//			),
//			array(
//				'db_column' => 'customer_account_type',
//				'on_values'	=> '1',
//			),
		),
		'customer_account_numberrange_id' => array(
			array(
				'db_column' => 'accounting_type',
				'on_values' => 'double',
			),
			array(
				'db_column' => 'customer_account_type',
				'on_values' => '1',
			),
			array(
				'db_column' => 'customer_account_use_number',
				'on_values' => '0',
			),
		),
		'agency_account_type' => array(
			array(
				'db_column' => 'accounting_type',
				'on_values' => 'double',
			)
		),
		'agency_account_booking_type' => array(
			array(
				'db_column' => 'accounting_type',
				'on_values' => 'double',
			)
		),
		'agency_active_account_use_number' => array(
//			array(
//				'db_column' => 'accounting_type',
//				'on_values'	=> 'double',
//			),
//			array(
//				'db_column' => 'agency_account_type',
//				'on_values'	=> '1',
//			),
		),
		'agency_active_account_numberrange_id' => array(
			array(
				'db_column' => 'accounting_type',
				'on_values' => 'double',
			),
			array(
				'db_column' => 'agency_account_type',
				'on_values' => '1',
			),
			array(
				'db_column' => 'agency_active_account_use_number',
				'on_values' => '0',
			),
		),
		'agency_activepassive_account_use_number' => array(
			array(
				'db_column' => 'accounting_type',
				'on_values' => 'double',
			),
			array(
				'db_column' => 'agency_account_type',
				'on_values' => '1',
			),
			array(
				'db_column' => 'agency_account_booking_type',
				'on_values' => '2',
			),
		),
		'agency_activepassive_account_numberrange_id' => array(
			array(
				'db_column' => 'accounting_type',
				'on_values' => 'double',
			),
			array(
				'db_column' => 'agency_account_type',
				'on_values' => '1',
			),
			array(
				'db_column' => 'agency_account_booking_type',
				'on_values' => '2',
			),
			array(
				'db_column' => 'agency_activepassive_account_use_number',
				'on_values' => '0',
			),
		),
		'service_expense_cn_account_course' => array(/*array(
				'db_column' => 'accounting_type',
				'on_values'	=> 'double',
			)*/
		),
		'service_expense_cn_account_additional_course' => array(/*array(
				'db_column' => 'accounting_type',
				'on_values'	=> 'double',
			)*/
		),
		'service_expense_cn_account_accommodation' => array(/*array(
				'db_column' => 'accounting_type',
				'on_values'	=> 'double',
			)*/
		),
		'service_expense_cn_account_additional_accommodation' => array(/*array(
				'db_column' => 'accounting_type',
				'on_values'	=> 'double',
			)*/
		),
		'service_expense_cn_account_additional_general' => array(/*array(
				'db_column' => 'accounting_type',
				'on_values'	=> 'double',
			)*/
		),
		'service_expense_cn_account_insurance' => array(/*array(
				'db_column' => 'accounting_type',
				'on_values'	=> 'double',
			)*/
		),
	);

	/**
	 * @var array
	 */
	protected $_aFlexibleFieldsConfig = [
		'accounting_companies_options' => []
	];

	/**
	 * @inheritdoc
	 */
	public function __construct($iDataID = 0, $sTable = null)
	{

		$this->setAdditionalAttributes();

		parent::__construct($iDataID, $sTable);

	}

	/**
	 * @see \TsAccounting\Service\Interfaces\AbstractInterface::get()
	 */
	public function __get($name)
	{

		$default = parent::__get($name);

		// Vordefinierte Schnittstellen, verschiedene Einstellungen werden überschrieben
		if ($this->_aData['interface'] != 'universal') {
			$service = \TsAccounting\Factory\AccountingInterfaceFactory::get($this);
			if ($service) {
				$value = $service->get($name, $default);
				if ($value !== null) {
					return $value;
				}
			}
		}

		return $default;
	}

	public function isDoubleAccounting()
	{
		return ($this->accounting_type === 'double');
	}

	public function hasAutomaticRelease(): bool
	{
		return ($this->automatic_release == 1);
	}

	public function save($bLog = true)
	{

		// Einstellungen wurden auskommentiert
		$this->customer_account_type = 1;
		$this->customer_account_use_number = 1;
		$this->agency_account_type = 1;
		$this->agency_active_account_use_number = 1;
		$this->agency_activepassive_account_use_number = 1;

		return parent::save($bLog);

	}


	/**
	 * Alle Schulen Kombinationen
	 *
	 * @return \Ext_Thebing_School[]
	 */
	public function getSchools()
	{

		if ($this->exist()) {
			$aSchools = $this->getCombinationObjectArray('getSchools');
		} else {
			// Wenn keine Firmen vorhanden, dann alle Schulen zurückliefern, da die Kombinationen
			// immer mit nem Firmenobjekt laufen (wenn leer mit nem leeren Firmenobjekt)
			$oClient = \Ext_Thebing_System::getClient();
			$aSchools = $oClient->getSchoolListByAccess(false, true, true);
		}

		return $aSchools;
	}

	/**
	 * Alle Inboxen Kombinationen
	 *
	 * @return \Ext_Thebing_Client_Inbox[]
	 */
	public function getInboxes()
	{

		$aInboxes = array();

		if (\Ext_Thebing_System::hasInbox()) {
			if ($this->exist()) {
				$aInboxes = $this->getCombinationObjectArray('getInboxes');
			} else {
				$aInboxList = \Ext_Thebing_System::getInboxList(false, true);
				foreach ($aInboxList as $aInbox) {
					$aInboxes[] = \Ext_Thebing_Client_Inbox::getInstance($aInbox['id']);
				}
			}
		}

		return $aInboxes;
	}

	/**
	 * siehe parent
	 *
	 * @param array $aSqlParts
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView = null)
	{

		$aSqlParts['select'] .= '
			, GROUP_CONCAT(
				DISTINCT `cdb2`.`ext_1` SEPARATOR \', \'
			) `schools`
			, GROUP_CONCAT(
				DISTINCT `k_inb`.`name` SEPARATOR \', \'
			) `inboxes`
			, `' . $this->_sTableAlias . '`.`name` `company_name`
		';

		$aSqlParts['from'] .= ' LEFT JOIN
			`ts_accounting_companies_combinations` `ts_com_c` ON
				`ts_com_c`.`company_id` = `' . $this->_sTableAlias . '`.`id` AND
				`ts_com_c`.`active` = 1 LEFT JOIN
			`ts_accounting_companies_combinations_to_schools` `ts_com_c_to_s` ON
				`ts_com_c_to_s`.`company_combination_id` = `ts_com_c`.`id` LEFT JOIN
			`customer_db_2` `cdb2` ON
				`cdb2`.`id` = `ts_com_c_to_s`.`school_id` AND
				`cdb2`.`active` = 1 LEFT JOIN
			`ts_accounting_companies_combinations_to_inboxes` `ts_com_c_to_i` ON
				`ts_com_c_to_i`.`company_combination_id` = `ts_com_c`.`id` LEFT JOIN
			`kolumbus_inboxlist` `k_inb` ON
				`k_inb`.`id` = `ts_com_c_to_i`.`inbox_id` AND
				`k_inb`.`active` = 1
		';

		$aSqlParts['groupby'] .= '
				`' . $this->_sTableAlias . '`.`id`
		';

	}

	/**
	 *
	 * @return \TsAccounting\Entity\Company\Combination[]
	 */
	public function getCombinations()
	{
		return (array)$this->getJoinedObjectChilds('combinations');
	}

	/**
	 * Kombination Arrays liefern
	 *
	 * @param string $sObjectCall
	 * @return \Ext_TC_Basic[]
	 */
	public function getCombinationObjectArray($sObjectCall)
	{

		$aObjects = array();

		$aCombinations = $this->getCombinations();
		foreach ($aCombinations as $oCombination) {
			$aCombinationObjects = $oCombination->$sObjectCall();
			foreach ($aCombinationObjects as $oCombinationObject) {
				$aObjects[$oCombinationObject->id] = $oCombinationObject;
			}
		}

		return $aObjects;
	}

	/**
	 *
	 * @return \TsAccounting\Entity\Company\Combination
	 */
	public function getEmptyAllocationObject()
	{
		return new Combination();
	}

	/**
	 * Falls das Objekt die ID "0" hat, kann man nur so an die Childs richtig dran kommen
	 *
	 * @return array
	 */
	public function getCombinationsFromObjectContext()
	{

		$aCombinations = array();

		if (isset($this->_aJoinedObjectChilds['combinations'])) {
			$aCombinations = $this->_aJoinedObjectChilds['combinations'];
		}

		return $aCombinations;
	}

	/**
	 * Kombination anhand des Cache-Keys(Container-ID) bekommen
	 *
	 * @param string $sCacheKey
	 * @return \TsAccounting\Entity\Company\Combination
	 */
	public function getCombination($sCacheKey)
	{

		$oCombination = null;

		$aCombinations = $this->getCombinationsFromObjectContext();
		if (isset($aCombinations[$sCacheKey])) {
			$oCombination = $aCombinations[$sCacheKey];
		}

		return $oCombination;
	}

	/**
	 * @param bool $bThrowExceptions
	 * @return array|bool
	 */
	public function validate($bThrowExceptions = false)
	{

		$this->removeDependencyWrongAttributes();

		$mReturn = parent::validate($bThrowExceptions);

		if ($mReturn === true) {
			$mReturn = array();
		}

		if ($this->agency_account_booking_type == 2 && $this->agency_account_type == 1) {
			// Wenn aktiv & passiv dürfen die Nummernkreise nicht übereinstimmen

			if (
				!empty($this->agency_active_account_numberrange_id) &&
				$this->agency_active_account_numberrange_id == $this->agency_activepassive_account_numberrange_id
			) {
				if (!isset($mReturn['agency_active_account_numberrange_id'])) {
					$mReturn['agency_active_account_numberrange_id'] = array();
				}
				$mReturn['agency_active_account_numberrange_id'][] = 'ACTIVE_PASSIVE_NUMBERRANGE';
			}
		}

		if (empty($mReturn)) {
			$mReturn = true;
		}

		return $mReturn;
	}

	/**
	 * Anhand der definierten Abhängigkeiten zwischen den Feldern Werte leeren, falls
	 * "on_values" in "$this->_aAttributesDependencies" Bedingung nicht zutrifft
	 */
	public function removeDependencyWrongAttributes()
	{

		$aDependencyAttributes = $this->_aAttributesDependencies;

		foreach ($aDependencyAttributes as $sAttribute => $aDependencies) {

			foreach ($aDependencies as $aDependencyData) {

				$sKey = $aDependencyData['db_column'];
				$mValueReal = $this->$sKey;
				$mValueExpected = $aDependencyData['on_values'];

				if ($mValueReal != $mValueExpected) {
					$this->$sAttribute = null;
				}

			}

		}

	}

	/**
	 * Zuweisungsobjekt löschen
	 */
	public function resetAllocationObject()
	{
		$this->_oAllocation = null;
	}


	/**
	 *
	 * @return \TsAccounting\Entity\Company\AccountAllocation
	 */
	public function getAllocationsObject()
	{

		if ($this->_oAllocation === null) {
			$this->_oAllocation = new AccountAllocation($this);
		}

		return $this->_oAllocation;
	}

	/**
	 * @param array $aData
	 */
	public function setAllocationData(array $aData)
	{
		$this->getAllocationsObject()->setAllocationData($aData);
	}

	/**
	 * @return mixed|array
	 */
	public function saveAllocations(bool $ignoreErrors)
	{
		$aSetAllocations = array();

		$aErrors = [];
		if (!$ignoreErrors) {
			$aErrors = $this->_validateAllocations();
		}

		if (empty($aErrors)) {
			$aAllocations = $this->getAllocationsObject()->getAllocations();

			foreach ($aAllocations as $aAllocation) {
				if (!empty($aAllocation['account_number'])) {

					if (!isset($aAllocation['type_id'])) {
						$aAllocation['type_id'] = 0;
					}

					if (!isset($aAllocation['automatic_account'])) {
						$aAllocation['automatic_account'] = 0;
					}

					if (!isset($aAllocation['parent_type'])) {
						$aAllocation['parent_type'] = '';
					}

					if (!isset($aAllocation['parent_type_id'])) {
						$aAllocation['parent_type_id'] = 0;
					}

					if (empty($aAllocation['account_number_discount'])) {
						$aAllocation['account_number_discount'] = null;
					}

					$aSetAllocations[] = array(
						'account_number' => $aAllocation['account_number'],
						'account_number_discount' => $aAllocation['account_number_discount'],
						'automatic_account' => $aAllocation['automatic_account'],
						'tax_id' => $aAllocation['vat_rate'],
						'type' => $aAllocation['type'],
						'type_id' => $aAllocation['type_id'],
						'parent_type' => $aAllocation['parent_type'],
						'parent_type_id' => $aAllocation['parent_type_id'],
						'currency_iso' => $aAllocation['currency_iso'],
						'account_type' => $aAllocation['account_type'],
					);
				}
			}

			$this->account_allocations = $aSetAllocations;

			$mReturn = $this->save();

			return $mReturn;
		} else {
			return $aErrors;
		}
	}

	/**
	 * Zuweisungen validieren
	 *
	 * @return array
	 */
	protected function _validateAllocations()
	{
		$aErrors = array();

		$oAllocation = $this->getAllocationsObject();

		$aAllocations = $oAllocation->getAllocations();

		foreach ($aAllocations as $sKeyAllocation => $aAllocation) {
			$sAccountNumber = $aAllocation['account_number'];

			if (!empty($sAccountNumber)) {
				$aCurrencyList = $oAllocation->getCurrencyList($aAllocation['account_type']);

				$aVatRates = $oAllocation->getVatRates();

				$sCurrencySelf = $aAllocation['currency_iso'];

				unset($aCurrencyList[$sCurrencySelf]);

				foreach ($aVatRates as $iRate => $sRate) {
					$aAllocationTemp = $aAllocation;

					$aAllocationTemp['vat_rate'] = $iRate;

					foreach ($aCurrencyList as $sKey => $mValue) {
						$aAllocationTemp['currency_iso'] = $sKey;

						$sKeyOtherAllocation = $oAllocation->generateKey($aAllocationTemp);

						if ($oAllocation->hasKey($sKeyOtherAllocation)) {
							$aOtherAllocation = $oAllocation->getAllocation($sKeyOtherAllocation);

							$sAccountNumberOtherAllocation = $aOtherAllocation['account_number'];

							if ($sAccountNumber == $sAccountNumberOtherAllocation) {
								unset($aAllocationTemp['currency_iso'], $aAllocationTemp['vat_rate']);

								$sKeyGrouped = $oAllocation->generateKey($aAllocationTemp);

								$aErrors['SAME_ACCOUNT_FOR_DIFFERENT_CURRENCY'][$sKeyGrouped][$sKeyAllocation] = $sCurrencySelf;
							}
						}
					}

					$aAllocationTemp['currency_iso'] = $aAllocation['currency_iso'];

					$sKeyOtherAllocation = $oAllocation->generateKey($aAllocationTemp);

					if ($oAllocation->hasKey($sKeyOtherAllocation)) {
						$aOtherAllocation = $oAllocation->getAllocation($sKeyOtherAllocation);

						if (
							$aAllocation['vat_rate'] != $aOtherAllocation['vat_rate'] &&
							$aAllocation['automatic_account'] == 1 &&
							$aAllocation['account_number'] == $aOtherAllocation['account_number']
						) {
							unset($aAllocationTemp['currency_iso'], $aAllocationTemp['vat_rate']);

							$sKeyGrouped = $oAllocation->generateKey($aAllocationTemp);

							$aErrors['SAME_ACCOUNT_FOR_AUTOMATIC_ACCOUNT'][$sKeyGrouped][$sKeyOtherAllocation] = $sRate;
						}

					}
				}
			}
		}

		return $aErrors;
	}

	/**
	 * @param $oSchool
	 * @param null $oInbox
	 * @return null|Company
	 */
	static public function searchByCombination($oSchool, $oInbox = null)
	{

		$sCacheKey = implode('_', [__METHOD__, $oSchool->getId(), ($oInbox) ? $oInbox->getId() : 0]);

		if (!isset(self::$aSearchCache[$sCacheKey])) {

			$sSql = " 
				SELECT 
					`ts_cc`.`id` 
				FROM 
					`ts_accounting_companies_combinations` `ts_cc` INNER JOIN
					`ts_accounting_companies_combinations_to_schools` `ts_cc_to_s` ON
						`ts_cc_to_s`.`company_combination_id` = `ts_cc`.`id` LEFT JOIN
					`ts_accounting_companies_combinations_to_inboxes` `ts_cc_to_i` ON
						`ts_cc_to_i`.`company_combination_id` = `ts_cc`.`id`
				WHERE
					`ts_cc_to_s`.`school_id` = :school_id AND
					`ts_cc`.`active` = 1
            ";

			$aSql = array('school_id' => $oSchool->getId());

			if ($oInbox) {
				$sSql .= ' AND (
					`ts_cc_to_i`.`inbox_id` = :inbox_id OR 
					`ts_cc_to_i`.`inbox_id` IS NULL
				) ';
				$aSql['inbox_id'] = $oInbox->getId();
			}

			$sSql .= '
				GROUP BY 
					`ts_cc`.`id`
				LIMIT 1';

			$iCompanyId = (int)\DB::getQueryOne($sSql, $aSql);

			self::$aSearchCache[$sCacheKey] = $iCompanyId;

		} else {
			$iCompanyId = self::$aSearchCache[$sCacheKey];
		}

		$oCompany = null;
		if ($iCompanyId > 0) {
			$oCompany = self::getInstance($iCompanyId);
		}

		return $oCompany;
	}

	/**
	 * Gibt zurück ob die Firma eine einfach Buchführung hat
	 *
	 * @return bool
	 */
	public function hasSingleAccounting()
	{
		$bRetVal = $this->accounting_type === 'single';
		return $bRetVal;
	}

	/**
	 * Gibt zurück ob die Firma eine doppelte Buchführung hat
	 *
	 * @return bool
	 */
	public function hasDoubleAccounting()
	{
		$bRetVal = $this->accounting_type === 'double';
		return $bRetVal;
	}

	/**
	 * Zusätzliche WDBasic-Attribute für Payment-Anbieter setzen
	 */
	private function setAdditionalAttributes()
	{

		foreach (\TsAccounting\Handler\ExternalApp\AbstractCompanyApp::APPS as $sClass) {
			/** @var \TsFrontend\Handler\Payment\Legacy\AbstractPayment $sClass */
			foreach ($sClass::getCompanyAttributes() as $sSetting => $aSetting) {
				$sAttributeClass = \WDBasic_Attribute_Type_Varchar::class;
				if ($aSetting['type'] === 'checkbox') {
					$sAttributeClass = \WDBasic_Attribute_Type_TinyInt::class;
				}
				$this->_aAttributes[$sSetting] = [
					'class' => $sAttributeClass
				];
			}
		}

	}

	public function getFinancialYear(\DateTime $date)
	{

		$iFinancialYear = $date->format('Y');

		if ($date->format('n') < $this->financial_year_start) {
			$iFinancialYear--;
		}

		return $iFinancialYear;
	}

}
