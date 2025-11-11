<?php

use Communication\Interfaces\Model\CommunicationSubObject;
use TsRegistrationForm\Interfaces\RegistrationInquiryService;

/**
 * @property int $id
 * @property string $changed (TIMESTAMP)
 * @property string $created (TIMESTAMP)
 * @property int $active
 * @property int $creator_id
 * @property int $journey_id
 * @property int $insurance_id
 * @property int $document_id
 * @property string $from (DATE)
 * @property string $until (DATE)
 * @property int $weeks
 * @property int $visible
 * @property string $info_customer (TIMESTAMP)
 * @property string $info_provider (TIMESTAMP)
 * @property string $confirm (TIMESTAMP)
 * @property int $user_id
 * @property int $changes_info_customer
 * @property int $changes_info_provider
 * @property int $changes_confirm
 */
class Ext_TS_Inquiry_Journey_Insurance extends Ext_TS_Inquiry_Journey_Service implements Ext_TS_Service_Interface_Insurance, RegistrationInquiryService, \Communication\Interfaces\Model\HasCommunication {

	use Ts\Traits\LineItems\Insurance;
	
	/**
	 * @var string
	 */
	protected $_sTable = 'ts_inquiries_journeys_insurances';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'ts_iji';

	/**
	 * @var array
	 */
	public static $aInsuranceErrors = array();

	protected $sInfoTemplateType = 'insurance';

	/**
	 * @var array
	 */
	protected $_aFormat = array(
		'from' => array(
			'validate' => 'DATE',
			'required'=>true,
		),
		'until' => array(
			'validate' => 'DATE',
			'required'=>true,
		)
	);

	protected $_aJoinTables = [
		// Wird nur bei Anfragen verwendet
		'travellers' => [
			'table' => 'ts_inquiries_journeys_insurances_to_travellers',
			'foreign_key_field' => 'contact_id',
			'primary_key_field' => 'journey_insurance_id',
			'class' => Ext_TS_Inquiry_Contact_Traveller::class,
			'autoload' => false
		]
	];

	/**
	 * @deprecated
	 * @param int $iInquiryID
	 * @param null|string $sLang
	 * @param null|int $iKII_ID
	 * @return array
	 * @throws Exception
	 */
	public static function getInquiryInsurances($iInquiryID, $sLang = null, $iKII_ID = null) {

		self::$aInsuranceErrors = array();

        $oInquiry = Ext_TS_Inquiry::getInstance($iInquiryID);
		$oSchool = $oInquiry->getSchool();

		if(empty($sLang)) {
			$sLang = $oSchool->getLanguage();
		}
		
		$oInsurance = new Ext_Thebing_Insurance();
		$aData		= $oInsurance->getArray();

        $sTemp = 'name_'.$sLang;

		if(!array_key_exists($sTemp, $aData)) {
			$sLang = 'en';
		}

		$sWhere = "";
		if(!empty($iKII_ID)) {
			$sWhere = " AND `kii`.`id` = " . (int)$iKII_ID;
		}

		$sSQL = "
			SELECT
				`kii`.`id`,
				`kins`.`id` AS `insurance_id`,
				`kins`.`name_" . $sLang . "` AS `insurance`,
				UNIX_TIMESTAMP(`kii`.`from`) AS `from`,
				UNIX_TIMESTAMP(`kii`.`until`) AS `until`,
				`kii`.`weeks`,
				`kins`.`payment`,
				0 AS `price`,
				`ki`.`currency_id`,
				`ts_i_j`.`school_id`,
				`ki`.`created` `inquiry_created` -- Nötig für Discount-Berechnung
			FROM
				`ts_inquiries_journeys_insurances` AS `kii`	INNER JOIN
				`ts_inquiries_journeys` `ts_i_j` ON
					`ts_i_j`.`id` = `kii`.`journey_id` AND
					`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_i_j`.`active` = 1 INNER JOIN
				`ts_inquiries` AS `ki`					ON
					`ts_i_j`.`inquiry_id` = `ki`.`id`			INNER JOIN
				`kolumbus_insurances` AS `kins`					ON
					`kii`.`insurance_id` = `kins`.`id`
			WHERE
				`kii`.`active`	= 1	AND
				`kii`.`visible`	= 1	AND
				`ki`.`active`	= 1	AND
				`kins`.`active`	= 1	AND
				`ts_i_j`.`inquiry_id` = :inquiry_id
			" . $sWhere . "
		";
		$aSQL = array('inquiry_id' => $iInquiryID);
		$aResult = DB::getPreparedQueryData($sSQL, $aSQL);

		$oGui2InsuranceCustomer = new Ext_Thebing_Insurances_Gui2_Customer($aResult);

		$aResult = $oGui2InsuranceCustomer->format($aResult, $sLang);

		self::$aInsuranceErrors = (array)$oGui2InsuranceCustomer->aErrors;

		return $aResult;
	}

	/**
	 * @param string $sLang
	 * @return array|mixed
	 */
	public function getInsuranceName($sLang = 'en') {

        $oInsurance = $this->getInsurance();
        $sName = $oInsurance->getName($sLang);

		return $sName;
	}

	/**
	 * @return Ext_Thebing_Insurance
	 */
	public function getInsurance() {

		$oInsurance	= Ext_Thebing_Insurance::getInstance($this->insurance_id);

		return $oInsurance;
	}

	/**
	 * @return Ext_Thebing_Insurances_Provider
	 */
	public function getInsuranceProvider() {

		$oInsurance	= Ext_Thebing_Insurance::getInstance($this->insurance_id);
		$oProvider	= Ext_Thebing_Insurances_Provider::getInstance($oInsurance->provider_id);

		return $oProvider;
	}

	/**
	 * Parent hat bereits getFrom()!
	 *
	 * @deprecated
	 * @return string|null
	 */
	public function getInsuranceStart() {

		// Muss für Index null sein
		if($this->from == '0000-00-00') {
			return null;
		}

		return $this->from;
	}

	/**
	 * Parent hat bereits getUntil)!
	 *
	 * @deprecated
	 * @return string|null
	 */
	public function getInsuranceEnd() {

		// Muss für Index null sein
		if($this->until == '0000-00-00') {
			return null;
		}

		return $this->until;
	}

	/**
	 * @inheritdoc
	 */
	public function getUntil() {
		return $this->getInsuranceEnd();
	}

	/**
	 * {@inheritdoc}
	 */
//	public function getInquiry() {
//
//		if($this->_oInquiry === null)  {
//			$this->setInquiry(Ext_TS_Inquiry::getInstance($this->inquiry_id));
//		}
//
//		return parent::getInquiry();
//
//	}

	/**
	 * @param null $oInsurance
	 * @param string $sModus
	 * @return bool|string
	 * @throws Exception
	 */
	public function checkForChange($oInsurance = null, $sModus = 'complete') {

		if($this->id <= 0) {
			return 'new';
		}

		if($this->active == 0) {
			return 'delete';
		}

		if($oInsurance == null) {
			$aOriginalData = $this->getOriginalData();
		} else {
			$aOriginalData = $oInsurance->getData();
		}

		if($sModus == 'complete') {

			if(
				(int)$this->insurance_id != (int)$aOriginalData['insurance_id'] ||
				$this->from	!= $aOriginalData['from'] ||
				$this->until != $aOriginalData['until'] ||
				$this->visible != $aOriginalData['visible']
			) {
				return 'edit';
			}

		} else if($sModus == 'only_time') {

			if(
				(int)$this->from != $aOriginalData['from'] ||
				(int)$this->until != $aOriginalData['until']
			) {
				return 'edit';
			}

		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function isConfirmed() {

		$bConfirmed = false;

		$mConfirm = (int)$this->confirm;
		if($mConfirm > 0){ 
			$bConfirmed = true;
		}
		
		return $bConfirmed;
	}

	/**
	 * @param int $iSchoolId
	 * @param string $sLanguage
	 * @param null $aData
	 * @return string
	 */
	public function getInfo($iSchoolId, $sLanguage, $aData = null) {
	
		$oInquiry = $this->getInquiry();

		if($aData === null) {
			$aData = self::getInquiryInsurances($this->inquiry_id, $sLanguage, $this->id);
			$aData = reset($aData);	
		}

		$sName = $aData['payment'].' '.$aData['insurance'].' ';
		$sName .= Ext_Thebing_Format::LocalDate($this->from, $iSchoolId).' - '.Ext_Thebing_Format::LocalDate($this->until, $iSchoolId);

		if(
			$oInquiry instanceof Ext_TS_Inquiry &&
			$oInquiry->hasGroup() &&
			$oInquiry->isGuide() &&
			$oInquiry->getJourneyTravellerOption('free_all')
		) {
			// Gratis-Gruppen-Guides extra aufführen in Maske
			$sName .= ' (' . \Ext_TC_Placeholder_Abstract::translateFrontend('gratis', $sLanguage) . ')';
		}

		return $sName;
	}

	/**
	 * @return string
	 */
 	public function getNameForEditData() {

		$sFrom = Ext_Thebing_Format::LocalDate($this->from);
		$sUntil = Ext_Thebing_Format::LocalDate($this->until);

		$sInterfaceLanguage = Ext_Thebing_School::fetchInterfaceLanguage();

		$sNameField = 'name_'.$sInterfaceLanguage;

		$sName = $this->getInsurance()->$sNameField. ' ('.$sFrom." - ".$sUntil.")";

		return $sName;
	}

	/**
	 * @return string
	 */
	public function getKey() {
		return 'insurance';
	}

	/**
	 * @return array
	 */
	public function validatePayment() {
		return array();
	}

	/**
	 * @param bool $bThrowExceptions
	 * @return array|bool
	 */
	public function validate($bThrowExceptions = false) {

		$aErrors = parent::validate($bThrowExceptions);
		
		if($aErrors === true) {			
			$aErrors = $this->_checkTimePeriod();
		}
		
		return $aErrors;
	}

	/**
	 * @return array|bool
	 */
	protected function _checkTimePeriod() {
		
		$mError = true;
		
		$oDateFrom = new DateTime($this->from);
		$oDateUntil = new DateTime($this->until);

		if($oDateUntil < $oDateFrom) {
			$mError = array();
			$mError[][] = L10N::t('Das Enddatum des angegebenen Zeitraumes muss größer sein als das Startdatum!');
		}
		
		return $mError;
	}

	/**
	 * @param array $aSqlParts
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		parent::manipulateSqlParts($aSqlParts, $sView);

		$sLang = Ext_TC_System::getInterfaceLanguage();

		$iAddressLabelContactAddress = (int)Ext_TS_AddressLabel::getContactAdressLabelId();

		$sJourneyAdditional = '';
		if(!Ext_Thebing_System::isAllSchools()) {
			$sJourneyAdditional = "AND `ts_i_j`.`school_id` = ".Ext_Thebing_School::getSchoolFromSession()->getId();
		}

		$sWhere = '';
		$iCustomerSetting = (int)Ext_Thebing_System::getConfig('show_customer_without_invoice');
		if($iCustomerSetting == 0) {
			$sWhere = " AND (`ts_i`.`has_invoice` = 1 OR `ts_i`.`has_proforma` = 1) ";
		} elseif($iCustomerSetting == 2) {
			$sWhere = " AND `ts_i`.`has_invoice` = 1 ";
		}
		$sWhere.= "AND `ts_i`.`confirmed` > 0";
		$aSqlParts['select'] = "
			`ts_iji`.`id`,
			`ts_iji`.`id` `inquiry_insurance_id`,
			`ts_iji`.`weeks`,
			`tc_c`.`gender`,
			`tc_c`.`lastname`,
			`tc_c`.`firstname`,
			`d_l_cl`.`name_".$sLang."` `corresponding_language`,
			`tc_c`.`birthday` `customer_birthday`,
			`tc_c`.`nationality` `customer_nationality`,
			`kins`.`name_".$sLang."` `insurance`,
			UNIX_TIMESTAMP(`ts_iji`.`from`) `from`,
			UNIX_TIMESTAMP(`ts_iji`.`until`) `until`,
			`kins`.`id` `insurance_id`,
			`kins`.`payment`,
			0 AS `price`,
			DATE(`ts_iji`.`info_customer`) `info_customer`,
			DATE(`ts_iji`.`info_provider`) `info_provider`,
			DATE(`ts_iji`.`confirm`) `confirm`,
			`ts_i`.`currency_id`,
			`ts_iji`.`changes_info_customer`,
			`ts_iji`.`changes_info_provider`,
			`ts_iji`.`changes_confirm`,
			`ts_i`.`id`	`inquiry_id`,
			`ts_i`.`inbox`,
			`ts_i`.`canceled` `canceled`,
			`d_c1`.`cn_short_".$sLang."` `customer_country`,
			`d_c2`.`nationality_".$sLang."` `customer_nationality_full`,
			`ts_i_j`.`school_id` `school_id`,
			`kg`.`short` `group_short`,
			`kg`.`name` `group_name`,
			DATEDIFF(`ts_iji`.`until`, `ts_iji`.`from`) `datediff`,
			`k_a`.`ext_1` `agency`,
			`k_a`.`ext_2` `agency_short`,
			`ts_iji`.`created` `created`,
			`ts_iji`.`creator_id` `creator_id`,
			`ts_iji`.`changed` `changed`,
			`ts_iji`.`user_id` `user_id`,
			`tc_cn`.`number` as `customerNumber`,
			`tc_ea`.`email`,
			 MAX(`ts_ijc`.`until`) `course_last_end_date`,
			 `kss`.`id` `student_status`,
			 `ts_j_t_v_d`.`passport_number`,
			 `tc_c_d_phone`.`value` `customer_phone`,
			 `tc_a`.`address`,
			 `tc_a`.`city`,
			 `tc_a`.`zip`
		";
		# #20464
//		`tc_c_emergency`.`firstname` `emergency_contact_firstname`,
//			 `tc_c_emergency`.`lastname` `emergency_contact_lastname`,
//			 `tc_ea_emergency`.`email` `emergency_contact_email`,
//			 `tc_c_d_phone_emergency`.`value` `emergency_contact_phone`

		$aSqlParts['from'] .= " INNER JOIN
			`ts_inquiries_journeys` `ts_i_j` ON
				`ts_i_j`.`id` = `ts_iji`.`journey_id` AND
				`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
				`ts_i_j`.`active` = 1 ".$sJourneyAdditional." INNER JOIN
			`ts_inquiries` AS `ts_i` ON
				`ts_i_j`.`inquiry_id` = `ts_i`.`id`	LEFT JOIN
			`kolumbus_student_status` `kss` ON
				`kss`.`id` = `ts_i`.`status_id` LEFT JOIN
			`ts_companies` as `k_a` ON
				`k_a`.`id` = `ts_i`.`agency_id` AND
				`k_a`.`active` = 1 INNER JOIN
			`ts_inquiries_to_contacts` `ts_i_to_c` ON
				`ts_i_to_c`.`inquiry_id` = `ts_i`.`id` AND
				`ts_i_to_c`.`type` = 'traveller' INNER JOIN
			`tc_contacts` AS `tc_c`	ON
				`tc_c`.`id` = `ts_i_to_c`.`contact_id` AND
				`tc_c`.`active` = 1	 INNER JOIN
			`kolumbus_insurances` AS `kins`	 ON
				`ts_iji`.`insurance_id` = `kins`.`id` LEFT JOIN
			`tc_contacts_to_addresses` `tc_c_to_a` ON
				`tc_c_to_a`.`contact_id` = `tc_c`.`id` LEFT JOIN
			`tc_contacts_numbers` `tc_cn` ON
			`tc_cn`.`contact_id` = `tc_c`.`id` LEFT JOIN
			`tc_addresses` `tc_a` ON
				`tc_a`.`id` = `tc_c_to_a`.`address_id` AND
				`tc_a`.`active` = 1 AND
				`tc_a`.`label_id` = ".$iAddressLabelContactAddress." LEFT JOIN
			`tc_contacts_to_emailaddresses` `tc_tea` ON
				`tc_tea`.`contact_id` = `tc_c`.`id` LEFT JOIN
			`tc_emailaddresses` `tc_ea` ON
				`tc_ea`.`id` = `tc_tea`.`emailaddress_id` LEFT JOIN
			`data_countries` AS `d_c1` ON
				`d_c1`.`cn_iso_2` = `tc_a`.`country_iso` LEFT JOIN
			`data_countries` AS `d_c2` ON
				`d_c2`.`cn_iso_2` = `tc_c`.`nationality` LEFT JOIN
			`kolumbus_groups` `kg` ON
				`kg`.`id` = `ts_i`.`group_id` AND
				`kg`.`active` = 1 LEFT JOIN
			`data_languages` `d_l_cl` ON
				`d_l_cl`.`iso_639_1` = `tc_c`.`corresponding_language` LEFT JOIN
			`ts_inquiries_journeys_courses` AS `ts_ijc`	ON
				`ts_i_j`.`id` = `ts_ijc`.`journey_id` LEFT JOIN
			`ts_journeys_travellers_visa_data` `ts_j_t_v_d` ON
				`ts_j_t_v_d`.`journey_id` = `ts_i_j`.`id` AND	
				`ts_j_t_v_d`.`traveller_id` = `tc_c`.`id` LEFT JOIN
			`tc_contacts_details` `tc_c_d_phone` ON
				`tc_c_d_phone`.`contact_id` = `tc_c`.`id` AND	
				`tc_c_d_phone`.`type` = 'phone_private'	
		";

		$aSqlParts['where'] .= " AND
			`ts_iji`.`visible` = 1 AND
			`ts_i`.`active`	= 1	AND
			`tc_c`.`active`	= 1	AND
			`kins`.`active`	= 1 "
			.$sWhere."
		";

		$aSqlParts['groupby'] = "
			`ts_iji`.`id`
		";

	}

	public function getRegistrationFormData(): array {

		$dFrom = \Ext_Thebing_Util::convertDateStringToDateOrNull($this->from);
		$dUntil = \Ext_Thebing_Util::convertDateStringToDateOrNull($this->until);

		return [
			'insurance' => !empty($this->insurance_id) ? (int)$this->insurance_id : null,
			'start' => $dFrom !== null ? 'date:'.$dFrom->toDateString() : null,
			'duration' => !empty($this->weeks) ? (int)$this->weeks : null,
			'end' => $dUntil !== null ? 'date:'.$dUntil->toDateString() : null,
		];

	}

	public function getPaidAmount(): float {

		// Sonderfall Versicherungen, weil der Betrag für einzele Services nirgends sonst benötigt wird
		// Gelöschte Rechnungen oder Proformas sollten keine Rolle spielen, da die Zahlungsitems nur einmal da sind
		$sSql = "
			SELECT
				SUM(kipi.amount_inquiry)
			FROM
				kolumbus_inquiries_documents_versions_items kidvi INNER JOIN
				kolumbus_inquiries_payments_items kipi ON
					kipi.item_id = kidvi.id AND
					kipi.active = 1 INNER JOIN
				kolumbus_inquiries_payments kip ON
					kip.id = kipi.payment_id AND
					kip.active = 1
			WHERE
				kidvi.type = 'insurance' AND
				kidvi.type_id = :journey_insurance_id AND
				kidvi.active = 1
		";

		return (float)DB::getQueryOne($sSql, ['journey_insurance_id' => $this->id]);

	}

	public function getCommunicationDefaultApplication(): string
	{
		return \Ts\Communication\Application\Insurance\CustomerAgency::class;
	}

	public function getCommunicationLabel(\Tc\Service\LanguageAbstract $l10n): string
	{
		return '';
	}

	public function getCommunicationSubObject(): CommunicationSubObject
	{
		return $this->getJourney()->getSchool();
	}

	public function getCommunicationAdditionalRelations(): array
	{
		return [
			$this->getJourney()->getInquiry()
		];
	}
}
