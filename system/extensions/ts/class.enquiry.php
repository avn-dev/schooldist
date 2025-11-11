<?php

/**
 * @property integer $editor_id
 * @property integer $creator_id
 * @property integer $school_id
 * @property integer $referer_id
 * @property string $profession
 * @property string $social_security_number
 * @property string $payment_method_comment
 * @property integer $currency_id
 * @property string $promotion_code
 * @property integer $status_id
 * @property string $firstname
 * @property string $lastname
 * @property integer $gender
 * @property string $nationality
 * @property string $language
 * @property string $corresponding_language
 * @property string $name
 * @property string $address
 * @property string $address_addon
 * @property string $zip
 * @property string $city
 * @property string $state
 * @property string $country_iso
 * @property string $email
 * @property string $course_category
 * @property string $course_intensity
 * @property string $accommodation_category
 * @property string $accommodation_room
 * @property string $accommodation_meal
 * @property string $transfer_category
 * @property string $transfer_location
 * @property string $comment
 * @property integer $frontend_log_id
 * @property integer $sales_person_id
 */
class Ext_TS_Enquiry extends Ext_TS_Inquiry_Abstract {

	public function __construct($iDataID = 0, $sTable = null) {
		throw new LogicException('Call of Ext_TS_Enquiry::__construct');
		parent::__construct($iDataID, $sTable);
	}

	public static function getInstance($iDataID = 0) {
		throw new LogicException('Call of Ext_TS_Enquiry::getInstance');
		return parent::getInstance($iDataID);
	}

	const TRANSLATION_PATH = 'Thebing » Enquiries';

	/**
	 * Tabelle
	 * @var string
	 */
	protected $_sTable = 'ts_enquiries';

	/**
	 * Tabellenalias
	 * @var string
	 */
	protected $_sTableAlias = 'ts_en';

	/**
	 * Bearbeiter Spalte
	 * @var string
	 */
	protected $_sEditorIdColumn = 'editor_id';


	/**
	 * @var string
	 */
	protected $_sOldPlaceholderClass = 'Ext_TS_Enquiry_Placeholder';

	/**
	 * @var null|bool
	 */
	protected $_bGroup = null;

	/**
	 * {@inheritdoc}
	 */
	protected $_aAttributes = array(
			'course_category' => array('class' => 'WDBasic_Attribute_Type_Text'),
			'course_intensity' => array('class' => 'WDBasic_Attribute_Type_Text'),
		
			'accommodation_category' => array('class' => 'WDBasic_Attribute_Type_Text'),
			'accommodation_room' => array('class' => 'WDBasic_Attribute_Type_Text'),
			'accommodation_meal' => array('class' => 'WDBasic_Attribute_Type_Text'),
		
			'transfer_category' => array('class' => 'WDBasic_Attribute_Type_Text'),
			'transfer_location' => array('class' => 'WDBasic_Attribute_Type_Text'),
	);

	/**
	 * @var Ext_TS_Inquiry_Contact_Traveller 
	 */
	protected $_oFirstTraveller = null;

	/**
	 * Format
	 * @var array
	 */
	protected $_aFormat = array(
		'school_id' => array(
			'required'	=> true,
			'validate'	=> 'INT_POSITIVE'
		),
		'agency_id'	=> array(
			'validate'	=> 'INT'
		),
		'referer_id'	=> array(
			'validate'	=> 'INT'
		),
	);

	/**
	 * @var array
	 */
	protected $_aJoinTables = array(
		'travellers' => array(
			'table'				=> 'ts_enquiries_to_contacts',
			'foreign_key_field'	=> 'contact_id',
	 		'primary_key_field'	=> 'enquiry_id',
			'static_key_fields'	=> array('type' => 'traveller'),
			'class'				=> 'Ext_TS_Enquiry_Contact_Traveller',
			'autoload'			=> true,
			'join_operator'		=> 'INNER JOIN',
		),
		'bookers' => array(
			'table'				=> 'ts_enquiries_to_contacts',
			'foreign_key_field'	=> 'contact_id',
	 		'primary_key_field'	=> 'enquiry_id',
			'static_key_fields'	=> array('type' => 'booker'),
			'class'				=> 'Ext_TS_Enquiry_Contact_Traveller',
			'autoload'			=> false,
		),
		'inquiries'				=> array(
			'table'				=> 'ts_enquiries_to_inquiries',
			'foreign_key_field'	=> 'inquiry_id',
	 		'primary_key_field'	=> 'enquiry_id',
			'autoload'			=> false,
		),
		// TODO Da eine Enquiry quasi eine Gruppe ist, ist das hier eigentlich sinnfrei
		'group'				=> array(
			'table'				=> 'ts_enquiries_to_groups',
			'foreign_key_field'	=> 'group_id',
	 		'primary_key_field'	=> 'enquiry_id',
			'class'				=> 'Ext_TS_Enquiry_Group',
			'autoload'			=> false,
			'bidirectional' => 'enquiry'
		),
		'documents'				=> array(
			'table'				=> 'ts_enquiries_to_documents',
			'foreign_key_field'	=> 'document_id',
	 		'primary_key_field'	=> 'enquiry_id',
			'class'				=> 'Ext_Thebing_Inquiry_Document',
			'autoload'			=> false,
			'read_only'			=> true
		),
	);

	/**
	 * {@inheritdoc}
	 */
	protected $_aJoinedObjects = array(
		'school' => [
			'class' => 'Ext_Thebing_School',
			'key' => 'school_id',
			'readonly' => true
		],
		'agency' => [
			'class'	=> 'Ext_Thebing_Agency',
			'key' => 'agency_id',
		],
		'combinations' => [
			'class' => 'Ext_TS_Enquiry_Combination',
			'key' => 'enquiry_id',
			'check_active' => true,
			'type' => 'child',
			'bidirectional' => true
		],
		'offers' => [
			'class' => 'Ext_TS_Enquiry_Offer',
			'key' => 'enquiry_id',
			'check_active' => true,
			'type' => 'child'
		]
	);

	/**
	 * @var Ext_TS_Enquiry_Offer 
	 */
	protected $_oOffer = null;

	/**
	 * @var Ext_TS_Enquiry_Combination 
	 */
	protected $_oCombination = null;

	/**
	 * @var Ext_TS_Enquiry_Contact_Traveller 
	 */
	protected $_oTraveller = null;

	/**
	 * defin if the group must be deleted
	 * @var bool
	 */
	protected $_bRemoveGroup = false;

	/**
	 * @var array
	 */
	protected $_aFlexibleFieldsConfig = [
		'enquiries_enquiries' => [],
//		'enquiries_groups' => [],
//		'groups_enquiries_bookings' => []
	];

//	/**
//	 * @var string
//	 */
//	protected $_sEntityFlexType = 'enquiry';
	
	public function __get($sName) {

		Ext_Gui2_Index_Registry::set($this);
		
		switch($sName) {
			case 'partial_invoices_terms':
				return null;
			case 'firstname':
			case 'lastname':
			case 'gender':
			case 'birthday':
			case 'nationality':
			case 'language':
			case 'corresponding_language':
			case 'name':
				$oFirstTraveller	= $this->getFirstTraveller();
				$mValue				= $oFirstTraveller->$sName;
				break;
			case 'is_group':
				$mValue = 0;
				if($this->_bGroup !== null){
					$mValue = (int)$this->_bGroup;
				} else {
					$bGroup = $this->hasGroup();
					if($bGroup){
						$mValue = 1;
					}
				}
				break;
			case 'address':
			case 'address_addon':
			case 'zip':
			case 'city':
			case 'state':
			case 'country_iso':
				$oFirstTraveller	= $this->getFirstTraveller();
				$oAddress			= $oFirstTraveller->getAddress('contact');
				$mValue				= $oAddress->$sName;
				break;
			case 'phone_private':
			case 'phone_office':
			case 'phone_mobile':
			case 'fax':
			case 'newsletter':
			case 'comment':
				$oFirstTraveller	= $this->getFirstTraveller();
				$mValue				= $oFirstTraveller->getDetail($sName);
				break;
			case 'email':
				$oFirstTraveller	= $this->getFirstTraveller();
				$mValue				= $oFirstTraveller->getEmail();
				break;
			case 'school_id':
				
				$iSchoolId = (int)$this->_aData['school_id'];
				
				if($iSchoolId <= 0)
				{
					$oClient		= Ext_Thebing_Client::getFirstClient();
					$oFirstSchool	= $oClient->getFirstSchool();
					$iSchoolId		= (int)$oFirstSchool->id;
				}
				
				$mValue = $iSchoolId;
				
				break;
			default:
				
				if(strpos($sName, 'billing') !== false)
				{
					$sBillingKey		= str_replace('billing_', '', $sName);
					$oFirstTraveller	= $this->getFirstTraveller();
					$oAddress			= $oFirstTraveller->getAddress('billing');
					$mValue				= $oAddress->$sBillingKey;
				}
				else
				{
					$mValue = parent::__get($sName);
				}
				
				break;
				
		}
		
		return $mValue;
	}
	
	public function __set($sName, $mValue) {

		switch($sName) {

			case 'partial_invoices_terms':
				return null;
			case 'is_group':
				$oGroup = $this->getGroup();
				if($oGroup === null){
					$oGroup = $this->getJoinTableObject('group', -1);
				}
				if($mValue == 0){
					$this->removeGroup();
				}
				$this->_bGroup = (boolean)$mValue;
				break;
			case 'firstname':
			case 'lastname':
			case 'gender':
			case 'birthday':
			case 'nationality':
			case 'language':
			case 'corresponding_language':
			case 'name':
				$oFirstTraveller	= $this->getFirstTraveller();
				$mValue				= $oFirstTraveller->$sName = $mValue;
				break;
			case 'address':
			case 'address_addon':
			case 'zip':
			case 'city':
			case 'state':
			case 'country_iso':
				$oFirstTraveller	= $this->getFirstTraveller();
				$oAddress			= $oFirstTraveller->getAddress('contact');
				$mValue				= $oAddress->$sName = $mValue;
				break;
			case 'phone_private':
			case 'phone_office':
			case 'phone_mobile':
			case 'fax':
			case 'newsletter':
			case 'comment':
				$oFirstTraveller	= $this->getFirstTraveller();
				$oDetail			= $oFirstTraveller->setDetail($sName, $mValue);
				break;
			case 'email':
				$oFirstTraveller	= $this->getFirstTraveller();
				$oEmail				= $oFirstTraveller->getFirstEmailAddress();
				$oEmail->master		= 1;
				$oEmail->email		= $mValue;
				break;
			default:
				
				if(strpos($sName, 'billing') !== false)
				{
					$sBillingKey		= str_replace('billing_', '', $sName);
					$oFirstTraveller	= $this->getFirstTraveller();
					$oAddress			= $oFirstTraveller->getAddress('billing');
					$oAddress->$sBillingKey = $mValue;	
				}
				else
				{
					$mValue = parent::__set($sName, $mValue);
				}
				
				break;
				
		}
		
		return $mValue;
	}
	
	/**
	 * @return Ext_TS_Enquiry_Contact_Traveller
	 */
	public function getFirstTraveller() {

		if($this->_oFirstTraveller === null) {
			$aTravellers = (array)$this->getJoinTableObjects('travellers');

			if(empty($aTravellers)) {
				// Nicht getJoinTableObject(), da Objekt in beiden Zwischentabellen steht
				$oFirstTraveller = new Ext_TS_Enquiry_Contact_Traveller();
				$this->addJoinTableObject('travellers', $oFirstTraveller);
				$this->addJoinTableObject('bookers', $oFirstTraveller);
			} else {
				$oFirstTraveller = reset($aTravellers);
			}
			
			$this->_oFirstTraveller = $oFirstTraveller;
		}
		
		$this->_oFirstTraveller->bCheckGender = false;
		
		return $this->_oFirstTraveller;

	}

	/**
	 * Ersten Reisenden ersetzen (für Kundensuche)
	 *
	 * @param Ext_TS_Inquiry_Contact_Abstract $oContact
	 */
	public function replaceFirstTraveller(Ext_TS_Inquiry_Contact_Abstract $oContact) {
		// Werte beider JoinTables überschreiben (es gibt immer nur den FirstTraveller und er kommt in beiden Tabellen vor)
		$this->travellers = array($oContact->id);
		$this->bookers = array($oContact->id);
		$this->_oFirstTraveller = $oContact;
	}

	/**
	 * @param boolean $bLog
	 * @return \Ext_TS_Enquiry
	 */
	public function save($bLog = true) {

		$bInsert = $this->isNew();

//		if($this->_oFirstTraveller !== null){
//			$this->addJoinTableObject('travellers', $this->_oFirstTraveller);
//			$this->addJoinTableObject('bookers', $this->_oFirstTraveller);
//		}
	
		if($this->id == 0) {
			$this->allocateSalesperson();
		}
		
		parent::save($bLog);  
		
		// je nach Clienteinstellung muss beim Eintrag in die Liste schon eine K-Nr vergeben werden
		if(System::d('customernumber_enquiry') === '1') {
			$aErrors = $this->_saveCustomerNr($this);
            if(!empty($aErrors)) {
                return $aErrors;
            }
		}

		if($bInsert) {
			System::wd()->executeHook('ts_enquiry_create', $this);
		} else {
			System::wd()->executeHook('ts_enquiry_update', $this);	
		}
		
		System::wd()->executeHook('ts_enquiry_save', $this);

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function delete() {

		// Daten holen bevor Zwischentabellen geleert sind
		$aDocuments = $this->getJoinTableObjects('documents'); /** @var Ext_Thebing_Inquiry_Document[] $aDocuments */
		$oGroup = $this->getGroup();
		$aGroupContacts = [];
		if($oGroup) {
			$aGroupContacts = $oGroup->getContacts();
		}

		$bSuccess = parent::delete();

		if($bSuccess) {

			// Kein JoinedObject
			foreach($aDocuments as $oDocument) {
				$oDocument->bPurgeDelete = $this->bPurgeDelete;
				$oDocument->delete();
			}

			// Da Gruppe eine JoinTable ist, das aber eigentlich 1:1 ist, wird diese nicht mitgelöscht
			if($oGroup) {
				foreach($aGroupContacts as $oContact) {
					$oContact->bPurgeDelete = $this->bPurgeDelete;
					$oContact->delete(); // Löscht außerdem die Zwischentabellen
				}
				$oGroup->bPurgeDelete = $this->bPurgeDelete;
				$oGroup->delete();
			}

		}

		return $bSuccess;

	}

	/**
	 * Kombinationen, Angebote, Dokumente, Mails, Kontakt
	 *
	 * @param bool $bAnonymize
	 */
	public function purge($bAnonymize = false) {

		if(DB::getLastTransactionPoint() === null) {
			throw new RuntimeException(__METHOD__.': Not in a transaction!');
		}

		if(!$this->exist()) {
			throw new RuntimeException('Object does not exist!');
		}

		if(!$bAnonymize) {
			$this->enablePurgeDelete();
		}

		// Angebote in jedem Fall löschen
//		$this->_aJoinedObjects['offers']['check_active'] = false;
//		$aOffers = $this->getJoinedObjectChilds('offers', false); /** @var Ext_TS_Enquiry_Offer[] $aOffers */
//		foreach($aOffers as $oOffer) {
//			$oOffer->enablePurgeDelete();
//			$oOffer->delete();
//		}

		// Dokumente und E-Mails löschen
		$this->purgeCommonData($bAnonymize);

		// Kontakt löschen oder nur anonymisieren
		$oContact = $this->getFirstTraveller();
		$oContact->purge($bAnonymize);

		if(!$bAnonymize) {
			$this->delete();
		} else {
			$this->profession = '';
			$this->social_security_number = '';
			$this->anonymized = 1;
			$this->save();
			$this->updateIndexStack(false, true);
		}

	}

	/**
	 * @inheritdoc
	 */
	public static function getPurgeLabel() {
		return L10N::t('Anfragen', \TsPrivacy\Service\Notification::TRANSLATION_PATH);
	}

	/**
	 * @return mixed|void
	 */
	public function getActivities() {

	}

	/**
	 * @inheritdoc
	 */
	public static function getPurgeSettings() {
		$oClient = Ext_Thebing_Client::getFirstClient();
		return [
			'action' => $oClient->privacy_enquiry_action,
			'quantity' => $oClient->privacy_enquiry_quantity,
			'unit' => $oClient->privacy_enquiry_unit,
			'basedon' => $oClient->privacy_enquiry_basedon
		];
	}

	/**
	 * @inheritdoc
	 */
	protected function getPurgeDocumentTypes($bAnonymize) {

		return ['all'];

	}

	/**
	 * Speichert eine Kundennumer zu einer Buchung
	 * @param Ext_TS_Inquiry $oInquiry 
	 */
	protected function _saveCustomerNr(Ext_TS_Inquiry_Abstract $oInquiry){
		
		$aErrors = array();
		
        if($oInquiry-> id > 0) {
			$oCustomerNumber = new Ext_Thebing_Customer_CustomerNumber($oInquiry);
			$aErrors = $oCustomerNumber->saveCustomerNumber();
		}
		
        return $aErrors;
	}
	
	/**
	 * @return Ext_Thebing_School
	 */
	public function getSchool()
	{
		$oSchool = $this->getJoinedObject('school');
		
		return $oSchool;
	}
	
	/**
	 * Überprüfen ob eine Anfrage umgewandelt wurde
	 * 
	 * @return bool
	 */
	public function isConvertedToInquiry() {

		$aInquiries = (array)$this->inquiries;
		return !empty($aInquiries);

	}
	
	/**
	 *
	 * @return array 
	 */
	public function getCustomerEmails()
	{
		$aEmails = $this->getFirstTraveller()->getEmails();

		return $aEmails;
	}

	/**
	 * @return Ext_TS_Enquiry_Contact_Traveller 
	 */
	public function getCustomer() {
		return $this->getFirstTraveller();
	}

	/**
	 * Gibt die Kommunikationssprache des zugehörigen Kunden zurück
	 * @return <string>
	 */
	public function getLanguage() {
		$oCustomer = $this->getCustomer();
		$sLanguage = $oCustomer->getLanguage();
		return $sLanguage;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function newDocument(string $sDocumentType = 'brutto', bool $bRelational = true): \Ext_Thebing_Inquiry_Document {

		$oDocument = new Ext_Thebing_Inquiry_Document(0);
		$oDocument->inquiry_id = 0;
		$oDocument->active = 1;
		$oDocument->type = $sDocumentType;
		
		$oDocument->enquiries_to_documents = array($this->id);
		
		if($this->_oOffer !== null) {
			$oDocument->enquiries_offers_to_documents = array($this->_oOffer->id);
		}

		return (object)$oDocument;
	}
	
	/**
	 *
	 * @return Ext_TS_Inquiry
	 */
	public function getFirstInquiry(){
		
		$oInquiry = false;
		
		if($this->isConvertedToInquiry()){
			$aInquiries = (array)$this->inquiries;
			$iInquiryId = (int)reset($aInquiries);
			$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);
		}
		
		return $oInquiry;
	}
	

	/**
	 * remove the group 
	 */
	public function removeGroup(){
		$aGroups	= (array)$this->getJoinTableObjects('group');
		foreach($aGroups as $sKey => $oGroup){
			$this->removeJoinTableObject('group', $sKey);
		}
	}
	
	/**
	 * Gruppe holen (falls vorhanden)
	 * @return Ext_TS_Enquiry_Group
	 */
	public function getGroup(){
		
		$oGroup = null;
		
		$aGroups	= $this->getJoinTableObjects('group');

		if(
			!empty($aGroups)
		){
			$oGroup		= reset($aGroups);
		}
		
		return $oGroup;
	}
	

	/**
	 *
	 * Angebot in die Anfrage setzen, um Positionen zu definieren
	 * 
	 * @param Ext_TS_Enquiry_Offer $oOffer 
	 */
	public function setOffer(Ext_TS_Enquiry_Offer $oOffer)
	{
		$this->_oOffer = $oOffer;
	}

	/**
	 * Offer liefern, welches in diesem Fassaden-Gottobjekt intern gesetzt wurde
	 *
	 * @return Ext_TS_Enquiry_Offer
	 */
	public function getInternalOffer() {
		return $this->_oOffer;
	}
	
	/**
	 *
	 * Kombination in die Anfrage setzen, um Positionen zu definieren
	 * 
	 * @param Ext_TS_Enquiry_Combination $oCombination
	 */
	public function setCombination(Ext_TS_Enquiry_Combination $oCombination)
	{
		$this->_oCombination = $oCombination;
	}
	
	/**
	 * Übergibt das jetzige Gruppenmitglied, um die Flags auszulesen & die Items zu bilden, weil Buchungen eine andere Struktur hat
	 * 
	 * @todo Hat eine ganz andere Funktion als die gleichbenannte Funktion in der Ext_TS_Inquiry
	 * @param Ext_TS_Contact $oTraveller
	 */
	public function setTraveller(Ext_TS_Contact $oTraveller)
	{
		$this->_oTraveller = $oTraveller;
	}
	
	/**
	 * @return Ext_TS_Enquiry_Combination_Course[]
	 */
	public function getCourses($bAsObjectArray = true)
	{
		$aCourses = array();
		
		if($this->_oOffer !== null)
		{ 
			$aCourses = $this->_oOffer->getCourses();
		}
		elseif($this->_oCombination !== null)
		{
			$aCourses = $this->_oCombination->getJoinedObjectChilds('course');
		}
		
		if(!$bAsObjectArray)
		{
			$aBack = array();
			
			foreach($aCourses as $oCourse)
			{
				$aBack[] = $oCourse->getArray();
			}
		}
		else
		{
			$aBack = $aCourses;
		}
		
		return $aBack;
	}
	
	/**
	 * @return Ext_TS_Enquiry_Combination_Accommodation[]
	 */
	public function getAccommodations($bAsObjectArray = true)
	{
		$aAccommodations = array();
		
		if($this->_oOffer !== null)
		{
			$aAccommodations = $this->_oOffer->getAccommodations($bAsObjectArray);
		}
		elseif($this->_oCombination !== null)
		{
			$aAccommodations = $this->_oCombination->getJoinedObjectChilds('accommodation');
		}
		
		return $aAccommodations;
	}

	/**
	 * @param string $sFilter
	 * @param bool $bIgnoreBookingStatus
	 * @return Ext_TS_Enquiry_Combination_Transfer|Ext_TS_Enquiry_Combination_Transfer[]
	 */
	public function getTransfers($sFilter = '', $bIgnoreBookingStatus = false) {
		$mTransfers = array();
		if($this->_oOffer !== null) {
			$mTransfers = $this->_oOffer->getTransfers($sFilter, $bIgnoreBookingStatus);
		} elseif($this->_oCombination !== null) {
			$mTransfers = $this->_oCombination->getTransfers($sFilter, $bIgnoreBookingStatus);
		}
		return $mTransfers;
	}

	/**
	 * @return Ext_TS_Enquiry_Combination_Insurance[]
	 */
	public function getInsurancesWithPriceData($sDisplayLanguage = null) {

		$aInsurances = array();

		if($this->_oOffer !== null) {
			$aInsurances = $this->_oOffer->getInsurancesWithPriceData($sDisplayLanguage);
		} elseif($this->_oCombination !== null) {
			$aInsurances = $this->_oCombination->getInsurancesWithPriceData($sDisplayLanguage);
		}

		return $aInsurances;

	}

	/**
	 * @return Ext_TS_Enquiry_Combination_Insurance[]
	 */
	public function getInsurances()
	{
		$aInsurances = array();
		
		if($this->_oOffer !== null)
		{
			$aInsurances = $this->_oOffer->getInsurances();
		}
		
		return $aInsurances;
	}

	/**
	 * Prüft ob der Reisende einen bestimmten Gruppenflag hat
	 *
	 * @param string $sFlag
	 * @param Ext_TS_Group_Contact $oGroupContact
	 * @return bool
	 * @throws Exception
	 */
	public function hasGroupFlag($sFlag, Ext_TS_Group_Contact $oGroupContact = null){

		if(!$oGroupContact) {
			$oGroupContact = $this->_oTraveller;
		}

		if(!is_object($oGroupContact)){
			throw new Exception('Traveller Object ist required for Group Flag checking.');
		}
		
		$aFlags = $oGroupContact->guide_flags;

		$bCheck = false;
		foreach($aFlags as $aData) {
			if(
				$aData['flag'] == $sFlag &&
				$aData['contact_id'] == $oGroupContact->id
			) {
				$bCheck = true;
				break;
			}
		}

		return $bCheck;
	}

	/**
	 * @param Ext_TS_Group_Contact $oGroupContact
	 * @return bool
	 * @throws Exception
	 */
	public function isGuide(Ext_TS_Contact $oGroupContact = null) {

		/*
		 * Keine Ahnung, was man hier sonst machen soll, weil Parent keinen Traveller bekommt
		 * und hasGroupFlag() ansonsten eine Exception schmeißt. Die Rechnungspositionen
		 * rufen die Methode aber ohne Argument auf (wie das Parent eben) und durch dieses
		 * Collection-oder-nicht-Collection-Gehabe von Gruppen bei Buchungen und Anfragen
		 * kann das auch nicht ohne größeren Umbau funktionieren. #9747
		 */
		if($this->_oTraveller === null) {
			return false;
		}

		return $this->hasGroupFlag('guide', $oGroupContact);
	}

	/**
	 * @param string $sOption
	 * @return bool
	 * @throws Exception
	 */
	public function getJourneyTravellerOption($sOption) {
		return $this->hasGroupFlag($sOption);
	}

	/**
	 * @return string
	 */
	public function getKey() {
		return 'enquiry';
	}
	
	public function getCombinations() {
		
		$aCombinations = $this->getJoinedObjectChilds('combinations', true);
		return $aCombinations;
		
	}
	
	public function getTravellers() {
		
		$aContacts = array();

		if($this->hasGroup()) {

			$oGroup = $this->getGroup();

			if($oGroup) {
				$aContacts = $oGroup->getContacts();
			}

		} else {
			$aContacts[] = $this->getFirstTraveller();
		}
		
		return $aContacts;
	}

	/**
	 *
	 * Verbindungstabelle für Specialpositionen
	 * 
	 * @return array 
	 */
	public function getSpecialPositionRelationTableData()
	{
		if($this->_oOffer !== null)
		{
			return $this->_oOffer->getSpecialPositionRelationTableData();
		}
		elseif($this->_oCombination !== null)
		{
			return $this->_oCombination->getSpecialPositionRelationTableData();
		}
		else
		{
			throw new Exception("No offer or combination found!");
		}
	}
	
	/**
	 *
	 * Verbindungstabelle für Specialpositionen abspeichern
	 * 
	 * @param array $aSpecialPositions
	 */
	public function saveSpecialPositionRelation($iType=null, $bSave = false)
	{
		return;//fürs erst besprochen und deaktiviert, #4270
		
		$oRelation = null;

		if($this->_oOffer !== null)
		{
			$oRelation = $this->_oOffer;
		}
		elseif($this->_oCombination !== null)
		{
			$oRelation = $this->_oCombination;
		}
		
		if($oRelation)
		{
			
			$this->_saveSpecialPositionRelation($oRelation, $iType);

		}
		else
		{
			throw new Exception('No offer or combination found!');
		}
	}
	
	/**
	 *
	 * @return Ext_TS_Enquiry_Offer | Ext_TS_Enquiry_Combination
	 */
	public function getSpecialRelationObject()
	{
		if($this->_oOffer !== null)
		{
			$oRelationObject = $this->_oOffer;
		}
		elseif($this->_oCombination !== null)
		{
			$oRelationObject = $this->_oCombination;
		}
		else
		{
			throw new Exception('No offer or combination found!');
		}
		
		return $oRelationObject;
	}

	public function getOffers(){
		$aOffers = $this->getJoinedObjectChilds('offers', true);
		return $aOffers;
	}

	/**
	 * prüft, ob für die Anfrage bereits Angebote erstellt wurden
	 * @return boolean 
	 */
	public function hasOffers() {
		
		$aOffers = $this->getOffers();
		if(!empty($aOffers)) {
			return true;
		}	
		return false;
	}
	
	/**
	 * Pfüft auf Fehler die erst bestätigt werden müssen bevor gespeichert werden darf
	 * @return type 
	 */
	public function checkIgnoringErrors(){
		$mError = true;
		
		if($this->id > 0){
			$oSchool = $this->getSchool();
			$iOriginalSchoolId = $this->getOriginalData('school_id');
			
			if($oSchool->id != $iOriginalSchoolId){
				$mError = array('ENQUIRY_SCHOOL_CHANGE');
			}
		}
		
		return $mError;
	}
	

	/**
	 * @TODO Was ist das für ein Mist, dass hier on-the-fly buildItems() aufgerufen wird?!
	 *
	 * Betrag für die Anfrage ausrechnen, Angebot oder Kombination muss vorher gesetzt sein
	 * 
	 * @return float 
	 */
	public function calculateAmount()
	{
		$fPrice = 0;
		
		if($this->hasGroup())
		{
			$oTravellersGroup	= $this->getGroup();
			$aTravellers		= $oTravellersGroup->getContacts();
		}
		else
		{
			$oEnquiryContact	= $this->getFirstTraveller();
			$aTravellers		= array($oEnquiryContact);
		}
		
		$oSchool = $this->getSchool();

		foreach($aTravellers as $oTraveller)
		{
			$this->setTraveller($oTraveller);

			$oInquiryDocument	= new Ext_Thebing_Inquiry_Document();
			$oInquiryDocument->enquiries_to_documents = array($this->id);
			$oVersion			= new Ext_Thebing_Inquiry_Document_Version();
			$oVersion->setInquiry($this);
			$oVersion->tax		= $oSchool->tax;
			#$aItems				= $oVersion->buildItems($oInquiryDocument);
			#$oVersion->setInquiry($this);
			$oVersion->bCalculateTax = true;
			$aAmount			= $oVersion->calculateAmount($oInquiryDocument, true, true);

			$sAmountKey = 'amount';

			if($this->hasNettoPaymentMethod())
			{
				$sAmountKey .= '_net';
			}

			if(isset($aAmount[$sAmountKey]))
			{
				$fPrice += $aAmount[$sAmountKey];
			}
		}
		
		return $fPrice;
	}
	
	public function getDocumentTemplateType($sType)
	{
		if($sType == 'gross')
		{
			return 'document_offer_customer';
		}
		else
		{
			return 'document_offer_agency';
		}
	}

	public function getFirstAccommodationStart($bTimestamps = true)
	{
		$aAccommodations = $this->getAccommodations();
		
		if(!empty($aAccommodations))
		{
			$oFirstAccommodation = reset($aAccommodations);

			if($bTimestamps) {
				$oDate = new WDDate($oFirstAccommodation->from, WDDate::DB_DATE);
				return $oDate->get(WDDate::TIMESTAMP);
			} else {
				return $oFirstAccommodation->from;
			}
		} else {
			return 0;
		}
	}
	
	public function countAllMembers()
	{
		//@todo
	}
	
	/**
	 * 
	 * @return string 
	 */
	public function getTransferMode()
	{
		if($this->_oOffer !== null) {
			$sMode = $this->_oOffer->getTransferMode();
		} elseif($this->_oCombination !== null) {
			$sMode = $this->_oCombination->getTransferMode();
		} else {
			$sMode = 'no';
		}

		return $sMode;
	}
	
	/**
	 * Liefert den Kommentar zum Transfer
	 * @return string 
	 */
	public function getTransferComment()
	{
		$sTransferComment = null;
		
		if($this->_oOffer !== null)
		{
			$sTransferComment = $this->_oOffer->getMergedFieldData('transfer_comment');
		}
		elseif($this->_oCombination !== null)
		{
			$sTransferComment = $this->_oCombination->transfer_comment;
		}
		
		return $sTransferComment;
	}
	
	public function getServiceObjectClasses() {
		return [
			'course' => 'Ext_TS_Enquiry_Combination_Course',
			'accommodation' => 'Ext_TS_Enquiry_Combination_Accommodation',
			'transfer' => 'Ext_TS_Enquiry_Combination_Transfer',
			'insurance' => 'Ext_TS_Enquiry_Combination_Insurance',
		];
	}

	public function setGroup(Ext_TS_Group_Interface $oGroup) {
		//@todo
	}

	public function hasSameData($sType) {
		//Kann nicht funktionieren wie bei den Buchungen, da merkt man sich nur einen Nicht-Guide-Mitglied und bildet 
		//damit die Items, hier muss ich aber unbedingt unterscheiden können zwischen den einzelnen Mitgliedern und
		//eventuell filtern, falls die nicht im Zuweisdialog angewählt wurden
		return false;
	}

	/**
	 * @param Ext_TS_Contact $oContact
	 * @return Ext_TS_Enquiry 
	 */
	public function createMemberInquiry(Ext_TS_Contact $oContact) {

		$oEnquiry = clone($this);
		$oEnquiry->setTraveller($oContact);

		return $oEnquiry;
	}

	/**
	 * Diese Funktion wird aufgerufen bei den Dokumenten, nachdem ein Traveller & Angebot ins Objekt gesetzt wird,
	 * normalerweise würde das per Referenz funktionieren, wir machen aber ein clone von dem objekt in der Funktion
	 * createMemberInquiry(), weil die Objekte in einem Array zwischengelagert werden, bevor die Positionen gebildet werden
	 * Diese Funktion ist ganz wichtig, damit die verschiedene Struktur zwischen Gruppenbuchungen & Gruppenanfragen 
	 * zusammen arbeiten kann, ohne die ganzen Dokumente von neu zu schreiben...
	 */
	public function manipulateInstance() {
		self::setInstance($this);
	}

	/**
	 * @return Ext_TS_Contact 
	 */
	public function getTraveller() {

		if($this->_oTraveller !== null) {
			$oTraveller = $this->_oTraveller;
		} else {
			$oTraveller = $this->getCustomer();
		}

		return $oTraveller;
	}
	
	/**
	 * Definiert ob ein Reisender schon ins Objekt gesetzt wurde per setTraveller()
	 * 
	 * @return bool 
	 */
	public function hasTraveller() {

		if($this->_oTraveller === null) {
			$bRetVal = false;
		} else {
			$bRetVal = true;
		}

		return $bRetVal;
	}

	/**
	 * Da die Struktur hier nicht so scheiße ist wie bei den Buchungen, reicht es natürlich vollkommen aus
	 * nur ein Gruppenmitglied zurückzugeben, da nicht für jedes Mitglied ein einzelnes Dokument angelegt wird, es gibt
	 * also nur 1 Dokument und n Reisende
	 *
	 * @param Ext_Thebing_Inquiry_Document $oDocument
	 * @return array
	 */
	public function getAllGroupMembersForDocument(Ext_Thebing_Inquiry_Document $oDocument) {

		$aMembers = array();

		if($oDocument->id > 0) {
			if($this->_oOffer !== null) {
				// Man darf nur die Schüler aus dem Angebot holen, für die
				// das Dokument erstellt worden ist
				$aMembers = $this->_oOffer->getAllocatedContacts();
				if(!empty($aMembers)) {
					// Workaround: Hier wird nur ein Kontakt zurückgebeben,
					// da die Struktur der aufrufenden Methode
					// es nicht anders hergibt
					$aMembers = array(reset($aMembers));
				}
			}	
		} else {
			//Hier müssen wir die scheiß Struktur leider übernehmen
			$oGroup = $this->getGroup();
			$aMembers = $oGroup->getMembers();
		}

		return $aMembers;
	}

	/**
	 * @return array
	 */
	public function getOfferAttachments() {

		$aBack = array();

		$aDocuments = $this->getOfferDocuments();
		foreach($aDocuments as $oDocument) {
			$oVersion = $oDocument->getLastVersion();
			if($oVersion) {
				$aBack[$oVersion->id] = $oVersion->getLabel();
			}
		}

		return $aBack;
	}
	
	/**
	 * Liefert alle Dokumente aller Angebote dieser Anfrage
	 * 
	 * @return array Ext_Thebing_Inquiry_Document 
	 */
	public function getOfferDocuments() {

		$aOffers = $this->getJoinedObjectChilds('offers');

		$aDocuments = array();
		foreach($aOffers as $oOffer){
			$oDocument = $oOffer->getOfferDocument();

			if(is_object($oDocument)){
				$aDocuments[] = $oDocument;
			}
		}
		return $aDocuments;
	}

	/**
	 * Platzhalter Objekt erstellen mit construct Parameter Übergabe
	 *
	 * @param array $aParams
	 * @return Ext_TS_Enquiry_Placeholder|Ext_TS_Enquiry_Offer_Placeholder
	 * @throws InvalidArgumentException
	 */
	public function createPlaceholderObject($aParams)
	{
		$aParamSet			= array();
		$sPlaceholderClass	= $this->getOldPlaceholderClass($aParams);
		
		if(!isset($aParams['inquiry']))
		{
			throw new InvalidArgumentException('Inquiry not defined!');
		}

		if(
			$sPlaceholderClass == 'Ext_TS_Enquiry_Placeholder' &&
			!empty($aParams['template_type']) &&
			strpos($aParams['template_type'], 'document_offer') !== false
		){
			$sPlaceholderClass = 'Ext_TS_Enquiry_Offer_Placeholder';
			
			$oDocument = $this->newDocument();
			
			$aParamSet = array(
				$oDocument,
			);
		}
		else
		{
			$aParamSet = array(
				$aParams['inquiry'],
			);	
		}
		
		$oReflection		= new ReflectionClass($sPlaceholderClass);
		$oPlaceholder		= $oReflection->newInstanceArgs($aParamSet);

		return $oPlaceholder;
	}

	/**
	 * gibt ein Array mit allen Kommentaren der Anfrage zurück
	 * @return array 
	 */
	public function getComments() {
		
		$aComments = array(
			'course' => array(), 
			'accommodation' => array(), 
			'transfer' => array()
		);
		
		// Kurs		
		$this->_setCommentAttributes('course', $this->course_category, $aComments);
		$this->_setCommentAttributes('course', $this->course_intensity, $aComments);
		// Unterkunft		
		$this->_setCommentAttributes('accommodation', $this->accommodation_category, $aComments);
		$this->_setCommentAttributes('accommodation', $this->accommodation_room, $aComments);
		$this->_setCommentAttributes('accommodation', $this->accommodation_meal, $aComments);
		// Transfer		
		$this->_setCommentAttributes('transfer', $this->transfer_category, $aComments);
		$this->_setCommentAttributes('transfer', $this->transfer_location, $aComments);
		
		$aComments['comment'] = $this->comment;
		
		return $aComments;		
	}
	
	/**
	 * prüft, ob Kommentar vorhanden ist und ergänzt ihn im Array
	 * @param string $sKey
	 * @param string $sValue
	 * @param array $aComments 
	 */
	protected function _setCommentAttributes($sKey, $sValue, &$aComments) {
		if($sValue != '') {
			$aComments[$sKey][] = $sValue;
		}
	}
	
	/**
	 *
	 * Definieren welcher Numberrange-Typ geholt werden muss anhand des Dokumenttypes
	 * 
	 * @param string $sDocumentType
	 * @return string 
	 */
	public function getTypeForNumberrange($sDocumentType, $mTemplateType=null) {
		return 'enquiry';
	}

	public function getTransferLocations($sType = '', $sLang = ''){
		
		$oSchool = $this->getSchool();
		
		$oInquiry	= new Ext_TS_Inquiry();
		
		// Schule setzen, da diese in der getTransferLocations von dem Inquiry-Objekt abgefragt wird
		$oJourney = $oInquiry->getJoinedObjectChild('journeys');
		$oJourney->school_id = $oSchool->id;
		
		$aLocations = $oInquiry->getTransferLocations($sType, $sLang);
		
		return $aLocations;
	}
	
	/**
	 * @inheritdoc
	 */
	public function getLastDocument($mType, $aTemplateTypes = [], $oSearch = null) {

		$oDocument = null;
		
		$sSql = "
			SELECT
				`kid`.`id`
			FROM
				`kolumbus_inquiries_documents` `kid` INNER JOIN
				`ts_enquiries_offers_to_documents` `ts_en_of_to_d` ON
					`ts_en_of_to_d`.`document_id` = `kid`.`id` INNER JOIN
				`ts_enquiries_offers` `ts_en_of` ON
					`ts_en_of`.`id` = `ts_en_of_to_d`.`enquiry_offer_id` AND
					`ts_en_of`.`active` = 1
			WHERE
				`kid`.`active` = 1 AND
				`ts_en_of`.`enquiry_id` = :enquiry_id
			ORDER BY
				`kid`.`created` DESC
			LIMIT 
				1
		";
		
		$aSql = array(
			'enquiry_id' => $this->id,
		);
		
		$iDocumentId = (int)DB::getQueryOne($sSql, $aSql);
		
		if($iDocumentId > 0)
		{
			$oDocument = Ext_Thebing_Inquiry_Document::getInstance($iDocumentId);
		}
		
		return $oDocument;
	}
	
	public function getLastAdditionalDocumentPDF() {
		
		$oDocument = null;
		
		$sSql = "
			SELECT
				`kid`.`id`
			FROM
				`kolumbus_inquiries_documents` `kid` INNER JOIN
				`ts_enquiries_to_documents` `ts_en_to_d` ON
					`ts_en_to_d`.`document_id` = `kid`.`id` AND 
					`ts_en_to_d`.`enquiry_id` = :enquiry_id 
			WHERE
				`kid`.`active` = 1 AND
				`kid`.`type` = 'additional_document'
			ORDER BY
				`kid`.`created` DESC
			LIMIT 
				1
		";
		
		$aSql = array(
			'enquiry_id' => $this->id,
		);
		
		$iDocumentId = (int)DB::getQueryOne($sSql, $aSql);
		
		$sPdfPath = '';
		
		if($iDocumentId > 0)
		{
			$oDocument = Ext_Thebing_Inquiry_Document::getInstance($iDocumentId);
			$oLastVersion = $oDocument->getLastVersion();
			
			if($oLastVersion) {
				$sPdfPath = $oLastVersion->path;
			}
		}
		
		return $sPdfPath;		
	}

	public function canShowPositionsTable()
	{
		if($this->_oOffer !== null)
		{
			$bShow = true;
		}
		elseif($this->_oCombination !== null)
		{
			$bShow = true;
		}
		else
		{
			$bShow = false;
		}
		
		return $bShow;
	}

	/**
	 * Ableiten, da das mit dem Guide hier irgendwie nicht funktioniert
	 * @return string
	 */
	public function getGroupShortName($bCheckForGuide = true) {
		return parent::getGroupShortName(false);
	}

	/**
	 * Datum für Frühbucherrabatt
	 *
	 * @return integer
	 */
	public function getCreatedForDiscount() {

		if($this->_oOffer instanceof Ext_TS_Enquiry_Offer) {
			return $this->_oOffer->created;
		} elseif($this->_oCombination instanceof Ext_TS_Enquiry_Combination) {
			return $this->_oCombination->created;
		} else {
			throw new RuntimeException('Cannot get created date for discount');
		}

	}

	/**
	 * @return null|Ext_Thebing_Agency_Contact
	 * @throws Exception
	 */
	public function getAgencyContact() {

		$oAgencyContact = Ext_Thebing_Agency_Contact::getInstance($this->agency_contact_id);

		if($oAgencyContact->getId() !== 0) {
			return $oAgencyContact;
		}

		return null;

	}

	/**
	 * Funktioniert nur mit Offer oder Combination (wie alles in diesem komischen Konstrukt)
	 *
	 * @inheritdoc
	 */
	public function getCompleteServiceTimeframe($allServices = true, $onlyVisible = true): ?\Carbon\CarbonPeriod {

		$dFrom = null;
		$dUntil = null;

		$this->compareServiceTimeframe($this->getCourses(), ['from', 'until'], $dFrom, $dUntil);
		$this->compareServiceTimeframe($this->getAccommodations(), ['from', 'until'], $dFrom, $dUntil);

		if($allServices) {
			$this->compareServiceTimeframe($this->getTransfers(), ['transfer_date', 'transfer_date'], $dFrom, $dUntil);
			$this->compareServiceTimeframe($this->getInsurances(), ['from', 'until'], $dFrom, $dUntil);
		}

		if(
			$dFrom === null ||
			$dUntil === null
		) {
			return null;
		}

		return new \Core\DTO\DateRange($dFrom, $dUntil);

	}

	/**
	 * @return Ext_Thebing_Client_Inbox
	 */
	public function getInbox() {

		if($this->inbox_id == 0) {
			return null;
		}

		return Ext_Thebing_Client_Inbox::getInstance($this->inbox_id);
	}

}
