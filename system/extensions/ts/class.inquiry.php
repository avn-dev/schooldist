<?php

use Carbon\Carbon;
use Core\Helper\DateTime;

/**
 * @property string|int $id
 * @property string $changed (TIMESTAMP)
 * @property string $created (TIMESTAMP)
 * @property int $active
 * @property string $status ENUM(ready, pending, fail)
 * @property int $creator_id
 * @property int $editor_id
 * @property int $type BIT-Array
 * @property string $inbox
 * @property int $group_id
 * @property int $agency_id
 * @property int $currency_id
 * @property $follow_up
 * @property $converted
 * @property string $confirmed (TIMESTAMP)
 * @property float $amount
 * @property float $amount_payed
 * @property float $amount_initial
 * @property float $amount_credit
 * @property float $amount_payed_prior_to_arrival
 * @property float $amount_payed_at_school
 * @property float $amount_payed_refund
 * @property string $changed_amount (TIMESTAMP)
 * @property string $changed_initial_amount (TIMESTAMP)
 * @property string $canceled (TIMESTAMP)
 * @property float $canceled_amount
 * @property string $tsp_transfer
 * @property string $tsp_comment
 * @property string $transfer_data_requested (TIMESTAMP)
 * @property int $referer_id
 * @property int $status_id
 * @property int $sponsored
 * @property int $sponsor_id
 * @property $number
 * @property $numberrange_id
 * @property string $promotion
 * @property string $voucher_id
 * @property string $social_security_number
 * @property int $payment_method
 * @property string $payment_method_comment
 * @property string $profession
 * @property int $agency_contact_id
 * @property int $sponsor_contact_id
 * @property int $has_invoice
 * @property int $has_proforma
 * @property int $frontend_log_id
 * @property string $arrival_date (DATE)
 * @property string $departure_date (DATE)
 * @property ?string $service_from (DATE)
 * @property ?string $service_until (DATE)
 * @property $checkin
 * @property $checkout
 * @property int $sales_person_id
 * @property int $partial_invoices_terms
 * @property ?string $unique_key
 */
class Ext_TS_Inquiry extends Ext_TS_Inquiry_Abstract {

	use \Core\Traits\WdBasic\MetableTrait;

	use \Ts\Traits\Numberrange;

	use \Notices\Traits\WithNotices;

	/** @var int DB: Bit-Array */
	const TYPE_ENQUIRY = 1;

	/** @var int DB: Bit-Array */
	const TYPE_BOOKING = 2;

	/** @var string Elasticsearch-Wert (Array) */
	const TYPE_ENQUIRY_STRING = 'enquiry';

	/** @var string Elasticsearch-Wert (Array) */
	const TYPE_BOOKING_STRING = 'booking';

	protected $_sTable = 'ts_inquiries';

	protected $_sEditorIdColumn = 'editor_id';

	protected $_sOldPlaceholderClass = \Ext_Thebing_Inquiry_Placeholder::class;

	protected $_sPlaceholderClass = \Ext_TS_Inquiry_Placeholder::class;

	protected $_aGetAccommodationProviderCache = array();

	protected string $uniqueKeyColumn = 'unique_key';

	protected int $uniqueKeyLength = 16;

//	protected $_aInsurancesCache = null;

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'ts_i';

	protected $_aFormat = array( 
		'group_id' => array(
			'validate' => 'INT',
		),
		'agency_id' => array(
			'validate' => 'INT',
		),
		'currency_id' => array(
			'validate' => 'INT_POSITIVE',
			'required'	=> true,
		),
		'amount' => array(
			'validate' => 'FLOAT',
		),
		'amount_payed' => array(
			'validate' => 'FLOAT',
		),
		'amount_initial' => array(
			'validate' => 'FLOAT',
		),
		'amount_credit' => array(
			'validate' => 'FLOAT',
		),
		'amount_payed_prior_to_arrival' => array(
			'validate' => 'FLOAT',
		),
		'amount_payed_at_school' => array(
			'validate' => 'FLOAT',
		),
		'amount_payed_refund' => array(
			'validate' => 'FLOAT',
		),
		'canceled_amount' => array(
			'validate' => 'FLOAT',
		),
		'referer_id' => array(
			'validate' => 'INT',
		),
		'status_id' => array(
			'validate' => 'INT',
		),
		'payment_method' => array(
			'validate' => 'INT',
			'required'	=> true
		),
		'agency_contact_id' => array(
			'validate' => 'INT'
		)
	);

	protected $_aJoinTables = array(
		'inquiries_childs' => [
			'table' => 'ts_inquiries_to_inquiries',
			'foreign_key_field' => 'child_id',
			'primary_key_field' => 'parent_id',
			'on_delete' => 'no_action',
			'cloneable' => false
		],
		'travellers' => array(
			'table' => 'ts_inquiries_to_contacts',
			'foreign_key_field'=> 'contact_id',
			'primary_key_field'=> 'inquiry_id',
			'static_key_fields'=> array('type' => 'traveller'),
			'class' => 'Ext_TS_Inquiry_Contact_Traveller',
			'autoload' => false,
			'on_delete' => 'no_action',
			'cloneable' => true
		),
		'bookers' => array(
			'table' => 'ts_inquiries_to_contacts',
			'foreign_key_field'=> 'contact_id',
			'primary_key_field'=> 'inquiry_id',
			'static_key_fields'=> array('type' => 'booker'),
			'class' => 'Ext_TS_Inquiry_Contact_Booker',
			'autoload' => false,
			'on_delete' => 'no_action'
		),
		'emergencies' => array(
			'table' => 'ts_inquiries_to_contacts',
			'foreign_key_field'=> 'contact_id',
			'primary_key_field'=> 'inquiry_id',
			'static_key_fields'=> array('type' => 'emergency'),
			'class' => 'Ext_TS_Inquiry_Contact_Emergency',
			'autoload' => false,
			'on_delete' => 'no_action',
			'cloneable' => false,
			'readonly' => true // Wegen Fake-JoinedObject 'other_contacts'
		),
		'special_position_relation' => array(
			'table'					=> 'ts_inquiries_to_special_positions',
			'primary_key_field'		=> 'inquiry_id',
			'foreign_key_field'		=> 'special_position_id',
			'autoload'				=> false,
			'class'					=> 'Ext_Thebing_Inquiry_Special_Position',
			'on_delete' => 'cascade',
			'cloneable' => false
		),
		'payment_reminders'	=> array(
			'table'					=> 'kolumbus_inquiries_payments_reminders',
			'primary_key_field'		=> 'inquiry_id',
			'autoload'				=> false,
			'on_delete' => 'no_action',
			'cloneable' => false
		),
		'versions_items_changes' => array(
			'table' => 'kolumbus_inquiries_documents_versions_items_changes',
			'primary_key_field'		=> 'inquiry_id',
			'foreign_key_field'		=> 'status',
			'static_key_fields'=> array('visible' => '1', 'active' => '1'),
			'on_delete' => 'no_action',
			'autoload'				=> false, // wehe das ändert jemand!! dann gehen die farb. makierungen bei transfer wieder nicht!
			'cloneable' => false
		),
		'tuition_index' => array(
			'table'				=> 'ts_inquiries_tuition_index',
			'primary_key_field'	=> 'inquiry_id',
			'autoload'			=> false,
			'readonly'			=> true,
			'cloneable' => false
		),
		'flex_uploads' => array( // kolumbus_school_customerupload
			'table' => 'ts_inquiries_flex_uploads',
			'primary_key_field'	=> 'inquiry_id',
			'foreign_key_field'	=> ['type', 'type_id', 'released_student_login'],
			'autoload' => false,
			'on_delete' => 'no_action',
			'cloneable' => false
		)
	);

	/**
	 * @TODO Entfernen, weil das einfach objekt-relational viel besser funktionieren würde
	 *
	 * @var Ext_TS_Inquiry_Contact_Traveller
	 */
	protected $_oFirstTraveller = null;

	/**
	 * @var Ext_TS_Inquiry_Matching
	 */
	protected $_oMatchingData	= null;

	/**
	 * @var Ext_TS_Inquiry_Contact_Emergency
	 */
	protected $_oEmergenyContact = null;

	/**
	 * @var null|Ext_TS_Inquiry_Journey
	 */
	protected $_oJourney = null;

	/**
	 * Eine Liste mit Klassen, die sich auf dieses Object beziehen, bzw. 
	 * mit diesem verknüpft sind (parent: n-1, 1-1, child: 1-n, n-m)
	 */
	protected $_aJoinedObjects = array(
		'journeys' => array(
			'class' => 'Ext_TS_Inquiry_Journey',
			'key' => 'inquiry_id',
			'type' => 'child',
			'check_active' => true,
			'query' => false,
			'bidirectional' => true,
			'on_delete' => 'cascade',
			'cloneable' => true
		),
		'documents' => array(
			'class' => Ext_Thebing_Inquiry_Document::class,
			'key' => 'entity_id',
			'static_key_fields'=> ['entity' => self::class],
			'type' => 'child',
			'check_active' => true,
			'query' => false,
//			'bidirectional' => true,
			'on_delete' => 'cascade', // Wird in delete() ggf. überschrieben
			'cloneable' => false
		),
		'agency' => array(
			'class' => 'Ext_Thebing_Agency',
			'key' => 'agency_id',
			'check_active' => true,
			'query' => false,
			'type' => 'parent',
			'cloneable' => false
		),
		'group' => array(
			'class' => 'Ext_Thebing_Inquiry_Group',
			'key' => 'group_id',
			'check_active' => true,
			'query' => false,
			'type' => 'parent',
			'cloneable' => false
		),
		'matching_data' => array(
			'class' => 'Ext_TS_Inquiry_Matching',
			'key' => 'inquiry_id',
			'type' => 'child',
			'query' => false,
			'cloneable' => true
		),
		'holidays' => [
			'class' => 'Ext_TS_Inquiry_Holiday',
			'key' => 'inquiry_id',
			'type' => 'child',
			'check_active' => true,
			'on_delete' => 'cascade',
			'cloneable' => false
		],
		'placementtests' => [ // Purge
			'class' => 'Ext_Thebing_Placementtests_Results',
			'key' => 'inquiry_id',
			'type' => 'child',
			'query' => false,
			'on_delete' => 'cascade',
			'cloneable' => false
		],
		'sponsoring_guarantees' => [
			'class' => '\TsSponsoring\Entity\InquiryGuarantee',
			'key' => 'inquiry_id',
			'type' => 'child',
			'query' => false,
			'check_active' => true,
			'on_delete' => 'cascade',
			'cloneable' => false
		],
		'partial_invoices_term' => [
			'class' => 'Ext_TS_Payment_Condition',
			'key' => 'partial_invoices_terms',
			'type' => 'parent',
			'check_active' => true,
		],
		'other_contacts' => [
			'class' => 'Ext_TS_Inquiry_Contact_Emergency',
			'type' => 'child',
			'handler' => Ts\Handler\Gui2\InquiryContactJoinedObject::class,
			'cloneable' => false
		]
	);

	protected $_aFlexibleFieldsConfig = [
		'student_record_course' => [],
		'student_record_matching' => [],
		'student_record_general' => [],
		'student_record_transfer' => [],
		'student_record_accommodation' => [],
		'student_record_upload' => [],
		'student_record_insurance' => [],
		'student_record_activities' => [],
		'student_record_visum' => [],
		'student_record_visum_status' => [],
		'enquiries_enquiries' => [],
		'student_record_sponsoring' => []
	];

	/**
	 * @var array
	 */
	protected $_aAttributes = [
		// TODO salesforce_id als Meta-Attribut auslagern
		'salesforce_id' => ['class' => 'WDBasic_Attribute_Type_Varchar',],
		'enquiry_course_category' => ['class' => 'WDBasic_Attribute_Type_Text'],
		'enquiry_course_intensity' => ['class' => 'WDBasic_Attribute_Type_Text'],
		'enquiry_accommodation_category' => ['class' => 'WDBasic_Attribute_Type_Text'],
		'enquiry_accommodation_room' => ['class' => 'WDBasic_Attribute_Type_Text'],
		'enquiry_accommodation_meal' => ['class' => 'WDBasic_Attribute_Type_Text'],
		'enquiry_transfer_category' => ['class' => 'WDBasic_Attribute_Type_Text'],
		'enquiry_transfer_location' => ['class' => 'WDBasic_Attribute_Type_Text'],
		'subagency_id' => ['type' => 'int'],
	];

	protected $sNumberrangeClass = \Ts\Service\Numberrange\Booking::class;

	const LOG_INQUIRY_CREATED = 'ts/inquiry-created';
	const LOG_INQUIRY_UPDATED = 'ts/inquiry-updated';

	const LOG_CHECKIN = 'ts/checkin';
	const LOG_CHECKIN_UNDO = 'ts/checkin-undo';

	const LOG_CHECKOUT = 'ts/checkout';
	const LOG_CHECKOUT_UNDO = 'ts/checkout-undo';
	
	const LOG_PARTIALINVOICES_REFRESH = 'ts/partialinvoices-refresh';
	const LOG_PARTIALINVOICES_MARK = 'ts/partialinvoices-mark';
	const LOG_PARTIALINVOICES_UNMARK = 'ts/partialinvoices-unmark';
	const LOG_PARTIALINVOICES_ADDED = 'ts/partialinvoices-added';

	const SERVICE_DEACTIVATED_COMPLETELY = 'deactivated_completely';
	const SERVICE_DEACTIVATED_PARTLY = 'deactivated_partly';
	const SERVICE_ACTIVE = 'all_active';
	const NO_SERVICE_AVAILABLE = 'no_available';

	/**
	 * Returns an array of Ext_TC_Countrygroup ids, that contain the inquiry's country
	 * @return array
	 */
	public function getCountryGroupList(): array {

		if ($this->hasAgency()) {
			$countryIso = $this->getAgency()->ext_6;
		} else {
			$countryIso = $this->getCustomer()->getAddress()->country_iso;
		}

		return collect(array_filter(\Ext_TC_Countrygroup::query()->get()->toArray(), function ($countryGroup) use ($countryIso) {
			return in_array($countryIso, reset($countryGroup->getJoinedObjectChilds('SubObjects'))->countries);
		}))->pluck('id')->toArray();

	}

	public function getPaymentReminders($bOnlyDate = false){
		
		$aReminders = $this->payment_reminders;
		
		$aList = array();
		
		foreach($aReminders as $aReminder){
			
			$sReminder = $aReminder['date'];
			
			$sFormat = '%Y-%m-%dT%H:%M:%S';			
			if($bOnlyDate) {
				$sFormat = '%Y-%m-%d';	
			}
			
			$sReminder = strftime($sFormat, strtotime($sReminder));
			
			$aList[] = $sReminder;
		}
		
		return $aList;
	}

	public function getFormatedPaymentReminders($bOnlyDate = false){
		$aPaymentReminders = (array) $this->getPaymentReminders($bOnlyDate);		
		$iCount = count($aPaymentReminders);

		if($iCount == 0) {
			return '';
		}
		
		$sLastDate = array_pop($aPaymentReminders);
		
		$oFormat = new Ext_Thebing_Gui2_Format_Date_Time();
		$sDate = str_replace('T', ' ', $sLastDate);
		
		$sFormated = $iCount.' | '.$oFormat->format($sDate);
		
		return $sFormated;
	}

	public function getAccommodationInfo($sInfo = 'detail') {
		
		$aAllocations = $this->getAllocations();
		
		$aInfo = array();
		
		foreach($aAllocations as $oAllocation) {
			$oAcco = $oAllocation->getAccommodationProvider();
			$oRoom = $oAllocation->getRoom();

			if(is_object($oAcco)) {
				switch($sInfo) {
					case 'full':
						$aData = array();
						$aData[] = '<strong>'.$oAcco->getName().'</strong>';
						$oAddInfo = function($sLabel, $sData) use(&$aData) {
							$sData = trim($sData);
							if(!empty($sData)) {
								$aData[] = L10N::t($sLabel, Ext_Thebing_Inquiry_Gui2::TRANSLATION_PATH).': '.$sData;
							}
						};
						$oAddInfo('Raum', $oRoom->getName());
						if(!empty($oAllocation->bed)) {
							$oAddInfo('Bett', $oAllocation->bed);
						}
						$oAddInfo('Adresse', $oAcco->street);
						$oAddInfo('Adresszusatz', $oAcco->address_addon);
						$oAddInfo('Stadt', $oAcco->zip.' '.$oAcco->city);
						$oAddInfo('Telefon', $oAcco->phone);
						$oAddInfo('Telefon 2', $oAcco->phone2);
						$oAddInfo('Handy', $oAcco->mobile_phone);

						$aInfo[] = implode('<br />', $aData);
						
						break;
					case 'detail':

						$sInfo = $oAcco->getName().' | '.$oRoom->getName();
						if($oAllocation->bed > 0) {
							$sInfo .= ' | '.L10N::t('Bett', Ext_Thebing_Inquiry_Gui2::TRANSLATION_PATH).' '.$oAllocation->bed;
						}

						$aInfo[] = $sInfo;
						
						break;
						
					case 'room':

						$aInfo[] = $oRoom->getName();
						
						break;
						
					case 'bed':

						$aInfo[] = (($oAllocation->bed > 0)?$oAllocation->bed:'');
						
						break;
					case 'room_bed':

						$aInfo[] = $oRoom->getName().' - '.(($oAllocation->bed > 0)?$oAllocation->bed:'');
						
						break;
						
				}
			}
		}
	
		return $aInfo;
	}

	public function getCountry($sLang = '')
	{
		$aCountries = Ext_Thebing_Country_Search::getLocalizedCountries($sLang);
		
		$oCustomer	= $this->getCustomer();
		
		$oAddress	= $oCustomer->getAddress();
		
		$sCountry	= $oAddress->country_iso;
		
		if(isset($aCountries[$sCountry]))
		{
			return $aCountries[$sCountry];
		}
		else
		{
			return null;
		}
	}

	/**
	 * @return Ext_Thebing_Client_Inbox
	 */
	public function getInbox() {
		return Ext_Thebing_Client_Inbox::getByShort($this->inbox);
	}

	/**
	 * Liefert die Schule.
	 *
	 * @return Ext_Thebing_School
	 */
	public function getSchool() {

		$aJourneys = $this->getJourneys();

		if(!empty($aJourneys)){
			$oJourney = reset($aJourneys);
			/** @var Ext_TS_Inquiry_Journey $oJourney */
			return $oJourney->getSchool();
		}

		// TODO Das ist auch ganz großer Mist und sollte entfernt werden
		return Ext_Thebing_School::getSchoolFromSession();

	}

	/**
	 * @return Ext_TS_Inquiry_Journey[]
	 */
	public function getJourneys() {
		// true ist für das angebotstool wichtig da wir in ein leeres inquiry childs setzen und dies ebrauchen
		$aJourneys = $this->getJoinedObjectChilds('journeys', true);
		return (array)$aJourneys;
	}

	/**
	 * Da überall nur eine Inquiry herumgereicht wird, es aber eigentlich um die Journey als Kontext geht,
	 * muss diese ggf. gesetzt werden. Bei den Buchungen hat das keine Relevanz, bei den Anfragen aber sehr wohl schon,
	 * da es mehrere Kombinationen = Journeys geben kann.
	 *
	 * @param Ext_TS_Inquiry_Journey $oJourney
	 */
	public function setJourneyContext(Ext_TS_Inquiry_Journey $oJourney = null) {
		$this->_oJourney = $oJourney;
	}

	/**
	 * @return Ext_TS_Inquiry_Journey 
	 */
	public function getJourney($bCreateIfNone = true) {

		if($this->_oJourney === null) {

			$aJourneysAll = $this->getJourneys();

			// Kompatibilität: Entweder muss der Journey, mit dem gearbeitet werden, gesetzt werden, oder es wird der gewählt, der nur einmal vorkommen darf
			$aJourneys = array_filter($aJourneysAll, function (Ext_TS_Inquiry_Journey $oJourney) {
				return $oJourney->type & Ext_TS_Inquiry_Journey::TYPE_BOOKING;
			});

			// Da die Anfragen ein Dummy-Objekt haben, aber hier auch reinspringen, darf nicht einfach eines vom Typ Buchung erzeugt werden
			if (
				empty($aJourneys) &&
				!empty($aJourneysAll) &&
				(int)$this->type === self::TYPE_ENQUIRY
			) {
				$aJourneys = $aJourneysAll;
			}

			if(!empty($aJourneys)) {
				$oJourney = reset($aJourneys);
			} elseif($bCreateIfNone === true) {
				$oSchool = $this->getSchool();
				$oJourney = $this->getJoinedObjectChild('journeys');
				$oJourney->type = Ext_TS_Inquiry_Journey::TYPE_BOOKING; // Wird eigentlich als Default gesetzt
				$oJourney->school_id = $oSchool->getId();
				$oJourney->productline_id = $oSchool->getProductLineId();
			} else {
				$oJourney = null;
			}

			$this->_oJourney = $oJourney;

		}

		return $this->_oJourney;
	}

	/**
	 * get the booker
	 * @return Ext_TS_Inquiry_Contact_Booker
	 */
	public function getBooker($createNew=false) {

		$bookers = $this->getJoinTableObjects('bookers');
		$booker = reset($bookers);
		
		if($booker) {
			return $booker;
		}
		
		if($createNew) {
			return $this->getJoinTableObject('bookers');
		}
		
		return null;
	}

	/**
	 * get a list of all Travellers of the inquiry
	 * @return Ext_TS_Inquiry_Contact_Traveller[]
	 */
	public function getTravellers(){
		$aList = $this->getJoinTableObjects('travellers');
		return (array)$aList;
	}

	/**
	 *
	 * @return Ext_TS_Inquiry_Contact_Emergency 
	 */
	public function getEmergencyContact(){
		
		if(
			$this->_oEmergenyContact === null
		) {
			
			$oContact = $this->getJoinedObjectChildByValueOrNew('other_contacts', 'type', 'emergency');

			$this->_oEmergenyContact = $oContact;
		}	

		return $this->_oEmergenyContact;
	}

	/**
	 *
	 * @param bool $createNewIfNotExists
	 * @return ?Ext_TS_Inquiry_Contact_Emergency
	 * @throws Exception
	 */
	public function getParent(bool $createNewIfNotExists = false): ?Ext_TS_Inquiry_Contact_Emergency {

		if ($createNewIfNotExists) {
			return $this->getJoinedObjectChildByValueOrNew('other_contacts', 'type', 'parent');
		} else {
			/** @var ?Ext_TS_Inquiry_Contact_Emergency */
			return $this->getJoinedObjectChildByValue('other_contacts', 'type', 'parent');
		}

	}

	public function getOtherContacts() {

		$contacts = $this->getJoinedObjectChilds('other_contacts');

		$contacts = array_filter($contacts, function($contact) {
			return ($contact->type !== 'emergency');
		});

		return $contacts;
	}

	/**
	 * @TODO Muss hier nicht setInquiry() eingebaut werden?
	 * @see getCustomer()
	 *
	 * @return Ext_TS_Inquiry_Contact_Traveller 
	 */
	public function getFirstTraveller(Ext_TS_Inquiry_Contact_Traveller $oNewTraveller = null){
		
		if(
			$this->_oFirstTraveller === null
		) {
			$aTravellers = $this->getTravellers();

			if(!empty($aTravellers)) {
				$oTraveller = reset($aTravellers);
			} else {
				
				if(is_object($oNewTraveller)) {
					$oTraveller = $oNewTraveller;
				} else { 
					$oTraveller = $this->getJoinTableObject('travellers', 0);
				}
			}
			
			$this->_oFirstTraveller = $oTraveller;
		}

		if ($this->type == self::TYPE_ENQUIRY) {
			$this->_oFirstTraveller->bCheckGender = false;
		}
	
		return $this->_oFirstTraveller;
	}

	public function resetFirstTraveller():void {
		$this->_oFirstTraveller = null;
	}
	
	/**
	 * Geburtsdatum des ersten Reisenden
	 * @return mixed
	 */
	public function getFirstTravellerBirthday() {
		$oTraveller = $this->getFirstTraveller();
		$sBirthday = $oTraveller->getBirthday();
		
		// Wichtig für Index
		if(
			$sBirthday == '0000-00-00' ||
			$sBirthday == ''
		) {
			$sBirthday = null;
		}
		
		return $sBirthday;
	}

	public function getFirstTravellerAgeAtCourseStart() {

		$firstCourse = $this->getFirstCourse();

		if(!$firstCourse instanceof Ext_TS_Inquiry_Journey_Course) {
			return null;
		}

		$firstCourseStartTime = new DateTime($firstCourse->getFrom());
		$ageAtCourseStart = $this->getFirstTraveller()->getAge($firstCourseStartTime);

		// Wichtig für Index
		if(
			$ageAtCourseStart == 0 ||
			$ageAtCourseStart == ''
		) {
			$ageAtCourseStart = null;
		}

		return $ageAtCourseStart;
	}

	/**
	 * get a list of all Travellers of the inquiry
	 * @return Ext_TS_Inquiry_Matching
	 */
	public function getMatchingData(){

		if(
			$this->_oMatchingData === null
		){
			$aMatchingData = $this->getJoinedObjectChilds('matching_data');	

			if(!empty($aMatchingData)) {
				$oMatching = reset($aMatchingData);
			} else {
				$oMatching = $this->getJoinedObjectChild('matching_data');
			}

			$this->_oMatchingData = $oMatching;
		}

		// @TODO Entfernen (bidirectional ergänzen?)
		$this->_oMatchingData->inquiry_id = $this->id;

		return $this->_oMatchingData;

	}

	/**
	 * Liefert die Visa Daten. Da wir momentan NUR einen traveller und Journey haben, holen wir die Daten hier.
	 * @return Ext_TS_Inquiry_Journey_Visa 
	 */
	public function getVisaData() {

		$oJourney = $this->getJourney();
		$oContact = $this->getCustomer(); 
		$oVisaData = Ext_TS_Inquiry_Journey_Visa::searchData($oJourney, $oContact);

		return $oVisaData;
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
	 * @return array 
	 */
	public function loadRoomSharingCustomers() {

		$mResult = array();
		if($this->id < 0) {
			return $mResult;
		}

		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_roomsharing`
			WHERE
				`master_id` = :master_id
			GROUP BY
				`share_id`
			;
		";
		$aSql = array(
			'master_id'  => (int)$this->id
		);
		
		$mResult = DB::getPreparedQueryData($sSql, $aSql);

		return $mResult;

	}

	/**
	 * Stornobetrag
	 * @return float 
	 */
	public function getCancellationAmount() {
		$oAmount = new Ext_Thebing_Inquiry_Amount($this);
		return (float)$oAmount->getCancellationAmount();
	}

	/**
	 * Gibt den zugehörigen Kunden zurück
	 *
	 * @global mixed $user_data
	 * @param Ext_TS_Inquiry_Contact_Traveller $oCustomer
	 * @return Ext_TS_Inquiry_Contact_Traveller
	 */
	public function getCustomer(Ext_TS_Inquiry_Contact_Traveller $oCustomer = null) {

		global $user_data;

		$oTraveler = $this->getFirstTraveller($oCustomer);		
		$oTraveler->setInquiry($this);

		if($oTraveler->id <= 0) {
			$oTraveler->creator_id = (int)$user_data['id'];
			$oTraveler->editor_id = (int)$user_data['id'];
		}

		return $oTraveler;

	}

	/**
	 * @return Ext_Thebing_Agency|bool
	 */
	public function getAgency() {

		if($this->agency_id == 0) {
			return null;
		}

		$oAgency = parent::getAgency();

		$mReturn = null;

		if($oAgency->id > 0){
			$mReturn = $oAgency;
		}

		return $mReturn;
	}

	/**
	 * @return bool
	 */
	public function isSponsored() {
		return (bool)$this->sponsored;
	}

	/**
	 * @return TsSponsoring\Entity\Sponsor|null
	 */
	public function getSponsor() {

		if($this->sponsor_id == 0) {
			return null;
		}

		return TsSponsoring\Entity\Sponsor::getInstance($this->sponsor_id);

	}

	/**
	 * @return Ext_Thebing_Inquiry_Group|Ext_TS_Enquiry_Group
	 */
	public function getGroup() {

		// Es darf nicht einfach getJoinedObject aufgerufen werden, da ansonsten dieses komische saveParents() eine Gruppe erzeugt!
		if($this->group_id == 0) {
			return null;
		}
		
		$oGroup = $this->getJoinedObject('group');

		if($oGroup->id > 0) {
			return $oGroup;
		}

		return null;

	}

	/**
	 * @inheritdoc
	 */
	public function hasGroup() {

		// Wenn es keine group_id gibt, muss auch nichts weiter geprüft werden
		if($this->group_id == 0) {
			return false;
		}

		return parent::hasGroup();

	}

//	/**
//	 *
//	 * @param type $bLocalPayments
//	 * @param type $iRefund
//	 * @param type $bCalcualteAgencyBrutto
//	 * @return type
//	 */
//	public function getPayedAmount($bLocalPayments = 0, $iRefund = 0, $bCalcualteAgencyBrutto = false) {
//
//		$fAmount = 0;
//		$iTypePayed = 1; // Vor Anreise
//		// Welche Art der Bezahlung soll geholt werden
//		if ($bLocalPayments != 0) {
//			$iTypePayed = 2; // Vorort
//		} elseif ($iRefund != 0) {
//			$iTypePayed = 3; // Refound (auszahlung)
//		}
//
//		$aDocuments = $this->getDocuments('invoice', true, true);
//
//		foreach ((array) $aDocuments as $oDocument) {
//			$fAmount += $oDocument->getPayedAmount($this->getCurrency(), $iTypePayed, $bCalcualteAgencyBrutto);
//		}
//
//		return $fAmount;
//	}

	/**
	 * Sucht, falls vorhanden, die aktuellste Rechnungsversion
	 * @return Ext_Thebing_Inquiry_Document_Version
	 */
	public function getLatestInvoiceVersion() {

		$iDocumentId = Ext_Thebing_Inquiry_Document_Search::search($this->id, 'invoice');

		// Kein Dokument, kein Betrag!
		if($iDocumentId == 0) {
			return null;
		}

		// Letztes Document
		$oDocument = Ext_Thebing_Inquiry_Document::getInstance($iDocumentId);
		// Letzte Version holen
		$oVersion = $oDocument->getLastVersion();

		if(!$oVersion){
			$oVersion = $oDocument->newVersion();
		}

		return $oVersion;

	}

	public function getVersionAmount($sTyp, $bLastDocument = true)
	{
		$aVersions = array();
		
		if($bLastDocument)
		{
			$bReturnAllData = false;
		}
		else
		{
			$bReturnAllData = true;
		}
		
		if($this->has_invoice == 1)
		{
			$oSearch	= new Ext_Thebing_Inquiry_Document_Type_Search();
			$oSearch->addSection('invoice_without_proforma');
			$oSearch->add('creditnote');

			$aDocuments = $this->getDocuments($oSearch, $bReturnAllData, true);
		}
		else
		{
			$aDocuments = $this->getDocuments('invoice', $bReturnAllData, true);
		}
		
		if(!$bReturnAllData && !empty($aDocuments))
		{
			$aDocuments = array($aDocuments);
		}

		$oIgnoreDocument = false;
		
		$aDocuments = (array)$aDocuments;

		foreach($aDocuments as $oDocument)
		{
			if($oIgnoreDocument === $oDocument)
			{
				continue;
			}

			if($oDocument->type == 'creditnote')
			{
				$oParent = $oDocument->getParentDocument();

				if($oParent)
				{
					$oIgnoreDocument = $oParent;
				}
			}

			$oLastVersion = $oDocument->getLastVersion();

			if($oLastVersion instanceof Ext_Thebing_Inquiry_Document_Version)
			{
				$aVersions[] = $oLastVersion;
			}
		}
		
		$fAmount = 0;
		
		foreach($aVersions as $oVersion)
		{
			$fAmount += $oVersion->getAmount(true, true, $sTyp);
		}
		
		
		return $fAmount;
	}
	
	/**
	 *
	 * @param type $bInitalCost
	 * @return type 
	 */
	public function getNetAmount($bLastDocument = true) {
	
		return $this->getVersionAmount('netto', $bLastDocument);

	}
	
	/**
	 *
	 * @param type $bInitalCost
	 * @return type 
	 */
	public function getProvisionAmount($bLastDocument = true){

		return $this->getVersionAmount('creditnote', $bLastDocument);

	}

//	/**
//	 * @param bool $bCalculateNew
//	 * @param bool $bSave
//	 * @return int|string
//	 */
//	public function getCreditAmount($bCalculateNew = false, $bSave=true){
//
//		if(
//			$this->amount_credit == 0 ||
//			$bCalculateNew
//		){
//
//			$iAmountCredit = 0;
//
//			// letzte Diff rechnung holen
//			$aDocuments		= Ext_Thebing_Inquiry_Document_Search::search($this->id, 'invoice', true, true);
//
//			foreach((array)$aDocuments as $oDocument){
//
//				if(
//					$oDocument->type == 'brutto_diff_special'
//				){
//					$oLastVersion		= $oDocument->getLastVersion();
//					$iAmount			= $oLastVersion->getAmount(true, true);
//					$iAmountCredit		+= $iAmount;
//				}
//			}
//
//			if($iAmountCredit > 0){
//				$iAmountCredit = 0;
//			}
//
//			$this->amount_credit = $iAmountCredit;
//			if($bSave) {
//				$this->save();
//			}
//
//
//			return $iAmountCredit;
//		}
//
//		return $this->amount_credit;
//
//	}
	
	public function getOpenPaymentAmount($sFilter = ''){
		
		$fAmount = 0;
		
		/**
		 * #5397
		 * 
		 * Werte müssen auf 2 Nachkommastellen gerundet werden. Bei DID war folgender Fall:
		 * amount: 1260.00004
		 * amount_payed: 1260.00000
		 * 
		 * Der Schüler wurde unter fälligen Zahlungen angezeigt
		 *
		 * Gleiches Verhalten bei Dokumenten!
		 */
		
		if(empty($sFilter) || $sFilter == 'prior_to_arrival'){
			$fTotalAmount = round($this->amount, 2);
			$fAmount = bcadd($fAmount, $fTotalAmount);
		} 
		
		if(empty($sFilter) || $sFilter == 'at_school'){
			$fAmountInitial = round($this->amount_initial, 2);
			$fAmount = bcadd($fAmount, $fAmountInitial);
		}
		
		if(empty($sFilter) || $sFilter == 'prior_to_arrival'){
			$fAmountPayedPriorToArrival = round($this->amount_payed_prior_to_arrival, 2); 
			$fAmount = bcsub($fAmount, $fAmountPayedPriorToArrival);
		} 
		
		if(empty($sFilter) || $sFilter == 'at_school'){
			$fAmountPayedAtSchool = round($this->amount_payed_at_school, 2); 
			$fAmount = bcsub($fAmount, $fAmountPayedAtSchool);
		}
		
		if(empty($sFilter))
		{
			$fAmountPayedRefund = round($this->amount_payed_refund, 2); 
			$fAmount = bcsub($fAmount, $fAmountPayedRefund);
		}
		
		return (float)$fAmount;
	}

	/**
	 * Betrag der Buchung berechnen (und in der Datenbank abspeichern)
	 * Diese Methode berechnet nur den Betrag vor Anreise ODER vor Ort, NICHT beides! Das muss summiert werden!
	 *
	 * @param bool $bInitalAmount Betrag vor Ort berechnen (ansonsten Betrag vor Anreise) –
	 * @param bool $bCalculateNew Betrag dennoch neu berechnen (passiert sonst nur initial)
	 * @param string $sType null|brutto|netto Bei brutto oder netto wird der Betrag nicht neu abgespeichert!
	 * @param bool $bSave
	 * @return mixed|string
	 * @throws Exception
	 */
	public function getAmount($bInitalAmount = false, $bCalculateNew = false, $sType = null, $bSave = true) {

		if($sType !== null) {
			if(is_bool($sType)) {
				// Drittes Argument war früher irgendwas für Stornobetrag
				throw new InvalidArgumentException('$sType must not be a boolean');
			}

			$bCalculateNew = true;
		}

		$sAmountField = 'amount';
		if($bInitalAmount == true) {
			$sAmountField = 'amount_initial';
		}
				
		if(
			(
				!$bInitalAmount && 
				$this->changed_amount <= 0
			) || 
			( 
				$bInitalAmount && 
				$this->changed_initial_amount <= 0 
			) || 
			$bCalculateNew == true
		) {

			$aDocuments = [];
			if($this->has_invoice) {
				$aDocuments = Ext_Thebing_Inquiry_Document_Search::search($this->id, 'invoice_without_proforma', true, true);
			} elseif($this->has_proforma) {
				$aDocuments = Ext_Thebing_Inquiry_Document_Search::search($this->id, 'invoice_proforma', true, true);
			}

			$iAmount		= 0;
//			$iAmountCredit	= 0;

			$bFirst = true;
			$sFirstDocType = '';
			$sLastDocType	= '';
			$iLastIsCredit	= 0;

			$bBreakCount = 0;

			foreach($aDocuments as $iKey => $oDocument) {

				$sDocType		= $oDocument->type;
				$iTempAmount	= 0;

				if($bFirst){
					$sFirstDocType = $sDocType;
				}

				/**
				 * Wenn das letzte Document keine Diff Rechnung war beende die Berechnung
				 * da nur letzte Rechnung, X Diff Rechnungen und Gutschriften summiert werden dürfen.
				 */
//				if(
//					!strpos($sLastDocType, 'diff') &&
//					$sLastDocType != '' &&
//					$iLastIsCredit == 0
//				){
//
//					if(
//						$sFirstDocType == 'storno' &&
//						$bBreakCount > 0
//					){
//						// Wenn das letzte Doc. ein Storno ist, so sollen jedoch auch die Summen
//						// aus der letzten Rechnung mit addiert werden und es soll erst beim 2.
//						// ($bBreakCount > 0) abgebrochen werden
//						break;
//					} elseif($sFirstDocType != 'storno') {
//						// Ansonsten ganz "normal" breaken
//						break;
//					}
//
//					$bBreakCount++;
//
//				}
				// -------------------------------------------------------------------------

				$sLastDocType = $sDocType;

				$iLastIsCredit = $oDocument->is_credit;

				$oLastVersion	= $oDocument->getLastVersion();

				// Darf nicht auftreten
				if($oLastVersion === null) {
					continue;
				}

				if($bInitalAmount == false){
					$iTempAmount = $oLastVersion->getAmount(true, false, $sType);
				} else {
					$iTempAmount = $oLastVersion->getAmount(false, true, $sType);
				}

//				if($oDocument->type == 'brutto_diff_special'){
//					$iAmountCredit		= bcadd($iAmountCredit, $iTempAmount);
//				} else {
					$iAmount			= bcadd($iAmount, $iTempAmount);
//				}

				$bFirst = false;
				
			}

//			if($iAmountCredit > 0){
//				$iAmount = bcadd($iAmount, $iAmountCredit);
//			}

			if($sType === null) {
				// Für Code-Suche:
				// $this->amount = $iAmount;
				// $this->amount_initial = $iAmount;
				$this->$sAmountField = $iAmount;

				if(!$bInitalAmount){
					$this->changed_amount = time();
				} else {
					$this->changed_initial_amount = time();
				}

				// Buchungen Ohne Währung darf es nicht geben.
				// in diesem fall setzten wir fest Euro
				// ist bei amount cache aktuallisieren aufgetreten ( alte datensätze )
				if($this->currency_id == 0){
					$this->currency_id = 1;
				}

				// Achtung: Das hier sorgt dafür, dass auch alle Dokumente der Inquiry wieder neu indiziert werden!
				if($bSave) {
					$this->save();
				}
			} else {
				return $iAmount;
			}

		}

		return $this->$sAmountField;
	
	}
	
	public function getTotalAmount(){
		$fAmount        = $this->amount;
		$fAmountInital  = $this->amount_initial;
		$fTotal         = bcadd($fAmount, $fAmountInital);
		return $fTotal;
	}

	public function getTotalPayedAmount(){
		$fAmount        = $this->amount_payed_at_school;
		$fAmountInital  = $this->amount_payed_prior_to_arrival;
		$fTotal         = bcadd($fAmount, $fAmountInital);
		return $fTotal;
	}
	
	public function getFirstCourse($bAsObjectArray = true, $bDatesAsTimestamp=true, $bFromCache=true, $bCheckVisible=true) {

		$aCourses = $this->getCourses($bAsObjectArray, $bDatesAsTimestamp, $bFromCache, $bCheckVisible);

		$oCourse = reset($aCourses);
		return $oCourse;

	}
	
	public function getFirstAccommodation() {

		$aAccommodations = $this->getAccommodations(true);

		$oAccommodation = reset($aAccommodations);
		
		return $oAccommodation;

	}

	public function getLastAccommodation() {
		$aAccommodations = $this->getAccommodations(true);

		$oAccommodation = array_pop($aAccommodations);
		return $oAccommodation;
	}
	
	public function getLastCourse() {
		$aCourses = $this->getCourses(true);
		$oCourse = array_pop($aCourses);
		
		return $oCourse;
	}

	/**
	 * Methode liefert den letzten Kurs (zeitlich) bezogen auf alle gebuchten Kurse
	 */
	public function getLatestCourse(): ?Ext_TS_Inquiry_Journey_Course {

		$courses = $this->getCourses(false);

		if(empty($courses)) {
			return null;
		}

		$tempLastStartDate = 0;
		$tempLastEndDate = 0;
		foreach($courses as $course) {
			if(
				$course['until'] > $tempLastEndDate || # Wenn es ein späteres Kursende gibt ODER
				$course['until'] == $tempLastEndDate && # Wenn das Kursende gleich ist, aber der Kurs später begonnen hat
				$course['from'] > $tempLastStartDate
			) {
				$courseId = $course['id'];
				$tempLastEndDate = $course['until'];
				$tempLastStartDate = $course['from'];
			}
		}

		return Ext_TS_Inquiry_Journey_Course::getInstance($courseId);
	}

	/**
	 * Methode liefert das letzte Kursende bezogen auf alle gebuchten Kurse
	 */
	public function getLatestCourseEnd(): string|null {
		return $this->getServiceFirstStartOrLastestEndDate('course', 'end');
	}

	/**
	 * Methode liefert den Start vom letzten Kurs (zeitlich) bezogen auf alle gebuchten Kurse
	 */
	public function getLatestCourseStart() {

		$latestCourse = $this->getLatestCourse();

		if($latestCourse != null) {
			return $latestCourse->from;
		}
	}

	/**
	 * Methode liefert die Kategorie vom letzten Kurs (zeitlich) bezogen auf alle gebuchten Kurse
	 */
	public function getLatestCourseCategory() {

		$latestCourse = $this->getLatestCourse();

		if($latestCourse != null) {
			$latestTuitionCourse = Ext_Thebing_Tuition_Course::getInstance($latestCourse->course_id);

			return $latestTuitionCourse->getCategory()->getName();
		}
	}

	/**
	 * Methode liefert die Wochen vom letzten Kurs (zeitlich) bezogen auf alle gebuchten Kurse
	 */
	public function getLatestCourseWeeks() {

		$latestCourse = $this->getLatestCourse();
		if($latestCourse != null) {
			return $latestCourse->getWeeks();
		}
	}

	/**
	 * TODO warum nicht direkt das Date-Objekt
	 *
	 * @param $bAsTimestamp
	 * @return int|string|null
	 */
	public function getLatestAccommodationEnd($bAsTimestamp = true) {

		$latestEnd = $this->getCompleteServiceTimeframe(['accommodation']);

		if(!$latestEnd) {
			return null;
		}

		if(!$bAsTimestamp) {
			$latestEnd = $latestEnd->end->format('Y-m-d');
		} else {
			$latestEnd = $latestEnd->end->getTimestamp();
		}

		return $latestEnd;
	}

	/**
	 * Rechnet die Wochen der einzelnen Kurse zusammen
	 * @return int
	 */
	public function getCoursesTotalWeeks() {
		
		$iWeeks = $this->getTuitionIndexValue('total_course_weeks');
		
		return $iWeeks;
	}
	
	public function getCoursesTotalWeeksForIndex() {

		$iWeeks = $this->getCoursesTotalWeeks();
		
		if(empty($iWeeks)) {
			$iWeeks = null;
		}
		
		return $iWeeks;
	}
	
	/**
	 * Rechnet die Wochen der einzelnen Kurse zusammen
	 * @return int
	 */
	public function getCoursesTotalRelativeWeeks() {
		
		$iWeeks = $this->getTuitionIndexValue('total_course_duration');
		
		return $iWeeks;
	}
	
	public function getCoursesTotalRelativeWeeksForIndex() {

		$iWeeks = $this->getCoursesTotalRelativeWeeks();
		
		if(empty($iWeeks)) {
			$iWeeks = null;
		}
		
		return $iWeeks;
	}

	public function getAccommodationsTotalWeeksForIndex()
	{
		$weeks = (int)Ext_TS_Inquiry_Journey_Accommodation::query()
			->where('journey_id', $this->getJourney()->id)
			->where('visible', 1)
			->sum('weeks');

		if($weeks == 0) {
			return null; // Für Index
		}

		return $weeks;
	}

	public function getFirstAccommodationStart($bTimestamps = true) {

		$aAccommodations = $this->getAccommodations(false, false, $bTimestamps);
		$aAccommodation = reset($aAccommodations);
		return $aAccommodation['from'];

	}

	/**
	 * Get Timestamp of last accommodation start
	 *
	 * @return int timestamp of last accommodation start
	 */
	public function getLastAccommodationStart() {

		$aAccommodations = $this->getAccommodations(false);
		$aAccommodation = end($aAccommodations);
		return $aAccommodation['from'];

	}
	
	/**
	 * TODO getLatestAccommodationEnd()?
	 * Get Timestamp of last accommodation end
	 *
	 * @return int timestamp of last accommodation end
	 */
	public function getLastAccommodationEnd() {

		$aAccommodations = $this->getAccommodations(false);
		$aAccommodation = end($aAccommodations);
		
		$mReturn = $aAccommodation['until'];
	
		return $mReturn;

	}

	/**
	 * Methode liefert den ersten Start oder das letzte Ende bezogen auf alle gebuchten angegebenen Leistungen
	 * @return string|null
	 */
	public function getServiceFirstStartOrLastestEndDate(string $service, string $dateType) {
		$firstStartOrLastEndDate = $this->getCompleteServiceTimeframe([$service]);

		if (!empty($firstStartOrLastEndDate)) {
			return $firstStartOrLastEndDate->$dateType->format('Y-m-d');
		}
	}

	public function getFeedbackInvitationSentDates() {

		$feedbackInvitations = Ext_TC_Marketing_Feedback_Questionary_Process::query()
			->where('journey_id', $this->getJourney()->id)
			->get()
			->toArray();

		$result = [];

		// Nur die Feedbacks, die noch nicht ausgefüllt wurden #19831
		foreach ($feedbackInvitations as $feedbackInvitation) {
			if (
				$feedbackInvitation->answered == '0000-00-00 00:00:00' ||
				$feedbackInvitation->answered == false
			) {
				$dateTime = new DateTime($feedbackInvitation->_aData['invited']);
				$result[] = $dateTime->format('Y-m-d');
			}
		}

		return $result; # Formatieren für Elasticsearch Query, weil in der Datenbank die Uhrzeit dabei ist
	}

	public function getCompleteServiceTimeframe($services = null, $onlyVisible = true): ?\Carbon\CarbonPeriod {

		$dates = collect();
		if ($services == null) {
			$services = [
				'course',
				'accommodation',
				'transfer',
				'insurance',
				'activity'
			];
		}

		/** @var $journeys Ext_TS_Inquiry_Journey[] */
		$journeys = $this->getJoinedObjectChilds('journeys', true);

		$iterate = function (array $services, string $fieldFrom, string $fieldUntil) use ($dates) {
			foreach ($services as $service) {
				$from = Carbon::parse($service->$fieldFrom);
				$until = Carbon::parse($service->$fieldUntil);

				// Der 0000-00-00-Quatsch ist auch ein validates Datum (letzte Sekunde in -1) und sollte eigentlich NULL sein
				if ($from->year < 1 || $until->year < 1) {
					continue;
				}

				$dates->add($from);
				$dates->add($until);
			}
		};

		foreach ($journeys as $journey) {
			
			// Bei einer Buchung (auch umgewandelt), nur Buchungs-Journeys beachten
			if($this->type & self::TYPE_BOOKING) {
				if(!($journey->type & \Ext_TS_Inquiry_Journey::TYPE_BOOKING)) {
					continue;
				}
			} elseif($this->type & self::TYPE_ENQUIRY) {
				if(!($journey->type & \Ext_TS_Inquiry_Journey::TYPE_REQUEST)) {
					continue;
				}				
			}
			
			// Für die Unterkünfte wird in den Statistiken der Zeitraum zwar auch verändert, aber niemals verlängert
			foreach ($services as $service) {
				if ($service === 'transfer') {
					$iterate($journey->getServiceObjects($service), 'transfer_date', 'transfer_date');
				} else {
					$iterate($journey->getServiceObjects($service), 'from', 'until');
				}
			}
		}

		if ($dates->isEmpty()) {
			return null;
		}

		return $dates->min()->toPeriod($dates->max());

	}

	/**
	 * @param ?string $sCreatedDocumentType
	 * @param bool $bSave
	 * @param bool $documentIsDraft
	 * @throws ErrorException
	 */
	public function setInquiryStatus($sCreatedDocumentType = '', $bSave = true, bool $documentIsDraft = false) {

		if (!($this->type & self::TYPE_BOOKING)) {
			return;
		}

		if(!empty($sCreatedDocumentType)) {

			if(
				$sCreatedDocumentType == 'proforma_brutto' ||
				$sCreatedDocumentType == 'proforma_netto'
			){
				$this->has_proforma = 1;
			}

			if(
				(
					$sCreatedDocumentType == 'brutto' ||
					$sCreatedDocumentType == 'netto' ||
					$sCreatedDocumentType == 'brutto_diff' || # Gruppenmitglieder können als einzige / erste Rechnung eine Diff haben
					$sCreatedDocumentType == 'netto_diff'
				) &&
				!$documentIsDraft
			){
				$this->has_invoice = 1;
			}
		}

		if (
			System::d('booking_auto_confirm') == \Ext_Thebing_Client::BOOKING_AUTO_CONFIRM_ALL || (
				System::d('booking_auto_confirm') == \Ext_Thebing_Client::BOOKING_AUTO_CONFIRM_ONLY_SYSTEM &&
				\System::getCurrentUser()?->exist()
			)
		) {
			$this->confirm();
		}

		if($bSave) {
			$this->save();
		}

		if ($this->exist()) {
			Ext_Gui2_Index_Stack::add('ts_inquiry', $this->id, 0);
		}

	}

	/**
	 *
	 */
	public function confirm() {
		if(!$this->isConfirmed()) {
			$this->log('INQUIRY_CONFIRMED');
			$this->confirmed = time();
			\Ts\Events\Inquiry\ConfirmEvent::dispatch($this);
		}
	}

	/**
	 * @param bool $bAsObjectArray
	 * @param bool $bDatesAsTimestamp
	 * @param bool $bFromCache
	 * @param bool $bCheckVisible
	 * @return Ext_TS_Inquiry_Journey_Course[]
	 */
	public function getCourses($bAsObjectArray = true, $bDatesAsTimestamp=true, $bFromCache=true, $bCheckVisible=true){

		// $bFromCache nicht beachten! sonst werden ggf. neue Kurse wieder überschrieben beim SR speichern!
		$oJourney = $this->getJourney();

		$aResult = (array)$oJourney->getCoursesAsObjects($bCheckVisible);

		if($bAsObjectArray == false) {
			$aCourses = array();
			foreach($aResult as $oCourse) {
				$aCourse = $oCourse->getData();
				if($bDatesAsTimestamp){
					$oDate = new DateTime($aCourse['from']);
					$aCourse['from'] = $oDate->getTimestamp();
					$oDate = new DateTime($aCourse['until']);
					$aCourse['until'] = $oDate->getTimestamp();
				}
				$aCourses[] = $aCourse;
			}
			return $aCourses;
		}

		return $aResult;

	}

	/**
	 * @param bool $bAsObjectArray
	 * @param bool $bWithUnvisible
	 * @param bool $bTimestamps Altes Verhalten (DEPRECATED)
	 * @return Ext_TS_Inquiry_Journey_Accommodation[]
	 */
	public function getAccommodations($bAsObjectArray = true, $bWithUnvisible = false, $bTimestamps = true) {

		$oJourney = $this->getJourney();

		$aJourneyAccommodations = (array)$oJourney->getAccommodationsAsObjects(!$bWithUnvisible);

		// Altes Verhalten mit Query und UNIX_TIMESTAMP()
		if(!$bAsObjectArray) {
			$aJourneyAccommodations = array_map(function(Ext_TS_Inquiry_Journey_Accommodation $oJourneyAccommodation) use ($bTimestamps) {

				$aData = $oJourneyAccommodation->getData();
				$aData['inquiry_id'] = $this->id;

				// UNIX_TIMESTAMP()
				if(
					DateTime::isDate($oJourneyAccommodation->from, 'Y-m-d') &&
					DateTime::isDate($oJourneyAccommodation->until, 'Y-m-d')
				) {
					if($bTimestamps) {
						$aData['from'] = (new DateTime($oJourneyAccommodation->from))->getTimestamp();
						$aData['until'] = (new DateTime($oJourneyAccommodation->until))->getTimestamp();
					} else {
						$aData['from'] = $oJourneyAccommodation->from;
						$aData['until'] = $oJourneyAccommodation->until;
					}
				} else {
					$aData['from'] = null;
					$aData['until'] = null;
				}

				return $aData;

			}, $aJourneyAccommodations);

		}

		return $aJourneyAccommodations;

	}

    /**
     * @return array|Ext_TS_Inquiry_Journey_Activity[]
     */
	public function getActivities() {

		$oJourney = $this->getJourney();
		$aJourneyActivities = $oJourney->getActivitiesAsObjects(true);

		return $aJourneyActivities;
	}

	/**
	 * Alle Versicherungen der Buchung
	 *
	 * @deprecated
	 * @param bool $bOnlyActive
	 * @return Ext_TS_Inquiry_Journey_Insurance[]
	 */
	public function getInsurances($bOnlyActive = false) {

		$oJourney = $this->getJourney();
		return $oJourney->getInsurancesAsObjects($bOnlyActive);

	}
	
	/**
	 *
	 * @return type 
	 */
	public function getFamiliePicturePdf(){
		
		ini_set("memory_limit", '1G');
		
		$aDocuments = array();
		// Familienbilder einlesen
		$oSchool = $this->getSchool();
		$aFamilie = Ext_Thebing_Accommodation_Util::getAccommodationProviderFromInquiryId($this->id);
		$iFamilieId = (int)($aFamilie['id'] ?? 0);

		if($iFamilieId > 0) {

			$oAcco = new Ext_Thebing_Accommodation($iFamilieId);
			$aBack = $oAcco->getUploadedImages();
			$aList = array();

			foreach((array)$aBack as $sPath => $mValue) {
				$aTemp = array();
				$aTemp['path'] = $sPath;
				$aList[] = $aTemp;
			}

			if(count($aList) > 0) {

				$iMaxX = 210;
				$iDocHeight = 297;
			
				// Objekt aus FPDF Klasse erzeugen
				$pdf = new Ext_Thebing_Pdf();
				
				// Dokument öffnen
				$pdf->Open();
				
				// Erste Seite erstellen
				$pdf->AddPage();
				$Y = $pdf->getY();
				foreach($aList as $aPicture) {

					//aktuelle bildgröße ermitteln
					$bild_daten = getimagesize($aPicture['path']);
					$iBreite = $bild_daten[0];
					$iHoehe = $bild_daten[1];
					//vergleich der bilddaten
					// 1) bildbreite
					if ($iBreite > $iMaxX) {
						 
						 $iFactor = $iMaxX / $iBreite;

						$iX = $iMaxX;
						$iY = $iHoehe * $iFactor;
						  
					} else {
						$iX = $iBreite;
						$iY = $iHoehe;
					}

					$Y = $pdf->getY();
					
					if(($Y + $iY + 10) > $iDocHeight){
						$pdf->AddPage();
						$Y = $pdf->getY();
					}
					
					// Bild einfügen (Position x = 0 / y = 0)
					$pdf->Image($aPicture['path'], 0, $Y,$iX);
							
					$pdf->setY($Y+$iY+10);
				}
				
				$sPath = $oSchool->getSchoolFileDir()."/inquirypdf/";
				$sPath2 = $oSchool->getSchoolFileDir(false)."/inquirypdf/";				
				$sPdfPath = $sPath.'pictures.pdf';
				// Dokument ausgeben
				$pdf->Output($sPdfPath, 'F');
				
				//$pdf->closeParsers();
				@chmod($sPdfPath, 0777);

				$aDocuments[$sPath2.'pictures.pdf'] = L10N::t('Bilder der Gastfamilie');

			}
			
		}
		
		return $aDocuments;
	}
	
	/**
	 *
	 * @param type $language
	 * @param type $iAccommodation
	 * @return type 
	 */
	public function getAvailableFamilieDocuments($language = "de", $iAccommodation = 0, $asObjects = false) {

		if(empty($language)) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			$language = $oSchool->getInterfaceLanguage();
		}

		$sNameField = 'name_'.$language;

		$aAllocations = Ext_Thebing_Allocation::getAllocationByInquiryId($this->id,$iAccommodation,true,false);

		$aBack = array();
		foreach((array)$aAllocations as $aAllocation) {

			$sSql = "
				SELECT
					`room`.*,
					`type`.#type_field as type
				FROM	
					`kolumbus_rooms` as `room`,
					`kolumbus_accommodations_roomtypes` as `type`
				WHERE 
					`room`.id 	=	:id 
				AND
					`type`.`id`	=	`room`.`type_id`
				ORDER BY
					`room`.`name` ASC";

			$aSql = array(
				'id'=> (int)$aAllocation['room_id'],
				'type_field' => $sNameField
				);
			$aRooms = DB::getPreparedQueryData($sSql,$aSql);
			$aRoom = $aRooms[0];
			$idProvider = (int)$aRoom['accommodation_id'];

			if ($idProvider > 0) {
				$oAcco = new Ext_Thebing_Accommodation($idProvider);
				$aTemp = $oAcco->getUploadedPDFs($language, !$asObjects);
				$aBack = array_merge($aBack, $aTemp);
			}
		
		}

		return $aBack;
		
	}

	/**
	 * @return string
	 */
	public function getCustomerEmail(){

		$oCustomer = $this->getCustomer();
		$sEmail = $oCustomer->getEmail();

		if(!\Util::checkEmailMX($sEmail)) {
			$sEmail = '';
		}

		return $sEmail;

	}

	/**
	 * Alle Unterkunftsanbieter die dieser Buchung zugewiesen sind.
	 *
	 * @param int $iAccommodation
	 * @return Ext_Thebing_Accommodation[]
	 */
	public function getAccommodationProvider($iAccommodation = 0) {

		$sCacheKey = 'acco_'.$iAccommodation;

		if(!isset($this->_aGetAccommodationProviderCache[$sCacheKey])) {

			$aAllocations = Ext_Thebing_Allocation::getAllocationByInquiryId($this->id, $iAccommodation, true, false);

			$aFamilyIds = array_map(
				function(array $aAllocation) {
					return $aAllocation['family_id'];
				},
				$aAllocations
			);

			$aFamilys = Ext_Thebing_Accommodation::getListByIds($aFamilyIds);

			$this->_aGetAccommodationProviderCache[$sCacheKey] = $aFamilys;

		}

		return array_values($this->_aGetAccommodationProviderCache[$sCacheKey]);

	}
	
	/**
	 * @param bool $bGroupByTimerange
	 * @return array
	 */
	public function getTuitionTeachers($bGroupByTimerange=false, $bAsObjects=false) {

		$sSelect = "";
		$sJoin = "";
		$sGroupBy = "";

		if($bGroupByTimerange) {
			$sSelect = ",
				`ktt`.`from`,
				`ktt`.`until`,
				`ktb`.`week`,
				`ktbd`.`day`
			";
			$sJoin = " INNER JOIN
				`kolumbus_tuition_blocks_days` `ktbd` ON
					`ktbd`.`block_id` = `ktb`.`id`
			";
			$sGroupBy = ",
				`ktb`.`week`,
				`ktbd`.`day`
			";
		}

		$sSql = "
			SELECT
				`ktb`.`teacher_id` `teacher_id`,
				GROUP_CONCAT(DISTINCT `ktc`.`id`) `course_ids`,
				CONCAT(`kt`.`lastname`,', ',`kt`.`firstname`) `teacher_name`
				{$sSelect}
			FROM
				`ts_inquiries_journeys` `ts_i_j` INNER JOIN
				`ts_inquiries_journeys_courses` `ts_ijc` ON
					`ts_ijc`.`journey_id` = `ts_i_j`.`id` AND
					`ts_ijc`.`active` = 1 INNER JOIN
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` ON
					`ktbic`.`inquiry_course_id` = `ts_ijc`.`id` INNER JOIN
				`kolumbus_tuition_blocks` `ktb` ON
					`ktb`.`id` = `ktbic`.`block_id` INNER JOIN
				`kolumbus_tuition_templates` `ktt` ON
					`ktt`.`id` = `ktb`.`template_id` INNER JOIN
				`ts_teachers` `kt` ON
					`kt`.`id` = `ktb`.`teacher_id` INNER JOIN
				`kolumbus_tuition_courses` `ktc` ON
					`ktc`.`id` = `ts_ijc`.`course_id`
				{$sJoin}
			WHERE
				`ts_i_j`.`inquiry_id` = :inquiry_id AND
			    `ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
				`ts_i_j`.active = 1 AND
				`ktbic`.active = 1 AND
				`ktb`.active = 1 AND
				`ktt`.active = 1
			GROUP BY
				`ktb`.`teacher_id`
				{$sGroupBy}
		";

		$aSql = array(
			'inquiry_id'=> (int)$this->id,
		);

		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		if ($bAsObjects) {
			return array_map(fn ($data) => \Ext_Thebing_Teacher::getInstance($data['teacher_id']), $aResult);
		}

		return $aResult;
	}
	
	public function __set($sField, $mValue) {

		if(
			$sField == 'accommodation_id' ||
			$sField == 'allocated_accommodations'
		) {
			// nichts machen, ich musste das hier leider machen
			// da bei den Unterkunftsanbietern wiedermal alles vergewaltigt wurde!
			// da wird die simple view geladen und der query manipuliert
			// und es wir als "fremdschlüssel" "accommodation_id" gesetzt!
			// das kann natürlich nur im query klappen aber die WDBasic kennt das natürlich nicht...
			// keine ahnung wie das geplant war oder mal geklappt hat daher dieser abschnitt
			// beschwerden bitte an #3163
			// gleiche mit allocated_accommodations
		} else {
			parent::__set($sField, $mValue);
		}
		
	}
	
	public function __get($sField){

		$mValue = '';

		Ext_Gui2_Index_Registry::set($this);

		switch($sField){
			case 'document_number':
				$iLastInvoice = Ext_Thebing_Inquiry_Document_Search::search($this->id, array('brutto','netto'));
				if($iLastInvoice > 0){
					$oInvoice = Ext_Thebing_Inquiry_Document::getInstance($iLastInvoice);
					$mValue = $oInvoice->document_number;
				}
				break;
			case 'course_time_from':
				$aCourses = $this->getCourses(false);
				$mValue = $aCourses[0]['from'];
				break;
			case 'course_time_to':
				$aCourses = $this->getCourses(false);
				$mValue = $aCourses[0]['until'];
				break;
			case 'acc_time_from':
				$aAccommodations = $this->getAccommodations(false);
				$mValue = $aAccommodations[0]['from'];
				break;
			case 'acc_time_to':
				$aAccommodations = $this->getAccommodations(false);
				$mValue = $aAccommodations[0]['until'];
				break;
			case 'acc_type':
				$aAccommodations = $this->getAccommodations(false);
				$mValue = $aAccommodations[0]['accommodation_id'];
				break;
			case 'acc_room':
				$aAccommodations = $this->getAccommodations(false);
				$mValue = $aAccommodations[0]['roomtype_id'];
				break;
			case 'acc_meal':
				$aAccommodations = $this->getAccommodations(false);
				$mValue = $aAccommodations[0]['meal_id'];
				break;
			case 'contact_name':
			case 'customer_name':
			case 'name':
				$oCustomer	= $this->getCustomer();
				$mValue		= $oCustomer->getName();
				break;
			case 'group_id':
				$mValue = $this->_aData['group_id'];
				break;
			default:
				$mValue = parent::__get($sField);
		}
			return $mValue;
	}
	
	/**
	 *
	 * @param type $iWeek
	 * @return type 
	 */
	public function getAllocation($iWeek){

		$oSchool = $this->getSchool();
		$sLanguage = $oSchool->getInterfaceLanguage();

		$sSql = "
			SELECT 
				`ktb`.*,
				`ktcl`.`name` `blockname`,
				UNIX_TIMESTAMP(`ktb`.`week`) `week`,
				UNIX_TIMESTAMP(`ktb`.`created`) `created`,
				`ktt`.`name`,
				`ktt`.`from`,
				`ktt`.`until`,
				`ktt`.`lessons`,
				`kc`.`name` `classroom`,
				`kc`.`max_students` `classroom_max`,
				`kt`.`firstname` `teacher_firstname`,
				`kt`.`lastname` `teacher_lastname`,
				`ktul`.#level_field `level`,
				`ktul`.`name_short` `level_short`,
				`ktbic`.`inquiry_course_id` `inquiry_course_id`
			FROM
				`ts_inquiries_journeys` `ts_j_j` INNER JOIN	
				`ts_inquiries_journeys_courses` `ts_i_j_c` ON
					`ts_i_j_c`.`journey_id` = `ts_j_j`.`id` AND
					`ts_i_j_c`.`active` = 1 LEFT OUTER JOIN
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` ON
					 `ts_i_j_c`.`id` = `ktbic`.`inquiry_course_id` AND
					 `ktbic`.`active` = 1 LEFT OUTER JOIN
				`kolumbus_tuition_blocks` `ktb` ON 
					`ktbic`.`block_id` = `ktb`.`id` LEFT OUTER JOIN
				`kolumbus_tuition_classes` `ktcl` ON
					`ktcl`.`id` = `ktb`.`class_id` LEFT OUTER JOIN
				`kolumbus_tuition_templates` `ktt` ON
					`ktb`.`template_id` = `ktt`.`id` LEFT OUTER JOIN
				`kolumbus_classroom` `kc` ON
					`ktbic`.`room_id` = `kc`.`id` LEFT OUTER JOIN
				`ts_teachers` `kt` ON
					`ktb`.`teacher_id` = `kt`.`id` LEFT OUTER JOIN
				`ts_tuition_levels` `ktul` ON
					`ktb`.`level_id` = `ktul`.`id`
			WHERE
				 `ts_j_j`.`inquiry_id` = :inquiry_id AND
				`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
				 `ts_j_j`.`active` = 1 AND
				 `ktb`.`week` = FROM_UNIXTIME(:week,'%Y-%m-%d')
		";
		
		$aSql = array(
			'inquiry_id'	=> (int) $this->id,
			'week'			=> (int) $iWeek,
			'level_field'	=> 'name_' . $sLanguage,
		);
		
		$aBlock = DB::getQueryRow($sSql, $aSql);

		return $aBlock;
	}
	
	public function getFirstTuitionAllocation() {
		
		$sSql =  "
				SELECT 
					`kc`.`id` classroomid
				FROM 
					`ts_inquiries_journeys` `ts_j_j` INNER JOIN
					`ts_inquiries_journeys_courses` `ts_i_j_c` ON
						`ts_i_j_c`.`journey_id` = `ts_j_j`.`id` AND
						`ts_i_j_c`.`active` = 1 INNER JOIN 
					`kolumbus_tuition_blocks_inquiries_courses` `ktbic` ON 
						 `ts_i_j_c`.`id` = `ktbic`.`inquiry_course_id` AND 
						 `ktbic`.`active` = 1 INNER JOIN 
					`kolumbus_tuition_blocks` `ktb` ON 
						`ktbic`.`block_id` = `ktb`.`id` INNER JOIN 
					`kolumbus_classroom` `kc` ON 
						`ktbic`.`room_id` = `kc`.`id` 
				WHERE 
					 `ts_j_j`.`inquiry_id` = :inquiry_id AND
					`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					 `ts_j_j`.`active` = 1
				ORDER BY 
					UNIX_TIMESTAMP(`ktb`.`week`) ASC 
				LIMIT 1 
					 ";
		$aSql = array();
		$aSql['inquiry_id'] =  (int)$this->id;

		$aBlock = DB::getQueryRow($sSql, $aSql);
		
		return $aBlock;
		
	}

	public function getArrivalTransfer(): ?Ext_TS_Inquiry_Journey_Transfer {
		$mJourneyTransfers = $this->getTransfers('arrival');
		if (is_array($mJourneyTransfers)) {
			return \Illuminate\Support\Arr::first($mJourneyTransfers);
		}
		return $mJourneyTransfers;
	}

	public function getDepartureTransfer(): ?Ext_TS_Inquiry_Journey_Transfer {

		$transfers = $this->getTransfers('departure');
		if (is_array($transfers)) {
			return \Illuminate\Support\Arr::first($transfers);
		}

		return $transfers;
	}

	/**
	 * @TODO $sFilter auf die neuen Konstanten umstellen
	 *
	 * @param string $sFilter
	 * @param bool $bIgnoreBookingStatus
	 * @return array|Ext_TS_Inquiry_Journey_Transfer[]|Ext_TS_Inquiry_Journey_Transfer
	 */
	public function getTransfers($sFilter = '', $bIgnoreBookingStatus = false) {

		$oJourney = $this->getJourney();
		$aJourneyTransfers = $oJourney->getTransfersAsObjects(!$bIgnoreBookingStatus);

		if(!empty($sFilter)) {

			$aTypeMapping = [
				'arrival' => Ext_TS_Inquiry_Journey_Transfer::TYPE_ARRIVAL,
				'departure' => Ext_TS_Inquiry_Journey_Transfer::TYPE_DEPARTURE,
				'additional' => Ext_TS_Inquiry_Journey_Transfer::TYPE_ADDITIONAL
			];

			if(!isset($aTypeMapping[$sFilter])) {
				throw new InvalidArgumentException('Unknown filter type: '.$sFilter);
			}

			$iType = $aTypeMapping[$sFilter];
			$aJourneyTransfers = array_filter($aJourneyTransfers, function(Ext_TS_Inquiry_Journey_Transfer $oJourneyTransfer) use ($iType) {
				return (int)$oJourneyTransfer->transfer_type === $iType;
			});

		}

		// TODO: Alten Mist entfernen (zieht sich aber mal wieder durch die ganze Software)
		if(
			!empty($aJourneyTransfers) &&
			(
				$sFilter === 'arrival' ||
				$sFilter === 'departure'
			)
		) {
			$aJourneyTransfers = reset($aJourneyTransfers);
		}

		return $aJourneyTransfers;
	}

	// Zusammenreisende Schüler löschen
	public function removeRoomSharingCustomers($iInquiryId = 0) {

		if($iInquiryId == 0){
			$iInquiryId = $this->id;
		}
	
		$aSql = array(); 
		$aSql['id'] = (int)$iInquiryId; 
	
		$sSql = "SELECT
					`master_id`
				FROM
					`kolumbus_roomsharing`
				WHERE
					`share_id` = :id
			";
	
		$aResultMaster = DB::getPreparedQueryData($sSql, $aSql);
		
		$sSql = "SELECT
					`share_id`
				FROM
					`kolumbus_roomsharing`
				WHERE
					`master_id` = :id
			";
	
		$aResultShare = DB::getPreparedQueryData($sSql, $aSql);
		
		// Aktuelle ID löschen
		$sSql = "
			DELETE FROM
				`kolumbus_roomsharing`
			WHERE
				`master_id` = :id OR
				`share_id` = :id
		";
		
		DB::executePreparedQuery($sSql, $aSql);

		// IDs der anderen Buchungen rekursiv löschen
		foreach((array)$aResultMaster as $aData){
			$this->removeRoomSharingCustomers($aData['master_id']);
		}
		foreach((array)$aResultShare as $aData){
			$this->removeRoomSharingCustomers($aData['share_id']);
		}

	}

	/**
	 * @param array $aSharedIds
	 * @return bool
	 */
	public function saveRoomSharingCustomers($aSharedIds) {

		if(
			$this->id < 1 ||
			empty($aSharedIds)
		) {
			return false;
		}

		$aSharedIds[] = (int)$this->id;
		
		$aSharedIds = (array)array_unique($aSharedIds);

		// Hier muss jede Buchung mit Jeder Buchung verknüpft werden
		foreach($aSharedIds as $iShareId) {
			
			foreach($aSharedIds as $iShareIdInner) {
				
				if($iShareId != $iShareIdInner) {
					
					$aSql = array();
					$aSql['master_id'] = (int)$iShareId;
					$aSql['share_id'] = (int)$iShareIdInner;
					
					// Erst löschen falls irgendwie schon eingefügt da Unique
					
					$sSql = "DELETE FROM
									`kolumbus_roomsharing`
								WHERE
									`master_id` = :master_id AND 
									`share_id` = :share_id
							";
							
					DB::executePreparedQuery($sSql, $aSql);
					
					$sSql = "INSERT INTO 
								`kolumbus_roomsharing` 
							SET 
								`master_id` = :master_id, 
								`share_id` = :share_id
							";
					
					DB::executePreparedQuery($sSql, $aSql);
					
				}
				
			}

		}

	}

	/**
	 * gibt den pfad der buchung für uploads an
	 * @todo noch nicht fertig!
	 * @return string
	 */
	public function getUploadPath() {
		$oSchool = $this->getSchool();
		$sSchoolPath = $oSchool->getSchoolFileDir(false);
		return $sSchoolPath;
	}
	
	/*
	 * Emails des Kunden
	 */
	public function getCustomerEmails($bIncludeEmergency=true) {
		
		$oCustomer = $this->getCustomer();
		
		$aEmails = $oCustomer->getEmails();

		$oGroup = $this->getGroup();

		// Gruppenkontaktmail zusammenbauen
		if(is_object($oGroup)) {
			$aEmails = array_merge($aEmails, $oGroup->getContactPerson()->getEmails());
		}

		if($bIncludeEmergency === false) {
			return $aEmails;
		}
		
		// Emergency Mail zusammenbauen
		$aEmergencyEmail = $this->getEmergencyEmail();
		
		if(!empty($aEmergencyEmail)) {
			$aEmails[] = $aEmergencyEmail;
		}

		return $aEmails;	
	}
	
	/*
	 * Emails des Kunden
	 */
	public function getEmergencyEmail() {

		$oCustomer = $this->getCustomer();

		// Emergency Mail zusammenbauen
		$oEmergencyContact = $this->getEmergencyContact();
		
		if(
			is_object($oEmergencyContact)
		) {
	
			$aMail = $oEmergencyContact->getEmails();
	
			if(!empty($aMail[0]['email'])){
			
				$sMail = $aMail[0]['email'];
				$sName = L10N::t('Emergency contact');
				if($oEmergencyContact->getName() != ''){
					$sName .= ': '.$oEmergencyContact->getName();
				}

				$aInfo = array();
				$aInfo['object'] = 'Ext_TS_Inquiry_Contact_Traveller';
				$aInfo['object_id'] = (int)$oCustomer->id;
				$aInfo['email'] = $sMail;
				$aInfo['name'] = $sName . ' (' . $sMail . ')';
				return $aInfo;
			}
		}

	}
	

	/**
	 * Canceled eine Buchung mit dem Storno-betrag und Zeitpunkt, löscht außerdem alle Allocaions
	 */
	public function confirmCancellation($fCancelationAmount = 0){
		// Betrag der Stornierungsrechnung
		$this->canceled_amount  = (float)$fCancelationAmount;

		$oDate = new WDDate();
		$this->canceled = $oDate->get(WDDate::DB_TIMESTAMP);

		$this->save();
		
		$this->deleteAllocations();

		$this->removeRoomSharingCustomers();

		// Da es bei den Dokumenten einen Filter für stornierte Buchungen gibt…
		// Draft null hinzugefügt, damit auch Entwürfe gelöscht werden.
		$aDocuments = $this->getDocuments('invoice', true, false, null);
		foreach($aDocuments as $aDocument) {
			Ext_Gui2_Index_Stack::add('ts_document', $aDocument['id'], 1);
		}

		System::wd()->executeHook('ts_inquiry_cancel', $this);
		
	}
	
	/*
	 * Löscht alle Zuweisungen
	 */
	public function deleteAllocations() {

		$aAllos = Ext_Thebing_Allocation::getAllocationByInquiryId($this->id, 0, true, true);
		
		foreach ((array)$aAllos as $aAllo) {
			$oAllocation = Ext_Thebing_Accommodation_Allocation::getInstance($aAllo['id']);
			$oAllocation->bPurgeDelete = $this->bPurgeDelete;
			$oAllocation->delete(false,true);
		}

		foreach ($this->getJourney()->getCoursesAsObjects() as $course) {
			/** @var Ext_Thebing_School_Tuition_Allocation[] $allocations */
			$allocations = $course->getJoinedObjectChilds('tuition_blocks');
			foreach ($allocations as $allocation) {
				$allocation->bPurgeDelete = $this->bPurgeDelete;
				$allocation->delete();
			}
		}
		
	}

	/**
	 * Erzeugt ein PDF das eine Ãœbersicht darstellt mit allen Rechnungen und wieviel dafür bereits gezahlt wurde (inquiry_payment_overview)
	 *
	 * @see Ext_Thebing_Inquiry_Payment::prepareInquiryPaymentOverviewPdfs()
	 *
	 * @param array $aData
	 * @return bool
	 * @throws Exception
	 */
	public function createInquiryDocumentOverview(array $aData) {

		$oMainDocument = Ext_Thebing_Inquiry_Document::getInstance($aData['main_document_id']);
		$oTemplate = Ext_Thebing_Pdf_Template::getInstance($aData['template_id']);

		if(
			$oMainDocument->id == 0 ||
			$oTemplate->id == 0
		) {
			throw new InvalidArgumentException('Invalid document ('.$aData['payment_document_id'].') or template ('.$aData['payment_document_template_id'].')');
		}

		$oCustomer = $this->getCustomer();
		$oSchool = $this->getSchool();
		$sLang = $oCustomer->getLanguage();
		$iSchoolId = $oSchool->id;
		$iCurrencyId = $this->getCurrency();

		$oGroup = $this->getGroup();

		$aInvoiceDocs = $this->getDocuments('invoice', true, true);
		$aNumbers = array();
		foreach($aInvoiceDocs as $oDoc){
			$aNumbers[] = $oDoc->document_number;
		}

		$aSearch = array('{document_number}');
		$aReplace = array(implode(', ', $aNumbers));

		// Version anlegen
		$oMainVersion = $oMainDocument->newVersion();

		$oMainVersion->date = $oTemplate->getStaticElementValue($sLang, 'date');
		$oMainVersion->txt_address = $oTemplate->getStaticElementValue($sLang, 'address');
		$oMainVersion->txt_subject = $oTemplate->getStaticElementValue($sLang, 'subject');
		$oMainVersion->txt_intro = $oTemplate->getStaticElementValue($sLang, 'text1');
		$oMainVersion->txt_outro = $oTemplate->getStaticElementValue($sLang, 'text2');
		$oMainVersion->txt_pdf = $oTemplate->getOptionValue($sLang, $iSchoolId, 'first_page_pdf_template');
		$oMainVersion->txt_signature = $oTemplate->getOptionValue($sLang, $iSchoolId, 'signatur_text');
		$oMainVersion->signature = $oTemplate->getOptionValue($sLang, $iSchoolId, 'signatur_img');
		$oMainVersion->template_id = $oTemplate->id;
		$oMainVersion->template_language = $sLang;

		$oReplace	= new Ext_Thebing_Inquiry_Placeholder($this->id);
				
		//SPEZIAL PLATZHALTER ersetzten!
		$oMainVersion->txt_address = str_replace($aSearch, $aReplace, $oMainVersion->txt_address);
		$oMainVersion->txt_subject = str_replace($aSearch, $aReplace, $oMainVersion->txt_subject);
		$oMainVersion->txt_intro = str_replace($aSearch, $aReplace, $oMainVersion->txt_intro);
		$oMainVersion->txt_outro = str_replace($aSearch, $aReplace, $oMainVersion->txt_outro);

		$oMainVersion->date = $oReplace->replace($oMainVersion->date);
		$oMainVersion->date = Ext_Thebing_Format::ConvertDate($oMainVersion->date, null, true);
		$oMainVersion->txt_address = $oReplace->replace($oMainVersion->txt_address);
		$oMainVersion->txt_subject = $oReplace->replace($oMainVersion->txt_subject);
		$oMainVersion->txt_intro = $oReplace->replace($oMainVersion->txt_intro);
		$oMainVersion->txt_outro = $oReplace->replace($oMainVersion->txt_outro);

		// PDF Tabellen
		$aPdfData = array();
		$aPdfData[0] = array();
		$aPdfData[1] = array();
		$aPdfData[2] = array();
		$aTable	= array();

		$aHeader = array();

		$i = 0;
		$aHeader[$i]['width'] = '30';
		$aHeader[$i]['text'] = Ext_TC_Placeholder_Abstract::translateFrontend('Rechnung', $sLang);
		$aHeader[$i]['align'] = 'L';
		$i++;

		$aHeader[$i]['width'] = 'auto';
		$aHeader[$i]['text'] = Ext_TC_Placeholder_Abstract::translateFrontend('Kunde', $sLang);
		$aHeader[$i]['align'] = 'L';
		$i++;

		$aHeader[$i]['width'] = '25';
		$aHeader[$i]['text'] = Ext_TC_Placeholder_Abstract::translateFrontend('Betrag', $sLang);
		$aHeader[$i]['align'] = 'R';
		$i++;

		$aHeader[$i]['width'] = '25';
		$aHeader[$i]['text'] = Ext_TC_Placeholder_Abstract::translateFrontend('Bezahlt', $sLang);
		$aHeader[$i]['align'] = 'R';
		$i++;

		$aHeader[$i]['width'] = '25';
		$aHeader[$i]['text'] = Ext_TC_Placeholder_Abstract::translateFrontend('Offen', $sLang);
		$aHeader[$i]['align'] = 'R';
		$i++;
		
		$aTable['header'] = $aHeader;

		$aDocuments = array();

		// Wenn gruppen alles zusammenführen
		$aOverpayments = [];
		if(is_object($oGroup)){
			$aGroupInquiries = $oGroup->getInquiries();
			foreach((array)$aGroupInquiries as $oInquiry){
				$aGroupInquiryDocuments = $oInquiry->getDocuments('invoice_without_proforma', true, true);
				$aDocuments = array_merge($aDocuments, $aGroupInquiryDocuments);
				foreach ($oInquiry->getOverpayments('invoice') as $oOverpayment) {
					$aOverpayments[$oOverpayment->id] = $oOverpayment;
				}
			}
		} else {
			$aDocuments = $this->getDocuments('invoice_without_proforma', true, true);
			$aOverpayments = $this->getOverpayments('invoice');
		}

		$aBody = array();

		$i = 0;
		$fAmountTotal = 0;
		$fPayedAmountTotal = 0;
		$fBalaceTotal = 0;

		/** @var Ext_Thebing_Inquiry_Document $oInquiryDocument */
		foreach((array)$aDocuments as $oInquiryDocument) {

			$oTempInquiry = $oInquiryDocument->getInquiry();
			$iCurrencyId = $oTempInquiry->getCurrency();

			$oInquiryCustomer = $oTempInquiry->getCustomer();

			$fAmount = $oInquiryDocument->getAmount();
			
			$oFormat = new Ext_Thebing_Gui2_Format_CustomerName();
			$aTemp = array('lastname' => $oInquiryCustomer->lastname, 'firstname' => $oInquiryCustomer->firstname);
			$sCustomer = $oFormat->format(null, $aTemp, $aTemp);

			// Wenn Kundenquittung ABER Agentur
			// muss ihrgendwie ien brutto betrag hergezaubert werde...
			if(
				$oMainDocument->type == 'document_payment_overview_customer' &&
				$oInquiryDocument->isNetto()
			) {

				$fPayedAmountTmp = 0;
				$aItems = $oInquiryDocument->getLastVersion()->getItemObjects(true);

				foreach($aItems as $oItem) {

					$fPayedAmount = $oItem->getPayedAmount($iCurrencyId);

					$fAmount = (float)$oItem->getTaxDiscountAmount($oSchool->id, 'brutto');
					$fAmountNet	= (float)$oItem->getTaxDiscountAmount($oSchool->id, 'netto');

					if($fAmount == 0) {
						continue;
					}

					// Wenn kein Nettobetrag (nur Provision), dann ist Item auf magische Weise immer bezahlt
					if($fAmountNet == 0) {
						$fPayedAmountTmp += $fAmount;
					} else {
						// Komische Umrechnung
						$fPayedAmountTmp += ($fAmount / $fAmountNet) * $fPayedAmount;
					}

				}

				$fPayedAmount = $fPayedAmountTmp;
				$fAmount = $oInquiryDocument->getAmount(true, true, 'brutto', true, true);

			} else {
				$fPayedAmount = $oInquiryDocument->getPayedAmount($iCurrencyId);
			}

			$fBalace = $fAmount - $fPayedAmount;

			$ii = 0;
			
			$fAmountTotal += $fAmount;
			$fPayedAmountTotal += $fPayedAmount;
			$fBalaceTotal += $fBalace;
			
			$aBody[$i][$ii]['text'] = $oInquiryDocument->document_number;
			$aBody[$i][$ii]['align'] = 'L';
			$ii++;

			$aBody[$i][$ii]['text'] = $sCustomer;
			$aBody[$i][$ii]['align'] = 'L';
			$ii++;


			$aBody[$i][$ii]['text'] = Ext_Thebing_Format::Number($fAmount, $iCurrencyId, $iSchoolId);
			$aBody[$i][$ii]['align'] = 'R';
			$ii++;

			$aBody[$i][$ii]['text'] = Ext_Thebing_Format::Number($fPayedAmount, $iCurrencyId, $iSchoolId);
			$aBody[$i][$ii]['align'] = 'R';
			$ii++;

			$aBody[$i][$ii]['text'] = Ext_Thebing_Format::Number($fBalace, $iCurrencyId, $iSchoolId);
			$aBody[$i][$ii]['align'] = 'R';
			$ii++;

			$i++;
		}

		// Überbezahlung als eigene Zeile ergänzen
//		$aOverpayments = $this->getOverpayments('invoice');
		if(!empty($aOverpayments)) {
			$fOverpaymentAmount = 0;
			foreach($aOverpayments as $oOverpayment) {
				$fOverpaymentAmount += $oOverpayment->amount_inquiry;
			}

			$fBalaceTotal -= $fOverpaymentAmount;

			$aRow = [];
			$aRow[] = ['text' => Ext_TC_Placeholder_Abstract::translateFrontend('Überbezahlung', $sLang), 'align' => 'L'];
			$aRow[] = ['text' => '']; // Colspan funktioniert aus irgendeinem Grund nicht
			$aRow[] = ['text' => ''];
			$aRow[] = ['text' => ''];
			$aRow[] = ['text' => Ext_Thebing_Format::Number($fOverpaymentAmount * -1, $iCurrencyId, $iSchoolId), 'align' => 'R'];
			$aBody[$i] = $aRow;
			$i++;
		}

		$aBody[$i] = 'line';
		$i++;

		$ii = 0;

		$aBody[$i][$ii]['text'] = '';
		$aBody[$i][$ii]['align'] = 'L';
		$ii++;

		$aBody[$i][$ii]['text'] = '';
		$aBody[$i][$ii]['align'] = 'L';
		$ii++;


		$aBody[$i][$ii]['text'] = Ext_Thebing_Format::Number($fAmountTotal, $iCurrencyId, $iSchoolId);
		$aBody[$i][$ii]['align'] = 'R';
		$ii++;

		$aBody[$i][$ii]['text'] = Ext_Thebing_Format::Number($fPayedAmountTotal, $iCurrencyId, $iSchoolId);
		$aBody[$i][$ii]['align'] = 'R';
		$ii++;

		$aBody[$i][$ii]['text'] = Ext_Thebing_Format::Number($fBalaceTotal, $iCurrencyId, $iSchoolId);
		$aBody[$i][$ii]['align'] = 'R';
		$ii++;

		$aTable['body'] = $aBody;

		$aPdfData[0] = $aTable;

		// PDF erzeugen

		$oPDF = new Ext_Thebing_Pdf_Basic($oMainVersion->template_id, $oSchool->id);
		$oPDF->setAllowSave(false);

		$oPDF->createDocument($oMainDocument, $oMainVersion, $aPdfData);
		
		## Dateinamen + Pfad bauen ##

		$aTemp = Ext_Thebing_Inquiry_Document::buildFileNameAndPath($oMainDocument, $oMainVersion, $oSchool);
		$sPath = $aTemp['path'];
		$sFileName = $aTemp['filename'];

		## ENDE ##

		$sFilePath = $oPDF->createPdf($sPath, $sFileName);

		if(!is_file($sFilePath)) {
			throw new RuntimeException('File "'.$sFilePath.'" does not exists!');
		}

		$oMainVersion->path = $oMainVersion->prepareAbsolutePath($sFilePath);

		// Dokument als fertig markieren
		$oMainDocument->status = 'ready';
		$oMainDocument->save();

		// Pro Dokument-ID eigene Version erzeugen, aber als PDF das zuvor generierte setzen
		foreach((array)$aData['document_ids'] as $iDocumentId) {
			$oDocument = Ext_Thebing_Inquiry_Document::getInstance($iDocumentId);

			$oVersion = $oDocument->newVersion();
			$oVersion->date = $oMainVersion->date;
			$oVersion->txt_address = $oMainVersion->txt_address;
			$oVersion->txt_subject = $oMainVersion->txt_subject;
			$oVersion->txt_intro = $oMainVersion->txt_intro;
			$oVersion->txt_outro = $oMainVersion->txt_outro;
			$oVersion->txt_pdf = $oMainVersion->txt_pdf;
			$oVersion->txt_signature = $oMainVersion->txt_signature;
			$oVersion->signature = $oMainVersion->signature;
			$oVersion->comment = $oMainVersion->comment;
			$oVersion->template_id = $oMainVersion->template_id;
			$oVersion->template_language = $oMainVersion->template_language;
			$oVersion->path = $oMainVersion->path;
			$oVersion->save();

			$oDocument->status = 'ready';
			$oDocument->save();
		}

		return true;
	}

	/**
	 * Amount-Payed-Beträge dieser Buchung neu berechnen
	 *
	 * @param bool $bSave
	 */
	public function calculatePayedAmount($bSave = true) {

		bcscale(5);
		
		// Da bei einer Gruppe irgendwem die übrigen Beträge zugewiesen werden müssen, müssen alle Zahlungen geholt werden
		$aPayments = $this->getPayments(true);

		$fAmountPayedTotal = 0;
		$aAmountsPerType = [1 => 0, 2 => 0, 3 => 0];
		foreach($aPayments as $oPayment) {

			// Gruppen brauchen mal wieder eine Sonderbehandlung
			if($this->hasGroup()) {
				// Betrag pro Gruppenmitglied (aber nur das, was über die Items zugewiesen wurde)
				$fPaymentAmount = $oPayment->getPayedAmount(false, $this->id);

				// Wenn diese Buchung in der Zahlung ausgewählt wurde: Beträge ohne Zuweisung dieser Buchung zuweisen
				if($oPayment->inquiry_id == $this->id) {
					$fPaymentAmount += $oPayment->getNotItemAllocatedAmount();
				}
			} else {
				$fPaymentAmount = (float)$oPayment->amount_inquiry;
			}

			$fAmountPayedTotal = bcadd($fAmountPayedTotal, $fPaymentAmount);
			$aAmountsPerType[$oPayment->type_id] = bcadd($aAmountsPerType[$oPayment->type_id], $fPaymentAmount);

		}

		$this->amount_payed	= $fAmountPayedTotal;
		$this->amount_payed_prior_to_arrival = $aAmountsPerType[1]; // Vor Anreise
		$this->amount_payed_at_school = $aAmountsPerType[2]; // Vor Ort
		$this->amount_payed_refund = $aAmountsPerType[3]; // Refund

		if($bSave) {
			$this->save();
		}

	}

	/**
	 * Überbezahlungs daten, für geänderte Pos. die Payments hatten
	 */
	public function getChangeOverpayments(){

		$aDocs = $this->getDocuments('invoice_without_proforma', true, true);

		$aBack = array();

		$aVersionIDs = array();
		
		// Alle Docs durchgehen
		foreach((array)$aDocs as $oDoc){

			$aVersions = $oDoc->getAllVersions(false, 'DESC');
			
			// Letzte version rauswerfen da nur allte versionen interresieren!
			// Edit: T-2894 Da die Payment Items umgeschreiben werden auf die "neuen" version_items
			// wenn sich diese verändern, brauchen wir sehr wohl die letzte version
			// Edit: s.o. hier muss eine andere Lösung gefunden werden
			array_shift($aVersions);

			foreach((array)$aVersions as $oVersion){

				$aVersionIDs[] = (int)$oVersion->id;
			}
		}
	

		// Bezahlungen aller Items holen
		if(!empty($aVersionIDs)){
			$sSql = " SELECT
						`kipi`.`id`
					FROM
						`kolumbus_inquiries_documents_versions_items` `kidvi` INNER JOIN
						`kolumbus_inquiries_payments_items` `kipi` ON
							`kipi`.`item_id` = `kidvi`.`id`
					WHERE
						`kidvi`.`active` = 1 AND
						`kipi`.`active` = 1 AND
						`kidvi`.`version_id` IN (" . implode(', ', $aVersionIDs) . ") ";
			$aSql = array();

			$aResult = DB::getPreparedQueryData($sSql, $aSql);
		
			$oDB = DB::getDefaultConnection();


			foreach((array)$aResult as $aData){
				$aBack[] = Ext_Thebing_Inquiry_Payment_Item::getInstance($aData['id']);
			}
		}

		return $aBack;
	}

	/**
	 * Alle Payments dieser Buchung (nur invoice_without_proforma, nicht Creditnotes)
	 *
	 * @param bool $bAllGroupPayments Alle Zahlungen der Gruppe (nicht nur die, welche auch der Buchung zugewiesen sind)
	 * @return Ext_Thebing_Inquiry_Payment[]
	 */
	public function getPayments($bAllGroupPayments = false) {

		$aIds = [$this->id];

		if(
			$bAllGroupPayments &&
			$this->hasGroup()
		) {
			$oGroup = $this->getGroup();
			$aIds = $oGroup->getInquiries(false, true, false);
		}

		// Man MUSS über Dokumente und Overpayments gehen, da die inquiry_id bei Gruppen nicht funktioniert
		$aPayments = Ext_Thebing_Inquiry_Payment::searchPaymentsByInquiryArray($aIds);

		return array_map(function($aPayment) {
			return Ext_Thebing_Inquiry_Payment::getInstance($aPayment['id']);
		}, $aPayments);

	}

	/**
	 * @TODO Funktioniert nur auf gut Glück bei Gruppen
	 *
	 * Gibt alle Überbezahlungen zurück
	 *
	 * Hier gibt es zwei getrennte Arten: Normale Überbezahlungen (Inquiry payments, Agency payments) und Creditnotes (CN-Ausbezahlung)
	 *
	 * @param string $sDocumentType invoice_without_proforma|creditnote
	 * @return Ext_Thebing_Inquiry_Payment_Overpayment[]
	 */
	public function getOverpayments($sDocumentType) {

		$aReturn = [];

		if(
			$sDocumentType !== 'invoice' &&
			$sDocumentType !== 'creditnote'
		) {
			throw new InvalidArgumentException('Invalid overpayment type: '.$sDocumentType);
		}

		$sSql = "
			SELECT
				`kipo`.*
			FROM
				`kolumbus_inquiries_payments_overpayment` `kipo` INNER JOIN
				`kolumbus_inquiries_payments` `kip` ON
					`kip`.`id` = `kipo`.`payment_id` AND
					`kip`.`active` = 1 LEFT JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`id` = `kipo`.`inquiry_document_id` AND
					`kid`.`active` = 1
			WHERE
				`kipo`.`active` = 1 AND (
				    (
				        :document_type != 'creditnote' AND
				        `kipo`.`inquiry_document_id` IS NULL AND
				        `kip`.`inquiry_id` = :id
				    ) OR (
				        `kid`.`entity` = :entity AND
			    		`kid`.`entity_id` = :id AND
						`kid`.`type` IN ( :document_types )
				    )
				)
		";

		$aSql = [
			'entity' => self::class,
			'id' => $this->id,
			'document_type' => $sDocumentType,
			'document_types' => Ext_Thebing_Inquiry_Document_Search::getTypeData($sDocumentType)
		];

		$aResult = (array)DB::getQueryRows($sSql, $aSql);

		foreach($aResult as $aOverpayment) {
			$aReturn[] = Ext_Thebing_Inquiry_Payment_Overpayment::getObjectFromArray($aOverpayment);
		}

		return $aReturn;

	}

	/*
	 * Funktion liefert ein Array aller Transferorte die zu der Buchung möglich sind
	 * $sType = 'arrival'
	 * $sType = 'departure'
	 * $sType = '' = individual transfer
	 */
	public function getTransferLocations($sType = '', $sLang = '') {

		$oSchool = $this->getSchool();
		
		$aBack = $oSchool->getTransferLocationsForInquiry($sType, $this, $sLang);

		return $aBack;
	}

	// Liefert einen String mit den Informationen aller zugewiesener Familien (student record matching Tab)
	public function getMatchingInformations($format = true){

		$sSql = "
			SELECT
				`cdb4`.`ext_33` `provider_name`,
				`kaa`.`from`,
				`kaa`.`until`,
				`kr`.`name` `room_name`,
				`kaa`.`bed`,
				`kaa`.`comment`,
				`kac`.`type_id`
			FROM
				`kolumbus_accommodations_allocations` AS `kaa` INNER JOIN
				`ts_inquiries_journeys_accommodations` `ts_ija` ON
					`ts_ija`.`id` = `kaa`.`inquiry_accommodation_id` AND 
					`ts_ija`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`id` = `ts_ija`.`journey_id` AND
					`ts_ij`.`active` = 1 AND
					`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_ij`.`inquiry_id` = :inquiry_id INNER JOIN
				`kolumbus_rooms` AS `kr` ON
					`kr`.`id` = `kaa`.`room_id` AND
					`kr`.`active` = 1 INNER JOIN
				`customer_db_4` AS `cdb4` ON
					`cdb4`.`id` = `kr`.`accommodation_id` AND
					`cdb4`.`active` = 1 INNER JOIN
				`kolumbus_accommodations_categories` `kac` ON   
				    `kac`.`id` = `cdb4`.`default_category_id`
			WHERE
				`kaa`.`active` = 1 AND
				`kaa`.`status` = 0 AND
				`kaa`.`room_id` > 0
		";

		$aResult = (array)DB::getQueryRows($sSql, ['inquiry_id' => $this->id]);

		if($format) {
			$aResult = array_map(function ($aRow) {

				if ($aRow['type_id'] == 2) {
					$sRoomLabel = L10N::t('Parkplatz', Ext_Thebing_Inquiry_Gui2::TRANSLATION_PATH);
					$sBedLabel = L10N::t('Platz', Ext_Thebing_Inquiry_Gui2::TRANSLATION_PATH);
				} else {
					$sRoomLabel = L10N::t('Raum', Ext_Thebing_Inquiry_Gui2::TRANSLATION_PATH);
					$sBedLabel = L10N::t('Bett', Ext_Thebing_Inquiry_Gui2::TRANSLATION_PATH);
				}

				$sRow = $aRow['provider_name'];
				$sRow .= ', ';
				$sRow .= $sRoomLabel . ' ' . $aRow['room_name'];
				if ($aRow['bed'] != 0) {
					$sRow .= ', ';
					$sRow .= $sBedLabel . ' ' . $aRow['bed'];
				}
				$sRow .= ' (';
				$sRow .= Ext_Thebing_Format::LocalDate($aRow['from']);
				$sRow .= ' - ';
				$sRow .= Ext_Thebing_Format::LocalDate($aRow['until']);
				$sRow .= ')';

				if(!empty($aRow['comment'])) {
					$sRow .= ', '.L10N::t('Kommentar', Ext_Thebing_Inquiry_Gui2::TRANSLATION_PATH).': '.$aRow['comment'];
				}
				
				return $sRow;
			}, $aResult);
		}

		return $aResult;

	}

	/**
	 * Liefert die ERSTE UND LETZTE Unterkunft zu der diese Buchung
	 * gematched wurde
	 *
	 * @return Ext_Thebing_Accommodation[]
	 */
	public function getFirstLastMatchedAccommodation(){


		$aFirst = array();
		$aLast	= array();
		// Alle momentan zugewiesenen Unterkünfte
		$aOtherAllocations = Ext_Thebing_Allocation::getAllocationByInquiryId($this->id, 0,true);

		// Erste bzw. letzte Unterkunft ermitteln
		foreach((array)$aOtherAllocations as $aAllocation){
			if(empty($aFirst)){
				$aFirst = $aAllocation;
			}elseif($aFirst['from'] > $aAllocation['from']){
				$aFirst = $aAllocation;
			}

			if(empty($aLast)){
				$aLast = $aAllocation;
			}elseif($aLast['to'] < $aAllocation['to']){
				$aLast = $aAllocation;
			}
		}

		$aBack = array();
		if($aFirst['family_id'] > 0){
			$oAccommodationFirst = Ext_Thebing_Accommodation::getInstance($aFirst['family_id']);
		}else{
			$oAccommodationFirst = NULL;
		}

		if($aLast['family_id'] > 0){
			$oAccommodationLast = Ext_Thebing_Accommodation::getInstance($aLast['family_id']);
		}else{
			$oAccommodationLast = NULL;
		}
		
		$aBack['first']		= $oAccommodationFirst;
		$aBack['last']		= $oAccommodationLast;

		return $aBack;
	}

	/**
	 * Prüfen ob eine Buchungskategorie (Kurs, Unterkunft, transfer) bei dieser Buchung verändert werden darf
	 * @param type $sType
	 * @return boolean 
	 */
	public function checkIfCategoryIsEditable($sType){
		$bReturn	= true;
		$oGroup		= $this->getGroup();

		switch($sType){
			case 'course':
				// Dürfen Kurse verändert werden?
				if(is_object($oGroup)){
					if($oGroup->course_data == 'complete'){
						$bReturn = false;
					}
				}
				break;
			case 'accommodation':
				// Dürfen Unterkünfte verändert werden?
				if(is_object($oGroup)){
					if($oGroup->accommodation_data == 'complete'){
						$bReturn = false;
					}
				}
				break;
			case 'transfer':
				// Dürfen Transfers verändert werden?
				if(is_object($oGroup)){
					if($oGroup->transfer_data == 'complete'){
						$bReturn = false;
					}
				}
				break;
			case 'matching':
				// Immer editierbar
				break;
		}

		return $bReturn;

	}

	
//		/**
//	 * Prüft ob eine Buchung NUR eine Proforma hat und sonst keine
//	 * Rechnungsdokumente
//	 * 1: Nur Proforma
//	 * 2: Andere Dokumente (keine Proforma)
//	 * 3: Keine Rechnungsdokumente Dokumente
//	 */
//	public function checkOnlyProforma(){
//
//		$aDocuments = $this->getDocuments('invoice', true, true);
//
//		if(empty($aDocuments)){
//			return 3;
//		}
//
//		foreach((array)$aDocuments as $iKey => $oDocument){
//			if(
//				$iKey == 0 && // falls 1. Doc == Proforma...
//				$oDocument &&
//				count($aDocuments) > 0 &&// ... und mehr als ein Doc gefunden
//				strpos($oDocument->type, 'proforma') !== false
//			){
//				return 1;
//			}
//			break;
//		}
//
//		return 2;
//	}
	
	public function getDocumentInvoiceProformaStatus(){
		
		$mStatus	= null;
		
		$bProforma	= $this->has_proforma;
		$bInvoice	= $this->has_invoice;
		
		if(
			$bProforma && 
			!$bInvoice
		){
			$mStatus = 'proforma';
		} else if(
			$bInvoice
		){
			$mStatus = 'invoice';
		}
		
		return $mStatus;
	}
	
	/*
	 * Prüft ob eine Proforma vorhanden ist
	 */
	public function hasProforma(){
		$aDocuments = $this->getDocuments('invoice_proforma', true, true);
		
		$bBack = false;
		if(count($aDocuments) > 0){
			$bBack = true;
		}
		
		return $bBack;
	}
	
	// Speichert die Gruppenflags
	public function saveGroupFlags($aFlags){

		$aChanges = array();

		if($this->id > 0){

			$aChanges = array();

			$oJourney = $this->getJourney();		
			$oContact = $this->getCustomer();

			foreach((array)$aFlags as $sFlag => $iValue){
				#if($iValue == 1){
					$oTravellerData = $oJourney->getTravellerDetail($sFlag);
					$oTravellerData->value = $iValue;

					// Sollte hier eine Exception auftauchen, am besten mal das Ticket R-#5047 betrachten.
					$oTravellerData->traveller_id = (int)$oContact->id;

					$mError = $oTravellerData->validate();

					if($mError === true) {
						$oTravellerData->save();

						$aChanges[$sFlag] = $oTravellerData->bChange;


					}else{
						return $mError;
					}
					
				#}
			}
		}

		return $aChanges;
	}

	// liefert Gruppenflags
	public function getGroupFlags(){
		

		$aBack = array();
		if($this->id > 0){

			$oJourney = $this->getJourney();
			$aTravellerDetails = $oJourney->getTravellerDetails();
		

			foreach((array)$aTravellerDetails as $oDetail){
				$aBack[] = $oDetail->type;
			}
		}

		return $aBack;
	}

	/**
	 * die Funktion liefert alle zeitgleich zugewiesenen Kunden
	 * der Familien zu allen zugewiesenen Familien dieser Inquiry
	 * 
	 * @param bool $bOnlyCustomerInfo
	 * @param Ext_Thebing_Accommodation $oAccommodationProvider
	 * @return array
	 */
	public function getRoommates($bOnlyCustomerInfo = false, $oAccommodationProvider = null){

		$sSql = "SELECT
						`kaa`.`id`				`accommodation_allocation_id`,
						`kr`.`accommodation_id` `family_id`,
						## Für jede Familie alle gematchten Kunden suchen
						(
							SELECT
								 CAST(
									GROUP_CONCAT(
										DISTINCT CONCAT(`tc_c`.`id`, '_', `kaa1`.`id`, '_', `ts_i1`.`id`)
										SEPARATOR ','
									)
									AS CHAR CHARACTER SET utf8
								)
							FROM
								`tc_contacts` `tc_c` INNER JOIN
								`ts_inquiries_to_contacts` `ts_i_to_c` ON
									`ts_i_to_c`.`contact_id` = `tc_c`.`id` AND
									`ts_i_to_c`.`type` = 'traveller' INNER JOIN
								`ts_inquiries` `ts_i1` ON
									`ts_i1`.`id` = `ts_i_to_c`.`inquiry_id` INNER JOIN
								`ts_inquiries_journeys` `ts_i_j1` ON
									`ts_i_j1`.`inquiry_id` = `ts_i1`.`id` AND
									`ts_i_j1`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
									`ts_i_j1`.`active` = 1 INNER JOIN
								`ts_inquiries_journeys_accommodations` `ts_i_j_a1` ON
									`ts_i_j_a1`.`journey_id` = `ts_i_j1`.`id` INNER JOIN
								`kolumbus_accommodations_allocations` `kaa1` ON
									`kaa1`.`inquiry_accommodation_id` = `ts_i_j_a1`.`id` INNER JOIN
								`kolumbus_rooms` `kr1` ON
									`kr1`.`id` = `kaa1`.`room_id`
							WHERE
								`ts_i1`.`active` = 1 AND
								`ts_i_j_a1`.`active` = 1 AND
								`kaa1`.`active` = 1 AND
								`kaa1`.`status` = 0 AND
								`kaa1`.`room_id` > 0 AND
								`ts_i1`.`id` != `ts_i_j`.`inquiry_id` AND
								`kr1`.`accommodation_id` = `kr`.`accommodation_id` AND
								(
									`kaa1`.`from` < `kaa`.`until` OR
									`kaa1`.`from` = `kaa`.`until`
								) AND (
									`kaa1`.`until` > `kaa`.`from` OR
									`kaa1`.`until` = `kaa`.`from`
								)
							
						) `customer_ids`
					FROM
						`ts_inquiries_journeys` `ts_i_j` INNER JOIN
						`ts_inquiries_journeys_accommodations` `ts_i_j_a` ON
							`ts_i_j_a`.`journey_id` = `ts_i_j`.`id` AND
							`ts_i_j_a`.`active` = 1  INNER JOIN
						`kolumbus_accommodations_allocations` `kaa` ON
							`kaa`.`inquiry_accommodation_id` = `ts_i_j_a`.`id` AND
							`kaa`.`status` = 0 INNER JOIN
						`kolumbus_rooms` `kr` ON
							`kr`.`id` = `kaa`.`room_id`
					WHERE
						`ts_i_j`.`active` = 1 AND
						`ts_i_j`.`inquiry_id` = :inquiry_id AND
						`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
						`ts_i_j`.`active` = 1 AND
						`kaa`.`room_id` > 0
					ORDER BY
						`kaa`.`from`
				";

		$aSql = array();
		$aSql['inquiry_id'] = (int)$this->id;

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		$oDate = new WDDate();

		foreach((array)$aResult as $iKey => $aData){
			
			// Wenn ein Unterkunfsanbieter angegeben wurde, dürfen nur Kunden hinzugefügt werden, 
			// welche ebenfalls diesem Anbieter zugewiesen sind
			if(
				$oAccommodationProvider instanceof Ext_Thebing_Accommodation &&
				$oAccommodationProvider->id > 0
			) {
				if($aData['family_id'] != $oAccommodationProvider->id) {
					continue;
				}
			}
			
			$aCustomerData = explode(',', $aData['customer_ids']);

			unset($aResult[$iKey]['customer_ids']);

			$oAccommodationAllocation = Ext_Thebing_Accommodation_Allocation::getInstance($aData['accommodation_allocation_id']);
			// Tage der Hauptzuweisung
			$iAllocationDays = $oAccommodationAllocation->getDays();

			foreach((array)$aCustomerData as $sCustomerData){

				$aAllocationData = explode('_', $sCustomerData);

				if(
					$aAllocationData[0] > 0 &&
					$aAllocationData[1] > 0 &&
					$aAllocationData[2] > 0
				){
					$aTemp = array();
					$aTemp['customer_id'] = (int)$aAllocationData[0];
					$aTemp['accommodation_allocation_id'] = (int)$aAllocationData[1];
					$aTemp['inquiry_id'] = (int)$aAllocationData[2];

					//Zeitüberschneidung errechnen
					$oTempAccommodationAllocation = Ext_Thebing_Accommodation_Allocation::getInstance($aTemp['accommodation_allocation_id']);

					$oDate->set($oTempAccommodationAllocation->from, WDDate::DB_DATETIME);
					$iDiffStart = (int)$oDate->getDiff(WDDate::DAY, $oAccommodationAllocation->from, WDDate::DB_DATETIME);
					$oDate->set($oTempAccommodationAllocation->until, WDDate::DB_DATETIME);
					$iDiffEnd	= (int)$oDate->getDiff(WDDate::DAY, $oAccommodationAllocation->until, WDDate::DB_DATETIME);

					// Tage der überschneidungsunterkunft
					$iDays = $oTempAccommodationAllocation->getDays();

					if(
						$iDiffStart > 0 &&
						$iDiffEnd > 0
					){
						$aTemp['overlapping_days'] = $iAllocationDays - abs($iDiffStart);
						$aTemp['overlapping_start'] = $oTempAccommodationAllocation->from;
						$aTemp['overlapping_end'] = $oAccommodationAllocation->until;
					}elseif(
						$iDiffStart < 0 &&
						$iDiffEnd < 0
					){
						$aTemp['overlapping_days'] = $iAllocationDays - abs($iDiffEnd);
						$aTemp['overlapping_start'] = $oAccommodationAllocation->from;
						$aTemp['overlapping_end'] = $oTempAccommodationAllocation->until;
					}elseif(
						$iDiffStart >= 0 &&
						$iDiffEnd <= 0
					){
						$aTemp['overlapping_days'] = abs($iDays);
						$aTemp['overlapping_start'] = $oTempAccommodationAllocation->from;
						$aTemp['overlapping_end'] = $oTempAccommodationAllocation->until;
					}elseif(
						$iDiffStart <= 0 && 
						$iDiffEnd >= 0
					){
						$aTemp['overlapping_days'] = abs($iAllocationDays);
						$aTemp['overlapping_start'] = $oAccommodationAllocation->from;
						$aTemp['overlapping_end'] = $oAccommodationAllocation->until;
					}

					$aResult[$iKey]['customer_data'][] = $aTemp;					
					
				}
			}

		}


		// Nur Kunden auflisten (Infos zu welcher allocation/family die roommates gehören gehen verloren
		if($bOnlyCustomerInfo){
			$aBack = array();
			foreach((array)$aResult as $aData){
				foreach((array)$aData['customer_data'] as $aCustomerData){
						$aBack[] = $aCustomerData; 
				}
			}
			$aResult = $aBack;
		}

		return $aResult;

	}

	public function validate($bThrowExceptions = false) {
		
		$mValidate = parent::validate($bThrowExceptions);

		if($mValidate === true) {

			// Nur vorhandenen Journey holen, wenn keiner da, keinen anlegen!
			$oJourney = $this->getJourney(false);
			$mValidate = array();

			if (
				$this->id > 0 &&
				!$this->active &&
				!empty($this->getPayments(true))
			) {
				$mValidate[] = 'PAYMENTS_EXIST';
			}

			// Transfer art darf nur geändert werden, wenn noch keine Zahlungen existieren
			if(
				$oJourney !== null &&
				$this->type & self::TYPE_BOOKING &&
				$oJourney->transfer_mode != $oJourney->getOriginalData('transfer_mode') &&
				!($oJourney->transfer_mode & $oJourney::TRANSFER_MODE_BOTH) &&
				$this->id > 0
			){
				$oArrival	= $this->getTransfers('arrival', true);
				$oDeparture = $this->getTransfers('departure', true);

				$aArrivalPayments = array();
				$aDeparturePayments = array();
				
				if(is_object($oArrival)){
					$aArrivalPayments	= $oArrival->getJoinedObjectChilds('accounting_payments_active');
				}
				
				if(is_object($oDeparture)){
					$aDeparturePayments = $oDeparture->getJoinedObjectChilds('accounting_payments_active');
				}
	
				if (
					(
						$oJourney->transfer_mode & $oJourney::TRANSFER_MODE_ARRIVAL &&
						count($aArrivalPayments) > 0
					) || (
						$oJourney->transfer_mode & $oJourney::TRANSFER_MODE_DEPARTURE &&
						count($aDeparturePayments) > 0
					)
				) {
					$mValidate['ki.tsp_transfer'] = 'TRANSFER_PAYED';
				}

			}
			
			// Bezahlmethode prüfen, diese darf NIE geändert werden, wenn es bereits eine Rechnung gibt aber KEINE Gutschrift
			if(
				$this->type & self::TYPE_BOOKING &&
				$this->_aOriginalData['payment_method'] != $this->payment_method &&
				$this->id > 0
			){
				// Rechnungstypen auf die man eine Gutschrift erstellen kann
				$aTypes = array();
				$aTypes[] = 'brutto';
				$aTypes[] = 'brutto_diff';
				$aTypes[] = 'netto';
				$aTypes[] = 'netto_diff';
				// Auch proforma soll berücksichtigt werden #3971
				$oDocument = Ext_Thebing_Inquiry_Document_Search::search($this->id, 'invoice', false, true);
	
				// Wenn nicht Creditnote -> Zahlmethode darf nicht geändert werden
				$bCheckNettoInquiry		= $this->hasNettoPaymentMethod();
				
				if(is_object($oDocument)){
					
					$bCheckNettoDocument	= $oDocument->isNettoDocument();

					if(
						$oDocument->is_credit != 1 &&
						(
							$oDocument->type == 'brutto' || 
							$oDocument->type == 'brutto_diff' ||
							$oDocument->type == 'proforma_brutto' || 
							$oDocument->type == 'netto' || 
							$oDocument->type == 'netto_diff' || 
							$oDocument->type == 'proforma_netto'
						) &&
						(
							//bei alten Datensätzen kann es vorkommen, dass die Zahlmethode & Dokumenttyp
							//nicht mehr übereinstimmt und das führt dann zu Problemen, das sollte man
							//dann unbedingt korrigieren dürfen, deshalb habe ich diese Abfrage noch ergänzt, siehe auch T-3370
							$bCheckNettoInquiry !== $bCheckNettoDocument
						)
					){
						$mValidate['ki.payment_method'] = 'INVALID_PAYMENT_METHOD';
					}
					
				}

			}

			if(empty($mValidate)) {
				$mValidate = true;
			}

		}

		return $mValidate;
	}
	
	/**
	 * Überprüfen ob die Währung verändert wurde
	 * getIntersectionData war mir zu Schade dafür, weil wir nur eine Spalte überprüfen wollen, unnötiger
	 * Durschlauf über die ganzen Spalten
	 * @return bool (false=niks verändert,true=verändert)
	 */
	public function checkIfCurrencyChanged(){

		if(
			$this->_aOriginalData['currency_id'] == $this->_aData['currency_id'] ||
			$this->_aData['id'] <= 0 //bei neuen Datensätzen keine Änderung
		){
			return false;
		}else{
			return true;
		}
		
	}

	public function hasActiveAccommodationAllocation(): bool {
		return !empty($this->getAllocations());
	}

	public function getFirstAccommodationAllocation(): ?Ext_Thebing_Accommodation_Allocation {
		return \Illuminate\Support\Arr::first($this->getAllocations());
	}

	/**
	 * Liefert alle aktiven Zuweisungen der Buchung
	 *
	 * @param array $aFilter
	 * @return Ext_Thebing_Accommodation_Allocation[]
	 */
	public function getAllocations($aFilter = array()){
		
		$aInquiryAccommodations = $this->getAccommodations(true);
		
		$sFrom = $sUntil = '';

		if(
			isset($aFilter['from']) &&
			isset($aFilter['until'])
		){
			
			$sFrom	= $aFilter['from'];
			$sUntil = $aFilter['until'];
			
			if(is_numeric($sFrom)){
				$sFrom = date('Y-m-d', $sFrom);
			}
			if(is_numeric($sUntil)){
				$sUntil = date('Y-m-d', $sUntil);
			}
	
			$oDateFilterFrom = new DateTime($sFrom);
			$oDateFilterUntil = new DateTime($sUntil);
			$oDateFilterFrom->setTime(0, 0, 0);
			$oDateFilterUntil->setTime(0, 0, 0);	
		}


		$aBack = array();
		foreach((array)$aInquiryAccommodations as $oInquiryAccommodation){

			if(
				isset($aFilter['journey_accommodations']) &&
				!in_array($oInquiryAccommodation->id, $aFilter['journey_accommodations'])
			) {
				continue;
			}

			if(
			    isset($aFilter['category_type_id']) &&
                !in_array($oInquiryAccommodation->getCategory()->type_id, $aFilter['category_type_id'])
            ) {
                continue;
            }

			$aAllocations = Ext_Thebing_Allocation::getAllocationByInquiryId( $this->id, $oInquiryAccommodation->id, true);

			foreach((array)$aAllocations as $aAllocation){
				
				$oAllocation = Ext_Thebing_Accommodation_Allocation::getInstance((int)$aAllocation['id']);
				
				// Filter - Nur Zuweisungen in einem bestimmten Zeitraum
				if(
					!empty($sFrom) &&
					!empty($sUntil)	
				) {

					$oDateFrom = new DateTime($oAllocation->from);
					$oDateUntil = new DateTime($oAllocation->until);	
					$oDateFrom->setTime(0, 0, 0);
					$oDateUntil->setTime(0, 0, 0);	
	
					$iComp1 = Ext_TC_Util::compareDate($oDateFilterFrom, $oDateUntil);
					$iComp2 = Ext_TC_Util::compareDate($oDateFilterUntil, $oDateFrom);
					
					if(
						!(
							$iComp1 === -1 &&
							$iComp2 === 1
						)
					){
						// Zuweisungen die nicht im angegebenen Bereich liegen interessieren nicht
						continue;
					}
				}
				
				$aBack[] = $oAllocation;

			}
		}

		return $aBack;
	}
	
	
	public function getRoomSharingCustomers(){
		
		$aBack = array();
		
		// Verknüpfungen zur eigenen Buchung suchen
		$aResult = $this->loadRoomSharingCustomers();
		
		foreach((array)$aResult as $aData){
			$aBack[] = (int)$aData['share_id'];
			
			// Zusammenreisende der Zusammenreisenden
			$oInquiryTemp = self::getInstance((int)$aData['share_id']);
			$aTempResult = $oInquiryTemp->loadRoomSharingCustomers();
			
			foreach((array)$aTempResult as $aDataTemp){
				if($aDataTemp['share_id'] != $this->id){
					$aBack[] = (int)$aDataTemp['share_id'];
				}
			}
		}
		
		$aBack = array_unique($aBack);
		
		return $aBack;
	}
	
	/*
	 * Alle Buchungen die mit dieser zusammenreisen
	 */
	public function getRoomSharingInquiries(){ 
		
		$aRoomSharingInquiries = array();
			
		$aRoomSharingData = $this->getRoomSharingCustomers();
		$aRoomSharingInquiries = array();
		foreach((array)$aRoomSharingData as $iShareInquiryId){
			$aRoomSharingInquiries[] = Ext_TS_Inquiry::getInstance($iShareInquiryId);
		}	
		
		return $aRoomSharingInquiries;		
	}
	
	/*
	 * Prüfen aller Bedingungen die erfüllt sein müssen damit storniert werden darf
	 */
	public function checkStornoConditions(){
		
		$aStornoCheck = Ext_Thebing_Storno_Condition::check($this);
		
		return $aStornoCheck;
	}
	
	public function checkForAllcoation($iAccommodation = 0){

		$aResult = Ext_Thebing_Allocation::checkForAllo($this->id,$iAccommodation);

		if(!empty($aResult)){
			return 1;
		}else{
			return 0;
		}
		
	}

	public function getShortArray() {

		$aData = array();
		$aData['id']							= $this->id;
		$aData['inbox']							= $this->inbox;

		$oCustomer = $this->getCustomer();
		
		$aData['idUser']						= $oCustomer->id;
		$aData['group_id']						= $this->group_id;
		$aData['idAgency']						= $this->agency_id;

		$oMatching = $this->getMatchingData();

		$aData['matching_cats']					= $oMatching->cats;
		$aData['matching_dogs']					= $oMatching->dogs;
		$aData['matching_pets']					= $oMatching->pets;
		$aData['matching_smoker']				= $oMatching->smoker;
		$aData['matching_distance_to_school']	= $oMatching->distance_to_school;
		$aData['matching_air_conditioner']		= $oMatching->air_conditioner;
		$aData['matching_bath']					= $oMatching->bath;
		$aData['matching_family_age']			= $oMatching->family_age;
		$aData['matching_residential_area']		= $oMatching->residential_area;
		$aData['matching_family_kids']			= $oMatching->family_kids;
		$aData['matching_internet']				= $oMatching->internet;
		$aData['acc_comment2']					= $oMatching->acc_comment2;
		
		$oArrival = $this->getTransfers('arrival');
		
		$oDeparture = $this->getTransfers('departure');

		$aData['tsp_arrival'] = trim($oArrival->transfer_date.' '.$oArrival->transfer_time);
		$aData['tsp_departure'] = trim($oDeparture->transfer_date.' '.$oDeparture->transfer_time);

		return $aData;

	}

	/**
	 * Decodiert eine Inquiry anhand des md5 hashes
	 * @param type $sHash
	 * @return type 
	 */
	public static function decodeInquiryMd5Hash($sHash){
		$sSql = "SELECT 
						`id` 
					FROM 
						`ts_inquiries` 
					WHERE 
						MD5(`id`) = :hash 
					LIMIT 1
				";
		$aSql = array('hash' => $sHash);
		$iInquiry = (int)DB::getQueryOne($sSql,$aSql);
		return $iInquiry;
	}
	
	/**
	 * store and build allocations for shared accommodations corresponding to a given inquiry into database
	 * @access	public
	 * @static
	 * @param	INTEGER	$iInquiryId
	 * @param	INTEGER	$iShareRoomId
	 * @param \DateTime $oFrom
	 * @param \DateTime $oUntil
	 * @return	bool
	 * @author	Bjoern Bartels <b.bartels@plan-i.de>
	 */
	public static function saveRoomSharingAllocation( $iInquiryId, $iShareRoomId, \DateTime $oFrom, \DateTime $oUntil) {

		// get shared inquiry accommodations


		$oInquiry = self::getInstance((int)$iInquiryId);
		
		$aSharedInquiries = $oInquiry->loadRoomSharingCustomers();

		foreach ( (array)$aSharedInquiries as $aSharedInquiry ) {

			$oSharedInquiry = Ext_TS_Inquiry::getInstance($aSharedInquiry["share_id"]);
			Ext_Gui2_Index_Stack::add('ts_inquiry', $oSharedInquiry->id, 0);
			$aSharedInquiryAccommodations = $oSharedInquiry->getAccommodations(false);

			foreach ( (array)$aSharedInquiryAccommodations as $aSharedAccommodation ) {

				$aFreeBeds = Ext_Thebing_Matching::getFreeBeds( $iShareRoomId, $oFrom, $oUntil, null, true);

				// check if this inquiry accommodation has already been allocated
				$bIsAlreadyAllocated = Ext_Thebing_Allocation::checkForAllo( (int)$oSharedInquiry->id, (int)$aSharedAccommodation["id"] );

				if(
					empty($bIsAlreadyAllocated) && 
					!empty($aFreeBeds)
				) {

					$oSharedAccommodation = new Ext_TS_Inquiry_Journey_Accommodation($aSharedAccommodation["id"]);
					$oSharedAccommodationFrom = new DateTime($oSharedAccommodation->from);
					$oSharedAccommodationUntil = new DateTime($oSharedAccommodation->until);

					$iCompareFrom		= Ext_TC_Util::compareDate($oFrom, $oSharedAccommodationFrom);
					$iCompareFromUntil	= Ext_TC_Util::compareDate($oFrom, $oSharedAccommodationUntil);
					$iCompareUntil		= Ext_TC_Util::compareDate($oUntil, $oSharedAccommodationUntil);
					$iCompareUntilFrom	= Ext_TC_Util::compareDate($oUntil, $oSharedAccommodationFrom);

					// Für zusammenreisenden Schüler das erstbeste freie Bett nehmen
					$iFirstFreeBed = reset($aFreeBeds)['bed_number'];

					if (
						$iCompareFrom <= 0 &&
						$iCompareUntil >= 0
					) {
						// if within or same and free beds

							$oAllocation = new Ext_Thebing_Allocation();
							$oAllocation->setAccommodation($oSharedAccommodation->id);
							// allocate    $oSharedAccommodation->from -> $oSharedAccommodation->until
							$oAllocation -> setRoom ( $iShareRoomId );
							$oAllocation->setBed($iFirstFreeBed);
							$oAllocation -> setFrom ( $oSharedAccommodation->from );
							$oAllocation -> setTo   ( $oSharedAccommodation->until );
							$oAllocation -> save();

							// no inactive allocation to save here

					} else if (
						$iCompareFrom > 0 &&
						$iCompareFromUntil < 0 &&
						$iCompareUntil >= 0
					) {
						// if overlaping from BEFORE start and free beds

							$oAllocation = new Ext_Thebing_Allocation();
							$oAllocation->setAccommodation($oSharedAccommodation->id);
							// allocate    $_VARS['iFrom'] -> $oSharedAccommodation->to
							$oAllocation -> setRoom ( $iShareRoomId );
							$oAllocation->setBed($iFirstFreeBed);
							$oAllocation -> setFrom ( $oFrom );
							$oAllocation -> setTo   ( $oSharedAccommodation->until );
							$oAllocation -> save();

							// save inactive part
							$oAllocation->saveInactiveAllocation(
								$oSharedAccommodationFrom,
								$oFrom
							);

					} else if (
						$iCompareFrom <= 0 &&
						$iCompareUntilFrom > 0 &&
						$iCompareUntil < 0
					) {
						// if overlaping AFTER end and free beds

							$oAllocation = new Ext_Thebing_Allocation();
							$oAllocation->setAccommodation($oSharedAccommodation->id);
							// allocate   $oSharedAccommodation->from -> $_VARS['iTo']
							$oAllocation -> setRoom ( $iShareRoomId );
							$oAllocation->setBed($iFirstFreeBed);
							$oAllocation -> setFrom ( $oSharedAccommodation->from );
							$oAllocation -> setTo   ( $oUntil );
							$oAllocation -> save();

							// save inactive part
							$oAllocation->saveInactiveAllocation(
								$oUntil,
								$oSharedAccommodationUntil
							);

					} else if (
						$iCompareFrom > 0 &&
						$iCompareUntil < 0
					) {
						// if overlaping at BOTH ends and free beds

							$oAllocation = new Ext_Thebing_Allocation();
							$oAllocation->setAccommodation($oSharedAccommodation->id);
							// allocate   $oSharedAccommodation->from -> $_VARS['iTo']
							$oAllocation -> setRoom ( $iShareRoomId );
							$oAllocation->setBed($iFirstFreeBed);
							$oAllocation -> setFrom ( $oFrom );
							$oAllocation -> setTo   ( $oUntil );
							$oAllocation -> save();
							// save inactive part
							$oAllocation->saveInactiveAllocation(
								$oSharedAccommodationFrom,
								$oFrom
							);

							// save inactive part
							$oAllocation->saveInactiveAllocation(
								$oUntil,
								$oSharedAccommodationUntil
							);

					}

					if(!$oSharedAccommodation->checkAllocationContext()) {
						return false;
					}

				} // free beds ?

			}

		}

		return true;
	}

	/**
	 * @param string $sOption
	 * @param Ext_TS_Group_Contact $oTraveller
	 * @return string
	 */
	public function getJourneyTravellerOption($sOption, Ext_TS_Contact $oTraveller = null) {

		$oJourney = $this->getJourney();

		if(!$oTraveller) {
			$oTraveller	= $this->getFirstTraveller();
		}

		// Bei den Anfragen stehen die Werte in den Contact-Details, da Anfrage (Gruppenkontakte) und Journey getrennt sind
		if ($this->type == self::TYPE_ENQUIRY) {
			return $oTraveller->getDetail($sOption);
		}
		
		$iJourneyId	= (int)$oJourney->id;
		$iTravellerId = (int)$oTraveller->id;

		// TODO Das ist doch auch wieder Mist, dass das über einen Query geht
		$mValue	= Ext_TS_Inquiry_Journey_Traveller::getDetailByJourneyAndTraveller($iJourneyId, $iTravellerId, $sOption);

		return $mValue;
	}

	/**
	 * @param Ext_TS_Group_Contact $oContact
	 * @return bool
	 */
	public function isGuide(Ext_TS_Contact $oContact = null) {

		$mValue = $this->getJourneyTravellerOption('guide', $oContact);

		if($mValue == 1) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @TODO Diese Methode ist eigentlich auch totaler Blödsinn, weil das direkt über objekt-relationale Beziehungen funktionieren sollte
	 */
	public function getObjectByAlias($sAlias, $sColumn = null)
	{ 
	
		$oContact	= $this->getCustomer();

		switch($sAlias)
		{
			case 'ki': // Deprecated
			case 'ts_i':
				$oObject	= $this;
				break;
			case 'ts_ij':
			case 'ts_i_j': // Deprecated
				return $this->getJourney();
			case 'cdb1': // Deprecated
			case 'tc_c':
				$oObject	= $oContact;
				break;
			case 'tc_a_c': // Deprecated
			case 'tc_ac':
				$oAddress	= $oContact->getAddress('contact');
				$oObject	= $oAddress;
				break;
			case 'tc_a_b':
				// @todo Leerer Booker wird immer gespeichert, auch wenn keine Daten da sind
				$oAddress	= $this->getBooker(true)->getAddress('billing');
				$oObject	= $oAddress;
				break;
			case 'tc_bc':
				$oAddress	= $this->getBooker(true);
				$oObject	= $oAddress;
				break;
			case 'tc_c_d': // Deprecated
			case 'tc_cd':
				$oDetail	= $oContact->getDetail($sColumn, true);
				$oObject	= $oDetail;
				break;
			case 'tc_c_de':
				$oEmergency	= $this->getEmergencyContact();
				$oDetail	= $oEmergency->getDetail($sColumn, true);
				$oObject	= $oDetail;
				break;
			case 'ts_i_m_d':
				$oMatching	= $this->getMatchingData();
				$oObject	= $oMatching;
				break;
			case 'tc_c_e':
				$oEmergency	= $this->getEmergencyContact();
				$oObject	= $oEmergency;
				break;
			case 'ts_ijv':
				$oJourney	= $this->getJourney();
				$oVisa		= $oJourney->getVisa();
				$oObject	= $oVisa;
				break;
			case 'group': // Enquiry
				return $this->getJoinedObject('group');
			case 'flex':
				// ArrayObject setzen und die Flex-Felder dann mit dem Object durchschleifen können
				$oObject = $this->transients['flex'] ?? null;
				break;
			case 'upload':
				$oObject = $this->transients['uploads'] ?? null;
				break;
			default:
				$oObject = null;
				// Darf/kann man natürlich nicht machen, da Fall natürlich auftritt
				//throw new \InvalidArgumentException('Unknown alias: '.$sAlias.':'.$sColumn);
		}
		
		return $oObject;
	}
	
	/**
	 * Ein Edit Feld im SR
	 * @param type $sAlias
	 * @param type $sColumn
	 * @param type $sValue
	 * @return type 
	 */
	protected function _getEditField($sAlias, $sColumn, $sValue){
		
		$aData = array();
		$aData['db_alias']			= $sAlias;
		$aData['db_column']			= $sColumn;
		$aData['value']				= $sValue;
		$aData['select_options']	= array();
		
		return (array)$aData;
	}

	/**
	 * TODO Das ist doch total bescheuert, dass hier parent komplett umgangen wird und auch noch diese Methode in der Entität ist
	 * @see \Ext_Thebing_Inquiry_Gui2::getEditDialogData()
	 *
	 * Srstellt die Dialogdaten für das Dialog objekt.
	 * @param Ext_Gui2 $oGui
	 * @param array $aSelectedIds
	 * @param array $aSaveData
	 * @return array
	 */
	public function getEditDialogData(Ext_Gui2_Dialog $oDialog, Ext_Gui2 $oGui, array $aSelectedIds, $aSaveData) {

		$aBack = array();
		
		$dialogData = $oDialog->getDataObject();
				
		$oCustomer = $this->getCustomer();
		$oSchool = $this->getSchool();
		$aSelectedIds = (array)$aSelectedIds;
		$oFormatDate = new Ext_Thebing_Gui2_Format_Date;

		// Anhand der SaveData die Werte für die Felder holen
		foreach($aSaveData as $aSaveField) {
			
			if($aSaveField['joined_object_key'] == 'other_contacts') {
				
				$fieldValue = $dialogData->getEditFieldValue($aSelectedIds, $this, $aSaveField);
				
				$aBack[]  = $fieldValue;
				
			} else {

				$sColumn = $aSaveField['db_column'];

				$oObject = $this->getObjectByAlias($aSaveField['db_alias'], $aSaveField['db_column']);

				if(
					is_object($oObject) &&
					$sColumn != 'upload1' &&
					$sColumn != 'upload2' &&
					strpos($sColumn, 'studentupload_') === false
				) {

					if($oObject instanceof Ext_TC_Contact_Detail) {
						$sValue = $oObject->value;
					} else {
						$sValue = $oObject->$sColumn;
					}

					if(!empty($sValue) && WDDate::isDate($sValue, WDDate::DB_DATE)) {
						$sValue = $oFormatDate->format($sValue);
					}

					$aTemp = $this->_getEditField($aSaveField['db_alias'], $aSaveField['db_column'], $sValue);

					// Select Optionen aus Selection Klasse holen
					if(!empty($aSaveField['selection'])) {

						$oObjectSelection = null;

						if($aSaveField['selection'] instanceof Ext_Gui2_View_Selection_Interface) {
							$oObjectSelection = $aSaveField['selection'];
						} else if(is_string($aSaveField['selection'])) {
							$sTempSelection = 'Ext_Gui2_View_Selection_'.$aSaveField['selection'];
							$oObjectSelection = new $sTempSelection();
						}

						if($oObjectSelection instanceof Ext_Gui2_View_Selection_Interface) {

							// Gui-Objekt übergeben
							$oObjectSelection->setGui($oGui);

							if($aSaveField['db_column'] == 'agency_contact_id') {
								$oWDBasicTemp = new \TsCompany\Entity\Comment();
								$iAgencyId = $this->agency_id;
								$oWDBasicTemp->company_id = $iAgencyId;
							} else {
								$oWDBasicTemp = $this;
							}

							$aSelectOptions	= $oObjectSelection->getOptions($aSelectedIds, array(), $oWDBasicTemp);
							$aSelectOptions	= $oObjectSelection->prepareOptionsForGui($aSelectOptions);
							$aTemp['select_options'] = $aSelectOptions;

							// dieser Flag ist nötig da sonst die Update Select Options ein "Unbekannt" Eintrag schreibt
							// da das Feld aber von der Agentur abhängt darf das nicht passieren und da es keine normale Dep. ist klappt das nur so
							$aTemp['has_dependency'] = 1;
							$aTemp['force_options_reload'] = 1;
#__out($aSaveField['db_column']);
#__out($aTemp['value']);
							if(
								empty($aTemp['value']) &&
								method_exists($oObjectSelection, 'getDefaultValue')
							) {
								$aTemp['value'] = $oObjectSelection->getDefaultValue($oWDBasicTemp);
							}
							
						}

					}

					$aBack[]  = $aTemp;

				}
		
			}	
			
		}

		// Closure für Values-Format für Upload-Felder-Checkboxen
		$oGetFlexUploadData = function($sType, $iTypeId) {
			return [
				'db_column' => 'studentupload_released_sl]['.$sType.']['.$iTypeId,
				'value' => (int)$this->isUploadReleasedForStudentApp($sType, $iTypeId)
			];
		};

		$sPhoto = $oCustomer->getPhoto();
		if(!empty($sPhoto)) {
			
			$aPhotoInfo = pathinfo($sPhoto);

			// Foto Upload ergänzen
			$aTemp = array();
			$aTemp['db_alias'] = 'upload';
			$aTemp['db_column'] = 'upload1';
			$aTemp['value'] = $aPhotoInfo['basename'];
			$aTemp['select_options'] = array();
			$aTemp['upload_path'] = $aPhotoInfo['dirname'].'/';
			$aTemp['no_cache'] = true;
			$aBack[] = $aTemp;
		}

		$aBack[] = $oGetFlexUploadData('static', 1);

		$sFile = $oCustomer->getPassport();
		if(!empty($sFile)) {
			
			$aFileInfo = pathinfo($sFile);
			
			// Passbild Upload ergänzen
			$aTemp = array();
			$aTemp['db_alias'] = 'upload';
			$aTemp['db_column'] = 'upload2';
			$aTemp['value'] = $aFileInfo['basename'];
			$aTemp['select_options'] = array();
			$aTemp['upload_path'] = $aFileInfo['dirname'].'/';
			$aTemp['no_cache'] = true;
			$aBack[] = $aTemp;
		}

		$aBack[] = $oGetFlexUploadData('static', 2);

		// Dynamische Uploads ergänzen
		if($oSchool->id > 0) {

			$aUploadFields = Ext_Thebing_School_Customerupload::getUploadFieldsBySchoolIds([$oSchool->id]);

			foreach($aUploadFields as $oUploadField) {
				
				$sFile = $oCustomer->getStudentUpload($oUploadField->id, $oSchool->id, $this->id);
				$aBack[] = $oGetFlexUploadData('flex', $oUploadField->id);
				
				if(!empty($sFile)) {
					
					$aFileInfo = pathinfo($sFile);

					$aTemp = array();
					$aTemp['db_alias']			= 'upload';
					$aTemp['db_column']			= 'studentupload_'.$oUploadField->id;
					$aTemp['value'] = $aFileInfo['basename'];
					$aTemp['upload_path'] = $aFileInfo['dirname'].'/';
					$aTemp['no_cache'] = true;
					$aBack[] = $aTemp;
				}

			}

		}

		// Uploadfelder für Financial Gurantees, da Uploadfeld kein value unterstützt
		/** @var TsSponsoring\Entity\InquiryGuarantee[] $aGurantees */
		$aGurantees = $this->getJoinedObjectChilds('sponsoring_guarantees', true);
		foreach($aGurantees as $oGurantee) {
			$aBack[] = [
				'id' => 'sponsoring_gurantee['.$this->id.']['.$oGurantee->id.'][path]',
				'db_column' => 'sponsoring_gurantee['.$this->id.']['.$oGurantee->id.'][path]', // Komisches hidden-Feld
				'value' => $oGurantee->path,
				'upload_path' => $oSchool->getSchoolFileDir(false).'/inquiry_sponsoring/',
				'no_cache' => true
			];
		}

		/*
		if(
			$_SESSION['sid'] > 0 &&
			empty($aSelectedIds)
		){
			$aTemp = array();
			$aTemp['db_alias'] = 'ts_i_j';
			$aTemp['db_column'] = 'school_id';
			$aTemp['value'] = (int)$_SESSION['sid'];
			$aBack[] = $aTemp;
		}
		*/

		return $aBack;
	}
	
	/**
	 *
	 * @param type $iInquiry
	 * @param type $iAccommodation
	 * @return type Liefert Inaktive Zuweisungen
	 */
	public function getInactiveAllocations($iAccommodation = 0){

		$aAllocations = Ext_Thebing_Allocation::getAllocationByInquiryId($this->id, $iAccommodation, true, true);
		
		$aBack = array();
		foreach($aAllocations as $aAllocation){
			if($aAllocation['room_id'] == 0){
				$aBack[] = $aAllocation;				
			}
		}
	
		return $aBack;
		
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	public function isNewBookingNumberNeeded():bool {
		if (
			$this->id > 0 &&
			$this->getOriginalData('id') > 0
		) {
			$sOriginalInbox = $this->getOriginalData('inbox');

			if ($this->inbox !== $sOriginalInbox) {
				Ext_TS_Numberrange::setInbox(Ext_Thebing_Client_Inbox::getByShort($sOriginalInbox));
				$oOriginalNumberrange = Factory::executeStatic($this->sNumberrangeClass, 'getByApplicationAndObject', ['booking', $this->getSchool()->id]);
				Ext_TS_Numberrange::setInbox($this->getInbox());
				$oNumberrange = Factory::executeStatic($this->sNumberrangeClass, 'getByApplicationAndObject', ['booking', $this->getSchool()->id]);

				if (
					$oOriginalNumberrange && $oNumberrange &&
					$oOriginalNumberrange->id != $oNumberrange->id
				) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * prüft ob eine neue Kundennummer nötigt ist
	 * kann passieren wenn agentur/direkt unterschiedliche nummern hat und man die art des kunden ändert
	 * @return boolean
	 */
	public function isNewCustomerNumberNeeded(): bool{

		if(
			$this->id > 0 &&
			$this->getOriginalData('id') > 0
		){
			$iOriginalAgencyId = $this->getOriginalData('agency_id');
			$sOriginalInbox = $this->getOriginalData('inbox');

			$oNumber = new Ext_Thebing_Customer_CustomerNumber($this);

			if (
				$this->agency_id != $iOriginalAgencyId &&
				(
					$this->agency_id == 0 ||
					$iOriginalAgencyId == 0
				)
			) {
				if($oNumber->checkForDifferentApplicationRanges()){
					return true;
				}
			}

			if ($this->inbox !== $sOriginalInbox) {
				Ext_TS_Numberrange_Contact::setInbox(Ext_Thebing_Client_Inbox::getByShort($sOriginalInbox));
				$oOriginalNumberrange = Ext_TS_Numberrange_Contact::getByApplicationAndObject($oNumber->getApplicationByType(), $this->getSchool()->id);
				Ext_TS_Numberrange_Contact::setInbox($this->getInbox());
				$oNumberrange = Ext_TS_Numberrange_Contact::getByApplicationAndObject($oNumber->getApplicationByType(), $this->getSchool()->id);

				if (
					$oNumberrange && $oOriginalNumberrange &&
					$oNumberrange->id != $oOriginalNumberrange->id
				) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 *
	 * @param string $sDisplayLanguage
	 * @return array 
	 */
	public function getInsurancesWithPriceData($sDisplayLanguage = null)
	{
		$aInsurances = (array)Ext_TS_Inquiry_Journey_Insurance::getInquiryInsurances($this->id, $sDisplayLanguage);
		
		return $aInsurances;
	}
	
	/**
	 *
	 * Verbindungstabelle für Specialpositionen
	 * 
	 * @return array 
	 */
	public function getSpecialPositionRelationTableData()
	{
		if(isset($this->_aJoinTables['special_position_relation']))
		{
			return $this->_aJoinTables['special_position_relation'];
		}
		else
		{
			throw new Exception('Position Relation Table not found!');
		}
	}
	
	/**
	 *
	 * @return Ext_TS_Inquiry 
	 */
	public function getSpecialRelationObject()
	{
		return $this;
	}
	
	/**
	 *
	 * Anzahl der Mitglieder in der Gruppe
	 * 
	 * @return int 
	 */
	public function countAllMembers()
	{
		if($this->hasGroup())
		{
			$oGroup = $this->getGroup();

			return $oGroup->countAllMembers();
		}
		else
		{
			return false;
		}
	}

	public function getServiceObjectClasses()
	{
		return array(
			'course' => 'Ext_TS_Inquiry_Journey_Course',
			'accommodation' => 'Ext_TS_Inquiry_Journey_Accommodation',
			'transfer' => 'Ext_TS_Inquiry_Journey_Transfer',
			'insurance' => 'Ext_TS_Inquiry_Journey_Insurance',
			'activity' => 'Ext_TS_Inquiry_Journey_Activity'
		);
	}
	
	public function getServiceFrom($bUsable = false): string|Carbon|null {
		
		$sFrom = $this->service_from;
		if($sFrom == '0000-00-00') {
			return null;
		}

		if ($bUsable) {
			return Carbon::parse($sFrom);
		}
		
		return $sFrom;
	}
	
	public function getServiceUntil($bUsable = false): string|Carbon|null {
		
		$sUntil = $this->service_until;
		if($sUntil == '0000-00-00') {
			return null;
		}

		if ($bUsable) {
			return Carbon::parse($sUntil);
		}
		
		return $sUntil;
	}

	public function getAccommodationsActiveTypes(): string {
		$accommodations = $this->getAccommodations(true, true);

		return $this->getServicesActiveTypes($accommodations);
	}

	public function getActivitiesActiveTypes(): string {
		$journey = $this->getJourney();
		$activities = $journey->getActivitiesAsObjects(false);

		return $this->getServicesActiveTypes($activities);
	}

	public function getCoursesActiveTypes(): string {
		$courses = $this->getCourses(true, true, true, false);

		return $this->getServicesActiveTypes($courses);
	}

	public function getInsurancesActiveTypes(): string {
		$journey = $this->getJourney();
		$insurances = $journey->getInsurancesAsObjects(false, false);

		return $this->getServicesActiveTypes($insurances);
	}

	public function getServicesActiveTypes(array $services): string {

		$i = 0;
		foreach($services as $service) {
			if($service->visible == 0) {
				$i++;
			}
		}
		if(!empty($services)) {
			if ($i == count($services)) {
				return self::SERVICE_DEACTIVATED_COMPLETELY;
			} elseif ($i > 0) {
				return self::SERVICE_DEACTIVATED_PARTLY;
			}
			return self::SERVICE_ACTIVE;
		}

		return self::NO_SERVICE_AVAILABLE;
	}

	/**
	 * Setzt die Gruppe für ein Anfragen/Buchungsobject
	 * @param Ext_TS_Group_Interface $group 
	 */
	public function setGroup(Ext_TS_Group_Interface $group){
		
		$this->group_id = (int)$group->id;
		$this->agency_id = $group->agency_id;
		$this->agency_contact_id = $group->agency_contact_id;
		$this->currency_id = $group->currency_id;
		$this->payment_method = $group->payment_methode_group;
		$this->payment_method_comment = $group->payment_method_comment_group;
		
		$journey = $this->getJourney();
		$journey->transfer_mode = $group->transfer_mode;
		
		if($group->inbox_id > 0) {
			$inbox = \Ext_Thebing_Client_Inbox::getInstance($group->inbox_id);

			$this->inbox = $inbox->short;
		}
		
		$traveller = $this->getTraveller();

		$traveller->corresponding_language = $group->correspondence_id;
		
	}
	
	/**
	 *
	 * Funktion überprüft im Rechnungsdialog ob Kurs/Unterkunft/Transfer für alle Mitglieder gleich sind
	 * 
	 * @param string $sType
	 * @return bool 
	 */
	public function hasSameData($sType)
	{
		$sKey	= $sType . '_data';
		$oGroup = $this->getGroup();
		
		if($oGroup->$sKey == 'complete')
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Legacy-Schmutz vom feinsten: Für das Gruppenkonstrukt musste bei einer Enquiry pro Durchlauf der Kontakt ersetzt werden, funktioniert aber mit bidirectional nicht mehr
	 *
	 * @param Ext_TS_Inquiry|Ext_TS_Contact $oInquiryOrContact
	 * @return Ext_TS_Inquiry
	 */
	public function createMemberInquiry(/*Ext_TS_Inquiry*/ $oInquiryOrContact) {

		if ($oInquiryOrContact instanceof Ext_TS_Contact) {

			$oConvert = new Ext_TS_Enquiry_Convert2($this);
			return $oConvert->cloneInquiry($oInquiryOrContact, true);

		}

		return $oInquiryOrContact;

	}
	
	/**
	 *
	 * @return Ext_TS_Inquiry_Contact_Abstract
	 */
	public function getTraveller()
	{
		return $this->getCustomer();
	}
	
	/**
	 *
	 * Definiert ob ein Reisender definiert wurde, bei Buchungen ist das immer der Fall
	 * 
	 * @return bool 
	 */
	public function hasTraveller()
	{
		return true;
	}
	
	public function hasVisum() {		
		$oVisumData = $this->getVisaData();
		
		if(
			$oVisumData instanceof Ext_TS_Inquiry_Journey_Visa &&
			$oVisumData->id > 0
		) {
			return 1;
		}
		
		return 0;
	}

	/**
	 * Alle Gruppenmitglieder die für ein Dokument relevant sind, in Buchungen sind das immer alle
	 * => Demnach nur richtig für neue Dokumente und Prüfung auf Änderung, nicht beim Editieren…
	 *
	 * @param Ext_Thebing_Inquiry_Document $oDocument
	 * @return Ext_TS_Inquiry[]|Ext_TS_Group_Contact[]
	 */
	public function getAllGroupMembersForDocument(Ext_Thebing_Inquiry_Document $oDocument)
	{
		$oGroup = $this->getGroup();

		if ($this->type == (self::TYPE_ENQUIRY | self::TYPE_BOOKING)) {
			throw new \RuntimeException('Cannot call getAllGroupMembersForDocument for a converted inquiry');
		}

		return $oGroup->getMembers();
		
	}

	/**
	 * Platzhalter Objekt erstellen mit construct Parameter Übergabe
	 *
	 * @param array $aParams
	 * @return Ext_Thebing_Inquiry_Placeholder
	 * @throws Exception
	 */
	public function createPlaceholderObject($aParams) {

		$sPlaceholderClass	= $this->getOldPlaceholderClass();
		
		if(!isset($aParams['inquiry'])) {
			throw new InvalidArgumentException('Inquiry not defined!');
		}

		if(!isset($aParams['contact'])) {
			throw new InvalidArgumentException('Contact not defined!');
		}

		if(!isset($aParams['school_format'])) {
			throw new InvalidArgumentException('Schoolformat not defined!');
		}

		$aParamSet = array(
			$aParams['inquiry']->id,
			$aParams['contact']->id,
			$aParams['school_format'],
		);

		$oReflection		= new ReflectionClass($sPlaceholderClass);
		/** @var Ext_Thebing_Inquiry_Placeholder $oPlaceholder */
		$oPlaceholder		= $oReflection->newInstanceArgs($aParamSet);

		/*if($aParams['template_type'] == 'document_insurances')
		{
			$iInsurance         = (int)$aParams['options']['insurance'];
			if($iInsurance > 0){
				$oInsurance  = Ext_TS_Inquiry_Journey_Insurance::getInstance($iInsurance);
				$oPlaceholder->oJourneyInsurance = $oInsurance;
			}
		}*/

		/*if(
			isset($aParams['options']) && 
			isset($aParams['options']['allocation'])
		)
		{
			// Wenn Klassenplanung Zuweisung übergeben, in die Platzhalterklasse injekten
			$oPlaceholder->setTuitionAllocation($aParams['options']['allocation']);
		}*/

		return $oPlaceholder;
	}

	/**
	 * @inheritdoc
	 */
	public function getLastDocument($mType, $aTemplateTypes = [], $oSearch=null) {

		if(!$oSearch) {
			$oSearch = new Ext_Thebing_Inquiry_Document_Search($this);
		}

		$oSearch->setType($mType);
		#$oSearch->setObjectType($this->getClassName());

		$oSearch->setTemplateTypes($aTemplateTypes);

		$oLastDocument = $oSearch->searchDocument(true, false);
		
		return $oLastDocument;
	}
	
	/**
	 * Aktualisiert den zwischengespeicherten Leistungszeitraum 
	 */
	public function refreshServicePeriod() {

		$this->service_from = null;
		$this->service_until = null;

		$period = $this->getCompleteServiceTimeframe();
		if ($period) {
			$this->service_from = $period->start->toDateString();
			$this->service_until = $period->end->toDateString();
		}
		
	}
	
	/**
	 * Überprüfen ob Buchung storniert wurde
	 * 
	 * @return boolean 
	 */
	public function isCancelled() {
		$iCanceled = (int)$this->canceled;

		if($iCanceled <= 0) {
			return false;
		} else {
			return true;
		}
	}

	public function getCancellationDate(): ?Carbon {

		if ((int)$this->canceled > 0) {
			return Carbon::createFromTimestamp((int)$this->canceled);
		}

		return null;
	}

	/**
	 * Alle Zuweisungen in der Klassenplanung Finden
	 * 
	 * @param Ext_TS_Inquiry $oInquiry
	 * @return array 
	 */
	public function getTuitionAllocationIds()
	{
		$oAllocation = new Ext_Thebing_School_Tuition_Allocation();
		
		$aIds	= $oAllocation->getAllocationIdsByInquiry($this);
		
		return $aIds;
	}

	public function getCourseWeeks()
	{
		$iWeeks		= 0;
		
		$aCourses	= $this->getCourses();
		
		foreach($aCourses as $oCourse)
		{
			$iWeeks += $oCourse->weeks;
		}
		
		if(empty($iWeeks))
		{
			$iWeeks = null;
		}

		return $iWeeks;
	}

	/**
	 * Liefert alle gebuchten Transfers dieser Buchung, die
	 * @return array
	 */
	public function getAllocatedTransfers()
	{
		$aTransfers = $this->getTransfers();
		$aJourneyTransferIds = array();
		foreach($aTransfers as $oJourneyTransfer) {
			$aJourneyTransferIds[] = $oJourneyTransfer->id;
		}

		$aProviders = Ext_TS_Inquiry_Journey_Transfer::getProvider($aJourneyTransferIds);
		return $aProviders;
	}

	/**
	 * @return string|null
	 */
	public function getLastDocumentNumber() {

		$oSearch = new Ext_Thebing_Inquiry_Document_Search($this);
		$oSearch->addJourneyDocuments();

		$oDocument = $this->getLastDocument(['invoice', 'offer'], [], $oSearch);
		if($oDocument !== null) {
			return $oDocument->document_number;
		}

		return null;

	}

	/**
	 * @param boolean $bLog
	 * @return Ext_TS_Inquiry
	 */
	public function save($bLog=true) {

		$bInsert = $this->isNew();

		$this->refreshServicePeriod();

		/**
		 * Bei jedem Speichervorgang soll changed gesetzt werden, da aktuell 
		 * nicht korrekt festgestellt wird, ob sich irgendwas an der Buchung 
		 * verändert hat
		 * @todo getIntersectionData dazu bringen korrekt zu checken, ob sich 
		 * was geändert hat
		 */
		$this->_bOverwriteCurrentTimestamp = true;
		$this->changed = time();

		// Notfallkontakt aus Relation löschen, wenn Objekt ohnehin leer (0er-Einträge in Zwischentabelle verhindern)
		if(
			$this->_oEmergenyContact !== null &&
			$this->_oEmergenyContact->isEmpty()
		) {
			unset($this->_aJoinTablesObjects['emergencies']);
		}

//		if(\TsSalesForce\Service\Inquiry::isActive()) {
//			$this->writeSalesForceToStack();
//		}

		if($this->partial_invoices_terms == 0) {
			$this->partial_invoices_terms = null;
		}
		
		if($this->id == 0) {
			$this->allocateSalesperson();
		}

		// Nummernkreis erzeugen
		$mNumber = $this->getNumber();
		if(empty($mNumber)) {
			$this->generateNumber();
		}

		$aTransfer = parent::save($bLog);

		$oStackRepository = \Core\Entity\ParallelProcessing\Stack::getRepository();
		$oStackRepository->writeToStack('ts/partial-invoice', ['inquiry_id' => $this->id], 1);

		// Specials müssen nach dem Speichern neu aus der DB geholt werden bei Bedarf
		$this->inquirySpecials = null;
		
		// TODO Gegen Events/Observer ersetzen (RDStation-Code)
		if($this->type & self::TYPE_BOOKING) {
			
			if($bInsert) {
				System::wd()->executeHook('ts_inquiry_create', $this);
			} else {
				System::wd()->executeHook('ts_inquiry_update', $this);	
			}

			System::wd()->executeHook('ts_inquiry_save', $this);

		} elseif($this->type & self::TYPE_ENQUIRY) {

			if($bInsert) {
				System::wd()->executeHook('ts_enquiry_create', $this);
			} else {
				System::wd()->executeHook('ts_enquiry_update', $this);	
			}

			System::wd()->executeHook('ts_enquiry_save', $this);
			
		}

		
		return $aTransfer;
	}
	
	/**
	 * Buchung löschen
	 *
	 * Zuweisungen zu Blöcken werden bereits durch on_delete gelöscht.
	 */
	public function delete($keepDocuments = false) {

		if ($keepDocuments) {
			$this->_aJoinedObjects['documents']['on_delete'] = 'no_action';
		}

		// Ist wohl sicherer, das hier auch noch aufzurufen (wie beim Stornieren)
		$this->deleteAllocations();
		$this->removeRoomSharingCustomers();

		$mDelete = parent::delete();

		$this->_aJoinedObjects['documents']['on_delete'] = 'cascade';

		return $mDelete;

	}

	/**
	 * @inheritdoc
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

		// Dokumente und E-Mails löschen
		$this->purgeCommonData($bAnonymize);

		// Kontakt löschen oder nur anonymisieren (Uploads auf jeden Fall)
		$oContact = $this->getCustomer();
		$oContact->deleteUploads($this->id); // Uploads gehören irgendwie Inquiry, School und nicht Contact
		$oContact->purge($bAnonymize);

		// Notfallkontakt komplett löschen
		$oEmergencyContact = $this->getEmergencyContact();
		$oEmergencyContact->purge();

		if(!$bAnonymize) {

			$aPayments = $this->getPayments();
			foreach($aPayments as $oPayment) {
				$oPayment->enablePurgeDelete();
				$oPayment->delete();
			}

			$this->delete();

		} else {

			$oVisa = $this->getVisaData();
			$oVisa->servis_id = '';
			$oVisa->tracking_number = '';
			$oVisa->passport_number = '';

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
		return L10N::t('Buchungen', \TsPrivacy\Service\Notification::TRANSLATION_PATH);
	}

	/**
	 * @inheritdoc
	 */
	public static function getPurgeSettings() {
		$oClient = Ext_Thebing_Client::getFirstClient();
		return [
			'action' => $oClient->privacy_inquiry_action,
			'quantity' => $oClient->privacy_inquiry_quantity,
			'unit' => $oClient->privacy_inquiry_unit,
			'basedon' => $oClient->privacy_inquiry_basedon
		];
	}

	/**
	 * @inheritdoc
	 */
	protected function getPurgeDocumentTypes($bAnonymize) {

		$aDocumentTypes = ['all'];
		if($bAnonymize) {
			// Beim Anonymisieren Rechnungen behalten
			$aDocumentTypes = ['additional_document', 'examination', 'offer'];
		}

		return $aDocumentTypes;

	}

	/**
	 * Werte der Betragsspalten löschen
	 * @param bool $bSave
	 *
	 * @return Ext_TS_Inquiry
	 */
	public function resetAmount($bSave = true)
	{
		$this->amount_initial			= 0;
		$this->amount					= 0;
		$this->canceled_amount			= 0;

		$mReturn = true;
		if($bSave) {
			$mReturn = $this->save();
		}
		
		return $mReturn;
	}
	
	/**
	 * Proforma Flag löschen
	 * 
	 * @return Ext_TS_Inquiry
	 */
	public function unsetProformaFlag()
	{
		$this->has_proforma		= 0;
		
		$mReturn				= $this->save();
		
		return $mReturn;
	}

	public function unsetInvoiceFlag(bool $bSave = true) {
		$this->has_invoice = 0;

		if ($bSave) {
			return $this->save();
		}
		return true;
	}

	/**
	 * Proforma einer Buchung löschen, entsprechende Flags aktualisieren
	 * 
	 * @param Ext_Thebing_Inquiry_Document $oProforma 
	 * 
	 * @return bool|array
	 */
	public function deleteProforma($bResetAmount = true, Ext_Thebing_Inquiry_Document $oProforma = null)
	{
		if(!$oProforma)
		{
			$oSearch = new Ext_Thebing_Inquiry_Document_Search($this);
			$oSearch->setType(array('proforma_netto', 'proforma_brutto'));
			$oProforma = $oSearch->searchDocument(true, false);
		}

		if(
			$oProforma &&
			$oProforma->isProforma() && 
			$oProforma->id > 0
		) {
			$mSuccess = $oProforma->delete();

			if($mSuccess === true) {
				$this->unsetProformaFlag();

				if($bResetAmount) {
					$this->resetAmount();
				}
			}

			return $mSuccess;
		} else {
			return false;
		}

	}

	public function deleteInvoice(Ext_Thebing_Inquiry_Document $oDocument, $bResetAmount = true)
	{
		if ($oDocument->isReleased()) {
			throw new \RuntimeException('Document cannot be deleted (Already released)');
		} else if ($oDocument->getPayedAmount() > 0) {
			throw new \RuntimeException('Document cannot be deleted (Payment existing)');
		}

		if(
			$oDocument->isInvoice() &&
			$oDocument->id > 0
		) {
			$mSuccess = $oDocument->delete();

			if($mSuccess === true) {

				$aDocuments = $this->getDocuments('invoice_without_proforma');

				if (empty($aDocuments)) {
					// Flags zurücksetzen
					$this->unsetInvoiceFlag(false);
					if($bResetAmount) {
						$this->resetAmount(false);
					}
				} else {
					// Betragsspalten neu berechnen
					$this->getAmount(false, true, null, false);
					$this->getAmount(true, true, null, false);
				}

				$this->save();
			}

			return $mSuccess;
		} else {
			return false;
		}

	}

	/**
	 * @return array
	 */
	public function getUsedSpecialIds() {

		$sSql = "
			SELECT
				`ks`.`id`
			FROM
				`kolumbus_inquiries_positions_specials` `kips` INNER JOIN
				`ts_specials` `ks` ON
					`ks`.`id` = `kips`.`special_id` AND
					`ks`.`active` = 1 INNER JOIN
				`ts_inquiries_to_special_positions` `ts_i_to_sp` ON
					`ts_i_to_sp`.`special_position_id` = `kips`.`id` AND
					`ts_i_to_sp`.`inquiry_id` = :inquiry_id
			WHERE
				`kips`.`active` = 1 AND
				`kips`.`used` = 1
		";

		$aResult = DB::getQueryCol($sSql, array(
			'inquiry_id' => (int)$this->id
		));
		
		return $aResult;
	}

	/**
	 * @return bool
	 */
	public function canShowPositionsTable() {
		return true;
	}

	/**
	 * Letztes Level holen
	 * Methode war zuvor in der Platzhalterklasse für {last_level}…
	 *
	 * $aOptions:
	 * 	language: Sprache
	 * 	courselanguage_id: Auf Kurssprache mit ID prüfen
	 * 	date_until: (Platzhalterklasse)
	 * 	order: ORDER BY überschreiben (Platzhalterklasse)
	 *
	 * @param string $sType id, name, short
	 * @param Ext_TS_Inquiry_Journey_Course $oInquiryCourse
	 * @param array $aOptions
	 * @return string
	 * @throws UnexpectedValueException
	 */
	public function getLastLevel($sType='id', $oInquiryCourse=null, $aOptions=array()) {

		$aSql = array(
			'inquiry_id' => $this->id,
			'inquiry_course_id' => 0,
			'name' => 'id',
			//'courselanguage_id' => 0,
			'course_id' => 0,
			'date_until' => ''
		);

		$aSql = array_merge($aSql, $aOptions);

		if($sType === 'short') {
			$aSql['name'] = 'name_short';
		} elseif($sType === 'name') {

			// Für den vollen Namen wird eine Sprache benötigt!
			if(empty($aOptions['language'])) {
				$oSchool				= Ext_Thebing_Client::getFirstSchool();
				$aOptions['language']	= $oSchool->getInterfaceLanguage();
			}

			$aSql['name'] = 'name_'.$aOptions['language'];
		}

		// Inquiry Course ID setzen, wenn vorhanden
		if($oInquiryCourse instanceof Ext_TS_Service_Interface_Course) {
			$aSql['inquiry_course_id'] = $oInquiryCourse->id;
		}

		// ORDER BY durch Parameter ggf. überschreiben
		$sOrderBy = " `ktp`.`week` DESC ";
		if(isset($aOptions['order'])) {
			// highest_level geht nach Sortierung, kommt aus Platzhalterklasse
			$sOrderBy = $aOptions['order'];
		}

		$sWhere = "";
		
		if(!empty($aSql['date_until'])) {
			$sWhere .= " `ktp`.`week` <= :date_until AND ";
		}

		if(isset($aSql['courselanguage_id']) && $aSql['courselanguage_id'] > 0) {
			$sWhere .= " `ts_i_j_c`.`courselanguage_id` = :courselanguage_id AND ";
		}

		if(isset($aSql['inquiry_course_id']) && $aSql['inquiry_course_id'] > 0) {
			$sWhere .= " `ktp`.`inquiry_course_id` = :inquiry_course_id AND ";
		}
		
		if(!empty($aSql['course_id'])) {
			$sWhere .= " `ktp`.`course_id` = :course_id AND ";
		}
		
		$sSql = "
			SELECT
				`ktul`.`{$aSql['name']}` `level`
			FROM
				`kolumbus_tuition_progress` `ktp` INNER JOIN
				`ts_tuition_levels` `ktul` ON
					`ktul`.`id` = `ktp`.`level` AND
					`ktul`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys_courses` `ts_i_j_c` ON
					`ts_i_j_c`.`id` = `ktp`.`inquiry_course_id` AND
					`ts_i_j_c`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys` `ts_i_j` ON
					`ts_i_j`.`id` = `ts_i_j_c`.`journey_id` AND
					`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_i_j`.`active` = 1 INNER JOIN
				`ts_inquiries` `ki` ON
					`ki`.`id` = `ts_i_j`.`inquiry_id` AND
					`ki`.`active` = 1 INNER JOIN
				`kolumbus_tuition_courses` `ktc` ON
					`ktc`.`id` = `ts_i_j_c`.`course_id` AND
					`ktc`.`active` = 1
			WHERE
				`ktp`.`inquiry_id` = :inquiry_id AND
				".$sWhere."
				`ktp`.`active` = 1
			ORDER BY
				".$sOrderBy."
			LIMIT
				1
			";

			$sLastLevel = DB::getQueryOne($sSql,$aSql);

			// TODO Auf einen Query umstellen
			if(empty($sLastLevel)) {

				if (!empty($oInquiryCourse)) {

					$levelId = Ext_Thebing_Placementtests_Results::getLevelForInquiryAndLanguage($this->id, $oInquiryCourse->getCourseLanguage()->id);
				}
				if (!empty($levelId)) {
					$sLastLevel = Ext_Thebing_Tuition_Level::getInstance($levelId)->getName($aOptions['language']);
				}
			}

		return $sLastLevel;
	}

	/**
	 * @TODO Das kann man auch viel besser lösen…
	 *
	 * @return int[] Ext_Thebing_Tuition_Level IDs
	 */
	public function getPlacementtestInfoForIndex($sField='level_id') {

		$aResult = $this->getPlacementtestResults();

		if(empty($aResult)) {
			return [];
		}

		$aIds = array_map(
			function($aRow) use ($sField) {
				return $aRow->$sField;
			},
			$aResult
		);
		$aIds = array_unique($aIds);

		return $aIds;
	}

	public function hasProformaOrInvoice() {
		if(
			$this->has_invoice ||
			$this->has_proforma
		) {
			return 1;
		}
		
		return 0;
	}

	/**
	 * Liefert den spätesten Kurs der Buchung anhand der spätesten Zuweisung
	 *
	 * @return Ext_TS_Inquiry_Journey_Course|null
	 */
	public function getFirstJourneyCourseByAllocation() {
		$aReturn = array(
			'course' => null,
			'week' => null
		);

		$sSql = "
			SELECT
				`ktbic`.`inquiry_course_id`,
				MIN(`ktb`.`week`) `min_week`
			FROM
				`kolumbus_tuition_blocks` `ktb` INNER JOIN
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` ON
					`ktbic`.`block_id` = `ktb`.`id`
			WHERE
				`ktbic`.`inquiry_course_id` IN (
					SELECT
						`ts_itc`.`id`
					FROM
						`ts_inquiries_journeys_courses` `ts_itc` INNER JOIN
						`ts_inquiries_journeys` `ts_ij` ON
							`ts_ij`.`id` = `ts_itc`.`journey_id`
					WHERE
						`ts_ij`.`inquiry_id` = :inquiry_id
				) AND
				`ktb`.`active` = 1 AND
				`ktbic`.`active` = 1
			GROUP BY
				`ktbic`.`id`
			ORDER BY
				`min_week` ASC
			LIMIT 1
		";

		$aRow = (array)DB::getQueryRow($sSql, array('inquiry_id' => $this->id));

		if(!empty($aRow)) {
			$aReturn['course'] = Ext_TS_Inquiry_Journey_Course::getInstance($aRow['inquiry_course_id']);
			$aReturn['week'] = new DateTime($aRow['min_week']);
		}

		return $aReturn;
	}
	/**
	 * Liefert den spätesten Kurs der Buchung anhand der spätesten Zuweisung
	 *
	 * @return Ext_TS_Inquiry_Journey_Course|null
	 */
	public function getLatestJourneyCourseByAllocation() {
		$aReturn = array(
			'course' => null,
			'week' => null
		);

		$sSql = "
			SELECT
				`ktbic`.`inquiry_course_id`,
				MAX(`ktb`.`week`) `max_week`
			FROM
				`kolumbus_tuition_blocks` `ktb` INNER JOIN
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` ON
					`ktbic`.`block_id` = `ktb`.`id`
			WHERE
				`ktbic`.`inquiry_course_id` IN (
					SELECT
						`ts_itc`.`id`
					FROM
						`ts_inquiries_journeys_courses` `ts_itc` INNER JOIN
						`ts_inquiries_journeys` `ts_ij` ON
							`ts_ij`.`id` = `ts_itc`.`journey_id`
					WHERE
						`ts_ij`.`inquiry_id` = :inquiry_id
				) AND
				`ktb`.`active` = 1 AND
				`ktbic`.`active` = 1
			GROUP BY
				`ktbic`.`id`
			ORDER BY
				`max_week` DESC
			LIMIT 1
		";

		$aRow = (array)DB::getQueryRow($sSql, array('inquiry_id' => $this->id));

		if(!empty($aRow)) {
			$aReturn['course'] = Ext_TS_Inquiry_Journey_Course::getInstance($aRow['inquiry_course_id']);
			$aReturn['week'] = new DateTime($aRow['max_week']);
		}

		return $aReturn;
	}

	/**
	 * liefert die Gesamtzahl aller gebuchten Kurse
	 * 
	 * @return integer
	 */
	public function getCourseUnits() {
		$iUnits = 0;
		
		$aJourneyCourses = $this->getCourses();
		foreach ($aJourneyCourses as $oJourneyCourses) {
			$iUnits += $oJourneyCourses->getUnits();
		}
		
		return $iUnits;
	}

	/**
	 * Liefert einen String mit eventuell mehreren
	 * Status der gewählten Woche zurück,
	 * getrennt mit einem Leerzeichen
	 *
	 * @param $sKey
	 * @param WDDate $oDate
	 * @return mixed
	 */
	public function getTuitionIndexValue($sKey, WDDate $oDate=null) {

		if($oDate !== null) {
			$oDate = new DateTime($oDate->get(WDDate::DB_DATE));
		}

		$mValue = Ext_TS_Inquiry_TuitionIndex::getWeekValue($sKey, $this, $oDate);

		return $mValue;
		
	}
	
	public function getRoomSharingForIndex() {
		
		$aRoomSharings = $this->loadRoomSharingCustomers();

		$aData = array();
		foreach($aRoomSharings as $aRoomSharing) {
			$oInquiry = Ext_TS_Inquiry::getInstance($aRoomSharing['share_id']);
			$oCustomer = $oInquiry->getCustomer();
			$aData[] = Ext_Thebing_Gui2_Format_CustomerName::manually_format($oCustomer->lastname, $oCustomer->firstname);
		}

		if(empty($aData)) {
			return null;
		}

		return implode('<br/>', $aData);

	}
	/**
	 * Liefert den letzten zugewiesen Lehrer aller Kurse.
	 * 
	 * @return Ext_Thebing_Teacher|null
	 */
	public function getLastTeacher() {
		
		$aTeacherDays = $this->getTuitionTeachers(true);
		$oSchool = $this->getSchool();
		$oTeacher = null;
		
		if(!empty($aTeacherDays)) {
			$aTmp = [];
			foreach($aTeacherDays as $aTeacher) {
				$dWeek = new DateTime($aTeacher['week']);
				$dDay = Ext_Thebing_Util::getRealDateFromTuitionWeek($dWeek, $aTeacher['day'], $oSchool->course_startday);

				// Until vom Block setzen, damit auch mehrere Lehrer am selben letzten Tag berücksichtigt werden
				$dDay->modify($aTeacher['until']);

				// Wenn es hier mehr als einen Lehrer gibt, ist das wohl auch egal
				$aTmp[$dDay->getTimestamp()] = $aTeacher;
			}

			$aLastTeacher = $aTmp[max(array_keys($aTmp))];
			$oTeacher = Ext_Thebing_Teacher::getInstance($aLastTeacher['teacher_id']);
		}
		
		return $oTeacher;

	}

	/**
	 * Werte für Rechnungsstatus-Filter
	 *
	 * @see \Ext_TS_Inquiry_Index_Gui2_Data::getInvoiceStatusOptions()
	 * @return array
	 */
	public function getInvoiceStatus() {

		$aStatus = array();
		$bIsCancelled = $this->isCancelled();

		// Dokument erstellt oder nicht
		if(
			$this->has_invoice == 1 ||
			$this->has_proforma == 1
		) {
			$aStatus[] = 'invoice_created';
		} else {
			$aStatus[] = 'invoice_not_created';
		}

		// Wurde die Buchung verändert? (Nur setzen, wenn auch Rechnung/Proforma vorhanden)
		if(
			$this->has_invoice == 1 ||
			$this->has_proforma == 1
		) {
			if($bIsCancelled) {
				/*
				 * Wenn die Buchung storniert wurde, ist das Dokument immer aktuell.
				 * Storno-Änderungen gibt es nämlich nicht, aber es kann weiterhin Changes geben,
				 * wenn die Buchung verändert wurde, die Rechnung aber vor der Storno nicht aktualisiert
				 * wurde. Eventuell schreibt das System auch beim Ändern einer stornierten Buchung
				 * noch Changes.
				 */
				$aStatus[] = 'invoice_uptodate';
			} else {
				$aChanges = $this->versions_items_changes; // Lazy Load

				if(empty($aChanges)) {
					$aStatus[] = 'invoice_uptodate';
				} else {
					$aStatus[] = 'invoice_outdated';
				}
			}
		}

		// Storniert oder nicht storniert
		if(!$bIsCancelled) {
			$aStatus[] = 'not_cancelled';
		} else {
			$aStatus[] = 'cancelled';
		}

		if ($this->type & self::TYPE_ENQUIRY) {
			$bAnyOffer = collect($this->getJourneys())->some(function (Ext_TS_Inquiry_Journey $oJourney) {
				return $oJourney->getDocument() !== null;
			});
			if ($bAnyOffer) {
				$aStatus[] = 'offer_created';
			} else {
				$aStatus[] = 'offer_not_created';
			}
		}

		if ($this->isConverted()) {
			// Wird nicht direkt im Filter verwendet, aber in Icon/Row-Style-Klassen
			$aStatus[] = 'enquiry_converted';
		}

		return $aStatus;

	}

	public function isConverted() {

		if (
			$this->hasGroup() &&
			!empty($this->inquiries_childs)
		) {
			return true;
		}

		if (
			$this->type & self::TYPE_ENQUIRY &&
			$this->type & self::TYPE_BOOKING
		) {
			return true;
		}

		return false;

	}

	/**
	 * @return int
	 */
	public function getCreatedForDiscount() {
		return $this->created;
	}

	/**
	 * Mit den Argumenten nach dem entsprechenden Eintrag in der Zwischentabelle suchen
	 *
	 * $sType und $iTypeId, da Uploads keine Objekte sind, sondern nur irgendwo rumschwirren…
	 *
	 * static, 1 = Schülerfoto
	 * static, 2 = Reisepass
	 * flex, ? = Flexibler Upload
	 *
	 * @param string $sType
	 * @param int $iTypeId
	 * @param bool $bReturnKey
	 * @return bool
	 */
	public function searchFlexUploadEntryForUpload($sType, $iTypeId, $bReturnKey=false) {
		foreach($this->flex_uploads as $iKey => $aFlexUploadData) {
			if(
				$aFlexUploadData['type'] === $sType &&
				$aFlexUploadData['type_id'] == $iTypeId
			) {
				if($bReturnKey) {
					return $iKey;
				} else {
					return $aFlexUploadData;
				}
			}
		}

		return null;
	}

	/**
	 * Wurde der Upload für die Student-App freigegeben?
	 *
	 * $sType und $iTypeId, da Uploads keine Objekte sind, sondern nur irgendwo rumschwirren…
	 *
	 * @param string $sType
	 * @param int $iTypeId
	 * @return bool
	 * @throws Exception
	 */
	public function isUploadReleasedForStudentApp($sType, $iTypeId) {
		$aFlexUploadData = $this->searchFlexUploadEntryForUpload($sType, $iTypeId);
		if($aFlexUploadData !== null) {
			return (bool)$aFlexUploadData['released_student_login'];
		}

		// Standardwert, wenn nichts in der Datenbank gefunden wurde
		if(!isset($aEntry)) {
			if($sType === 'flex') {
				$oUploadField = Ext_Thebing_School_Customerupload::getInstance($iTypeId);
				return (bool)$oUploadField->release_sl;
			}
		}

		return false;
	}

	/**
	 * Alle Tage ermitteln, an denen der Schüler in einer Klasse zugewiesen ist
	 *
	 * @param \DateTime $dFrom
	 * @param \DateTime $dUntil
	 * @param Ext_TS_Inquiry_Journey_Course $oJourneyCourse
	 * @return \Core\Helper\DateTime[]
	 */
	public function getClassDays(\DateTime $dFrom=null, \DateTime $dUntil=null, Ext_TS_Inquiry_Journey_Course $oJourneyCourse=null) {

		$aDates = [];

		$oSearch = new Ext_Thebing_School_Tuition_Allocation_Result();
		$oSearch->setInquiry($this);

		if($dFrom !== null) {
			$oSearch->setTimePeriod('block_day', $dFrom, $dUntil);
		}

		if($oJourneyCourse instanceof Ext_TS_Inquiry_Journey_Course) {
			$oSearch->setInquiryCourse($oJourneyCourse);
		}

		$aBlockDays = $oSearch->fetch();

		// Jeder zugewiesene Tag ist ein Eintrag, aber es interessieren nur die Tage (Gruppierung)
		foreach($aBlockDays as $aBlockDay) {
			if(!in_array($aBlockDay['block_day_date'], $aDates)) {
				$aDates[] = $aBlockDay['block_day_date'];
			}
		}

		$aDates = array_map(function($sDate) {
			return new DateTime($sDate);
		}, $aDates);

		return $aDates;

	}

	/**
	 * @TODO Prüfen, ob das noch verwendet wird, da das nur bei Transcript-Platzhaltern verwendet wird
	 *
	 * Alle Tage ermitteln, an dem der Schüler (im Zeitraum) Ferien hat (5 Tage pro Kurswoche)
	 *
	 * @param \DateTime $dFrom
	 * @param \DateTime $dUntil
	 * @return \Core\Helper\DateTime[]
	 */
	public function getHolidayDays(\DateTime $dFrom=null, \DateTime $dUntil=null) {

		$aDates = [];
		/** @var Ext_TS_Inquiry_Holiday[] $aHolidays */
		$aHolidays = $this->getJoinedObjectChilds('holidays', true);
		$aCourseWeekDays = Ext_Thebing_Util::getCourseWeekDays($this->getSchool()->course_startday);

		foreach($aHolidays as $oHoliday) {

			$dHolidayFrom = new DateTime($oHoliday->from);
			$dHolidayUntil = new DateTime($oHoliday->until);

			// Prüfen, ob Ferien-Eintrag in den Zeitraum fällt
			if(
				$dFrom !== null &&
				$dUntil !== null && !(
					$dHolidayFrom <= $dUntil &&
					$dHolidayUntil >= $dFrom
				)
			) {
				continue;
			}

			// Von jedem Ferientag ein DateTime-Objekt erzeugen, da die Kurswoche verglichen werden muss…
			$oDatePeriod = new DatePeriod($dHolidayFrom, new DateInterval('P1D'), $dHolidayUntil);
			foreach($oDatePeriod as $dDate) {
				if(
					// Prüfen, ob Tag im Zeitraum liegt
					(
						(
							$dFrom === null &&
							$dUntil === null
						) ||
						$dDate->isBetween($dFrom, $dUntil)
					) &&
					// Prüfen, ob Tag in die Kurswoche fällt
					in_array($dDate->format('N'), $aCourseWeekDays) &&
					!in_array($dDate, $aDates) // Tag nur einmal zählen
				) {
					$aDates[] = $dDate;
				}
			}

		}

		return $aDates;

	}

	/**
	 * Alle Prüfungen dieser Buchung ermitteln
	 *
	 * @return Ext_Thebing_Examination[]
	 */
	public function getExaminations() {

		$sSql = "
			SELECT
				`kex`.*
			FROM
				`ts_inquiries` `ts_i` INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
					`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_ij`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys_courses` `ts_ijc` ON
					`ts_ijc`.`journey_id` = `ts_ij`.`id` AND
					`ts_ijc`.`active` = 1 AND
					`ts_ijc`.`visible` = 1 INNER JOIN
				`kolumbus_examination` `kex` ON
					`kex`.`inquiry_course_id` = `ts_ijc`.`id` AND
					`kex`.`active` = 1
			WHERE
				`ts_i`.`id` = :inquiry_id
			GROUP BY
				`kex`.`id`
		";

		$aExaminations = array_map(function($aRow) {
			return Ext_Thebing_Examination::getObjectFromArray($aRow);
		}, (array)DB::getQueryRows($sSql, ['inquiry_id' => $this->id]));

		return $aExaminations;

	}

	/**
	 * Alle Rechnungsnummern + CN für Liste und Tootip
	 *
	 * Das muss leider so gemacht werden, da der Tooltip den originalen Wert braucht.
	 * Der originale Wert wird aber immer aus call: genommen.
	 *
	 * @return string
	 */
	public function getDocumentNumbersCommaSeparatedForIndex() {

		$oSearch = new Ext_Thebing_Inquiry_Document_Search($this);
		$oSearch->setType(['invoice_with_creditnote', 'offer']);
		$oSearch->addJourneyDocuments();

		$aDocuments = collect($oSearch->searchDocument());

		return $aDocuments->map(function (Ext_Thebing_Inquiry_Document $oDocument) {
			return $oDocument->document_number;
		})->join(', ');

	}

	/**
	 * Dokumente (Rechnungen) einer Buchung löschen
	 *
	 * @param $sDocumentTypes
	 */
	public function removeDocuments($sDocumentTypes) {

		$aDocuments = $this->getDocuments($sDocumentTypes, true, true);
		foreach($aDocuments as $oDocument) {
			$oDocument->delete();
		}

		// Alle Dokument-Änderungen löschen (damit Buchung nicht rosa wird, obwohl es keine Rechnung mehr gibt)
		Ext_Thebing_Inquiry_Document_Version::clearChanges($this->id);

		// Cache-Variablen löschen
		$this->has_proforma = 0;
		$this->has_invoice = 0;

		// Beträge auf 0 setzen
		$this->amount = 0;
		$this->amount_initial = 0;
		$this->amount_credit = 0;

	}

	/**
	 * Schreibt die Buchungsdaten in den Stack (wurde eingebaut für die SalesForce Api)
	 *
	 * @return bool
	 * @throws Exception
	 */
	private function writeSalesForceToStack() {

		$iId = $this->_aData['id'];

		$oStackRepository = \Core\Entity\ParallelProcessing\Stack::getRepository();
		$iStackId = $oStackRepository->writeToStack('ts-sales-force/sales-force-transfer', ['inquiry_id' => $iId], 10);

		if ($iStackId === 0) {
			return false;
		}

		return true;

	}

	/**
	 * @TODO Auf getDueTerms() umstellen
	 * @deprecated
	 *
	 * Übersicht oder Betrag/Datum nächster Zahlung
	 *
	 * @param $sField
	 * @return float|null|string|\Ts\Dto\ExpectedPayment
	 */
	public function getIndexPaymentTermData($sField) {

		$sType = 'invoice_without_proforma_and_cancellation';
		if(!$this->has_invoice) {
			// Neuerdings soll das auch für Proformas funktionieren (#13068)
			$sType = 'invoice_without_storno';
		}

		$oSearch = new Ext_Thebing_Inquiry_Document_Search($this);

		$oSearch->setType($sType);

		$aDocuments = $oSearch->searchDocument(true, true);

		if(
			$sField === 'paymentterms_next_payment_date' ||
			$sField === 'paymentterms_next_payment_amount' ||
			$sField === 'paymentterms_next_payment_object'
		) {

			$dMinDate = null;
			$oMinVersion = null;
			foreach($aDocuments as $oDocument) {
				$oVersion = $oDocument->getLastVersion();

				if (!$oVersion) {
					continue;
				}

				$oVersion->setInquiry($this);
				$sDate = $oVersion->getIndexPaymentTermData('paymentterms_next_payment_date');
				if($sDate !== null) {
					$dDate = new DateTime($sDate);
					if(
						$dMinDate === null ||
						$dMinDate > $dDate
					) {
						$oMinVersion = $oVersion;
						$dMinDate = $dDate;
					}
				}
			}

			if($oMinVersion !== null) {
				return $oMinVersion->getIndexPaymentTermData($sField);
			}

			return null;

		} elseif($sField === 'paymentterms_overview') {

			$sOverview = '';
			$iEntries = 0;
			foreach($aDocuments as $oDocument) {
				$oVersion = $oDocument->getLastVersion();

				$oVersion->setInquiry($this);
				
				$sVersionOverview = $oVersion->getIndexPaymentTermOverview(true);
				
				$iEntries += (int)Illuminate\Support\Str::before($sVersionOverview, "\n");
				$sOverview .= Illuminate\Support\Str::after($sVersionOverview, "\n");

			}

			$sOverview = $iEntries."\n".$sOverview;
			
			return $sOverview;
		}
		
	}

	public function generatePaymentProcessKey(string $type): string {

		if (
			$type === 'next' &&
			$this->type & Ext_TS_Inquiry::TYPE_BOOKING &&
			($oProcess = \Ts\Entity\Payment\PaymentProcess::createPaymentProcess($this)) !== null
		) {
			$oProcess->save();
			return $oProcess->hash;
		}

		return '';
	}

	/**
	 * @inheritdoc
	 */
	public function getPaymentCondition() {

		$oPaymentCondition = parent::getPaymentCondition();

		if($this->checkValidSponsorFinancialGurantee(new DateTime())) {
			$oTmp = TsSponsoring\Entity\Sponsor::getRepository()->getValidPaymentCondition($this->sponsor_id);
			if($oTmp !== null) {
				$oPaymentCondition = $oTmp;
			}
		}

		return $oPaymentCondition;

	}

	/**
	 * Prüfen, ob die Buchung zum angegeben Datum eine gültige Finanzgarantie hat
	 *
	 * @param \DateTime $dDate
	 * @return bool
	 * @throws Exception
	 */
	public function checkValidSponsorFinancialGurantee(\DateTime $dDate) {

		if(
			!$this->isSponsored() ||
			$this->sponsor_id == 0
		) {
			return false;
		}

		$dMax = null;
		$aGurantees = $this->getJoinedObjectChilds('sponsoring_guarantees', true);

		foreach($aGurantees as $oGurantee) {
			if(Core\Helper\DateTime::isDate($oGurantee->until, 'Y-m-d')) {
				$dUntil = new DateTime($oGurantee->until);
				if($dMax === null) {
					$dMax = $dUntil;
				}
				$dMax = max($dMax, $dUntil);
			}
		}

		return $dMax > $dDate;

	}

	/**
	 * @return \TsSponsoring\Entity\InquiryGuarantee[]
	 */
	public function getSponsoringGuarantees() {
		return $this->getJoinedObjectChilds('sponsoring_guarantees', true);
	}

	/**
	 * @return TsSponsoring\Entity\Sponsor\Contact[]
	 */
	public function getSponsorContactsWithValidEmails() {

		/** @var TsSponsoring\Entity\Sponsor\Contact[] $aContacts */
		$aContacts = [];

		$oSponsor = $this->getSponsor();
		if($oSponsor === null) {
			return $aContacts;
		}

		// Das Feld wird eigentlich immer mit dem ersten Eintrag befüllt
		$aContacts = [];
		if($this->sponsor_contact_id) {
			$aContacts[] = TsSponsoring\Entity\Sponsor\Contact::getInstance($this->sponsor_contact_id);
		} else {
			$aContacts = $oSponsor->getJoinTableObjects('contacts');
		}

		$aContacts = array_filter($aContacts, function(Ext_TS_Contact $oContact) {
			$sEmail = $oContact->getFirstEmailAddress()->email;
			return !empty($sEmail) && Util::checkEmailMx($sEmail);
		});

		return $aContacts;

	}

	/**
	 * Wurde die Buchung bestätigt?
	 *
	 * @return boolean
	 */
	public function isConfirmed() {

		// 0000-00-00 00:00:00 ist kein gültiges Datumsformat
		$bConfirmed = DateTime::isDate($this->getData('confirmed'), 'Y-m-d H:i:s');

		return $bConfirmed;

	}
	
	public function copyToSchool(Ext_Thebing_School $oSchool) {
		
		DB::begin(__METHOD__);
		
		$oCopy = $this->createCopy();

		// Dokumente sind keine da, aber diese Methode setzt den Rechnungsstatus und die Beträge zurück
		$oCopy->removeDocuments('all');
		// Zahlungen werden nicht kopiert, aber die Werte werden in der Buchung gespeichert
		$oCopy->calculatePayedAmount(false);
		// Weitere Felder müssen manuell zurückgesetzt werden
		$oCopy->number = '';
		$oCopy->numberrange_id = 0;
		$oCopy->service_from = '0000-00-00';
		$oCopy->service_until = '0000-00-00';
		$oCopy->checkin = null;
		$oCopy->checkout = null;
		$oCopy->save();

		/** @var Ext_TS_Inquiry_Journey[] $aJourneys */
		$aJourneys = $oCopy->getJoinedObjectChilds('journeys');
		foreach ($aJourneys as $oJourney) {
			$oJourney->school_id = $oSchool->id;
			$oJourney->transfer_mode = Ext_TS_Inquiry_Journey::TRANSFER_MODE_NONE;
			$oJourney->transfer_comment = '';
			$oJourney->save();
		}

		// Weitere Kontakte manuell zuweisen
		$otherContacts = $this->getJoinedObjectChilds('other_contacts');
		$otherContactsJoinData = [];
		foreach($otherContacts as $otherContact) {
			\DB::insertData('ts_inquiries_to_contacts', [
				'inquiry_id'=>$oCopy->id,
				'contact_id' => $otherContact->id,
				'type' => $otherContact->type
			]);
		}

		\System::wd()->executeHook('ts_inquiry_copy_to_school', $this, $oCopy);

		DB::commit(__METHOD__);

		return $oCopy;
	}

	public function changeInbox(\Ext_Thebing_Client_Inbox $inbox, bool $force = false)
	{
		if ($this->inbox === $inbox->short) {
			return;
		}

		$this->inbox = $inbox->short;

		if (!$force) {
			if (
				$this->isNewBookingNumberNeeded() ||
				$this->isNewCustomerNumberNeeded()
			) {
				throw new \RuntimeException('New number is needed after inbox change');
			}
		}

	}

	public function generatePartialInvoices() {

		if ($this->partial_invoices_terms === null) {

			Ts\Entity\Inquiry\PartialInvoice::query()
				->where('inquiry_id', $this->id)
				->whereNull('document_id')
				->whereNull('converted')
				->each(fn(Ts\Entity\Inquiry\PartialInvoice $partialInvoice) => $partialInvoice->delete());

			return;
		}
		
		$oPaymentConditionService = new Ext_TS_Document_PaymentCondition($this, true);
		
		$oSchool = $this->getSchool();
		$sLanguage = $this->getLanguage();

		$oDocument = $this->newDocument('proforma_brutto');
		$oDocument->bLockNumberrange = false;
		$oVersion = $oDocument->getJoinedObjectChild('versions'); /** @var Ext_Thebing_Inquiry_Document_Version $oVersion */
		$oVersion->setInquiry($this);
		$oVersion->tax = $oSchool->tax;
		$oVersion->template_language = $sLanguage;
		$oVersion->sLanguage = $sLanguage;
		$oVersion->payment_condition_id = $this->partial_invoices_terms;

		$oPaymentCondition = Ext_TS_Payment_Condition::getInstance($this->partial_invoices_terms);
		
		$oPaymentConditionService->setPaymentCondition($oPaymentCondition);
		$oPaymentConditionService->setDocumentDate(date('Y-m-d'));

		$aItems = $oVersion->buildItems();

		// TODO Externe Steuer fehlt komplett
		$aPaymentRows = $oPaymentConditionService->generateRows($aItems);

		// Das System übernimmt den Status NUR nach der Reihenfolge. Das ist suboptimal.
		$convertedInvoices = DB::getQueryRows("SELECT `converted`,`document_id` FROM `ts_inquiries_partial_invoices` WHERE `inquiry_id` = :inquiry_id AND `converted` IS NOT NULL AND `additional` NOT LIKE '%manual_entry%' ORDER BY `date` ASC", ['inquiry_id'=>$this->id]);
		
		// Vorhandene Einträge löschen
		DB::executePreparedQuery("DELETE FROM `ts_inquiries_partial_invoices` WHERE `inquiry_id` = :inquiry_id AND additional NOT LIKE '%manual_entry%'", ['inquiry_id'=>$this->id]);

		// Manuelle Anzahlungen ermitteln
		$manualInvoices = DB::getQueryRows("SELECT * FROM `ts_inquiries_partial_invoices` WHERE `inquiry_id` = :inquiry_id AND type IN ('deposit', 'interim') AND additional LIKE '%manual_entry%' ORDER BY `date`", ['inquiry_id'=>$this->id]);
		
		$i = 0;
		foreach($aPaymentRows as $oPaymentRow) {

			$amount = $oPaymentRow->fAmount;
			
			foreach($manualInvoices as $manualInvoiceKey=>$manualInvoice) {
				$manualInvoiceDate = new \Carbon\Carbon($manualInvoice['date']);
				if($manualInvoiceDate <= $oPaymentRow->dDate) {
					$amount -= $manualInvoice['amount'];
					unset($manualInvoices[$manualInvoiceKey]);
				} else {
					break;
				}
			}
			
			$oPartialInvoice = Ts\Entity\Inquiry\PartialInvoice::getInstance();
			$oPartialInvoice->inquiry_id = $this->id;
			$oPartialInvoice->payment_condition_id = $oPaymentCondition->id;
			$oPartialInvoice->date = $oPaymentRow->dDate->format('Y-m-d');
			$oPartialInvoice->from	= $oPaymentRow->aSettingData['from'];
			$oPartialInvoice->until = $oPaymentRow->aSettingData['until'];
			$oPartialInvoice->amount = $amount;
			$oPartialInvoice->type = $oPaymentRow->sType;
			$oPartialInvoice->additional = json_encode($oPaymentRow->getArray());
			
			if(isset($convertedInvoices[$i])) {
				$converted = new \Carbon\Carbon($convertedInvoices[$i]['converted']);
				$oPartialInvoice->converted = $converted->getTimestamp();
				$oPartialInvoice->document_id = $convertedInvoices[$i]['document_id'];
			}
			
			$oPartialInvoice->save();

			$i++;
		}
		
		if(!empty($manualInvoices)) {
			throw new RuntimeException('Not able to reassign manual invoices! ('.$this->id.')');
		}
		
	}

	public function getTypeForIndex(): array {

		$types = [];

		if ($this->type & self::TYPE_BOOKING) {
			$types[] = self::TYPE_BOOKING_STRING;
		}

		if ($this->type & self::TYPE_ENQUIRY) {
			$types[] = self::TYPE_ENQUIRY_STRING;
		}

		return $types;

	}
	
	public function isCompanyBooking() {
		
		$booker = $this->getBooker();#->getAddress('billing');

		if(
			$booker instanceof Ext_TS_Inquiry_Contact_Booker &&
			$booker->exist()
		) {
			return true;
		}
        
		return false;
	}

	public function getJoinedObject($sMixed, $sKey = null) {

		// Objekt für Enquiries morphen (kann die WDBasic natürlich nicht), da das absolut unterschiedlich läuft
		if (
			$sMixed === 'group' &&
			$this->type == self::TYPE_ENQUIRY
		) {
			$this->_aJoinedObjects['group']['class'] = Ext_TS_Enquiry_Group::class;
		}

		return parent::getJoinedObject($sMixed, $sKey);

	}

	/**
	 * @return int
	 */
	public function getBookingCreated() {
		
		// Das Erstellungsdatum einer Buchung ist bei umgewandelten Anfragen das Umwandlungsdatum
		if(
			$this->type & self::TYPE_ENQUIRY &&
			$this->type & self::TYPE_BOOKING &&
			$this->converted !== null
		) {
			return $this->converted;
		}
		
		return $this->created;
	}
	
	public function getBookingCreatedForIndex() {

        $iCreated = $this->getBookingCreated();
		$sCreated = date('Y-m-d\TH:i:s', $iCreated);

        return $sCreated;
	}
	
	public function getBookingConfirmedForIndex() {

        $iCreated = $this->confirmed;
		
		if(empty($iCreated)) {
			return null;
		}
			
		$sCreated = strftime('%Y-%m-%dT%H:%M:%S', $iCreated);#

        return $sCreated;
	}

	public function buildDocumentItems(): array {

		$school = $this->getSchool();
		$language = $this->getLanguage();

		$document = $this->newDocument('proforma_brutto');
//		$document->bLockNumberrange = false;
		$version = $document->getJoinedObjectChild('versions'); /** @var Ext_Thebing_Inquiry_Document_Version $version */
		$version->setInquiry($this);
		$version->tax = $school->tax;
		$version->template_language = $language;
		$version->sLanguage = $language;

		return $version->buildItems();

	}

	/**
	 * @return \Illuminate\Support\Collection<Ext_TS_Document_Version_PaymentTerm>
	 */
	public function getDueTerms(): \Illuminate\Support\Collection {

		$terms = collect();
		$type = $this->has_invoice ? 'invoice_without_proforma' : 'invoice';
		$documents = \Ext_Thebing_Inquiry_Document_Search::search($this, $type, true, true);

		foreach ($documents as $document) {
			$version = $document->getLastVersion();
			foreach ($version->getPaymentTerms() as $term) {
				$terms[] = $term;
			}
		}

		return \Ext_TS_Document_Version_PaymentTerm::calculateDueTerms($terms);

	}

//	public function getPaymentInstruction(): ?string {
//
//		/** @var ?Ext_TS_Inquiry_Payment_Unallocated $payment */
//		$payment = Ext_TS_Inquiry_Payment_Unallocated::query()
//			->where('inquiry_id', $this->id)
//			->where('status', Ext_Thebing_Inquiry_Payment::STATUS_PENDING)
//			->whereNotNull('instructions')
//			->first();
//
//		return $payment?->instructions;
//
//	}

	public function isCheckedIn() {
		return $this->checkin !== null;

	}

	public function getInboxKey():?string {

		if(!empty($this->inbox)) {
			return $this->inbox;
		}

		return null;
	}

	public function isTravellerMinor() :bool
	{
		$customer = $this->getFirstTraveller();
		$school = $this->getSchool();

 		if($customer->getAge() <= $school->adult_age) {
			return true;
		} else {
			return false;
		}
	}

	public function isMatchingVegetarian() {

		$matching = $this->getMatchingData();

		if($matching->acc_vegetarian == 2){
			return \L10N::t('Ja');
		} elseif ($matching->acc_vegetarian == 1) {
			return \L10N::t('Nein');
		}

		return null;
	}

	public function isMatchingMuslimDiet() {

		$matching = $this->getMatchingData();

		if($matching->acc_muslim_diat == 2){
			return \L10N::t('Ja');
		} elseif ($matching->acc_muslim_diat == 1) {
			return \L10N::t('Nein');
		}

		return null;
	}

	public function getAccommodationMatchingDates() :?string {

		$matchingInformation = $this->getMatchingInformations(false)[0];

		if(empty($matchingInformation)) {
			return null; // für index
		}

		return Ext_Thebing_Format::LocalDate($matchingInformation['from']).' - '.Ext_Thebing_Format::LocalDate($matchingInformation['until']);
	}

	public function getPlacementtestResults() {
		 return Ext_Thebing_Placementtests_Results::query()
			->where('inquiry_id', $this->id)
			->get()
		 	->toArray();
	}

	public function getCorrectPlacementtestAnswers() {

		$placementtestResults = $this->getPlacementtestResults();

		$result = [];
		foreach ($placementtestResults as $placementtestResult) {
			$result[] = $placementtestResult->getFormattedTotalCorrectAnswers();
		}

		return implode(', ', $result);
	}

	/**
	 * Die Methode setzt einen Einzigartigen Schlüssel für die Buchung (wenn es noch keinen gibt).
	 * Im Moment wird die Methode nur Aufgerufen wenn der "visa_qr_code"-Platzhalter verwendet wird
	 */
	public function setUniqueKey() {
		if (empty($this->unique_key)) {
			$this->unique_key = $this->getUniqueKey();
			$this->updateField('unique_key');
		}
	}

	/**
	 * getUnique Key gibt es schon für das Generieren vom Key
	 * @return string
	 */
	public function uniqueKey() {
		$this->setUniqueKey();
		return $this->unique_key;
	}

	public function getVisaQrCode() {

		$renderer = new BaconQrCode\Renderer\ImageRenderer(
			new BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
			new BaconQrCode\Renderer\Image\SvgImageBackEnd()
		);

		$writer = new BaconQrCode\Writer($renderer);

		$this->setUniqueKey();

		$url = 'https://'.\System::d('visa_host', 'visa.fidelo.com').'/'.\Util::getInstallationKey().'/'.$this->unique_key;

		// Format funktioniert so nur für PDFs (wegen TCPDF) und "HTML-Felder als Textfelder anzeigen" muss in dem Layout
		// aktiviert sein
		$qrImage = base64_encode($writer->writeString($url));

		$image = new Ext_Gui2_Html_Image();

		$image->src = 'data:image/svg;base64,@'. $qrImage;

		return $image->generateHTML();
	}

	// Wird noch nicht benutzt, kommt noch, siehe #21752
//	public function getFeedbackLink($questionaryId) {
//		return '[FEEDBACKLINK:'.$questionaryId.':'.$this->getJourney()->id.']';
//	}

	public function getCourseLanguages() {

		$courses = $this->getCourses();
		$courseLanguages = [];
		foreach ($courses as $course) {
			$courseLanguage = $course->getJoinedObject('course_language');
			if (!in_array($courseLanguage, $courseLanguages)) {
				$courseLanguages[$courseLanguage->id] = $courseLanguage;
			}
		}

		return $courseLanguages;
	}


//
//	public static function getCourseListShortAndLongForms() {
//
//		$courseListLongForms = Ext_TS_Inquiry_Journey_Course_Gui2_Data::getCourseList();
//		$courseListShortForms = Ext_TS_Inquiry_Journey_Course_Gui2_Data::getCourseList(true);
//		$courseListIds = array_flip($courseListLongForms);
//		$result = [];
//
//		foreach ($courseListIds as $courseListId) {
//			$result[$courseListId] = $courseListShortForms[$courseListId] .= ' - '.$courseListLongForms[$courseListId];
//		}
//
//		return $result;
//	}
//
//

	public function getSponsoringID() {
		return $this->getTraveller()->getDetail('sponsor_id_number');
	}

	public function getInvoices() {
		// array_reverse(), damit der erste Eintrag die erste Rechnung ist und so, damit @first und @last im smarty Template
		// dann entsprechend auch Sinn ergeben.
		return array_reverse($this->getDocuments('invoice', true, true));
	}

	public function getGroupMembers() {
		return $this->getGroup()?->getMembers();
	}

	public function hasSubAgency(): bool {
		
		if($this->subagency_id > 0) {
			return true;
		}
		
		return false;
	}

	/**
	 * True, falls die Buchung bereits einen Entwurf hat.
	 * Übergebene $ignoreDocumentId zählt dabei nicht.
	 * Bei Angabe von $companyId, werden nur Rechnungen an diese Firma betrachtet.
	 *
	 * @param array $ignoreDocumentIds
	 * @param ?\TsAccounting\Entity\Company $company
	 * @param bool|null $creditNote
	 * @return bool
	 * @throws Exception
	 */
	public function hasDraft(array $ignoreDocumentIds = [], ?TsAccounting\Entity\Company $company = null, ?bool $creditNote = null): bool
	{
		$types = match($creditNote) {
			false => 'invoice_without_creditnote',
			true => 'creditnote',
			null => 'invoice_with_creditnote',
		};
		$result = $this->getDocuments($types, draft: true);
		if (!is_array($result)) {
			return false;
		}
		$documents = collect($result);
		if (!empty($ignoreDocumentIds)) {
			$documents = $documents->reject(
				fn($document) => in_array($document['id'], $ignoreDocumentIds)
			);
		}
		if ($company) {
			return (bool)\Ext_Thebing_Inquiry_Document::query()
				->select('kid.*')
				->join('kolumbus_inquiries_documents_versions', 'kid.latest_version', '=', 'kolumbus_inquiries_documents_versions.id')
				->whereIn('kid.id', $documents->pluck('id'))
				->where('kolumbus_inquiries_documents_versions.company_id', $company->getId())
				->first();
		} else {
			return $documents->isNotEmpty();
		}
	}
	
}
