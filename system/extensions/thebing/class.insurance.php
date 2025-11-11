<?php

class Ext_Thebing_Insurance extends Ext_Thebing_Basic {

	const TYPE_ONCE = 1;

	const TYPE_DAILY = 2;

	const TYPE_WEEK = 3;

	protected $_aWeeks = array();

	protected $_sTable = 'kolumbus_insurances';

	protected $_sTableAlias = 'kins';

	protected $_aAttributes = [
		'frontend_icon_class' => [
			'class' => 'WDBasic_Attribute_Type_Varchar'
		],
		'cost_center' => [
			'class' => 'WDBasic_Attribute_Type_Varchar'
		]
	];

	protected $_aJoinTables = [
		'pdf_templates' => [
			'table' => 'kolumbus_pdf_templates_services',
			'class' => 'Ext_Thebing_Pdf_Template',
			'primary_key_field' => 'service_id',
			'foreign_key_field' => 'template_id',
			'static_key_fields'	=> ['service_type' => 'insurance'],
			'autoload' => false
		]
	];

	protected $_aJoinedObjects = array(
		'kinsp' => array(
			'class' => 'Ext_Thebing_Insurances_Provider',
			'key' => 'provider_id'
		)
	);

	protected $_aFormat = array(
		'provider_id' => array(
			'required' => true,
			'validate' => 'INT_POSITIVE'
		)
	);

	protected $_aFlexibleFieldsConfig = [
		'insurances' => []
	];

	/**
	 * {@inheritdoc}
	 */
	public function __set($sName, $sValue) {

		if($sName == 'weeks') {
			$this->_aWeeks = (array)$sValue;
		} elseif($sName == 'user_id') {
			throw new Exception('User-ID is not rewritable value.');
		} else {
			parent::__set($sName, $sValue);
		}

	}

	/**
	 * {@inheritdoc}
	 */
	public function __get($sName) {

		Ext_Gui2_Index_Registry::set($this);

		if($sName == 'weeks') {
			$sValue = $this->_aWeeks;
		} else {
			$sValue = parent::__get($sName);
		}

		return $sValue;

	}

	/**
	 * @inheritdoc
	 */
	public function validate($bThrowExceptions = false) {

		$mValidate = parent::validate($bThrowExceptions);

		if($mValidate === true) {
			// Klick auf All + verhindern
			if(count($this->pdf_templates) > System::d('ts_max_attached_additional_docments', Ext_Thebing_Document::MAX_ATTACHED_ADDITIONAL_DOCUMENTS)) {
				$mValidate = ['pdf_templates' => 'TOO_MANY'];
			}
		}

		return $mValidate;

	}

	/**
	 * {@inheritdoc}
	 */
	public function save($bLog = true) {

		$aWeeks = $this->_aWeeks;

		if($this->payment != 3) {
			$aWeeks = array();
		}

		parent::save($bLog);

		DB::updateJoinData(
			'kolumbus_insurances2weeks',
			array('insurance_id' => $this->id),
			$aWeeks,
			'week_id'
		);

		$this->weeks = $aWeeks;

		return $this;

	}

	/**
	 * {@inheritdoc}
	 */
	protected function _loadData($iDataID) {

		parent::_loadData($iDataID);

		if($iDataID > 0) {
			// Load weeks links
			$this->_aWeeks = DB::getJoinData(
				'kolumbus_insurances2weeks',
				array('insurance_id' => $this->id),
				'week_id'
			);
		}

	}

	/**
	 * {@inheritdoc}
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {
		$aSqlParts['from'] .= "
			INNER JOIN
				`kolumbus_insurance_providers` AS `kinsp`
			ON
				`kins`.`provider_id` = `kinsp`.`id`
		";
	}

	/**
	 * Name der Versicherung
	 *
	 * @param string $sLang
	 * @return mixed
	 */
	public function getName($sLang = ''){

		if(empty($sLang)) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			$sLang = $oSchool->getInterfaceLanguage();
		}

		$sColumn = 'name_' . $sLang;
		return $this->$sColumn;

	}	

	/**
	 * Prüft, ob es sich um eine Wochenversicherung handelt.
	 *
	 * @return boolean 
	 */
	public function isWeekInsurance() {
		return !empty($this->weeks);
	}

	/**
	 * @return Ext_Thebing_Insurances_Provider 
	 */
	public function getProvider() {
		return Ext_Thebing_Insurances_Provider::getInstance($this->provider_id);
	}

	/**
	 * Errechnet den Versicherungspreis für den angegebenen Zeitraum.
	 *
	 * Der zurückgegebene Preis beinhaltet die Umsatzsteuer.
	 *
	 * @param Ext_Thebing_School $oSchool
	 * @param Ext_Thebing_Currency $oCurrency
	 * @param \DateTime $dStartDate
	 * @param \DateTime $dEndDate
	 * @return float
	 */
	public function getInsurancePriceForPeriod(Ext_Thebing_School $oSchool, Ext_Thebing_Currency $oCurrency, \DateTime $dStartDate, \DateTime $dEndDate, $iWeeks) {

		$oFormat = new Ext_Thebing_Insurances_Gui2_Customer($this);
		$aData = array(array(
			'school_id' => $oSchool->id,
			'insurance_id' => $this->id,
			'currency_id' => $oCurrency->id,
			'from' => $dStartDate->getTimestamp(),
			'until' => $dEndDate->getTimestamp(),
			'weeks' => $iWeeks,
			'payment' => $this->payment
		));
		$aData = $oFormat->format($aData);
		$aData = reset($aData);

		if($oSchool->getTaxStatus() == Ext_Thebing_School::TAX_EXCLUSIVE) {

			$iTaxRate = 0;
			$iTaxCategory = Ext_TS_Vat::getDefaultCombination('Ext_Thebing_Insurances', $this->id, $oSchool);
			if($iTaxCategory > 0) {
				$iTaxRate = Ext_TS_Vat::getTaxRate($iTaxCategory, $oSchool->id);
			}

			$aTax = Ext_TS_Vat::calculateExclusiveTaxes($aData['price'], $iTaxRate);
			$aData['price'] += $aTax['amount'];

		}

		return (float)$aData['price'];

	}

}
