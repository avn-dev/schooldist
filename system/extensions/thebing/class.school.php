<?php

use Carbon\Carbon;
use Communication\Interfaces\Model\CommunicationContact;
use Communication\Interfaces\Model\CommunicationSubObject;
use Communication\Interfaces\Model\CommunicationSender;
use FileManager\Traits\FileManagerTrait;

/**
 * @property string $ext_1
 * @property string $short
 * @property integer $idClient
 * @property string|int $course_startday Starttag der Kurse (1 = Montag, ..., 7 = Sonntag / date('N')-Format)
 * @property string $accommodation_start Starttag der Unterkünfte (mo = Montag, ..., so = Sonntag)
 *                                    (siehe Ext_Thebing_Util::convertWeekdayToInt())
 * @property integer $tuition_automatic_holiday_allocation
 * @property integer $inclusive_nights Anzahl der Nächte pro Unterkunftswoche
 * @property string $date_format_long
 * @property string $date_format_short
 * @property string $account_holder Gibt den Namen des Kontoinhabers wieder
 * @property string $account_number Gibt die Kontonummer wieder
 * @property string $bank Name der Bank
 * @property string $bank_code Bankleitzahl der Bank
 * @property string $bank_address Bankadresse
 * @property string $iban Ibannummer der Schule
 * @property string $bic Bicnummer der Schule
 * @property string $sepa_file_format Gibt an zu welchem Dateiformat der Export ausgeführt werden soll (Liste: Bezahlte Unterkunftsanbieter; Dieser Wert kommt aus der WDBasic_Attributes-Tabelle)
 * @property string $sepa_export_separator Trennzeichen des Export (Wird nur bei CSV und TXT verwendet, da SEPA ein XML-Format hat; Dieser Wert kommt aus der WDBasic_Attributes-Tabelle)
 * @property string $sepa_file_coding Kodierung der Datei (Dieser Wert kommt aus der WDBasic_Attributes-Tabelle)
 * @property string $sepa_export_per Welche Daten sollen berücksichtigt werden (Dieser Wert kommt aus der WDBasic_Attributes-Tabelle)
 * @property string $sepa_columns Welche Spalten sollen verwendet werden (gilt nur für CSV und TXT; Dieser Wert kommt aus der WDBasic_Attributes-Tabelle)
 * @property string $sepa_comment Kommentar für den Verwendungszweck (Gilt nur für das SEPA-XML; Dieser Wert kommt aus der WDBasic_Attributes-Tabelle)
 * @property string $sepa_pain_format "pain.001.001.09" (default) or "pain.001.002.03" (optional) or "pain.001.001.03" (optional) (Dieser Wert kommt aus der WDBasic_Attributes-Tabelle)
 * @property string $sepa_org_id (Dieser Wert kommt aus der WDBasic_Attributes-Tabelle)
 * @property string $cost_center (Dieser Wert kommt aus der WDBasic_Attributes-Tabelle)
 * @property string|int $frontend_years_of_bookable_services
 * @property string|int $frontend_min_bookable_days_ahead
 * @property string $tuition_excused_absence_calculation
 * @property int $tuition_allow_allocation_with_attendances_modification
 */
class Ext_Thebing_School extends Ext_Thebing_Basic implements CommunicationSubObject, CommunicationContact, CommunicationSender {

	use FileManagerTrait;

	use \Core\Traits\WdBasic\MetableTrait;

	use \Ts\Traits\BankAccount;

	use \Illuminate\Notifications\RoutesNotifications;

	/**
	 * Steuern: Inklusive (berechnete Preise enthalten Steuern)
	 *
	 * @var integer
	 */
	const TAX_INCLUSIVE = 1;

	/**
	 * Steuern: Exklusive (berechnete Preise enthalten KEINE Steuern)
	 *
	 * @var integer
	 */
	const TAX_EXCLUSIVE = 2;

	/**
	 * @var string
	 */
	const ACCOMMODATION_COMBINATION_CACHE = 'Ext_Thebing_School::getAccommodationCombinations';

	const ADDITIONAL_SERVICES_CACHE_GROUP = 'TS_ADDITIONAL_SERVICES_CACHE_GROUP';

	/**
	 * @var string
	 */
	protected $sLanguage;

	/**
	 * @var null|mixed[]
	 */
	protected $aLanguages;

	/**
	 * @var mixed[]
	 */
	protected static $_aCache = [];

	/**
	 * @var mixed[]
	 */
	protected $_aListCacheFields = ['id', 'ext_1', 'short', 'language'];

	/**
	 * @var mixed[]
	 */
	public static $_aCacheTransferProvider = [];

	/**
	 * @var null|Ext_Thebing_Client
	 */
	protected $_oClient = null;

	protected $_sPlaceholderClass = \Ts\Service\Placeholder\School::class;

	/**
	 * @var mixed[]
	 */
	protected $_aFormat = [
		'idClient' => [
			'required'	=> true,
			'validate'	=> 'INT_POSITIVE',
		],
		'ext_1' => [
			'required' => true,
		],
		'email' => [
			'required' => true,
			'validate' => 'MAIL',
		],
		'passport_due' => [
			'validate' => 'INT_NOTNEGATIVE',
		],
		'visum_due' => [
			'validate' => 'INT_NOTNEGATIVE',
		],
		'date_format_long' => [
			'required' => true,
		],
		'date_format_short' => [
			'required' => true,
		],
		'critical_attendance' => [
			'validate' => 'FLOAT_POSITIVE',
		],
		'adult_age' => [
			'validate' => 'INT_POSITIVE',
		],
		'examination_automatically_passed' => [
			'validate' => 'INT_POSITIVE',
		],
		'iban' => [
			'validate' => 'IBAN'
		],
		'frontend_min_bookable_days_ahead' => [
			'validate' => 'INT_NOTNEGATIVE'
		]
	];

	/**
	 * @var mixed[]
	 */
	protected $_aJoinTables = [
		'classroom_usage' => [
			'table' => 'ts_schools_classrooms_usage',
			'primary_key_field' => 'school_id',
			'foreign_key_field' => 'classroom_id',
			'sort_column' => 'position',
			'autoload' => false,
			'on_delete' => 'no_action'
		],
		'activity_times' => [
			'table' => 'ts_schools_activities_times',
			'primary_key_field' => 'school_id',
			'autoload' => false,
			'check_active' => true
		],
		'productlines' => [
			'table' => 'ts_productlines_schools',
			'primary_key_field'	=> 'school_id',
			'foreign_key_field'	=> 'productline_id',
			'autoload' => false,
			'on_delete' => 'no_action',
			'class' => 'Ext_TC_Productline'
		],
		'app_settings' => [
			'table' => 'ts_schools_app_settings',
			'foreign_key_field' => ['key', 'additional', 'value'],
			'primary_key_field' => 'school_id',
			'autoload' => false,
			'on_delete' => 'no_action'
		],
		'teacherlogin_flexfields' => [
			'table' => 'ts_schools_teacherlogin_flex_fields',
			'foreign_key_field' => 'field_id',
			'primary_key_field' => 'school_id',
			'autoload' => false,
			'on_delete' => 'no_action'
		],
		'levels'=> [
			'table' => 'ts_tuition_levels_to_schools',
			'class' => 'Ext_Thebing_Tuition_Level',
			'primary_key_field' => 'school_id',
			'foreign_key_field' => 'level_id',
			'autoload' => false,
		],
		'communication_emailsignatures' => [
			'table' => 'tc_objects_emailsignatures',
			'primary_key_field' => 'object_id',
			'autoload' => false
		],
	];

	/**
	 * Eine Liste mit Klassen, die sich auf dieses Object beziehen, bzw.
	 * mit diesem verknüpft sind (parent: n-1, 1-1, child: 1-n, n-m)
	 *
	 * array(
	 *		'ALIAS'=>array(
	 *			'class'=>'Ext_Class',
	 *			'key'=>'class_id',
	 *			'type'=>'child' / 'parent',
	 *			'check_active'=>true,
	 *			'orderby'=>position,
	 *			'orderby_type'=>ASC,
	 *			'orderby_set'=>false
	 *			'query' => false,
	 *          'readonly' => false,
	 *			'cloneable' => true,
	 *			'static_key_fields' = array('field' => 'value'),
	 *			'on_delete' => 'cascade' / '' / 'detach' ( nur bei "childs" möglich ),
	 *			'bidirectional' => false // legt fest, ob eine Verknüpfung in beide Richtungen besteht
	 *		)
	 * )
	 *
	 * @var array
	 */
	protected $_aJoinedObjects = [
		'teacherlogin_template' => [
			'class' => \Ext_TC_Communication_Template::class,
			'key' => 'teacherlogin_template',
			'type' => 'parent',
			'check_active'=>true
		],
		'teacherlogin_reportcard_template' => [
			'class' => \Ext_TC_Communication_Template::class,
			'key' => 'teacherlogin_reportcard_template',
			'type' => 'parent',
			'check_active'=>true
		],
		'class_times' => [
			'class' => 'Ext_Thebing_School_ClassTimes',
			'type' => 'child',
			'key' => 'school_id',
			'check_active'=>true
		]
	];

	/**
	 * @var array
	 */
	protected $_aAttributes = [
		'sepa_file_format' => [
			'class' => 'WDBasic_Attribute_Type_Varchar',
		],
		'sepa_export_separator' => [
			'class' => 'WDBasic_Attribute_Type_Varchar',
		],
		'sepa_file_coding' => [
			'class' => 'WDBasic_Attribute_Type_Varchar',
		],
		'sepa_export_per' => [
			'class' => 'WDBasic_Attribute_Type_Varchar',
		],
		'sepa_columns' => [
			'class' => 'WDBasic_Attribute_Type_Array',
		],
		'sepa_comment' => [
			'class' => 'WDBasic_Attribute_Type_Text',
		],
		'sepa_pain_format' => [
			'class' => 'WDBasic_Attribute_Type_Text',
		],
		'sepa_org_id' => [
			'class' => 'WDBasic_Attribute_Type_Varchar',
		],
		'teacherlogin_teacher_email_replyto' => [
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		],
		'cost_center' => [
			'class' => 'WDBasic_Attribute_Type_Varchar',
		],
		'accommodation_parallel_assignment' => [
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		],
		'frontend_course_structure' => [
			'class' => 'WDBasic_Attribute_Type_Text',
		],
		'sms_sender' => [
			'class' => 'WDBasic_Attribute_Type_Varchar',
			'validate' => 'REGEX',
			'validate_value' => '([A-Za-z0-9]{1,11}|[0-9]{1,16})'
		],
		'teacherlogin_show_internal_class_comment' => [
			'type' => 'int'
		],
		'teacherlogin_show_course_comment_in_attendance' => [
			'type' => 'int'
		],
		'teacherlogin_communication_enable_booking_contact_email' => [
			'type' => 'int'
		],
		'teacherlogin_student_informations' => [
			'type' => 'array'
		],
		'default_placementtest_id' => [
			'type' => 'int'
		],
		'tuition_show_empty_classes' => [
			'type' => 'int'
		],
		'tuition_automatic_course_extension_allocation' => [
			'type' => 'int'
		],
		'default_communication_layout_id' => [
			'type' => 'int'
		],
		'tuition_allow_allocation_with_attendances_modification' => [
			'type' => 'int'
		],
		'invoice_amount_null_forbidden' => [
			'type' => 'int'
		],
		'draft_invoices' => [
			'type' => 'int'
		]
	];

	/**
	 * @var string
	 */
	protected $_sTable = 'customer_db_2';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'cdb2';

	/**
	 * @var mixed[]
	 */
	protected $_aFlexibleFieldsConfig = [
		'schools_accounting' => [],
		'schools_frontend' => []
	];

	/**
	 * @inheritdoc
	 */
	public function __construct($iDataID = 0, $sTable = null) {

		// Erster Paramter war damals $sMd5 und konnte ID oder String sein
		// TODO: Irgendwann mal rausnehmen
		if(
			!empty($iDataID) && // "" wird auch gerne mal übergeben
			is_string($iDataID) &&
			!is_numeric($iDataID)
		) {
			throw new InvalidArgumentException('ID cannot be a non numeric string');
		}

		parent::__construct($iDataID, $sTable);

	}

	/**
	 * Gibt das Schul-Objekt zur angegeben Schule zurück.
	 *
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @return Ext_Thebing_School
	 */
	public static function createSchoolObjectFromArgument($mSchool) {

		$oSchool = null;

		if($mSchool instanceof Ext_Thebing_School) {
			$oSchool = $mSchool;
		} elseif($mSchool instanceof Ext_Thebing_School_Proxy) {
			$oSchool = Ext_Thebing_School::getInstance($mSchool->getId());
		} else {
			$oSchool = Ext_Thebing_School::getInstance($mSchool);
		}

		return $oSchool;

	}

	/**
	 * Liefert die Client Id
	 *
	 * @return int
	 */
	public function getClientId() {
		return (int)$this->_aData['idClient'];
	}

	/**
	 * @return Ext_Thebing_Client
	 */
	public function getClient() {

		//Muss Objekt-intern auch noch gecached werden wegen den Unittests, da es keine setInstance Methode gibt in der WDBasic
		if($this->_oClient === null) {
			$oClient = Ext_Thebing_Client::getInstance($this->idClient);
			$this->_oClient = $oClient;
		}

		return (object)$this->_oClient;

	}

	/**
	 * Setter injection für die Unittests, damit man die Client Lizenzinformationen mokken kann
	 *
	 * @param Ext_Thebing_Client $oClient
	 */
	public function setClient(Ext_Thebing_Client $oClient) {
		$this->_oClient = $oClient;
	}

	/**
	 * Nummerformat der Schule
	 *
	 * @param int|bool $iFormat
	 * @return array
	 */
	public function getNumberFormatData($iFormat = false) {

		if(!$iFormat) {
			$iFormat = $this->number_format;
		}

		$aFormat = self::getNumberFormatArray();
		$aValue	= $aFormat[$iFormat];

		return $aValue;

	}

	/**
	 * @return array
	 */
	public static function getNumberFormatArray() {

		$aFormat = array();
		$aFormat[0] = array('t' => '.', 'e' => ',');
		$aFormat[1] = array('t' => ',', 'e' => '.');
		$aFormat[2] = array('t' => ' ', 'e' => '.');
		$aFormat[3] = array('t' => ' ', 'e' => ',');
		$aFormat[4] = array('t' => "'", 'e' => '.');

		return $aFormat;

	}

	/**
	 * @return mixed|string
	 */
	public function getCurrency() {
		return (int)$this->currency;
	}

	/**
	 * Liefert alle Währungen der Schule
	 *
	 * @param bool $bAsObject
	 * @return array
	 */
	public function getCurrencies($bAsObject = false) {

		$aBack = array();
		$aResult = (array)json_decode($this->currencies);

		foreach($aResult as $iId) {
			if($bAsObject) {
				$aBack[] = Ext_Thebing_Currency::getInstance($iId);
			} else {
				$aBack[] = Ext_Thebing_Data_Currency::getCurrencyData($iId);
			}
		}

		return $aBack;

	}

	/**
	 * @return mixed|string
	 */
	public function getTransferCurrency() {
		return $this->currency_transfer;
	}

	/**
	 * @return mixed|string
	 */
	public function getTeacherCurrency() {
		return $this->currency_teacher;
	}

	/**
	 * @return mixed|string
	 */
	public function getAccommodationCurrency() {
		return $this->currency_accommodation;
	}

	/**
	 * @param bool $bIso
	 * @return array
	 */
	public function getSchoolCurrencyList($bIso = false) {

		$aBack = array();
		$aResult = (array)json_decode($this->currencies);

		foreach($aResult as $iId) {

			$oCurrency = Ext_Thebing_Currency::getInstance($iId);

			if(!$bIso) {
				$mData = $oCurrency->getSign();
			} else {
				$mData = $oCurrency->getIso();
			}

			$aBack[$oCurrency->id] = $mData;

		}

		return $aBack;

	}

	protected function _setLanguage() {

		global $_VARS;

		$this->sLanguage = $this->language;

		if(
			isset($_VARS['sLanguage']) &&
			$_VARS['sLanguage']
		) {
			$this->sLanguage = $_VARS['sLanguage'];
		}

		if($this->sLanguage == '') {
			$this->sLanguage = 'en';
		}

		$_VARS['sLanguage'] = $this->sLanguage;

	}

	/**
	 * Gibt die Frontend-Sprache zurück die der Loginsprache (Backend) am ehesten entspricht.
	 *
	 * @param string $sLanguage
	 * @return string
	 */
	public function getInterfaceLanguage($sLanguage = null) {

		// Über Parameter oder Globals
		if($sLanguage !== null) {
			$sSystemLanguage = $sLanguage;
		} else {
			$sSystemLanguage = System::d('systemlanguage');

			// Mit $system_data war das früher null; array_key_exists() erzeugt Warning bei false
			// @TODO Ist es richtig, dass es den Wert (im Frontend) nicht gibt?
			if($sSystemLanguage === false) {
				$sSystemLanguage = null;
			}
		}

		if(Ext_Thebing_System::isAllSchools()) {
			$oConfig = Ext_TS_Config::getInstance();
			$aLanguages = (array)$oConfig->frontend_languages;
			$aSchoolLanguages = [];
			foreach($aLanguages as $sLanguage) {
				$aSchoolLanguages[$sLanguage] = $sLanguage;
				$aSchoolLanguages[substr($sLanguage, 0, 2)] = $sLanguage;
			}
		}

		// Der Wert wird eigentlich durch die Thebing-Backend gesetzt
		// Im Frontend oder durch WDMVC wird dies aber nicht aufgerufen!
		if(empty($aSchoolLanguages)) {

			$aSchoolLanguages = $this->getLanguageList();

			// ISO Codes auf Sprache reduzieren
			foreach($aSchoolLanguages as $sIso=>$sLocale) {
				$aSchoolLanguages[substr($sIso, 0, 2)] = $sLocale;
			}

		}

		if(array_key_exists($sSystemLanguage, $aSchoolLanguages)) {
			$sLanguage = $aSchoolLanguages[$sSystemLanguage];
		} else {
			$sLanguage = $this->getLanguage();
		}

		return $sLanguage;
	}

	/**
	 * @return string
	 */
	public static function fetchInterfaceLanguage() {

		$oSchool = self::getSchoolFromSessionOrFirstSchool();

		// Fallback für neue Installation ohne Schulen
		if(!$oSchool) {
			$aLanguages = (array)Ext_TS_Config::getInstance()->frontend_languages;
			return reset($aLanguages);
		}

		$sLanguage = $oSchool->getInterfaceLanguage();

		return (string)$sLanguage;
	}

	/**
	 * Gibt die Standardsprache der Schule zurück
	 *
	 * @return string language code
	 * @throws Exception
	 */
	public function getLanguage() {

		if($this->language == "") {
			$this->language = 'en';
		}

		return $this->language;
	}

	/**
	 * @param string $sField
	 * @return mixed|string
	 * @throws ErrorException
	 */
	public function __get($sField) {

		// Da ALLES an der Schule hängt, ist das hier nicht gut
		//Ext_Gui2_Index_Registry::set($this);

		switch($sField) {
			case 'languages':
				if(!is_array($this->_aData['languages'])) {
					$aLanguages = (array)json_decode($this->_aData['languages']);
					return $aLanguages;
				}
				return $this->_aData['languages'];
			case 'aExclusivePDFs':
				if(!is_array($this->_aData['tax_exclusive'])) {
					$aReturn = (array)json_decode($this->_aData['tax_exclusive']);
					return $aReturn;
				}
				return $this->_aData['tax_exclusive'];
			case 'aCurrencies':
				if(!is_array($this->_aData['currencies'])) {
					$aReturn = (array)json_decode($this->_aData['currencies']);
					return $aReturn;
				}
				return $this->_aData['currencies'];
			case 'name':
				return $this->ext_1;
			case 'logo':
				$sLogo = $this->getLogo();
				if(empty($sLogo)) {
					$sLogo = '';
				} else {
					$sLogo = 'logo.png';
				}
				return $sLogo;
			case 'class_time_from':
			case 'class_time_until':
			case 'class_time_interval':
				$sKey = str_replace('class_time_', '', $sField);
				$aClassTimes = $this->getClassTimes();
				$oClassTime = reset($aClassTimes);
				$sValue = $oClassTime->$sKey;
				if(
					$sKey === 'from' ||
					$sKey === 'until'
				) {
					$sValue = substr($sValue, 0, 5);
				}
				return $sValue;
			default:

				if (str_starts_with($sField, 'signature_')) {
					return $this->getSignatureValue($sField);
				}

				// Ext_TC_Basic wegen Registry überspringen
				return WDBasic::__get($sField);
		}

	}

	/**
	 * @param string $sField
	 * @param mixed $mValue
	 */
	public function __set($sField, $mValue) {

		switch($sField) {
			case 'languages':
				if(is_array($mValue)) {
					$this->_aData['languages'] = json_encode($mValue);
				}
				break;
			case 'aExclusivePDFs':
				if(is_array($mValue)) {
					$this->_aData['tax_exclusive'] = json_encode($mValue);
				}
				break;
			case 'aCurrencies':
				if(is_array($mValue)) {
					$this->_aData['currencies'] = json_encode($mValue);
				}
				break;
			case 'logo':
				break;
			case 'client':
				$this->setClient($mValue);
				break;
			case 'class_time_from':
			case 'class_time_until':
			case 'class_time_interval':
				$sKey = str_replace('class_time_', '', $sField);
				$aClassTimes = $this->getJoinedObjectChilds('class_times', true);
				if(empty($aClassTimes)) {
					$aClassTimes = [$this->getJoinedObjectChild('class_times')];
				}
				$oClassTime = reset($aClassTimes);
				$oClassTime->$sKey = $mValue;
				break;
			default:

				if (str_starts_with($sField, 'signature_')) {
					$this->setSignatureValue($sField, $mValue);
				} else {
					parent::__set($sField, $mValue);
				}

		}

	}

	/**
	 * @param bool $bDocumentRoot
	 * @return string
	 */
	public function getLogo($bDocumentRoot = false) {

		$sDocumentRoot = Util::getDocumentRoot(false);
		if($bDocumentRoot) {
			$sDocumentRoot = '';
		}

		$sLogo = $this->getSchoolFileDir($bDocumentRoot).'/logo.png';
		if(is_file($sDocumentRoot.$sLogo)) {
			return $sLogo;
		}

		return '';

	}

	/**
	 * get the Field Value of the School
	 */
	public function getField($sField) {
		return $this->_aData[$sField];
	}

	/**
	 * Get grown age (Erwachsenenalter)
	 *
	 * @return int
	 */
	public function getGrownAge() {

		if((int)$this->adult_age > 0) {
			return (int)$this->adult_age;
		}

		return 18;

	}

	/**
	 * @param bool $bLabels
	 * @return mixed
	 */
	public function getLanguageList($bLabels=false) {
		return $this->_getLanguageList($bLabels);
	}

	/**
	 * @see \Ext_TC_SubObject::getLanguages()
	 *
	 * @return string[]
	 */
	public function getLanguages() {
		return $this->_getLanguageList(false);
	}

	/**
	 * @return Ext_TS_Inquiry[]
	 */
	public function getPendingConfirmationInquirys() {

		$sSql = "
			SELECT
				`ts_i`.`id`
			FROM
				`ts_inquiries` `ts_i`
			INNER JOIN
				`ts_inquiries_journeys` `ts_i_j`
			ON
				`ts_i_j`.`inquiry_id` = `ts_i`.`id` AND
				`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
				`ts_i_j`.`active` = 1 AND
				`ts_i_j`.`school_id` = :school_id
			WHERE
				`ts_i`.`active` = 1 AND
				`ts_i`.`type` & ".Ext_TS_Inquiry::TYPE_BOOKING." AND
				`ts_i`.`confirmed` = 0
		";

		$aResult = DB::getPreparedQueryData($sSql, [
			'school_id' => (int)$this->id
		]);

		$aBack = array();
		foreach($aResult as $aInquiry) {
			$aBack[] = new Ext_TS_Inquiry($aInquiry['id']);
		}

		return $aBack;

	}

	/**
	 * @deprecated
	 * @param bool $bForSelect
	 * @return array
	 */
	public function getPaymentMethodList($bForSelect = false) {

		return Ext_Thebing_Admin_Payment::getPaymentMethods($bForSelect, [$this->id]);

	}

	/**
	 * Funktion liefert die über das Konto verknüpfte Währung zu einer Bezahlmethode
	 *
	 * @param int $iMethodId
	 * @return array|mixed
	 * @throws Exception
	 */
	public static function getCurrencyOfPaymentMethod($iMethodId) {

		$oPaymentMethod = Ext_Thebing_Admin_Payment::getInstance($iMethodId);
		$iCurrencyId = $oPaymentMethod->currency_id;

		return $iCurrencyId;

	}

	/**
	 * @return array
	 */
	public static function getLangList() {
		$oSchool = self::getSchoolFromSession();
		return $oSchool->getLanguageList();
	}

	/**
	 * Liste mit allen Saisons
	 *
	 * @param boolean $bForSelects
	 * @param boolean $bPriceSaisons
	 * @param boolean $bTeacherSaisons
	 * @param boolean $bTransferSaisons
	 * @param boolean $bAccommodationSaisons
	 * @param boolean $bFixcostSaisons
	 * @param boolean $bInsurancesSaisons
	 * @return mixed[]
	 */
	public function getSaisonList(
		$bForSelects = true,
		$bPriceSaisons = true,
		$bTeacherSaisons = false,
		$bTransferSaisons = false,
		$bAccommodationSaisons = false,
		$bFixcostSaisons = false,
		$bInsurancesSaisons = false,
		$bActivitiesSaisons = false
	) {

		$sInterfaceLanguage = $this->getInterfaceLanguage();
		$aSqlAddon = array();

		if($bInsurancesSaisons) {
			$aSqlAddon[] = " saison_for_insurance = 1 ";
		}
		if($bPriceSaisons) {
			$aSqlAddon[] = " saison_for_price = 1 ";
		}
		if($bTeacherSaisons) {
			$aSqlAddon[] = " saison_for_teachercost = 1 ";
		}
		if($bTransferSaisons) {
			$aSqlAddon[] = " saison_for_transfercost = 1 ";
		}
		if($bAccommodationSaisons) {
			$aSqlAddon[] = " saison_for_accommodationcost = 1 ";
		}
		if($bFixcostSaisons) {
			$aSqlAddon[] = " saison_for_fixcost = 1 ";
		}
		if($bActivitiesSaisons) {
			$aSqlAddon[] = " season_for_activity = 1 ";
		}

		$sSqlAddon = '';

		if(count($aSqlAddon) > 0) {
			$sSqlAddon = " ( " . implode(" OR ", $aSqlAddon) . " ) AND ";
		}

		$sSql = "
			SELECT 
				*,
				UNIX_TIMESTAMP(valid_from) as valid_from,
				UNIX_TIMESTAMP(valid_until) as valid_until 
			FROM 
				`kolumbus_periods`
			WHERE
				".$sSqlAddon."
				`active` = 1 AND
				`idPartnerschool` = :idSchool
			ORDER BY
				`valid_from`
		";
		$aSql = [
			'idSchool' => (int)$this->id,
		];
		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		if($bForSelects == true) {
			$aSelect = array();
			foreach($aResult as $aValue){
				$aSelect[$aValue['id']] = $aValue['title_'.$sInterfaceLanguage]." (".Ext_Thebing_Format::LocalDate($aValue['valid_from'])." - ".Ext_Thebing_Format::LocalDate($aValue['valid_until']).")";
			}
			return $aSelect;
		}

		return $aResult;

	}

	/**
	 * Liefert alle Saisons
	 *
	 * @param boolean $bForSelects
	 * @param boolean $bPriceSaisons
	 * @param boolean $bTeacherSaisons
	 * @param boolean $bTransferSaisons
	 * @param boolean $bAccommodationSaisons
	 * @param boolean $bFixcostSaisons
	 * @return mixed[]
	 */
	public static function getAllSaisons(
		$bForSelects = true,
		$bPriceSaisons = true,
		$bTeacherSaisons = false,
		$bTransferSaisons = false,
		$bAccommodationSaisons = false,
		$bFixcostSaisons = false
	) {

		$aSqlAddon = array();

		if($bPriceSaisons) {
			$aSqlAddon[] = " `p`.saison_for_price = 1 ";
		}
		if($bTeacherSaisons) {
			$aSqlAddon[] = " `p`.saison_for_teachercost = 1 ";
		}
		if($bTransferSaisons) {
			$aSqlAddon[] = " `p`.saison_for_transfercost = 1 ";
		}
		if($bAccommodationSaisons) {
			$aSqlAddon[] = " `p`.saison_for_accommodationcost = 1 ";
		}
		if($bFixcostSaisons) {
			$aSqlAddon[] = " `p`.saison_for_fixcost = 1 ";
		}

		$sSqlAddon = '';

		if(count($aSqlAddon) > 0) {
			$sSqlAddon = " ( " . implode(" OR ", $aSqlAddon) . " ) AND ";
		}

		$sLanguageName = 'title_en';//.$system_data['systemlanguage'];

		$sSql = "
			SELECT 
				`p`.*,
				UNIX_TIMESTAMP(`p`.valid_from) as valid_from,
				UNIX_TIMESTAMP(`p`.valid_until) as valid_until 
			FROM 
				#table `p`,
				#table2 `s` 
			WHERE
				".$sSqlAddon."
				`p`.`active` = 1 AND
				`s`.`id` = `p`.`idPartnerschool`AND
				`s`.`active` = 1 AND
				`p`.`idPartnerschool` > 0
			ORDER BY
				s.ext_1,
				p.#language_name
		";

		$aSql = [
			'table' => 'kolumbus_periods',
			'table2' => 'customer_db_2',
			'language_name' => $sLanguageName
		];
		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		if($bForSelects == true){
			$aSelect = array();
			foreach($aResult as $aValue){
				if($aValue['idPartnerschool'] <= 0) {
					continue;
				}
				$oSchool = Ext_Thebing_School::getInstance($aValue['idPartnerschool']);
				if($oSchool->getField('id') <= 0) {
					continue;
				}
				$aSelect[$aValue['id']] = $oSchool->ext_1 . " | ".$aValue[$sLanguageName]." (".Ext_Thebing_Format::LocalDate($aValue['valid_from'],$aValue['idSchool'])." - ".Ext_Thebing_Format::LocalDate($aValue['valid_until'],$aValue['idSchool']).")";
			}
			return $aSelect;
		}

		return $aResult;

	}

	/**
	 * Liefert die aktuelle Saison
	 *
	 * @param boolean $bForSelects
	 * @param boolean $bPriceSaisons
	 * @param boolean $bTeacherSaisons
	 * @param boolean $bTransferSaisons
	 * @param boolean $bAccommodationSaisons
	 * @param boolean $bFixcostSaisons
	 * @param bool $bInsurancesSaisons
	 * @return mixed[]
	 */
	public function getCurrentSaison(
		$bForSelects = true,
		$bPriceSaisons = true,
		$bTeacherSaisons = false,
		$bTransferSaisons = false,
		$bAccommodationSaisons = false,
		$bFixcostSaisons = false,
		$bInsurancesSaisons = false
	) {

		$iTimestamp = time();

		$aSqlAddon = array();
		if($bPriceSaisons) {
			$aSqlAddon[] = " saison_for_price = 1 ";
		}
		if($bTeacherSaisons) {
			$aSqlAddon[] = " saison_for_teachercost = 1 ";
		}
		if($bTransferSaisons) {
			$aSqlAddon[] = " saison_for_transfercost = 1 ";
		}
		if($bAccommodationSaisons) {
			$aSqlAddon[] = " saison_for_accommodationcost = 1 ";
		}
		if($bFixcostSaisons) {
			$aSqlAddon[] = " saison_for_fixcost = 1 ";
		}
		if($bInsurancesSaisons) {
			$aSqlAddon[] = " saison_for_insurance = 1 ";
		}

		$sSqlAddon = '';

		if(count($aSqlAddon) > 0) {
			$sSqlAddon = " ( " . implode(" OR ", $aSqlAddon) . " ) AND ";
		}

		$sSql = "
			SELECT 
				* 
			FROM 
				#table 
			WHERE
				".$sSqlAddon."	
				`active` = 1 AND
				`idPartnerschool` = :idSchool AND
				".(int)$iTimestamp." BETWEEN UNIX_TIMESTAMP(`valid_from`) AND UNIX_TIMESTAMP(`valid_until`)
		";
		$aSql = [
			'table' => 'kolumbus_periods',
			'idSchool' => $this->id,
		];
		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		if(count($aResult) <= 0) {
			$sSql = "
				SELECT 
					*
				FROM 
					#table
				WHERE
					`active` = 1
				AND
					`idPartnerschool` = :idSchool
				LIMIT
					1
			";
			$aResult = DB::getPreparedQueryData($sSql, $aSql);
		}

		if($bForSelects == true) {
			$aSelect = array();
			$aValue = $aResult[0];
			$aSelect[$aValue['id']] = $aValue['title_en'];
			return $aSelect;
		}

		return $aResult[0];

	}

	/**
	 * Liste mit Course Units
	 *
	 * @param bool $bForSelects
	 * @return mixed[]|Ext_Thebing_School_TeachingUnit[]
	 */
	public function getCourseUnitList($bForSelects = false) {

		$aCourseUnits = Ext_Thebing_School_TeachingUnit::getListBySchool($this);

		if($bForSelects !== true) {
			$aBack = [];
			foreach($aCourseUnits as $oCourseUnit) {
				$aBack[$oCourseUnit->position] = $oCourseUnit->getData();
			}

			return $aBack;
		}

		return array_map(
			function(Ext_Thebing_School_TeachingUnit $oCourseUnit) {
				return $oCourseUnit->title;
			},
			$aCourseUnits
		);

	}

	/**
	 * @TODO was kann $oCourse alles sein? Das Kurs-Objekt hat kein getCourseUnitList()?
	 * @param integer $iPosition
	 * @param mixed $oCourse
	 * @param integer $_trash_iAccommocation
	 * @return mixed
	 */
	public function getCourseUnitByPosition($iPosition, $oCourse = null, $_trash_iAccommocation = 0) {

		if($oCourse == null) {
			$aCourseUnits = $this->getCourseUnitList();
		} else {
			$aCourseUnits = $oCourse->getCourseUnitList(false, false);
		}

		$aLastCourseUnit = end($aCourseUnits);

		if(empty($aCourseUnits[$iPosition])) {

			$aCourseUnits[$iPosition] = $aLastCourseUnit;
			$aExtraCourseUnit = $oCourse->getCourseUnitList(false, 2);
			$aExtraCourseUnit = end($aExtraCourseUnit);
			$aCourseUnits[$iPosition]['extraWeek'] = $aExtraCourseUnit;

			// Fehler da Berechnung so nicht klappen kann korrekt
			if($aExtraCourseUnit['start_unit'] > $iPosition) {
				$aCourseUnits[$iPosition]['error'] = array('extraunit_start_gt_position' => $oCourse->getCourseId());
			}

			// Fehler da Berechnung so nicht klappen kann korrekt
			if(empty($aExtraCourseUnit)) {
				$aCourseUnits[$iPosition]['error'] = array('wrong_unit_number' => $oCourse->getCourseId());
			}

		}

		return $aCourseUnits[$iPosition];

	}

	/**
	 * @TODO Wird nur in der Preisberechnung an einer einzigen Stelle benutzt
	 *
	 * @TODO was kann $oCourse alles sein?
	 * @param integer $iNumber
	 * @param mixed $oCourse
	 * @return mixed
	 */
	public function getCourseUnitByNumberOfCourseUnits($iNumber, $oCourse = null) {

		$sWhereAddon = '';

		if($oCourse != null) {
			$aCourseUnitIds = $oCourse->getField('units');
			$sTemp = " AND (";
			$sWhereAddon = "";
			$i = 1;
			foreach((array)$aCourseUnitIds as $iId){
				$sWhereAddon .= $sTemp." `kcou`.`id` = '".$iId."'";
				$sTemp = " OR ";
				if(count($aCourseUnitIds) == $i){
					$sWhereAddon .= " ) ";
				}
				$i++;
			}
		}

		$sSql = "
			SELECT 
				`kcou`.*
			FROM 
				`kolumbus_courseunits` `kcou`
			INNER JOIN
				`ts_courseunits_schools` `ts_cs`
			ON
				`ts_cs`.`courseunit_id` = `kcou`.`id` AND
				`ts_cs`.`school_id` = :idSchool
			WHERE
				`kcou`.`active` = 1 AND
				:number BETWEEN `kcou`.`start_unit` AND (`kcou`.`start_unit`+`kcou`.`unit_count`)
				".$sWhereAddon."
			GROUP BY
				`kcou`.`id`
			ORDER BY
				`kcou`.`position` ASC,
				`kcou`.`id` ASC
		";
		$aSql = [
			'idSchool' => (int)$this->id,
			'number' => $iNumber,
		];
		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		if ($oCourse->getField('per_unit') == 2) {
			$aResult = \Ext_Thebing_Tuition_Course::getExamFakeUnitResource();
		}

		// Wenn mehrere passende gefunden wurden, wird der genommen dessen Start == der Anzahl der Lektionen entspricht
		if(count($aResult) > 1) {
			foreach((array)$aResult as $aData) {
				if($aData['start_unit'] == $iNumber) {
					$aResult = array();
					$aResult[] = $aData;
					break;
				}
			}
		}

		if(count($aResult) <= 0) {
			// Wenn keine Wochenstruktur gefunden werden konnte
			$aExtraCourseUnit = $oCourse->getCourseUnitList(false, 2);

			// größt mögliche Struktur ermitteln
			if(count($aExtraCourseUnit) > 0) {

				$aTempExtraCourseUnit = reset($aExtraCourseUnit);
				foreach((array)$aExtraCourseUnit as $aData) {
					if($aData['start_unit'] > $aTempExtraCourseUnit['start_unit']) {
						$aTempExtraCourseUnit = $aData;
					}
				}

				$aExtraCourseUnit = $aTempExtraCourseUnit;

				// Warnung
				$aResult[0] = $aExtraCourseUnit;
				$aResult[0]['extraWeek'] = $aExtraCourseUnit;

			} else {

				$aResult[0]['error'] = array('wrong_unit_number' => $oCourse->getCourseId());

			}

		}

		return $aResult[0];

	}

	/**
	 * @param boolean $bForSelects
	 * @return mixed[]
	 */
	public function getWeekList($bForSelects = false) {

		$sSql = "
			SELECT
				`kw`.*
			FROM 
				`kolumbus_weeks` `kw`
			INNER JOIN
				`ts_weeks_schools` `ts_ws`
			ON
				`ts_ws`.`week_id` = `kw`.`id` AND
				`ts_ws`.`school_id` = :idSchool
			WHERE
				`kw`.`active` = 1
			GROUP BY
				`kw`.`id`
			ORDER BY
				`kw`.`position` ASC,
				`kw`.`id` ASC
		";
		$aSql = [
			'idSchool' => (int)$this->id,
		];
		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		foreach((array)$aResult as $aWeek) {
			if($bForSelects == false) {
				$aBack[$aWeek['position']] = $aWeek;
			} else {
				$aBack[$aWeek['id']] = $aWeek['title'];
			}
		}

		return $aBack;

	}

	/**
	 * @param int $iPosition
	 * @param Ext_Thebing_Course_Util $oCourse
	 * @param int $_trash_iAccommocation
	 * @return mixed[]
	 */
	public function getWeekByPosition($iPosition, $oCourse = null, $_trash_iAccommocation = 0) {

		if($oCourse == null){
			$aWeeks = (array)$this->getWeekList();
		} else {
			$aWeeks = (array)$oCourse->getCourseWeekList(false, false, 'start_week');
		}

		// Wenn Woche nicht gefunden werden kann
		if(!isset($aWeeks[$iPosition])) {

			// Alle Wochen rausfiltern, die größer als die aktuelle sind
			foreach($aWeeks as $iWeek=>$aWeek) {
				if($aWeek['start_week'] > $iPosition) {
					unset($aWeeks[$iWeek]);
				}
			}

			$aLastWeek = end($aWeeks);

			$aWeeks[$iPosition] = $aLastWeek;
			$aExtraWeek = $oCourse->getCourseWeekList(false,2);
			$aExtraWeek = end($aExtraWeek);
			$aWeeks[$iPosition]['extraWeek'] = $aExtraWeek;

			// Fehler da Berechnung so nicht korrekt klappen kann
			if($aExtraWeek['start_week'] > $iPosition) {
				$aWeeks[$iPosition]['error'] = array('extraweek_start_gt_position' => $oCourse->getCourseId());
			}

			// Fehler da Berechnung so nicht klappen kann korrekt
			if(empty($aExtraWeek)) {
				$aWeeks[$iPosition]['error'] = array('wrong_week_number' => $oCourse->getCourseId());
			}

		}

		return $aWeeks[$iPosition];

	}

	/**
	 * Die Funktion müsste gcached werden!
	 * Aber: $oCourse ist das Util_Course obj. und kann nicht anhand dessen ID
	 * gecached werden, man müsste an die inquiry_course_id über das obj kommen zum cachen!!!
	 *
	 * @param int $iNumber
	 * @param mixed $oCourse
	 * @return mixed[]
	 */
	public function getWeekByNumberOfWeeks($iNumber, $oCourse = null) {

		$sWhereAddon = '';
		if($oCourse != null) {

			// Alle Preiswochen des Kurses holen und im Query abfragen
			$aWeekIds = $oCourse->getField('weeks');

			$sTemp = " AND (";
			$i = 1;
			foreach((array)$aWeekIds as $iId) {
				$sWhereAddon .= $sTemp." `kw`.`id` = ".(int)$iId."";
				$sTemp = " OR ";
				if(count($aWeekIds) == $i){
					$sWhereAddon .= " ) ";
				}
				$i++;
			}

		}

		$sSql = "
			SELECT 
				`kw`.* 
			FROM 
				`kolumbus_weeks` `kw` INNER JOIN
				`ts_weeks_schools` `ts_ws` ON
					`ts_ws`.`week_id` = `kw`.`id` AND
					`ts_ws`.`school_id` = :idSchool
			WHERE
				`kw`.`active` = 1 AND
				:number BETWEEN `kw`.`start_week` AND (`kw`.`start_week`+IF(`kw`.`week_count`=0,1,`kw`.`week_count`)-1) AND
				`kw`.`extra` = 0
				".$sWhereAddon."
			GROUP BY
				`kw`.`id`
			ORDER BY
				`kw`.`position` ASC,
				`kw`.`id` ASC
		";
		$aSql = [
			'idSchool' => (int)$this->id,
			'number' => (int)$iNumber,
		];

		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		// Wenn keine Woche gefunden wurde, wird immer die extrawoche genommen
		if(count($aResult) <= 0) {
			$aExtraWeek = $oCourse->getCourseWeekList(false, 2);
			$aExtraWeek = end($aExtraWeek);

			$aResult[0] = $aExtraWeek;
			$aResult[0]['extraWeek'] = $aExtraWeek;
			// Fehler da Berechnung so nicht klappen kann korrekt, Fall es keine Extrawoche ist
			if($aExtraWeek['extra'] != 1) {
				$aResult[0]['error'] = array('wrong_week_number' => $oCourse->getCourseId());
			}

		}

		return $aResult[0];

	}

	/**
	 * @param int $iPosition
	 * @param mixed $oAccommodation
	 * @return mixed[]
	 */
	public function getWeekByPositionForAccommodation($iPosition, $oAccommodation = null) {

		if($oAccommodation == null) {
			$aWeeks = $this->getWeekList();
		} else {
			$aWeeks = $oAccommodation->getAccommodationWeekList(false, false);
		}

		if(empty($aWeeks[$iPosition])) {

			$aLastWeek = end($aWeeks);

			$oPrice = new Ext_Thebing_Price($this);
			$aWeeks[$iPosition] = $aLastWeek;
			$aWeeks[$iPosition]['extraWeek'] = $oPrice->getExtraWeek($oAccommodation->getCategoryId());

		}

		return $aWeeks[$iPosition];

	}

	/**
	 * @param int $iNumber
	 * @param mixed $oAccommodation
	 * @return mixed[]
	 */
	public function getWeekByNumberOfWeeksForAccommodation($iNumber, Ext_Thebing_Accommodation_Util $oAccommodation = null){

		$sWhereAddon = "";
		if($oAccommodation != null) {

			$accommodationCategorySetting = $oAccommodation->getAccommodationCategory()->getSetting($this);

			$aWeekIds = $accommodationCategorySetting->weeks;

			$sTemp = " AND (";
			$i = 1;
			foreach((array)$aWeekIds as $iId){
				$sWhereAddon .= $sTemp." `kw`.`id` = '".$iId."'";
				$sTemp = " OR ";
				if(count($aWeekIds) == $i){
					$sWhereAddon .= " ) ";
				}
				$i++;
			}
		}

		$sSql = "
			SELECT 
				`kw`.* 
			FROM 
				`kolumbus_weeks` `kw` INNER JOIN
				`ts_weeks_schools` `ts_ws` ON
					`ts_ws`.`week_id` = `kw`.`id` AND
					`ts_ws`.`school_id` = :idSchool
			WHERE
				`kw`.`active` = 1 AND
				:number BETWEEN `kw`.`start_week` AND (`kw`.`start_week`+`kw`.`week_count`-1) AND
				`kw`.`extra` = 0
				".$sWhereAddon."
			GROUP BY
				`kw`.`id`
			ORDER BY
				`kw`.`position` ASC,
				`kw`.`id` ASC
		";
		$aSql = [
			'idSchool' => (int)$this->id,
			'number' => (int)$iNumber,
		];

		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		if(count($aResult) <= 0){
			$oPrice = new Ext_Thebing_Price($this);
			$aResult[0] = $oPrice->getExtraWeek($oAccommodation->getCategoryId());
			$aResult[0]['extraWeek'] = $aResult[0];
		}

		return $aResult[0];

	}

	/**
	 * @param bool $bWithDocumentRoot
	 * @param bool $bWithStorageDir
	 * @return string
	 */
	public function getSchoolFileDir($bWithDocumentRoot = true, $bWithStorageDir = true) {

		$sDir = '';

		if($bWithDocumentRoot) {
			$sDir = Util::getDocumentRoot();
			$sDir = rtrim($sDir, '/');
		}

		if($bWithStorageDir) {
			$sDir = $sDir.'/storage';
		}

		$sDir = $sDir.'/clients';
		$sDir = $sDir.'/client_'.$this->getField('idClient');
		$sDir = $sDir.'/school_'.$this->getId();

		return $sDir;
	}

	/**
	 * Holt alle hochgeladenen Schulfiles eines Types und einer/oder aller Sprachen
	 *
	 * @param int $iType
	 * @param string $sLangTemp
	 * @param bool $bOnlySecurePath
	 * @param bool $bNoGlobalFiles
	 * @return array
	 */
	public function getSchoolFiles($iType, $sLangTemp = '', $bOnlySecurePath = false, $bNoGlobalFiles = false) {

		if($sLangTemp === null) {
			$aLanguages = null;
		} elseif($sLangTemp == '') {
			// alle Sprachen
			$aLanguages = $this->getLanguageList();
		} else {
			$aLanguages = array($sLangTemp);
		}

		if($aLanguages !== null && empty($aLanguages)) {
			return [];
		}

		$sKey = 'KEY_'.$this->id.'_'.$iType.'_'.(int)$bOnlySecurePath.'_'.(int)$bNoGlobalFiles.'_'.implode('-', (array)$aLanguages);

		// Caching
		if(!isset(self::$_aCache['school_files'][$sKey])) {

			$uploads = \Ext_Thebing_Upload_File::query()
				->select('tc_u.*')
				->join('tc_upload_objects as tc_uo', function ($join) {
					$join->on('tc_uo.upload_id', '=', 'tc_u.id')
						->where('tc_uo.object_id', $this->id);
				})
				->join('tc_upload_languages as tc_ul', function ($join) use ($aLanguages) {
					$join->on('tc_ul.upload_id', '=', 'tc_u.id');
					if($aLanguages !== null) {
						$join->whereIn('tc_ul.language_iso', \Illuminate\Support\Arr::wrap($aLanguages));
					}
				})
				->where('tc_u.category', $iType)
				->where('tc_u.filename', '!=', '')
				->where('tc_u.description', '!=', '')
				->get();

			$list = [];
			foreach($uploads as $upload){

				$file = Ext_Thebing_Upload_File::buildPath($upload->filename);

				if(
					// Bei lokalen Installationen sollen nicht gleich die Werte überall rausfliegen
					// Eigentlich sollte das is_file() rausfliegen, da das eben die ausgewählten Werte verfälschen kann (PDF Templates)
					Ext_TC_Util::isDevSystem() ||
					is_file($file)
				) {
					if($bOnlySecurePath){
						$file = '/uploads/'.$upload->filename;
					}

					$list[$upload->id] = [
						'id' => $upload->id,
						'object' => $upload,
						'path'  => $file,
						'file' => $upload->filename,
						'description' => $upload->description,
					];;
				}
			}

			self::$_aCache['school_files'][$sKey] = $list;
		}

		return self::$_aCache['school_files'][$sKey];

	}

	/**
	 * Liste aller Lehrer in einer Schule
	 * @param bool $bForSelect
	 * @return array
	 */
	public function getTeacherList($bForSelect = false, $dValidUntil = null) {

		$aTeachers = Ext_Thebing_Teacher::query()
			->select('kt.*')
			->join('ts_teachers_to_schools as ts_tts', 'ts_tts.teacher_id', '=', 'kt.id')
			->where('ts_tts.school_id', '=', $this->id)
			->onlyValid($dValidUntil)
			->get();

		if ($bForSelect) {
			$oFormat = new Ext_Gui2_View_Format_Name();
			return $aTeachers
				->mapWithKeys(function (Ext_Thebing_Teacher $oTeacher) use ($oFormat) {
					return [$oTeacher->id => $oFormat->formatByResult($oTeacher->getData())];
				})
				->sort()
				->toArray();
		}

		return $aTeachers->toArray();

	}

	/**
	 * @param string $sDateFrom
	 * @param string $sDateUntil
	 * @param bool $bByCalculatedInterval
	 * @return array
	 */
	public function getClasses($sDateFrom, $sDateUntil, $bByCalculatedInterval=true) {

		if($bByCalculatedInterval) {
			$sWherePart = " DATE_ADD(`ktcl`.`start_week` , INTERVAL `ktcl`.`weeks` week) >= :date_from ";
		} else {
			$sWherePart = " `ktb`.`week` >= :date_from ";
		}

		$sSql = "SELECT
					`ktcl`.*
				FROM
					`kolumbus_tuition_classes` `ktcl` INNER JOIN
					`kolumbus_tuition_blocks` `ktb` ON
						`ktb`.`class_id` = `ktcl`.`id` AND
						`ktb`.`active` = 1
				WHERE
					`ktcl`.`active` = 1 AND
					`ktcl`.`school_id` = :school_id  AND					
					(
						`ktcl`.`start_week` <= :date_until AND
						$sWherePart
					)
				GROUP BY
					`ktcl`.`id`
				ORDER BY
					`ktcl`.`name`
				";

		$aSql = array('school_id'=>$this->id, 'date_from' => $sDateFrom, 'date_until' => $sDateUntil);
		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		return $aResult;
	}

	protected function _getDataById($iId = 0)
	{

		$sSql = "SELECT 
							* 
						FROM 
							`customer_db_2` 
						WHERE
							`id` = :id
						LIMIT 1
						";
		if($iId > 0){
			$iSchoolId = $iId;
		}else {
			$iSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');
		}
		$aSql = array (
			'id' => (int)$iSchoolId
		);
		$aResult = DB :: getPreparedQueryData($sSql, $aSql);
		if ($aResult <= 0 || $iSchoolId == 0)
		{
			exit ('No matching School');
		}
		return $aResult[0];
	}

	/**
	 * Language Liste
	 *
	 * @param bool $bLabels
	 * @return string[]
	 */
	protected function _getLanguageList($bLabels=false) {

		if(empty($this->aLanguages[$bLabels])) {

			if($bLabels) {
				$oLocaleService = new Core\Service\LocaleService;
				$aLocales = $oLocaleService->getInstalledLocales();
			}

			$aResult = json_decode($this->getField('languages'), true);

			$aBack = [];
			foreach ((array)$aResult as $sValue) {
				if($bLabels) {
					$aBack[$sValue] = $aLocales[$sValue];
				} else {
					$aBack[$sValue] = $sValue;
				}
			}

			if(count($aBack) <= 0) {
				if($bLabels) {
					$aBack['en'] = $aLocales['en'];
				} else {
					$aBack['en'] = 'en';
				}
			}

			$this->aLanguages[$bLabels] = $aBack;

		}

		return $this->aLanguages[$bLabels];

	}

	/**
	 * Schul Key
	 * @param type $iSchoolId
	 * @return type
	 */
	public static function getSchoolKey($iSchoolId = 0) {

		if(empty($iSchoolId)) {
			$iSchoolId = (int)\Core\Handler\SessionHandler::getInstance()->get('sid');
		}

		$sSchoolMd5 = crc32('thebingschool' . (int)$iSchoolId . 'plani');
		$sSchoolMd5 = sprintf("%u", $sSchoolMd5);
		$sSchoolMd5 = "KEY-" . $sSchoolMd5;

		return (string)$sSchoolMd5;

	}

	public static function setSchoolKey($iSchoolId = 0) {

		if(empty($iSchoolId)) {
			$iSchoolId = (int)\Core\Handler\SessionHandler::getInstance()->get('sid');
		}

		$sSchoolKey = self::getSchoolKey((int)$iSchoolId);

		$sSql = "
			UPDATE
				`customer_db_2`
			SET
				`sMd5` = :sMd5
			WHERE
				`id` = :id AND
				`sMd5` = ''
			LIMIT 1";
		$aSql = array('sMd5' => $sSchoolKey, 'id' => (int)$iSchoolId);
		DB::executePreparedQuery($sSql, $aSql);

		return $sSchoolKey;

	}

	/**
	 * Booking Status
	 * @param int $iFrom
	 * @param int $iUntil
	 * @return array
	 */
	public function getBookingStats($iFrom, $iUntil) {

		$sSql = "
				SELECT 
					COUNT(DISTINCT `ts_i`.`id`) bookings,
					SUM(`ts_i_j_c`.`weeks`) weeks
				FROM
					`ts_inquiries` `ts_i` INNER JOIN
					`ts_inquiries_journeys` `ts_i_j` ON
						`ts_i_j`.`inquiry_id`= `ts_i`.`id` AND
						`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
						`ts_i_j`.`active` = 1 AND
						`ts_i_j`.`school_id` = :school_id INNER JOIN
					`ts_inquiries_journeys_courses` `ts_i_j_c` ON
						`ts_i_j_c`.`journey_id` = `ts_i_j`.`id` AND
						`ts_i_j_c`.`for_tuition` = 1 AND
						`ts_i_j_c`.`active` = 1 AND
						`ts_i_j_c`.`visible` = 1 AND
						`ts_i_j_c`.`calculate` = 1
				WHERE
					`ts_i`.`active` = 1 AND
					`ts_i`.`confirmed` != 0 AND
					`ts_i`.`canceled` = 0 AND
					`ts_i`.`created` BETWEEN :from AND :until 
				";
		$aSql = array();
		$aSql['school_id'] = $this->id;
		$aSql['from'] = date("YmdHis", $iFrom - 1);
		$aSql['until'] = date("YmdHis", $iUntil + 1);

		$aStats = DB::getPreparedQueryData($sSql, $aSql);

		return $aStats[0];

	}

	/**
	 * Studenten Status
	 * @param int $iFrom
	 * @param int $iUntil
	 * @return int
	 */
	public function getStudentStats($iFrom, $iUntil) {

		$sSql = "
			SELECT
				COUNT(DISTINCT `ts_i`.`id`) `students`
			FROM
				`ts_inquiries` `ts_i` INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`inquiry_id`= `ts_i`.`id` AND
					`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_ij`.`active` = 1 AND
					`ts_ij`.`school_id` = :school_id INNER JOIN
				`ts_inquiries_journeys_courses` `ts_ijc` ON
					`ts_ijc`.`journey_id` = `ts_ij`.`id` AND
					`ts_ijc`.`active` = 1 AND
					`ts_ijc`.`visible` = 1 AND
					`ts_ijc`.`for_tuition` = 1 AND
					`ts_ijc`.`from` < :until AND
					`ts_ijc`.`until` > :from
			WHERE
				`ts_i`.`active` = 1 AND
				`ts_i`.`canceled` = 0
				".Ext_Thebing_System::getWhereFilterStudentsByClientConfig('ts_i')."
		";

		$aSql = array();
		$aSql['school_id'] = $this->id;
		$aSql['from'] = date('YmdHis', $iFrom - 1);
		$aSql['until'] = date('YmdHis', $iUntil + 1);

		$aStats = DB::getPreparedQueryData($sSql, $aSql);

		return $aStats[0]['students'];

	}

	/**
	 * Materialbestellung
	 * @param type $bPrepareSelect
	 * @param type $bOnlyOrderable
	 * @return type
	 */
	public function getMaterialOrderItems($bPrepareSelect=0, $bOnlyOrderable=0) {

		$sWhere = "";
		if($bOnlyOrderable) {
			$sWhere = " AND `orderable` = 1";
		}

		$sSql = "
				SELECT
					*
				FROM
					`kolumbus_material_orders_items`
				WHERE
					`school_id` = :school_id AND
					`active` = 1
					".$sWhere."
				ORDER BY
					`name`
				";
		$aSql = array(
			'school_id'=> (int)$this->id
		);
		$aItems = DB::getPreparedQueryData($sSql, $aSql);

		if($bPrepareSelect) {
			$aReturn = array();
			foreach((array)$aItems as $aItem) {
				$aReturn[$aItem['id']] = $aItem['name'];
			}
			return $aReturn;
		} else {
			return $aItems;
		}
	}

	/**
	 * Unterkünfte der Schule.
	 *
	 * @param bool $bForSelect
	 * @return mixed[]|Ext_Thebing_Accommodation[]
	 */
	public function getAccommodationProvider($bForSelect = true) {

		$aAccommodationProviders = Ext_Thebing_Accommodation::getListBySchool($this);

		if(!$bForSelect) {
			return $aAccommodationProviders;
		}

		$aBack = [];
		foreach($aAccommodationProviders as $oAccommodationProvider) {
			$aBack[$oAccommodationProvider->id] = $oAccommodationProvider->getName();
		}
		return $aBack;

	}


	protected static $_aGetPositionOrderCache;

	/**
	 * Positionsreihenfolge der Positionen
	 * @return type
	 */
	public function getPositionOrder(){

		if(empty(self::$_aGetPositionOrderCache[$this->id])){

			$sSql = " 
				SELECT 
					* 
				FROM
					`kolumbus_positions_order`
				WHERE
					school_id = :school_id
				ORDER BY `position`
			";
			$aSql = array('school_id'=> (int)$this->id);
			$aResult = DB::getPreparedQueryData($sSql,$aSql);
			$aBack = array();
			foreach((array)$aResult as $aData){
				$aBack[] = $aData['position_key'];
			}

			/**
			 * Default Reihenfolge
			 */
			if(empty($aResult)){
				$aAllPositions	= Ext_Thebing_School_Positions::getAllPositions();
				$aBack			= array_keys($aAllPositions);
			}

			// immer am ende
			$aBack[] = 'extra_position';
			$aBack[] = 'payment_surcharge';
			$aBack[] = 'deposit';
			$aBack[] = 'deposit_credit';

			if(!in_array('insurance', $aBack)){
				$aBack[] = 'insurance';
			}

			if(!in_array('activity', $aBack)){
				$aBack[] = 'activity';
			}

			if(!in_array('special', $aBack)){
				$aBack[] = 'special';
			}

			if(!in_array('storno', $aBack)){
				$aBack[] = 'storno';
			}

			// Paketpreise immer vorne
			$aBack = array_merge(array('paket'), $aBack);

			self::$_aGetPositionOrderCache[$this->id] = $aBack;

		}

		return self::$_aGetPositionOrderCache[$this->id];
	}

	/**
	 * @param string $sType
	 * @return string
	 */
	public function getPositionTemplate(string $sType): string {

		$oTemplate = Ext_Thebing_School_Positions::getRepository()->findOneBy([
			'school_id' => $this->id,
			'position_key' => $sType
		]);

		if(empty($oTemplate)) {
			$aDefaultTemplates = Ext_Thebing_School_Positions::getAllPositions();
			$sTemplate = $aDefaultTemplates[$sType];
		} else {
			$sTemplate = $oTemplate->title;
		}

		return $sTemplate;
	}

	/**
	 * Steuern
	 * Welche Steuern gibt es?
	 * 0 => keine steuern
	 * 1 => inclusive
	 * 2 => exclusive
	 * @return int
	 */
	public function getTaxStatus() {
		return (int)$this->tax;
	}

	/**
	 * @return array
	 */
	public function getTaxExclusive() {
		if((int)$this->tax > 0) {
			return (array)json_decode($this->tax_exclusive, true);
		} else {
			return array();
		}
	}

	/**
	 * @see self::getCourses()
	 *
	 * diese Funktion ist viel zu unflexibel, wurde nur wegen Abwärtskompabilität behalten
	 * bitte nicht mehr benutzen sondern direkt mit getCourseListObject() arbeiten
	 * @deprecated
	 * @param bool $bForSelect
	 * @param bool $bOnlyLectionCourses
	 * @param bool $bWithoutCombineCourses
	 * @return array
	 */
	public function getCourseList($bForSelect = true, $bOnlyLectionCourses = false, $bWithoutCombineCourses=false, $bShort = false, $bForScheduling = true){

		$oCourseList = $this->getCourseListObject($bForSelect, false, $bShort);
		$oCourseList->bForScheduling = $bForScheduling;

		if(
			$bOnlyLectionCourses
		) {
			$oCourseList->iUnitCourses = 1;
		}

		if(
			$bWithoutCombineCourses
		) {
			$oCourseList->iCombinedCourses = 0;
		}

		$aCourses = $oCourseList->getList();

		return $aCourses;
	}

	/**
	 * @return Ext_Thebing_Tuition_Course[]
	 */
	public function getCourses() {
		return Ext_Thebing_Tuition_Course::getRepository()->getBySchool($this);
	}

	/**
	 * @TODO Redundanz mit getAccommodationCategoriesList()
	 *
	 * Liste mit Unterkunftskategorien, die der Schule zugewiesen sind.
	 *
	 * @param bool $bForSelect
	 * @param false|int $iType
	 * @return mixed[]|Ext_Thebing_Accommodation_Category[]
	 */
	public function getAccommodationList($bForSelect = true, $iType = false) {

		$aAccommodations = Ext_Thebing_Accommodation_Category::getListBySchool($this);

		$dToday = new DateTime();
		$dToday->setTime(0, 0, 0);
		$aAccommodations = array_filter(
			$aAccommodations,
			function(Ext_Thebing_Accommodation_Category $oAccommodation) use ($iType, $dToday) {
				if(
					$iType !== false &&
					$oAccommodation->type_id != $iType
				) {
					return false;
				}
				if($oAccommodation->valid_until != '0000-00-00') {
					$dValidUntil = new DateTime($oAccommodation->valid_until);
					$dValidUntil->setTime(0, 0, 0);
					if($dValidUntil < $dToday) {
						return false;
					}
				}
				return true;
			}
		);

		if(!$bForSelect) {
			return array_values($aAccommodations);
		}

		$sLanguage = $this->getInterfaceLanguage();

		return array_map(
			function(Ext_Thebing_Accommodation_Category $oAccommodation) use ($sLanguage) {
				return $oAccommodation->getName($sLanguage);
			},
			$aAccommodations
		);

	}

	/**
	 * @TODO Es gibt für dasselbe schon Methoden in der entsprechenden Klasse
	 *
	 * Das Array ist nach der Listensortierung ("position"-Feld) sortiert.
	 *
	 * @param bool $bForSelect
	 * @return mixed[]|Ext_Thebing_Accommodation_Meal[]
	 */
	public function getMealList($bForSelect = true) {

		$aMeals = Ext_Thebing_Accommodation_Meal::getMealTypesBySchool($this);

		if(!$bForSelect) {
			return array_values($aMeals);
		}

		$sLanguage = $this->getInterfaceLanguage();

		return array_map(
			function(Ext_Thebing_Accommodation_Meal $oMeal) use ($sLanguage) {
				return $oMeal->getName($sLanguage);
			},
			$aMeals
		);

	}

	/**
	 * @TODO Es gibt für dasselbe schon Methoden in der entsprechenden Klasse
	 *
	 * Das Array ist nach der Listensortierung ("position"-Feld) sortiert.
	 *
	 * @param bool $bForSelect
	 * @return mixed[]|Ext_Thebing_Accommodation_Roomtype[]
	 */
	public function getRoomtypeList($bForSelect = true) {

		$aMeals = Ext_Thebing_Accommodation_Roomtype::getRoomTypesBySchool($this);

		if(!$bForSelect) {
			return array_values($aMeals);
		}

		$sLanguage = $this->getInterfaceLanguage();

		return array_map(
			function(Ext_Thebing_Accommodation_Roomtype $oRoomtype) use ($sLanguage) {
				return $oRoomtype->getName($sLanguage);
			},
			$aMeals
		);

	}

	/**
	 * @return mixed[]
	 */
	public static function getDefaultSchool() {

		$sSql = "
			SELECT 
				`id`
			FROM 
				`customer_db_2`
			WHERE
				`active` = 1
			ORDER BY
				`created` ASC
		";
		$aSql = [];
		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		return $aResult[0];

	}

	public function getLevelList($bForSelect = false, $sDisplayLanguage = null, $sNiveau = 0, $bEmptyItem=true, $bUseShortNames=false) {

		if(empty($sDisplayLanguage)) {
			$sDisplayLanguage = $this->getInterfaceLanguage();
		}

		if(is_numeric($sNiveau)) {
			if($sNiveau == 0) {
				$sNiveau = 'normal';
			} else {
				$sNiveau = 'internal';
			}
		}

		$sSql = "
			SELECT 
				* 
			FROM 
				`ts_tuition_levels` `ts_tl` JOIN
				`ts_tuition_levels_to_schools` `ts_tlts` ON
					`ts_tl`.`id` = `ts_tlts`.`level_id`
			WHERE
				`ts_tl`.`active` = 1 AND
				`ts_tlts`.`school_id` = :school_id AND
				`ts_tl`.`type` = :type AND
				(
					`ts_tl`.`valid_until` IS NULL OR
					`ts_tl`.`valid_until` >= CURDATE()
				)
			ORDER BY 
				`ts_tl`.`position` ASC,
				`ts_tl`.`id` ASC
			";

		$aSql = array (
			'type' => $sNiveau,
			'school_id' => (int)$this->id
		);

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		$aBack = [];

		if($bEmptyItem == true) {
			$aBack[0] = " --- ";
		}

		foreach ($aResult as $aIntesy) {

			if($bForSelect == true){
				$sName = $aIntesy['name_'.$sDisplayLanguage];
				if($bUseShortNames && !empty($aIntesy['name_short'])){
					$sName = $aIntesy['name_short'];
				}

				if(empty($sName)) {
					$sName = 'Level #'.$aIntesy['id'];
				}

				$aBack[$aIntesy['id']] = $sName;
			} else {
				$aBack[$aIntesy['id']] = $aIntesy;
			}

		}

		return $aBack;
	}

	public function getCourseLevelList() {

		return $this->getLevelList(true, null, 'normal', false);
	}

	/**
	 * @param bool $bForSelect
	 * @param string|null $sValidUntil
	 * @param bool $bWithOtherSchoolRooms
	 * @param int|null $iFloorId
	 * @return array
	 */
	public function getClassRooms($bForSelect = false, $sValidUntil = null, $bWithOtherSchoolRooms = true, $iFloorId = null, $buildingIds=null, $bSortByFloor=false) {

		$aReturn = [];

		if($sValidUntil === null) {
			$sValidUntil = date('Y-m-d');
		}

		$sWhere = "";

		$aSql = [
			'school_id' => $this->id,
			'valid_until' => $sValidUntil,
			'floor_id' => $iFloorId
		];

		// Mit Räumen anderer Schulen
		if($bWithOtherSchoolRooms) {
			$sWhere .= " AND (
				`kc`.`idSchool` = :school_id OR
				`ts_scu`.`classroom_id` IS NOT NULL
			)
			";
		} else {
			$sWhere .= " AND `kc`.`idSchool` = :school_id ";
		}

		// Mit Etage
		if($iFloorId > 0) {
			$sWhere = " AND `kc`.`floor_id` = :floor_id ";
		}

		// Mit Gebäude
		if(!empty($buildingIds)) {
			$buildingIds = (array)$buildingIds;
			$aSql['buildings'] = $buildingIds;
			$sWhere = " AND `ksf`.`building_id` IN (:buildings) ";
		}

		$sSql = "
			SELECT
				`kc`.*
			FROM
				`kolumbus_classroom` `kc` LEFT JOIN
				`ts_schools_classrooms_usage` `ts_scu` ON
					`ts_scu`.`classroom_id` = `kc`.`id` AND
					`ts_scu`.`school_id` = :school_id LEFT JOIN
				`kolumbus_school_floors` `ksf` ON
					`kc`.`floor_id` = `ksf`.`id`
			WHERE
				`kc`.`active` = 1 AND (
					`kc`.`valid_until` >= :valid_until OR
					`kc`.`valid_until` = '0000-00-00'
				)
				{$sWhere}
			ORDER BY
				`kc`.`idSchool` != :school_id";

		if($bSortByFloor) {
			$sSql .= ", `ksf`.`title` ";
		}

		$sSql .= ", IF(`kc`.`idSchool` = :school_id, `kc`.`position`, `ts_scu`.`position`) ";

		$aResult = (array)DB::getQueryRows($sSql, $aSql);

		foreach($aResult as $aClassroom) {
			if($bForSelect) {
				$aReturn[$aClassroom['id']] = $aClassroom['name'];
			} else {
				$aReturn[$aClassroom['id']] = Ext_Thebing_Tuition_Classroom::getObjectFromArray($aClassroom);
			}
		}

		return $aReturn;
	}

	public function getTuitionTemplates($bPrepareForSelect=false, $bWithCustom=false, $oClass=null) {
		$aSql = array();

		$oDateTime = new \DateTime();
		$aSql['valid_until_value'] = $oDateTime->format('Y-m-d');

		$sWhereAddon = ' AND `ktt`.`custom` = 0 ';
		$sJoin = '';

		if($bWithCustom){
			$sWhereAddon = '';
		}

		if($oClass instanceof Ext_Thebing_Tuition_Class) {
			$sJoin = " 
				INNER JOIN `kolumbus_tuition_blocks` `ktb` ON
					`ktb`.`template_id` = `ktt`.`id` AND
					`ktb`.`class_id` = :class_id AND
					`ktb`.`active` = 1
			";

			$aSql['class_id'] = $oClass->id;
		}

		$sSql = "
			SELECT
				`ktt`.*
			FROM
				`kolumbus_tuition_templates` `ktt`
				".$sJoin."
			WHERE
				 `ktt`.`school_id` = :school_id AND
				 `ktt`.`active` = 1 AND
				 (`ktt`.`valid_until` = '0000-00-00' OR `ktt`.`valid_until` >= :valid_until_value)
				 " . $sWhereAddon . "
			ORDER BY
				`ktt`.`position`,
				`ktt`.`name`
		";

		$aSql['school_id'] = $this->_aData['id'];

		$aTemplates	= array();
		$aResult	= DB::getPreparedQueryData($sSql, $aSql);

		if(!$bPrepareForSelect){
			$aTemplates = $aResult;
		}else{
			foreach((array)$aResult as $aData){
				$aTemplates[$aData['id']] = $aData['name'];
			}
		}

		return $aTemplates;
	}

	public function getTeachersList($iWeek=false, $bPrepareForSelect=false) {

		$oDate			= new WDDate();
		$sValidDate		= $oDate->get(WDDate::DB_DATE);

		$aSql					= array();
		$aSql['school']			= (int)$this->_aData['id'];
		$aSql['valid_date']		= $sValidDate;

		if($iWeek) {

			$aWeek = Ext_Thebing_Util::getWeekTimestamps($iWeek);

			$sSql =  "
					SELECT 
						kt.*,
						COALESCE(SUM(
							DATEDIFF(
								IF(kab.until > :end, :end, kab.until),
								IF(kab.from < :start, :start, kab.from)
							) + 1
						), 0) `holidays`
					FROM
						`ts_teachers` kt JOIN
						`ts_teachers_to_schools` `ts_ts` ON
							kt.id = `ts_ts`.`teacher_id` LEFT OUTER JOIN
						`kolumbus_absence` kab ON
							kt.id = kab.item_id AND
							kab.item = 'teacher' AND
							kab.active = 1 AND
							kab.until >= :start AND
							kab.from <= :end
					WHERE
						 `ts_ts`.`school_id` = :school AND
						 `kt`.`active` = 1 AND
						 (
							`kt`.`valid_until` >= :valid_date OR
							`kt`.`valid_until` = '0000-00-00'
						 )
					GROUP BY
						kt.id
					HAVING
						 `holidays` < 7
					ORDER BY 
						kt.`lastname` ASC
						";
			$aSql['start'] = date('Y-m-d', $aWeek['start']);
			$aSql['end'] = date('Y-m-d', $aWeek['end']);

		} else {

			$sSql =  "
					SELECT 
						*
					FROM
						`ts_teachers` JOIN
						`ts_teachers_to_schools` `ts_ts` ON
							kt.id = `ts_ts`.`teacher_id`
					WHERE
						 `ts_ts`.`school_id = :school AND
						 `active` = 1 AND
						 (
							`valid_until` >= :valid_date OR
							`valid_until` = '0000-00-00'
						 )
					ORDER BY 
						`lastname` ASC
						";

		}

		$aResult = DB::getPreparedQueryData($sSql, $aSql);
		if(!$bPrepareForSelect) {
			$aItems = $aResult;
		} else {
			$oFormat	= new Ext_Thebing_Gui2_Format_TeacherName();
			$aItems		= array();

			foreach($aResult as $aRowData) {
				$aItems[$aRowData['id']] = $oFormat->format(null, $oDummy, $aRowData);
			}
		}

		return $aItems;
	}

	public function getTeacher($iTeacher) {

		$aSql = array();
		$aSql['school'] = $this->_aData['id'];
		$aSql['teacher'] = (int)$iTeacher;
		$sSql =  "
				SELECT 
					kt.*
				FROM
					`ts_teachers` kt JOIN
					`ts_teachers_to_schools` `ts_ts` ON
						kt.id = `ts_ts`.`teacher_id`
				WHERE
					 ts_ts.`school_id` = :school AND
					 kt.`id` = :teacher
				LIMIT 1
					";
		$aTeacher = DB::getPreparedQueryData($sSql, $aSql);

		return $aTeacher[0];
	}

	/**
	 * @TODO Redundanz mit getAccommodationList()
	 *
	 * Unterkunftskategorien holen
	 *
	 * @param bool $bForSelect
	 * @return mixed[]|Ext_Thebing_Accommodation_Category[]
	 */
	public function getAccommodationCategoriesList($bForSelect = false, $sLanguage = null) {

		if($bForSelect) {
			return Ext_Thebing_Accommodation_Category::getSelectOptions(false, $sLanguage, $this);
		}

		$aList = Ext_Thebing_Accommodation_Category::getListBySchool($this);
		return array_values($aList);

	}

	/*
	 * Kurskategorien holen
	 */
	public function getCourseCategoriesList($sReturnType=false, $sLanguage = null) {

		if ($sLanguage === null) {
			$sLanguage = $this->getInterfaceLanguage();
		}

		$sSql = "
			SELECT 
				`ts_tcc`.*
			FROM 
				`ts_tuition_coursecategories` `ts_tcc` JOIN
				`ts_tuition_coursecategories_to_schools` `ts_tccts` ON
					`ts_tcc`.`id` = `ts_tccts`.`category_id`
			WHERE
				`ts_tcc`.`active` = 1 AND
				`ts_tccts`.`school_id` = :school_id
			ORDER BY
				`ts_tcc`.`position`
			";

		$aSql = array (
			'school_id' => (int)$this->id
		);

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		$aBack = array();

		foreach((array)$aResult as $aData) {

			if($sReturnType=='select') {
				$aBack[$aData['id']] = $aData['name_'.$sLanguage];
			} elseif($sReturnType=='object'){
				$aBack[] = Ext_Thebing_Tuition_Course_Category::getObjectFromArray($aData);
			} else {
				$aBack[] = $aData;
			}
		}

		return $aBack;
	}

	public function getCombinationCoursePlaceholder() {

		//$oRepository = Ext_Thebing_Tuition_Course::getRepository();
		//$aCourses = $oRepository->findBy(array('school_id' => $this->id, 'combination' => 0));

		$aCourses = Ext_Thebing_Tuition_Course::query()
			->where('school_id', $this->id)
			->where('per_unit', '!=', Ext_Thebing_Tuition_Course::TYPE_COMBINATION)
			->get();

		$aBack = array();

		foreach ($aCourses as $oCourse) {

			$sHtml	= '{last_level_course_'.$oCourse->id.'}';
			$sHtml .= '<b>'.L10N::t('Sprachniveau des Schülers am Ende des Kurses "%s"', 'Thebing » Placeholder').'</b>';

			$sHtml  = str_replace('%s',$oCourse->getName(), $sHtml);

			$aBack[] = $sHtml;
		}

		return $aBack;
	}

	/**
	 * @TODO Timestamps entfernen
	 *
	 * @param int $iFrom
	 * @param int $iUntil
	 * @param Ext_Thebing_Tuition_Classroom[] $aRooms
	 * @param int $iDay
	 * @param int $iFloorId
	 * @return array
	 */
	public function getWeekBlocks($iFrom, $iUntil, $aRooms, $iDay = null, $iFloorId = 0) {

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$sLang = $oSchool->getInterfaceLanguage();
		$sFrom = date('Y-m-d', $iFrom);
		$sUntil = date('Y-m-d', $iUntil);

		$sField		= 'name_' . $sLang;

		// Wir laden unten im Query auch Schulen, die Klassenzimmer aus unserer Schule benutzen,
		// das machen wir, damit wir auch die Klassen sehen können, die sie in unser Klassenzimmer
		// angelegt haben, nur müssen diese Klassen unbedingt auf einem Zimmer aus unserer Schule
		// liegen oder in einem Zimmer aus einer Schule, die uns Klassenzimmer zur Verfügung stellt...
		// Deshalb werden die $aRooms noch einmal abgefragt... (#4412)
		//$aRooms = $this->getClassRooms(true, $sFrom);

		$aRoomIds = array_map(function($oRoom) {
			return $oRoom->id;
		}, $aRooms);
		//$aRoomIds[] = 0;
		$aRoomIds[] = -1;

		$aSql		= array();
		$sSelect	= '';
		$sJoin		= '';
		$sWhere		= '';

		if($iDay) {

			$aSql['day'] = (int)$iDay;

			$sWhere .= " AND
					`ktbd`.`day` = :day";

			$sSelect .= "
				, IF (`ts_tbdu`.`state` IS NOT NULL && `ts_tbdu`.`state` & '".\TsTuition\Entity\Block\Unit::STATE_CANCELLED."', 1, 0) `cancelled`
			";

			$sJoin .= " JOIN
					`kolumbus_tuition_blocks_days` `ktbd` ON
						`ktb`.`id` = `ktbd`.`block_id` LEFT JOIN
					`ts_tuition_blocks_daily_units` `ts_tbdu` ON
						`ts_tbdu`.`block_id` = `ktb`.`id` AND
						`ts_tbdu`.`day` = `ktbd`.`day`
			";

		}

		$iFloorId = (int)$iFloorId;

		if($iFloorId > 0)
		{
			$aSql['floor_id'] = (int)$iFloorId;

			$sWhere .= " AND 
						`kc`.`floor_id` = :floor_id					
					";

		}

		/**
		 * Achtung, hier gibt es einen Join auf "kolumbus_tuition_blocks_to_rooms" damit der Block pro Raum aufgelistet
		 * wird
		 */
		$sSql =  "
				SELECT DISTINCT
					`ktb`.*,
					UNIX_TIMESTAMP(`ktb`.`created`) `created`,
					`ktb`.`week` `week`,
					`ktt`.`name`,
					`ktt`.`from`,
					`ktt`.`until`,
					`ktt`.`lessons`
					".$sSelect.",
					`ktco`.`code` `class_color`,
					`ktbtr`.`room_id` `room_id`,
					`ktcl`.`name` `class_name`,
					`kc`.`name` `classroom`,
					`kc`.`max_students` `classroom_max`,
					`kt`.`firstname` `teacher_firstname`,
					`kt`.`lastname` `teacher_lastname`,
					`ktul`.`" . $sField . "` `level`,
					`ktul`.`name_short` `level_short`
				FROM
					`kolumbus_tuition_blocks` ktb JOIN
					`kolumbus_tuition_templates` ktt ON
						ktb.template_id = ktt.id
					".$sJoin." LEFT JOIN
					`kolumbus_tuition_classes` `ktcl` ON
						`ktcl`.`id` = `ktb`.`class_id` LEFT JOIN
					`kolumbus_tuition_colors` `ktco` ON
						`ktco`.`id` = `ktcl`.`color_id` AND
						`ktco`.`active` = 1 LEFT JOIN 
					`kolumbus_tuition_blocks_to_rooms` `ktbtr` ON
					    ktbtr.`block_id` = `ktb`.`id` LEFT JOIN
					`kolumbus_classroom` `kc` ON
						`ktbtr`.`room_id` = `kc`.`id` LEFT JOIN
					`ts_teachers` `kt` ON
						`ktb`.`teacher_id` = `kt`.`id` LEFT JOIN
					`ts_tuition_levels` `ktul` ON
						`ktb`.`level_id` = `ktul`.`id`
				WHERE
					`ktb`.`active` = 1 AND
					`ktb`.`week` BETWEEN :from AND :until AND
					(						
						`ktb`.`class_id` = 0 OR 
						(
							`ktb`.`class_id` > 0 AND
							`ktcl`.`active` = 1
						)
					) AND (
						(
							`ktbtr`.`room_id` IS NULL AND
							`ktb`.`school_id` = :school_id
						) OR
						`ktbtr`.`room_id` IN (:room_ids)
					)
					".$sWhere." 
				ORDER BY 
					`ktt`.`from` ASC
		";

		$aSql['school_id']		= (int)$this->id;
		$aSql['from']		= $sFrom;
		$aSql['until']		= $sUntil;
		$aSql['room_ids']	= $aRoomIds;

		$aItems = DB::getPreparedQueryData($sSql, $aSql);

		return $aItems;
	}

	/**
	 * @return Ext_Thebing_School
	 */
	static public function getSchoolFromSession() {

		$oSession = Core\Handler\SessionHandler::getInstance();
		$oSchool = Ext_Thebing_School::getInstance((int)$oSession->get('sid'));
		return $oSchool;
	}

	/**
	 * @return int
	 */
	static public function getSchoolIdFromSession() {
		return (int)Core\Handler\SessionHandler::getInstance()->get('sid');
	}

	/**
	 * Liefert die erste angelegte Schule
	 * @return Ext_Thebing_School
	 */
	public static function getFirstSchool() {
		$aSchools = Ext_Thebing_Client::getSchoolList(false, 0, true);
		$oSchool = reset($aSchools);
		return $oSchool;
	}

	/**
	 * Liefert die Schule aus der Session oder die erste Schule
	 *
	 * Methode darf nicht benutzt werden! Die wurde eingebaut als Ersatz für den alten, perversen Konstruktor
	 *
	 * @internal
	 * @return Ext_Thebing_School
	 */
	public static function getSchoolFromSessionOrFirstSchool() {
		$oSchool = self::getSchoolFromSession();
		if(!$oSchool->exist()) {
			$oSchool = self::getFirstSchool();
		}

		return $oSchool;
	}

	/*
	 * Funktion liefert alle  TranferProvider zurück
	 */
	public function getTransferProvider($bForSelect = false){

		$sCacheKey = 'company';
		$sCacheKey .= '_';
		$sCacheKey .= $this->id;
		$sCacheKey .= '_';
		$sCacheKey .= $this->idClient;
		$sCacheKey .= '_';
		$sCacheKey .= $bForSelect;

		$sKey		= 'transfer_provider';

		if(empty(self::$_aCacheTransferProvider[$sKey][$sCacheKey])) {

			$aCachedProviders = (array)WDCache::get($sKey);

			$aProviders = $aCachedProviders[$sCacheKey];
			if(empty($aProviders)) {

				$sSql = "
					SELECT
						`kc`.`id` `provider_id`,
						`kc`.`name` `provider_name`,
						`kc`.`from_airports` `provider_airports_from`,
						`kc`.`to_airports` `provider_airports_to`,
						`kc`.`from_accommodations` `provider_accommodations_from`,
						`kc`.`to_accommodations` `provider_accommodations_to`,
						`kc`.`from_all_accommodations` `provider_from_all_accommodations`,
						`kc`.`to_all_accommodations` `provider_to_all_accommodations`,
						`kc`.`email` `provider_email`,
						`kd`.`id` `driver_id`,
						`kd`.`name` `driver_name`
					FROM
						`kolumbus_companies` AS `kc` LEFT JOIN
						`kolumbus_drivers` AS `kd` ON
							`kd`.`companie_id` = `kc`.`id` AND
							`kd`.`active` = 1
					WHERE
						`kc`.`idSchool` = :school_id AND
						`kc`.`active` = 1 AND (
							`kc`.`valid_until` = '0000-00-00' OR
							`kc`.`valid_until` >= CURDATE()
    					)
					ORDER BY
						`kc`.`name`
				";
				$aSql = [
					'school_id' => (int)$this->id,
				];
				$aProviders = DB::getPreparedQueryData($sSql, $aSql);

				if($bForSelect) {
					$aBack = array();
					foreach((array)$aProviders as $aProvider) {
						$aBack[$aProvider['provider_id']] = $aProvider['provider_name'];
					}
					$aProviders = $aBack;
				}

				self::$_aCacheTransferProvider[$sKey][$sCacheKey] = $aProviders;

				$aCachedProviders[$sCacheKey] = $aProviders;
				WDCache::set($sKey, 86400, $aCachedProviders);

			} else {
				self::$_aCacheTransferProvider[$sKey][$sCacheKey] = $aProviders;
			}

		} else {
			$aProviders = self::$_aCacheTransferProvider[$sKey][$sCacheKey];
		}

		return $aProviders;

	}

	// Alle Unterkünfte der Schule z.B. Familien
	public function getTransferLocations($bForSelect = true) {

		$sSql = "
			SELECT
				`cdb4`.*
			FROM
				`customer_db_4` `cdb4` INNER JOIN
				`ts_accommodation_providers_schools` `ts_aps` ON
					`ts_aps`.`accommodation_provider_id` = `cdb4`.`id` AND
					`ts_aps`.`school_id` = :school_id
			WHERE
				`cdb4`.`active` = 1
		";
		$aSql = [
			'school_id' => (int)$this->id,
		];
		$aProviders = DB::getPreparedQueryData($sSql, $aSql);

		if(!$bForSelect) {
			return $aProviders;
		}

		$aBack = [];
		foreach((array)$aProviders as $aProvider) {
			$aBack[$aProvider['id']] = $aProvider['ext_33'];
		}

		return $aBack;
	}

	/**
	 * @TODO Anders benennen (getHolidays() sollte nur Schulferien liefern und nicht primär Feiertage)
	 *
	 * Methode liefert alle Feiertage und ggf. auch Schulferien der Schule
	 *
	 * @see getSchoolHolidays()
	 * @param int $iFrom
	 * @param int $iUntil
	 * @param bool $bIncludeSchoolHolidays
	 * @param bool $bAbsenceFormat
	 * @return array
	 * @throws Exception
	 */
	public function getHolidays($iFrom, $iUntil, $bIncludeSchoolHolidays=true, $bAbsenceFormat=false) {

		$sSql = "(
			SELECT
				`date`,
				'-1' `category_id`,
				`khp`.*
			FROM
				`kolumbus_holidays_public` `khp` INNER JOIN
				`kolumbus_holidays_public_schools` `khps` ON
					`khps`.`holiday_id` = `khp`.`id` AND
					`khps`.`school_id` = :school_id
			WHERE
				`khp`.`active` = 1 AND
				`khp`.`status` = 1 AND
				IF(
					`khp`.`annual` = 1
					,

						IF(
							YEAR(:from) = YEAR(:until)
							,
							CAST(DAYOFYEAR(`khp`.`date`) AS SIGNED) BETWEEN
								CAST(DAYOFYEAR(:from) AS SIGNED) AND
								CAST(DAYOFYEAR(:until) AS SIGNED)
							,
							(
								CAST(DATE_FORMAT(:from, '%c%d') AS SIGNED) <=
								CAST(DATE_FORMAT(`khp`.`date`, '%c%d') AS SIGNED)
							)OR(
								CAST(DATE_FORMAT(:until, '%c%d') AS SIGNED) >=
								CAST(DATE_FORMAT(`khp`.`date`, '%c%d') AS SIGNED)
							)
						)
					,
					`khp`.`date` BETWEEN :from AND :until
				)
			GROUP BY
				`khp`.`date`
		)";

		$aSql = array();
		$aSql['school_id'] = (int)$this->id;
		$aSql['from'] = date('Y-m-d', $iFrom);
		$aSql['until'] = date('Y-m-d', $iUntil);

		if($bAbsenceFormat) {
			$aAllHolidays = DB::getQueryPairs($sSql, $aSql);
		} else {
			$aAllHolidays = DB::getQueryRows($sSql, $aSql);
		}

		if($bIncludeSchoolHolidays) {

			$sSql = "
				SELECT
					*
				FROM
					`kolumbus_absence` `ka`
				WHERE
					`ka`.`active` = 1 AND
					`ka`.`item` = 'holiday' AND
					`ka`.`item_id` = :school_id AND
					(
						`ka`.`from` < :until AND
						`ka`.`until` > :from
					)
			";

			$aSql = array();
			$aSql['school_id'] = (int)$this->id;
			$aSql['from'] = date('Y-m-d', $iFrom);
			$aSql['until'] = date('Y-m-d', $iUntil);

			$aResult = DB::getPreparedQueryData($sSql,$aSql);

			// Tageweise formatieren
			foreach((array)$aResult as $aHolidayData) {

				$oEndDate = new WDDate($aHolidayData['until'], WDDate::DB_DATE);

				$oDate = new WDDate($aHolidayData['from'], WDDate::DB_DATE);
				$iDiff = (int)$oEndDate->getDiff(WDDate::DAY, $oDate);

				for($i = 0; $i < ($iDiff +1); $i++){
					// Wenn noch nicht vorhanden ist -> Feiertage überschreiben Schulferien :)
					if(!isset($aSchoolHolidays[$oDate->get(WDDate::DB_DATE)])){
						if($bAbsenceFormat) {
							$aAllHolidays[$oDate->get(WDDate::DB_DATE)] = -2;
						} else {
							$aHolidayData['date'] = $oDate->get(WDDate::DB_DATE);
							$aAllHolidays[] = $aHolidayData;
						}
					}
					$oDate->add(1, WDDate::DAY);
				}

			}

		}

		return (array)$aAllHolidays;
	}

	public function getContractTemplates($bPrepareSelect=true, $sUsage=null) {

		$sSelect = '*';
		$sWhere = "";
		$aSql = array();

		if($bPrepareSelect) {
			$sSelect = '`kct`.`id`, `kct`.`name`';
		}

		if($sUsage) {
			$sWhere .= " AND `kct`.`usage` = :usage";
			$aSql['usage'] = $sUsage;
		}

		$sSql = "
				SELECT
					".$sSelect."
				FROM
					`kolumbus_contract_templates` kct JOIN
					`kolumbus_contract_templates_schools` kcts ON
						kct.id = kcts.template_id
				WHERE
					`kct`.`active` = 1 AND
					`kcts`.`school_id` = :school_id
					".$sWhere."
				ORDER BY
					`kct`.`name`
				";
		$aSql['school_id'] = (int)$this->id;

		if($bPrepareSelect) {
			$aTemplates = DB::getQueryPairs($sSql, $aSql);
		} else {
			$aTemplates = DB::getQueryRows($sSql, $aSql);
		}

		$oDB = DB::getDefaultConnection();

		return $aTemplates;

	}

	public function getAdditionalServices($type, $optional=true, $chargeProvider=false, $language=null) {

		$result = Ext_Thebing_Client::getAdditionalServices($type, $this, $optional, $chargeProvider, $language);

		return $result;
	}

	/**
	 * Liefert mir alle generellen Kosten der Schule
	 * Hinweis: Kurszusatzkosten sind ober das Kursobj.
	 * bzw. Unterkunftszusatz über das Unterkinftsobj. erreichbar!!!
	 *
	 * @deprecated
	 * @param bool $mReturnMode
	 * @param int|null $iCurrencyId
	 * @param int|null $iSaisonId
	 * @param string $sLang
	 * @return Ext_Thebing_School_Additionalcost[]|array|array[]
	 */
	public function getGeneralCosts($mReturnMode = false, $iCurrencyId = null, $iSaisonId = null, $sLang = '') {
		$aBack = array();

		if(empty($sLang)){
			$sLang = self::fetchInterfaceLanguage();
		}

		$aSql = array();
		$sJoin = "";
		$sSelect = "";
		$sWhere = "";

		if($iCurrencyId) {
			$sJoin = "LEFT JOIN `kolumbus_prices_new` `kpn` ON
						`kpn`.`typeParent` = CONCAT('additionalcost_', `kc`.`id`) AND
						`kpn`.`idSaison` = :saison_id";
			$sSelect = ", `kpn`.`value` `price`";
			$aSql['saison_id'] = (int)$iSaisonId;

			// Hier darf NICHT getCurrency() aufgerufen werden da wir hier die währung brauchen die manuell gesetzt wurde über setCurrency
//			$iIdCurrency = $this->_iCurrency;

//			if(!empty($iIdCurrency)){
			$sWhere .= " AND
					IF(`kpn`.`id` IS NOT NULL, `kpn`.`idCurrency` = :currency_id, 1)
				";
//				$aSql['currency_id'] = $iIdCurrency;
			$aSql['currency_id'] = $iCurrencyId;
//			}
		}

		$sSql = "
					SELECT
						`kc`.`id`,
						`kc`.#name_field `name`,
						`kc`.`group_option`
						".$sSelect."
					FROM
						`kolumbus_costs` `kc`
						".$sJoin."
					WHERE
						`kc`.`type` = ".Ext_Thebing_School_Additionalcost::TYPE_GENERAL." AND
						`kc`.`active` = 1 AND
						(`kc`.`valid_until` >= CURDATE() OR `kc`.`valid_until` = 0000-00-00) AND
						`kc`.`idSchool` = :school_id
						".$sWhere."
					ORDER BY
						`kc`.#name_field
					";
		$aSql['school_id'] = (int)$this->id;
		$aSql['name_field'] = 'name_' . $sLang;

		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		foreach((array)$aResult as $aData){
			if($mReturnMode === true) {
				$aBack[$aData['id']] = $aData['name'];
			} elseif($mReturnMode === 2) {
				$aBack[$aData['id']] = $aData;
			} elseif($mReturnMode === false) {
				// TODO Totaler Mist, aber wird praktisch nicht verwendet
				$oCost = Ext_Thebing_School_Additionalcost::getInstance($aData['id']);
				$aBack[] = $oCost;
			}
		}

		return $aBack;
	}

	/*
	 * Liefert alle Kurs/Unterkunfts Zusatzkosten die mit Positionen verknüpft sind
	 */
	public function getAdditionalCosts($sLang = ''){

		$aFinalCosts				= array();

		$aCourses					= $this->getCourseList(false);

		$aAccommodationCategories	= Ext_Thebing_Accommodation_Category::getListBySchool($this);

		foreach((array)$aCourses as $aCourseData){
			$oCourse = Ext_Thebing_Tuition_Course::getInstance((int)$aCourseData['id']);
			$aCosts = $oCourse->getAdditionalCosts();

			foreach((array)$aCosts as $oCost){
				if(!empty($sLang)){
					$aFinalCosts[$oCost->id] = $oCost->getName($sLang);
				}else{
					$aFinalCosts[$oCost->id] = $oCost;
				}
			}

		}

		foreach($aAccommodationCategories as $oCategory){

			// Unterkunftskategorien sind mittlerweile global, daher andere Schulen wegfiltern
			$aCosts = array_filter($oCategory->getAdditionalCosts(), function(Ext_Thebing_School_Additionalcost $oAdditionalFee) {
				return $oAdditionalFee->idSchool == $this->id;
			});

			foreach((array)$aCosts as $oCost){
				if(!empty($sLang)){
					$aFinalCosts[$oCost->id] = $oCost->getName($sLang);
				}else{
					$aFinalCosts[$oCost->id] = $oCost;
				}
			}
		}

		return $aFinalCosts;
	}

	/**
	 * Liefert alle Kunden der Schule die an einem bestimmten Datum Geburtstag haben
	 *
	 * @param \DateTime $dDate
	 * @param string $sType
	 * @return Ext_TS_Inquiry_Contact_Traveller[]
	 */
	public function getBirthdayCustomers(\DateTime $dDate, $sType) {

		$sDate = $dDate->format('Y-m-d');

		switch($sType) {
			case 'current_customers':
				$sWhere = " AND
					`ts_i`.`service_from` <= '{$sDate}' AND
					`ts_i`.`service_until` >= '{$sDate}'
				";
				break;
			case 'current_and_future_customers':
				$sWhere = " AND
					`ts_i`.`service_until` >= '{$sDate}'
				";
				break;
			case 'current_and_old_customers':
				$sWhere = " AND
						`ts_i`.`service_from` <= '{$sDate}'
					";
				break;
			default:
				$sWhere = "";
		}

		$sSql = "
			SELECT
				`tc_c`.*
			FROM
				`tc_contacts` `tc_c` INNER JOIN
				`ts_inquiries_to_contacts` `ts_i_to_c` ON
					`ts_i_to_c`.`contact_id` = `tc_c`.`id` AND
					`ts_i_to_c`.`type` = 'traveller' INNER JOIN
				`ts_inquiries` `ts_i` ON
					`ts_i`.`id` = `ts_i_to_c`.`inquiry_id` AND
					`ts_i`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys` `ts_i_j` ON
					`ts_i_j`.`inquiry_id` = `ts_i`.`id` AND
					`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_i_j`.`active` = 1 AND
					`ts_i_j`.`school_id` = :school_id INNER JOIN
				`tc_contacts_to_emailaddresses` `tc_cte` ON
					`tc_cte`.`contact_id` = `tc_c`.`id` INNER JOIN
				`tc_emailaddresses` `tc_ea` ON
					`tc_ea`.`id` = `tc_cte`.`emailaddress_id` AND
					`tc_ea`.`active` = 1 AND
					`tc_ea`.`email` != ''
			WHERE
				`tc_c`.`active` = 1 AND
				DAY(`tc_c`.`birthday`) = :day AND
				MONTH(`tc_c`.`birthday`) = :month
				{$sWhere}
			GROUP BY
				`tc_c`.`id`
			ORDER BY
				`tc_c`.`created` DESC
		";

		$aSql = array();
		$aSql['school_id'] = (int)$this->id;
		$aSql['day'] = (int)$dDate->format('d');
		$aSql['month'] = (int)$dDate->format('m');

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		$aResult = array_map(function($aTraveller) {
			return Ext_TS_Inquiry_Contact_Traveller::getObjectFromArray($aTraveller);
		}, $aResult);

		return $aResult;

	}

	public function save($bLog = true) {

		$bSaveProductLine = false;

		$originalData = $this->_aOriginalData;

		if(empty($this->productlines)) {
			$bSaveProductLine = true;
		}

		if($this->_aOriginalData['accommodation_allocation_label'] !== $this->_aData['accommodation_allocation_label']) {
			WDCache::deleteGroup('Ext_Thebing_Allocation::addAllocationData');
		}

		// Bei Änderung der PayPal Client ID das WebProfile löschen, da das bei neuer ID fehlen dürfte
		if($this->_aOriginalData['paypal_client_id'] !== $this->_aData['paypal_client_id']) {
			$this->paypal_webprofile_id = '';
		}

		$save = parent::save($bLog);

		/**
		 * Reiseabschnitte sind mit einer Produktlinie verknüpft, deshalb muss beim neu Anlegen
		 * einer Schule eine Produktlinie erstellt und der Schule zugewiesen werden
		 */
		if($bSaveProductLine) {

			$oProductLine = $this->getJoinTableObject('productlines');
			$oProductLine->setI18NName('Productline ' . $this->ext_1	, 'en', 'name', 'languages_tc_p_i18n');

			$mValidate = $oProductLine->validate();

			if($mValidate === true) {

				parent::save();

			} else {
				//Erstellen der Produktlinie fehlgeschlagen, Schule wieder löschen...
				$this->delete();

				return array('PRODUCTLINE_FAILED');
			}

		}

		if(empty($this->sMd5)) {
			self::setSchoolKey($this->id);
		}

		if (
			!is_array($save) &&
			$this->tuition_excused_absence_calculation !== $originalData['tuition_excused_absence_calculation']
		) {
			(new \TsTuition\Service\AttendanceService())
				->forSchool($this)
				->onlyExcused()
				->writeLazyTask(\TsTuition\Handler\ParallelProcessing\AttendanceService::TASK_FIND_AND_REFRESH);
		}

		return $this;
	}

	/*`
	 * Unterrichtszeiten der Schule
	 *
	 * Da das Feature generell rausgenommen wurde, liefert diese Methode immer nur einen Eintrag zurück
	 *
	 * @return \Ext_Thebing_School_ClassTimes[]
	 */
	public function getClassTimes() {

		$aTimes = $this->getJoinedObjectChilds('class_times', true);

		// Standardzeit
		if(empty($aTimes)) {

			$oClassTime = $this->getJoinedObjectChild('class_times');
			$oClassTime->from = '08:00';
			$oClassTime->until = '17:00';
			$oClassTime->interval = 15;

			$aTimes = [$oClassTime];
		}

		return $aTimes;
	}

	/**
	 * @param string $sType
	 * @param null $iInterval
	 * @return array
	 */
	public function getClassTimesOptions($sType='assoc', $iInterval=null) {

		$aClassTimes = $this->getClassTimes();
		$aTimes			= array();

		foreach((array)$aClassTimes as $oClassesTime)
		{
			$iStart = Ext_Thebing_Util::convertTimeToSeconds($oClassesTime->from);
			$iEnd	= Ext_Thebing_Util::convertTimeToSeconds($oClassesTime->until);

			if($iInterval == null) {
				$iInterval = $oClassesTime->interval;
			}

			// get time rows
			$aTimeRows = Ext_Thebing_Util::getTimeRows($sType, $iInterval, $iStart, $iEnd);
			$aTimes += $aTimeRows;
		}

		return $aTimes;
	}

	/**
	 * Liefert die Ferien dieser Schule, optinal mit einem Filter nach Zeitraum (Überschneidung)
	 *
	 * Zusätzliche Informationen / Unterschied zur getHolidays():
	 * * Ferien tangieren ein Intervall und liegen nicht darinnen
	 * * Feiertage überschreiben nicht die Ferien
	 *
	 * @see getHolidays()
	 * @param DateTimeInterface|null $dFrom
	 * @param DateTimeInterface|null $dUntil
	 * @return Ext_Thebing_Absence[]
	 */
	public function getSchoolHolidays(\DateTimeInterface $dFrom = null, \DateTimeInterface $dUntil = null) {

		$aSql = ['school_id' => $this->id];

		$sWhere = "";
		if($dFrom !== null && $dUntil !== null) {
			$sWhere = " AND
				`from` <= :until AND
				`until` >= :from
			";

			$aSql['from'] = $dFrom->format('Y-m-d');
			$aSql['until'] = $dUntil->format('Y-m-d');
		}

		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_absence`
			WHERE
				`active` = 1 AND
				`item` = 'holiday' AND
				`item_id` = :school_id AND
				`category_id` = -2 
				{$sWhere}
			ORDER BY
				`from`
		";

		$aResult = (array)DB::getQueryRows($sSql, $aSql);

		return array_map(function($aRow) {
			return Ext_Thebing_Absence::getObjectFromArray($aRow);
		}, $aResult);

	}

	/**
	 * @deprecated
	 * @return string[]
	 */
	public function getRefererList() {
		return \Ext_TS_Referrer::getReferrers(true, $this->id);
	}

	/**
	 * @deprecated
	 * @return string[]
	 */
	public function getCustomerStatusList(){
		$aCustomerStatus = Ext_Thebing_Marketing_Studentstatus::getList(true, $this->id);
		return $aCustomerStatus;
	}

	public function getAccommodationTime(){
		$aAccommodations = $this->getAccommodationList();
		$aAccTimes = array();
		foreach(array_keys((array)$aAccommodations) as $iId) {
			$oAccommodation = new Ext_Thebing_Accommodation_Category($iId);
			$aAccTimes[$iId] = array(
				'from' => substr($oAccommodation->arrival_time, 0, 5),
				'until' => substr($oAccommodation->departure_time, 0, 5));
		}
		return $aAccTimes;
	}

	public function getAccommodationRoomCombinations(){

		$aAccommodations = $this->getAccommodationList();
		$oAccommodation  = new Ext_Thebing_Accommodation_Util($this);
		$aAccRooms = array();
		$aAccRooms[0] = array();

		foreach(array_keys((array)$aAccommodations) as $iId) {

			$oAccommodation->setAccommodationCategorie($iId);
			$aRoomlist = $oAccommodation->getRoomtypeList();

			$aRoomIds = array();

			foreach((array)$aRoomlist as $aRoom){

				// Räume zu einer Unterkunft
				if(!in_array($aRoom['id'], $aRoomIds)){
					$aRoomIds[] = $aRoom['id'];
				}
			}

			$aAccRooms[$iId] = $aRoomIds;

		}

		return $aAccRooms;
	}

	public function getAccommodationMealCombinations() {

		$aBack = [];

		$oAccommodation  = new Ext_Thebing_Accommodation_Util($this);
		$aAccRooms = $this->getAccommodationRoomCombinations();

		foreach(array_keys((array)$aAccRooms) as $iAccId) {

			if($iAccId < 1) {
				continue;
			}

			$oAccommodation->setAccommodationCategorie($iAccId);
			$aRoomlist = $oAccommodation->getRoomtypeList();

			foreach((array)$aRoomlist as $aRoom) {

				$aMeals = array_filter(explode(',', $aRoom['meal']), function($iMealId) {
					$oMeal = Ext_Thebing_Accommodation_Meal::getInstance($iMealId);
					return $oMeal->isValid();
				});

				if(isset($aBack[$iAccId][$aRoom['id']])) {
					$aBack[$iAccId][$aRoom['id']] = array_merge($aBack[$iAccId][$aRoom['id']], $aMeals);
				} else {
					$aBack[$iAccId][$aRoom['id']] = $aMeals;
				}

				// array_values() damit Array auch im JSON ein Array ist
				$aBack[$iAccId][$aRoom['id']] = array_values(array_unique($aBack[$iAccId][$aRoom['id']]));

			}

		}

		return $aBack;

	}

	/**
	 * Liefert alle Transferorte für Gruppen zurück
	 * @param type $iType
	 * @return type
	 */
	public function getGroupTransferLocations($iType = 1){

		$aBack = array();
		$aBack[] = '';

		$aLocations = Ext_TS_Transfer_Location::getLocations(true, $this->id);

		// Schule immer möglich
		$aBack['school_' . $this->id] = L10N::t('Schule');

		if($iType != 0){
			// Unterkunft möglich
			$aBack['accommodation_0'] = L10N::t('Unterkunft');
		}



		foreach((array)$aLocations as $iKey => $sLocation){
			$aBack['location_' . $iKey] = $sLocation;
		}

		return $aBack;
	}

	public function getGroups($bShort=false) {

		$sField = 'name';
		if($bShort) {
			$sField = 'short';
		}

		$sSql = " SELECT 
						`kg`.`id`,
						`kg`.#name_field
					FROM 
						`kolumbus_groups` `kg`
					WHERE 
						`kg`.`active` = 1 AND 
						`kg`.`school_id` = :school_id 
					ORDER BY
						`kg`.#name_field ASC
					";

		$aSql = array(
			'school_id' => (int)$this->id,
			'name_field' => $sField
		);
		$aGroups = DB::getQueryPairs($sSql, $aSql);

		return $aGroups;

	}

	public function getTransferLocationsForInquiry($sType, $oInquiry, $mLang = '') {
		$aBack = array();
		$aBack[0] = '';

		$mLang = Ext_Thebing_Util::getLanguageObject($mLang, 'Thebing » Transfer');

		// Schule immer möglich
		$aBack['school_' . $this->id] = $mLang->translate('Schule');

		// Alle Schulbezogenen Orte
		$aLocations = Ext_TS_Transfer_Location::getLocations(true, $this->id, $mLang);

		// Buchungsbezogene Transfers
		if(
			$sType == 'arrival' ||
			$sType == 'departure'
		) {

			$aBack['accommodation_0'] = $mLang->translate('Unterkunft');

		} else if(
			$oInquiry instanceof Ext_TS_Inquiry &&
			$oInquiry->exist()
		) {

			// Individual Transfer
			$aOtherAllocations = Ext_Thebing_Allocation::getAllocationByInquiryId($oInquiry->id, 0,true);

			foreach((array)$aOtherAllocations as $aFamily_info) {
				$oAccommodation = Ext_Thebing_Accommodation::getInstance($aFamily_info['family_id']);
				$aBack['accommodation_' . $aFamily_info['family_id']] = $oAccommodation->ext_33;
			}

		}

		foreach((array)$aLocations as $iKey => $sLocation){
			$aBack['location_' . $iKey] = $sLocation;
		}

		return $aBack;
	}

	public function getVisumList($sDescription = ''){
		$aList = Ext_Thebing_Visum::getVisumStatusList($this->id);
		if($sDescription != ""){
			$aList = Ext_Thebing_Util::addEmptyItem($aList, L10N::t('kein Visum', $sDescription));
		}
		return $aList;
	}

	/*
	 * Validate Ableiten der WD:BASIC
	 */
	public function validate($bThrowExceptions = false) {

		$aErrors = parent::validate($bThrowExceptions);

		if(
			$aErrors === true
		) {
			$aErrors = array();
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		// Prüfen, ob Zeitzone auf dem Server verfügbar ist
		if(!empty($this->_aData['timezone'])) {
			$bReturn = Ext_Thebing_Util::setTimezone($this->_aData['timezone']);
			if($bReturn === false) {
				if(!is_array($aErrors)) {
					$aErrors = array();
				}
				$aErrors['cdb2.timezone'] = ['NOT_SUPPORTED_TIMEZONE'];
			}
		}

		// TODO Zu helle Farben rausfiltern
		/*if (
			$this->system_color !== $this->_aOriginalData['system_color'] &&
			str_starts_with(strtolower($this->system_color), '#f')
		) {
			if(!is_array($aErrors)) {
				$aErrors = array();
			}
			$aErrors['cdb2.system_color'] = ['TOO_BRIGHT_COLOR'];
		}*/

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Date format check

		if($this->date_format_long != '' && !preg_match('/^([\.\- \/]*\%[dmyYObB][\.\- \/]*){3,}$/', $this->date_format_long))
		{
			if(!is_array($aErrors)) {
				$aErrors = array();
			}
			$aErrors['cdb2.date_format_long'] = array('NOT_SUPPORTED_DATE_FORMAT');
		}
		if($this->date_format_short != '' && !preg_match('/^([\.\- \/]*\%[dmyYObB][\.\- \/]*){1,3}$/', $this->date_format_short))
		{
			if(!is_array($aErrors)) {
				$aErrors = array();
			}
			$aErrors['cdb2.date_format_short'] = array('NOT_SUPPORTED_DATE_FORMAT');
		}

		if(empty($aErrors)){
			$aErrors = true;
		}

		return $aErrors;

	}

	/**
	 * Liefert die SchulSchriftart für PDFs
	 */
	public function getPdfFont(){

		if($this->font == ''){
			return 'dejavusans';
		}elseif(array_key_exists($this->font, $this->getClient()->getFonts())){
			return $this->font;
		}else{
			return 'dejavusans';
		}

	}

	/**
	 * Liefert alle Kosten dieser Schule
	 * Hinweis: Die einzelnen Kosten eines Kurses/Unterk. können über das entpr. Obj. geholt werden
	 */
	public function getCosts($bAsObject = true, $sLang=false){

		if(!$sLang){
			$sLang = $this->getLanguage();
		}

		$sSql = "SELECT
						`id`,
						`name_".$sLang."` `title`
					FROM
						`kolumbus_costs`
					WHERE
						`idSchool` = :idSchool AND 
						(`valid_until` >= CURDATE() OR `valid_until` = 0000-00-00) AND
						`active` = 1
					ORDER BY
						`name_".$sLang."`
					";

		$aSql = array();
		$aSql['idSchool'] = (int)$this->id;

		$aDynamicCostOptions = (array)DB::getQueryPairs($sSql,$aSql);

		$aBack = array();

		if($bAsObject){
			foreach((array)$aDynamicCostOptions as $iId => $sName){
				$aBack[] = Ext_Thebing_School_Additionalcost::getInstance($iId);
			}
		}else{
			$aBack = $aDynamicCostOptions;
		}

		return $aBack;
	}

	/**
	 * Optionen für Storno Typen
	 */
	public function getStornoTypeFromOptions(Tc\Service\LanguageAbstract $oLanguage = null) {

		$oLanguage = Ext_Thebing_Util::getLanguageObject($oLanguage, 'Thebing » Marketing » Stornofee');

		$aStornoFrom = [
			'all'				=> $oLanguage->translate('alles (gesamt)', 'Thebing » Marketing » Stornofee'),
			'all_split'			=> $oLanguage->translate('alles (einzeln)', 'Thebing » Marketing » Stornofee'),
			'selection'			=> $oLanguage->translate('Differenziert', 'Thebing » Marketing » Stornofee'),
			'accommodation'		=> $oLanguage->translate('Unterkunft', 'Thebing » Marketing » Stornofee'),
			'course'			=> $oLanguage->translate('Kurs', 'Thebing » Marketing » Stornofee'),
		];

		$add = function(array $selectOptions, string $prefix) use (&$aStornoFrom) {
			foreach ($selectOptions as $key => $value) {
				$aStornoFrom[$prefix.'_'.$key] = $value;
			}
		};

		$add($this->getAccommodationCategoriesList(true, $oLanguage->getLanguage()), 'accommodation_category');
		$add($this->getCourseCategoriesList('select', $oLanguage->getLanguage()), 'course_category');
		// Schulspezifische Kosten
		$add($this->getCosts(false, $oLanguage->getLanguage()), 'additional_cost');

		return $aStornoFrom;
	}

	/**
	 * Liefert alle Untzerkunftskombinationen der Schule
	 */
	public function getAccommodationCombinations($sLanguage=null) {

		if(empty($sLanguage)) {
			$sLanguage = $this->fetchInterfaceLanguage();
		}

		$sCacheKey = self::ACCOMMODATION_COMBINATION_CACHE.'_'.$this->id.'_'.$sLanguage;

		$aBack = WDCache::get($sCacheKey);

		if($aBack === null) {
			$aCombinations = $this->getAccommodationMealCombinations();

			$aBack = array();
			foreach((array)$aCombinations as $iAccommodationCat => $aRoomdata){
				foreach((array)$aRoomdata as $iRoom => $aMealdata){
					foreach((array)$aMealdata as $iMeal){
						$oCategory	= Ext_Thebing_Accommodation_Category::getInstance($iAccommodationCat);
						$oRoom		= Ext_Thebing_Accommodation_Roomtype::getInstance($iRoom);
						$oMeal		= Ext_Thebing_Accommodation_Meal::getInstance($iMeal);

						$sName = '';
						$sName .= $oCategory->getName($sLanguage);
						$sName .= '/';
						$sName .= $oRoom->getName($sLanguage);
						$sName .= '/';
						$sName .= $oMeal->getName($sLanguage, true);

						$aBack[$iAccommodationCat.'_'.$iRoom.'_'.$iMeal] = $sName;
					}
				}
			}

			WDCache::set($sCacheKey, (60*24*24), $aBack, false, self::ACCOMMODATION_COMBINATION_CACHE);

		}

		return $aBack;
	}

	/**
	 * Liefert die Transferpackete einer Schule
	 *
	 * @param bool $bForSelect
	 * @return mixed[]
	 */
	public function getTransferPackages($bForSelect = false){
		$sSql = "SELECT
						*
					FROM
						`kolumbus_transfers_packages`
					WHERE
						`active` = 1 AND
						`school_id` = :school_id
				";

		$aSql = array();
		$aSql['school_id'] = (int)$this->id;

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		$aBack = array();
		if($bForSelect){
			foreach((array)$aResult as $aData){
				$aBack[$aData['id']] = $aData['name'];
			}
		}else{
			foreach((array)$aResult as $aData){
				$aBack[] = Ext_Thebing_Transfer_Package::getInstance($aData['id']);
			}
		}


		return $aBack;

	}

	/**
	 * Die Funktion liefert alle Specials dieser Schule
	 *
	 * @param bool $bForSelect
	 * @return Ext_Thebing_School_Special[]|string[]
	 * @throws Exception
	 */
	public function getSpecials($bForSelect = false) {

		$sSql = "
			SELECT
				`ts_sp`.*
			FROM
				`ts_specials` ts_sp JOIN
				`ts_specials_schools` `ts_sps` ON
					`ts_sp`.`id` = `ts_sps`.`special_id`
			WHERE
				`ts_sp`.`active` = 1 AND
				`ts_sps`.`school_id` = :school_id
			ORDER BY
				`ts_sp`.`position`
		";

		$aSql = array();
		$aSql['school_id'] = (int)$this->id;

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		$aBack = array();

		foreach((array)$aResult as $aData) {

			if($bForSelect) {
				$aBack[$aData['id']] = $aData['name'];
			} else {
				$oSpecial = Ext_Thebing_School_Special::getObjectFromArray($aData);
				$aBack[] = $oSpecial;
			}
		}

		return $aBack;
	}

	/*
	 * Gibt alle Special-Filter optionen wieder
	 */
	public function getSpecialFilterOptions($bQueryParts = false){

		$aSpecials = $this->getSpecials(true);

		$aBack = array();
		foreach((array)$aSpecials as $iId => $sName){
			if($bQueryParts){
				$aBack['special_'.$iId] = " `kips`.`special_id` = " . $iId . " ";
			}else{
				$aBack['special_'.$iId] = $sName;
			}

		}

		return $aBack;
	}

	// Berechnet das Unterkunftsdatum anhand des Kurszeitraums
	public function getAccommodationDatesOfCourseDates($iFrom, $iUntil, \Ext_Thebing_Accommodation_Category $category){

		$aBack = array();
		$aBack['first_i']			= 0;
		$aBack['last_i']			= 0;
		$aBack['first']				= '';
		$aBack['last']				= '';
		$aBack['weeks_i']			= '';

		if(
			WDDate::isDate($iFrom, WDDate::TIMESTAMP) &&
			WDDate::isDate($iUntil, WDDate::TIMESTAMP) &&
			$iFrom > 0 &&
			$iUntil > 0
		) {

			// Unterkunftswochentag
			$sWeekDay = $category->getAccommodationStart($this);

			// Local-Schulen ohne UK
			if(empty($sWeekDay)) {
				return null;
			}

			// TODO Ext_TC_Util::convertWeekdayToInt()
			switch($sWeekDay){
				case 'sa':
					$iWeekDay = 6;
					break;
				case 'so':
					$iWeekDay = 7;
					break;
				case 'mo':
					$iWeekDay = 1;
					break;
				default:
					$sMsg = 'Invalid accommodation start day ('.$sWeekDay.')';
					throw new RuntimeException($sMsg);
			}

			$oDateFrom	= new WDDate($iFrom, WDDate::TIMESTAMP);
			$oDateUntil = new WDDate($iUntil, WDDate::TIMESTAMP);

			$iWeekDayFrom = $oDateFrom->get(WDDate::WEEKDAY);

			// Solange ein Tag abziehen bis Schul-Starttag erreicht ist
			while($iWeekDayFrom != $iWeekDay){
				$oDateFrom->sub(1, WDDate::DAY);
				$iWeekDayFrom = $oDateFrom->get(WDDate::WEEKDAY);
			}


			$oTempDateUntil = new WDDate($oDateFrom->get(WDDate::TIMESTAMP));
			$iWeeks = 0;

			// Ende errechnen lassen
			while($oDateUntil->compare($oTempDateUntil) > 0){
				$iWeeks++;
				$iTo = Ext_Thebing_Util::getUntilDate($oDateFrom->get(WDDate::TIMESTAMP), $iWeeks, $this->id, $category, true, $this->id);
				$oTempDateUntil->set($iTo, WDDate::TIMESTAMP);
			}

			$iFirst = $oDateFrom->get(WDDate::TIMESTAMP);
			$iLast = $oTempDateUntil->get(WDDate::TIMESTAMP);

			if($iWeeks <= 0){
				$iWeeks = 1;
			}

			$aBack['first_i']			= $iFirst;
			$aBack['last_i']			= $iLast;
			$aBack['first']				= Ext_Thebing_Format::LocalDate($iFirst, $this->id);
			$aBack['last']				= Ext_Thebing_Format::LocalDate($iLast, $this->id);
			$aBack['weeks_i']			= $iWeeks;
		}

		return $aBack;
	}

	public function checkNormalPriceCalculationMode($bUnit=false) {

		if(
			(
				$this->price_structure_week == 0 &&
				$bUnit === false
			)
			||
			(
				$this->price_structure_unit == 0 &&
				$bUnit === true
			)
		) {
			return true;
		} else {
			return false;
		}

	}


	public function getAllStornoValidity(){

		$sSql = "SELECT
						*
					FROM
						`kolumbus_validity`
					WHERE
						`active` = 1 AND
						`parent_id` = :school_id AND
						`parent_type` = 'school' AND
						`item_type` = 'cancellation_group'
				";

		$aSql = array();
		$aSql['school_id'] = (int)$this->id;

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		$aBack = array();

		foreach((array)$aResult as $aData){
			$aBack[] = Ext_Thebing_Validity_WDBasic::getInstance((int)$aData['id']);
		}

		return $aBack;
	}

	// Sucht für passende Schüler als Zusammenreisend in Frage kommen
	public static function searchForRoomSharingInqiryJourneys($iInquiryId, $sSearch, $iSchool, $aFrom, $aUntil)
	{
		$aBack	= array();
		$oDate	= new WDDate();
		$aFrom	= (array)$aFrom;
		$aUntil = (array)$aUntil;
		$iCount	= count($aFrom);
		$iMax	= $iCount - 1;

		if(count($aFrom) != count($aUntil) || $iCount <= 0)
		{
			return;
		}

		$aSql	= array();

		$sWhere = ' AND (';

		foreach($aFrom as $iKey => $sFrom)
		{
			$sUntilForFrom = $aUntil[$iKey];

			$sTempFrom	= Ext_Thebing_Format::ConvertDate($sFrom, $iSchool, true);
			$sTempUntil = Ext_Thebing_Format::ConvertDate($sUntilForFrom, $iSchool, true);

			$sKeyFrom	= 'from_val_'.$iKey;
			$sKeyUntil	= 'until_val_'.$iKey;

			$sWhere .= '
				(
					`ts_i_j_a`.`until` >= :'.$sKeyFrom.' AND `ts_i_j_a`.`from` <= :'.$sKeyUntil.'
				)
			';

			if($iKey < $iMax)
			{
				$sWhere .= ' OR ';
			}

			$aSql[$sKeyFrom]	= $sTempFrom;
			$aSql[$sKeyUntil]	= $sTempUntil;
		}

		$sWhere .= ')';

		$sSql = "SELECT
						`ts_i_j_a`.`id` `journey_accommodation`,
						`ts_i`.`id` `inquiry_id`,
						`tc_c_n`.`number` `customer_number`,
						`tc_c`.`firstname`,
						`tc_c`.`lastname`
					FROM
						`ts_inquiries` `ts_i` INNER JOIN
						`ts_inquiries_to_contacts` `ts_i_to_c` ON
							`ts_i_to_c`.`inquiry_id` = `ts_i`.`id` AND
							`ts_i_to_c`.`type` = 'traveller' INNER JOIN
						`tc_contacts` `tc_c` ON
							`tc_c`.`id` = `ts_i_to_c`.`contact_id` AND
							`tc_c`.`active` = 1 INNER JOIN
						`ts_inquiries_journeys` `ts_i_j` ON
							`ts_i_j`.`inquiry_id` = `ts_i`.`id` AND
							`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
							`ts_i_j`.`active` = 1 AND
							`school_id` = :school_id INNER JOIN
						`ts_inquiries_journeys_accommodations` `ts_i_j_a` ON
							`ts_i_j_a`.`journey_id` = `ts_i_j`.`id` AND
							`ts_i_j_a`.`active` = 1 INNER JOIN
						`tc_contacts_numbers` `tc_c_n` ON
							`tc_c_n`.`contact_id` = `tc_c`.`id`
					WHERE
						`ts_i`.`active` = 1 AND
						`ts_i`.`canceled` <= 0 AND
						(
							`tc_c`.`firstname` LIKE :like_search OR
							`tc_c`.`lastname` LIKE :like_search OR
							`tc_c_n`.`number` = :search
						) AND
						`ts_i`.`id` != :inquiry_id
						".$sWhere."
					GROUP BY
						`ts_i_j_a`.`id`
					ORDER BY
						`ts_i_j_a`.`from` DESC
			";

		$aSql['date'] = $sDate;
		$aSql['school_id'] = (int)$iSchool;
		$aSql['like_search'] = '%' . $sSearch . '%';
		$aSql['search'] = $sSearch;
		$aSql['inquiry_id'] = $iInquiryId;

		$aResult = (array)DB::getPreparedQueryData($sSql, $aSql);

		$oFormatCustomerName = new Ext_Thebing_Gui2_Format_CustomerName();

		foreach($aResult as $aRowData)
		{
			$sCustomerName			= $oFormatCustomerName->formatByResult($aRowData);

			#$iJourneyAccommodation	= $aRowData['journey_accommodation'];

			//db-struktur passt noch nicht dazu...
			//
			#$oJourneyAccommodation	= Ext_TS_Inquiry_Journey_Accommodation::getInstance($iJourneyAccommodation);
			#$sInfo					= $oJourneyAccommodation->getInfoString($sLanguage, $iSchool);

			$aBack[] = array(
				#'info' => $sCustomerName . ' - ' . $sInfo,
				'info' => $sCustomerName,
				'customer_number' => $aRowData['customer_number'],
				'inquiry_id' => $aRowData['inquiry_id'],
			);
		}

		return $aBack;
	}


	/**
	 * Liefert alle Gruppen einer Schule
	 * @param type $iLimit
	 * @param type $iOffset
	 * @param type $sSearch
	 * @param type $iSchoolId
	 * @return type
	 */
	public function getAllGroups($bForSelect = false){

		$sSql = "SELECT 
						`kg`.* ,
						(
							SELECT 
								UNIX_TIMESTAMP(`from`) 
							FROM 
								`kolumbus_groups_courses` 
							WHERE
								`group_id` = `kg`.`id` AND
						  		`active` = 1 
							ORDER BY 
								`from` ASC
							LIMIT 1
						 ) `start_course`,
						 (
							SELECT 
								UNIX_TIMESTAMP(`from`) 
							FROM 
								`kolumbus_groups_accommodations` 
							WHERE
								`group_id` = `kg`.`id` AND
						  		`active` = 1 
							ORDER BY 
								`from` ASC
							LIMIT 1
						  ) `start_accommodation`
						
					FROM 
						`kolumbus_groups` `kg`
					WHERE 
						`kg`.`active` = 1 AND 
						`kg`.`school_id` = :school_id 
					ORDER BY 
						`kg`.`id` DESC 
					 ";

		$aSql = array();
		$aSql['school_id'] = (int)$this->id;

		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		if($bForSelect){
			$aBack = array();
			foreach($aResult as $aData){
				$aBack[$aData['id']] = $aData['name'];
			}

			$aResult = $aBack;
		}

		return (array)$aResult;
	}

	public function getProductLineId() {

		$iFirstProductLine	= 0;
		$aProductLines		= $this->productlines;

		if(
			!empty($aProductLines)
		) {
			$iFirstProductLine = (int)reset($aProductLines);
		}

		return $iFirstProductLine;

	}

	/**
	 * @param bool $bForSelect
	 * @param string $sLanguage
	 * @param bool $bShort
	 * @return Ext_Thebing_Accommodation[]
	 */
	public function getAccommodations($bForSelect = false, $sLanguage = false, $bShort = false){

		if(
			!$sLanguage
		){
			$sLanguage = $this->getInterfaceLanguage();
		}

		if(
			$bShort
		){
			$sNameField = 'short_'.$sLanguage;
		}else{
			$sNameField = 'name_'.$sLanguage;
		}

		$oAccommodation		= new Ext_Thebing_Accommodation();
		$oAccommodation->setSchoolId($this->id);
		$aAccommodations	= $oAccommodation->getArrayListSchool($bForSelect, $sNameField);

		if(
			!$bForSelect
		){
			foreach($aAccommodations as $iKey => $mRowData){

				$oAccommodation = new Ext_Thebing_Accommodation();
				$oAccommodation->setData($mRowData);

				$aAccommodations[$iKey] = $oAccommodation;
			}
		}

		return $aAccommodations;
	}

	/**
	 * @see self::getCourses()
	 *
	 * @deprecated
	 * @param bool $bForSelect
	 * @param bool $sLanguage
	 * @param bool $bShort
	 * @return Ext_Thebing_Tuition_Course_List
	 */
	public function getCourseListObject($bForSelect = false, $sLanguage = false, $bShort = false) {

		if(
			!$sLanguage
		) {
			$sLanguage = $this->getInterfaceLanguage();
		}

		$oCourseList = new Ext_Thebing_Tuition_Course_List();
		$oCourseList->bForSelect	= $bForSelect;
		$oCourseList->sLanguage		= $sLanguage;
		$oCourseList->iSchoolId		= $this->id;
		$oCourseList->bShort		= $bShort;

		return $oCourseList;
	}

	/**
	 * Liefert den Zeichensatz der Schule für den CSV Export
	 * @return string
	 */
	public function getCharsetForExport(){

		$sCharset	= $this->csv_charset;
		if(empty($sCharset)){
			$sCharset = 'CP1252';
		}

		return $sCharset;
	}

	/**
	 * Liefert das Trefnnzeichen der Schule für den CSV Export
	 * @return string
	 */
	public function getSeparatorForExport(){

		$sDelimiter	= $this->export_delimiter;
		if(empty($sDelimiter)){
			$sDelimiter = ';';
		}

		return $sDelimiter;
	}

	/**
	 * Methode errechnet das früheste/letzte Datum für zeitintervalle z.B. Kurse/Unterkünfte
	 * das aFrom muss identisch wie aUntil aufgebaut sein
	 * @param type $aFrom
	 * @param type $aUntil
	 * @param type $aActive
	 * @return type
	 */
	public function getFirstLastDate ($aFrom, $aUntil, $aActive = array()){

		$aBack						= array();
		$aBack['first']				= '';
		$aBack['first_i']			= 0;
		$aBack['first_weekday']		= '';
		$aBack['last']				= '';
		$aBack['last_i']			= 0;
		$aBack['last_weekday']		= '';

		$iFirst = 0;
		$iLast = 0;

		if(!empty($aFrom[0])){


			$bSetFirst = true;

			foreach((array)$aFrom as $i => $mTemp){

				if(
					!empty($aActive) &&
					$aActive[$i] != 1
				){
					continue;
				}

				$iTempFrom = Ext_Thebing_Format::ConvertDate($aFrom[$i], $this->id);
				$iTempUntil = Ext_Thebing_Format::ConvertDate($aUntil[$i], $this->id);

				if($iTempFrom > 0 && $iTempUntil > 0){
					if($bSetFirst){
						$iFirst = $iTempFrom;
						$iLast = $iTempUntil;
						$bSetFirst = false;
					}else{
						if($iTempFrom < $iFirst){
							$iFirst = $iTempFrom;
						}
						if($iTempUntil > $iLast){
							$iLast = $iTempUntil;
						}
					}
				}
			}

			$oDateFrom					= new WDDate($iFirst);
			$oDateUntil					= new WDDate($iLast);

			$aBack['first']				= Ext_Thebing_Format::LocalDate($iFirst, $this->id);
			$aBack['first_i']			= $iFirst;
			$aBack['first_weekday']		= Ext_Thebing_Util::getWeekDay(3, $iFirst);
			$aBack['last']				= Ext_Thebing_Format::LocalDate($iLast, $this->id);
			$aBack['last_i']			= $iLast;
			$aBack['last_weekday']		= Ext_Thebing_Util::getWeekDay(3, $iLast);
		}

		return $aBack;
	}


	/**
	 * Liefert ein Array mit allen Dokumenten zu denen eine Anfrage konvertiert werden darf
	 * @return type
	 */
	public function getEnquiryDocumentOptions(){

		$aOptions = array();

		if(Ext_Thebing_Access::hasRight('thebing_invoice_proforma_new', $this->id) ){
			$aOptions['proforma'] = L10N::t('Proforma', 'Thebing » Students » Contact');
		}

		$aOptions['invoice'] = L10N::t('Rechnung', 'Thebing » Students » Contact');

		return $aOptions;
	}

	/**
	 * Liefert ein Array mit allen Nummernkreisen, die für Anfragen zur Verfügung stehen
	 * @return array
	 */
	public function getEnquiryDocumentNumberrangeOptions() {
		$aReturn = array();

		$aDocumentOptions = $this->getEnquiryDocumentOptions();

		// Fake Buchung
		$oInquiry = new Ext_TS_Inquiry();

		// Hier braucht nur auf brutto typen gepfüft werden da es NICHT möglich ist
		// Netto Rechnungen andere Nummernkreise zuzuweisen als brutto, außermanuell über das
		// dd einen anderen zu wählen
		$aTypes = array();
		$aTypes['proforma']	= 'proforma_brutto';
		$aTypes['invoice']	= 'brutto';

		$bCheckNumberrangeAccess = !Ext_Thebing_Access::hasRight('thebing_invoice_numberranges');
		$aInboxList = Ext_Thebing_School::getInboxList();

		// Array mit Nummernkreisen aufbauen, die überhaupt zur Verfügung stehen
		foreach($aDocumentOptions as $sKey => $sName) {
			$sTypeNumberRange = $oInquiry->getTypeForNumberrange($aTypes[$sKey]);

			$aNumberranges = (array)Ext_Thebing_Inquiry_Document_Numberrange::getNumberrangesByType($sTypeNumberRange, false, $bCheckNumberrangeAccess, $this->id, true);

			// Gruppieren nach Inboxen
			// Die Gruppierung ist wichtig für die Abhängigkeiten zur Inbox über die Sets
			foreach($aNumberranges as $oNumberrange) {
				$aAllocationSets = $oNumberrange->getJoinedObjectChilds('sets');
				foreach($aAllocationSets as $oAllocationSet) {
					$aInboxes = $oAllocationSet->inboxes;
					foreach($aInboxes as $iInbox) {
						$aReturn[$sKey][$iInbox][$oNumberrange->id] = $oNumberrange->getName();
					}
				}
			}

			// Wenn kein Nummernkreis gefunden wurde, Default-Werte setzen
			if(empty($aReturn[$sKey])) {
				foreach($aInboxList as $aInbox) {
					$aReturn[$sKey][$aInbox['id']][0] = '';
				}
			}
		}

		return $aReturn;
	}

	/**
	 * Alle Inboxen des Mandanten mit der Rechteübrüfung aus dieser Schule
	 *
	 * @return array
	 */
	public function getInboxList()
	{
		$aBack = array();

		$oClient	= $this->getClient();

		if($oClient->checkUsingOfInboxes())
		{
			$aInboxList = $oClient->getInboxList(false, false);

			foreach($aInboxList as $aInboxData)
			{
				$sRightKey = 'thebing_invoice_inbox_' . $aInboxData['id'];

				if(Ext_Thebing_Access::hasRight($sRightKey, $this->id))
				{
					$aBack[] = $aInboxData;
				}
			}
		}

		return $aBack;

	}

	/**
	 * @TODO Entfernen (es gibt dafür erweiterte Methoden in der Ext_Thebing_Util)
	 * @deprecated
	 *
	 * Gibt den Starttag der Kurse zurück.
	 *
	 * 1 = Montag, ..., 7 = Sonntag (date('N')-Format)
	 *
	 * @TODO Auf course_startday umstellen? Vermutlich funktioniert dann aber gar nichts mehr
	 * @return integer
	 */
	public function getCourseStartDay() {
		return 1; // MO
	}

	/**
	 * @TODO Entfernen (es gibt dafür erweiterte Methoden in der Ext_Thebing_Util)
	 * @deprecated
	 *
	 * Gibt den Endtag der Kurse zurück.
	 *
	 * 1 = Montag, ..., 7 = Sonntag (date('N')-Format)
	 *
	 * @return integer
	 */
	public function getCourseEndDay() {
		return 5; // FR
	}

	/**
	 * Liefert die Anzahl der gebuchten Unterkünfte pro Unterkunftskategorie (alle der Schule)
	 *
	 * @param DateTime $oFrom
	 * @param DateTime $oUntil
	 * @param bool $bAllocated Nur Unterkunftsbuchungen mit Zuweisung
	 * @return array
	 */
	public function getBookedAccommodationCountPerAccommodationCategory(DateTime $oFrom, DateTime $oUntil, $bAllocated=false) {

		if(!$bAllocated) {
			$sSelect = ", COUNT(`ts_ija`.`id`) `accommodation_count` ";
			$sJoinAddon = "";
			$sGroupBy = " GROUP BY `kac`.`id` ";
		} else {
			$sSelect = ", `kaa`.`inquiry_accommodation_id`, `kaa`.`room_id` ";
			$sJoinAddon = " INNER JOIN
				`kolumbus_accommodations_allocations` `kaa` ON
					`kaa`.`inquiry_accommodation_id` = `ts_ija`.`id` AND
					`kaa`.`active` = 1 AND
					`kaa`.`status` = 0 AND
					`kaa`.`from` <= :until AND
					`kaa`.`until` >= :from
			";
			$sGroupBy = "";
		}

		$sSql = "
			SELECT
				`kac`.`id` `category_id`
				".$sSelect."
			FROM
				`kolumbus_accommodations_categories` `kac` JOIN
				`ts_accommodation_categories_settings` `ts_acs` ON
					`ts_acs`.`category_id` = `kac`.`id` JOIN
				`ts_accommodation_categories_settings_schools` `ts_acss` ON
					`ts_acs`.`id` = `ts_acss`.`setting_id` AND
					`ts_acss`.`school_id` = :school_id
			LEFT JOIN (
					`ts_inquiries_journeys_accommodations` `ts_ija`
				INNER JOIN
					`ts_inquiries_journeys` `ts_ij`
				ON
					`ts_ij`.`id` = `ts_ija`.`journey_id` AND
					`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_ij`.`school_id` = :school_id AND
					`ts_ij`.`active` = 1
				INNER JOIN
					`ts_inquiries` `ts_i`
				ON
					`ts_i`.`id` = `ts_ij`.`inquiry_id` AND
					`ts_i`.`active` = 1 AND
					`ts_i`.`canceled` = 0
					".Ext_Thebing_System::getWhereFilterStudentsByClientConfig('ts_i')."
					".$sJoinAddon."
			) ON
				`ts_ija`.`accommodation_id` = `kac`.`id` AND
				`ts_ija`.`active` = 1 AND
				`ts_ija`.`visible` = 1 AND
				`ts_ija`.`from` <= :until AND
				`ts_ija`.`until` >= :from
			WHERE
				`kac`.`active` = 1
			".$sGroupBy."
		";
		$aSql = [
			'from' => $oFrom->format('Y-m-d H:i:s'),
			'until' => $oUntil->format('Y-m-d H:i:s'),
			'school_id' => $this->id,
		];

		if(!$bAllocated) {

			// Bei »nicht zugewiesen« liefert der Query bereits das gewünschte Resultat
			$aResult = (array)DB::getQueryPairs($sSql, $aSql);

		} else {

			$aResult = (array)DB::getQueryRows($sSql, $aSql);
			$aTmp = array();

			// Bei »zugewiesen« ist der Schüler in der Woche nur zugewiesen, wenn das auch alle Zuweisungen aus der KW sind
			foreach($aResult as $aRow) {
				if(!isset($aTmp[$aRow['category_id']][$aRow['inquiry_accommodation_id']])) {
					$aTmp[$aRow['category_id']][$aRow['inquiry_accommodation_id']] = 1;
				}

				// Wenn kein Raum, dann ist Schüler nicht zugewiesen
				if($aRow['room_id'] == 0) {
					$aTmp[$aRow['category_id']][$aRow['inquiry_accommodation_id']] = 0;
				}
			}

			$aResult = array_map(function($aJourneyAccommodations) {
				return array_sum($aJourneyAccommodations);
			}, $aTmp);

		}

		return $aResult;

	}

	/**
	 * Liefert die Bezahlbedingung zu einer bestimmten Schule
	 *
	 * @return Ext_TS_Payment_Condition|null
	 */
	public function getDefaultPaymentCondition() {

		if($this->payment_condition_id > 0) {
			return Ext_TS_Payment_Condition::getInstance($this->payment_condition_id);
		}

		return null;

	}

	/**
	 * @return Ext_TC_Communication_Template|null
	 */
	public function getTemplateForMobileAppForgottenPassword() {

		$iTemplateId = (int)$this->getMeta('student_app_template_forgotten_password');
		if(!empty($iTemplateId)) {
			return \Ext_TC_Communication_Template::getInstance($iTemplateId);
		}

		return null;

	}

	/**
	 * @deprecated
	 *
	 * Gibt die Unterkunft-Starttage zu einem Kurs-Starttag zurück.
	 *
	 * Die zurückgegebene Liste ist nach Datum sortiert, das letzte Datum ist der reguläre Unterkunfts-Starttag,
	 * mögliche vorherige Eintrage sind Extranächte.
	 *
	 * Es wird nur das Datum von $dCourseStartDate beachtet, die Zeitangaben werden ignoriert. Das als Parameter
	 * übergebene Objekt bleibt unverändert.
	 *
	 * Das Datum sollte ein korrekter Starttag für die Schule (siehe Ext_Thebing_School::$course_startday)
	 * oder ein Montag sein (in diesem Fall wird der Tag mit Ext_Thebing_Util::getCorrectCourseStartDay() korrigiert).
	 * Ansonsten kann der Rückgabewert der Methode falsch sein.
	 *
	 * Die Unterkunftskategorie wird für die Berechnung der Extranächte benötigt.
	 *
	 * @param \DateTime $dCourseStartDate
	 * @param \Ext_Thebing_Accommodation_Category $oAccommodationCategory
	 * @return \DateTime[]
	 */
	public function getAccommodationStartDates($dCourseStartDate, Ext_Thebing_Accommodation_Category $oAccommodationCategory) {

		$dCourseStartDate = clone $dCourseStartDate;
		if($dCourseStartDate->format('N') == 1) {
			$dCourseStartDate = Ext_Thebing_Util::getCorrectCourseStartDay($dCourseStartDate, $this->course_startday);
		}

		$iCourseStart = $this->course_startday;
		$iAccommodationStart = Ext_Thebing_Util::convertWeekdayToInt($oAccommodationCategory->getAccommodationStart($this));

		/*
		 * Berechnung des Unterkunft-Starttags
		 *
		 * * $iCourseStart > $iAccommodationStart (Kurs startet später als die Unterkunft innerhalb der Woche)
		 *   * X Tage abziehen
		 *   * X = ($iCourseStart - $iAccommodationStart)
		 *   * Beispiel: $iCourseStart = 6, $iAccommodationStart = 4
		 *     * X = ((6 - 4) - 0) = 2
		 *
		 * * alle anderen Fälle (Kurs startet am selben Tag oder vor der Unterkunft inenrhalb der Woche)
		 *   * X Tage abziehen
		 *   * X = (($iCourseStart - $iAccommodationStart) + 7)
		 *   * Beispiel: $iCourseStart = 6, $iAccommodationStart = 7
		 *     * X = ((6 - 7) + 7) = 6
		 *   * Beispiel: $iCourseStart = 7, $iAccommodationStart = 7
		 *     * X = ((7 - 7) + 7) = 7
		 *   * Beispiel: $iCourseStart = 3, $iAccommodationStart = 3
		 *     * X = ((3 - 3) + 7) = 7
		 */
		$dAccommodationStartDate = clone $dCourseStartDate;
		$iModifier = 0;
		if($iCourseStart <= $iAccommodationStart) {
			$iModifier = -7;
		}
		$dAccommodationStartDate->sub(new \DateInterval('P'.(($iCourseStart - $iAccommodationStart) - $iModifier).'D'));

		$aStartDates = array($dAccommodationStartDate);

		$iExtraNights = $oAccommodationCategory->max_extra_nights_prev;
		$dCurrentExtraNight = clone $dAccommodationStartDate;
		for($i = 1; $i <= $iExtraNights; $i++) {
			$dCurrentExtraNight->sub(new \DateInterval('P1D'));
			array_unshift($aStartDates, $dCurrentExtraNight);
			$dCurrentExtraNight = clone $dCurrentExtraNight;
		}

		return $aStartDates;

	}

	/**
	 * @deprecated
	 *
	 * Gibt die Unterkunft-Endtage zu einem Kurs-Starttag zurück.
	 *
	 * Die zurückgegebene Liste ist nach Datum sortiert, das erste Datum ist der reguläre Unterkunfts-Endtag,
	 * mögliche weitere Eintrage sind Extranächte.
	 *
	 * Es wird nur das Datum von $dCourseStartDate beachtet, die Zeitangaben werden ignoriert. Das als Parameter
	 * übergebene Objekt bleibt unverändert.
	 *
	 * Das Datum sollte ein korrekter Starttag für die Schule (siehe Ext_Thebing_School::$course_startday)
	 * oder ein Montag sein (in diesem Fall wird der Tag mit Ext_Thebing_Util::getCorrectCourseStartDay() korrigiert).
	 * Ansonsten kann der Rückgabewert der Methode falsch sein.
	 *
	 * Die Unterkunftskategorie wird für die Berechnung der Extranächte benötigt.
	 *
	 * Wenn eine Kursdauer von weniger als 1 angegeben wird, wird 1 angenommen.
	 *
	 * @param \DateTime $dCourseStartDate
	 * @param \Ext_Thebing_Accommodation_Category $oAccommodationCategory
	 * @param integer $iDuration Die Kursdauer in Wochen (>=1)
	 * @return \DateTime[]
	 */
	public function getAccommodationEndDates($dCourseStartDate, Ext_Thebing_Accommodation_Category $oAccommodationCategory, $iDuration = 1) {

		$dCourseStartDate = clone $dCourseStartDate;
		$iDuration = (int)$iDuration;
		if($dCourseStartDate->format('N') == 1) {
			$dCourseStartDate = Ext_Thebing_Util::getCorrectCourseStartDay($dCourseStartDate, $this->course_startday);
		}
		if($iDuration < 1) {
			$iDuration = 1;
		}
		if($iDuration > 1) {
			$dCourseStartDate->add(new \DateInterval('P'.($iDuration - 1).'W'));
		}

		$iInclusiveNights = $oAccommodationCategory->getAccommodationInclusiveNights($this);
		if($iInclusiveNights < 1) {
			$iInclusiveNights = 1;
		}

		$aAccommodationStartDates = $this->getAccommodationStartDates($dCourseStartDate, $oAccommodationCategory);
		$dAccommodationEndDate = array_pop($aAccommodationStartDates);
		/** @var \DateTime $dAccommodationEndDate */
		$dAccommodationEndDate->add(new \DateInterval('P'.$iInclusiveNights.'D'));

		$aEndDates = array($dAccommodationEndDate);

		$iExtraNights = $oAccommodationCategory->max_extra_nights_after;
		$dCurrentExtraNight = clone $dAccommodationEndDate;
		for($i = 1; $i <= $iExtraNights; $i++) {
			$dCurrentExtraNight->add(new \DateInterval('P1D'));
			$aEndDates[] = $dCurrentExtraNight;
			$dCurrentExtraNight = clone $dCurrentExtraNight;
		}

		return $aEndDates;

	}

	/**
	 * @return array
	 */
	public function getAttendanceFlexFields() {

		$aSectionFlexFields = \Ext_TC_Flexibility::getSectionFieldData(array('tuition_attendance_register'), true);

		$aFlexFieldsForSelect = [];

		foreach($aSectionFlexFields as $oFlexField) {

			$aFlexFieldsForSelect[$oFlexField->aData['id']] = $oFlexField;

		}

		return $aFlexFieldsForSelect;
	}

	public function getAllowedFlexFields() {

		$aFlexFields = $this->getAttendanceFlexFields();

		$aAllowedFields = $this->getJoinTableData('teacherlogin_flexfields');
		$aAllowedFields = array_flip($aAllowedFields);

		$aReturn = array_intersect_key($aFlexFields, $aAllowedFields);

		return $aReturn;

	}

	/**
	 * Liefert den Absender einer SMS
	 * @return string
	 */
	public function getSmsSenderName() {
		return $this->sms_sender;
	}

	public function getEmailSenderName() {
		return $this->ext_1; // Name
	}

	public function getSenderName(string $channel)
	{
		return match ($channel) {
			'sms' => $this->sms_sender,
			default => $this->ext_1,
		};
	}

	public function getFileManagerEntityPath(): string {
		return \Util::getCleanFilename('Ts\School');
	}

	public function getTimezone(): string {

		if (!empty($this->timezone)) {
			return $this->timezone;
		}

		return \Ext_Thebing_Client::getFirstClient()->timezone;

	}

	public function routeNotificationFor($driver, $notification = null)
	{
		return match ($driver) {
			'mail' => [$this->email, $this->getEmailSenderName()],
			default => null,
		};
	}

	public function getAgencies($returnSelectOptions=false) {

		$agencies = \Ext_Thebing_Agency::query()
			->leftJoin('ts_agencies_to_schools as schools', 'schools.agency_id', 'ka.id')
			->where('ka.status', '=', 1)
			->where(
				function($query) {
					$query->where('schools.school_id', '=', $this->id)
						->orWhere('ka.schools_limited', '=', 0);
				}
			)
			->orderBy('ext_1')
			->pluck('ext_1', 'id')
			->toArray();

		return $agencies;
	}

	public function getCorrespondenceLanguagesOptions(): array {
		$allLanguages = Ext_Thebing_Data::getSystemLanguages();
		$languages = \Illuminate\Support\Arr::wrap($this->languages);

		return array_intersect_key($allLanguages, array_flip($languages));
	}

	public function getCorrespondenceLanguages(): array {
		return \Illuminate\Support\Arr::wrap($this->languages);
	}

	public function getSignatureValue($sField): string {

		[$type, $languageIso] = explode('_', \Illuminate\Support\Str::after($sField, 'signature_'));

		$signature = \Illuminate\Support\Arr::first($this->communication_emailsignatures, fn ($signature) => $signature['language_iso'] === $languageIso);

		if ($signature && isset($signature[$type])) {
			return (string) $signature[$type];
		}

		return '';
	}

	public function setSignatureValue(string $key, $value) {

		[$type, $languageIso] = explode('_', \Illuminate\Support\Str::after($key, 'signature_'));

		// TODO \Illuminate\Support\Arr::mapWithKeys()
		$grouped = collect($this->communication_emailsignatures)
			->mapWithKeys(fn ($signature) => [$signature['language_iso'] => $signature])
			->toArray();

		if (!isset($grouped[$languageIso])) {
			$grouped[$languageIso] = ['text' => '', 'html' => ''];
		}

		$grouped[$languageIso][$type] = $value;

		$joinData = \Illuminate\Support\Arr::map($grouped, function ($data, string $languageIso) {
			$data['language_iso'] = $languageIso;
			return $data;
		});

		$this->communication_emailsignatures = array_values($joinData);

	}

	public function getTeacherLoginViewPeriod(string $view): ?\Spatie\Period\Period
	{
		$field = sprintf('teacherlogin_%s_period', $view);

		[$before, $after] = explode(',', (string)$this->$field, 2);
		$start = $end = null;

		$now = \Carbon\Carbon::now();

		if (!empty($before)) {
			$start = $now->clone()->sub(new DateInterval($before));
			if (str_ends_with($before, 'W')) {
				$start = $start->startOfWeek(Carbon::MONDAY);
			}
			if ($view === 'attendance') {
				$end = $now->clone();
			} else {
				$end = $start->clone()->addYear()->endOfYear()->endOfWeek(Carbon::SUNDAY);
			}
		}

		if (!empty($after)) {
			$end = $now->clone()->add(new DateInterval($after));
			if (str_ends_with($after, 'W')) {
				$end = $end->endOfWeek(Carbon::SUNDAY);
			}
			if ($start === null) {
				$start = $end->clone()->subYear()->startOfYear()->startOfWeek(Carbon::MONDAY);
			}
		}

		if ($start && $end) {
			return Spatie\Period\Period::make($start->startOfDay(), $end->endOfDay(), \Spatie\Period\Precision::SECOND());
		}

		return null;
	}

	/**
	 * True, wenn in dieser Schule Entwürfe aktiviert sind.
	 *
	 * @param ?Ext_Thebing_School $school
	 * @return bool
	 */
	public static function draftInvoicesActive(?\Ext_Thebing_School $school = null): bool
	{
		return Ext_Thebing_Client::draftInvoicesForced() ||
			(
				$school &&
				$school->draft_invoices
			);
	}

	public function getCommunicationName(string $channel): string
	{
		return $this->ext_1;
	}

	public function getCommunicationRoutes(string $channel): ?\Illuminate\Support\Collection
	{
		return match ($channel) {
			'mail' => collect((!empty($this->email)) ? [$this->email] : [])
				->map(fn ($email) => [$email, $this->getCommunicationName($channel)]),
			default => null,
		};
	}

	public function getCommunicationDefaultLayout(): ?\Ext_TC_Communication_Template_Email_Layout
	{
		return ($this->default_communication_layout_id > 0)
			? \Ext_TC_Communication_Template_Email_Layout::getInstance($this->default_communication_layout_id)
			: null;
	}

	public function getCommunicationSenderName(string $channel, CommunicationSubObject $subObject = null): string
	{
		return match ($channel) {
			'sms' => $this->sms_sender,
			default => $this->ext_1,
		};
	}

	public function getCommunicationEmailAccount(CommunicationSubObject $subObject = null): ?\Ext_TC_Communication_EmailAccount
	{
		if ($this->email_account_id > 0) {
			return \Ext_Thebing_Mail::getInstance($this->email_account_id);
		}

		return null;
	}
}
