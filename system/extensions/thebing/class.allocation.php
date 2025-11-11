<?php
/**
 * @todo: from und to umstellen -> mysql date
 */
class Ext_Thebing_Allocation {

	/**
	 * @var Ext_TS_Inquiry
	 */
	protected $oInquiry;

	/**
	 * @var Ext_TS_Inquiry_Journey_Accommodation
	 */
	protected $oAccommodation;
	
	public $iInquiry;
	protected $iAccommodation;
	protected $iFrom;
	protected $iTo;
	protected $sFrom;
	protected $sTo;
	protected $iRoom;
	protected $iBed;
	protected $_aAllocations = array();

	/**
	 * @TODO Entfernen
	 *
	 * @var array
	 */
	protected static $_aCache = array();

	public function getAccommodation() {
	    return $this->oAccommodation;
    }

	public function setAccommodation($iId){
		$this->iAccommodation = $iId;
		$oAccommodation = new Ext_TS_Inquiry_Journey_Accommodation($iId);
		$this->oAccommodation = $oAccommodation;
		$this->iInquiry = $oAccommodation->inquiry_id;
		$this->setFrom($oAccommodation->from);
		$this->setTo($oAccommodation->until);
		$oInquiry = new Ext_TS_Inquiry($this->iInquiry);
		$this->oInquiry = $oInquiry;
		$this->iInquiry = $oInquiry->id;
	}

	public function setInquiryObject(&$oInquiry){
		
		$this->oInquiry = $oInquiry;
		
		$this->iInquiry = $oInquiry->id;
		
		// Setzte Zeitraum
		$this->setFrom($this->oInquiry->acc_time_from);
		$this->setTo($this->oInquiry->acc_time_to);
	}
	
	public function getBedCount($iRoomId) {

		if(!isset(self::$_aCache['getBedCount'][$iRoomId])) {

			if(!isset(self::$_aCache['getBedCount'])) {

				self::$_aCache['getBedCount'] = WDCache::get(Ext_Thebing_Accommodation_Room::CACHE_KEY_ROOM_BED_COUNT);

				if(self::$_aCache['getBedCount'] === null) {

					$sSql = "
						SELECT 
							* 
						FROM 
							`kolumbus_rooms` 
						WHERE 
							active = 1
						";

					$oRoomCollection = DB::getDefaultConnection()->getCollection($sSql);

					self::$_aCache['getBedCount'] = [];
					foreach($oRoomCollection as $aRoom) {
						$iBeds = $aRoom['single_beds'] + ($aRoom['double_beds']*2);
						self::$_aCache['getBedCount'][$aRoom['id']] = $iBeds;
					}

				}
				
			}

		}

		return self::$_aCache['getBedCount'][$iRoomId];
	}

	/**
	 * If no Double Occupancy it returns False
	 * @todo Das ist ekelhaft was in dieser Klasse passiert. Muss komplett überarbeitet werden, insbesondere im 
	 * Hinblick auf die Verwendung von Unix-Timestamps
	 */
	public function checkForDoubleOccupancy($iRoomId = 0) {
		
		$iInquiryId = $this->oInquiry->id;
		
		// Finde heraus in welchem Zimmer der Kunde liegt und berechne die anzahl der betten
		
		$sSql = "SELECT 
						`kaal`.* ,
						UNIX_TIMESTAMP(`kaal`.`from`) `from`,
						UNIX_TIMESTAMP(`kaal`.`until`) `until`,
						UNIX_TIMESTAMP(`kaal`.`until`) `to`,
						`ts_i_j`.`inquiry_id` `inquiry_id`
				FROM
					`kolumbus_accommodations_allocations` `kaal` INNER JOIN
					`ts_inquiries_journeys_accommodations` `kia` ON
						`kaal`.`inquiry_accommodation_id` = `kia`.`id` INNER JOIN
					`ts_inquiries_journeys` `ts_i_j` ON
						`ts_i_j`.`id` = `kia`.`journey_id` AND
						`ts_i_j`.`active` = 1 AND
						`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."'
				WHERE 
					`ts_i_j`.`inquiry_id` = :inquiry_id AND
					`kaal`.`from` = :from AND
					`kaal`.`until` = :until AND
					`kaal`.`active` = 1 AND
					`kaal`.`status` = 0
				";
		$aSql = array(
			'from'=> $this->sFrom,
			'until'=> $this->sTo,
			'inquiry_id'=>(int)$iInquiryId
		);

		$aResult = DB::getQueryRow($sSql,$aSql);
		
		if(count($aResult) <= 0) {
			return false;
		}

		if($iRoomId == 0) {
			$iRoomId = $aResult['room_id'];
		}

		$iBeds = $this->getBedCount($iRoomId);

		$sSql = "
					SELECT
						`kaal`.* ,
						UNIX_TIMESTAMP(`kaal`.`from`) `from`,
						UNIX_TIMESTAMP(`kaal`.`until`) `until`,
						UNIX_TIMESTAMP(`kaal`.`until`) `to`,
						`ts_i_j`.`inquiry_id` `inquiry_id`
					FROM
						`kolumbus_accommodations_allocations` `kaal` INNER JOIN
						`ts_inquiries_journeys_accommodations` `kia` ON
							`kia`.`id` = `kaal`.`inquiry_accommodation_id` AND
							`kia`.`active` = 1 INNER JOIN
						`ts_inquiries_journeys` `ts_i_j` ON
							`ts_i_j`.`id` = `kia`.`journey_id` AND
							`ts_i_j`.`active` = 1 AND
							`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."'
					WHERE					
						`kaal`.`room_id` = :room_id AND
						(
							(
								`kaal`.`from` <= :from AND
								`kaal`.`until` >= :until
							)
						)
						AND
						`ts_i_j`.`inquiry_id` != :inquiry_id AND
						`kaal`.`active` = 1 AND
						`kaal`.`status` = 0
				";
		$aSql = array(
			'room_id' => (int)$iRoomId,
			'inquiry_id' => (int)$iInquiryId,
			'until'=> $this->sTo,
			'from' => $this->sFrom
		);

		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		$iOtherAllocations = count($aResult);
		
		$iFree = $iBeds - $iOtherAllocations;

		unset($aResult);

		if($iFree <= 0) {
			return true;
		} else {
			return false;
		}

	}
	
	public function setFamilie($idFamilie){
		// Liste alle passenden Familien/Räume auf
		$oMatch = new Ext_Thebing_Matching($this->iInquiry);
		$oMatch->iFrom = $this->oInquiry->acc_time_from;
		$oMatch->iTo = $this->oInquiry->acc_time_to;
		// Liste familien auf
		$oInquiry = Ext_TS_Inquiry::getInstance($this->iInquiry);
		$aFamilies = $oMatch->getMatchedFamilie($oInquiry);
		
		foreach($aFamilies as $aFamilie){
			// wähle den ersten Raum der gesetzten Familie
			if($aFamilie['id'] == $idFamilie) {
				foreach($aFamilie['rooms'] as $aRoom){
					$iFree = 1;
					foreach((array)$aRoom['allocation'] as $aAllo){

						if(
							
							($oMatch->iFrom >= $aAllo['from'] && $oMatch->iFrom <= $aAllo['to']) || 
							($oMatch->iTo >= $aAllo['from'] && $oMatch->iTo <= $aAllo['to']) || 
							($oMatch->iFrom <= $aAllo['from'] && $oMatch->iTo >= $aAllo['to']) 							 
							 ){							
							$iFree = 0;
						}
					}
					if($aAllo == 0 || $iFree == 1){
						$this -> setRoom($aRoom['id']);
						break;
					}
				}
			}
		}	
	}
	
	public function setOther($idFamilie){
		// Liste alle passenden Familien/Räume auf
		$oMatch = new Ext_Thebing_Matching($this->iInquiry);
		$oMatch->iFrom = $this->oInquiry->course_time_from;
		$oMatch->iTo = $this->oInquiry->course_time_to;
		// Liste familien auf
		$aFamilies = $oMatch->getOtherMatched($this->oInquiry);
		foreach($aFamilies as $aFamilie){
			// wähle den ersten Raum der gesetzten Familie
			
			if($aFamilie['id'] == $idFamilie){
				
				foreach($aFamilie['rooms'] as $aRoom){
					$iFree = 1;
					$bFree = $this->checkForDoubleOccupancy($aRoom['id']);
					
					if($bFree == true){
						continue;
					}else{
						
						$this->setRoom($aRoom['id']);
						break;
					}
				}
			}
		}	
	}
	
	public function setRoom($iRoom) {
		$this->iRoom = $iRoom;
	}

    /**
     * @return Ext_Thebing_Accommodation_Room|null
     * @throws Exception
     */
    public function getRoom() {
        if(!is_null($this->iRoom)) {
            return Ext_Thebing_Accommodation_Room::getInstance($this->iRoom);
        }
        return null;
    }

	public function setBed($iBedNumber) {
		$this->iBed = $iBedNumber;
	}
	
	public static function getAllocationById($iAllocation) {
		
		$bBack = false;
		
		$sSql = " 
					SELECT
						`kaal`.*,
						UNIX_TIMESTAMP(`kaal`.`from`) `from`,
						UNIX_TIMESTAMP(`kaal`.`until`) `until`,
						UNIX_TIMESTAMP(`kaal`.`until`) `to`,
						`ts_i_j`.`inquiry_id` `inquiry_id`
					FROM
						`kolumbus_accommodations_allocations` `kaal` INNER JOIN
						`ts_inquiries_journeys_accommodations` `kia` ON
							`kia`.`id` = `kaal`.`inquiry_accommodation_id` AND
							`kia`.`active` = 1 INNER JOIN
						`ts_inquiries_journeys` `ts_i_j` ON
							`ts_i_j`.`id` = `kia`.`journey_id` AND
							`ts_i_j`.`active` = 1 AND
							`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."'
					WHERE
						`kaal`.id = :id AND
						`kaal`.`status` = 0 
					";
		$aSql = array('id'=>(int)$iAllocation);
		$aAllocation = DB::getQueryRow($sSql,$aSql);

		$oInquiryAccommodation = Ext_TS_Inquiry_Journey_Accommodation::getInstance($aAllocation['inquiry_accommodation_id']);
	}
	
	
	public static function checkAllocation($iAllocation, $iNewRoomId, $bOtherMatching, $sType = '') {
		
		$bBack = true;
	
		$oAllocation = Ext_Thebing_Accommodation_Allocation::getInstance($iAllocation);
		

		$oInquiryAccommodation = $oAllocation->getInquiryAccommodation();

		$oFrom = new WDDate($oAllocation->from, WDDate::DB_TIMESTAMP);
		$oFrom->set('00:00:00', WDDate::DB_TIME);
		$oUntil = new WDDate($oAllocation->until, WDDate::DB_TIMESTAMP);
		$oUntil->set('00:00:00', WDDate::DB_TIME);

		
		$oMatch = new Ext_Thebing_Matching();
		$oMatch->oAccommodation	= $oInquiryAccommodation;
		$oMatch->iFrom				= $oFrom->get(WDDate::TIMESTAMP);
		$oMatch->iTo				= $oUntil->get(WDDate::TIMESTAMP);
		$oMatch->sFrom				= $oFrom->get(WDDate::DB_DATE);
		$oMatch->sTo				= $oUntil->get(WDDate::DB_DATE);
		$oMatch->bSkipAllocationCheck = true;
		
		$oInquiry = $oInquiryAccommodation->getInquiry();
		
		// Wenn der Schüler  mit anderen zusammen reist, so müssen auch diese gecheckt werden!
		$aRoomSharingInquiries = $oInquiry->getRoomSharingInquiries();
		
		// Alle Buchungen die gecheckt werden müssen. Normalfall nur eine, bei Zusammenreisenden alle die zusammenreisen
		$aInquiries = array($oInquiry);

		$aInquiries = array_merge($aInquiries, $aRoomSharingInquiries);

		
		// Jeden Zusammenreisenen prüfen ob Raum geignet ist
		foreach((array)$aInquiries as $iKey => $oTempInquiry){
		
			// Eigene Buchung ignorieren, da man mit sich selber immer in einem Bett schlafen darf
			// Da man nur eine Unterkunft gleichzeitig verschieben kann
			// Edit: Das ist schwachsinn, da sonst die ganze Funktion keinen Sinn hat wenn NIE geprüft wird noch nicht mal bei sich selber
			if(
				$sType == 'move' &&
				$oTempInquiry->id == $oInquiry->id
			){
				#continue;
			}
		
			if($bOtherMatching) {
				$aEntries = $oMatch->getOtherMatched($oTempInquiry, 1, false, $iNewRoomId);
			} else {
				$aEntries = $oMatch->getMatchedFamilie($oTempInquiry, 1, $iNewRoomId);
			}

			$aRoom = (array)$aEntries[0]['rooms'][0];
			
			if(
				$aRoom['id'] == $iNewRoomId && 
				$aRoom['isAssignable'] != 0
			) {

				if($iKey == 0){
					// Prüfen ob Bettenanzahl passen würde
					// nur relevant bei room sharing ansonst wird eh schon geprüft muss nur einmal geprüft werden
					$iBedCount = (int)$aRoom['single_beds'] + 2 * (int)$aRoom['double_beds'];
					$iFreeBeds = $iBedCount - (int)$aRoom['all_allocations'];

					if($iFreeBeds < count($aInquiries)){
						// Betten reichen nicht 
						$bBack = false;
						break;
					}
				}

			}else{
				// passt nicht
				$bBack = false;
				break;         
			}                  
                               
		}                          
		return $bBack;         
                               
	}  

	// For Part Saving
	public function setFrom($mFrom){
		
		if($mFrom instanceof DateTime){
			$mFrom->setTime(0,0,0);
			$this->sFrom = $mFrom->format('Y-m-d H:i:s');
			$this->iFrom = $mFrom->getTimestamp();
		} else if(is_numeric($mFrom)) {
			$this->iFrom = $mFrom;
			$this->sFrom = Ext_Thebing_Util::formatGMT($mFrom);
		} elseif(WDDate::isDate($mFrom, WDDate::DB_DATE)) {
			$oFrom = new WDDate($mFrom, WDDate::DB_DATE);
			$this->iFrom = gmmktime(0, 0, 0, $oFrom->get(WDDate::MONTH), $oFrom->get(WDDate::DAY), $oFrom->get(WDDate::YEAR));
			$this->sFrom = $oFrom->get(WDDate::DB_DATETIME);
		}

	}

	public function getFrom(): ?DateTime {
        return (!is_null($this->iFrom))
            ? (new DateTime())->setTimestamp($this->iFrom)
            : null;
    }

	public function setTo($mTo){

		if($mTo instanceof DateTime){
			$mTo->setTime(0,0,0);
			$this->sTo = $mTo->format('Y-m-d H:i:s');
			$this->iTo = $mTo->getTimestamp();
		} else if(is_numeric($mTo)) {
			$this->iTo = $mTo;
			$this->sTo = Ext_Thebing_Util::formatGMT($mTo);
		} elseif(WDDate::isDate($mTo, WDDate::DB_DATE)) {
			$oFrom = new WDDate($mTo, WDDate::DB_DATE);
			$this->iTo = gmmktime(0, 0, 0, $oFrom->get(WDDate::MONTH), $oFrom->get(WDDate::DAY), $oFrom->get(WDDate::YEAR));
			$this->sTo = $oFrom->get(WDDate::DB_DATETIME);
		}

	}

    public function getUntil(): ?DateTime {
        return (!is_null($this->iTo))
            ? (new DateTime())->setTimestamp($this->iTo)
            : null;
    }

	public function save() {

		$oInquiry = $this->oInquiry;

		if($this->iRoom > 0) {

			$oMatching = new Ext_Thebing_Matching();
			$iFreeBeds = $oMatching->getFreeBedsOfRoom($this->iRoom, new DateTime($this->sFrom), new DateTime($this->sTo), 0, true, $oInquiry->id);

			if($iFreeBeds > 0) {

				$oJourneyAccommodation = Ext_TS_Inquiry_Journey_Accommodation::getInstance($this->iAccommodation);
				$aAllocations = $oJourneyAccommodation->getAllocations(true, false);
				$aAllocations = self::sortAllocationsByDate($aAllocations);
				$aAllocationDates = Ext_TC_Util::getDateTimeTuples($aAllocations);

				$oInactiveAllocation = null;
				$oNewAllocationFrom = new DateTime($this->sFrom);
				$oNewAllocationUntil = new DateTime($this->sTo);
				$bNewAllocation = false;
				$iBed = $this->iBed;

				/*
				 * Nach inaktiver Zuweisung suchen, welche in den Zeitraum passt
				 * Wenn hier eine inaktive Zuweisung gefunden wird, wird diese Zuweisung gerade zum Zuweisen benutzt
				 * Hier wird aus irgendeinem Grund keine Id aus dem JavaScript übergeben, sonst dürfte das hier unnötig sein
				 */
				foreach($aAllocations as $oAllocation) {
					if(
						$oAllocation->room_id == 0 &&
						$oAllocation->from == $this->sFrom &&
						$oAllocation->until == $this->sTo &&
						$oAllocation->status == 0
					) {
						$oInactiveAllocation = $oAllocation;

						// Raum schon mal setzen für das mögliche Zusammenfügen von Zuweisungen
						$oInactiveAllocation->room_id = $this->iRoom;
						$oInactiveAllocation->bed = $this->iBed;

						break;
					}
				}

				/*
				 * Nach Zuweisungen suchen, welche zusammengefügt werden können
				 * Es werden von der frühsten bis zur spätestens Zuweisung alle Zuweisungen durchgelaufen.
				 * Daher ist das Sortieren nach Startdatum auch wichtig!
				 *
				 * Verschmolzen wird übrigens auch in Ext_Thebing_Accommodation_Allocation::moveToRoom().
				 */
				$mergeAllocations = false;
				if(count($aAllocations) > 1) {
					$mergeAllocations = true;
					foreach($aAllocations as $iIndex => $oAllocation) {

						if(!isset($aAllocations[$iIndex + 1])) {
							// Wenn es keine nachfolgende Zuweisung gibt, kann auch nichts mehr zusammengefügt werden
							break;
						}

						$oNextAllocation = $aAllocations[$iIndex + 1];

						// Zuweisung, für die schon ein UAB-Eintrag generiert wurde, darf nicht mehr verändert werden! #8615
						$dSavedPaymentDate = $oAllocation->getLatestSavedPaymentDate();
						$dSavedPaymentDate2 = $oNextAllocation->getLatestSavedPaymentDate();
						if(
							$dSavedPaymentDate !== null ||
							$dSavedPaymentDate2 !== null
						) {
							$mergeAllocations = false;
							break;
						}

						// Wenn nächste Zuweisung direkt folgt und der Raum derselbe ist, dann Zuweisungen zusammenfügen
						if(
							$oAllocation->until == $oNextAllocation->from &&
							$oAllocation->room_id == $oNextAllocation->room_id &&
							$oAllocation->bed == $oNextAllocation->bed &&
							$oAllocation->room_id != 0 &&
							$oNextAllocation->room_id != 0
						) {

						} else {
							$mergeAllocations = false;
							break;
						}
					}
				}

				if($mergeAllocations) {
				
					foreach($aAllocations as $iIndex => $oAllocation) {

						if(!isset($aAllocations[$iIndex + 1])) {
							// Wenn es keine nachfolgende Zuweisung gibt, kann auch nichts mehr zusammengefügt werden
							break;
						}

						$oNextAllocation = $aAllocations[$iIndex + 1];

						// Zeitraum erweitern und alte Zuweisungen löschen
						$oNewAllocationFrom = min($oNewAllocationFrom, $aAllocationDates[$oAllocation->id][0]);
						$oNewAllocationUntil = max($oNewAllocationUntil, $aAllocationDates[$oNextAllocation->id][1]);
						$oAllocation->remove();
						$oNextAllocation->remove();
						$bNewAllocation = true;

						/*
						 * Das System bietet den Zuweisen-Balken nur immer im ersten Bett der Unterkunft an.
						 * Wenn schon jemand im ersten Bett liegt, die eigentlich hier passende Zuweisung aber
						 * im zweiten Bett, darf nicht $this->iBed benutzt werden, da die Zuweisung aus Bett 2
						 * dann in Bett 1 landen würde. Prinzipiell müsste dann auch ein fataler Fehler kommen. #10563
						 * DAS IST NICHT MEHR DER FALL, DA EIN KONKRETES BETT ZUGEWIESEN WIRD
						 */
						$iBed = $oAllocation->bed;
						if($iBed == 0) {
							// Wenn $iBed == 0, ist die inaktive Zuweisung vor der existierenden Zuweisung
							$iBed = $oNextAllocation->bed;
						}

					}
				}

				if($bNewAllocation) {
					// Wenn erweitert wurde, keinesfalls die inaktive erweitern, sondern neue Zuweisung anlegen
					$oSaveAllocation = new Ext_Thebing_Accommodation_Allocation();

					if($oInactiveAllocation instanceof Ext_Thebing_Accommodation_Allocation) {
						// Wenn inaktive Zuweisung gefunden, dann diese löschen, da diese nun überdeckt wird
						$oInactiveAllocation->remove();
					}
				} else {
					if($oInactiveAllocation instanceof Ext_Thebing_Accommodation_Allocation) {
						// Wenn inaktive Zuweisung gefunden, diese benutzen
						$oSaveAllocation = $oInactiveAllocation;
					} else {
						// Bei keiner gefundener Zuweisung neue Zuweisung anlegen
						$oSaveAllocation = new Ext_Thebing_Accommodation_Allocation();
					}
				}

				$oSaveAllocation->room_id = (int)$this->iRoom;
				$oSaveAllocation->bed = $iBed;
				$oSaveAllocation->from = $oNewAllocationFrom->format('Y-m-d H:i:s');
				$oSaveAllocation->until = $oNewAllocationUntil->format('Y-m-d H:i:s');
				$oSaveAllocation->share_with = $this->share_with;
				$oSaveAllocation->inquiry_accommodation_id = (int)$this->iAccommodation;
				$oSaveAllocation->save();

				// Durch das System gelöschte Zuweisungen wieder resetten, da er nun manuell neu zugewiesen wurde
				$oJourneyAccommodation->resetSystemDeletedAllocations();

			} else {

				return false;
			}

			return true;
		}

		return false;
	}

	/**
	 * @todo: $sFrom/$sTo refaktorisieren (nur noch DateTime)
	 *
	 * @param string|int|\DateTime $sFrom
	 * @param string|int|\DateTime $sTo
	 * @param int $iAccommodationId
	 * @return bool
	 */
	public function saveInactiveAllocation($sFrom, $sTo, $iAccommodationId = 0){

		if($iAccommodationId <= 0) {
			$iAccommodationId = $this->iAccommodation;
		}

		if(
			(
				$sFrom instanceof DateTime &&
				!$sTo instanceof DateTime
			) || (
				is_numeric($sFrom) &&
				!is_numeric($sTo)
			) || (
				is_string($sFrom) &&
				!is_string($sTo)
			)
		) {
			// Wegen der Zeitzonen-Problematik dürfen hier keine gemischten Typen reinkommen
			throw new InvalidArgumentException('It is not possible to mix $sFrom ('.gettype($sFrom).') and $sTo ('.gettype($sTo).') types in saveInactiveAllocation()!');
		}

		if(
			$sFrom instanceof DateTime &&
			$sTo instanceof DateTime
		) {
			$sFrom = $sFrom->format('Y-m-d H:i:s');
			$sTo = $sTo->format('Y-m-d H:i:s');
		} else {

			if(
				is_numeric($sFrom) &&
				is_numeric($sTo)
			) {

				if($sFrom > $sTo){
					return false;
				}

				$sFrom = Ext_Thebing_Util::convertUTCDate($sFrom);
				$sFrom = Ext_Thebing_Util::formatGMT($sFrom);

				$sTo = Ext_Thebing_Util::convertUTCDate($sTo);
				$sTo = Ext_Thebing_Util::formatGMT($sTo);
			}

			// Date Format in DB_DATETIME umwandeln.... (leider speichert unser system das so)
			if(WDDate::isDate($sFrom, WDDate::DB_DATE)){
				$oDate = new WDDate($sFrom, WDDate::DB_DATE);
				$sFrom = $oDate->get(WDDate::DB_DATETIME);
			}

			if(WDDate::isDate($sTo, WDDate::DB_DATE)){
				$oDate = new WDDate($sTo, WDDate::DB_DATE);
				$sTo = $oDate->get(WDDate::DB_DATETIME);
			}
			
		}
		
		if(
			WDDate::isDate($sFrom, WDDate::DB_DATETIME) &&
			WDDate::isDate($sTo, WDDate::DB_DATETIME)
		){

			$sSql = "
				SELECT
					`id`
				FROM
					`kolumbus_accommodations_allocations`
				WHERE
					`room_id` = 0 AND
					`from` = :from AND
					`until` = :until AND
					`inquiry_accommodation_id` = :accommodation_id AND
					`active` = 1 AND
					`status` = 0
			";

			$aResult = DB::getQueryCol($sSql, array(
				'from' => $sFrom,
				'until' => $sTo,
				'accommodation_id' => $iAccommodationId
			));

			// Nur anlegen, wenn es den Eintrag nicht schon gibt
			// Das kann unter gewissen Umständen in Kombination mit Ext_Thebing_Accommodation_Allocation::delete() vorkommen
			if(empty($aResult)) {
				$oAllocation = new Ext_Thebing_Accommodation_Allocation();
				$oAllocation->room_id = 0;
				$oAllocation->from = $sFrom;
				$oAllocation->until = $sTo;
				$oAllocation->inquiry_accommodation_id = (int)$iAccommodationId;
				$oAllocation->save();
			}

		}else{
			return false;
		}
		
		
	}
	
	public function deleteAllAllocations($bSystem = false) {
		
		$sSql = "SELECT `id` FROM
							`kolumbus_accommodations_allocations`
						WHERE
							`inquiry_accommodation_id` = :accommodation_id AND
							`active` = 1 AND
							`status` = 0
						";
		$aSql = array();
		$aSql['accommodation_id'] = (int)$this->oAccommodation->id;
				
		$aResult = DB::getPreparedQueryData($sSql,$aSql);
		foreach((array)$aResult as $aData){
			$oAllocation = new Ext_Thebing_Accommodation_Allocation($aData['id']);
			$oAllocation->delete(true, $bSystem);
		}
		
	}
	
	public function updateTime($iId, $iFrom, $iTo, $iAccommodation){

		if(is_numeric($sFrom)){
			$sFrom = Ext_Thebing_Util::convertUTCDate($sFrom);
			$sFrom = Ext_Thebing_Util::formatGMT($sFrom);
		}

		if(is_numeric($sTo)){
			$sTo = Ext_Thebing_Util::convertUTCDate($sTo);
			$sTo = Ext_Thebing_Util::formatGMT($sTo);
		}

		$oAllocation = new Ext_Thebing_Accommodation_Allocation($iId);
		$oAllocation->updateTime($iFrom, $iTo, $iAccommodation);
	
	}
	
	public static function updateAllocationTime($iId, $iFrom, $iTo, $iAccommodation){
		
		$oAllocation = new Ext_Thebing_Accommodation_Allocation($iId);
		$oAllocation->updateTime($iFrom, $iTo, $iAccommodation);
		
	}

	/**
	 * @param int $iInquiry
	 * @param int $iAccommodation
	 * @param bool $bAll
	 * @param bool $bWithInactive
	 * @param bool $bReturnObjects
	 * @param bool $bWithDeleted Achtung, auch active = 0
	 * @return int[]|Ext_Thebing_Accommodation_Allocation[]
	 */
	public static function getAllocationByInquiryId($iInquiry, $iAccommodation = 0, $bAll = false, $bWithInactive = false, $bReturnObjects=false, $bWithDeleted=false) {

		// Neue Inquiry hat noch keine Zuweisungen
		if($iInquiry < 1){
			return array();
		}

		$aArguments = func_get_args();
		$sKey = 'KEY_'.implode('-', $aArguments);

		if(!isset(self::$_aCache['allocation_by_inquiry_id'][$sKey])) {

			$aSql = array();
			$aSql['inquiry_id'] = (int) $iInquiry;

			$sAddon = "";
			if($iAccommodation > 0){
				$sAddon = " AND `kaal`.`inquiry_accommodation_id` = :accommodation_id ";
				$aSql['accommodation_id'] = (int)$iAccommodation;
			}

			if($bWithInactive == false){
				$sAddon .= " AND `kaal`.`room_id` > 0";
			}

			if(!$bWithDeleted) {
				$sAddon .= " AND `kaal`.`active` = 1 ";
				$sAddon .= " AND `kaal`.`status` = 0 ";
			}

			$sSql = "
					SELECT
						`kaal`.*,
						`kaal`.`from` `date_from`,
						`kaal`.`until` `date_until`,
						UNIX_TIMESTAMP(`kaal`.`from`) `from`,
						UNIX_TIMESTAMP(`kaal`.`until`) `until`,
						UNIX_TIMESTAMP(`kaal`.`until`) `to`,
						`ts_i_j`.`inquiry_id` `inquiry_id`,
						`kr`.`accommodation_id` AS `family_id`
					FROM
						`kolumbus_accommodations_allocations` AS `kaal` INNER JOIN
						`ts_inquiries_journeys_accommodations` `ts_ija` ON
							`ts_ija`.`id` = `kaal`.`inquiry_accommodation_id` AND
							`ts_ija`.`active` = 1 INNER JOIN
						`ts_inquiries_journeys` `ts_i_j` ON
							`ts_i_j`.`id` = `ts_ija`.`journey_id` AND
							`ts_i_j`.`active` = 1 AND
							`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
							`ts_i_j`.`inquiry_id` = :inquiry_id LEFT JOIN
						`kolumbus_rooms` AS `kr` ON
							`kr`.`id` = `kaal`.`room_id`
					WHERE
						1 = 1
						".$sAddon."
					ORDER BY
						`kaal`.`from`
					";

			$aResult = DB::getPreparedQueryData($sSql, $aSql);

			self::$_aCache['allocation_by_inquiry_id'][$sKey] = (array)$aResult;

		}

		$aResult = self::$_aCache['allocation_by_inquiry_id'][$sKey];

		if($bReturnObjects) {
			$aResult = array_map(function($aAllocation) {
				return Ext_Thebing_Accommodation_Allocation::getInstance($aAllocation['id']);
			}, $aResult);
		}

		if($bAll == false){
			return $aResult[0] ?? null;
		}

		return $aResult;

	}

	public function getFromInquiryId($iInquiry,$iAccommodation = 0,$bAll = 0){
				
		$aResult = self::getAllocationByInquiryId( $iInquiry, $iAccommodation, $bAll);
		if($bAll == 0){
			return $aResult[0];
		}
		return $aResult;
	}
	
	/**
	 * 
	 * @param $iInquiry
	 * @param $iAccommodation
	 * @return Array first Allo
	 */
	public static function checkForAllo($iInquiry,$iAccommodation = 0){

		$aResult = self::getAllocationByInquiryId($iInquiry,$iAccommodation);
		return $aResult;
	}

	/**
	 * Sortiert Zuweisungen nach Startdatum
	 * @param Ext_Thebing_Accommodation_Allocation[] $aAllocations
	 * @return Ext_Thebing_Accommodation_Allocation[]
	 */
	public static function sortAllocationsByDate(array $aAllocations) {

		$aAllocationDates = Ext_TC_Util::getDateTimeTuples($aAllocations);

		// Zuweisungen sortieren nach Startdatum
		usort($aAllocations, function($oAllocation1, $oAllocation2) use($aAllocationDates) {
			$oFrom1 = $aAllocationDates[$oAllocation1->id][0];
			$oFrom2 = $aAllocationDates[$oAllocation2->id][0];

			if($oFrom1 == $oFrom2) {
				return 0;
			}

			return $oFrom1 > $oFrom2 ? 1 : -1;
		});

		return $aAllocations;
	}

	public static function resetStaticCache() {
		self::$_aCache = [];
	}
	
}
