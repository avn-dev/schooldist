<?php

namespace TsAccounting\Entity\Company;

use Ext_Thebing_Inquiry_Document_Version_Item;
use stdClass;

class AccountAllocation extends \Ext_Thebing_Basic
{

	use \Ts\Traits\ServiceSettings;

	// Tabelle nur definieren damit __set, __get & _aData Funktioniert
	protected $_sTable = 'ts_accounting_companies_account_allocations';

	/**
	 * Alle Zuweisungen
	 *
	 * @var array
	 */
	protected $_aAllocations = array();

	/**
	 * Trennzeichen zwischen den Schlüsseln für die Zuweisungen
	 *
	 * @var type
	 */
	public $sKeyDelimiter = '#';

	/**
	 *
	 * @var \TsAccounting\Entity\Company
	 */
	protected $_oCompany = null;

	/**
	 * Alle Steuern
	 *
	 * @var array
	 */
	protected $_aVatRates = array();

	/**
	 * Alle Währungen
	 *
	 * @var array
	 */
	protected $_aCurrencyIds = array();

	/**
	 *
	 * @var string
	 */
	protected $_sTranslatePath = '';

	/**
	 *
	 * @param \TsAccounting\Entity\Company $oCompany
	 */
	public function __construct(\TsAccounting\Entity\Company $oCompany)
	{

		$this->_oCompany = $oCompany;

		$this->_aVatRates = \Ext_TS_Vat::getCategories(true);

		$this->_aVatRates = \Ext_Thebing_Util::addEmptyItem($this->_aVatRates, '');

		$aSchools = $this->_oCompany->getCombinationObjectArray('getSchools');

		$aCurrencyIds = array();

		foreach ($aSchools as $oSchool) {
			$aCurrencyList = (array)$oSchool->getSchoolCurrencyList(true);

			foreach ($aCurrencyList as $iCurrencyId => $sCurrency) {
				$aCurrencyIds[$sCurrency] = $sCurrency;
			}

		}

		$this->_aCurrencyIds = $aCurrencyIds;

		$this->_createAllocations();

		$this->_setCompanyData();

	}

	/**
	 *
	 * @param bool $bLog
	 * @return false
	 */
	public function save($bLog = true)
	{
		// Diese Klasse ist nur für Darstellung, Generierung da, nicht auf die Iddee kommen mit
		// dieser Klasse zu speichern :)
		return false;
	}

	/**
	 * Konten-Zuweisung generieren
	 *
	 * @param array $aAllocationData
	 */
	public function createAllocation($aAllocationData)
	{
		$aCurrencyIds = $this->getCurrencyList($aAllocationData['account_type']);

		foreach ($aCurrencyIds as $iCurrencyId => $sCurrency) {
			if (in_array($aAllocationData['type'], ['vat', 'payment_method'])) {
				$aVatRates = array(0 => '');
			} else {
				$aVatRates = $this->_aVatRates;
			}

			foreach ($aVatRates as $iRate => $sRate) {
				$aAllocationData['currency_iso'] = $iCurrencyId;

				$aAllocationData['vat_rate'] = $iRate;

				$sKey = $this->generateKey($aAllocationData);

				$this->_aAllocations[$sKey] = $aAllocationData;
			}
		}
	}


	/**
	 * Alle Konten-Zuweisungen
	 *
	 * @return array
	 */
	public function getAllocations()
	{
		return $this->_aAllocations;
	}

	public function getTaxAccount(\Ext_Thebing_Inquiry_Document_Version_Item $oItem)
	{


		$iCurrencyOption1 = $this->_oCompany->service_expense_account_currency;
		$iCurrencyOption2 = $this->_oCompany->service_income_account_currency;

		//continuance#vat#1#0#no_parent#EUR#0
		$oDocument = $oItem->getDocument();
		$aArray['account_type'] = 'continuance';
		$aArray['type'] = 'vat';
		$aArray['type_id'] = $oItem->tax_category;
		$aArray['parent_type'] = '';
		$aArray['parent_type_id'] = 0;
		$aArray['currency_iso'] = $oDocument->getCurrency()->getIso();
		$aArray['vat_rate'] = 0;

		if ($iCurrencyOption1 != 1 && $iCurrencyOption2 != 1) {
			$aArray['currency_iso'] = '';
		}

		$sKey = $this->generateKey($aArray);

		$aAccount = (array)$this->getAllocation($sKey);

		$aAccount['automatic_account'] = match ($this->_oCompany->automatic_account_setting) {
			'none' => 0,
			'all' => 1,
			default => $aAccount['automatic_account']
		};

		$oAccount = null;
		if (!empty($aAccount) && !empty($aAccount['account_number'])) {
			$oAccount = (object)$aAccount;
		}

		return $oAccount;
	}

	public function getPaymentMethodAccount(\Ext_Thebing_Admin_Payment $oPaymentMethod)
	{

		//continuance#vat#1#0#no_parent#EUR#0
		$aArray['account_type'] = 'clearing';
		$aArray['type'] = 'payment_method';
		$aArray['type_id'] = $oPaymentMethod->getId();
		$aArray['parent_type'] = '';
		$aArray['parent_type_id'] = 0;
		$aArray['currency_iso'] = "";
		$aArray['vat_rate'] = 0;

		$sKey = $this->generateKey($aArray);

		$aAccount = (array)$this->getAllocation($sKey);

		$aAccount['automatic_account'] = match ($this->_oCompany->automatic_account_setting) {
			'none' => 0,
			'all' => 1,
			default => $aAccount['automatic_account']
		};

		$oAccount = null;
		if (!empty($aAccount) && !empty($aAccount['account_number'])) {
			$oAccount = (object)$aAccount;
		}

		return $oAccount;
	}

	/**
	 * Alle Zuweisungen anhand der Firmeneinstellungen generieren
	 */
	protected function _createAllocations()
	{

		$aTypes = array('income', 'expense', 'expense_net');

		if ($this->_oCompany->accounting_type === 'double') {
			$aTypes[] = 'clearing';
		}

		foreach ($aTypes as $sType) {

			if (
				$sType == 'income' || (
					$sType == 'expense' && (
						(
							// Einfache Buchführung + Gutschrift NICHT als Reduktion
							$this->_oCompany->accounting_type === 'single' &&
							$this->_oCompany->service_account_book_credit_as_reduction == 0
						) || (
							// Doppelte Buchführung + Aktiv und Passiv
							$this->_oCompany->accounting_type === 'double' &&
							$this->_oCompany->agency_account_booking_type == '2'
						)
					)
				) || (
					$sType == 'expense_net' &&
					// Nettorechnungen mit Brutto- und Provisionsbetrag verbuchen
					$this->_oCompany->book_net_with_gross_and_commission
				)
			) {

				$this->_createCourseAllocations($sType);

				$this->_createAdditionalAllocations($sType, 'course');

				$this->_createAccommodationAllocations($sType);

				$this->_createAdditionalAllocations($sType, 'accommodation');

				$this->_createAdditionalAllocations($sType, 'general');

				$this->_createInsuranceAllocations($sType);

				$this->_createActivityAllocation($sType);

				$this->_createOtherAllocations($sType);
			}

			if ($sType == 'clearing') {
				$this->_createPaymentMethodsAllocations($sType);
			} else {
				$this->_createCancellationAllocations($sType);
			}
		}

		// Provision: Nur bei Quickbooks Basic, da es hier Zeilen für die Provision gibt
//		if($this->_oCompany->interface === 'quickbooks_basic') {
//			$this->createAllocation(array(
//				'account_type' => 'expense',
//				'type' => 'commission',
//			));
//		}

		// Manuelle Creditnotes
		$this->createAllocation(array(
			'account_type' => 'expense',
			'type' => 'manual_creditnote',
		));

		if ($this->_oCompany->customer_account_type == '2') {
			// Sammelkonto

			$aAllocation = array(
				'account_type' => 'continuance',
				'type' => 'customer_active',
			);

			$this->createAllocation($aAllocation);
		}

		if ($this->_oCompany->agency_account_type == '2') {
			// Sammelkonto

			$aAllocation = array(
				'account_type' => 'continuance',
				'type' => 'agency_active',
			);

			$this->createAllocation($aAllocation);

			if ($this->_oCompany->agency_account_booking_type == '2') {
				// Aktiv & Passiv eingestellt

				$aAllocation = array(
					'account_type' => 'continuance',
					'type' => 'agency_passive',
				);

				$this->createAllocation($aAllocation);
			}
		}

		if ($this->_oCompany->accounting_type == 'double') {
			// doppelte Buchführung

			if ($this->_oCompany->agency_account_booking_type == '2') {
				// Aktiv & Passiv eingestellt

				$aAllocation = array(
					'account_type' => 'continuance',
					'type' => 'accrual_account_active',
				);

				$this->createAllocation($aAllocation);
			}

			$aAllocation = array(
				'account_type' => 'continuance',
				'type' => 'accrual_account_passive',
			);

			$this->createAllocation($aAllocation);
		}

		$aVatRates = $this->getVatRates();

		unset($aVatRates[0]);

		foreach ($aVatRates as $iRate => $sRate) {
			$aAllocation = array(
				'account_type' => 'continuance',
				'type' => 'vat',
				'type_id' => $iRate,
			);

			$this->createAllocation($aAllocation);
		}
	}

	/**
	 * Kurs Erträge/Auwände generieren
	 *
	 * @param string $sType
	 */
	protected function _createCourseAllocations($sType)
	{

		$sTypeAttribute = $this->_getAttributeTypeForAccountType($sType);

		$sKey = 'service_' . $sTypeAttribute . '_account_course';

		$this->createCourseAllocations($sType, $this->_oCompany, $sKey);

	}

	/**
	 * Zusätzliche Kursgebühren Erträge / Aufwände generieren
	 *
	 * @param type $sType
	 */
	protected function _createAdditionalAllocations($sAccountType, $sCostType)
	{

		$sTypeAttribute = $this->_getAttributeTypeForAccountType($sAccountType);

		$sKey = 'service_' . $sTypeAttribute . '_account_additional_' . $sCostType;

		$this->createAdditionalAllocations($sAccountType, $sCostType, $this->_oCompany, $sKey);

	}

	/**
	 * Unterkunft Erlöse / Erträge generieren
	 *
	 * @param string $sType
	 */
	protected function _createAccommodationAllocations($sType)
	{

		$sTypeAttribute = $this->_getAttributeTypeForAccountType($sType);

		$sKey = 'service_' . $sTypeAttribute . '_account_accommodation';

		$this->createAccommodationAllocations($sType, $this->_oCompany, $sKey);

	}

	/**
	 * Versicherung Erlöse / Erträge generieren
	 *
	 * @param string $sType
	 */
	protected function _createInsuranceAllocations($sType)
	{
		$sTypeAttribute = $this->_getAttributeTypeForAccountType($sType);

		$sKey = 'service_' . $sTypeAttribute . '_account_insurance';

		$this->createInsuranceAllocations($sType, $this->_oCompany, $sKey);
	}

	/**
	 * Storno Erlöse(Gebühren) / Erträge generieren
	 *
	 * @param string $sType
	 */
	protected function _createCancellationAllocations($sType)
	{
		$sKey = 'service_' . $sType . '_account_cancellation';

		if ($this->_oCompany->$sKey == '1' && $sType == 'income') {
			// pro Gebühr

			$this->createAllocation(array(
				'type' => 'cancellation',
				'parent_type' => 'course',
				'account_type' => $sType,
			));

			$this->createAllocation(array(
				'type' => 'cancellation',
				'parent_type' => 'accommodation',
				'account_type' => $sType,
			));

			$this->createAllocation(array(
				'type' => 'cancellation',
				'parent_type' => 'all',
				'account_type' => $sType,
			));

			$aSchools = $this->_oCompany->getCombinationObjectArray('getSchools');

			foreach ($aSchools as $oSchool) {
				$aCosts = $oSchool->getCosts();

				foreach ($aCosts as $oCost) {
					if ($oCost->type == '1') {
						$sTypeParent = 'additional_accommodation';
					} elseif ($oCost->type == '2') {
						$sTypeParent = 'additional_general';
					} else {
						$sTypeParent = 'additional_course';
					}

					$this->createAllocation(array(
						'type' => 'cancellation',
						'parent_type' => $sTypeParent,
						'parent_type_id' => $oCost->id,
						'account_type' => $sType,
					));
				}
			}
		} elseif ($this->_oCompany->$sKey == '2') {
			$this->createAllocation(array(
				'type' => 'cancellation',
				'parent_type' => 'all',
				'account_type' => $sType,
			));
		} elseif ($this->_oCompany->$sKey == '3') {
			// einmalig
			$this->_createDefaultAllocation('cancellation', $sType);
		}
	}

	protected function _createPaymentMethodsAllocations($sType)
	{

		$aPaymentMethodIds = $this->getPaymentMethodsIds();

		foreach ($aPaymentMethodIds as $iPaymentMethodId) {
			$aData = array(
				'type' => 'payment_method',
				'type_id' => $iPaymentMethodId,
				'account_type' => $sType,
			);

			$this->createAllocation($aData);
		}

	}

	/**
	 * @param $sType
	 */
	protected function _createActivityAllocation($sType)
	{

		$sTypeAttribute = $this->_getAttributeTypeForAccountType($sType);

		$sKey = 'service_' . $sTypeAttribute . '_account_activity';

		$this->createActivityAllocation($sType, $this->_oCompany, $sKey);

	}

	public function getPaymentMethodsIds()
	{

		$aSchools = $this->_oCompany->getSchools();

		$aPaymentMethods = \Ext_Thebing_Admin_Payment::getPaymentMethods(true, array_column($aSchools, 'id'));

		return array_keys($aPaymentMethods);
	}

	/**
	 * Für den Service-Typen(z.B. Kurs) den Attributenamen aus der Firma bekommen(unterscheidet sich je nach Ertrag/Aufwand)
	 *
	 * @param type $sType
	 * @return type
	 */
	protected function _getAttributeTypeForAccountType($sType)
	{

		if ($sType == 'expense') {
			$sTypeAttribute = 'expense_cn';
		} elseif ($sType == 'expense_net') {
			$sTypeAttribute = 'expense_net';
		} else {
			$sTypeAttribute = $sType;
		}

		return $sTypeAttribute;
	}

	/**
	 *
	 * @return array
	 */
	public function getCurrencyList($sAccountType)
	{
		$sKey = 'service_' . $sAccountType . '_account_currency';

		if ($sAccountType == 'continuance') {
			if ($this->_oCompany->service_income_account_currency == '1' || $this->_oCompany->service_expense_account_currency == '1') {
				$aCurrencyIds = $this->_aCurrencyIds;
			} else {
				$aCurrencyIds = array('' => '');
			}
		} else {
			if ($this->_oCompany->$sKey == '1') {
				// pro Währung
				$aCurrencyIds = $this->_aCurrencyIds;
			} else {
				// einmalig
				$aCurrencyIds = array('' => '');
			}
		}

		return $aCurrencyIds;
	}

	/**
	 *
	 * @return array
	 */
	public function getVatRates()
	{
		return $this->_aVatRates;
	}

	protected function _getSchoolIdOfType($sType, $iTypeId)
	{
		$iSchoolId = 0;

		$sClass = false;

		switch ($sType) {
			case 'course':
				$sClass = 'Ext_Thebing_Tuition_Course';
				break;
			case 'course_category':
				$sClass = 'Ext_Thebing_Tuition_Course_Category';
				break;
			case 'accommodation':
				$sClass = 'Ext_Thebing_Accommodation';
				break;
			case 'accommodation_category':
				$sClass = 'Ext_Thebing_Accommodation_Category';
				break;
			case 'additional_course':
			case 'additional_accommodation':
			case 'additional_general':
				$sClass = 'Ext_Thebing_School_Additionalcost';
				break;
			default:
				break;
		}

		if ($sClass) {
			$oObject = new $sClass();

			$sSchoolField = $oObject->_checkSchoolIdField();

			$iSchoolId = (int)$this->_getDataFromObjectClass($sClass, $iTypeId, $sSchoolField);
		}

		return $iSchoolId;
	}

	/**
	 * @param $sClass
	 * @param $iTypeId
	 * @param $sField
	 * @param bool $bByObject Wird genutzt um i18n Tabellen nutzen zu können. Indem der Name über den Magic getter geholt wird.
	 * @return bool
	 */
	protected function _getDataFromObjectClass($sClass, $iTypeId, $sField, $bByObject = false)
	{

		$mData = false;

		$oObject = new $sClass();

		if ($bByObject) {
			$sName = $sClass::getInstance($iTypeId)->$sField;
			$aList[$iTypeId] = $sName;

//		if($oObject instanceof \TsActivities\Entity\Activity) {
//			$aList = $oObject->getActivitiesForSelect();
		} else {
			$aList = $oObject->getArrayList(true, $sField);
		}


		if (isset($aList[$iTypeId])) {
			$mData = $aList[$iTypeId];
		}

		return $mData;
	}

	/**
	 * Übersetzungspfad setzen
	 *
	 * @param string $sTranslationPath
	 */
	public function setTranslationPath($sTranslationPath)
	{
		$this->_sTranslatePath = $sTranslationPath;
	}

	protected function _t($sKey)
	{
		if (strlen($this->_sTranslatePath) > 0) {
			return \L10N::t($sKey, $this->_sTranslatePath);
		} else {
			throw new \Exception('you cant translate without translation path!');
		}
	}

	protected function _createOtherAllocations($sType)
	{

		$this->createOtherAllocations($sType);

	}

	public function setAllocationData(array $aData)
	{

		$this->resetAllocationData();

		foreach ($aData as $sKey => $aAllocation) {
			if (isset($this->_aAllocations[$sKey])) {
				foreach ($aAllocation as $sSet => $mValue) {
					$this->_aAllocations[$sKey][$sSet] = $mValue;
				}
			}
		}

	}

	public function getService(\Ext_Thebing_Inquiry_Document_Version_Item $oItem)
	{
		$oService = $oItem->getService();
		// transfers sind einmalig gespeichert daher unseten damit er keine id benutzt
		if ($oService instanceof \Ext_TS_Transfer_Location) {
			$oService = null;
		}
		return $oService;
	}

	/**
	 * ermittelt das aktive/passive kunden/agentur konto oder die Abgrenzungskonten
	 * @param \Ext_Thebing_Inquiry_Document_Version_Item $oItem
	 * @param int $iTaxCategory
	 * @param string $sAccountType accrual_account_passive|accrual_account_active|passiv|active
	 * @return \stdClass
	 */
	public function getContinuanceAccount(\Ext_Thebing_Inquiry_Document_Version_Item $oItem, $iTaxCategory, $sAccountType = 'passiv')
	{

		$oDocument = $oItem->getDocument(true);
		$oCurrency = $oDocument->getCurrency();
		$oAgency = $oDocument->getAgency();
		$iCurrencyOption1 = $this->_oCompany->service_expense_account_currency;
		$iCurrencyOption2 = $this->_oCompany->service_income_account_currency;
		$oService = $this->getService($oItem);
		$iService = 0;
		if ($oService) {
			$iService = $oService->getId();
		}

		// Bei Passiven und aktiven konten muss je nach kunde/agentur ein prefix gesetz werden
		if (
			$sAccountType != 'accrual_account_passive' &&
			$sAccountType != 'accrual_account_active'
		) {
			$sTypePrefix = 'customer';
			if ($oAgency && $oAgency->getId() > 0) {
				$sTypePrefix = 'agency';
			}
			$sAccountType = $sTypePrefix . '_passive';
		}

		$aArray = array(
			'type' => $sAccountType,
			'account_type' => 'continuance',
			'vat_rate' => (int)$iTaxCategory,
			'currency_iso' => $oCurrency->getIso()
		);

		// wenn weder ausgabe noch einnahme je währung ist sind die abgrenzungskonten ebenfalls nicht je währung
		// das unterscheidet sich von den anderen einstellungen
		if (
			$iCurrencyOption1 != 1 &&
			$iCurrencyOption2 != 1
		) {
			$aArray['currency_iso'] = '';
		}

		$sKey = $this->generateKey($aArray);

		$aAccount = (array)$this->getAllocation($sKey);

		$aAccount['automatic_account'] = match ($this->_oCompany->automatic_account_setting) {
			'none' => 0,
			'all' => 1,
			default => $aAccount['automatic_account']
		};

		$oAccount = null;

		if (!empty($aAccount)) {
			$oAccount = (object)$aAccount;
		}

		return $oAccount;
	}

	/**
	 *
	 * @param Ext_Thebing_Inquiry_Document_Version_Item $oItem
	 * @param string $sAccountType income|expense
	 * @return stdClass
	 */
	public function getAccount(Ext_Thebing_Inquiry_Document_Version_Item $oItem, $iTaxCategory, $sAccountType = 'income')
	{

		$oDocument = $oItem->getDocument(true);
		$oCurrency = $oDocument->getCurrency();

		$aArray = array(
			'account_type' => $sAccountType,
			'vat_rate' => (int)$iTaxCategory,
			'currency_iso' => $oCurrency->getIso()
		);

		$oAccount = null;
		$aAccount = array();

		try {
			$aArray = $this->manipulateAccountKeyArray($aArray, $oItem, $sAccountType);
			$sKey = $this->generateKey($aArray);
			$aAccount = (array)$this->getAllocation($sKey);

			$aAccount['automatic_account'] = match ($this->_oCompany->automatic_account_setting) {
				'none' => 0,
				'all' => 1,
				default => $aAccount['automatic_account']
			};
		} catch (\Exception $exc) {
			\Ext_Thebing_Log::error('No account found', array('error' => $exc->getMessage(), 'info' => $aArray, 'item' => $oItem->getData(), 'account_type' => $sAccountType));
		}

		if (!empty($aAccount) && !empty($aAccount['account_number'])) {
			$oAccount = (object)$aAccount;
		}

		return $oAccount;
	}

	/**
	 * ermittelt die korrekten Elterninformationen vom Item
	 * @param Ext_Thebing_Inquiry_Document_Version_Item $oItem
	 * @return string
	 */
	public function getDocumentItemParentData(Ext_Thebing_Inquiry_Document_Version_Item $oItem)
	{

		$aData = array(
			'type' => '',
			'type_id' => 0
		);

		$oService = $this->getService($oItem);
		if ($oService instanceof \Ext_Thebing_Tuition_Course) {
			$oCategory = $oService->getCategory();
			$aData = array(
				'type' => 'course_category',
				'type_id' => $oCategory->getId()
			);
		} else if ($oService instanceof \Ext_Thebing_Accommodation_Category) {
			$aData = array(
				'type' => 'accommodation_category',
				'type_id' => $oService->getId()
			);
		} else if (
			(
				$oItem->type != 'additional_course' ||
				$oItem->type != 'additional_accommodation'
			) &&
			$oItem->parent_id > 0
		) {

			if ($oItem->type == 'additional_course') {
				$sParent = 'course';
			} else {
				$sParent = 'accommodation_category';
			}

			$aData = array(
				'type' => $sParent,
				'type_id' => $oItem->parent_id
			);
		} else if ($oItem->type == 'storno') {
			$aData = array(
				'type' => 'all',
				'type_id' => 0
			);
		}

		return $aData;
	}

	/**
	 * manipuliert das Array so das es nur die EInträge enthält die zu den aktuellen Einstellungen passen
	 * so das wenn das Array in die Key Methode gegeben wird der korrekte key für die aktuelle Einstellung zurück kommt
	 * @param array $aArray
	 * @param Ext_Thebing_Inquiry_Document_Version_Item $oItem
	 * @param type $sAccountType
	 * @return array
	 */
	public function manipulateAccountKeyArray($aArray, Ext_Thebing_Inquiry_Document_Version_Item $oItem, $sAccountType = 'income')
	{

		$sCurrencyOption = 'service_' . $sAccountType . '_account_currency';
		$iCurrencyOption = $this->_oCompany->$sCurrencyOption;

		/*
		 * wenn nicht je währung, dann key unseten um den korrekten key zu bekommen
		 * Provisionskonten bei Nettrechnungen haben diese Einstellung nicht
		 */
		if (
			$iCurrencyOption == 3 ||
			$sAccountType === 'expense_net'
		) {
			$aArray['currency_iso'] = '';
		}

		$aArray['type'] = $oItem->type;
		$aArray['type_id'] = $oItem->type_id;
		$aArray['parent_type'] = $oItem->parent_type;
		$aArray['parent_type_id'] = $oItem->parent_id;
		// Wenn Special dann müssen wir für das Konto das Urspungsitem nutzen
		// nur für belegtexte gibt es specielle einstellungen
		if ($oItem->type == 'special' && $oItem->parent_type == 'item_id') {
			$oItem = Ext_Thebing_Inquiry_Document_Version_Item::getInstance($oItem->parent_id);
			$aArray['type'] = $oItem->type;
			// bei stornierung sind die werte beschissen gespeichert
			// wir müssen die werte 1:1 umdrehen damit es stimmt....
		} else if ($oItem->parent_type == 'cancellation') {
			$aArray['type'] = $oItem->parent_type;
			$aArray['type_id'] = $oItem->parent_id;
			$aArray['parent_type'] = $oItem->type;
			$aArray['parent_type_id'] = $oItem->type_id;
		}

		$sItemType = $aArray['type'];

		// Bei Extra Nächten/Wochen müssen wir die Parent id ermitteln da
		// diese 1:1 wie unterkünfte klappen sollen
		// aber die parent_id nicht befüllt ist
		if (
			$sItemType == 'extra_nights' ||
			$sItemType == 'extra_weeks'
		) {
			$sItemType = 'accommodation';
			$aArray['type'] = $sItemType;
			// Parent ID ermitteln damit auch je Kategorie klappt
			$oJourneyAccommodation = \Ext_TS_Inquiry_Journey_Accommodation::getInstance($aArray['type_id']);
			$oCategory = $oJourneyAccommodation->getCategory();
			$aArray['parent_id'] = $oCategory->getId();
		}

		// Positionen haben nur jeweils ein Konto, kein »tasknten je Kategorie/Item«
		if ($sItemType === 'extraPosition') {
			$iOption = 3;
			$sItemType = 'extra_position';
			$aArray['type'] = $sItemType;
		} elseif ($sItemType === 'transfer') {
			$iOption = 3;
		} elseif ($sItemType === 'manual_creditnote') {
			$iOption = 3;
		} elseif ($sItemType === 'commission') {
			$iOption = 3;
		} else {

			// Aufwände kann man nur unter den Begriff "Creditnote" einstellen
			// hier muss _cn ergäntz werden
			// stornierung sowie die Währungsabfrage sind jedoch wiederrum ohne _cn....
			if (
				$sAccountType == 'expense' &&
				$sItemType != 'cancellation' &&
				$sItemType != 'currency'
			) {
				$sAccountType .= '_cn';
			}

			$sOptionType = 'service_' . $sAccountType . '_account_' . $sItemType;
			$iOption = $this->_oCompany->$sOptionType;
			$oService = $this->getService($oItem);

			if ($oService) {

				if ($sItemType != 'cancellation') {
					$aArray['type_id'] = $oService->getId();
					// Storno + ZUsatzkosten müssen umgeändert werden wenn wir je gebür verbuchen
				} else {

					//zusatzkosten
					if (
						$aArray['parent_type'] == 'additional_course' ||
						$aArray['parent_type'] == 'additional_accommodation'
					) {
						$aArray['parent_type_id'] = $oService->getId();
						// Bei Kurs/Unterkunft gibt es keine weitere Aufgliederung
					} else if (
						$aArray['parent_type'] == 'course' ||
						$aArray['parent_type'] == 'accommodation'
					) {
						$aArray['parent_type_id'] = 0;
					} else {
						$aArray['parent_type'] = 'all';
						$aArray['parent_type_id'] = 0;
					}

				}
				// Storno ohne leistung müssen immer "all" mit id 0 sein!
			} else if ($sItemType == 'cancellation') {
				$aArray['parent_type'] = 'all';
				$aArray['parent_type_id'] = 0;
			}
		}

		switch ($iOption) {
			case 1: // alles
				if ($sItemType != 'cancellation') {
					unset($aArray['parent_type']);
					unset($aArray['parent_type_id']);
				}
				break;
			case 2: // je Kategorie/Parent
				// Bei Kurs/UNterkunft muss die Type_id gelöscht werden da wir nach Eltern gehen
				// Bei zusatzkosten ist das noch mehr verschachtelt da brauchen wir die id..
				if (
					$sItemType != 'additional_course' &&
					$sItemType != 'additional_accommodation'
				) {
					unset($aArray['type_id']);
				}

				$aParent = $this->getDocumentItemParentData($oItem);

				$aArray['parent_type'] = $aParent['type'];
				$aArray['parent_type_id'] = $aParent['type_id'];

				break;
			case 3: // ein Konto
				unset($aArray['type_id']);
				unset($aArray['parent_type']);
				unset($aArray['parent_type_id']);
				break;
		}

		return $aArray;
	}

	protected function _setCompanyData()
	{
		$aCompanyAllocations = (array)$this->_oCompany->account_allocations;

		$aData = array();

		foreach ($aCompanyAllocations as $aAllocation) {
			$aAllocation['vat_rate'] = (int)$aAllocation['tax_id'];

			$sKey = $this->generateKey($aAllocation);

			$aData[$sKey] = array(
				'account_number' => $aAllocation['account_number'],
				'account_number_discount' => $aAllocation['account_number_discount'],
				'automatic_account' => $aAllocation['automatic_account'],
			);
		}

		$this->setAllocationData($aData);
	}

	public function resetAllocationData()
	{
		foreach ($this->_aAllocations as $sKey => $aAllocation) {
			$this->_aAllocations[$sKey]['account_number'] = '';
			$this->_aAllocations[$sKey]['account_number_discount'] = '';
			$this->_aAllocations[$sKey]['automatic_account'] = 0;
		}
	}

	protected function getSchools()
	{
		return $this->_oCompany->getCombinationObjectArray('getSchools');
	}

}
