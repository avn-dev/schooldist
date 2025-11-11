<?php

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Communication\Interfaces\Model\CommunicationSubObject;
use TsAccommodation\Dto\Allocation\ConfirmationStatus;

/**
 * @property $id 
 * @property $created
 * @property $changed
 * @property $active 		
 * @property $creator_id 		
 * @property $user_id 		
 * @property $inquiry_accommodation_id 		
 * @property $status
 * @property $active_storno
 * @property $room_id
 * @property int $bed
 * @property $from
 * @property $until
 * @property $share_with 		
 * @property $accommodation_confirmed
 * @property $accommodation_transfer_confirmed
 * @property $customer_agency_confirmed
 * @property $accommodation_canceled
 * @property $customer_agency_canceled
 * @property $allocation_changed
 * @property $matching_canceled
 *
 * @method static \Ext_Thebing_Accommodation_AllocationRepository getRepository()
*/
class Ext_Thebing_Accommodation_Allocation extends Ext_Thebing_Basic implements \Communication\Interfaces\Model\HasCommunication {
	use \Core\Traits\WdBasic\MetableTrait;

	// Tabellenname
	protected $_sTable = 'kolumbus_accommodations_allocations';
	protected $_sTableAlias = 'kaal';

	public $bSkipUpdatePaymentStack = false;

	protected $_sPlaceholderClass = \TsAccommodation\Service\AccommodationAllocationPlaceholder::class;

	public $bPaymentGenerationDeleteCheck = true;
	
	protected $_aFormat = array(
		'changed' => array(
			'format' => 'TIMESTAMP'
			),
		'created' => array(
			'format' => 'TIMESTAMP'
			),
		'inquiry_accommodation_id' => array(
			'validate' => 'INT_POSITIVE'
			),
		'room_id' => array(
			'validate' => 'INT_NOTNEGATIVE'
			),
		'from' => array(
			'required' => true
			),
		'until' => array(
			'required' => true
			),
		'accommodation_confirmed' => array(
			'format' => 'TIMESTAMP'
			),
		'customer_agency_confirmed' => array(
			'format' => 'TIMESTAMP'
			),
		'accommodation_transfer_confirmed' => array(
			'format' => 'TIMESTAMP'
			),
		'accommodation_canceled' => array(
			'format' => 'TIMESTAMP'
			),
		'customer_agency_canceled' => array(
			'format' => 'TIMESTAMP'
			),
		'allocation_changed' => array(
			'format' => 'TIMESTAMP'
			),
		'matching_canceled' => array(
			'format' => 'TIMESTAMP'
			)
	);

	protected $_aJoinedObjects = array(
		'journey_accommodation' => array(
			'class' => Ext_TS_Inquiry_Journey_Accommodation::class,
			'key' => 'inquiry_accommodation_id',
			'check_active' => true,
			'type' => 'parent'
		),
	);

	public function isParking(): bool {
        return $this->getAccommodationCategory()->isParking();
    }

	public function isReservation(): bool {
	    return (
	        !empty($this->reservation) &&
            !$this->hasInquiryAccommodation()
        );
    }

    public function setReservationData(array $data) {
        $this->reservation = json_encode($data);
    }

    public function getReservationData(): array {
        if(empty($this->reservation)) {
            return [];
        }

        return json_decode($this->reservation, true);
    }

    public function hasInquiryAccommodation(): bool {
	    return ($this->inquiry_accommodation_id > 0);
    }

	/**
	 * @return Ext_TS_Inquiry_Journey_Accommodation
	 */
	public function getInquiryAccommodation() {

		if($this->hasInquiryAccommodation()){
			return Ext_TS_Inquiry_Journey_Accommodation::getInstance($this->inquiry_accommodation_id);
		}

		// TODO das ist Mist
		return false;
	}

	public function isFirstAllocation() {
		
		$inquiryAccommodation = $this->getInquiryAccommodation();

		$from = new \Carbon\Carbon($this->from);
		
		if($from->toDateString() == $inquiryAccommodation->from) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Liefert Mail adressen der Kunden zu denen Einträge in dieser Tabelle gehören
	 * $bShowBoth = true <- Es werden auch Schüleradressen angegeben wenn Agentur vorhanden
	 */
	public function getMail4Communication($sType = 'customer', $bShowBoth = false){

		$oInquiryAccommodation = $this->getInquiryAccommodation();
		$oInquiry = $oInquiryAccommodation->getInquiry();

		if(
			$oInquiry->agency_id > 0 &&
			$sType == 'agency'
		){

			// Unterkunftskontakte
			// Falls InquiryObjekt vorhanden, dann immer diese Funktion für die Agenturemails verwenden!
			$aContacts = $oInquiry->getAgencyContactsWithValidEmails('accommodation');

			$aBack = array();
			foreach((array)$aContacts as $oContact){
				$aInfo = array();
				$aInfo['object'] = 'Ext_Thebing_Agency_Contact';
				$aInfo['object_id'] = (int)$oContact->id;
				$aInfo['email'] = $oContact->email;
				$aInfo['name'] = $oContact->name_description;
				$aBack[] = $aInfo;
			}

			return $aBack;

		} elseif(
			(
				$oInquiry->agency_id < 1 ||
				$bShowBoth === true 
			) &&
			$sType == 'customer'
		){
			// Kundenkontakt für diese Zuweisung
			$aBack = $oInquiry->getCustomerEmails();
			return $aBack;
			
		} elseif($sType == 'accommodation') {

			// Unterkunftskontakt für diese Zuweisung
			// TODO Irgendwas funktioniert hier mit object_id nicht, im Log steht die Zuweisungs-ID!
			$oAccommodationProvider = $this->getAccommodationProvider();
			if($oAccommodationProvider){
				$aInfo = array();
				$aInfo['object'] = 'Ext_Thebing_Accommodation';
				$aInfo['object_id'] = (int)$oAccommodationProvider->id;
				$aInfo['email'] = $oAccommodationProvider->email;
				$aInfo['name'] = $oAccommodationProvider->ext_33 . ' ('.$oAccommodationProvider->email.')';
				return $aInfo;	
			} else {
				return array();
			}

		}else{
			return array();
		}
	}

	/**
	 * Liefert den Provider der zu dieser Zuweisung gehört.
	 *
	 * @return false|Ext_Thebing_Accommodation
	 */
	public function getAccommodationProvider() {
		if($this->room_id > 0) {
			$oRoom = Ext_Thebing_Accommodation_Room::getInstance($this->room_id);
			return $oRoom->getProvider();
		} else {
			return false;
		}
	}

	/**
	 * Key für Template in Unterkunftskommunikation
	 */
	public static function getCommunicationTemplateKey($aItem = null, $sApplication = '') {

		if(
			$sApplication == 'accommodation_communication_customer_agency' ||
			$sApplication == 'accommodation_communication_history_customer_confirmed' ||
			$sApplication == 'accommodation_communication_history_customer_canceled'
		){
			if($aItem['object'] == 'Ext_Thebing_Agency') {
				$sKey = 'agency';
			}else{
				$sKey = 'customer';
			}
		}else{
			$sKey = 'default';
		}

		return $sKey;
	}

	/**
	 * Check if the Allocation was confirmed
	 * @return bolean
	 */
	public function checkConfirmed(){

		if(
			$this->accommodation_confirmed > 0 ||
			$this->accommodation_transfer_confirmed > 0 ||
			$this->customer_agency_confirmed > 0
		) {
			return true;
		}

		return false;

	}

	/**
	 * @TODO Es ist ganz großer Müll, dass die Methode hier irgendwas macht, anstatt einfach den Eintrag zu löschen
	 *
	 * Delete
	 * $bSystem durch das System gelöschte Zuweisung
	 */
	public function delete($bCheckUnallocatedAllocations = true, $bSystem = false, $bDeleteInactiveAllocations = true){

        if($this->id <= 0){
            return false;
        }

        if($this->bPurgeDelete) {
			$this->bPaymentGenerationDeleteCheck = false;
        	return parent::delete();
		}
        
		// Anzahl aller aktiven Zuweisungen
		$aActiveAllocations = array();

		$oInquiryAccommodation = $this->getInquiryAccommodation();

		$oInquiry = $this->getInquiry();

		// Wurde Zuweisung bestätigt
		$bAllocationConfirmed = $this->checkConfirmed();

		if($bCheckUnallocatedAllocations){
			// Aktive Zuweisungen holen
			$aActiveAllocations = Ext_Thebing_Allocation::getAllocationByInquiryId(
				$oInquiry->id, 
				$this->inquiry_accommodation_id, 
				true
			);
		}

		// Wenn es mehrere zugewiesene Einträge gibt
		if(count($aActiveAllocations) > 1) {

			// Klon erstellen um die alte Zuweisung fest zu halten
			$oNew = $this->createClone(false);
			if($bSystem){
				$oNew->status = 2;
			}else{
				$oNew->status = 1;
			}
			$oNew->matching_canceled = time();
			$oNew->save();
			
			// Zerschneidung verfügbar machen
			$this->active = 1;
			$this->room_id = 0;
			$this->bed = 0;
			$this->comment = '';
			$this->save();

		} else {
			
			// Nur der eigene gefunden
			if(
				$bDeleteInactiveAllocations &&
				$bCheckUnallocatedAllocations &&
				is_object($oInquiryAccommodation)
			){
				// Inaktive allocations löschen
				$oInquiryAccommodation->deleteInactivAllocations();
			}

			if($bAllocationConfirmed === true) {

				if($oInquiry->canceled > 0){
					// Wenn storniert wurde den Flag setzen, da sie trotzdem angezeigt werden sollen zwecks Absage
					$this->active_storno = 0;
				}
				// Wenn bestÃ¤tigt nur deaktivieren wegen History
				$this->active = 0;
				$this->status = 0;
				$this->allocation_changed = time();
				$this->save();

				// Klon erstellen um die alte Zuweisung fest zu halten
				$oNew = $this->createClone(false);
				if($bSystem){
					$oNew->status = 2;
				}else{
					$oNew->status = 1;
				}
				$oNew->matching_canceled = time();
				$oNew->save();

			} else {

				// sich selber deleten
				$this->remove($bSystem);

			}
				
		}

	}

	/**
	 * Cut the Allocation with a space between the two parts
	 */
	public function cut($sCutDBDateTime){

		if($this->id <= 0){
			return false;
		}

		// Edit Old Part
		$oFirst = $this;
		// Create Second Part new
		$oSecond = $this->createClone();

		// If Confirmed
		if($this->checkConfirmed()) {

			$iRoomId = $oFirst->room_id;
			$iBed = $oFirst->bed;

			// Exception deaktivieren, da Eintrag unten auf magische Weise wieder auf active = 1 gesetzt wird
			$this->bPaymentGenerationDeleteCheck = false;

			// Delete Old
			$this->delete(true, false, false);

			$this->bPaymentGenerationDeleteCheck = true;

			// Create both new
			/*
			 * Auskommentiert, da das hier die Zuweisung dupliziert und dann zwei gleiche existieren.
			 * Vermutlich wurden damals durch ->delete() auch $oFirst gelöscht, aber dies scheint
			 * jetzt nicht mehr der Fall zu sein, da $oFirst immer eine aktive Zuweisung sein müsste
			 * (erster Paramter von delete()). #6068
			 */
			//$oFirst = $this->createClone();

			// Raum-ID wird von ->delete() gelöscht, daher wieder setzen
			$oFirst->room_id = $iRoomId;
			$oFirst->bed = $iBed;

		}

		$oFirst->active						= 1;
		$oFirst->until						= $sCutDBDateTime;
		$oFirst->allocation_changed			= time();
		$oFirst->save();

		$oSecond->active = 1;
		$oSecond->room_id = 0;
		$oSecond->bed = 0;
		$oSecond->from = $sCutDBDateTime;
		$oSecond->allocation_changed = time();
		$oSecond->save();

		return true;
	}

	/**
	 * Update Timeframe and Move it to an other room
	 */
	public function updateTimeAndMoveToRoom($iFrom, $iUntil, $iRoom, $iBed=0){
		
		if($this->id <= 0){
			return false;
		}

		$oNewAllo = $this->updateTime($iFrom, $iUntil);
		$oNewAllo = $oNewAllo->moveToRoom($iRoom, $iBed);
		return $oNewAllo;
	}

	/**
	 * Move the Allocation to an other toom
	 *
	 * @param int $iRoom
	 * @param int $iBed
	 * @return bool|Ext_Thebing_Accommodation_Allocation
	 */
	public function moveToRoom($iRoom, $iBed=0){

		if($this->id <= 0){
			return false;
		}

		$oNew = $this;

		// Prüfen ob Einträge verschmolzen werden müssen
		// Anmerkung: Hier wird nicht auf das Bett geprüft, daher wird der Schüler dann ins andere Bett bewegt
		$sSql = "
			SELECT
				*
			FROM
				#table
			WHERE
				`inquiry_accommodation_id` = :inquiry_accommodation_id AND
				`active` = 1 AND
				`status` = 0 AND
				`active_storno` = 1 AND
				`room_id` = :room_id AND (
					`from` = :until OR
					`until` = :from
				)
		";
		
		$aSql = array(
			'table' => $this->_sTable,
			'from' => $this->from ,
			'until' => $this->until,
			'room_id' => $iRoom,
			'inquiry_accommodation_id' => (int)$this->inquiry_accommodation_id
		);
		
		$aResult = (array)DB::getPreparedQueryData($sSql, $aSql);

		// Bezahlte Einträge dürfen nicht verschmolzen werden!
		$aResult = array_filter($aResult, function($aAllocation) {
			$oAllocation = Ext_Thebing_Accommodation_Allocation::getInstance($aAllocation['id']);
			return $oAllocation->getLatestPaymentDate() === null;
		});

		if(count($aResult) > 0) {

			$iFrom = $oNew->from;
			$iUntil = $oNew->until;

			// Start/Ende suchen
			// Passende Löschen
			foreach($aResult as $iKey => $aData){
				if($aData['from'] == $oNew->until){
					$iUntil = $aData['until'];
				} else if($aData['until'] == $oNew->from){
					$iFrom = $aData['from'];
				}

				// Wenn nicht der aktuelle -> löschen
				if($aData['id'] != $oNew->id){
					$oAllocation = Ext_Thebing_Accommodation_Allocation::getInstance($aData['id']);
					$oAllocation->delete(false);
				}
			}

			// Zeitraum anpassen und verschieben
			$oNew = $oNew->updateTimeAndMoveToRoom($iFrom, $iUntil, $iRoom, $iBed);

			// Script hier unterbrechen damit die Matchinginfo am ende nicht unnötig doppelt gestartet wird
			return $oNew;
			
		// Wenn bestätigt
		} else if($this->checkConfirmed()){

			$iOldRoom = $this->room_id;
			$oOldRoom = new Ext_Thebing_Accommodation_Room($iOldRoom);
			$oNewRoom = new Ext_Thebing_Accommodation_Room($iRoom);

			// Neu eintragen mit neuem Raum
			$oNew = $this->createClone();

			// Löschen
			$this->delete(false);

			// Neu eintragen mit neuem Raum
			$oNew->active					= 1;
			$oNew->room_id = $iRoom;
			$oNew->bed = $iBed;
			$oNew->allocation_changed		= time();
			$oNew->save();

		} else {
			// Nicht bestätigte überschreiben
			$oNew->room_id = $iRoom;
			$oNew->bed = $iBed;
			$oNew->allocation_changed		= time();
			$oNew->save();

		}

		return $oNew;
	}

	/**
	 * Wandelt einen MYSQL DATE TIME bzw. ein Timestamp in einen korrekten
	 * GMT! DATE TIME String um
	 * Wird benötigt da aus JS GTM Timestamps kommen und PHP Seitet manchmal DATE TIME bzw. nicht GTM Daten
	 */
	static public function convertTimestampIntoGMTString($mTimestamp) {

		if($mTimestamp instanceof DateTime) {
			return $mTimestamp->format('Y-m-d H:i:s');
		}

		$sTimestamp = $mTimestamp;

		if(is_numeric($mTimestamp)){
			$sTimestamp = Ext_Thebing_Util::formatGMT($mTimestamp);
		}

		// Prüfen ob Stunde != 00
		// Wenn es zutriff haben wir ein Sommer/Winterzeitproblem!
		$sH = substr($sTimestamp, 11, 2);
		if($sH != '00'){
			$mTimestamp = Ext_Thebing_Util::convertToGMT($mTimestamp);
			$sTimestamp = Ext_Thebing_Util::formatGMT($mTimestamp);
		}

		return $sTimestamp;
	}

	/**
	 * Update Timeframe and (otional) inquiry_accommodation_id
	 * @param DATE TIME/Timestamp $mFrom
	 * @param DATE TIME/Timestamp $mUntil
	 * @param INT $iNewInquiryAccommodationId
	 * @param BOEALEN $bSetUnallocated
	 * @return Ext_Thebing_Accommodation_Allocation
	 */
	public function updateTime($mFrom, $mUntil, $iNewInquiryAccommodationId = 0, $bSetUnallocated = false){

		if($this->id <= 0){
			return false;
		}

		$oNow = $this;

		$sFrom = self::convertTimestampIntoGMTString($mFrom);
		$sUntil = self::convertTimestampIntoGMTString($mUntil);

		if($this->checkConfirmed()) {
			$oNow = $this->createClone();
			//$this->delete();
			$this->remove(true); // Zuweisung ganz löschen, da delete() irgendwas macht

			$oNow->from = $sFrom;
			$oNow->until = $sUntil;
			if($iNewInquiryAccommodationId > 0) {
				$oNow->inquiry_accommodation_id = $iNewInquiryAccommodationId;
			}
			$oNow->allocation_changed = time();
			$oNow->save();

			if($bSetUnallocated){
				$oNow->convertToUnallocatedAllocation();
			}

		} else {

			$oNow->from = $sFrom;
			$oNow->until = $sUntil;
			
			if($iNewInquiryAccommodationId > 0) {
				$oNow->inquiry_accommodation_id = $iNewInquiryAccommodationId;
			}

			$oNow->save();

			if($bSetUnallocated) {
				$this->convertToUnallocatedAllocation();
			}

		}

		return $oNow;
	}

	/**
	 * Convert Allocation to a Unallocated Allocation vor Part Allocation
	 *
	 * @param bool $bSystem
	 * @return bool
	 */
	public function convertToUnallocatedAllocation($bSystem = false){

		if($this->id <= 0){
			return false;
		}

		// Prüfen ob Zuweisung bestätigt wurde über die Kommunikation
		$bConfirmed = $this->checkConfirmed();
		
		if($bConfirmed) {
			
			$oNew = $this->createClone();
			//$this->delete();
			$this->remove($bSystem); // Direkt löschen, da delete() in etwa dasselbe nochmal macht
			
			$oNew->room_id = 0;
			$oNew->bed = 0;
			$oNew->comment = '';
			$oNew->save();
			
		} else {
			
			// Zuweisung wieder zuweisbar machen  UND
			// Ein Clon der Zuweisung erstellen und als gelöscht speichern damitman die Zuweisung nachvollziehen kann
			$oNew = $this->createClone(false);
			if($bSystem){
				$oNew->status = 2;
			}else{
				$oNew->status = 1;
			}
			$oNew->matching_canceled = time();
			$oNew->save();
			
			$this->room_id = 0;
			$this->bed = 0;
			$this->comment = '';
			$this->save();
			
		}

	}

	/**
	 * Create a Clone
	 * @return Ext_Thebing_Accommodation_Allocation
	 */
	public function createClone($bResetTimestamps = true){

		$oClone = new self();
		$oClone->active								= $this->active;
		$oClone->inquiry_accommodation_id			= $this->inquiry_accommodation_id;
		$oClone->room_id = $this->room_id;
		$oClone->bed = $this->bed;
		
		$oClone->from								= $this->from;
		$oClone->until								= $this->until;
		$oClone->share_with							= $this->share_with;
		$oClone->active_storno						= $this->active_storno;

		if(!$bResetTimestamps) {
			$oClone->accommodation_confirmed			= $this->accommodation_confirmed;
			$oClone->accommodation_transfer_confirmed	= $this->accommodation_transfer_confirmed;
			$oClone->customer_agency_confirmed			= $this->customer_agency_confirmed;
			$oClone->accommodation_canceled				= $this->accommodation_canceled;
			$oClone->customer_agency_canceled			= $this->customer_agency_canceled;
			$oClone->allocation_changed					= $this->allocation_changed;
		}

		return $oClone;
	}

	/**
	 * Liefert das Inquiry Object
	 * @return Ext_TS_Inquiry 
	 */
	public function getInquiry(){
		$oInquiryAccommodation = Ext_TS_Inquiry_Journey_Accommodation::getInstance($this->inquiry_accommodation_id);
		$oInquiry = $oInquiryAccommodation->getInquiry();
		return $oInquiry;
	}

	/**
	 * Wurde wohl abgeleitet weil getListQueryData immer active=1 abfragt
	 * @todo : WDBASIC Flag erstellen zum verhindern der active = 1 Abfrage in getListQueryData
	 * Erzeugt ein Query für eine Liste mit Items dieses Objektes
	 * @return array
	 */
	public function getListQueryData($oGui=null) {

		$aQueryData = array();

		$sFormat = $this->_formatSelect();

		$aQueryData['data'] = array();

		$sTableAlias = $this->_sTableAlias;

		if(empty($sTableAlias)) {
			$sTableAlias = $this->_sTable;
		}

		$sAliasString = '';
		$sAliasName = '';
		if(!empty($sTableAlias)) {
			$sAliasString .= '`'.$sTableAlias.'`.';
			$sAliasName .= '`'.$sTableAlias.'`';
		}

		$aQueryData['sql'] = "
				SELECT
					".$sAliasString."*
					{FORMAT}
				FROM
					`{TABLE}` ".$sAliasName."
			";

		$iJoinCount = 1;

		foreach((array)$this->_aJoinTables as $sJoinAlias => $aJoinData){

			$aQueryData['sql'] .= " LEFT OUTER JOIN
									#join_table_".$iJoinCount." #join_alias_".$iJoinCount." ON
									#join_alias_".$iJoinCount.".#join_pk_".$iJoinCount." = ".$sAliasString."`id`
								";

			$aQueryData['data']['join_table_'.$iJoinCount]	=  $aJoinData['table'];
			$aQueryData['data']['join_pk_'.$iJoinCount]		=  $aJoinData['primary_key_field'];
			$aQueryData['data']['join_alias_'.$iJoinCount]	=  $sJoinAlias;

			$iJoinCount++;
		}

		if(array_key_exists('room_id', $this->_aData)) {
			$aQueryData['sql'] .= " WHERE ".$sAliasString."`room_id` > 0 ";
		}

		if(count($this->_aJoinTables) > 0){
			$aQueryData['sql'] .= "GROUP BY ".$sAliasString."`id` ";
		}

		if(array_key_exists('id', $this->_aData)) {
			$aQueryData['sql'] .= "ORDER BY ".$sAliasString."`id` ASC ";
		}

		$aQueryData['sql'] = str_replace('{FORMAT}', $sFormat, $aQueryData['sql']);
		$aQueryData['sql'] = str_replace('{TABLE}', $this->_sTable, $aQueryData['sql']);

		return $aQueryData;

	}

	/**
	 * Sprache der Templates wenn mit Allocations kommuniziert werden soll.
	 *
	 * @return string
	 */
	public function getLanguage() {
		$oSchool = Ext_Thebing_Client::getFirstSchool();		
		return $oSchool->getLanguage();
	}

	public function getPeriod(): ?CarbonPeriod {

		if(empty($this->from) || empty($this->until)) {
			return null;
		}

		$from = (new Carbon($this->from))->startOfDay();
		$until = (new Carbon($this->until))->endOfDay();

		return CarbonPeriod::create($from, $until);
	}

	public function getPeriodWithTime(): ?CarbonPeriod {

		$period = $this->getPeriod();

		if($period === null) {
			return null;
		}

		$category = $this->getAccommodationCategory();

		if(!empty($category->arrival_time)) {
			$period->setStartDate($period->getStartDate()->setTimeFromTimeString($category->arrival_time));
		}

		if(!empty($category->departure_time)) {
			$period->setEndDate($period->getEndDate()->setTimeFromTimeString($category->departure_time));
		}

		return $period;

	}

	/**
	 * Liefert die Länge der Unterkunft in Tagen.
	 *
	 * Wenn kein Start- oder Enddatum angegeben ist, wird 0 zurück gegeben.
	 *
	 * @return int
	 */
	public function getDays() {

		// ohne Start- und Enddatum klappt das nicht
		if(
			empty($this->from) ||
			empty($this->until)
		) {
			return 0;
		}

		$dFrom = new DateTime($this->from);
		$dUntil = new DateTime($this->until);
		$oDiff = $dFrom->diff($dUntil);

		// Das Enddatum gehört auch noch zur Zuweisung, aber da steht immer 00:00:00 drin
		return $oDiff->days + 1;

	}

	// Liefert Kunden dieser Zuweisung
	public function getCustomer(){
		return $this->getInquiry()->getCustomer();
	}

	public function hasRoom() {
		return ($this->room_id > 0);
	}

	/**
	 * Liefert den Raum
	 *
	 * @return bool|Ext_Thebing_Accommodation_Room
	 * @throws Exception
	 */
	public function getRoom(){
		if($this->room_id > 0){
			return Ext_Thebing_Accommodation_Room::getInstance($this->room_id);
		}else{
			return false;
		}
	}
	
	/*
	 * Funktion löscht den Matching Eintrag dieser Zuweisung
	 * $bSystem durch das System gelöschtes Matching
	 */
	public function deleteMatching($bRemoveComplete = false, $bSystem = false){
		
		$bSuccess = false;
		
		$oInquiryAccommodation = $this->getInquiryAccommodation();

		$aAllocations = Ext_Thebing_Allocation::getAllocationByInquiryId(
			$oInquiryAccommodation->inquiry_id, 
			$oInquiryAccommodation->id, 
			true
		);

		// wenn es von der gebuchten unterkunft mehr als 1 aktiven Eintrag gibt wurde er zerschnitten und ein Teil ist noch vorhanden
		// daher nur auf inactive setzten damit man ihn wiede rzuweisen kann
		// ansonsten lösche alle vorhandenen einträge auch die inactiven raus ( komplett neue zuweisung )
		if(
			count($aAllocations) > 1 &&
			$bRemoveComplete == false
		) {
			$bSuccess = $this->convertToUnallocatedAllocation($bSystem);
		} else {
			$bSuccess = $this->delete(true, $bSystem);
		}

		// Wird aktuell von den Methoden noch nicht geliefert
		$bSuccess = true;
		
		return $bSuccess;
	}
	
	/**
	 * Entfernt den aktuellen Eintrag / Setzt den Status und cancelled
	 * @param bool $bSystem 
	 */
	public function remove($bSystem = false){
				
		$iStatus = 1;
		
		if($bSystem){
			$iStatus = 2;
		}
		
		$this->status = (int)$iStatus;
		$this->matching_canceled = date('Y-m-d H:i:s');
		$this->save();

	}

	public function manipulateSqlParts(&$aSqlParts, $sView=null) {
		parent::manipulateSqlParts($aSqlParts, $sView);
		$aSqlParts['where'] .= ' AND
			(active > 0 OR status > 0)
		';
	}
	
	/**
	 * Die Funktion sucht Zuweisungen der zusammenreisenden Schülern dieser Buchung, die mit dieser Zuweisung übereinstimmen
	 * @return Ext_Thebing_Accommodation_Allocation[]
	 */
	public function getRoomSharingAllocations(){
		
		$oInquiry = $this->getInquiry();
		
		$oDate = new WDDate();
		
		$aAllocations = array();
		
		// Zusammenreisende Schüler
		$aRoomSharingInquiries = $oInquiry->getRoomSharingInquiries();
		
		foreach((array)$aRoomSharingInquiries as $oRoomSharingInquiry){
			
			$aInquiryAccommodations = $oRoomSharingInquiry->getAccommodations(true);
			
			// Zuweisungen
			foreach((array)$aInquiryAccommodations as $oInquiryAccommodation){
				$aActivAllocations = Ext_Thebing_Allocation::getAllocationByInquiryId(
					$oRoomSharingInquiry->id, $oInquiryAccommodation->id, true
				);
				
				// prüfen welche Zuweisung Zeitlleich liegen
				foreach((array)$aActivAllocations as $aAllocation){

					$oDate->set($aAllocation['from'], WDDate::TIMESTAMP);				
					$iCheckFrom = $oDate->compare($this->from, WDDate::DB_TIMESTAMP);
					
					$oDate->set($aAllocation['until'], WDDate::TIMESTAMP);				
					$iCheckUntil = $oDate->compare($this->until, WDDate::DB_TIMESTAMP);

					if(
						$iCheckFrom == 0 &&
						$iCheckUntil == 0
					){
						$aAllocations[] = self::getInstance($aAllocation['id']);
					}
				}
			}
		}		
		return $aAllocations;
	}

	/**
	 * Prüft ob vorhandene Unteterkunftsbezahlungen für diese Zuweisung relevant sind
	 * Optional können db_date parameter übergeben werden die als Filter dienen in DENEN
	 * dann geprüft wird
	 *
	 * @param DateTime|null $dFilterFrom
	 * @param DateTime|null $dFilterUntil
	 * @return Ext_Thebing_Accommodation_Payment[]
	 */
	public function checkPaymentStatus(DateTime $dFilterFrom = null, DateTime $dFilterUntil = null) {
		
		$aPayments = array();

		if(
			$dFilterFrom !== null &&
			$dFilterUntil !== null
		) {
			// Nur im Filterzeitraum prüfen
			$dDateCheckFrom = $dFilterFrom;
			$dDateCheckUntil = $dFilterUntil;
		} else {
			// Komplette Allocation prüfen
			$dDateCheckFrom = new DateTime($this->from);
			$dDateCheckUntil = new DateTime($this->until);
		}

		$oInquiryAccommodation = $this->getInquiryAccommodation();

		if(is_object($oInquiryAccommodation)) {

			/** @var Ext_Thebing_Accommodation_Payment[] $aAccommodationPayments */
			$aAccommodationPayments = (array)$oInquiryAccommodation->getJoinedObjectChilds('accounting_payments_active');
			
			// Familie
			$oProvider = $this->getAccommodationProvider();
		
			if(is_object($oProvider)) {
				foreach($aAccommodationPayments as $oAccommodationPayment) {
									
					// Bezahlungen nach Fixgehalt können vernachlässigt werden, bei anderen Kostenkategorien
					// wird die Provider ID bei den Zahlungen gespeichert.
					if($oProvider->id == $oAccommodationPayment->accommodation_id) {
						
						// Prüfen ob die Zeitspanne der Bezahlung im Zeitraum der Allocation ist
						$dDatePaymentDateFrom = new DateTime($oAccommodationPayment->timepoint);
						
						switch($oAccommodationPayment->payment_type) {
							case 'week':						
								$dDatePaymentDateUntil = clone $dDatePaymentDateFrom;
								$dDatePaymentDateUntil->add(new DateInterval('P7D'));
								break;
							case 'month':
								$aMonthLimit = \Core\Helper\DateTime::getMonthPeriods($dDatePaymentDateFrom, $dDatePaymentDateFrom);
								$dDatePaymentDateUntil = reset($aMonthLimit)->until;
								break;
							default:
								$dDatePaymentDateUntil = new DateTime($oAccommodationPayment->until);
								// Da Matching den Tag komplett blockiert, aber eigentlich nach Uhrzeiten gehen müsste, Tag abziehen
								$dDatePaymentDateUntil->sub(new DateInterval('P1D'));
								// Gleiches mit dem Tag davor (Zuweisung wurde in die Vergangenheit verlängert)
								$dDatePaymentDateFrom->add(new DateInterval('P1D'));
								break;
						}

						if(\Core\Helper\DateTime::checkDateRangeOverlap($dDateCheckFrom, $dDateCheckUntil, $dDatePaymentDateFrom, $dDatePaymentDateUntil)) {
							// Zahlung liegt im zu prüfenden Intervall
							$aPayments[$oAccommodationPayment->id] = $oAccommodationPayment;
						}

					}

				}
			}

		}
		
		return $aPayments;

	}
	
	public function save($bLog = true){

		// Eingebaut wegen UAB, sonst gibt es wieder doppelte Zahlungen
		// Im SR ist die Exception durch Vorab-Prüfungen abgefangen
		if(
			(
				$this->_aOriginalData['active'] == 1 &&
				$this->active == 0
			) || (
				$this->_aOriginalData['status'] == 0 &&
				$this->status != 0
			) || (
				$this->_aOriginalData['active_storno'] == 1 &&
				$this->active_storno == 0
			)
		) {
			if(
				$this->bPaymentGenerationDeleteCheck &&
				$this->getLatestPaymentDate(true) !== null
			) {
				throw new RuntimeException('Can not delete an accommodation allocation with payments! ('.$this->id.')');
			}
		}

		if ($this->active) {
			$from = new Carbon($this->from);
			$until = new Carbon($this->until);
			if ($from->gt($until)) {
				throw new \RuntimeException('Can not save an allocation with a timeframe with until before from');
			}
		}

		parent::save($bLog);

		if($this->bSkipUpdatePaymentStack === false) {
			// UAB-Eintrag aktualisieren falls vorhanden
			$this->updatePaymentStack();
		}

		$sCacheKey = 'Ext_Thebing_Allocation::addAllocationData_'.$this->id;
		WDCache::delete($sCacheKey);

		\System::wd()->executeHook('ts_matching_save_allocation', $this);
	}
	
	/**
	 * Lösche alle vorhandenen Einträge im UAB-Stack und generiert sie neu
	 */
	protected function updatePaymentStack() {

		// Reservierung ist nicht relevant
		if($this->inquiry_accommodation_id !== null) {
			\Core\Facade\SequentialProcessing::add('ts/accommodation-provider-payment', $this);
		}

	}

	/**
	 * @param string $sValidUntil
	 * @param int $iAccommodationId
	 * @param int $iRoomId
	 * @return array
	 */
	public static function getInvalidEntries($sValidUntil, $iAccommodationId = 0, $iRoomId = 0) {

		$sWhere	= "";

		if(
			// Beim Löschen von valid_until ist $sValidUntil leer
			!\Core\Helper\DateTime::isDate($sValidUntil, 'Y-m-d') ||
			$sValidUntil === '0000-00-00'
		) {
			return [];
		}

		if(!\Core\Helper\DateTime::isDate($sValidUntil, 'Y-m-d')) {
			throw new InvalidArgumentException('Invalid date given: '.$sValidUntil);
		}

		if($iAccommodationId > 0) {
			$sWhere .= " AND `kr`.`accommodation_id` = :accommodation_id ";
		}
		
		if($iRoomId > 0) {
			$sWhere .= " AND `kr`.`id` = :room_id ";
		}
		
		$sSql = "
			SELECT
				`kaa`.`id`
			FROM
				`kolumbus_accommodations_allocations` `kaa` INNER JOIN
				`kolumbus_rooms` `kr` ON
					`kr`.`id` = `kaa`.`room_id` AND
					`kr`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys_accommodations` `ts_ija` ON
					`ts_ija`.`id` = `kaa`.`inquiry_accommodation_id` AND
					`ts_ija`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`id` = `ts_ija`.`journey_id` AND
					`ts_ij`.`active` = 1 AND
					`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' INNER JOIN
				`ts_inquiries` `ts_i` ON
					`ts_i`.`id` = `ts_ij`.`inquiry_id` AND
					`ts_i`.`active` = 1
			WHERE
				`kaa`.`status` = 0 AND
				`kaa`.`active` = 1 AND
				`kaa`.`active_storno` = 1 AND
				`kaa`.`until` > :valid_date
				{$sWhere}
		";
		
		$aSql = array(
			'accommodation_id' => $iAccommodationId,
			'room_id' => $iRoomId,
			'valid_date' => $sValidUntil,
		);
		
		$aIds = (array)DB::getQueryCol($sSql, $aSql);
		
		return $aIds;
	}
	
	/**
	 * Liefert die Anzahl der Nächte für die Zuweisung
	 *
	 * @return int
	 */
	public function getNights() {
		$iDays = (int)$this->getDays();
		// Am letzten Tag der Zuweisung findet keine Übernachtung mehr statt 
		$iNights = $iDays - 1;
		
		return $iNights;
	}

	/**
	 * Liefert die Anzahl der Unterkunftswochen für die Zuweisung
	 *
	 * @return int
	 */
	public function getWeeks() {

//		$oInquiry = $this->getInquiry();
//		$oSchool = $oInquiry->getSchool();
		$iDays = $this->getDays();

//		$iWeeks = floor($iDays / $oSchool->inclusive_nights);
		$iWeeks = ceil($iDays / 7);

		return $iWeeks;

	}

	/**
	 * Erwartete Kosten (UAB) für diese Zuweisung
	 *
	 * @return float
	 */
	public function getExpectedCostsAmount() {

		$dFrom = new DateTime($this->from);
		$dUntil = new DateTime($this->until);
		$oAllocation = new \Ts\Helper\Accommodation\AllocationCombination($this);
		$oPaymentGenerator = new \Ts\Generator\AccommodationProvider\PaymentGenerator($oAllocation);
		$oPaymentGenerator->initializeCostCategory($dFrom);
		return $oPaymentGenerator->getAmount($dFrom, $dUntil);

	}

	/**
	 * Summe aus erwarteten Kosten (UAB) und Transferkosten
	 *
	 * @return float
	 */
	public function getExpectedCostsAmountWithTransfer() {

		return $this->getExpectedCostsAmount() + $this->getExpectedCostsAmountTransfer();

	}

	/**
	 * Transferkosten dieser Zuweisung wenn Anbieter der Zuweisung = Transfer-Anbieter
	 *
	 * Sollte es mehrere Zuweisungen zum selben Anbieter geben, werden die Kosten verdoppelt.
	 * Transfere haben eigentlich auch nichts mit Zuweisungen zu tun, aber das verstehen Kunden nicht.
	 *
	 * @return float
	 */
	public function getExpectedCostsAmountTransfer() {

		$aTransfers = $this->getInquiryAccommodation()->getJourney()->getTransfersAsObjects(true);
		$oTransferArrival = null;
		$oTransferDeparture = null;
		foreach($aTransfers as $oTransfer) {
			if(
				$oTransfer->provider_type === 'accommodation' &&
				$oTransfer->provider_id == $this->getAccommodationProvider()->id
			) {
				if($oTransfer->transfer_type == Ext_TS_Inquiry_Journey_Transfer::TYPE_ARRIVAL) {
					$oTransferArrival = $oTransfer;
				}
				if($oTransfer->transfer_type == Ext_TS_Inquiry_Journey_Transfer::TYPE_DEPARTURE) {
					$oTransferDeparture = $oTransfer;
				}
			}
		}

		$oPackage = null;
		$iMultiplier = 1;
		if(
			$oTransferArrival !== null &&
			$oTransferDeparture !== null
		) {
			$oPackage = Ext_Thebing_Transfer_Package::searchPackageByTwoWayTransfer($oTransferArrival, $oTransferDeparture);
			$iMultiplier = 2;
		} elseif($oTransferArrival !== null) {
			$oPackage = Ext_Thebing_Transfer_Package::searchPackageByTransfer($oTransferArrival, $oTransferArrival->provider_id);
		} elseif($oTransferDeparture !== null) {
			$oPackage = Ext_Thebing_Transfer_Package::searchPackageByTransfer($oTransferDeparture, $oTransferDeparture->provider_id);
		}

		if($oPackage instanceof Ext_Thebing_Transfer_Package) {
			return $oPackage->amount_cost * $iMultiplier;
		}

		return 0;

	}

	/**
	 * @return Ext_Thebing_Accommodation_Category
	 */
	public function getAccommodationCategory() {
		
		$oProvider = $this->getAccommodationProvider();
		$oInquiryAccommodation = $this->getInquiryAccommodation();
				
		$iAccommodationCategoryId = $oProvider->default_category_id;

		if(
			in_array($oInquiryAccommodation->accommodation_id, (array)$oProvider->accommodation_categories)
		) {
			$iAccommodationCategoryId = $oInquiryAccommodation->accommodation_id;
		}

		$oCategory = Ext_Thebing_Accommodation_Category::getInstance($iAccommodationCategoryId);

		return $oCategory;
	}
	
	/**
	 * Harte Matching-Kriterien vergleichen, ob diese für diese Zuweisung noch erfüllt sind
	 *
	 * @return bool
	 */
	public function compareHardMatchingCriteria() {

		$oInquiry = $this->getInquiry();
		$oSchool = $oInquiry->getSchool();
		$oCustomer = $oInquiry->getCustomer();
		$oMatchingCriteria = $oInquiry->getMatchingData();
		$oProvider = $this->getAccommodationProvider();

		$oCategory = $this->getAccommodationCategory();

		// Bei anderen Unterkünften keine Prüfung machen, da das hier nicht so einfach geht
		if($oCategory->getMatchingType() === 'residential') {
			return true;
		}

		// Closure vergleicht Inquiry-Matching-Option (Select) und Provider-Setting (Checkbox)
		$oCompareCheckboxSetting = function($sMatchingCriteriaField, $sProviderField) use($oMatchingCriteria, $oProvider) {

			// Wenn Select der Buchung auf JA (2) steht, aber Provider Checkbox NICHT AKTIVIERT (0) ist: Rausschmeißen
			if(
				$oMatchingCriteria->$sMatchingCriteriaField == 2 &&
				$oProvider->$sProviderField == 0
			) {
				return false;
			}

			return true;

		};

		// Volljährigkeit, Minderjährigkeit
		if(
			!empty($oCustomer->birthday) &&
			$oCustomer->birthday !== '0000-00-00'
		) {
			// Alter beim Beginn der Zuweisung
			$dFrom = null;
			if(
				!empty($this->from) &&
				$this->from !== '0000-00-00'
			) {
				$dFrom = new DateTime($this->from);
			}

			$iAge = $oCustomer->getAge($dFrom);
			if($iAge >= $oSchool->getGrownAge()) {
				if(!$oProvider->ext_35) {
					// Nimmt keine Erwachsenen auf
					return false;
				}
			} else {
				if(!$oProvider->ext_36) {
					// Nimmt keine Minderjährigen auf
					return false;
				}
			}

		}

		// Geschlecht
		if($oCustomer->gender > 0) {
			if(
				(
					// Anbieter will keine Adamssöhne
					$oCustomer->gender == 1 &&
					$oProvider->ext_37 != 1
				) || (
					// Anbieter will keine Evastöchter
					$oCustomer->gender == 2 &&
					$oProvider->ext_38 != 1
				) || (
					// Anbieter will keine Diverse
					$oCustomer->gender == 3 &&
					$oProvider->diverse != 1
				)
			) {
				return false;
			}
		}

		// Raucher
		if(!$oCompareCheckboxSetting('acc_smoker', 'ext_39')) {
			return false;
		}

		// Vegetarier
		if(!$oCompareCheckboxSetting('acc_vegetarian', 'ext_40')) {
			return false;
		}

		// Muslimische Diät
		if(!$oCompareCheckboxSetting('acc_muslim_diat', 'ext_41')) {
			return false;
		}

		return true;
	}

	/**
	 * Setzt den Flag für die vollständige Generierung von Zahlungen
	 * Überprüft, ob auch wirklich schon alles generiert wurden
	 */
	public function setPaymentCompleted($bNoValidation=false) {
		
		$dLatestPayment = $this->getLatestPaymentDate();
		$dUntil = new DateTime($this->until);

		/*
		 * Nur setzen, wenn das Datum der letzten Zahlung größer oder gleich dem Ende der Zuweisung ist
		 * oder wenn keine Validierung stattfinden soll (z.B. wenn "non_calculate" ausgewählt ist). 
		 */
		if(
			$bNoValidation === true ||
			$dLatestPayment >= $dUntil
		) {

			$this->payment_generation_completed = date('Y-m-d H:i:s');
			$this->bSkipUpdatePaymentStack = true;
			$this->save();
			$this->bSkipUpdatePaymentStack = false;

		} else {
			
			$aMessage = [
				'latest_payment' => $dLatestPayment,
				'allocation_until' => $dUntil,
				'allocation' => $this->aData,
			];			
			Ext_Thebing_Util::reportError('TS - Invalid setPaymentCompleted call', $aMessage);

			$oLog = Log::getLogger();
			$oLog->addError('Invalid setPaymentCompleted call', $aMessage);

		}
		
	}

	/**
	 * Letztes Zahlungsdatum dieser Zuweisung (tatsächlicher Zahlung ODER generierter UAB-Eintrag)
	 *
	 * @param bool $bOnlyExisting Nur tatsächliche Zahlungen (ansonsten auch generierte UAB-Eintrag!)
	 * @return DateTime
	 */
	public function getLatestPaymentDate($bOnlyExisting = false) {
		
		$sSql = "
			SELECT
				`kaa`.`id`,
				`kaa`.`until`,
				MAX(`kap`.`until`) `saved_until`,
				MAX(`ts_app`.`until`) `stack_until`
			FROM
				`kolumbus_accommodations_allocations` `kaa` LEFT JOIN
				`kolumbus_accommodations_payments` `kap` ON
					`kaa`.`id` = `kap`.`allocation_id` AND
					`kap`.`active` = 1 LEFT JOIN
				`ts_accommodation_providers_payments` `ts_app` ON
					`kaa`.`id` = `ts_app`.`accommodation_allocation_id`
			WHERE
				`kaa`.`id` = :allocation_id
			GROUP BY
				`kaa`.`id`				
		";
		
		$aSql = [
			'allocation_id' => (int)$this->id
		];
		$aItem = DB::getQueryRow($sSql, $aSql);

		$dUntilSavedUntil = null;
		$dUntilStackUntil = null;
		if(!empty($aItem['saved_until'])) {
			$dUntilSavedUntil = new DateTime($aItem['saved_until']);
		}
		if(
			$bOnlyExisting === false &&
			!empty($aItem['stack_until'])
		) {
			$dUntilStackUntil = new DateTime($aItem['stack_until']);
		}

		$dUntilMax = max($dUntilSavedUntil, $dUntilStackUntil);

		return $dUntilMax;
	}

	/**
	 * @return DateTime
	 */
	public function getLatestSavedPaymentDate() {
		
		$sSql = "
			SELECT
				`kaa`.`id`,
				`kaa`.`until`,
				MAX(`kap`.`until`) `saved_until`
			FROM
				`kolumbus_accommodations_allocations` `kaa` LEFT JOIN
				`kolumbus_accommodations_payments` `kap` ON
					`kaa`.`id` = `kap`.`allocation_id` AND
					`kap`.`active` = 1
			WHERE
				`kaa`.`id` = :allocation_id
			GROUP BY
				`kaa`.`id`				
		";
		
		$aSql = [
			'allocation_id' => (int)$this->id
		];
		$aItem = DB::getQueryRow($sSql, $aSql);

		$dUntilSavedUntil = null;
		if(!empty($aItem['saved_until'])) {
			$dUntilSavedUntil = new DateTime($aItem['saved_until']);
		}

		return $dUntilSavedUntil;
	}

	/**
	 * Prüfen, ob sich Zuweisungen im Zeitraum dieser Zuweisung (vom selben Raum) irgendwie überschneiden (Zeitraum und Bett)
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function checkAllocationPlausibility() {

		// Inaktive Zuweisungen sind egal
		if(
			$this->room_id == 0 ||
			$this->active == 0 ||
			$this->status != 0
		) {
			return true;
		}

		$oMatching = new Ext_Thebing_Matching();
		$oRoom = $this->getRoom();
		$aAllocationList = $oMatching->getAllocationOfRoom($oRoom->id, new DateTime($this->from), new DateTime($this->until));

		// $aAllocationList sollte zumindest sich selbst beinhalten
		if(empty($aAllocationList)) {
			throw new RuntimeException('$aAllocationList must at least return itself');
		}

		$aBeds = $oMatching->_getBedsInRoom($oRoom->getData(), $aAllocationList);

		// $aAllocationList wurde durch $oMatching->_getBedsInRoom() verändert und wenn was übrig bleibt, wurde eine E-Mail verschickt
		if(!empty($aAllocationList)) {
			return false;
		}

		// Alle Zuweisungen der Betten durchlaufen und prüfen, ob sich auch nur irgendein Eintrag überschneidet
		foreach($aBeds as $aBed) {
			if(empty($aBed['allocation'])) {
				continue;
			}

			foreach($aBed['allocation'] as $aBedAllocation) {
				$dFrom1 = new DateTime($aBedAllocation['from_date']);
				$dUntil1 = new DateTime($aBedAllocation['until_date']);

				foreach($aBed['allocation'] as $aBedAllocation2) {
					if($aBedAllocation['id'] == $aBedAllocation2['id']) {
						continue;
					}

					$dFrom2 = new DateTime($aBedAllocation2['from_date']);
					$dUntil2 = new DateTime($aBedAllocation2['until_date']);

					if(
						// Nicht DateTime::checkDateRangeOverlap() nutzen, da hier das <= nicht sein darf!
						$dFrom1 < $dUntil2 &&
						$dFrom2 < $dUntil1
					) {
						// Entweder gibt es ein Problem in der Datenbank oder die $oMatching->_getBedsInRoom() ist mal wieder kaputt (Zeiträume oder bed = 0)
						return false;
					}
				}
			}
		}

		return true;
	}
	
	public function validate($bThrowExceptions = false) {
		
		$validate = parent::validate($bThrowExceptions);
		
		if($validate === true) {
			
			if(
				$this->room_id > 0 && 
				empty($this->bed)
			) {
				$validate = [];
				$validate[$this->_sTableAlias.'.bed'][] = 'INVALID_INT_POSITIVE';
			}
			
		}
		
		return $validate;
	}

	public function getCustomerAgencyConfirmedStatus(): ConfirmationStatus {

		$date = (!empty($this->customer_agency_confirmed))
			? Carbon::createFromTimestamp($this->customer_agency_confirmed)
			: null;

		return new ConfirmationStatus($this, $date);
	}

	public function getAccommodationConfirmedStatus(): ConfirmationStatus {

		$date = (!empty($this->accommodation_confirmed))
			? Carbon::createFromTimestamp($this->accommodation_confirmed)
			: null;

		return new ConfirmationStatus($this, $date);
	}

	public function getAllocationChangedDate(): ?Carbon {

		if (!empty($this->allocation_changed)) {
			return Carbon::createFromTimestamp($this->allocation_changed);
		}

		return null;
	}

	/**
	 * TODO der Methodenname passt nicht zur Projektstruktur, es gibt JourneyAccommodation aber nicht AccommodationJourney
	 * TODO Außerdem gibt es schon getInquiryAccommodation())
	 */
	public function getAccommodationJourney() {
		return $this->getJoinedObject('journey_accommodation');
	}

	public function getCommunicationDefaultApplication(): string
	{
		return ''; // TODO
	}

	public function getCommunicationLabel(\Tc\Service\LanguageAbstract $l10n): string
	{
		return ''; // TODO
	}

	public function getCommunicationSubObject(): CommunicationSubObject
	{
		return $this->getInquiryAccommodation()->getCommunicationSubObject();
	}

	public function getCommunicationAdditionalRelations(): array
	{
		$journeyAccommodation = $this->getInquiryAccommodation();

		if ($journeyAccommodation) {
			return [
				$journeyAccommodation->getJourney()->getInquiry()
			];
		}

		return [];
	}
}

