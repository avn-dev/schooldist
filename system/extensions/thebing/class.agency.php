<?php

use Communication\Interfaces\Model\CommunicationSubObject;
use Core\Database\WDBasic\Builder;
use Core\Traits\WdBasic\MetableTrait;
use TsCompany\Entity\AbstractCompany;

/**
 * Class Ext_Thebing_Agency
 *
 * @property int $id
 * @property int $idClient
 * @property int $idSchool
 * @property int $active
 * @property int $creator_id
 * @property string $email
 * @property string $nickname
 * @property string $password
 * @property string $changed
 * @property string $last_login
 * @property int $changed_by
 * @property string $created
 * @property int $views
 * @property $access_code
 * @property string $ext_1 ist der Name
 * @property string $ext_2 ist die Abkürzung des Namens
 * @property string $ext_3 ist die Straße
 * @property string $ext_4 ist die Postleitzahl
 * @property string $ext_5 ist die Stadt der Agentur
 * @property string $ext_6
 * @property string $ext_10 ist die Webseite der Agentur
 * @property string $ext_12
 * @property string $ext_13
 * @property string $ext_14
 * @property string $ext_15
 * @property string $ext_16
 * @property string $ext_17
 * @property string $ext_18
 * @property string $ext_19
 * @property string $ext_20
 * @property string $ext_21
 * @property string $ext_22
 * @property int $ext_23
 * @property string $ext_24
 * @property int $ext_25
 * @property int $ext_26
 * @property int $ext_27
 * @property int $ext_28
 * @property int $ext_29
 * @property string $ext_32
 * @property string $ext_33
 * @property string $ext_34
 * @property string $ext_35
 * @property int $ext_37
 * @property string $ext_38
 * @property int $ext_39
 * @property string $vat_number
 * @property string $recipient_code
 * @property int $status
 * @property string $tracking_key
 * @property string $state
 * @property string founding_year
 * @property string $start_cooperation
 * @property string $staffs
 * @property string $partner_schools
 * @property string $customers
 * @property int $invoice
 * @property string $comment
 * @property int $user_id
 *
 * @method static \Ext_Thebing_AgencyRepository getRepository()
 * @property $contacts
 *
 */
class Ext_Thebing_Agency extends \TsCompany\Entity\AbstractCompany implements \Communication\Interfaces\Model\HasCommunication {

	use MetableTrait;

	public $oAccommodation;
	public $oCourse;
	public $oSchool;

	public $oSaison;

	protected $_sPlaceholderClass = \TsCompany\Service\Placeholder\Agency::class;

	protected $_aFormat = [
		'subagency_commission' => [
			'validate' => 'REGEX',
			'validate_value' => '^(100|[1-9]?[0-9])$'
		]
	];
	
	/**
	 * TODO als Meta umsetzen
	 * @var array
	 */
	protected $_aAttributes = [
		'salesforce_id' => [
			'class' => 'WDBasic_Attribute_Type_Varchar',
		],
		'hubspot_id' => [
			'type' => 'int',
		],
		'recipient_code' => [
			'type' => 'text',
		],
		'vat_number' => [
			'type' => 'text',
		],
		'subagency_id' => [
			'type' => 'int',
		],
		'subagency_commission' => [
			'type' => 'float',
		],
		'subagency_commission_basedon' => [
			'type' => 'text',
		],
	];

	/**
	 * @var array
	 */
	protected $_aJoinTables = [
		'provision_groups' => [
			'table' => 'ts_agencies_to_commission_categories',
			'foreign_key_field' => '',
			'primary_key_field' => 'agency_id'
		],
		'lists' => [
			'table' => 'kolumbus_agency_lists_agencies',
			'foreign_key_field' => 'list_id',
			'primary_key_field' => 'agency_id',
			'class' => 'Ext_Thebing_Agency_List'
		],
		'comments' => [
			'table' => 'ts_companies_comments',
			'foreign_key_field' => 'id',
			'primary_key_field' => 'company_id',
			'class' => \TsCompany\Entity\Comment::class,
			'autoload' => false
		],
		'groups' => [
			'table' => 'kolumbus_agency_groups_assignments',
			'foreign_key_field' => 'group_id',
			'primary_key_field' => 'agency_id',
			'class' => 'Ext_Thebing_Agency_Group'
		],
		'contacts' => [
			'table' => 'ts_companies_contacts',
			'foreign_key_field' => '',
			'primary_key_field' => 'company_id',
			'readonly' => true // Wichtig, da es das auch als JOINEDOBJECT gibt!
		],
		'numbers' => [
			'table' => 'ts_companies_numbers',
			'foreign_key_field' => ['number', 'numberrange_id'],
			'primary_key_field' => 'company_id',
			'autoload' => true
		],
		'activation_codes' => [
			'table' => 'ts_agencies_activation_codes',
			'foreign_key_field' => ['activation_code', 'expired'],
			'primary_key_field' => 'agency_id',
			'autoload' => false,
			'readonly' => true
		],
		'schools' => [
			'table' => 'ts_agencies_to_schools',
			'foreign_key_field' => 'school_id',
			'primary_key_field' => 'agency_id'
		],
	];

	/**
	 * @var array
	 */
	protected $_aJoinedObjects = array( 
        'creditnotes' => array(
			'class' => 'Ext_Thebing_Agency_Manual_Creditnote',
			'key' => 'agency_id',
			'check_active' => true,
			'type' => 'child'
        ),
        'inquiries' => array(
			'class' => 'Ext_TS_Inquiry',
			'key' => 'agency_id',
			'check_active' => true,
			'type' => 'child',
			'delete_check' => true
        ),
        'contacts' => array(
			'class' => 'Ext_Thebing_Agency_Contact',
			'key' => 'company_id',
			'check_active' => true,
			'type' => 'child'
        ),
        'provision_groups' => array(
			'class' => 'Ext_Thebing_Agency_Provision_Group',
			'key' => 'agency_id',
			'check_active' => true,
			'type' => 'child'
        )
    );

	/**
	 * @var array
	 */
	static public $aCache = array();

	/**
	 * @var array
	 */
	protected $_aFlexibleFieldsConfig = [
		'agencies_bank' => [],
		'agencies_details' => [],
		'agencies_info' => [],
		'agencies_provision' => []
	];

	/**
	 * @var bool
	 */
	protected $bForceUpdateUser = true;

    public function setSchool(Ext_Thebing_School $oSchool){
        $this->oSchool = $oSchool;
    }

	// GetField wird noch an einigen stellen genutzt!
	public function getField($sField){
		return $this->$sField;
	}
	
	public function setSaison($oSaison){
		$this->oSaison = $oSaison;
	}

	/**
	 * @param int $idPeriod
	 * @return Ext_Thebing_Agency_Provision
	 */
	public function getSchoolProvisions($idPeriod) {

		$oSchool = $this->oSchool;

		$oAgencySchoolProvisions = new Ext_Thebing_Agency_Provision($this->_aData['id'], $oSchool,$idPeriod);

		return $oAgencySchoolProvisions;

	}

	// METHODS TO MANAGE THE ENTRY

	public static function getGroupList($bForSelect = false, $bAsObject = false, $bAddEmptyEntry = true){
		$sSQL = "SELECT 
					*
				FROM
					`kolumbus_agency_groups` 
				WHERE 
					`active` = 1
				ORDER BY
					`name`
				";
		$aSQL = array();
		$aBack = DB::getPreparedQueryData($sSQL, $aSQL);
		if($bForSelect){
				if($bAddEmptyEntry){
				$aBack_[0] = ' --- ';
				}
			foreach((array)$aBack as $aData){
				$aBack_[$aData['id']] = $aData['name'];
			}
			return $aBack_;
		}elseif($bAsObject){
			$aReturn = array();
			foreach((array)$aBack as $aData){
				$aReturn[] = Ext_Thebing_Agency_Group::getInstance($aData['id']);
			}
			return $aReturn;
		}
		return $aBack;
	}
	
	public function getGroupById($iGroupId){
		$sSQL = "SELECT 
					*
				FROM
					`kolumbus_agency_groups` 
				WHERE 
					`id` 		= :id AND
					`active` 	= 1 
				";
		$aSQL = array(
			'id'		=> $iGroupId
		);
		$aResult = DB::getPreparedQueryData($sSQL, $aSQL);
		return $aResult[0];
	}
	
	public function getGroupByName($sName) {

		$sSQL = "SELECT 
					*
				FROM
					`kolumbus_agency_groups` 
				WHERE 
					`name` LIKE :name AND
					`active` = 1 
				";
		$aSQL = array(
			'name'		=> $sName
		);
		$aResult = DB::getQueryRow($sSQL, $aSQL);

		return $aResult;

	}

	public function saveInitalCosts($aIds){
		
		$sSql = "DELETE FROM `kolumbus_agency_initialcosts` WHERE agency_id = :agency_id";
		$aSql = array('agency_id'=>$this->id);
		DB::executePreparedQuery($sSql,$aSql);
		
		foreach((array)$aIds as $iId => $aTemp){
			
			$sSQL = "INSERT INTO
					`kolumbus_agency_initialcosts` 
				SET
					`agency_id` = :agency_id,
					`cost_id` 	= :cost_id
				";
			$aSQL = array(
				'agency_id'	=> $this->id,
				'cost_id'	=> $iId
			);
			DB::executePreparedQuery($sSQL, $aSQL);
			
		}
		
	}

	public function getInitalCosts($bFormat = 0, $bForce = false) {

		// TODO: Prüfen, ob der statische Cache noch benötigt wird (#6869)
		$sCacheKey = 'getInitalCosts_'.$this->id.'_'.$bFormat;

		if(
			$bForce ||
			!array_key_exists($sCacheKey, self::$aCache)
		) {

			$sSql = "
				SELECT 
					*
				FROM 
					`kolumbus_agency_initialcosts` 
				WHERE 
					`agency_id` = :agency_id
				";
			$aSql = array('agency_id'=>(int)$this->id);
			$aResult = DB::getPreparedQueryData($sSql,$aSql);

			if($bFormat == 2) {
				foreach($aResult as $aData){
					$aBack[] = $aData['cost_id'];
				}
				self::$aCache[$sCacheKey] = json_encode($aBack);
			} else {
				foreach($aResult as $aData) {
					$aBack[] = $aData['cost_id'];
				}
				self::$aCache[$sCacheKey] = $aBack;
			}
			
		}

		return self::$aCache[$sCacheKey];
	}

	public function checkInitalCost($iIdCost){
		
		$aInitalCosts = $this->getInitalCosts(1);

		foreach((array)$aInitalCosts as $iId) {
			if((int)$iIdCost == (int)$iId) {
				
				return true;
			}
		}
		return false;
	}

	public function setRequireDocuments(){
		//$this->setFieldValue('ext_27', 1);
		//$this->setFieldValue('ext_28', 1);
		$this->setFieldValue('invoice', 2);
		$this->setFieldValue('ext_29', 1);
		$this->save();
	}
	
	public function getStornoFees(){

		$sSql = "SELECT
					 * 
				FROM 
					`kolumbus_stornofee`
				WHERE
					`agency_id` = :agency_id AND
					`idSchool` = :idSchool AND
					`active` = 1";
		$aSql = [
		    'agency_id'=>$this->getField('id'),
			'idSchool'=>\Core\Handler\SessionHandler::getInstance()->get('sid')
        ];
		
		$aResult = DB::getPreparedQueryData($sSql,$aSql);
		return $aResult;
		
	}
	
	public function deleteStornoFee($iId){

		$sSql = "DELETE FROM
					`kolumbus_stornofee`
				WHERE
					`agency_id` = :agency_id AND
					`idSchool` = :idSchool AND
					`id` = :id";
		$aSql = array('id'=>$iId,
						'agency_id'=>$this->getField('id'),
						'idSchool'=>\Core\Handler\SessionHandler::getInstance()->get('sid')
					);

		DB::executePreparedQuery($sSql,$aSql);		

	}

	public function createPassword() {
		$sPassword = \Util::generateRandomString(6);
		$this->password = md5($sPassword);
		
		$this->save();
		return $sPassword;
	}

	public function sendPassword() {

		$oClient = new Ext_Thebing_Client($this->idClient);
	
		$sPassword = $this->createPassword();
	
		$sSubject = L10N::t('agency_send_password_subject');
		$sMessage = L10N::t('agency_send_password_body');
		
		$sMessage = str_replace('{client}', $oClient->name, $sMessage);
		$sMessage = str_replace('{username}', $this->nickname, $sMessage);
		$sMessage = str_replace('{password}', $sPassword, $sMessage);
	
		$sFrom = $oClient->getEmailFrom();

		$oMaster = $this->getMasterContact();
		if( !$oMaster )
		{
			return false;
		}
		
		$sMail = $oMaster->email;

		$bSuccess = wdmail($sMail, $sSubject, $sMessage, $sFrom);

		return $bSuccess;

	}
	
	public function sendMaterialOrder($oOrder) {

		$oClient = new Ext_Thebing_Client($this->idClient);
		$oAddress = new Ext_Thebing_Agency_Address($oOrder->address_id);
	
		$aUsers = $oClient->getMaterialOrderUsers();

		$sMaterial = $oOrder->getMaterialString();

		$sSubject = L10N::t('agency_material_order_subject');
		$sMessage = L10N::t('agency_material_order_body');
		
		$sMessage = str_replace('{material}', $sMaterial, $sMessage);
		$sMessage = str_replace('{address}', $oAddress->getAddressString(), $sMessage);
		$sMessage = str_replace('{message}', $oOrder->message, $sMessage);

		$sFrom = $oClient->getEmailFrom();

		foreach((array)$aUsers as $aUser) {
			wdmail($aUser['email'], $sSubject, $sMessage, $sFrom);		
		}

	}
	
	public function getSchools($bPrepareSelect=0) {
		
		$iClient = $this->idClient;
		$oClient = Ext_Thebing_Client::getInstance($iClient);
		$aSchools = $oClient->getSchools((bool)$bPrepareSelect);

		$aAgencySchools = $this->ext_34;

		if($bPrepareSelect) {
			foreach((array)array_keys($aSchools) as $iSchoolId) {
				if(!in_array($iSchoolId, $aAgencySchools)) {
					unset($aSchools[$iSchoolId]);
				}
			}
		} else {
			foreach((array)$aSchools as $iKey=>$aSchool) {
				if(!in_array($aSchool['id'], $aAgencySchools)) {
					unset($aSchools[$iKey]);
				}
			}
		}

		return $aSchools;

	}

    /**
     * Gibt alle Buchungen dieser Agentur zurück
     *
     * @return Ext_TS_Inquiry[]
     */
	public function getInquiries() {
		$aInquiries = $this->getJoinedObjectChilds('inquiries');
		return $aInquiries;
	}

    /**
     * @Todo: Sollte überarbeitet werden, damit keine zwei Rückwertetypen mehr vorhanden sind. Bei Objekten eher null zurück geben.
     *
     * @param int $iInquiryId
     * @return bool|Ext_TS_Inquiry
     */
	public function getInquiry($iInquiryId) {
		
		$oInquiry = new Ext_TS_Inquiry($iInquiryId);

		if($oInquiry->agency_id == $this->id) {
			return $oInquiry;
		} else {
			return false;
		}
		
	}

	public function getMaterialOrder($iOrderId) {

		$oOrder = new \Ext_Thebing_Agency_Materialorder($iOrderId);

		if($oOrder->agency_id == $this->id) {
			return $oOrder;
		} else {
			return false;
		}

	}

	public function getMaterialOrders($iFrom, $iUntil, $sFilter=false, $iOffset=0, $iRows=20) {

		$aSql = array();
		$sWhere = '';

		if($sFilter == 'not_sent') {
			$sWhere .= " AND `kmoo`.`sent_date` = :date";
			$aSql['date'] = '0000-00-00 00:00:00';
		} elseif($sFilter == 'sent') {
			$sWhere .= " AND `kmoo`.`sent_date` != :date";
			$aSql['date'] = '0000-00-00 00:00:00';
		}

		$sSql = "
				SELECT
					SQL_CALC_FOUND_ROWS
					kmoo.*,
					ks.ext_1 school,
					kaa.shortcut address,
					UNIX_TIMESTAMP(`kmoo`.`created`) `created`,
					UNIX_TIMESTAMP(`kmoo`.`sent_date`) `sent_date`
				FROM
					`kolumbus_material_orders_orders` kmoo JOIN
					`customer_db_2` ks ON
						kmoo.school_id = ks.id JOIN
					`ts_companies_addresses` kaa ON
						kmoo.address_id = kaa.id
				WHERE
					`kmoo`.`agency_id` = :agency_id AND
					`kmoo`.`active` = 1 AND
					`kmoo`.`created` BETWEEN :from AND :until
					".$sWhere."
				ORDER BY
					`kmoo`.`created` DESC
				LIMIT
					".(int)$iOffset.", ".(int)$iRows."
					";
		$aSql['agency_id'] = $this->id;
		$aSql['from'] = date("YmdHis", $iFrom-1);
		$aSql['until'] = date("YmdHis", $iUntil+1);

		$aOrders = \DB::getPreparedQueryData($sSql, $aSql);

		$aCount = \DB::getQueryData('SELECT FOUND_ROWS() `c`');
		$iTotal = $aCount[0]['c'];

		$aReturn = array();
		$aReturn['total'] = $iTotal;
		$aReturn['orders'] = $aOrders;

		return $aReturn;

	}

	/**
	 * {@inheritdoc}
	 *
	 * @param integer $iSchoolId
	 */
	public function getSchool($iSchoolId = 0) {
		
		$aSchools = $this->getSchools(1);
		if(array_key_exists($iSchoolId, $aSchools)) {
			$oSchool = Ext_Thebing_School::getInstance($iSchoolId);
			return $oSchool;
		} else {
			// TODO die Parent-Methode gibt im Fehlerfall null zurück, sollte das dann nicht hier auch so sein?
			return false;
		}

	}


	public function getBookingStats($iFrom, $iUntil, $iSchoolId=false) {
		
		$sWhere = "";
		
		if($iSchoolId) {
			$sWhere .= " AND
					`ts_ij`.`school_id` = :school_id
				";
			$aSql['school_id'] = (int)$iSchoolId;
		}
		
		$sSql = "
				SELECT 
					COUNT(DISTINCT `ts_i`.`id`) `bookings`,
					SUM(`ts_ijc`.`weeks`) `weeks`
				FROM
					`ts_inquiries` `ts_i` INNER JOIN
					`ts_inquiries_journeys` `ts_ij` ON
						`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
						`ts_ij`.`active` = 1 AND
						`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' INNER JOIN
					`ts_inquiries_journeys_courses` `ts_ijc` ON
						`ts_ijc`.`journey_id` = `ts_ij`.`id` AND
						`ts_ijc`.`for_tuition` = 1 AND
						`ts_ijc`.`active` = 1 AND
						`ts_ijc`.`visible` = 1 AND
						`ts_ijc`.`calculate` = 1
				WHERE
					`ts_i`.`active` = 1 AND
					`ts_i`.`confirmed` != 0 AND
					`ts_i`.`canceled` = 0 AND
					`ts_i`.`created` BETWEEN :from AND :until AND
					`ts_i`.`agency_id` = :agency_id
					".$sWhere."
		";

		$aSql['agency_id'] = (int)$this->id;
		$aSql['from'] = date("YmdHis", $iFrom-1);
		$aSql['until'] = date("YmdHis", $iUntil+1);

		$aStats = DB::getQueryRow($sSql, $aSql);

		return $aStats;		
	}

	public static function getAgencyPaymentMethods($oLanguage=null) {

		$oLanguage = Ext_Thebing_Util::getLanguageObject($oLanguage, 'Thebing » Marketing » Agenciegroups');

		$aPaymentMethods = array( 
			0 => $oLanguage->translate('Netto vor Anreise'), 
			1 => $oLanguage->translate('Brutto vor Anreise'), 
			2 => $oLanguage->translate('Netto bei Anreise'), 
			3 => $oLanguage->translate('Brutto bei Anreise')
		);

		return $aPaymentMethods;
	}

	public static function getAgencyPaymentsCurrenciesContactCacheKey() {
		return 'ts_agency_getAgencyPaymentsCurrenciesContactCacheKey';
	}
	
	public static function getAgencyPaymentsCurrenciesContact(&$aPayments, &$aCurrencies, &$aContacts) {

		$sCacheKey = self::getAgencyPaymentsCurrenciesContactCacheKey();
		
		$aCacheData = WDCache::get($sCacheKey);
		
		if($aCacheData === null) {

			$aPayments = array();
			$aAgencies = Ext_Thebing_Client::getFirstClient()->getAgencies(true);
			foreach((array)$aAgencies as $aAgencyId => $sName){
				$oAgency  = Ext_Thebing_Agency::getInstance($aAgencyId);
				$aPayments[$aAgencyId] = array('id'=>$oAgency->ext_26, 'comment' => $oAgency->ext_38);
				if($oAgency->ext_23 != '') {
					$aCurrencies[$aAgencyId] = $oAgency->ext_23;
				}
				$aAgencyContacts = $oAgency->getContacts(true);
				$aAgencyContacts = Ext_Thebing_Util::addEmptyItem($aAgencyContacts);
				$aSelectOptions = array();
				foreach($aAgencyContacts as $iContact => $sValue) {

					$aOption = array(
						'value' => $iContact,
						'text' => $sValue
					);

					if($iContact == $oGroup->agency_contact_id) {
						$aOption['selected'] = 1;
					}

					$aSelectOptions[] = $aOption;
				}
				$aContacts[$aAgencyId] = $aSelectOptions;
			}
			
			$aCacheData['payments'] = $aPayments;
			$aCacheData['currencies'] = $aCurrencies;
			$aCacheData['contacts'] = $aContacts;

			WDCache::set($sCacheKey, 60*60*24, $aCacheData);
			
		} else {

			$aPayments = $aCacheData['payments'];
			$aCurrencies = $aCacheData['currencies'];
			$aContacts = $aCacheData['contacts'];
			
		}

	}

	/**
	 * Gibt die Anzahl der Kontakte (Mitarbeiter) der Agentur zurück
	 *
	 * @return int
	 */
	public function getNumberOfContacts() {
		return count($this->getContacts(true));
	}

	/*
	 * Liefert alle AgenturContacte
	 */

	/**
	 * @param $bForSelect
	 * @param $bAsObjects
	 * @param $mFor
	 * @return array[]|Ext_Thebing_Agency_Contact[]
	 * @throws Exception
	 */
	public function getContacts($bForSelect=false, $bAsObjects = false, $mFor = '') {

		$query = \Ext_Thebing_Agency_Contact::query()
			->where('company_id', $this->id)
			->orderBy('lastname')
			->orderBy('firstname');

		// Filter welche Kontakte geholt werden sollen
		if(is_numeric($mFor)) {
			$query->where('id', (int)$mFor);
		} else if (in_array($mFor, ['accommodation', 'transfer', 'reminder'])) {
			$query->where(function (Builder $where) use ($mFor) {
				$where->where($mFor, 1)
					->orWhere('master_contact', 1);
			});
		}

		$collection = $query->get();

		if ($bForSelect) {
			$collection = $collection->mapWithKeys(fn($contact) => [$contact->id => $contact->getName()]);
		} else if(!$bAsObjects) {
			$collection = $collection->map(fn($contact) => $contact->getData());
		}

		return $collection->toArray();
	}

	/*
	 * Alle Provisionsgruppen der Agentur
	 */
	public function getProvisionGroups(){
		return Ext_Thebing_Util::convertDataIntoObject($this->provision_groups, 'Ext_Thebing_Agency_Provision_Group');
	}

	/*
	 * Funktion liefert die Platzhalter Tabelle für die Agentur übersichts PDFs (CRM)
	 */
	public function getPlaceholderOverview($bFormatTable = false, $bWithRevenues=true) {

		$aList = array();
		$oClient = Ext_Thebing_Client::getFirstClient();
		$aColumnTitles = Ext_Thebing_Management_Statistic_Gui2::getColumns();
		$aSchools = (array)$oClient->getSchools();

		if($bWithRevenues) {
			$aColumns = [
				6, // Schüler gesamt
				70, // Kurswochen gesamt
				173, // Umsätze gesamt (netto, inkl. Storno)
				63 // Provision gesamt
			];
		} else {
			$aColumns = [
				6, // Schüler gesamt
				70, // Kurswochen gesamt
			];
		}

		// Fake-Statistik bauen, damit man an die Werte kommt
		$oStatistic = new Ext_Thebing_Management_Statistic();
		$oStatistic->columns = array('cols' => $aColumns);
		$oStatistic->list_type = 1; // Anzeigen als: Summe
		$oStatistic->type = 2;  // Statistik-Typ: absolut
		$oStatistic->interval = 1; // Zeitrahmen: Jahr
		$oStatistic->period = 1; // Basierend auf: Buchungsdatum
		$oStatistic->agency = 1; // Agenturkunden
		$oStatistic->group_by = 1; // Filtern nach: Agenturen
		$oStatistic->agencies = array($this->id); // Nur diese Agentur

		foreach($aSchools as $aSchool) {
			$iSchoolID = $aSchool['id'];

			// Werte individuell pro Schule
			$oStatistic->currency_id = $aSchool['currency'];
			$oStatistic->schools = array($iSchoolID);

			$aList[$iSchoolID] = array(
				'title'	=> $aSchool['ext_1'],
				'data'	=> array()
			);

			$iStartTime = mktime(0, 0, 0, 1, 1, date('Y') - 2);
			$iEndTime = mktime(0, 0, 0, 12, 31, date('Y'));

			$oStatisticBlock = new Ext_Thebing_Management_PageBlock($oStatistic, $iStartTime, $iEndTime);
			$oStatisticBlock->bFormat = false;

			$oResult = $oStatisticBlock->getResults(array(), true);
			$aLabels = $oResult->getLabels();
			$aResult = $oResult->getData();

			// $aLabels muss iteriert werden, da dort die Jahre drin stehen
			// $aLabels muss außerdem umgedreht werden, damit die Jahre die richtige Reihenfolge haben für den Platzhalter
			foreach(array_reverse($aLabels, true) as $mKey => $aLabelData) {
				if($mKey === '-') {
					continue; // Summenzeile ignorieren
				}

				$iYear = (int)substr($aLabelData['until'], 0, 4);
				$aData = $aResult[$mKey][0][null][null];

				foreach($aColumns as $iColumnId) {

					if(isset($aData[$iColumnId])) {
						$mValue = $aData[$iColumnId];
					} else {
						// Wert setzen, sonst steht gar nichts in der Zelle
						$mValue = 0;
					}

					$aList[$iSchoolID]['data'][$iYear][$iColumnId] = array(
						'title' => $aColumnTitles[$iColumnId],
						'value' => $mValue
					);
				}
			}


		}

		if($bFormatTable) {
			return $this->getFormatedPlaceholderOverview($aList);
		} else {
			return $aList;
		}
	}

	/**
	 * Agentur-CRM-Statistik: Schüler in der Schule
	 *
	 * Basiert auf absolutem Leistungszeitraum der Buchung.
	 *
	 * @param string $sLanguage
	 * @return string
	 */
	public function getPlaceholderStudentsAtSchool($sLanguage) {

		$sSql = "
			SELECT
				`cdb2`.`ext_1` `school_name`,
				`tc_c`.`lastname`,
				`tc_c`.`firstname`,
				`tc_c`.`gender`,
				GROUP_CONCAT(`ktc`.`name_short` SEPARATOR ', ') `courses`,
				MIN(`ts_ijc`.`from`) `course_start`,
				MAX(`ts_ijc`.`until`) `course_end`,
				SUM(`ts_ijc`.`weeks`) `course_weeks`
			FROM
				`ts_inquiries` `ts_i` INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
					`ts_ij`.`active` = 1 AND
					`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' INNER JOIN
				`ts_inquiries_journeys_courses` `ts_ijc` ON
					`ts_ijc`.`journey_id` = `ts_ij`.`id` AND
					`ts_ijc`.`active` = 1 AND
					`ts_ijc`.`visible` = 1 INNER JOIN
				`kolumbus_tuition_courses` `ktc` ON
					`ktc`.`id` = `ts_ijc`.`course_id` INNER JOIN
				`ts_inquiries_to_contacts` `ts_itc` ON
					`ts_itc`.`inquiry_id` = `ts_i`.`id` AND
					`ts_itc`.`type` = 'traveller' INNER JOIN
				`tc_contacts` `tc_c` ON
					`tc_c`.`id` = `ts_itc`.`contact_id` AND
					`tc_c`.`active` = 1 INNER JOIN
				`customer_db_2` `cdb2` ON
					`cdb2`.`id` = `ts_ij`.`school_id`
			WHERE
				`ts_i`.`active` = 1 AND
				`ts_i`.`confirmed` > 0 AND
				`ts_i`.`canceled` = 0 AND
				`ts_i`.`agency_id` = :agency_id AND
				`ts_i`.`service_from` <= NOW() AND
				`ts_i`.`service_until` >= NOW()
			GROUP BY
				`ts_i`.`id`
			ORDER BY
				`school_name`,
				`course_start`
		";

		$aResult = (array)DB::getQueryRows($sSql, ['agency_id' => $this->id]);

		foreach($aResult as &$axRow) {

			$oDummy = null;
			$axRow['customer_name'] = (new Ext_TC_Gui2_Format_Name())->format(null, $oDummy, $axRow);

			$axRow['gender'] = (new Ext_Thebing_Gui2_Format_Gender())->format($axRow['gender']);

			$oDateFormat = new Ext_Thebing_Gui2_Format_Date();
			$axRow['course_dates'] = $oDateFormat->format($axRow['course_start']).' - '.$oDateFormat->format($axRow['course_end']);

		}

		$aTranslations = [
			'school' => Ext_TC_Placeholder_Abstract::translateFrontend('Schule', $sLanguage),
			'customer_name' => Ext_TC_Placeholder_Abstract::translateFrontend('Name', $sLanguage),
			'gender' => Ext_TC_Placeholder_Abstract::translateFrontend('Geschlecht', $sLanguage),
			'courses' => Ext_TC_Placeholder_Abstract::translateFrontend('Kurse', $sLanguage),
			'course_dates' => Ext_TC_Placeholder_Abstract::translateFrontend('Kursdaten', $sLanguage),
			'course_weeks' => Ext_TC_Placeholder_Abstract::translateFrontend('Anzahl der Kurswochen', $sLanguage),
		];

		$oSmarty = new SmartyWrapper();
		$oSmarty->assign('aRows', $aResult);
		$oSmarty->assign('aTranslations', $aTranslations);

		$sPath = Util::getDocumentRoot().'system/extensions/thebing/agency/placeholder/template/students_in_school.tpl';
		$sHtml = $oSmarty->fetch($sPath);

		return $sHtml;

	}

	/**
	 * Agentur-CRM-Statistik: Fällige Zahlungen
	 *
	 * @param string $sLanguage
	 * @return string
	 */
	public function getPlaceholderDuePayments($sLanguage) {

		$oLanguage = new Tc\Service\Language\Frontend($sLanguage);
		$oLanguage->setContext('Fidelo » Agencies');

		$oSearch = new \ElasticaAdapter\Facade\Elastica(\ElasticaAdapter\Facade\Elastica::buildIndexName('ts_document'));

		$oQuery = new \Elastica\Query\Term();
		$oQuery->setTerm('agency_id', $this->id);
		$oSearch->addQuery($oQuery);

		$types = array_keys(Ext_Thebing_Inquiry_Document_Search::getTypeData(['invoice_netto_without_proforma', 'creditnote']));
		$oQuery = new \Elastica\Query\Terms('type_original', $types);
		$oSearch->addQuery($oQuery);

		$aFields = [
			'id',
			'document_number_original',
			'school_id',
			'customer_name',
			'customer_number',
			'first_course_start',
			'course_last_end_date',
			'amount_open_original',
			'currency_id',
			'type_original'
		];

		$oDueQuery = Ext_TS_Inquiry_Index_Gui2_Data::getPaymentTypeFilterOptionQuery('due');
		$oSearch->addQuery($oDueQuery);

		$oSearch->setFields($aFields);
		$oSearch->setSort(['document_number_original' => 'asc']);
		$oSearch->setLimit(1000);
		$aResult = $oSearch->search();

		$aSums = [];

		$aDocuments = array_map(function (array $aDocument) use ($oLanguage, &$aSums) {

			$aDocument = array_map(function (array $aValue) {
				return reset($aValue);
			}, $aDocument['fields']);

			$bCreditnote = $aDocument['type_original'] === 'creditnote';

			$aDocument['document_number'] = $aDocument['document_number_original'];
			$aDocument['school'] = Ext_Thebing_School::getInstance($aDocument['school_id'])->short;
			$aDocument['type'] = $oLanguage->translate($bCreditnote ? 'CN' : 'Re.');
			$aDocument['course_dates'] = $aDocument['first_course_start'].' - '.$aDocument['course_last_end_date'];

			if ($bCreditnote) {
				$aDocument['amount_open_original'] *= -1;
			}

			if (!isset($aSums)) {
				$aSums[$aDocument['currency_id']] = 0;
			}

			$aDocument['amount_open'] = Ext_Thebing_Format::Number($aDocument['amount_open_original'], $aDocument['currency_id']);
			$aSums[$aDocument['currency_id']] += $aDocument['amount_open_original'];

			return $aDocument;

		}, $aResult['hits']);

		$aSums = array_map(function (float $fAmount, int $iCurrencyId) {
			return Ext_Thebing_Format::Number($fAmount, $iCurrencyId);
		}, $aSums, array_keys($aSums));

		$aConfiguredColumns = (array)unserialize(System::d('ts_agency_crm_due_payments_columns'));

		$aColumns = array_filter(Ext_Thebing_Agency_Placeholderoverview::getPlaceholderDuePayments($oLanguage), function (string $sKey) use ($aConfiguredColumns) {
			return in_array($sKey, $aConfiguredColumns);
		}, ARRAY_FILTER_USE_KEY);

		$oSmarty = new SmartyWrapper();
		$oSmarty->assign('columns', $aColumns);
		$oSmarty->assign('documents', $aDocuments);
		$oSmarty->assign('totals', $aSums);
		$oSmarty->assign('column_widths', ['type' => '10%']);
		$oSmarty->assign('translations', [
			'total_amount' => $oLanguage->translate('Summe')
		]);

		$sPath = Util::getDocumentRoot().'system/extensions/thebing/agency/placeholder/template/due_payments.tpl';
		$sHtml = $oSmarty->fetch($sPath);

		return $sHtml;

	}

	/**
	 * Formatiert den Statistic Platzhalter als Tabelle für PDF
	 *
	 * @param $aList
	 * @return string
	 */
	private function getFormatedPlaceholderOverview($aList) {

		$oDivContent = new Ext_Gui2_Html_Div();

		foreach((array)$aList as $iSchool => $aSchoolData){

			$oSchool = Ext_Thebing_School::getInstance($iSchool);


			$oDivContent->setElement('<h4>'.$aSchoolData['title'].'</h4>');

			$oTable = new Ext_Gui2_Html_Table();
			$oTable->cellpadding = '2';
			$oTable->class = 'table tblDocumentTable ';

			// Jahre durchgehen
			$iFirstRow = true;
			foreach((array)$aSchoolData['data'] as $sYear => $aRows){

				// Erste Zeile sind die Überschriften
				if($iFirstRow){
					$oTr = new Ext_Gui2_Html_Table_tr();
						$oTh = new Ext_Gui2_Html_Table_Tr_Th();
						$oTh->setElement((string)L10N::t('Jahr', 'Thebing » Placeholder'));
						$oTh->style = 'border-bottom: 1px solid black';
					$oTr->setElement($oTh);

					//Spalten durchgehen
					foreach((array)$aRows as $iRowId => $aRowData){
							$oTh = new Ext_Gui2_Html_Table_Tr_Th();
							$oTh->style = 'border-left: 1px solid black; border-bottom: 1px solid black';
							$oTh->setElement((string)$aRowData['title']);
						$oTr->setElement($oTh);
					}
					$oTable->setElement($oTr);

					$iFirstRow = false;
				}

				$oTr = new Ext_Gui2_Html_Table_tr();
					$oTd = new Ext_Gui2_Html_Table_Tr_Td();
					$oTd->setElement((string)$sYear);
				$oTr->setElement($oTd);
				//Spalten durchgehen
				foreach((array)$aRows as $iRowId => $aRowData){
						if(
							$iRowId == 63 ||
							$iRowId == 173
						){
							$sValue = Ext_Thebing_Format::Number((float)$aRowData['value'], $oSchool->getCurrency(), $oSchool->id);
						}else{
							$sValue = (int)$aRowData['value'];
						}

						$oTd = new Ext_Gui2_Html_Table_Tr_Td();
						$oTd->style = 'border-left: 1px solid black;';
						$oTd->setElement((string)$sValue);
					$oTr->setElement($oTd);
				}
				$oTable->setElement($oTr);
			}

			$oDivContent->setElement($oTable);
		}

		$sHTML = $oDivContent->generateHTML();
		return $sHTML;
	}

	/*
	 * Liefert den PDF Platzhalter der Provisionsgruppen zurück
	 */

	public function getPlaceholderProvisiongroups(){
		$aGroups = $this->getProvisionGroups();
		$oFormatDate = new Ext_Thebing_Gui2_Format_Date();


		$oDivContent = new Ext_Gui2_Html_Div();
		$oDivContent->setElement('<h4>'.L10N::t('Provisionsgruppen', 'Thebing » Placeholder').'</h4>');

		// Tabelle aufbauen
		$oTable = new Ext_Gui2_Html_Table();
		$oTable->cellpadding = '2';
		$oTable->class = 'table tblDocumentTable ';

		$oTr = new Ext_Gui2_Html_Table_tr();
			$oTh = new Ext_Gui2_Html_Table_Tr_Th();
			$oTh->setElement((string)L10N::t('Provisionsgruppe', 'Thebing » Placeholder'));
			$oTh->style = 'border-bottom: 1px solid black';
		$oTr->setElement($oTh);
			$oTh = new Ext_Gui2_Html_Table_Tr_Th();
			$oTh->setElement((string)L10N::t('Von', 'Thebing » Placeholder'));
			$oTh->style = 'border-left: 1px solid black; border-bottom: 1px solid black';
		$oTr->setElement($oTh);
			$oTh = new Ext_Gui2_Html_Table_Tr_Th();
			$oTh->setElement((string)L10N::t('Bis', 'Thebing » Placeholder'));
			$oTh->style = 'border-left: 1px solid black; border-bottom: 1px solid black';
		$oTr->setElement($oTh);
		$oTable->setElement($oTr);

		foreach((array)$aGroups as $oGroup){
			$sName = '';
			$oProvisionGroup = $oGroup->getProvisionGroup();
			if($oProvisionGroup){
				$sName = $oProvisionGroup->name;
			}

			$oTr = new Ext_Gui2_Html_Table_tr();
				$oTd = new Ext_Gui2_Html_Table_Tr_Td();
				$oTd->setElement((string)$sName);
				$oTd->style = '';
			$oTr->setElement($oTd);
				$oTd = new Ext_Gui2_Html_Table_Tr_Td();
				$oTd->setElement((string)$oFormatDate->format($oGroup->valid_from));
				$oTd->style = 'border-left: 1px solid black;';
			$oTr->setElement($oTd);
				$oTd = new Ext_Gui2_Html_Table_Tr_Td();
				$oTd->setElement((string)$oFormatDate->format($oGroup->valid_until));
				$oTd->style = 'border-left: 1px solid black;';
			$oTr->setElement($oTd);
			$oTable->setElement($oTr);
		}		

		$oDivContent->setElement($oTable);

		$sHTML = $oDivContent->generateHTML();
		return $sHTML;
	}

	/**
	 * Erzeugt ein Query für eine Liste mit Items dieses Objektes
	 * @return array
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		parent::manipulateSqlParts($aSqlParts, $sView);

		$aSqlParts['select'] .= ",
					## aktuelle Provisionsgruppe
					(
						{$this->getCurrentCategoryQueryForType('commission_category', "`kpg`.`name`", "`ka`.`id`")}
					) `provision_group_name`,
					## aktuelle Bezahlgruppe
					(
						{$this->getCurrentCategoryQueryForType('payment_category', "GROUP_CONCAT(DISTINCT `ts_pc`.`name` SEPARATOR ', ')", "`ka`.`id`")}
					) `payment_group_name`,
					## aktuelle Stornokategorie
					(
						{$this->getCurrentCategoryQueryForType('cancellation_category', "`kcg`.`name`", "`ka`.`id`")}
					) `storno_group_name`,
					GROUP_CONCAT(DISTINCT `groups`.`group_id`) `groups` 
					";


		$aSqlParts['from'] .= " LEFT JOIN
					`kolumbus_agency_groups` `kag` ON
						`kag`.`id` = `groups`.`group_id` AND
						`kag`.`active` = 1
					";


		// Unteragentur
		$aSqlParts['select'] .= ", wb_a_subagency.value subagency_id ";
		$aSqlParts['from'] .= " LEFT JOIN 
					`wdbasic_attributes` `wb_a_subagency` ON
						`wb_a_subagency`.`entity` = 'ts_companies' AND
						`wb_a_subagency`.`entity_id` = `ka`.`id` AND
						`wb_a_subagency`.`key` = 'subagency_id'
						";
		
	}

	/**
	 * @param int $iSchoolId
	 * @return Ext_TS_Payment_Condition|null
	 */
	public function getValidPaymentCondition($iSchoolId = null) {

		$sSql = "
			SELECT
				`payment_condition_id`
			FROM
				`ts_agencies_payment_conditions_validity`
			WHERE
				`active` = 1 AND
				`agency_id` = :agency_id AND (
					`valid_until` = '0000-00-00' OR
					:date BETWEEN `valid_from` AND `valid_until`
				)
		";

		if($iSchoolId !== null) {
			$sSql .= "
				AND (
					`school_id` IS NULL OR
					`school_id` = 0 OR
					`school_id` = :school_id
				)
			";
		}

		$iPaymentConditionId = DB::getQueryOne($sSql, [
			'agency_id' => $this->id,
			'school_id' => $iSchoolId,
			'date' => (new DateTime())->format('Y-m-d')
		]);

		if($iPaymentConditionId !== null) {
			return Ext_TS_Payment_Condition::getInstance($iPaymentConditionId);
		}

		return null;

	}

	/**
	 * Achtung: Methode ist quasi redundant mit Ext_Thebing_Inquiry_Document_Version_Item::getNewProvisionAmount()
	 *
	 * @TODO Vereinen mit Ext_Thebing_Inquiry_Document_Version_Item::getNewProvisionAmount()
	 * @see Ext_Thebing_Inquiry_Document_Version_Item::getNewProvisionAmount()
	 * @param Ext_TS_Inquiry_Abstract $oInquiry
	 * @param float $fAmount
	 * @param array $aOptions
	 * @return float
	 */
	public function getNewProvisionAmountByType(Ext_TS_Inquiry_Abstract $oInquiry, $fAmount, $aOptions, $documentType=null) {

		$oSchool = $oInquiry->getSchool();
		$sType = $aOptions['type'];
		$iTypeId = (int)$aOptions['type_id'];
		$iParentBookingId = (int)$aOptions['parent_booking_id'];
		$sAdditional = $aOptions['additional'];

		$oProvision = null;

		if($documentType === 'creditnote_subagency') {
			
			$commissionAmount = 0;
			
			if($sType === 'course' ) {

				$commissionAmount = $fAmount * $this->subagency_commission/100;

			}
			
			return round($commissionAmount, 2);
		}
		
		switch($sType) {
			case 'course':
				if($iTypeId > 0){
					$oInquiryCourse = $oInquiry->getServiceObject($sType, $iTypeId);
					$oSeason = $oInquiryCourse->getSeason();
					$oSchoolProvision = $this->getSchoolProvisions($oSeason->id);

					$oProvision = $oSchoolProvision->getCourseProvision($oInquiryCourse->course_id);
				}
				break;
			case 'accommodation':
				if($iTypeId > 0){
					$oInquiryAccommodation = $oInquiry->getServiceObject($sType, $iTypeId);
					$oSeason = $oInquiryAccommodation->getSeason();
					$oSchoolProvision = $this->getSchoolProvisions($oSeason->id);

					$oProvision = $oSchoolProvision->getAccommodationProvision($oInquiryAccommodation->accommodation_id, $oInquiryAccommodation->roomtype_id, $oInquiryAccommodation->meal_id);
				}
				break;
			case 'extra_nights':
					$aAccommodations = $oInquiry->getAccommodations();
					if(!empty($aAccommodations)){
						$oInquiryAccommodation = reset($aAccommodations);
						$oSeason = $oInquiryAccommodation->getSeason();
						$oSchoolProvision = $this->getSchoolProvisions($oSeason->id);

						$oProvision = $oSchoolProvision->getExtraNightProvision($oInquiryAccommodation->accommodation_id, $oInquiryAccommodation->roomtype_id, $oInquiryAccommodation->meal_id);
					}
				break;
			case 'additional_course':
				if($iTypeId > 0) {
					if(empty($iParentBookingId)) {
						throw new RuntimeException('Missing $iParentBookingId for '.$sType.' in '.__METHOD__.'()');
					}

					$oService = $oInquiry->getServiceObject('course', $iParentBookingId);
					$oSeason = $oService->getSeason();
					$oSchoolProvision = $this->getSchoolProvisions($oSeason->id);

					$oProvision = $oSchoolProvision->getAdditionalProvision($iTypeId, $oService->course_id, 'course');
				}
				break;
			case 'additional_accommodation':
				if($iTypeId > 0) {
					if(empty($iParentBookingId)) {
						throw new RuntimeException('Missing $iParentBookingId for '.$sType.' in '.__METHOD__.'()');
					}

					$oService = $oInquiry->getServiceObject('accommodation', $iParentBookingId);
					$oSeason = $oService->getSeason();
					$oSchoolProvision = $this->getSchoolProvisions($oSeason->id);

					$oProvision = $oSchoolProvision->getAdditionalProvision($iTypeId, $oService->accommodation_id, 'accommodation');
				}
				break;
			case 'additional_general':

				if($iTypeId > 0) {
					$aCourses = $oInquiry->getCourses();
					$aAccommodations = $oInquiry->getAccommodations();

					if(!empty($aCourses) || !empty($aAccommodations)){

						// TODO In der getNewProvisionAmount() wird nur der Kurs (des Items) beachtet
						if(!empty($aCourses)){
							$oInquiryCourse = reset($aCourses);
							$oSeason = $oInquiryCourse->getSeason();
						} else if(!empty($aAccommodations)) {
							$oInquiryAccommodation = reset($aAccommodations);
							$oSeason = $oInquiryAccommodation->getSeason();
						} else {
							$oSaisonSearch 	= new Ext_Thebing_Saison_Search();
							$aSaisonData 	= $oSaisonSearch->bySchoolAndTimestamp($oSchool->id, time(), $oInquiry->getCreatedForDiscount());
							$iSaisonId 		= (int)$aSaisonData[0]['id'];
							$oSeason = Ext_Thebing_Marketing_Saison::getInstance($iSaisonId);
						}
						$oSchoolProvision = $this->getSchoolProvisions($oSeason->id);

						$oProvision = $oSchoolProvision->getGeneralProvision($iTypeId);
					}
				}
				break;
			case 'transfer':

				if(!empty($iTypeId)) {

					// Einfachtransfer oder Hin- und Rücktransfer
					$bTwoWay = false;
					if(strpos($iTypeId, '_') !== false) {
						$bTwoWay = true;
					}					

					// Erste ID holen
					list($iTypeId) = explode('_', $iTypeId);

					$oTransfer = $oInquiry->getServiceObject($sType, $iTypeId);
					$oSeason = $oTransfer->getSeason();
					$oSchoolProvision = $this->getSchoolProvisions($oSeason->id);

					$oProvision = $oSchoolProvision->getTransferProvision($oTransfer, $bTwoWay);

				}
				break;
			case 'special':
				$aAdditionalData = json_decode($sAdditional);
				$oObject = $oInquiry->getServiceObject($aAdditionalData->type, $aAdditionalData->type_id);
				if($oObject instanceof Ext_TS_Service_Abstract) {
					$oSeason = $oObject->getSeason();
					$oSchoolProvision = $this->getSchoolProvisions($oSeason->id);
					if($aAdditionalData->type == 'course') {
						$oProvision = $oSchoolProvision->getCourseProvision($oObject->course_id);
					} else if($aAdditionalData->type == 'accommodation') {
						$oProvision = $oSchoolProvision->getAccommodationProvision($oObject->accommodation_id, $oObject->roomtype_id, $oObject->meal_id);
					}
				}
				break;
			case 'activity':
				$oInquiryActivity = $oInquiry->getServiceObject($sType, $iTypeId);
				$oSeason = $oInquiryActivity->getSeason();				
				$oSchoolProvisionCourse = $this->getSchoolProvisions($oSeason->id);
				$oProvision = $oSchoolProvisionCourse->getActivityCommission($oInquiryActivity->getActivity());
				break;
		}

		$fProvision = 0;
		if ($oProvision) {
			$fProvision = $oProvision->calculate((float)$fAmount);
		}

		// Hook existiert auch in Ext_Thebing_Inquiry_Document_Version_Item::getNewProvisionAmount()
		$aOptions['amount'] = $fAmount;
		$aHookData = ['item' => $aOptions, 'commission' => &$fProvision];
		System::wd()->executeHook('ts_inquiry_document_get_item_commission', $aHookData);

		// Betrag runden (analog zu Ext_Thebing_Inquiry_Document_Version_Item::getNewProvisionAmount())
		return round($fProvision, 2);
	}

	public function getOpenManualCreditNotes($iCurrency = 0){

		$aSql = array('id' => (int)$this->id);

		$sSql = " SELECT
						`kamc`.`id`
					FROM
						`kolumbus_agencies_manual_creditnotes` `kamc` LEFT JOIN
						`kolumbus_agencies_manual_creditnotes_payments` `kamcp` ON
							`kamcp`.`creditnote_id` = `kamc`.`id` AND
							`kamcp`.`active` = 1
					WHERE
						`kamc`.`agency_id` = :id AND
						`kamc`.`amount` > 0 AND
						`kamc`.`storno_id` = 0 AND
						`kamc`.`active` = 1 AND
						`kamc`.`amount` >
						-- Runden wegen Ungenauigkeit
						ROUND(
							COALESCE(
								(
									SELECT
										SUM(`kamcp`.`amount`)
									FROM
										`kolumbus_agencies_manual_creditnotes_payments` `kamcp`
									WHERE
										`kamcp`.`creditnote_id` = `kamc`.`id` AND
										`kamcp`.`active` = 1
								), 0
							), 2
						)
					";
		if($iCurrency > 0){
			$sSql .= ' AND `kamc`.`currency_id` = :currency_id ';
			$aSql['currency_id'] = $iCurrency;
		}
		
		$aResult = DB::getPreparedQueryData($sSql, $aSql);
		$aBack = array();

		foreach((array)$aResult as $aData){
			$aBack[] = Ext_Thebing_Agency_Manual_Creditnote::getInstance($aData['id']);
		}

		return $aBack;
	}
	
	public function getCreditnotesForSelect(){
		
		$aBack = array();
		
		$aCreditnotes = $this->getJoinedObjectChilds('creditnotes');

		foreach((array)$aCreditnotes as $oCreditnote){
			$oVersion = $oCreditnote->getLastVersion();
			if(is_object($oVersion)){
				$oDocument = $oVersion->getDocument();
				$aBack[$oVersion->id] = $oDocument->document_number.' - '.L10N::t('Creditnote');
			}
		}
		
		
		return $aBack;
	}

	/**
	 * Nicht verrechnete Creditnotes
	 *
	 * @param int $iCurrencyId
	 * @return array|Ext_Thebing_Inquiry_Document[]
	 */
	public function getOpenCreditNotes($iCurrencyId) {

		$aBack = array();

		$sSql = "
 			SELECT
				`kid`.`id`
			FROM
				`ts_inquiries` `ki` INNER JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`entity` = '".Ext_TS_Inquiry::class."' AND
					`kid`.`entity_id` = `ki`.`id` AND
					`kid`.`type` = 'creditnote' AND
					`kid`.`active` = 1 INNER JOIN
				`kolumbus_inquiries_documents_versions_items` `kidvi` ON
					`kidvi`.`version_id` = `kid`.`latest_version`
			WHERE
				`ki`.`active` = 1 AND
				`ki`.`agency_id` = :id AND
				`ki`.`currency_id` = :currency AND
				COALESCE(
					(
						SELECT
							SUM(`kipi`.`amount_inquiry`) * -1 `amount`
						FROM
							`kolumbus_inquiries_payments` `kip` INNER JOIN
							`kolumbus_inquiries_payments_items` `kipi` ON
								`kipi`.`payment_id` = `kip`.`id` INNER JOIN
							`kolumbus_inquiries_documents_versions_items` `kidvi` ON
								`kidvi`.`id` = `kipi`.`item_id` INNER JOIN
							`kolumbus_inquiries_documents_versions` `kidv` ON
								`kidv`.`id` = `kidvi`.`version_id` INNER JOIN
							`kolumbus_inquiries_documents` `kid_sub` ON
								`kid_sub`.`id` = `kidv`.`document_id`
						WHERE
							`kipi`.`active` = 1 AND
							`kip`.`active` = 1 AND
							`kid_sub`.`id` = `kid`.`id`
						GROUP BY
							`kid_sub`.`id`
					), 0
				) <
				ROUND -- Wegen Rundungsfehlern auf vier Nachkommastellen runden
				(
					COALESCE((
						SELECT
							SUM(
								IF(
									`kidvi_sub`.`amount_discount` > 0,
									(
										## Discount ausrechnen
										`kidvi_sub`.`amount_provision` -
										(
											`kidvi_sub`.`amount_provision` / 100 * `kidvi_sub`.`amount_discount`
										)
									),
									`kidvi_sub`.`amount_provision`
								) *
								IF(
									`kidv_sub`.`tax` = 2,
									(
										(`kidvi_sub`.`tax` / 100) + 1
									),
									1
								)
							)
						FROM
							`kolumbus_inquiries_documents_versions` `kidv_sub` INNER JOIN
							`kolumbus_inquiries_documents_versions_items` `kidvi_sub` ON
								`kidvi_sub`.`version_id` = `kidv_sub`.`id`
						WHERE
							`kidv_sub`.`document_id` = `kid`.`id` AND
							`kidv_sub`.`id` = `kid`.`latest_version` AND
							`kidvi_sub`.`onPDF` = 1 AND
							`kidvi_sub`.`active` = 1
					), 0)
				, 4)
			GROUP BY
				`kid`.`id`
		";

		$aResult = (array)DB::getPreparedQueryData($sSql, array(
			'id' => (int)$this->id,
			'currency' => $iCurrencyId
		));

		foreach($aResult as $aData) {
			$aBack[] = Ext_Thebing_Inquiry_Document::getInstance($aData['id']);
		}

		return $aBack;
	}
	
	/**
	 * Liste aller Agenturkategorienn
	 * @global type $user_data
	 * @param type $bForSelect
	 * @return type 
	 */
	public static function getCategoryList($bForSelect = true){
		global $user_data;
		
		$sSql = " SELECT 
						* 
					FROM 
						`kolumbus_agency_categories` 
					WHERE
						`active` = 1 ";
		$aSql = array();


		$aResult = DB::getPreparedQueryData($sSql,$aSql);
		
		if(!$bForSelect){
			return $aResult;
		} else {
			$aBack = array();
			foreach($aResult as $aData){
				$aBack[$aData['id']] = $aData['name'];
			}
			return $aBack;
		}
		
	}

	/**
	 * @param bool $bLog
	 * @return type
	 */
	public function save($bLog = true) {

		$sCacheKey = self::getAgencyPaymentsCurrenciesContactCacheKey();
		WDCache::delete($sCacheKey);

		$aTransfer = parent::save($bLog);

		// TODO Hook im Bundle?
		if(\TsSalesForce\Service\Agency::isActive()) {
			$this->writeSalesForceToStack();
		}

		System::wd()->executeHook('ts_agency_save', $this);

		return $aTransfer;
	}

	/**
	 * @return string
	 */
	public function getCategoryName() {
		$oCategory = $this->getCategory();
		if ($oCategory) {
			return $oCategory->getName();
		}

		return '';

	}

	public function getCategory(): ?Ext_Thebing_Agency_Category {
		if ($this->ext_39 > 0) {
			return Ext_Thebing_Agency_Category::getInstance($this->ext_39);
		}
		return null;
	}

	/**
	 * Schreibt die Agentur Id in den Stack um die Agenturinformationen zu SalesForce zu schicken.
	 * TODO: Hook?
	 *
	 * @return bool
	 * @throws Exception
	 */
	private function writeSalesForceToStack() {

		$iId = $this->_aData['id'];

		$oStackRepository = \Core\Entity\ParallelProcessing\Stack::getRepository();
		$iStackId = $oStackRepository->writeToStack('ts-sales-force/sales-force-transfer', ['agency_id' => $iId], 10);

		if ($iStackId === 0) {
			return false;
		}

		return true;

	}

	/**
	 * Query für aktuelle Provisionskategorie/Bezahlkategorie/Stornokategorie
	 *
	 * @param string $sType
	 * @param string $sSelect
	 * @param string $sIdField
	 * @return string
	 */
	public static function getCurrentCategoryQueryForType($sType, $sSelect, $sIdField) {

		if($sType === 'commission_category') {

			return "
				SELECT
					{$sSelect}
				FROM
					`ts_agencies_to_commission_categories` `kapg` INNER JOIN
					`ts_commission_categories` `kpg` ON
						`kpg`.`id` = `kapg`.`group_id` AND
						`kpg`.`active` = 1
				WHERE
					`kapg`.`agency_id` = {$sIdField} AND
					`kapg`.`active` = 1 AND
					(
						DATE(NOW()) BETWEEN `kapg`.`valid_from` AND `kapg`.`valid_until` OR
						(
							DATE(NOW()) >= `kapg`.`valid_from` AND
							`kapg`.`valid_until` = '0000-00-00'
						)
					)
				LIMIT 1
			";

		} elseif($sType === 'payment_category') {

			return "
				SELECT
					{$sSelect}
				FROM
					`ts_agencies_payment_conditions_validity` `ts_apcv` INNER JOIN
					`ts_payment_conditions` `ts_pc` ON
						`ts_pc`.`id` = `ts_apcv`.`payment_condition_id`
				WHERE
					`ts_apcv`.`agency_id` = {$sIdField} AND
					`ts_apcv`.`active` = 1 AND
					(
						DATE(NOW()) BETWEEN `ts_apcv`.`valid_from` AND `ts_apcv`.`valid_until` OR
						(
							DATE(NOW()) >= `ts_apcv`.`valid_from` AND
							`ts_apcv`.`valid_until` = '0000-00-00'
						)
					)
			";

		} elseif($sType === 'cancellation_category') {

			return "
				SELECT
					{$sSelect}
				FROM
					`kolumbus_validity` `kv` LEFT JOIN
					`tc_cancellation_conditions_groups` `kcg` ON
						`kcg`.`id` = `kv`.`item_id`
				WHERE
					`kv`.`parent_id` = {$sIdField} AND
					`kv`.`parent_type` = 'agency' AND
					`kv`.`active` = 1 AND
					`kv`.`item_type` = 'cancellation_group' AND
					(
						DATE(NOW()) BETWEEN `kv`.`valid_from` AND `kv`.`valid_until` OR
						(
							DATE(NOW()) >= `kv`.`valid_from` AND
							`kv`.`valid_until` = '0000-00-00'
						)
					)
				LIMIT 1
			";

		} else {
			throw new InvalidArgumentException('Invalid type');
		}

	}

	/**
	 * Aktuelle Provisionskategorie der Agentur (nicht die Zwischentabelle!)
	 *
	 * @see Ext_Thebing_Agency_Provision_Group
	 * @return Ext_Thebing_Provision_Group|null
	 */
	public function getCurrentCommissionCategory() {
		$iCategoryId = (int)DB::getQueryOne(self::getCurrentCategoryQueryForType('commission_category', "`kpg`.`id`", $this->id));
		if(!empty($iCategoryId)) {
			return Ext_Thebing_Provision_Group::getInstance($iCategoryId);
		}

		return null;
	}

	public function getCurrentCommissionValidity() {
		$validityId = (int)DB::getQueryOne(self::getCurrentCategoryQueryForType('commission_category', "`kapg`.`id`", $this->id));
		return Ext_Thebing_Agency_Provision_Group::getInstance($validityId);
	}
	
	/**
	 * Aktuelle Bezahlkategorie der Agentur (nicht die Zwischentabelle!)
	 *
	 * @return Ext_TS_Payment_Condition|null
	 */
	public function getCurrentPaymentCategory() {
		$iCategoryId = (int)DB::getQueryOne(self::getCurrentCategoryQueryForType('payment_category', "`ts_pc`.`id`", $this->id));
		if(!empty($iCategoryId)) {
			return Ext_TS_Payment_Condition::getInstance($iCategoryId);
		}

		return null;
	}

	public function getCurrentPaymentValidity() {
		$validityId = (int)DB::getQueryOne(self::getCurrentCategoryQueryForType('payment_category', "`ts_apcv`.`id`", $this->id));
		return Ext_TS_Agency_PaymentConditionValidity::getInstance($validityId);
	}
	
	/**
	 * Aktuelle Stornokategorie der Agentur (nicht die Zwischentabelle!)
	 *
	 * @see Ext_Thebing_Agency_Provision_Group
	 * @return Ext_Thebing_Cancellation_Group|null
	 */
	public function getCurrentCancellationCategory() {
		$iCategoryId = (int)DB::getQueryOne(self::getCurrentCategoryQueryForType('cancellation_category', "`kcg`.`id`", $this->id));
		if(!empty($iCategoryId)) {
			return Ext_Thebing_Cancellation_Group::getInstance($iCategoryId);
		}

		return null;
	}

	public function getCurrentCancellationValidity() {
		$validityId = (int)DB::getQueryOne(self::getCurrentCategoryQueryForType('cancellation_category', "`kv`.`id`", $this->id));
		return Ext_Thebing_Validity_WDBasic::getInstance($validityId);
	}
	
	/**
	 * Alle Specials, die der Agentur zugewiesen sind
	 *
	 * @return Ext_Thebing_School_Special[]
	 */
	public function getSpecials() {

		$sSql = "
			SELECT
				`ks`.*
			FROM
				`ts_specials` `ks` LEFT JOIN
				`ts_specials_agencies_country` `ksacn` ON
					`ks`.`agency_grouping` = 2 AND
					`ksacn`.`special_id` = `ks`.`id` LEFT JOIN
				`ts_specials_agencies_group` `ksag` ON
					`ks`.`agency_grouping` = 3 AND
					`ksag`.`special_id` = `ks`.`id` LEFT JOIN
				`ts_specials_agencies_categories` `ksac` ON
					`ks`.`agency_grouping` = 4 AND
					`ksac`.`special_id` = `ks`.`id` LEFT JOIN
				`ts_specials_agencies` `ksa` ON
					`ks`.`agency_grouping` = 5 AND
					`ksa`.`special_id` = `ks`.`id` LEFT JOIN
				`ts_specials_countries_group` `kscg` ON
					`ks`.`agency_grouping` = 6 AND
					`kscg`.`special_id` = `ks`.`id` INNER JOIN
				`ts_companies` `ka` ON
					`ka`.`id` = :id LEFT JOIN
				`kolumbus_agency_groups_assignments` `kaga` ON
					`kaga`.`agency_id` = `ka`.`id`
			WHERE
				`ks`.`active` = 1 AND
				`ks`.`agency_bookings` = 1 AND (
					/* Alle Agenturen */
					`ks`.`agency_grouping` = 1 OR (
						/* Agenturland */
						`ks`.`agency_grouping` = 2 AND
						`ksacn`.`agency_country_id` = `ka`.`ext_6`
					) OR (
						/* Agenturgruppen */
						`ks`.`agency_grouping` = 3 AND
						`ksag`.`agency_group_id` = `kaga`.`group_id`
					) OR (
						/* Agenturkategorie */
						`ks`.`agency_grouping` = 4 AND
						`ksac`.`agency_category_id` =  `ka`.`ext_39`
					) OR (
						/* Agenturen */
						`ks`.`agency_grouping` = 5 AND
						`ksa`.`agency_id` = `ka`.`id`
					) OR (
						/* Ländergruppen */
						`ks`.`agency_grouping` = 6 AND
						`kscg`.`country_group_id` = `ka`.`id`
					)
				)
			GROUP BY
				`ks`.`id`
				
		";

		$aResult = (array)DB::getQueryRows($sSql, ['id' => $this->id]);
		$aResult = array_map(function(array $aRow) {
			return Ext_Thebing_School_Special::getObjectFromArray($aRow);
		}, $aResult);

		return $aResult;

	}

	/**
	 * Alle Beschwerden, die der Agentur über die Buchungen zugewiesen sind
	 *
	 * @return \TsComplaints\Entity\Complaint[]
	 */
	public function getComplaints() {

		$sSql = "
			SELECT
				`tc_cs`.*
			FROM
				`tc_complaints` `tc_cs` INNER JOIN
				`ts_inquiries` `ts_i` ON
					`ts_i`.`id` = `tc_cs`.`inquiry_id` AND
					`ts_i`.`active` = 1
			WHERE
				`tc_cs`.`active` = 1 AND
				`ts_i`.`agency_id` = :id
		";

		$aResult = (array)DB::getQueryRows($sSql, ['id' => $this->id]);
		$aResult = array_map(function(array $aRow) {
			return \TsComplaints\Entity\Complaint::getObjectFromArray($aRow);
		}, $aResult);

		return $aResult;

	}

	public static function booted() {

		static::addGlobalScope('type', function (Builder $builder) {
			$builder->where($builder->getModel()->qualifyColumn('type'), '&', AbstractCompany::TYPE_AGENCY);
		});

	}

	public function getAvailableCommissionCategories($returnSelectOptions=false) {
		
		$query = Ext_Thebing_Provision_Group::query()
			->where('active', '=', 1)
			->orderBy('name');

		if($this->schools_limited) {
			$query->where(function(Builder $query) {
				$query->whereIn('school_id', $this->schools)
					->orWhereNull('school_id');
			});
		}

		if($returnSelectOptions) {
			return $query->pluck('name', 'id')->toArray();
		}
			
		return $query->get();
	}

	public function getCommunicationDefaultApplication(): string
	{
		return \TsCompany\Communication\Application\Agency::class;
	}

	public function getCommunicationLabel(\Tc\Service\LanguageAbstract $l10n): string
	{
		return $this->getName();
	}

	public function getCommunicationSubObject(): CommunicationSubObject
	{
		return \Ext_Thebing_School::getSchoolFromSession();
	}
}
