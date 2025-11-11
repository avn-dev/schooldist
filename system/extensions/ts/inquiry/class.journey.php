<?php

/**
 * @property $id
 * @property $changed
 * @property $created
 * @property $active
 * @property $creator_id
 * @property $editor_id
 * @property $inquiry_id
 * @property $productline_id
 * @property $school_id
 * @property string $type
 * @property int|string $transfer_mode BIT-ARRAY
 * @property string $transfer_comment DefinedAttribut
 */
class Ext_TS_Inquiry_Journey extends Ext_Thebing_Basic {

	/**
	 * Jede Anfrage hat initial einen Journey mit Dummy-Typ, damit die Schulverknüpfung vorhanden ist
	 */
	const TYPE_DUMMY = 0;

	/**
	 * Journeys vom Typ Anfragen-Kombination
	 */
	const TYPE_REQUEST = 1;

	/**
	 * Genereller Journey einer Buchung
	 */
	const TYPE_BOOKING = 2;

	const TRANSFER_MODE_NONE = 0;

	const TRANSFER_MODE_ARRIVAL = 1;

	const TRANSFER_MODE_DEPARTURE = 2;

	const TRANSFER_MODE_BOTH = self::TRANSFER_MODE_ARRIVAL | self::TRANSFER_MODE_DEPARTURE;

	protected $_sTable = 'ts_inquiries_journeys';

	protected $_sEditorIdColumn = 'editor_id';

	/**
	 * @var <string> 
	 */
	protected $_sTableAlias = 'ts_ij';
	
	protected $_aFormat = array( 
		'productline_id' => array(
			'validate' => 'INT_POSITIVE',
			'required'	=> true,
		),
		'school_id' => array(
			'validate' => 'INT_POSITIVE',
			'required'	=> true,
		)
	);

	protected $_aJoinedObjects = array(
		'visa'			=> array(
			'class'				=> 'Ext_TS_Inquiry_Journey_Visa',
			'key'				=> 'journey_id',
			'type'				=> 'child',
			'cloneable' => true
		),
		'traveller_detail'		=> array(
			'class'				=> 'Ext_TS_Inquiry_Journey_Traveller',//@todo: umbennen in Ext_TS_Inquiry_Journey_Traveller_Detail
			'key'				=> 'journey_id',
			'type'				=> 'child',
			'cloneable' => true
		),
		'inquiry'               => array(
			'class'				=> 'Ext_TS_Inquiry',
			'key'				=> 'inquiry_id',
            'bidirectional'     => true
		),
		'courses'			=> array(
			'class'				=> 'Ext_TS_Inquiry_Journey_Course',
			'key'				=> 'journey_id',
			'type'				=> 'child',
            'check_active'      => true,
            'bidirectional'     => true,
            'orderby'           => array('from', 'until'),
            'orderby_set'       => false,
			'on_delete' => 'cascade',
			'cloneable' => false
		),
		'accommodations'		=> array(
			'class'				=> 'Ext_TS_Inquiry_Journey_Accommodation',
			'key'				=> 'journey_id',
			'type'				=> 'child',
            'check_active'      => true,
            'bidirectional'     => true,
            'orderby'           => 'from',
			'orderby_type'      => 'ASC',
            'orderby_set'       => false,
			'on_delete' => 'cascade',
			'cloneable' => false
		),
        // Achtung! Transfer NICHT SORTIEREN!
		'transfers'		=> array(
			'class'				=> 'Ext_TS_Inquiry_Journey_Transfer',
			'key'				=> 'journey_id',
			'type'				=> 'child',
            'check_active'      => true,
            'bidirectional'     => true,
			'on_delete' => 'cascade',
			'cloneable' => false
		),
		// Aus Enquiry-Combinations übernommen, für Combination-Dialog mit Containern
		'transfers_arrival' => [
			'class' => 'Ext_TS_Inquiry_Journey_Transfer',
			'key' => 'journey_id',
			'type' => 'child',
			'static_key_fields' => ['transfer_type' => Ext_TS_Inquiry_Journey_Transfer::TYPE_ARRIVAL, 'active' => 1],
			'check_active' => true,
			'bidirectional' => true
		],
		// Aus Enquiry-Combinations übernommen, für Combination-Dialog mit Containern
		'transfers_departure' => [
			'class' => 'Ext_TS_Inquiry_Journey_Transfer',
			'key' => 'journey_id',
			'type' => 'child',
			'static_key_fields' => ['transfer_type' => Ext_TS_Inquiry_Journey_Transfer::TYPE_DEPARTURE, 'active' => 1],
			'check_active' => true,
			'bidirectional' => true
		],
		// Aus Enquiry-Combinations übernommen, für Combination-Dialog mit Containern
		'transfers_additional' => [
			'class' => 'Ext_TS_Inquiry_Journey_Transfer',
			'key' => 'journey_id',
			'type' => 'child',
			'static_key_fields' => ['transfer_type' => Ext_TS_Inquiry_Journey_Transfer::TYPE_ADDITIONAL, 'active' => 1],
			'check_active' => true,
			'bidirectional' => true
		],
		'insurances'		=> array(
			'class'				=> 'Ext_TS_Inquiry_Journey_Insurance',
			'key'				=> 'journey_id',
			'type'				=> 'child',
            'check_active'      => true,
            'bidirectional'     => true,
			'on_delete' => 'cascade',
			'cloneable' => false
		),
		'activities' => [
			'class' => 'Ext_TS_Inquiry_Journey_Activity',
			'key' => 'journey_id',
			'type' => 'child',
			'check_active' => true,
			'bidirectional' => true,
			'on_delete' => 'cascade',
			'cloneable' => false
		],
		'additionalservices' => [
			'class' => Ext_TS_Inquiry_Journey_Additionalservice::class,
			'key' => 'journey_id',
			'type' => 'child',
			'check_active' => true,
			'bidirectional' => true,
			'on_delete' => 'cascade',
			'cloneable' => false
		],
		'feedback_processes' => [ // Purge
			'class'	=> 'Ext_TC_Marketing_Feedback_Questionary_Process',
			'key' => 'journey_id',
			'type' => 'child',
			'check_active' => true,
			'on_delete' => 'cascade',
			'cloneable' => false
		]
	);

	protected $_aAttributes = [
		'transfer_comment' => ['type' => 'text'],
	];

	protected $_oVisa = null;
	  
    protected $_aCourseCache = array();
	
	/**
	 * get the school object
	 * @return Ext_Thebing_School
	 */
	public function getSchool() {
		$oSchool = Ext_Thebing_School::getInstance($this->school_id);
		return $oSchool;
	}
	
	/**
	 * Visa Object dieser Journey
	 *
	 * @TODO Das ist nicht korrekt. Visa sind Travellern der Journey zugewiesen,
	 * 	hier wird aber nur das erstbeste Visum geholt! Siehe Ext_TS_Inquiry_Journey_Visa::searchData()
	 *
	 * @return Ext_TS_Inquiry_Journey_Visa 
	 */
	public function getVisa() {

		// Da diese Such-Methode nicht objekt-relational ist (d.h. ohne IDs macht jeder Aufruf eine neue Instanz),
		//   bleibt nur, die einmal erzeugte/gefundene Instanz nochmal hier vorzuhalten.
		if ($this->_oVisa !== null) {
			return $this->_oVisa;
		}

		// Direkt die andere Methode nutzen, da diese hier immer den Kontakt ignoriert hat
		$oContact = $this->getInquiry()->getCustomer();
		$this->_oVisa = \Ext_TS_Inquiry_Journey_Visa::searchData($this, $oContact);

		return $this->_oVisa;

//		if($this->_oVisa === null) {
//			$aVisa = $this->getJoinedObjectChilds('visa', true);
//
//			if(!empty($aVisa)) {
//				$oVisa = reset($aVisa);
//			} else {
//				$oVisa = $this->getJoinedObjectChild('visa');
//				$oContact = $this->getInquiry()->getCustomer();
//				$oVisa->setJoinedObject('traveller', $oContact);
//			}
//
//			$this->_oVisa = $oVisa;
//		}
//
//		return (object)$this->_oVisa;

	}

	/**
	 * TravellerData Object dieser Journey
	 * @param $sType
	 * @return Ext_TS_Inquiry_Journey_Traveller[]
	 */
	public function getTravellerDetail($sType){
		
		$aTravellerData = (array)$this->getJoinedObjectChilds('traveller_detail');

		foreach($aTravellerData as $oTravellerDetail){
			if($oTravellerDetail->type == $sType){
				return $oTravellerDetail;
			}
		}
		// Wenn nix gespeichertes gefunden wurde neues Obj returnen
		$oTravellerDetail = new Ext_TS_Inquiry_Journey_Traveller();
		$oTravellerDetail->journey_id = $this->id;
		$oTravellerDetail->type = $sType;
		return $oTravellerDetail;
	}
	
	/**
	 * Alle gespeicherten Traveler Details
	 * @return Ext_TS_Inquiry_Journey_Traveller[]
	 */
	public function getTravellerDetails() {
		$aTravellerData = $this->getJoinedObjectChilds('traveller_detail');
		return (object)$aTravellerData;	
	}
	
	/**
	 * Liefert das Inquiry Objekt
	 * @return Ext_TS_Inquiry
	 */
	public function getInquiry(){
		$oInquiry = $this->getJoinedObject('inquiry');
		return (object)$oInquiry;
	}

	/**
	 * @param $sName
	 * @param $mValue
	 */
	public function __set($sName, $mValue) {
        // Cache zurück setzten
        if($sName == 'courses'){
            $this->_aCourseCache = array();
        } 
        
        parent::__set($sName, $mValue);
        
        if($sName == 'school_id' && $mValue > 0){
            $oSchool = $this->getSchool();
            $this->productline_id = $oSchool->getProductLineId();
        }
    }

	/**
	 * Gibt alle verwendeten Unterkünfte zurück
	 *
	 * @return Ext_TS_Inquiry_Journey_Accommodation[]
	 */
	public function getUsedAccommodations() {

		$aList = array();
		$aObjects = $this->getJoinedObjectChilds('accommodations', true);

		foreach($aObjects as $oObject) {
			if(
				$oObject->visible == 1 &&
				(
					$oObject->journey_id > 0 ||
					(
						$oObject->journey_id <= 0 &&
						$oObject->id <= 0
					)
				)
			) {
				$aList[] = $oObject;
			}
		}

		return $aList;
	}

	/**
	 * Gibt alle verwendeten Transfers zurück
	 *
	 * @return Ext_TS_Inquiry_Journey_Transfer[]
	 */
	public function getUsedTransfers() {

		$aList = array();
		$aObjects = $this->getJoinedObjectChilds('transfers', true);

		/** @var $oObject Ext_TS_Inquiry_Journey_Transfer */
		foreach($aObjects as $oObject) {
			if(
				// $oObject->getInquiry()->tsp_transfer != 'no' &&
				$this->transfer_mode != self::TRANSFER_MODE_NONE &&
				(
					$oObject->journey_id > 0 ||
					(
						$oObject->journey_id <= 0 &&
						$oObject->id <= 0
					)
				)
			) {
				$aList[] = $oObject;
			}
		}

		return $aList;
	}

	/**
	 * Gibt alle verwendeten Kurse zurück
	 *
	 * @return Ext_TS_Inquiry_Journey_Course[]
	 */
	public function getUsedCourses() {

		$aList = array();
		$aObjects = $this->getJoinedObjectChilds('courses', true);

		foreach($aObjects as $oObject) {
			if(
				$oObject->visible == 1 &&
				(
					$oObject->journey_id > 0 ||
					(
						$oObject->journey_id <= 0 &&
						$oObject->id <= 0
					)
				)
			) {
				$aList[] = $oObject;
			}
		}

		return $aList;
	}

	/**
	 * Alle Kurse dieses Journeys holen
	 *
	 * @param bool $bCheckVisible Prüfen, ob Leistung sichtbar ist
	 * @param bool $bCheckService Prüfen, ob überhaupt eine Leistung ausgewählt wurde
	 * @return Ext_TS_Inquiry_Journey_Course[]
	 */
	public function getCoursesAsObjects($bCheckVisible = false, $bCheckService=false) {

		$aObjects = $this->getJoinedObjectChilds('courses', true);
		$aObjects = $this->filterServiceObjects($aObjects, $bCheckVisible, $bCheckService, 'course_id');

		return $aObjects;

	}

	/**
	 * Alle Unterkünfte dieses Journeys holen
	 *
	 * @param bool $bCheckVisible Prüfen, ob Leistung sichtbar ist
	 * @param bool $bCheckService Prüfen, ob überhaupt eine Leistung ausgewählt wurde
	 * @return Ext_TS_Inquiry_Journey_Accommodation[]
	 */
	public function getAccommodationsAsObjects($bCheckVisible = false, $bCheckService=false) {

		$aObjects = $this->getJoinedObjectChilds('accommodations', true);
		$aObjects = $this->filterServiceObjects($aObjects, $bCheckVisible, $bCheckService, 'accommodation_id');

        return $aObjects;

	}
	
	/**
	 * Alle Transfers dieses Journeys holen
	 *
	 * Individuelle Transfers sind unabhängig vom Select, also immer Bestandteil der Rückgabe
	 *
	 * @param bool $bCheckRequested Transfers, die nicht gewünscht sind, werden exkludiert
	 * @return Ext_TS_Inquiry_Journey_Transfer[]
	 */
	public function getTransfersAsObjects($bCheckRequested = false) {

		$aTransfers = $this->getJoinedObjectChilds('transfers', true);

		if (!$bCheckRequested) {
			return $aTransfers;
		}

		return array_filter($aTransfers, function (Ext_TS_Inquiry_Journey_Transfer $oJourneyTransfer) {
			return (
				$oJourneyTransfer->transfer_type == $oJourneyTransfer::TYPE_ARRIVAL &&
				$this->transfer_mode & self::TRANSFER_MODE_ARRIVAL
			) || (
				$oJourneyTransfer->transfer_type == $oJourneyTransfer::TYPE_DEPARTURE &&
				$this->transfer_mode & self::TRANSFER_MODE_DEPARTURE
			) || (
				// Zusätzliche Transfers sind immer gebucht
				$oJourneyTransfer->transfer_type == $oJourneyTransfer::TYPE_ADDITIONAL
			);
		});
	}

	/**
	 * @param bool $bCheckVisible
	 * @param bool $bCheckService
	 * @return Ext_TS_Inquiry_Journey_Activity[]
	 */
	public function getActivitiesAsObjects($bCheckVisible = true, $bCheckService = true) {

		$aObjects = $this->getJoinedObjectChilds('activities', true);
		$aObjects = $this->filterServiceObjects($aObjects, $bCheckVisible, $bCheckService, 'activity_id');

		return $aObjects;

	}

	/**
	 * @return Ext_TS_Inquiry_Journey_Insurance[]
	 */
	public function getInsurancesAsObjects($bCheckVisible = true, $bCheckService = true) {

		$aObjects = $this->getJoinedObjectChilds('insurances', true);
		$aObjects = $this->filterServiceObjects($aObjects, $bCheckVisible, $bCheckService, 'insurance_id');

		return $aObjects;

	}

	/**
	 * @return Ext_TS_Inquiry_Journey_Additionalservice[]
	 */
	public function getAdditionalServicesAsObjects($bCheckVisible = true, $bCheckService = true) {

		$aObjects = $this->getJoinedObjectChilds('additionalservices', true);
		$aObjects = $this->filterServiceObjects($aObjects, $bCheckVisible, $bCheckService, 'additionalservice_id');

		return $aObjects;

	}

	/**
	 * @param array $aObjects
	 * @param bool $bCheckVisible
	 * @param bool $bCheckService
	 * @param string $sServiceKey
	 * @return array
	 */
	private function filterServiceObjects(array $aObjects, $bCheckVisible, $bCheckService, $sServiceKey) {

		if(
			$bCheckVisible ||
			$bCheckService
		) {
			foreach($aObjects as $iKey => $oObject) {
				if(
					(
						$bCheckVisible &&
						$oObject->visible == 0
					) || (
						$bCheckService &&
						$oObject->{$sServiceKey} == 0
					)
				) {
					unset($aObjects[$iKey]);
				}
			}
		}

		return $aObjects;

	}
	
	/**
	 * Minimum von-Wert ausrechnen
	 * 
	 * @param array $aSplitting
	 * @param array $aHolidayBySplitting
	 * @return string 
	 */
	protected function _getHolidayFrom(array $aSplitting, array $aHolidayBySplitting)
	{
		$oDateFrom	= new DateTime($aSplitting['course_right_from']);
		$oDateUntil	= new DateTime($aSplitting['course_right_until']);
		
		$oFromMin	= $oDateFrom;
		$oUntilMax	= $oDateUntil;
		
		foreach($aHolidayBySplitting as $aOtherSplittingData)
		{
			$oDateOtherFrom		= new DateTime($aOtherSplittingData['course_right_from']);
			$oDateOtherUntil	= new DateTime($aOtherSplittingData['course_right_until']);
		
			if(
				$oDateFrom < $oDateOtherUntil &&
				$oDateUntil > $oDateOtherFrom
			)
			{
				if($oDateOtherFrom < $oFromMin)
				{
					$oFromMin = $oDateOtherFrom;
				}

				if($oDateOtherUntil > $oUntilMax)
				{
					$oUntilMax = $oDateOtherUntil;
				}	
			}
		}
		
		$sFrom	= $oFromMin->format('Y-m-d');
		$sUntil = $oUntilMax->format('Y-m-d');
		
		return $sFrom;
	}
	
	public function getSameService($sJoinedObjectKey, $aCompareData)
	{
		$oSameService	= null;
		
		$aServices		= $this->getJoinedObjectChilds($sJoinedObjectKey, true);
		
		foreach($aServices as $oService)
		{
			$aDataService	= $oService->getArray();
			
			$bSame			= true;
			
			foreach($aCompareData as $sKeyCompare => $mValueCompare)
			{
				if(
					$mValueCompare != $aDataService[$sKeyCompare]
				)
				{
					$bSame = false;
					break;
				}
			}
			
			if($bSame)
			{
				$oSameService = $oService;
				
				break;
			}
		}
		
		return $oSameService;
	}

	/**
	 * @param string $sServiceType course|accommodation
	 * @param string $sType student|school
	 * @return array
	 */
	public function getRelatedServices($sServiceType, $sType = 'both') {

		$aRelations = [];

		if($sServiceType === 'course') {
			$aJourneyServices = $this->getCoursesAsObjects();
		} elseif($sServiceType === 'accommodation') {
			$aJourneyServices = $this->getAccommodationsAsObjects();
		} else {
			throw new InvalidArgumentException('Invalid service type!');
		}

		/** @var Ext_TS_Inquiry_Holiday[] $aInquiryHolidays */
		$aInquiryHolidays = $this->getInquiry()->getJoinedObjectChilds('holidays', true);

		foreach($aInquiryHolidays as $oHoliday) {
			if(
				$sType === null ||
				$sType === $oHoliday->type
			) {
				foreach($oHoliday->getSplittings() as $oSplitting) {
					if(
						$oSplitting->getType() === $sServiceType &&
						// Muss geprüft werden, da getJoinedObject() leider IMMER ein Objekt zurückgibt
						$oSplitting->hasSplitting()
					) {
						$oOldService = $oSplitting->getJoinedObject('old_'.$sServiceType); /** @var Ext_TS_Inquiry_Journey_Course $oOldService */
						$oNewService = $oSplitting->getJoinedObject('new_'.$sServiceType); /** @var Ext_TS_Inquiry_Journey_Course $oNewService */
						if($oOldService->active && $oNewService->active) {
							$aRelations[] = [$oOldService, $oNewService];
						}
					}
				}
			}
		}

		// Zusammenpassende Leistungsbuchungen (Gruppen) ermitteln und mergen
		$aGroups = [];
		foreach($aRelations as $aRelevant) {
			/** @var Ext_TS_Inquiry_Journey_Course[] $aRelevant */
			$aRelevantGroups = [];
			foreach($aRelevant as $oJourneyService) {
				foreach($aGroups as $iGroupKey => $aGroup) {
					if(in_array($oJourneyService, $aGroup, true)) {
						$aRelevantGroups[] = $aGroup;
						unset($aGroups[$iGroupKey]);
					}
				}
			}
			$aNewGroup = $aRelevant;
			foreach($aRelevantGroups as $aGroup) {
				$aNewGroup = array_merge($aGroup, $aNewGroup);
			}

			// array_unique manuell, da array_unique() manchmal »Nesting level too deep« schmeißt
			$aNewGroup2 = [];
			foreach($aNewGroup as $oJourneyService) {
				if(!in_array($oJourneyService, $aNewGroup2, true)) {
					$aNewGroup2[] = $oJourneyService;
				}
			}

			$aGroups[] = $aNewGroup2;
		}

		// Services, welche keine Relation haben, einzeln hinzufügen, damit jeder Service auch eine Gruppe hat
		foreach($aJourneyServices as $oJourneyService) {
			$bFound = false;
			foreach($aGroups as $aGroup) {
				if(in_array($oJourneyService, $aGroup, true)) {
					$bFound = true;
					break;
				}
			}

			if(!$bFound) {
				$aGroups[] = [$oJourneyService];
			}
		}

		$aGroups = array_values($aGroups);

		return $aGroups;

	}

	/**
	 * Anfragen: Offer gehört zum Journey (Combination)
	 *
	 * @return Ext_Thebing_Inquiry_Document|null
	 */
	public function getDocument(): ?Ext_Thebing_Inquiry_Document {

		$oSearch = new Ext_Thebing_Inquiry_Document_Search($this->id);
		$oSearch->setType('offer');
		$oSearch->setObjectType(Ext_TS_Inquiry_Journey::class);
		$aDocuments = $oSearch->searchDocument();

		if (count($aDocuments) === 1) {
			return reset($aDocuments);
		}

		if (count($aDocuments) > 1) {
			throw new LogicException('More than one document for journey '.$this->id);
		}

		return null;

	}

	public function getServiceObjects(string $service)
	{
		return match ($service) {
			'course' => $this->getCoursesAsObjects(true, true),
			'accommodation' => $this->getAccommodationsAsObjects(true, true),
			'transfer' => $this->getTransfersAsObjects(true),
			'insurance' => $this->getInsurancesAsObjects(),
			'activity' => $this->getActivitiesAsObjects(),
		};
	}

}
