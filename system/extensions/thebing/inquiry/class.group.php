<?php

/**
 * @property int|string $id
 * @property int|string $transfer_mode
 */
class Ext_Thebing_Inquiry_Group extends Ext_Thebing_Basic implements Ext_TS_Group_Interface{

	use \Ts\Traits\Numberrange;

	const JOIN_CONTACTS = 'contacts';

	protected $_sTable = 'kolumbus_groups';

	/**
	 * @var string
	 */
	protected $_sEditorIdColumn = 'editor_id';

	/**
	 * @var array
	 */
	protected static $aMemberCache = [];

	protected $_sPlaceholderClass = \Ts\Service\Placeholder\Booking\Group::class;

	/**
	 * @var array
	 */
	protected $_aFormat = array(
		'changed' => array(
			'format' => 'TIMESTAMP'
			),
		'created' => array(
			'format' => 'TIMESTAMP'
			),
		'arrival' => array(
			'format' => 'TIMESTAMP'
			),
		'departure' => array(
			'format' => 'TIMESTAMP'
			)
	);

	protected $_aJoinTables = [
		// Die Tabelle wird nur für Enquiry-Gruppen verwendet, da bei Inquiries jeder Kontakt eine Buchung ist
		self::JOIN_CONTACTS => [
			'table'	=> 'ts_groups_to_contacts',
			'foreign_key_field'	=> 'contact_id',
			'primary_key_field'	=> 'group_id',
			'static_key_fields' => ['type' => 'inquiry'],
			'class'	=> 'Ext_TS_Group_Contact',
			'autoload' => false
		]
	];

	protected $_aJoinedObjects = array(
		'contact' => array(
			'class' => 'Ext_TS_Inquiry_Group_ContactPerson',
			'key' => 'contact_id',
			'check_active' => true,
			'query' => false
		),
		'inquiries' => [
			'type' => 'child',
			'class' => Ext_TS_Inquiry::class,
			'key' => 'group_id',
			'check_active' => true
		]
	);

	protected $sNumberrangeClass = \Ts\Service\Numberrange\BookingGroup::class;
	
	/**
	 * @var array
	 */
	protected $_aFlexibleFieldsConfig = [
		'groups_enquiries_bookings' => []
	];

	public function __get($name) {
		
		switch($name) {
			case 'lastname':
			case 'firstname':
			case 'email':
				$contact = $this->getContactPerson();
				return $contact->$name;
				break;
			default:
				return parent::__get($name);
		}
		
	}
	
//	/**
//	 * @var string
//	 */
//	protected $_sEntityFlexType = 'booking';
	
	public function getShortName(){
		return $this->short;
	}

	/**
	 * Agentur der Gruppe (analog zu Ext_TS_Inquiry::getAgency())
	 *
	 * @return Ext_Thebing_Agency|false
	 * @throws Exception
	 */
	public function getAgency() {

		if($this->agency_id == 0) {
			return false;
		}

		$oAgency = \Ext_Thebing_Agency::getInstance($this->agency_id);

		if (!$oAgency->exist()) {
			return false;
		}

		$oAgency->setSchool($this->getSchool());
		return $oAgency;
	}

	/**
	 * @param string $sType
	 * @param bool|false $bAsObjects
	 * @return array|Ext_Thebing_Inquiry_Group_Accommodation[]
	 */
	public function getAccommodations($sType = '', $bAsObjects = false) {

		$aSql = array();
		$sWhereAddon = "";

		if(!empty($sType)){
			$sWhereAddon .=" AND `type` = :type";
			$aSql['type'] = $sType;
		}

		$sSql = "SELECT 
						*,
						UNIX_TIMESTAMP(`from`) `from`,
						UNIX_TIMESTAMP(`until`) `until`
					FROM
						`kolumbus_groups_accommodations`
					WHERE
				`group_id` = :group_id AND
						`active` = 1
				{$sWhereAddon}
			ORDER BY
				`from`
		";

		$aSql['group_id'] = (int)$this->id;
		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		if($bAsObjects) {
			$aResult = array_map(function ($aAccommodation) {
				return Ext_Thebing_Inquiry_Group_Accommodation::getInstance($aAccommodation['id']);
			}, $aResult);
		}

		return $aResult;
	}
	
	/**
	 * @param string $sType
	 * @param bool|false $bAsObjects
	 * @return array|Ext_Thebing_Inquiry_Group_Course[]
	 */
	public function getCourses($sType = '', $bAsObjects = false) {

		$aSql = array();
		$sWhereAddon = "";

		if(!empty($sType)){
			$sWhereAddon .=" AND `type` = :type";
			$aSql['type'] = $sType;
		}

		$sSql = "SELECT
						*,
						UNIX_TIMESTAMP(`from`) `from`,
						UNIX_TIMESTAMP(`until`) `until`
					FROM
						`kolumbus_groups_courses`
					WHERE
				`group_id` = :group_id  AND
						`active` = 1
				{$sWhereAddon}
			ORDER BY
				`from`
		";

		$aSql['group_id'] = (int)$this->id;

		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		if($bAsObjects) {
			$aResult = array_map(function ($aCourse) {
				return Ext_Thebing_Inquiry_Group_Course::getInstance($aCourse['id']);
			}, $aResult);
		}

		return $aResult;
	}

	/**
	 * @param string $sFilter
	 * @param bool|false $bIgnoreBookingStatus
	 * @return Ext_Thebing_Inquiry_Group_Transfer|Ext_Thebing_Inquiry_Group_Transfer[]
	 */
	public function getTransfers($sFilter = '', $bIgnoreBookingStatus = false){
		$aBack = array();

		$sWhereAddon = '';

		switch($sFilter){
			case 'arrival':
				// Prüfen ob Gruppe Anreise haben darf
				if (
					$this->transfer_mode & Ext_TS_Inquiry_Journey::TRANSFER_MODE_ARRIVAL ||
					$bIgnoreBookingStatus
				) {
					$sWhereAddon.= " AND `transfer_type` = 1 ";
				} else {
					return $aBack;
				}
				break;
			case 'departure':
				// Prüfen ob Gruppe Abreise haben darf
				if (
					$this->transfer_mode & Ext_TS_Inquiry_Journey::TRANSFER_MODE_DEPARTURE ||
					$bIgnoreBookingStatus
				) {
					$sWhereAddon.= " AND `transfer_type` = 2 ";
				} else {
					return $aBack;
				}
				break;
			case 'additional':
				$sWhereAddon.= " AND `transfer_type` = 0 ";
				break;
			default:
				break;
		}

		$sSql = "SELECT
						*
					FROM
						`kolumbus_groups_transfers`
					WHERE
						`group_id` = :group_id AND
				`active` = 1
				{$sWhereAddon}
			ORDER BY
				`transfer_date`
		";

		$aSql = array();
		$aSql['group_id'] = (int)$this->id;

		$aResult = DB::getPreparedQueryData($sSql,$aSql);
		
		foreach((array)$aResult as $aData){
			if(
				$sFilter == 'arrival' ||
				$sFilter == 'departure'
			){
				// KEIN getInstance() verwenden!!!
				return new Ext_Thebing_Inquiry_Group_Transfer($aData['id']);
			}else{
				// KEIN getInstance() verwenden!!!
				$aBack[] = new Ext_Thebing_Inquiry_Group_Transfer($aData['id']);
			}
		}

		return $aBack;
	}
	
	/**
	 * Liefert ein Array mit allen Inquiry Objekten dieser Gruppe
	 * ACHTUNG: Liefert keine echten Objekte!!!
	 *
	 * @param int $iGroup
	 * @return Ext_TS_Inquiry[]
	 */
	public static function getInquiriesOfGroup($iGroup) {

		$sSql = "
					SELECT
						*
					FROM
						`ts_inquiries`
					WHERE
						`group_id` = :group_id AND
					    `type` & ".Ext_TS_Inquiry::TYPE_BOOKING." AND
						`active` = 1
					";

		$aSql = array();
		$aSql['group_id'] = (int)$iGroup;

		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		$aBack = array();
		foreach((array)$aResult as $aData) {
			$aBack[] = Ext_TS_Inquiry::getObjectFromArray($aData);
		}

		return $aBack;
	}

	protected function getInquiryQuery(string $sSelect, string $sWhere = '') {

		$sSql = "
					SELECT
						".$sSelect."
					FROM
						`ts_inquiries` `ts_i` INNER JOIN
						`ts_inquiries_journeys` `ts_i_j` ON
							`ts_i_j`.`inquiry_id` = `ts_i`.`id` AND
							`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
							`ts_i_j`.`active` = 1 AND
							`ts_i_j`.`school_id` = :school_id INNER JOIN
						`ts_inquiries_to_contacts` `ts_i_to_c` ON
							`ts_i_to_c`.`inquiry_id` = `ts_i`.`id` AND
							`ts_i_to_c`.`type` = 'traveller' INNER JOIN
						`tc_contacts` `tc_c` ON
							`tc_c`.`id` = `ts_i_to_c`.`contact_id` AND
							`tc_c`.`active` = 1 LEFT JOIN
						`ts_journeys_travellers_detail` `ts_j_t_d` ON
							`ts_j_t_d`.`journey_id` = `ts_i_j`.`id` AND
							`ts_j_t_d`.`traveller_id` = `tc_c`.`id` AND
							`ts_j_t_d`.`type` = 'guide'
					WHERE
						`ts_i`.`group_id` = :group_id AND
						`ts_i`.`active` = 1 AND
						`ts_i`.`type` & ".Ext_TS_Inquiry::TYPE_BOOKING."
						".$sWhere."
					ORDER BY
						`ts_i`.`created`
						";
		return $sSql;
	}

	/**
	 * @TODO Die Methode liefert wegen den Joins nicht unbedingt alle Buchungen der Gruppe
	 *
	 * @param bool|false $mOnlyGuides (true = only guides, false = all, 2 = no guides)
	 * @param bool|true $bFilterCancelled
	 * @param bool|true $mReturn (true = objects, false = ids, 2 = array)
	 * @return Ext_TS_Inquiry[]|int[]|array
	 */
	public function getInquiries($mOnlyGuides = false, $bFilterCancelled=true, $mReturn=true) {

		$aBack = array();

		if(
			$this->id > 0 &&
			$this->school_id > 0
		){

			$sWhereAddon = "";

			if($mOnlyGuides === true) {
				$sWhereAddon .= " AND IFNULL(`ts_j_t_d`.`value`, 0) = 1 ";
			} elseif($mOnlyGuides === 2) {
				$sWhereAddon .= " AND IFNULL(`ts_j_t_d`.`value`, 0) != 1 ";
			}
			if($bFilterCancelled){
				#$sWhereAddon .= " AND `ki`.`canceled` = 0 ";
			}

			$sSelect = "`ts_i`.`id`,
						`tc_c`.`lastname`,
						`tc_c`.`firstname`,
						`ts_j_t_d`.`value`,
						IFNULL(`ts_j_t_d`.`value`, 0) `guide`";

			$sSql = $this->getInquiryQuery($sSelect, $sWhereAddon);

			$aSql = array(
				'group_id'=> (int)$this->id,
				'school_id'=> (int)$this->school_id
			);

			if(
				$mReturn === true ||
				$mReturn === false
			) {
				$aResult = DB::getQueryCol($sSql,$aSql);
				if($mReturn === true) {
					foreach((array)$aResult as $iDataId){
						$aBack[] = Ext_TS_Inquiry::getInstance($iDataId);
					}
				} else {
					$aBack = (array)$aResult;
				}
			} else {
				$aResult = DB::getQueryRows($sSql,$aSql);

				$aBack = (array)$aResult;
			}

		}

		return $aBack;

	}

	/**
	 * Funktion prüft anhand der Gruppen ID ob Kurse gespeichert werden dürfen
	 * 1 = speichern
	 * 2 = nur Zeiträume speichern
	 * 3 = nicht speichern
	 */
	public static function checkForSaveData($sType = 'course', $iGroupid = 0){
		if($iGroupid > 0){
			$oGroup = new Ext_Thebing_Inquiry_Group($iGroupid);
			
			switch($sType){
				case 'course':
					if($oGroup->course_data == 'no'){
						return 1;
					}elseif($oGroup->course_data == 'only_time'){
						return 2;
					}elseif($oGroup->course_data == 'complete'){
						return 3;
					}else{
						return 1;
					}
					break;
				case 'accommodation':
					if($oGroup->accommodation_data == 'no'){
						return 1;
					}elseif($oGroup->accommodation_data == 'only_time'){
						return 2;
					}elseif($oGroup->accommodation_data == 'complete'){
						return 3;
					}else{
						return 1;
					}
					break;
				case 'transfer':
					if($oGroup->transfer_data == 'no'){
						return 1;
					}elseif($oGroup->transfer_data == 'only_time'){
						return 2;
					}elseif($oGroup->transfer_data == 'complete'){
						return 3;
					}else{
						return 1;
					}
					break;
				default:
					return 1;
			}
			
		}else{
			return 1;
		}
	}

	// Transferorte die für diese Gruppe gelten
	public function getTransferLocations($iType = 1){

		if($this->id > 0){
			$oSchool = $this->getSchool();
		}else{
			$oSchool = Ext_Thebing_Client::getFirstSchool();
		}
		$aBack = $oSchool->getGroupTransferLocations($iType);
		
		return $aBack;
	}

	/**
	 * @return Ext_Thebing_School|null
	 */
	public function getSchool(){
		if($this->school_id > 0){
			return Ext_Thebing_School::getInstance($this->school_id);
		}else{
			return null;
		}
	}
	
	/**
	 * Kontaktperson
	 *
	 * @return Ext_TS_Contact
	 */
	public function getContactPerson(){
		$oContact = $this->getJoinedObject('contact');

		return $oContact;
	}
	
	/**
	 * Ermittelt die Inquiry mit den meisten Dokumenten
	 *
	 * @TODO Das muss anders gelöst werden, da das hier sehr unperformant ist
	 *
	 * @return Ext_TS_Inquiry
	 */
	public function getMainDocumentInquiry() {
		
		$aInquirys = $this->getInquiries(false, false, false);
		$iTempCountDocuments = -1;
		$iMaxDocumentsInquiryId = 0;
		foreach((array)$aInquirys as $iTempInquiryId) {

			$aDocuments = Ext_Thebing_Inquiry_Document_Search::search($iTempInquiryId, 'invoice', true);

			if(count($aDocuments) > $iTempCountDocuments) {
				$iMaxDocumentsInquiryId = $iTempInquiryId;
				$iTempCountDocuments = count($aDocuments);
			}

		}

		$oInquiry = Ext_TS_Inquiry::getInstance($iMaxDocumentsInquiryId);
		
		return $oInquiry;

	}
	
	/**
	 * Wenn die Gruppe keine Buchung hat, liefert die obere Methode trotzdem ein Objekt zurück.
	 * Das ist schlecht für den Index, da dann die Methoden nach inquiry_id = 0 suchen,
	 * was tatsächlich Ergebnisse liefert…
	 *
	 * @return Ext_TS_Inquiry|null
	 */
	public function getExistingMainDocumentInquiry() {
		$oInquiry = $this->getMainDocumentInquiry();
		
		if($oInquiry->exist()) {
			return $oInquiry;
		}
		
		return null;
	}

	/**
	 * @param bool $bOnlyGuides
	 * @return int
	 */
	public function countAllMembers() {
		$aMembers = $this->countMembers();
		return (int)$aMembers['all'];
	}

	
	/**
	 *
	 * @return Ext_TS_Inquiry <array> 
	 */
	public function getGuides() {
		$aGuides = $this->getInquiries(true, true);

		return $aGuides;
	}

	/**
	 * Array of id => name of guides
	 * @return array
	 * @throws Exception
	 */
	function getGuideNames(): array {
		$names = array_map(
			fn ($inquiry) => [
				$inquiry->getCustomer()->getId() => $inquiry->getCustomer()->getName()
			],
			$this->getInquiries(true)
		);
		return count($names) ? array_replace(...$names) : [];
	}
	
	/**
	 *
	 * @return Ext_TS_Inquiry <array> 
	 */
	public function getNotGuideMembers() {
		$aNotGuides	= $this->getInquiries(2, true);
		
		return $aNotGuides;
	}
	
	/**
	 *
	 * Anzahl der Guides in der Gruppe
	 * 
	 * @return int 
	 */
	public function countGuides() {
		$aMembers = $this->countMembers();
		return (int)$aMembers['guides'];
	}
	
	/**
	 *
	 * Anzahl der nicht-Guides in der Gruppe
	 * 
	 * @return int 
	 */
	public function countNonGuideMembers() {
		$aMembers = $this->countMembers();
		$iNonGuideMembersAmount = (int)$aMembers['all'] - (int)$aMembers['guides'];
		return $iNonGuideMembersAmount;
	}

	/**
	 * @return string[]
	 */
	private function countMembers() {

		if(!isset(self::$aMemberCache[$this->id])) {

			$sSelect = "SUM(`ts_j_t_d`.`value`) guides,
						COUNT(*) `all`";
			$aSql = [
				'school_id' => $this->getSchoolId(),
				'group_id' => $this->getId()
			];

			$sSql = $this->getInquiryQuery($sSelect);

			self::$aMemberCache[$this->id] = DB::getQueryRow($sSql, $aSql);
		}

		return self::$aMemberCache[$this->id];
	}
	
	/**
	 * Mitglieder der Gruppe
	 *
	 * Achtung, unterschiedliche Signaturen!
	 * 
	 * @return Ext_TS_Inquiry[]|Ext_TS_Group_Contact[]
	 */
	public function getMembers() {
		$iGroupId = (int)$this->id;
		
		$aMembers = self::getInquiriesOfGroup($iGroupId);
		
		return $aMembers;
	}

	/**
	 * Überschreiben, da nur ID und created (nicht als Int-Timestamp!) benötigt werden
	 *
	 * {@inheritdoc}
	 */
	public function getListQueryDataForIndex($oGui = null) {
		$aQueryData['sql'] = " SELECT `id`, `created` FROM `kolumbus_groups` WHERE `active` = 1 ORDER BY `created` ASC ";

		return $aQueryData;
	}

	/**
	 * Frühster Anfang oder spätestes Ende aller Services (dieser Gruppe) eines Typs
	 *
	 * @param Ext_Thebing_Inquiry_Group_Service[] $aObjects
	 * @param string $sType from/until
	 * @return DateTime|null
	 */
	protected function getServiceStartOrEndDate(array $aObjects, $sType) {
		$aDates = [];

		foreach($aObjects as $oObject) {

			if($oObject instanceof Ext_Thebing_Inquiry_Group_Transfer) {
				// Transfer, der ewige Sonderfall
				$sDate = $oObject->transfer_date;
			} else {
				$sDate = $oObject->$sType;
			}

			if(
					!empty($sDate) &&
					$sDate !== '0000-00-00'
			) {
				$aDates[] = new DateTime($sDate);
			}
		}

		if(!empty($aDates)) {
			if($sType === 'from') {
				return min($aDates);
			} else {
				return max($aDates);
			}
		}

		return null;
	}

	/**
	 * Globals Start- oder Enddatum
	 *
	 * @param string $sType from/until
	 * @return DateTime|null
	 */
	public function getServiceFromOrUntil($sType) {

		$aCourses = $this->getCourses('', true);
		$aAccommodations = $this->getAccommodations('', true);
		$aTransfers = $this->getTransfers();

		$aServices = array_merge($aCourses, $aAccommodations, $aTransfers);

		return $this->getServiceStartOrEndDate($aServices, $sType);

	}

	/**
	 * @return DateTime|null
	 */
	public function getFirstCourseStartDate() {
		$aCourses = $this->getCourses('', true);

		return $this->getServiceStartOrEndDate($aCourses, 'from');
	}

	/**
	 * @return DateTime|null
	 */
	public function getLastCourseEndDate() {
		$aCourses = $this->getCourses('', true);

		return $this->getServiceStartOrEndDate($aCourses, 'until');
	}

	/**
	 * @return DateTime|null
	 */
	public function getFirstAccommodationStartDate() {
		$aAccommodations = $this->getAccommodations('', true);

		return $this->getServiceStartOrEndDate($aAccommodations, 'from');
	}

	/**
	 * @return DateTime|null
	 */
	public function getLastAccommodationEndDate() {
		$aAccommodations = $this->getAccommodations('', true);

		return $this->getServiceStartOrEndDate($aAccommodations, 'until');
	}

	/**
	 * Nächte aller Unterkünfte zählen:
	 *    1. Lücken werden ignoriert
	 *    2. Überschneidende Nächte werden ignoriert
	 *
	 * @return int
	 */
	public function getAccommodationNightsCount() {
		$aDays = [];
		$aAccommodations = $this->getAccommodations('', true);

		foreach($aAccommodations as $oAccommodation) {
			$dFrom = new DateTime($oAccommodation->from);
			$dUntil = new DateTime($oAccommodation->until);
			$oPeriod = new DatePeriod($dFrom, new DateInterval('P1D'), $dUntil);
			foreach($oPeriod as $dDay) {
				if(!in_array($dDay, $aDays)) {
					$aDays[] = $dDay;
				}
			}
		}

		return count($aDays);
	}

	/**
	 * Beim Speichern einer Gruppe muss der Index immer aktualisiert werden,
	 * da $bChanged die ggf. veränderten Buchungen nicht berücksichtigt.
	 *
	 * @inheritdoc
	 */
	public function updateIndexStack($bInsert = false, $bChanged = false) {
		if($this->bUpdateIndexEntry) {
			Ext_Gui2_Index_Stack::add('ts_inquiry_group', $this->id, 0);
		}
	}

	/**
	 * Prüfen, ob die übergebene Inquiry zum übergebenen Dokument gehört
	 *
	 * @param Ext_TS_Inquiry_Abstract $oInquiry
	 * @param Ext_Thebing_Inquiry_Document $oDocument
	 * @return bool
	 */
	public function isInquiryBelongingToDocument(Ext_TS_Inquiry_Abstract $oInquiry, Ext_Thebing_Inquiry_Document $oDocument) {

		// Bei neuen Dokumenten kann man immer davon ausgehen, dass sie zu der Buchung gehören
		if($oDocument->id == 0) {
			return true;
		}
		
		if(!$oDocument->isNumberRequired()) {
			// Methode kann nur für Dokumente mit Nummer aufgerufen werden, da andere keine Verknüpfung haben (Zusatzdokumente sind aber auch jeweils individuell)
			throw new InvalidArgumentException('Can\'t match this group document ('.$oDocument->type.') to other group members!');
		}

		$aInquiryDocuments = $oInquiry->getDocuments('invoice_with_creditnote', true, true);
		foreach($aInquiryDocuments as $oInquiryDocument) {
			if(
				$oDocument->document_number == $oInquiryDocument->document_number &&
				$oDocument->numberrange_id == $oInquiryDocument->numberrange_id
			) {
				return true;
			}
		}

		return false;

	}
	
	/**
	 * @param bool $bLog
	 * @return type
	 */
	public function save($bLog = true) {
		
		// Nummernkreis erzeugen
		$mNumber = $this->getNumber();
		if(empty($mNumber)) {
			$this->generateNumber();
		}

		$aTransfer = parent::save($bLog);

		return $aTransfer;
	}

	public function hasInvoice() {
		
		if($this->id > 0) {
			$oMainInquiry = $this->getExistingMainDocumentInquiry();
			if($oMainInquiry instanceof Ext_TS_Inquiry) {
				return $oMainInquiry->has_invoice;
			}
		}
		
		return false;
	}
	
	public function hasInvoiceOrProforma() {

		if($this->id > 0) {
			$oMainInquiry = $this->getExistingMainDocumentInquiry();
			if($oMainInquiry instanceof Ext_TS_Inquiry) {
				return $oMainInquiry->has_proforma || $oMainInquiry->has_invoice;
			}
		}
		
		return false;
	}
	
}
