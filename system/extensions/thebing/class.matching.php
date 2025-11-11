<?php

use TsAccommodation\Entity\Matching\Criterion;

class Ext_Thebing_Matching {

	const L10N_DESCRIPTION = 'Thebing » Accommodation » Matching';

	/** @var Ext_TS_Inquiry */
	protected $oInquiry ;
	protected $oCustomer ;
	protected $_bOnlyCorrectRooms;
	
	// Array mit allen Inquiries derer die die Räume mit Teilen
	protected $aRoomSharingInquiries = array();
	
	// Familien zudenen aktuell zugewiesen ist
	protected $aActiveFamilies = array();
	
	// Räume zudenen aktuell zugewiesen ist
	protected $aActiveRooms = array();
	
	protected static $aCache = array();

	public $oAccommodation;
	// Timestamp
	public $iFrom = 0 ;
	public $iTo = 0 ;
	// DB Date
	public $sFrom = '';
	public $sTo = '';
	
	public $iIgnoreAllocation = 0;
	
	/**
	 * @var DateTime 
	 */
	protected $_oFrom;
	/**
	 * @var DateTime 
	 */
	protected $_oUntil;

	public $bSkipAllocationCheck = false;
	public $bIgnoreCategory = false;
	public $bIgnoreRoomtype = false;

	protected $_sInterfaceLanguage = 'en';

	/**
	 * @var DB 
	 */
	protected $_oDB;
	
	private $bDebug = false;
	
	/**
	 * @var Ext_Thebing_School
	 */
	protected $school;

	public function __construct() {
		global $_VARS; 

		$oSchool =  Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
		$this->_sInterfaceLanguage = $oSchool->getInterfaceLanguage();
		$this->_oDB = DB::getDefaultConnection();

		if(!empty($_VARS['matching_debugmode']) && $_VARS['matching_debugmode'] == 1) {
			$this->bDebug = true;
		}

	}

	public function getSchool(): Ext_Thebing_School {
		
		if($this->school) {
			return $this->school;
		}
		
		if(
			$this->oInquiry instanceof Ext_TS_Inquiry &&
			$this->oInquiry->exist()
		) {
			$this->school = $this->oInquiry->getSchool();
		} else {
			$this->school = Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
		}

		return $this->school;
	}
	
	/**
	 * 
	 * @param DateTime $oFrom
	 * @param DateTime $oTo
	 * @param int $iFamilie
	 * @param int $iCategoryId
	 * @param boolean $bLimitedData
	 * @param boolean $bRooms
	 * @return array
	 */
	public function getAllFamiliesWithBeds(DateTime $oFrom, DateTime $oTo, $iFamilie=1, $iCategoryId=0, $bLimitedData = false, $bRooms = true) {
		
		$aSql				= array();
		$oSchool			= $this->getSchool();
		$iSchoolId			= (int)$oSchool->id;
		$sInterfaceLanguage = $oSchool->getInterfaceLanguage();

		$oAccommodationRepo = new Ext_Thebing_Accommodation_Matching_AccommodationRepository($this->_oDB, (int)$iSchoolId);
		$oAccommodationRepo->setValidUntil($oFrom);

        if($iFamilie == \Ext_Thebing_Accommodation_Category::TYPE_PARKING) {
            $oAccommodationRepo->enableTypeParking();
        } else if($iFamilie == \Ext_Thebing_Accommodation_Category::TYPE_HOSTFAMILY) {
			$oAccommodationRepo->enableTypeHostfamily();
		} else {
			$oAccommodationRepo->enableTypeOther();
		}
		
		if(!empty($iCategoryId)) {
			$oAccommodationRepo->setCategoryId($iCategoryId);
		}

		$aResult = $oAccommodationRepo->getCollection();

		$aBack				= array();
		$i = 0;

		foreach($aResult as $aAccom) {
			
			$aBack[$i] = $aAccom;
			
			$sSql = "SELECT 
						`room`.*, 
						`room_type`.`name_".$sInterfaceLanguage."` as `roomtype`, 
						GROUP_CONCAT(CONCAT (`ts_arcs`.`bed`, '{|}', `ts_arcs`.`status`) SEPARATOR '{||}') `cleaning_status`,
						'0' as `kra`
					FROM
						`kolumbus_rooms` as `room` LEFT JOIN
						`kolumbus_accommodations_roomtypes` as `room_type` ON
							`room`.`type_id` = `room_type`.`id`  LEFT JOIN
						`ts_accommodation_rooms_latest_cleaning_status` `ts_arcs` ON 
					    	`ts_arcs`.`room_id` = `room`.`id`
					WHERE
						`room`.`accommodation_id` = :idAccommodation AND
						`room`.`active` = 1	AND	(
							`room`.`valid_until` = 0 OR
							`room`.`valid_until` > :valid_until
						)
					GROUP BY 
						`room`.`id`	
					ORDER BY 
						`room`.`name`
			";
			
			$aSql						= array();
			$aSql['idAccommodation']	= $aAccom['id'];
			$aSql['valid_until']		= $oFrom->format('Y-m-d H:i:s');
			$aBackR						= DB::getPreparedQueryData($sSql,$aSql);

			$aBack[$i]['rooms'] = $aBackR;
			
			if(count($aBack[$i]['rooms']) <= 0) {
				
				unset($aBack[$i]);
				continue;
				
			} else {
				$aRooms_ = array();
				foreach($aBack[$i]['rooms'] as $aRoom) {

					$aAllocationList = $this->getAllocationOfRoom($aRoom['id'], $oFrom, $oTo); 

					$aFamilieBlockingOfRoom = Ext_Thebing_Accommodation_Dates::getBlockingPeriods($aRoom['id'], $oFrom, $oTo);
					
					$aFamilieBlockingOfRoom = $this->addBlockingDayData($aFamilieBlockingOfRoom);
					
					$aRoom['blocking'] = $aFamilieBlockingOfRoom;

					// Reinigungsstatus
					if (!empty($aRoom['cleaning_status'])) {
						$aRoom['cleaning_status'] = $this->getCleaningStatusDataFromResult($aRoom['cleaning_status']);
					}

					if($bRooms){
						$aTempRoom = $this->_getBedsInRoom($aRoom, $aAllocationList);
						$aRooms_ = array_merge($aRooms_, $aTempRoom);
					} else {
						$aRooms_ = array_merge($aRooms_, array($aRoom));
					}

				}

				$aBack[$i]['rooms'] =$aRooms_;
				$i++;
			}
			
		}

		return $aBack;

	}
	
	public function addBlockingDayData($aFamilieBlockingOfRoom){
		foreach($aFamilieBlockingOfRoom as $key => $aFamilieBlocking){

			$oWDDateFamilyBlockingFrom 	= new DateTime($aFamilieBlocking['from']);
			$oWDDateFamilyBlockingUntil = new DateTime($aFamilieBlocking['until']);

			$aFamilieBlockingOfRoom[$key]['day_from'] = $this->getDayData($oWDDateFamilyBlockingFrom);
			$aFamilieBlockingOfRoom[$key]['day_until'] = $this->getDayData($oWDDateFamilyBlockingUntil);

		}
		return $aFamilieBlockingOfRoom;
	}
	
	public function getMatchedFamilie(Ext_TS_Inquiry_Abstract $oInquiry, $iWithOtherTypes = 1, $iRoomId=false) {

		$aBack = [];
		$oCustomer = $oInquiry->getCustomer();
		$oSchool = $oInquiry->getSchool();
		$this->oInquiry = $oInquiry;
		$this->oCustomer = $oCustomer;

		// Schüler die mit dieser Buchung zusammenreisen	
		$this->aRoomSharingInquiries = $this->oInquiry->getRoomSharingInquiries();

		$aActiveFamilies = [];
		$aActiveRooms = [];

		$aFilters = [];
        $aFilters['category_type_id'] = [\Ext_Thebing_Accommodation_Category::TYPE_HOSTFAMILY];

		// TODO nur die familien ins array packen die im Filterzeitraum liegen nicht alle
		$aAllocations = $this->oInquiry->getAllocations($aFilters);
		foreach($aAllocations as $oAllocation){

			$oRoom = $oAllocation->getRoom();

			$aActiveFamilies[] = $oRoom->getProvider()->id;

			if($oRoom->id > 0) {
				$aActiveRooms[] = $oRoom->id;
			}

		}

		$this->aActiveFamilies = $aActiveFamilies;				
		$this->aActiveRooms = $aActiveRooms;

		// Für die "Erwachsenen" Prüfung, soll nicht das aktuelle Alter, sondern das Alter bestimmt werden
		// wenn der Kunden zugewiesen werden soll.
		$oDateAllocation = new DateTime($this->sFrom);
		if($oCustomer->getAge($oDateAllocation) >= $oSchool->adult_age) {
			$bAdults = true;
			$bMinors = false;
		} else {
			$bAdults = false;
			$bMinors = true;
		}

		$bMale = $bFemale = $diverse = false;

		if($oCustomer->gender == 1) {
			$bMale = true;
		} else if($oCustomer->gender == 2) {
			$bFemale = true;
		} else if($oCustomer->gender == 3) {
			$diverse = true;
		}

		$oMatchingData = $oInquiry->getMatchingData();

		if($oMatchingData->acc_smoker == 2) {
			$bSmoker = true;
		} else {
			$bSmoker = false;
		}

		if($oMatchingData->acc_vegetarian == 2) {
			$bVegetarian = true;
		} else {
			$bVegetarian = false;
		}

		if($oMatchingData->acc_muslim_diat == 2) {
			$bMuslimDiet = true;
		} else {
			$bMuslimDiet = false;
		}

		$aBack = $this->_getAccommodationList(
		    $this->oAccommodation->accommodation_id, 
		    $this->oAccommodation->roomtype_id, 
		    $this->oAccommodation->meal_id, 
		    $bAdults, 
		    $bMinors, 
		    $bMale, 
		    $bFemale,
			$diverse,
		    $bSmoker, 
		    $bVegetarian, 
		    $bMuslimDiet, 
		    $iWithOtherTypes, 
		    $iRoomId
		);

		return $aBack;
	}
	
	
	public function getOtherMatched($oInquiry, $iWithOtherTypes = 1, $bOnlyCorrectRooms = false, $iRoomId = false, $bParking = false) {
		
		$oCustomer	= $oInquiry->getCustomer();
		$oSchool	= $oInquiry->getSchool();
		
		$this->oInquiry = $oInquiry;
		$this->oCustomer = $oCustomer;
		
		$oDateAllocation = new DateTime($this->sFrom);

		// Es soll nicht das aktuelle Alter, sondern das Alter bestimmt werden
		// wenn der Kunde zugewiesen werden soll.
		$iCustomerAge = $oCustomer->getAge($oDateAllocation);

		if($iCustomerAge >= $oSchool->adult_age){
			$bAdults = true;
			$bMinors = false;
		}else{
			$bAdults = false;
			$bMinors = true;
		}
		
		// Schüler die mit dieser Buchung zusammenreisen
		$this->aRoomSharingInquiries = $this->oInquiry->getRoomSharingInquiries();

		$aActiveFamilies	= array();
		$aActiveRooms		= array();

		$aFilters = [];
		if($bParking) {
            $aFilters['category_type_id'] = [\Ext_Thebing_Accommodation_Category::TYPE_PARKING];
        } else {
            $aFilters['category_type_id'] = [\Ext_Thebing_Accommodation_Category::TYPE_OTHERS];
        }

		// TODO nur die familien ins array packen die im Filterzeitraum liegen nicht alle
		$aAllocations = $this->oInquiry->getAllocations($aFilters);
		
		foreach($aAllocations as $oAllocation){

			$oRoom				= $oAllocation->getRoom();
			
			$aActiveFamilies[]	= $oRoom->getProvider()->id;
			$aActiveRooms[]		= $oRoom->id;
			
		}

		$this->aActiveFamilies	= $aActiveFamilies;	
		$this->aActiveRooms		= $aActiveRooms;

		$iGender					= $oCustomer->gender;
		$iMotherTongue				= $oCustomer->language;
		$iVegetarian				= $oInquiry->getMatchingData()->acc_vegetarian;
		
		$this->_bOnlyCorrectRooms = $bOnlyCorrectRooms;	

		$aBack = $this->_getOtherAccommodationList(
		    $this->oAccommodation->accommodation_id,
		    $this->oAccommodation->roomtype_id,
		    $this->oAccommodation->meal_id,
			$iCustomerAge,
		    $iGender,
		    $iMotherTongue,
		    $iVegetarian,
		    $iWithOtherTypes, 
		    $iRoomId,
			$bAdults, 
		    $bMinors,
            $bParking
		);

		return $aBack;
	}

    /**
     * @param int $iCategoryId
     * @param int $iRoomtypeId
     * @param int $sMeals
     * @param bool $bAdults
     * @param bool $bMinors
     * @param bool $bMale
     * @param bool $bFemale
	 * @param bool $diverse
     * @param bool $bSmoker
     * @param bool $bVegetarian
     * @param bool $bMuslimDiet
     * @param int $iWithOtherTypes
     * @param bool $iRoomId
     * @return array
     */
	protected function _getAccommodationList($iCategoryId = 0, $iRoomtypeId = 0, $sMeals = 0, $bAdults = true, $bMinors = false, $bMale = true, $bFemale=false, $diverse = false, $bSmoker = false, $bVegetarian = false, $bMuslimDiet = false, $iWithOtherTypes = 1, $iRoomId = false) {

		$oSchool = $this->getSchool();
		if(!$oSchool->exist()) {
			$sMsg = 'No school available';
			throw new RuntimeException($sMsg);
		}

		$oAccommodationRepo = new Ext_Thebing_Accommodation_Matching_AccommodationRepository($this->_oDB, $oSchool->id);
		$oAccommodationRepo->setValidUntil($this->_oFrom, $this->_oUntil);
		$oAccommodationRepo->setActiveFamilies($this->aActiveFamilies);
		$oAccommodationRepo->enableTypeHostfamily();

		if(!$this->bIgnoreCategory) {
			$oAccommodationRepo->setCategoryId($iCategoryId);
		}		
		if(!empty($iRoomId)) {
			$oAccommodationRepo->setRoomId($iRoomId);
		}
		if($bAdults) {
			$oAccommodationRepo->enableAdults();
		}
		if($bMinors) {
			$oAccommodationRepo->enableMinors(); 
		}
		if($bMale) {
			$oAccommodationRepo->enableMale();
		}
		if($bFemale) {
			$oAccommodationRepo->enableFemale();
		}
		if($diverse) {
			$oAccommodationRepo->enableDiverse();
		}
		if($bSmoker) {
			$oAccommodationRepo->enableSmoker();
		}
		if($bVegetarian) {
			$oAccommodationRepo->enableVegetarian();
		}
		if($bMuslimDiet) {
			$oAccommodationRepo->enableMuslimDiet();
		}

		$aResult = $oAccommodationRepo->getCollection();

		$aBack = [];
		$i = 0;

		foreach($aResult as $aAccom) {

			$aMealsAcc = explode(',', $aAccom['meals']);
			$bContinue = false;

			if(
				$sMeals > 0 &&
				count($aMealsAcc) > 0
			) {
				$bContinue = true;
				foreach((array)$aMealsAcc as $idMealTemp) {
					if($idMealTemp == $sMeals) {
						$bContinue = false;
					}
				}
			}

			if(
				$bContinue == true &&
				!in_array($aAccom['id'], $this->aActiveFamilies)
			) {
				continue;
			}

			$aBack[$i] = $aAccom;
			$aBack[$i]['rooms'] = (array)$this->_getRoomList($aAccom['id'], $iRoomtypeId, $iWithOtherTypes, $iRoomId);

			$aMealIds = explode(',', $aAccom['meals']);
			$aBack[$i]['meals'] = $this->_getMealList($aMealIds);

			// TODO: Fehlt hier Abfrage auf $this->aActiveFamilies?
			if(empty($aBack[$i]['rooms'])) {
				unset($aBack[$i]);
				continue;
			} else {

				$aTmpRoomList = [];

				foreach($aBack[$i]['rooms'] as $aRoom) {

					$aAllocationList = null;
					$aTmpRoom = $this->_getBedsInRoom($aRoom, $aAllocationList);
					$aTmpRoomList = array_merge($aTmpRoomList, $aTmpRoom);

				}

				$aBack[$i]['rooms'] = $aTmpRoomList;
				$i++;

			}

		}

		return $aBack;

	}

	protected function _getOtherAccommodationList( 
		$iCategoryId = 0,
		$iRoomtypeId = 0,
		$idMeal=0,
		$iAge,
		$iGender,
		$iMotherTongue,
		$iVegetarian,
		$iWithOtherTypes = 1, 
		$iRoomId=false,
		$bAdults,
		$bMinors,
        $bParking = false
	) {

		$oSchool = $this->getSchool();
		if(!$oSchool->exist()) {
			$sMsg = 'No school available';
			throw new RuntimeException($sMsg);
		}

		$oAccommodationRepo = new Ext_Thebing_Accommodation_Matching_AccommodationRepository($this->_oDB, $oSchool->id);
		$oAccommodationRepo->setValidUntil($this->_oFrom);
		$oAccommodationRepo->setActiveFamilies($this->aActiveFamilies);

		if($bParking) {
            $oAccommodationRepo->enableTypeParking();
        } else {
            $oAccommodationRepo->enableTypeOther();
        }

		if(!$this->bIgnoreCategory) {
			$oAccommodationRepo->setCategoryId($iCategoryId);
		}
		if(!empty($iRoomId)) {
			$oAccommodationRepo->setRoomId($iRoomId);
		}
		if($bAdults) {
			$oAccommodationRepo->enableAdults();
		}
		if($bMinors) {
			$oAccommodationRepo->enableMinors(); 
		}

		$aResult = $oAccommodationRepo->getCollection();

		$aBack = array();
		$i = 0;

		$this->debugMatchingCriterions($aResult, 'Accommodations', true);

		foreach($aResult as $aAccom) {

			$aMealsAcc = explode(',', $aAccom['meals']);

			$bContinue = false;
			if($idMeal > 0){
				$bContinue = true;
				foreach((array)$aMealsAcc as $idMealTemp) {
					if($idMeal == $idMealTemp) {
						$bContinue = false;
					}
				}
			}

            if(
				$bContinue == true &&
				!in_array($aAccom['id'], $this->aActiveFamilies)
			) {
                continue;
			}

			$bAge = $aAccom['ext_55'];
			$bGender = $aAccom['ext_54'];
			$bNationality = $aAccom['ext_56'];
			$bMotherTongue = $aAccom['ext_57'];
			$aBack[$i] = $aAccom;
			$aBack[$i]['rooms'] = $this->_getOtherRoomList($aAccom['id'], $iRoomtypeId, $bAge, $iAge, $bGender, $iGender, $bNationality, $bMotherTongue, $iMotherTongue, $iWithOtherTypes, $iRoomId, $bParking);

			$aMealIds = explode(',', $aAccom['meals']);
			$aBack[$i]['meals'] = $this->_getMealList($aMealIds);

			// TODO: Fehlt hier Abfrage auf $this->aActiveFamilies?
			if(
				empty($aBack[$i]['rooms']) ||
				count($aBack[$i]['rooms']) <= 0
			) {
				
				unset($aBack[$i]);
				continue;
			
			} else {

				$aRooms_ = array();
				foreach((array)$aBack[$i]['rooms'] as $aRoom) {

					$aAllocationList = null;
					$aTempRoom = $this->_getBedsInRoom($aRoom, $aAllocationList, $bParking);

					$aRooms_ = array_merge($aRooms_, $aTempRoom);

				}
				$aNewRooms = $aRooms_;
				$aBack[$i]['rooms'] = $aNewRooms;
				
				$i++;
			}
			
		}

		return $aBack;

	}	

	/**
	 * @param array $aRoom
	 * @param array $aAllocations Achtung, Referenz!
	 * @param bool $bParking
	 * @return array
	 */
	public function _getBedsInRoom($aRoom, &$aAllocations = null, $bParking = false) {

		$oSchool = $this->getSchool();
//		if(!$oSchool->exist()) {
//			$sMsg = 'No school available';
//			throw new RuntimeException($sMsg);
//		}

		if(!$aAllocations) {
			$aAllocations = $aRoom['allocation'];
		}

		$aBeds = [];

		// Raum nach Anzahl der Betten gruppieren
		$cFillBedsArray = function($iBed, $iIndex, $sComment) use ($aRoom, &$aBeds, $bParking) {
			$aBed = $aRoom;

			$sLabel = ($bParking) ? Ext_Thebing_L10N::t('Platz') : Ext_Thebing_L10N::t('Bett');

			$aBed['name'] = $aRoom['name']." / ".$sLabel." ".$iBed;

			$aBed['bed_number'] = $iBed;
			$aBed['room_name'] = $aRoom['name'];
			$aBed['allocation'] = []; // Array mit Arrays, aber hier kann eigentlich nur ein Array drin stehen

			$aBed['bed_comment'] = $sComment;

			if (isset($aRoom['cleaning_status'])) {
				if (isset($aRoom['cleaning_status'][$iBed])) {
					$aBed['cleaning_status'] = $aRoom['cleaning_status'][$iBed];
				} else {
					unset($aBed['cleaning_status']);
				}
			}

			$aBeds[$iBed] = $aBed;
		};

		$iSingleBeds = intval($aRoom['single_beds']);
		$iDoubleBeds = intval($aRoom['double_beds'] * 2);
		$iTotalBeds = $iSingleBeds + $iDoubleBeds;

		$iOffset = 1;

		// Einzelbetten
		if($iSingleBeds > 0) {
			array_walk(
				range($iOffset, $iSingleBeds),
				$cFillBedsArray,
				$aRoom['single_beds_comment']
			);
			$iOffset = $iSingleBeds+1;
		}

		// Doppelbetten
		if($iDoubleBeds > 0) {
			array_walk(
				range($iOffset, $iOffset+$iDoubleBeds-1),
				$cFillBedsArray,
				$aRoom['double_beds_comment']
			);
		}

		if(is_array($aAllocations)) {

			// Arrays aufbauen für array_multisort()
			foreach($aAllocations as $iKey => $aAllocation) {
			    $aSortKey1[$iKey] = $aAllocation['bed'] > 0;
			    $aSortKey2[$iKey] = new DateTime($aAllocation['from_date']);
			}

			// Zuweisungen mit Bett nach oben sortieren, aber chronologische Reihenfolge behalten #9932
			array_multisort($aSortKey1, SORT_DESC, $aSortKey2, SORT_ASC, $aAllocations);

			/*
			 * Alle übrigen Zuweisungen mit bed = 0 allen übrigen Betten zuordnen
			 * Dabei Zeitraum beachten.
			 * Annahme: Zuweisungen sind chronologisch geordnet
			 */
			foreach($aAllocations as $iAllocationKey => $aAllocation) {

				$dAllocationFrom = new \Core\Helper\DateTime($aAllocation['from_date']);
				$dAllocationUntil = new \Core\Helper\DateTime($aAllocation['until_date']);
			
				$iAllocationToBed = null;

				// Zuweisungen mit bed != 0 dem entsprechenden Bett des Raums zuordnen
				if($aAllocation['bed'] > 0) {

					$iAllocationToBed = (int)$aAllocation['bed'];

				} else {

					foreach($aBeds as $iBedKey => $aBed) {

						// Wenn nicht leer, dann Zeitraum prüfen
						if(!empty($aBeds[$iBedKey]['allocation'])) {

							foreach($aBeds[$iBedKey]['allocation'] as $aBedAllocation) {

								$dBedAllocationFrom = new \Core\Helper\DateTime($aBedAllocation['from_date']);
								$dBedAllocationUntil = new \Core\Helper\DateTime($aBedAllocation['until_date']);

								// Wenn sich Zuweisungen irgendwie überschneiden (berühren ist OK), darf das Datum nicht benutzt werden
								if(
									$dAllocationFrom < $dBedAllocationUntil &&
									$dBedAllocationFrom < $dAllocationUntil
								) {
									// Nächstes Bett versuchen
									continue 2;
								}

							}
	
						}
						
						$iAllocationToBed = $iBedKey;

					}
	
				}

				if($iAllocationToBed !== null) {
					$aAllocation['allocation_from_other_school'] = null;
					if($oSchool->exist()) {
						$aAllocation['allocation_from_other_school'] = false;
						if(
							empty($aAllocation['reservation']) &&
							$aAllocation['school_id'] != $oSchool->id
						) {
							$aAllocation['allocation_from_other_school'] = true;
						}
					}
					$aBeds[$iAllocationToBed]['allocation'][] = $aAllocation;
					unset($aAllocations[$iAllocationKey]);
				}

			}

			// Eine Exception ist hier zu gefährlich
			if(!empty($aAllocations)) {
				Ext_TC_Util::reportError('TS Matching: More allocations than beds', print_r([$aRoom, $aAllocations], true));
			}

		}

		return $aBeds;
	}
			
	public static function getRoomAllocation($idRoom = 0, $oFrom, $oTo) {
		$oMatching = new Ext_Thebing_Matching();
		return $oMatching->getAllocationOfRoom($idRoom, $oFrom, $oTo);
	}

	public function getAllocationOfRoom($idRoom, \DateTime $oFrom, \DateTime $oTo) {

		$sSql = "
			SELECT 
				kra.*,
				UNIX_TIMESTAMP(kra.`from`) `from`, /* TODO: NICHT MEHR BENUTZEN UND ENTFERNEN */
				UNIX_TIMESTAMP(kra.`until`) `until`, /* TODO: NICHT MEHR BENUTZEN UND ENTFERNEN */
				DATE(kra.`from`) `from_date`,
				DATE(kra.`until`) `until_date`,
				UNIX_TIMESTAMP(kra.`until`) `to`, /* TODO: NICHT MEHR BENUTZEN UND ENTFERNEN */
				`ts_i_j`.`inquiry_id`,
				`ts_i_j`.`school_id`,
				GROUP_CONCAT(DISTINCT `kro`.`share_id` SEPARATOR ',') `share_ids`
			FROM 
				`kolumbus_accommodations_allocations` `kra` LEFT JOIN
				`ts_inquiries_journeys_accommodations` `ts_i_j_a` ON
					`ts_i_j_a`.`id` = `kra`.`inquiry_accommodation_id` AND
					`ts_i_j_a`.`active` = 1 AND
					`ts_i_j_a`.`visible` = 1 LEFT JOIN
				`ts_inquiries_journeys` `ts_i_j` ON
					`ts_i_j`.`active` = 1 AND
					`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_i_j`.`id` = `ts_i_j_a`.`journey_id` LEFT JOIN
				`ts_inquiries` `ts_i` ON
					`ts_i`.`id` = `ts_i_j`.`inquiry_id` AND
					`ts_i`.`active` = 1 LEFT JOIN
				`ts_inquiries_to_contacts` `ts_i_to_c` ON
					`ts_i_to_c`.`inquiry_id` = `ts_i`.`id` AND
					`ts_i_to_c`.`type` = 'traveller' LEFT JOIN
				`tc_contacts` `tc_c` ON
					`tc_c`.`id` = `ts_i_to_c`.`contact_id` AND
					`tc_c`.`active` = 1 LEFT OUTER JOIN
				`kolumbus_roomsharing` `kro` ON
					`kro`.`master_id` = `ts_i`.`id`
			WHERE					
				`kra`.`room_id` = :room_id AND
				`kra`.`room_id` > 0 AND
				`kra`.`active` = 1 AND
				`kra`.`status` = 0 AND (
					`kra`.`from` <= :to AND
					`kra`.`until` >= :from
				)
			GROUP BY
				`kra`.`id`
			ORDER BY
				`kra`.`from`
		";
		$aSql = [
			'room_id' => (int)$idRoom,
			'from' => $oFrom->format('Y-m-d H:i:s'),
			'to' => $oTo->format('Y-m-d H:i:s'),
		];
		$aResult = $this->_oDB->getCollection($sSql, $aSql);

		$aBack = [];
		$i = 0;
		foreach($aResult as $aAllocation) {

			if(
				$aAllocation['inquiry_id'] > 0 ||
				$aAllocation['reservation'] !== null
			) {

				$aBack[$i] = $aAllocation;
				
				$this->addAllocationData($aBack[$i]);

				$i++;

			}

		}

		if(empty($aBack)) {
			return 0;
		}

		return $aBack;
	}

	public function getNextAllocationsOfRoom($roomId, \DateTime $currentDate) {

	    $room = \Ext_Thebing_Accommodation_Room::getInstance($roomId);
	    $beds = $room->getNumberOfBeds();

        $aBack = [];
        $i = 0;

	    for($bed = 1; $bed <= $beds; ++$bed) {

            /**
             * @todo denselbe Query (außer WHERE) gibt es auch in getAllocationOfRoom()
             */

            $sSql = "
                SELECT 
                    kra.*,
                    UNIX_TIMESTAMP(kra.`from`) `from`, /* TODO: NICHT MEHR BENUTZEN UND ENTFERNEN */
                    UNIX_TIMESTAMP(kra.`until`) `until`, /* TODO: NICHT MEHR BENUTZEN UND ENTFERNEN */
                    DATE(kra.`from`) `from_date`,
                    DATE(kra.`until`) `until_date`,
                    UNIX_TIMESTAMP(kra.`until`) `to`, /* TODO: NICHT MEHR BENUTZEN UND ENTFERNEN */
                    `ts_i_j`.`inquiry_id`,
                    `ts_i_j`.`school_id`,
                    GROUP_CONCAT(DISTINCT `kro`.`share_id` SEPARATOR ',') `share_ids`
                FROM 
                    `kolumbus_accommodations_allocations` `kra` LEFT JOIN
                    `ts_inquiries_journeys_accommodations` `ts_i_j_a` ON
                        `ts_i_j_a`.`id` = `kra`.`inquiry_accommodation_id` AND
                        `ts_i_j_a`.`active` = 1 AND
                        `ts_i_j_a`.`visible` = 1 LEFT JOIN
                    `ts_inquiries_journeys` `ts_i_j` ON
                        `ts_i_j`.`active` = 1 AND
                        `ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
                        `ts_i_j`.`id` = `ts_i_j_a`.`journey_id` LEFT JOIN
                    `ts_inquiries` `ts_i` ON
                        `ts_i`.`id` = `ts_i_j`.`inquiry_id` AND
                        `ts_i`.`active` = 1 LEFT JOIN
                    `ts_inquiries_to_contacts` `ts_i_to_c` ON
                        `ts_i_to_c`.`inquiry_id` = `ts_i`.`id` AND
                        `ts_i_to_c`.`type` = 'traveller' LEFT JOIN
                    `tc_contacts` `tc_c` ON
                        `tc_c`.`id` = `ts_i_to_c`.`contact_id` AND
                        `tc_c`.`active` = 1 LEFT OUTER JOIN
                    `kolumbus_roomsharing` `kro` ON
                        `kro`.`master_id` = `ts_i`.`id`
                WHERE					
                    `kra`.`room_id` = :room_id AND
                    `kra`.`bed` = :bed AND
                    `kra`.`room_id` > 0 AND
                    `kra`.`active` = 1 AND
                    `kra`.`status` = 0 AND
                    `kra`.`from` >= :date
                GROUP BY
                    `kra`.`id`
                ORDER BY
                    `kra`.`from`
                LIMIT 1
            ";

            $aSql = [
                'room_id' => (int)$roomId,
                'bed' => (int)$bed,
                'date' => $currentDate->format('Y-m-d H:i:s'),
            ];

            $aResult = $this->_oDB->getCollection($sSql, $aSql);

            foreach($aResult as $aAllocation) {

                if(
                    $aAllocation['inquiry_id'] > 0 ||
                    $aAllocation['reservation'] !== null
                ) {

                    $aBack[$i] = $aAllocation;

                    $this->addAllocationData($aBack[$i]);

                    $i++;

                }

            }
        }

	    return $aBack;
    }

	public function getBedsWithAllocation($idRoom, $oFrom, $oTo, $__unused_iSchool = 0) {

		$sSql = "
			SELECT
				`room`.*,
			     GROUP_CONCAT(CONCAT (`ts_arcs`.`bed`, '{|}', `ts_arcs`.`status`) SEPARATOR '{||}') `cleaning_status`
			FROM
				`kolumbus_rooms` `room` LEFT JOIN
				`ts_accommodation_rooms_latest_cleaning_status` `ts_arcs` ON 
					`ts_arcs`.`room_id` = `room`.`id`
			WHERE
				`room`.`id` = :id AND
				`room`.`active` = 1
			GROUP BY `room`.`id`	
			LIMIT 1
		";
		$aSql = [
			'id' => (int)$idRoom,
		];
		$aRoom = DB::getPreparedQueryData($sSql,$aSql);
		$aRoom = reset($aRoom);

		// Reinigungsstatus
		if (!empty($aRoom['cleaning_status'])) {
			$aRoom['cleaning_status'] = $this->getCleaningStatusDataFromResult($aRoom['cleaning_status']);
		}

		$aAllocationList = $this->getAllocationOfRoom($idRoom, $oFrom, $oTo);
		return $this->_getBedsInRoom($aRoom, $aAllocationList);

	}
	
	public static function getFreeBeds($idRoom, $oFrom, $oTo, $iSchool = null, $bReturnBeds=false) {
		$oMatching = new Ext_Thebing_Matching();
		return $oMatching->getFreeBedsOfRoom($idRoom, $oFrom, $oTo, $iSchool, false, 0, $bReturnBeds);
	}
	
	public static function between( $iTime, $iFrom ,$iTo ){
		
		if(
			$iTime >= $iFrom && $iTime <= $iTo
		){
			return true;
		} else {
			return false;
		}
		
	}
	
	public function getFreeBedsOfRoom($idRoom, $oFrom, $oTo, $iSchool = null, $bWithoutOwnInquiry = false, $iInquiryId = 0, $bReturnBeds=false) {
		$aFreeBeds = [];

		$aBeds = $this->getBedsWithAllocation($idRoom, $oFrom, $oTo, $iSchool);

		// Raumblockierungen
		$aFamilieBlockingOfRoom = Ext_Thebing_Accommodation_Dates::getBlockingPeriods($idRoom, $oFrom, $oTo);

		//Wenn das Zimmer blockiert ist, ist kein Bett hier mehr frei!
		if(!empty($aFamilieBlockingOfRoom)){
			return 0;
		}

		foreach((array)$aBeds as $aBed){
			
			$bFree = true;

			if(is_array($aBed['allocation'])){

				foreach((array)$aBed['allocation'] as $aAllo){

					if($aAllo['id'] == $this->iIgnoreAllocation){
						continue;
					}

					$oDateFromAllocation = new DateTime($aAllo['from_date']);
					$oDateFromAllocation->setTime(0, 0, 0);

					$oDateUntilAllocation = new DateTime($aAllo['until_date']);
					$oDateUntilAllocation->setTime(0, 0, 0);

					if(
						$oFrom < $oDateUntilAllocation &&
						$oDateFromAllocation < $oTo
					) {
						if(
							(
								$bWithoutOwnInquiry == true && 
								$aAllo['inquiry_id'] == $iInquiryId
							) ||
							$bWithoutOwnInquiry == false
						){
							$bFree = false;
						}
						
					} 
					
				}
			}
			
			if($bFree == true){
				$aFreeBeds[] = $aBed;
			}

		}

		if(!$bReturnBeds) {
			return count($aFreeBeds);
		}

		return $aFreeBeds;
	}
	
	public function getRoomtypeData($iRoomtypeId){

		if(!isset(self::$aCache['room_type'][$iRoomtypeId])) {

			$sSql = "SELECT 
					*,'0' as `allocation`
				FROM
					`kolumbus_accommodations_roomtypes` as `type`
				WHERE
					`type`.`id` = :id AND
					`type`.`active` = 1
			";
			$aSql = array();
			$aSql['id'] = (int)$iRoomtypeId;
			self::$aCache['room_type'][$iRoomtypeId] = DB::getQueryRow($sSql, $aSql);
		
		}

		return self::$aCache['room_type'][$iRoomtypeId];

	}

	public function getRoomtypesOfType($iType) {

		$oSchool = $this->getSchool();
		if(!$oSchool->exist()) {
			$sMsg = 'No school available';
			throw new RuntimeException($sMsg);
		}
		$iSchoolId = $oSchool->id;
		$iType = (int)$iType;

		if(!isset(self::$aCache['roomtypes_of_type'][$iSchoolId][$iType])) {

			$sSql = "
				SELECT 
					`kar`.*,
					'0' as `allocation`
				FROM
					`kolumbus_accommodations_roomtypes` `kar` INNER JOIN
					`ts_accommodation_roomtypes_schools` `ts_ars` ON
						`ts_ars`.`accommodation_roomtype_id` = `kar`.`id` AND
						`ts_ars`.`school_id` = :idSchool
				WHERE
					`kar`.`type` = :type AND
					`kar`.`active` = 1
				GROUP BY
					`kar`.`id`
			";
			$aSql = [
				'idSchool' => $iSchoolId,
				'type' => $iType,
			];
			$aBack = DB::getPreparedQueryData($sSql,$aSql);

			self::$aCache['roomtypes_of_type'][$iSchoolId][$iType] = $aBack;

		}

		return self::$aCache['roomtypes_of_type'][$iSchoolId][$iType];

	}

	protected function getCleaningStatusDataFromResult($sResult) {

		$aStatus = explode('{||}', $sResult);
		$aCleaningStatus = [];

		foreach ($aStatus as $sStatusRow) {
			[$iBed, $sStatus] = explode('{|}', $sStatusRow);
			$aCleaningStatus[$iBed] = $sStatus;
		}

		return $aCleaningStatus;
	}

	public function setFrom(DateTime $oFrom){
		$this->_oFrom	= $oFrom;
		$this->iFrom 	= $oFrom->getTimestamp();
		$this->iFrom = Ext_Thebing_Util::convertUTCDate($this->iFrom);
		$this->sFrom 	= $oFrom->format('Y-m-d');
	}
	
	public function setUntil(DateTime $oUntil){
		$this->_oUntil = $oUntil;
		$this->iTo		= $oUntil->getTimestamp();
		$this->iTo = Ext_Thebing_Util::convertUTCDate($this->iTo);
		$this->sTo		= $oUntil->format('Y-m-d');
	}
	
	protected function strtotimeWithFrom($sString, $sFormat='unix'){
		$oDate = clone $this->_oFrom; 
		return $this->_getModifiedDate($oDate, $sString, $sFormat);
	}

	protected function strtotimeWithUntil($sString, $sFormat='unix') {
		$oDate = clone $this->_oUntil; 
		return $this->_getModifiedDate($oDate, $sString, $sFormat);
	}
	
	protected function _getModifiedDate($oDate, $sString, $sFormat='unix') {

		$oDate->modify($sString);
		if($sFormat == 'unix') {
			return $oDate->getTimestamp();
		} else {
			return $oDate->format('Y-m-d H:i:s');
		}
		
	}
	
	public function getDisplayDays($bAddDaysBeforeAndAfter=false) {
		
		$oDisplayFrom = clone $this->_oFrom;
		$oDisplayUntil = clone $this->_oUntil;

		if($bAddDaysBeforeAndAfter) {
			$oDisplayFrom->sub(new DateInterval('P7D'));
			$oDisplayUntil->add(new DateInterval('P7D'));
		} else {
			$oDisplayUntil->add(new DateInterval('P1D'));
		}

		$aDays	= array();
		$oDiv	= $oDisplayUntil->diff($oDisplayFrom);
		$iDays	= $oDiv->days;
		for($i = 1; $i <= $iDays; $i++){
			$aDay = $this->getDayData($oDisplayFrom);
			$aDays[] = $aDay;
			$oDisplayFrom->add(new DateInterval('P1D'));
		}
		
		return $aDays;
	}

	protected function _getRoomList($idAccommodation, $iRoomtypeId, $iWithOtherTypes = 1, $iRoomId=false) {
		global $_VARS;
		
		if(
			isset($_VARS['matching_debug_provider'][$idAccommodation])
		) {
			$this->bDebug = true;
		}
		
		$aRoomtypes1 = $this->getRoomtypesOfType(0);
		$aRoomtypes2 = $this->getRoomtypesOfType(1);
		$aRoomtypes3 = $this->getRoomtypesOfType(2);

		$aSql						= array();
		$aSql['type_id']			= $iRoomtypeId;
		$aSql['idAccommodation']	= $idAccommodation;
		$aSql['valid_until'] = $this->_oFrom->format('Y-m-d');

		// Raumtype
		$oRoomType = Ext_Thebing_Accommodation_Roomtype::getInstance((int)$iRoomtypeId);
		// RaumKategorie (0 = EZ, 1 = DZ, 2 = MZ)
		$iRoomCategory = $oRoomType->type;
		
		$sTemp = " AND (";
		$sWhereAddon = " ";

		$aRoomtypeDataOfInquiry = $this->getRoomtypeData($this->oAccommodation->roomtype_id);
		
	
		if($iWithOtherTypes == 1){	
			foreach($aRoomtypes1 as $aData){
				$sWhereAddon .= $sTemp." `room`.`type_id` = ".$aData['id']." ";
				$sTemp = " OR ";
			}
			foreach($aRoomtypes2 as $aData){
				$sWhereAddon .= $sTemp." `room`.`type_id` = ".$aData['id']." ";
				$sTemp = " OR ";
			}
			foreach($aRoomtypes3 as $aData){
				$sWhereAddon .= $sTemp." `room`.`type_id` = ".$aData['id']." ";
				$sTemp = " OR ";
			}
		}
		
		$sWhereAddon .= " ) ";
		
		if(($iRoomId !== false) && ($iRoomId != 0)) {
			$sWhereAddon .= " AND `room`.`id` = :room_id ";
			$aSql['room_id'] = (int)$iRoomId;
		}
		
		$sSql = "SELECT 
					`room`.*,
					'0' as `allocation`,
					`room_category`.`type` `type_category` # 0 = EZ, 1 = DZ, 2 = MZ
				FROM
					`kolumbus_rooms` as `room` INNER JOIN
					`kolumbus_accommodations_roomtypes` as `room_category` ON
						`room_category`.`id` = `room`.`type_id` AND
						`room_category`.`active` = 1 AND
						(
							`room_category`.`valid_until` >= NOW() OR
							`room_category`.`valid_until` = '0000-00-00' OR
							`room`.`id` IN(:active_rooms)
						)
				WHERE
					`room`.`accommodation_id` = :idAccommodation
					".$sWhereAddon." AND
					`room`.`active` = 1 AND (
						(
							`room`.`valid_until` >= NOW() OR
							`room`.`valid_until` = '0000-00-00' OR
							`room`.`id` IN(:active_rooms)
						) OR (
							`room`.`valid_until` = 0 OR
							`room`.`valid_until` > :valid_until
						)
					)
				ORDER BY
					##(IF(`room`.`type_id`=".(int)$iRoomtypeId.",1,0)) DESC,
					`room`.`position` ASC
			";
		
		$aSql['active_rooms']	= (array)$this->aActiveRooms;

		$aBack	= $this->_oDB->getCollection($sSql,$aSql);

		$i = 0;
		foreach($aBack as $aRoom){

			$this->debugMatchingCriterions($aRoom, 'START: ROOM');

			$sSql = "SELECT 
						`kra`.*,
						kra.`from` `date_from`,
						kra.`until` `date_until`,
						UNIX_TIMESTAMP(kra.`from`) `from`, /* TODO: Entfernen */
						UNIX_TIMESTAMP(kra.`until`) `until`, /* TODO: Entfernen */
						UNIX_TIMESTAMP(kra.`until`) `to`, /* TODO: Entfernen */
						`ts_i_j`.`inquiry_id`,
						`ts_i_j`.`school_id`,
						`ts_i_j_a`.`roomtype_id` `roomtype_id`,
						GROUP_CONCAT(DISTINCT `kro`.`share_id` SEPARATOR ',') `share_ids`,
						`tc_c`.`gender` `gender`
					FROM
						`kolumbus_accommodations_allocations` as `kra` LEFT JOIN
						`ts_inquiries_journeys_accommodations` as `ts_i_j_a` ON
							`ts_i_j_a`.`id` = `kra`.`inquiry_accommodation_id` AND
							`ts_i_j_a`.`active` = 1 AND
							`ts_i_j_a`.`visible` = 1 LEFT JOIN
						`ts_inquiries_journeys` `ts_i_j` ON
							`ts_i_j`.`id` = `ts_i_j_a`.`journey_id` AND
							`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
							`ts_i_j`.`active` = 1 LEFT JOIN
						`ts_inquiries` as `ts_i` ON
							`ts_i`.`id` = `ts_i_j`.`inquiry_id` AND
							`ts_i`.`active` = 1 LEFT JOIN
						`ts_inquiries_to_contacts` `ts_i_to_c` ON
							`ts_i_to_c`.`inquiry_id` = `ts_i`.`id` AND
							`ts_i_to_c`.`type` = 'traveller' LEFT JOIN
						`tc_contacts` `tc_c` ON
							`tc_c`.`id` = `ts_i_to_c`.`contact_id` AND
							`tc_c`.`active` = 1 LEFT OUTER JOIN
						`kolumbus_roomsharing` `kro` ON
							`kro`.`master_id` = `ts_i_j`.`inquiry_id`
					WHERE
						`kra`.`room_id` = :room_id AND
						`kra`.`room_id` > 0 AND
						`kra`.`active` = 1 AND
						`kra`.`status` = 0 AND
						(
							`kra`.`from` <= :to AND
							`kra`.`until` >= :from
						)
					GROUP BY
						kra.id
					ORDER BY 
						`kra`.from
			";
			$aSql = array();
			$aSql['room_id'] = (int)$aRoom['id'];
			// TODO: Achtung, die 7 Tage sind auch im JS abgelegt
			$aSql['from']	= $this->strtotimeWithFrom("- 7 days", 'date'); 
			$aSql['to']		= $this->strtotimeWithUntil("+ 7 days", 'date'); 
			
			$aResult = $this->_oDB->getCollection($sSql,$aSql);

			#$this->debugMatchingCriterions($aResult, 'Allocations', true);

			// Wird auf true gesetzt wenn NICHT zuweisbar
			$bFalse = false;
			$notAssignableReasons = [];

			// Anzahl freie Betten pro Raum
			$iFreeBeds = 0;
			
			$oAllocation = new Ext_Thebing_Allocation();

			$aInactiveAllocations = $this->oInquiry->getInactiveAllocations($this->oAccommodation->id);

			$aPeriods = array();
			
			if(
				!empty($aInactiveAllocations) &&
				$this->bSkipAllocationCheck == false // Wenn man eine Zuweisung verschiebt (Flag == true) dann darf NUR der zu verschiebende Zeitpunkt geprüft werden!
			){
			    /* */
				foreach($aInactiveAllocations as $aInactiveAllocation){

					$oDateFrom = new DateTime($aInactiveAllocation['date_from']);
					$oDateUntil = new DateTime($aInactiveAllocation['date_until']);

					// Prüfen ob eine der offenen Zuweisungen noch freie Betten hat
					$iFreeBeds += $this->getFreeBedsOfRoom($aRoom['id'], $oDateFrom, $oDateUntil);

					$aPeriods[] = [
						'from'=>$oDateFrom, 
						'to'=>$oDateUntil
					];
					
				}
		
			} else {
				// Wenn es keine Inactiven gibt aber Aktive dann kann es nur komplett zugewisen sein!
				// dann also nicht erneut zuweisbar machen
				$aActiveAllocation = Ext_Thebing_Allocation::getAllocationByInquiryId($this->oInquiry->id,$this->oAccommodation->id,1);
	
				if(
					$this->bSkipAllocationCheck == false &&
					!empty($aActiveAllocation) && 
					$iRoomId <= 0
				) {
					$bFalse = true;
					$notAssignableReasons[] = self::t('Schon zugewiesen');
				}

				$aPeriods[] = [
					'from'=>$this->_oFrom, 
					'to'=>$this->_oUntil
				];

			    $iFreeBeds = $this->getFreeBedsOfRoom($aRoom['id'], $this->_oFrom , $this->_oUntil);

			}

			$this->debugMatchingCriterions($bFalse, 'Schon zugewiesen');

			if($iFreeBeds <= 0){
				$bFalse = true;
				$notAssignableReasons[] = self::t('Keine freien Betten');
			}

			$this->debugMatchingCriterions($bFalse, 'Freie Betten <= 0');
					
			$aAllocation_ = $aAllocationsSamePeriod = array();
			$ii=0;
			
			$sGender = "";

			$oFrom = new DateTime($aSql['from']);
			$oUntil = new DateTime($aSql['to']);

			$aFamilieBlockingOfRoom = Ext_Thebing_Accommodation_Dates::getBlockingPeriods($aRoom['id'], $oFrom, $oUntil);
			$aFamilieBlockingOfRoom = $this->addBlockingDayData($aFamilieBlockingOfRoom);
			$aRoom['blocking'] = $aFamilieBlockingOfRoom;
			
//			// START :: Schauen ob der Raum von der Schule aus Blockiert wurde
//			foreach($aFamilieBlockingOfRoom as $aFamilieBlocking){
//				
//				$oWDDateFamilyBlockingFrom 	= new DateTime($aFamilieBlocking['from']);
//				$oWDDateFamilyBlockingUntil = new DateTime($aFamilieBlocking['until']);
//					
//				// Ein Tag abziehen, da matching am selben Tag von blockierungen möglich sein muss
//				$oWDDateFamilyBlockingFrom->add(new DateInterval('P1D'));
//				$oWDDateFamilyBlockingUntil->sub(new DateInterval('P1D'));
//				
//				foreach($aPeriods as &$aPeriod) {
//					
//					$bCheck = \Core\Helper\DateTime::checkDateRangeOverlap($aPeriod['from'], $aPeriod['to'], $oWDDateFamilyBlockingFrom, $oWDDateFamilyBlockingUntil);
//
//					// Auf überschneidungen prüfen
//					if($bCheck) {
//						$aPeriod['blocked'] = true; 
//						#break; // Kein Break, da sonst nicht alle Perioden gechecked werden können und der raum so nie blockiert werden könnte
//					}
//				}
//			}
//
//			// Nur wenn alle Perioden blockiert sind, ist der Raum nicht verfügbar
//			$iBlockedCounter = 0;
//			foreach($aPeriods as $aPeriod) {
//				if($aPeriod['blocked'] === true) {
//					$iBlockedCounter++;
//				}
//			}
//
//			if($iBlockedCounter == count($aPeriods)) {
//				$bFalse = true;
//				$this->debugMatchingCriterions($bFalse, '['.$aRoom['id'].'] Blockiert');
//			}

			// END

			// START check if Customer gender matches with room-gender-setting /////////////////

			$this->checkIfGenderMatchesWithRoom($notAssignableReasons, $bFalse, $aRoom);

			// END
				
			// alle Zuweisungen dieses Raums
			$iAlloCount = 0;
			// alle DZ Zuweisungen dieses Raums
			$iAlloDZCount = 0;
			// Männer dieses Raums
			$aAlloMale = array();
			// Frauen dieses Raums
			$aAlloFemale = array();
			// Zeiträume der Zuweisungen
			$aAlloTimes = array();
			
			//Nur gleiche InquiryAccommodations zählen, falls geteilt ist, nicht als 2 Allocations betrachten
			$aInquiryAccommodations = array();

			// Schüler die noch in dem Zimmer liegen
			foreach($aResult as $key => $aAllocation) {

				$this->addReservationData($aAllocation);

				// In der DB sind sowieso alle gespeicherten Zeiten lokal und nicht UTC
				$oDateFrom = new DateTime($aAllocation['date_from']);
				$oDateUntil = new DateTime($aAllocation['date_until']);
				
				$aTypeDataAlloCustomer = $this->getRoomtypeData($aAllocation['roomtype_id']);

				$bSkipAllocationCheck = false;
				// Alle Zuweisungen im Zuweisungszeitraum holen
				// Da nur die Zuweisungen gecheckt werden dürfen bei denen es nicht schon eine Zuweisung
				// gibt. (Das hatte Probleme gemacht bei zerschnittenen Buchungen)
				$aFilter = array();
				$aFilter['from']		= $oDateFrom->format('Y-m-d');
				$aFilter['until']		= $oDateUntil->format('Y-m-d');

				// Hier darf NUR nach Zuweisungen für die gleiche Unterkunftsbuchung gesucht werden
				$aFilter['journey_accommodations'] = [$this->oAccommodation->id];
				
				$aMatchedAllocations = $this->oInquiry->getAllocations($aFilter); // T-3998 (altes Ticketsystem)
		
				// Im Buchungszeitraum vorhandene Zuweisungen
				//warum wird bei aResult iFrom 1 Woche verkürzt und iTo eine Woche verlängert,
				//für welche Überprüfung ist es relevant? hier macht es auf jeden fall keinen sinn...
				//die räume sind nur belegt, wenn in unser buchungszeitraum jemand zutrifft... 
				if(
					$oDateFrom < $this->_oUntil &&
					$oDateUntil > $this->_oFrom &&
					$this->oInquiry->id != $aAllocation['inquiry_id'] && // Da es Nie zeitgleich mehrere Zuweisungen geben darf dess selben Schülers, muss das hier auch ausgeschlossen werden
					(
						empty($aMatchedAllocations) ||
						$iRoomId > 0					// Ist > 0 Wenn man eine Zuweisung verschiebt
					)
				){ 
					if(
						!in_array($aAllocation['inquiry_accommodation_id'],$aInquiryAccommodations)
					){
						/* 
							Diese Lösung ist leider noch nicht komplett richtig, für diesen Fall würde es nicht klappen
							
							Bett1 I----------------------------I
							Zuw 1   I------------------------I
							
							Bett2 I----------------------------I
							Zuw 2    I------I
							Zuw 3                I------I
							
							
							Dann würde der Counter für Zuw2 & Zuw3 doppelt gezählt werden, es ist auf jeden Fall richtiger als vorher (#4674),
							um es 100 Prozent richtig zu machen müsste man die Schleife etc alles anders aufbauen, ein $aBeds array aufbauen
							und die Zuweisungen in die Betten verteilen und unten in der Bedingungen mit dem Kommentar "Wenn DZ gebuch ist und schon 2 allo hat wird diese ausgeblendet"
							die Anzahl der $aBeds überprüfen statt $iAlloCount
						*/
						if(!empty($aAlloTimes))
						{
							$bAddAllCount = false;
							
							foreach($aAlloTimes as $aTimes)
							{
								if(
									$aTimes['from'] < $oDateUntil &&
									$aTimes['until'] > $oDateFrom
								)
								{
									$bAddAllCount = true;
								}
							}
						}
						else
						{
							$bAddAllCount = true;
						}
						
						if($bAddAllCount)
						{
							$iAlloCount++;
						}
						
						$aInquiryAccommodations[] = $aAllocation['inquiry_accommodation_id'];
					}

					
					if(is_array($aTypeDataAlloCustomer)){
						
						// Doppelzimmerzuweisungen
						if($aTypeDataAlloCustomer['type'] == 1){
							$iAlloDZCount++;
						}

						// Wenn in dem Raum schon eine Einzelzimmer Buchung liegt, darf hier niemand sonst rein
						if($aTypeDataAlloCustomer['type'] == 0){
							$bFalse = true;
							$notAssignableReasons[] = self::t('Einzelzimmer-Belegung');
							$this->debugMatchingCriterions($bFalse, 'EZ');
						}
					}

					// Geschlechter diesen Raums
					if($aAllocation['gender'] == 1){
						$aAlloMale[] = $aAllocation['inquiry_id'];
					}else{
						$aAlloFemale[] = $aAllocation['inquiry_id'];
					}
				} else {
					// Müsste hier stimmen, da sich die Prüfung auf bereits zugewiesene Kunden NUR auf welche bezieht
					// die tatsächlich in der selben zeit im Raum liegen.
					$bSkipAllocationCheck = true;
				}

				$oInquiryAllocation		= Ext_TS_Inquiry::getInstance($aAllocation['inquiry_id']);
				$oCustomerAllocation	= $oInquiryAllocation->getCustomer();
						
				if(!$bSkipAllocationCheck){
					// Zusammenreisende Schüler
					$aRoomSharing = explode(',', $aAllocation['share_ids']);


					// Wenn DZ gebuch ist und schon 2 allo hat wird diese ausgeblendet
					if(
						is_array($aRoomtypeDataOfInquiry) && 
						$aRoomtypeDataOfInquiry['type'] == 1 &&
						$iAlloCount > 1
					){
						$bFalse = true;
						$notAssignableReasons[] = self::t('Doppelzimmer-Belegung');
						$this->debugMatchingCriterions($bFalse, 'DZ');
					}           

					// wenn MZ  gebucht ist,  aber schon 1-2 DZ allos hat wird diese ausgeblendet
					if(         
						(is_array($aRoomtypeDataOfInquiry) && $aRoomtypeDataOfInquiry['type'] == 2) &&
						$iAlloDZCount > 0 &&
						$iAlloCount >= 2
					){          
						$bFalse = true;
						$notAssignableReasons[] = self::t('Doppelzimmer-Belegung');
						$this->debugMatchingCriterions($bFalse, 'MZ');
					}					                    

					// START :: inactive Belegungen (Zerschneidungen) prüfen
					if(!empty($aInactiveAllocations)){
						$bHavePlaceForParts = false;
						foreach($aInactiveAllocations as $aInactiveAllocation){

							// TODO: Timestamp-Mist entfernen
							if( 
								(
									(self::between($aAllocation['from'],$aInactiveAllocation['from'],$aInactiveAllocation['to']-86400)) ||
									(self::between($aAllocation['to'],$aInactiveAllocation['from']+86400,$aInactiveAllocation['to'])) ||
									(self::between($aInactiveAllocation['from']+86400,$aAllocation['from'],$aAllocation['to'])) ||
									(self::between($aInactiveAllocation['to']-86400,$aAllocation['from'],$aAllocation['to']))
								) && 
								is_object($oCustomerAllocation)
							){  
								/* 
								$sGender = $oCustomerAllocation->getField('ext_6');				
								Die Geschlechtsüberprüfung sollte weiter unten in checkRoomSharingGender() erfolgen
								if($sGender != $this->oCustomer->getField('ext_6')){
									$bFalse = true;
									$this->debugMatchingCriterions($bFalse, 'Geschlecht');	
								}
								*/

								if($oCustomerAllocation->id != $this->oCustomer->id){
								// Wenn EZ gebucht ist und schon eine Allo hat wird diese ausgeblendet
									if( 
										(is_array($aRoomtypeDataOfInquiry) && $aRoomtypeDataOfInquiry['type'] == 0) || 
										(is_array($aTypeDataAlloCustomer) && $aTypeDataAlloCustomer['type'] == 0)
									){
										$bFalse = true;
										$notAssignableReasons[] = self::t('Einzelzimmer-Belegung');
										$this->debugMatchingCriterions($bFalse, 'EZ');	
									}
								} 

							} else {
								$bHavePlaceForParts = true;
							}   


						}       

						if($bHavePlaceForParts == true){
							$bFalse = false;
						}       

					// ENDE			
					} else {    

						// TODO: Timestamp-Mist entfernen
						if(     
							(   
								(self::between($aAllocation['from'],$this->iFrom,$this->iTo-86400)) ||
								(self::between($aAllocation['to'],$this->iFrom+86400,$this->iTo)) ||
								(self::between($this->iFrom+86400,$aAllocation['from'],$aAllocation['to'])) ||
								(self::between($this->iTo-86400,$aAllocation['from'],$aAllocation['to']))
							) && 
							is_object($oCustomerAllocation)
						) {      
							
							if($oCustomerAllocation->exist()) {
								$sGender = $oCustomerAllocation->gender;
							} else {
								$sGender = $aAllocation['gender'];
							}

							if( 
								$sGender != $this->oCustomer->gender &&
								!in_array($this->oInquiry->id, $aRoomSharing) // Zusammenreisende Schüler ignorieren
							) {
								$bFalse = true;
								$notAssignableReasons[] = self::t('Geschlecht');
								$this->debugMatchingCriterions($bFalse, 'Geschlecht '.__LINE__);	
							}   

							if( 
								$oCustomerAllocation->id != $this->oCustomer->id &&
								!in_array($this->oInquiry->id, $aRoomSharing) // Zusammenreisende Schüler ignorieren
							){  
							// Wenn EZ gebucht ist und schon eine Allo hat wird diese ausgeblendet
								if( 
									(is_array($aRoomtypeDataOfInquiry) && $aRoomtypeDataOfInquiry['type'] == 0) || 
									(is_array($aTypeDataAlloCustomer) && $aTypeDataAlloCustomer['type'] == 0)
								){

									$bFalse = true;
									$notAssignableReasons[] = self::t('Einzelzimmer-Belegung');
									$this->debugMatchingCriterions($bFalse, 'EZ');	
								}

							}   

						}			
					}
				}

				if(
					$aAllocation['inquiry_id'] > 0 ||
					$aAllocation['reservation'] !== null
				) {

					$aAllocation_[$ii] = $aAllocation;

					// Einträge ohne Daten können übersprungen werden
					$aInquiryTempCheckData = $oInquiryAllocation->getArray();
					if(empty($aInquiryTempCheckData)) {
						unset($aAllocation_[$ii]);
						continue;
					}
					
					$this->addAllocationData($aAllocation_[$ii]);

					if(!$bSkipAllocationCheck) {
						$aAllocationsSamePeriod[$ii] = $aAllocation_[$ii];
					}
					
					if($oCustomerAllocation != false) {
						$aAllocation_[$ii]['halfStartDay'] = 0;
						$aAllocation_[$ii]['halfEndDay'] = 0;
						$ii++;
					}

				} 
				
				$aAlloTimes[] = array(
					'from' => $oDateFrom,
					'until' => $oDateUntil,
				);
				            
			}

			// ---------------------------------------------------------------------------

			// Prüfen ob das Geschlecht der zugewiesenen Kunden mit den Geschlechtern der Zusammenreisenden zusammenpast
			if($bFalse == false){
				$bFalse = $this->_checkRoomSharing($aAllocationsSamePeriod, 'gender');
				if($bFalse === true) {
					$notAssignableReasons[] = self::t('Geschlecht');
				}
			}

			if(count($aAllocation_)<=0){
				$aAllocation_ = 0;
			}
			
			if($iRoomtypeId != $aRoom['type_id']){
				$aRoom['wrongType'] = 1;
				            
				// Wenn der Raumtyp nicht passt darf man nicht zuweisen es sei denn
				// der Raum ist Minderwertiger
				$bCheckRoomCategory = $this->checkRoomCategory($iRoomCategory, $aRoom['type_category']);
	                        
				if(!$bCheckRoomCategory){
					$bFalse = true;
					$notAssignableReasons[] = self::t('Raumart');
					#continue; // könnte man einblenden :)
				}           
			} else {
				$aRoom['wrongType'] = 0;
			}      

			// Wenn das Zimmer nicht passt zeige es nicht an
			if($bFalse == true){
				$aRoom['isAssignable'] = 0;
				$aRoom['not_assignable_reasons'] = implode(', ', array_unique($notAssignableReasons));
				//continue; 
			} else {        
				$aRoom['isAssignable'] = 1;
			}

			// Alle aktiven Zuweisungen dieses Raums speichern
			$aRoom['all_allocations'] = $iAlloCount;

			$aBack_[$i] = $aRoom;
			$aBack_[$i]['allocation'] = $aAllocation_;
			                
			$i++;           
                            
		}                 

		if(
			isset($_VARS['matching_debug_provider'][$idAccommodation])
		) {
			$this->bDebug = false;
		}

		return $aBack_;
	}

	/**
	 * @param Ext_TS_Inquiry_Journey_Accommodation $oInquiryAccommodation
	 * @param Ext_TS_Inquiry_Contact_Traveller $oCustomer
	 * @return string
	 */
	protected function getAllocationLabel(Ext_TS_Inquiry_Journey_Accommodation $oInquiryAccommodation, Ext_TS_Inquiry_Contact_Traveller $oCustomer, Ext_Thebing_Accommodation_Allocation $allocation) {

		$oSchool = $this->getSchool();
		$sLabel = $oSchool->accommodation_allocation_label;

		if(empty($sLabel)) {
			$sLabel = '{surname}, {firstname_capital}., {age}, {room}, {language}, {nationality}';
		}

		$aRoomtypeTemp = $this->getRoomtypeData($oInquiryAccommodation->roomtype_id);

		$oInquiry = $oCustomer->getInquiry();

		if($oInquiry->hasGroup()) {
			$oGroup = $oInquiry->getGroup();
			$sGroupName = $oGroup->getShortName();
		} else {
			$sGroupName = '';
		}

		$matchingData = $oInquiry->getMatchingData();

		$gender = (int)$oCustomer->gender;

		$aPossiblePlaceholders = array(
			'firstname' => $oCustomer->firstname,
			'firstname_capital' => mb_substr($oCustomer->firstname, 0, 1),
			'student_number' => $oCustomer->getCustomerNumber(),
			'surname' => $oCustomer->lastname,
			'age' => $oCustomer->getAge(),
			// TODO ist das korrekt wenn der Cache $this->_sInterfaceLanguage nicht berücksichtigt
			'room' => $aRoomtypeTemp['short_'.$this->_sInterfaceLanguage],
			'roomtype' => $aRoomtypeTemp['short_'.$this->_sInterfaceLanguage],
			'language' => $oCustomer->language,
			'nationality' => $oCustomer->nationality,
			'group' => $sGroupName,
			// Die Icons werden auch in einem title-Attribut verwendet
			'gender' => self::getGenderIcon($gender),
			'accommodation' => $oInquiryAccommodation->getAccommodationName(true),
			'meal' => $oInquiryAccommodation->getMeal()->getName($this->_sInterfaceLanguage, true),
			'comment' => $allocation->comment,
			'matching_note' => $matchingData->acc_comment,
			'matching_additional_note' => $matchingData->acc_comment2,
		);

		foreach($aPossiblePlaceholders as $sPlaceHolder=>$sValue) {
			if(empty($sValue)) {
				$sValue = '-';
			}
			$sLabel = str_replace('{'.$sPlaceHolder.'}', $sValue, $sLabel);
		}

		return $sLabel;
	}

	protected function getReservationTooltip($aAllocation): string {

		$aTooltip = [];
		if ((int)$aAllocation['category_id'] > 0) {
			$aTooltip[] = Ext_Thebing_Accommodation_Category::getInstance($aAllocation['category_id'])->getShortName($this->_sInterfaceLanguage);
		}
		if ((int)$aAllocation['roomtype_id'] > 0) {
			$aTooltip[] = Ext_Thebing_Accommodation_Roomtype::getInstance($aAllocation['roomtype_id'])->getShortName($this->_sInterfaceLanguage);
		}
		if (!empty($aAllocation['meal_id'])) {
			$aTooltip[] = \Ext_Thebing_Accommodation_Meal::getInstance($aAllocation['meal_id'])->getName($this->_sInterfaceLanguage, true);
		}
		if (!empty($aAllocation['age'])) {
			$aTooltip[] = self::getAgeOptions()[$aAllocation['age']] ?? '';
		}
		if (isset($aAllocation['gender'])) {
			$aTooltip[] = self::getGenderIcon((int)$aAllocation['gender']);
		}
		if (isset($aAllocation['reservation_date'])) {
			$format = new Ext_Thebing_Gui2_Format_Date();
			$aTooltip[] = L10N::t('Gültig bis').': '.$format->format($aAllocation['reservation_date']);
		}

		return (!empty($aTooltip))
			? sprintf('%s - %s', $aAllocation['reservation_comment'], implode(', ', $aTooltip))
			: $aAllocation['reservation_comment'];
	}

	protected function _getOtherRoomList($idAccommodation,$iRoomtypeId,$bAge,$iAge,$bGender,$iGender, $bNationality, $bMotherTongue, $iMotherTongue, $iWithOtherTypes = 1, $iRoomId=false, $bParking = false) {
		global $_VARS;

		if(
			isset($_VARS['matching_debug_provider'][$idAccommodation])
		) {
			$this->bDebug = true;
		}
		
		$bOnlyCorrectRooms = $this->_bOnlyCorrectRooms;

		$aRoomtypes1 = $this->getRoomtypesOfType(0);
		$aRoomtypes2 = $this->getRoomtypesOfType(1);
		$aRoomtypes3 = $this->getRoomtypesOfType(2);

		// Familie
		$oProvider = Ext_Thebing_Accommodation::getInstance((int)$idAccommodation);
		
		// Raumtype
		$oRoomType = Ext_Thebing_Accommodation_Roomtype::getInstance((int)$iRoomtypeId);
		// RaumKategorie (0 = EZ, 1 = DZ, 2 = MZ)
		$iRoomCategory = $oRoomType->type;

		$aSql = array();    
		$aSql['type_id']		= (int) $iRoomtypeId;
		$aSql['provider_id']	= (int) $oProvider->id;
		$aSql['active_rooms']		= (array)$this->aActiveRooms;
		$aSql['valid_until']	= $this->_oFrom->getTimestamp();

		$sTemp = " AND (";  
		$sWhereAddon = " "; 

		//$aRoomtypeDataOfInquiry = $this->getRoomtypeData($this->oAccommodation->accommodation_id);
		$aRoomtypeDataOfInquiry = $this->getRoomtypeData($this->oAccommodation->roomtype_id);

		if($iWithOtherTypes == 1){	
			foreach($aRoomtypes1 as $aData){
				$sWhereAddon .= $sTemp." `k_r`.`type_id` = ".$aData['id']." ";
				$sTemp = " OR ";
			}               
			foreach($aRoomtypes2 as $aData){
				$sWhereAddon .= $sTemp." `k_r`.`type_id` = ".$aData['id']." ";
				$sTemp = " OR ";
			}               
			foreach($aRoomtypes3 as $aData){
				$sWhereAddon .= $sTemp." `k_r`.`type_id` = ".$aData['id']." ";
				$sTemp = " OR ";
			}                             
		} else {
			$sWhereAddon .= " AND `k_r`.`type_id` = :type_id ";
		}                   
                            
		if($iWithOtherTypes == 1) {
			$sWhereAddon .= " ) ";
		}                   

		if($iRoomId !== false) {
			$sWhereAddon .= " AND `k_r`.`id` = :room_id ";
			$aSql['room_id'] = (int)$iRoomId;
		}                   
				            
		$sSql = "SELECT     
					`k_r`.*,      
					'0' as `allocation`,
					`room_category`.`type` `type_category`, /* 0 = EZ, 1 = DZ, 2 = MZ */
					GROUP_CONCAT(CONCAT (`ts_arcs`.`bed`, '{|}', `ts_arcs`.`status`) SEPARATOR '{||}') `cleaning_status`
				FROM        
					`kolumbus_rooms` as `k_r` INNER JOIN
					`kolumbus_accommodations_roomtypes` as `room_category` ON
						`room_category`.`id` = `k_r`.`type_id` AND
						`room_category`.`active` = 1 AND
						(
							`room_category`.`valid_until` >= NOW() OR
							`room_category`.`valid_until` = '0000-00-00' OR
							`k_r`.`id` IN(:active_rooms)
						) LEFT JOIN
					ts_accommodation_rooms_latest_cleaning_status `ts_arcs` ON 
					    `ts_arcs`.`room_id` = `k_r`.`id`
				WHERE       
					`k_r`.`accommodation_id` = :provider_id
					".$sWhereAddon." AND
					`k_r`.`active` = 1 AND	(
						`k_r`.`valid_until` = 0 OR
						UNIX_TIMESTAMP(`k_r`.`valid_until`) > :valid_until
					)	
				GROUP BY
					`k_r`.`id`	
				ORDER BY    
					##(IF(`k_r`.`type_id`=".(int)$iRoomtypeId.",1,0)) DESC,
					`k_r`.`position` ASC
			";              
		
		$aBack = DB::getPreparedQueryData($sSql,$aSql);

		$this->debugMatchingCriterions($aBack, 'Rooms', true);
                            
		$i = 0;             
		foreach($aBack as $aRoom) {
                            
			$sSql = "SELECT 
						`kra`.*,
						`kra`.`from` `date_from`,
						`kra`.`until` `date_until`,
						UNIX_TIMESTAMP(kra.`from`) `from`, /* TODO: NICHT MEHR BENUTZEN UND ENTFERNEN */
						UNIX_TIMESTAMP(kra.`until`) `until`, /* TODO: NICHT MEHR BENUTZEN UND ENTFERNEN */
						UNIX_TIMESTAMP(kra.`until`) `to`, /* TODO: NICHT MEHR BENUTZEN UND ENTFERNEN */
						`kra`.`id`,
						`ts_i_j_a`.`roomtype_id` `roomtype_id`,
						GROUP_CONCAT(DISTINCT `kro`.`share_id` SEPARATOR ',') `share_ids`,
						`tc_c`.`gender` `gender`,
						`ts_i_j`.`inquiry_id`,
						`ts_i_j`.`school_id`
					FROM    
						`kolumbus_accommodations_allocations` as `kra` LEFT JOIN
						`ts_inquiries_journeys_accommodations` as `ts_i_j_a` ON
							`ts_i_j_a`.`id` = `kra`.`inquiry_accommodation_id` AND
							`ts_i_j_a`.`active` = 1 AND
							`ts_i_j_a`.`visible` = 1 LEFT JOIN
						`ts_inquiries_journeys` `ts_i_j` ON
							`ts_i_j`.`id` = `ts_i_j_a`.`journey_id` AND
							`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
							`ts_i_j`.`active` = 1 LEFT JOIN
						`ts_inquiries` as `ts_i` ON
							`ts_i`.`id` = `ts_i_j`.`inquiry_id` AND
							`ts_i`.`active` = 1 LEFT JOIN
						`ts_inquiries_to_contacts` `ts_i_to_c` ON
							`ts_i_to_c`.`inquiry_id` = `ts_i`.`id` AND
							`ts_i_to_c`.`type` = 'traveller' LEFT JOIN
						`tc_contacts` `tc_c` ON
							`tc_c`.`id` = `ts_i_to_c`.`contact_id` AND
							`tc_c`.`active` = 1 LEFT JOIN
						`kolumbus_roomsharing` `kro` ON
							`kro`.`master_id` = `ts_i`.`id`
					WHERE   
						`kra`.`room_id` = :room_id AND
						`kra`.`room_id` > 0 AND
						`kra`.`active` = 1 AND
						`kra`.`status` = 0 AND
						(   
							`kra`.`from` <= :to AND
							`kra`.`until` >= :from
						)
					GROUP BY
						kra.id
					ORDER BY 
						`kra`.`from`
			";              
			$aSql = array();
			$aSql['room_id'] = (int)$aRoom['id']; 
			// Zeitraum wird erweitert, damit auch angrenzende Zuweisungen in der Übersicht angezeigt werden
			$aSql['from'] = $this->strtotimeWithFrom("- 7 days", 'date'); // $this->iFrom-(84600*14);
			$aSql['to'] = $this->strtotimeWithUntil("+ 7 days", 'date'); // $this->iTo+(84600*14);
			$aResult = (array)DB::getPreparedQueryData($sSql,$aSql);
                            
			$this->debugMatchingCriterions($aResult, 'Allocations', true);

			// Wird auf true gesetzt wenn NICHT zuweisbar
			$bFalse = false;
			$notAssignableReasons = [];

			// Reinigungsstatus
			if (!empty($aRoom['cleaning_status'])) {
				$aRoom['cleaning_status'] = $this->getCleaningStatusDataFromResult($aRoom['cleaning_status']);
			}

			// schauen ob freie betten da sind
			$aInactiveAllocations = $this->oInquiry->getInactiveAllocations($this->oAccommodation->id);

			$aPeriods = array();
			if(!empty($aInactiveAllocations)){
			    /* */       
				            
				$iFreeBeds = 0;
				
				foreach($aInactiveAllocations as $aInactiveAllocation) {

					$iFreeBeds_ = (int)$this->getFreeBedsOfRoom($aRoom['id'], new DateTime($aInactiveAllocation['date_from']), new DateTime($aInactiveAllocation['date_until']));					        

					if((int)$iFreeBeds_ > (int)$iFreeBeds){
						$iFreeBeds = (int)$iFreeBeds_;
					}       
					        
					$aPeriods[] = array(
						'from'=>new DateTime($aInactiveAllocation['date_from']),
						'to'=>new DateTime($aInactiveAllocation['date_until'])
					);
					        
				}           
				            
			} else {        
				            
				// Wenn es keine Inactiven gibt aber Aktive dann kann es nur komplett zugewisen sein!
				// dann also nicht erneut zuweisbar machen
				$aActiveAllocation = Ext_Thebing_Allocation::getAllocationByInquiryId($this->oInquiry->id, $this->oAccommodation->id, 1);
                            
				if(         
					$this->bSkipAllocationCheck == false &&
					!empty($aActiveAllocation)
				){          
					$bFalse = true;
					$notAssignableReasons[] = self::t('Bereits zugewiesen');
					$this->debugMatchingCriterions($bFalse, '['.$aRoom['id'].'] Schon zugewiesen');
				}           
       
                $iFreeBeds = (int)$this->getFreeBedsOfRoom($aRoom['id'], $this->_oFrom , $this->_oUntil);

                $aPeriods[] = array(
                	'from'=>$this->_oFrom,
					'to'=>$this->_oUntil
				);
                            
			}               
                            
			if((int)$iFreeBeds <= 0){
				$bFalse = true;
				$notAssignableReasons[] = self::t('Keine freien Betten');
				$this->debugMatchingCriterions($bFalse, '['.$aRoom['id'].'] Frei betten <= 0');
			}
		
			$aAllocation_ = array();
			//$bFalse = false;
			$iMatching_other_vegetarian = 0;
			$iMatching_heterogen = 1;
							
			$oFrom = new DateTime($aSql['from']);
			$oUntil = new DateTime($aSql['to']);

			$aFamilieBlockingOfRoom = Ext_Thebing_Accommodation_Dates::getBlockingPeriods($aRoom['id'], $oFrom, $oUntil);
			$aFamilieBlockingOfRoom = $this->addBlockingDayData($aFamilieBlockingOfRoom);
			$aRoom['blocking'] = $aFamilieBlockingOfRoom;
							
//			// Schauen ob Zimmer Blockiert ist ( von dem Provider aus )
//			foreach($aFamilieBlockingOfRoom as $key => $aFamilieBlocking){
//				       
//				$oWDDateFamilyBlockingFrom 	= new DateTime($aFamilieBlocking['from']);
//				$oWDDateFamilyBlockingUntil = new DateTime($aFamilieBlocking['until']);
//					
//				$aFamilieBlockingOfRoom[$key]['day_from'] = $this->getDayData($oWDDateFamilyBlockingFrom);
//				$aFamilieBlockingOfRoom[$key]['day_until'] = $this->getDayData($oWDDateFamilyBlockingUntil);
//				
//				// Ein Tag abziehen, da matching am selben Tag von blockierungen möglich sein muss
//				$oWDDateFamilyBlockingFrom->add(new DateInterval('P1D'));
//				$oWDDateFamilyBlockingUntil->sub(new DateInterval('P1D'));
//				
//				foreach($aPeriods as &$aPeriod) {
//					
//					$bCheck = \Core\Helper\DateTime::checkDateRangeOverlap($aPeriod['from'], $aPeriod['to'], $oWDDateFamilyBlockingFrom, $oWDDateFamilyBlockingUntil);
//										
//					if($bCheck) {
//						$aPeriod['blocked'] = true;
//						break;
//					}       
//				}           
//                            
//			}   
//			
//			
//			                
//			// Nur wenn alle Perioden blockiert sind, ist der Raum nicht verfügbar
//			$iBlockedCounter = 0;
//			foreach($aPeriods as $aPeriod) {
//				if($aPeriod['blocked'] === true) {
//					$iBlockedCounter++;
//				}           
//			}               
//                            
//			if($iBlockedCounter == count($aPeriods)) {
//				// Wird doch schon weiter oben in der getFreeBedsOfRoom geprüft, oder irre ich?
//				#$bFalse = true;
//				#$this->debugMatchingCriterions($bFalse, '['.$aRoom['id'].'] Blockiert');
//			}               
                            
			// START check if Customer gender matches with room-gender-setting /////////////////

            if(!$bParking) {

				$this->checkIfGenderMatchesWithRoom($notAssignableReasons, $bFalse, $aRoom);

            }

			// END          
                            
			// alle Zuweisungen dieses Raums
			$iAlloCount = 0;
			// alle DZ Zuweisungen dieses Raums
			$iAlloDZCount = 0;
			// Männer dieses Raums
			$aAlloMale = array();
			// Frauen dieses Raums
			$aAlloFemale = array();

			// Schüler die noch in dem Zimmer liegen
			foreach($aResult as $key => &$aAllocation) {
				            
				// Zuweisung des anderen Schülers
				$oInquiryAllocation = Ext_TS_Inquiry::getInstance($aAllocation['inquiry_id']);
				$oCustomerAllocation = $oInquiryAllocation->getCustomer();
				          
				$aAllocation['object_from'] = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $aAllocation['date_from']);
				$aAllocation['object_to'] = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $aAllocation['date_until']);
				
				$aAllocation['from'] = $aAllocation['object_from']->toDateString();
				$aAllocation['to'] = $aAllocation['object_to']->toDateString();

				$oCurrentFrom = \Carbon\Carbon::instance($this->_oFrom);
				$oCurrentTo = \Carbon\Carbon::instance($this->_oUntil);

				$bOverlapping = $oCurrentFrom < $aAllocation['object_to'] && $aAllocation['object_from'] < $oCurrentTo;

				$aAllocation['overlapping'] = $bOverlapping;

				if(!$bParking) {
                    // Alle Zerschneidungen prüfen ob eine erlaubt ist
                    $iCountCheckPeriod = 0;
                    foreach((array)$aPeriods as $aPeriod) {

                        // Defferenz errechnen ob sich die Zeiträume überschneiden
                        // Aktuelle Zuweisung die überprüft wird
    //					$oDateFrom2 = new WDDate();
    //					$oDateFrom2->set($aPeriod['from'], WDDate::TIMESTAMP);
    //					$iDiff1 = $oDateFrom2->getDiff(WDDate::DAY, $aAllocation['to'], WDDate::DB_DATE);
    //
    //					$oDateTo2 = new WDDate();
    //					$oDateTo2->set($aPeriod['to'], WDDate::TIMESTAMP);
    //					$iDiff2 = $oDateTo2->getDiff(WDDate::DAY, $aAllocation['from'], WDDate::DB_DATE);

                        // Der WDDate-Schrott kriegt wegen Timestamps und Zeitzone mal wieder den Diff nicht hin
                        // Bsp.: Zeitraum von einem Tag, Diff ist 0 und 1 statt -1 und 1, somit EZ gebucht mehrfach zuweisbar #13234
                        $oDiff1 = $aPeriod['from']->diff(new DateTime($aAllocation['to']));
                        $oDiff2 = $aPeriod['to']->diff(new DateTime($aAllocation['from']));
                        $iDiff1 = $oDiff1->invert ? $oDiff1->days * -1 : $oDiff1->days;
                        $iDiff2 = $oDiff2->invert ? $oDiff2->days * -1 : $oDiff2->days;

                        // Schüler rausfinden die zur selben Zeit im Zimmer liegen
                        // TODO Ginge die Abfrage nicht viel einfacher?
                        if(
                            !(
                                (
                                    $iDiff1 >= 0 &&
                                    $iDiff2 >= 0
                                )  || (
                                    $iDiff1 <= 0 &&
                                    $iDiff2 <= 0
                                )
                            )
                            &&
                            is_object($oCustomerAllocation)
                        ) {
                            if($oCustomerAllocation->id != $this->oCustomer->id){
                            // Wenn EZ gebucht ist und schon eine Allo hat wird diese ausgeblendet
                                $aTypeDataAlloCustomer = $this->getRoomtypeData($aAllocation['roomtype_id']);
                                    if(
                                        (is_array($aRoomtypeDataOfInquiry) && $aRoomtypeDataOfInquiry['type'] == 0) ||
                                        (is_array($aTypeDataAlloCustomer) && $aTypeDataAlloCustomer['type'] == 0)
                                    ){
                                    // HIER NICHT ZUWEISBAR! Deswegen hochcounten
                                    $iCountCheckPeriod++;
                                }
                            }
                        }

                    }

                    if($iCountCheckPeriod == count($aPeriods)){
                        $bFalse = true;
						$notAssignableReasons[] = self::t('Einzelzimmer-Belegung');
                        $this->debugMatchingCriterions($bFalse, '['.$aRoom['id'].'] EZ');
                    }
                }

			}               
                       
			unset($aAllocation);
			
			//Nur gleiche InquiryAccommodations zählen, falls geteilt ist, nicht als 2 Allocations betrachten
			$aInquiryAccommodations = array();

			foreach($aResult as $key => $aAllocation) {

				$bOverlapping = $aAllocation['overlapping'];
				
				$this->addReservationData($aAllocation);
				
				$aTypeDataAlloCustomer = $this->getRoomtypeData($aAllocation['roomtype_id']);
				            
				// Im Buchungszeitraum vorhandene Zuweisungen
				//warum wird bei aResult iFrom 1 Woche verkürzt und iTo eine Woche verlängert,
				//für welche Überprüfung ist es relevant? hier macht es auf jeden fall keinen sinn...
				//die räume sind nur belegt, wenn in unser buchungszeitraum jemand zutrifft... 
				     
				$oDateFrom = $aAllocation['object_from'];
				$oDateFrom->setTime(0, 0, 0);
				$oDateUntil = $aAllocation['object_to'];
				$oDateUntil->setTime(0, 0, 0);
				
				if(!empty($aInactiveAllocations)){
					// TODO für Inaktive Zuweisungen muss hier auch irgendwie geprüft werden...
                            
				} else {      
					
					$oDateThisFrom = new DateTime($this->sFrom);
					$oDateThisUntil = new DateTime($this->sTo);
                    
					$iDiff1 = 0;
					
					if($oDateFrom > $oDateThisUntil){
						$iDiff1 = 1;
					} else if($oDateFrom < $oDateThisUntil){
						$iDiff1 = -1;
					}
					
					$iDiff2 = 0;
					
					if($oDateUntil > $oDateThisFrom){
						$iDiff2 = 1;
					} else if($oDateUntil < $oDateThisFrom){
						$iDiff2 = -1;
					}
                            
					if(     
						(   
							$iDiff1 == -1 &&
							$iDiff2 == 1
						) || (
							$iDiff1 == 0 &&
							$iDiff2 == -1
						) || (
							$iDiff1 == 0 &&
							$iDiff2 == 0
						) || (
							$iDiff1 == 1 &&
							$iDiff2 == -1
						) || (
							$iDiff1 == 1 &&
							$iDiff2 == 0
						)   
					){      
                            
						if( 
							!in_array($aAllocation['inquiry_accommodation_id'],$aInquiryAccommodations)
						){  
							$iAlloCount++;
                            
							$aInquiryAccommodations[] = $aAllocation['inquiry_accommodation_id'];
						}   
						    
						// Doppelzimmerzuweisungen
						if((is_array($aTypeDataAlloCustomer) && $aTypeDataAlloCustomer['type'] == 1)){
							$iAlloDZCount++;
						}

						// Geschlechter diesen Raums
						if($aAllocation['gender'] == 1){
							$aAlloMale[] = $aAllocation['inquiry_id'];
						}else{
							$aAlloFemale[] = $aAllocation['inquiry_id'];
						}
					
					}
				}
				
				// Ohne H:i:m
				$aAllocation['from'] = $oDateFrom->getTimestamp();
				$aAllocation['to'] = $oDateUntil->getTimestamp();

				if(!$bParking) {

                    // Zusammenreisende Schüler
                    $aRoomSharing = explode(',', $aAllocation['share_ids']);

                    // Wenn DZ gebuch ist und schon 2 allo hat wird diese ausgeblendet
                    if(
                        (is_array($aRoomtypeDataOfInquiry) && $aRoomtypeDataOfInquiry['type'] == 1) &&
                        !in_array($this->oAccommodation->id, $aInquiryAccommodations) && // Liegt der Kunde schonmal im Zimmer, darf er auch nochmal drinnen liegen! (hinzubuchen extranächste)
                        $iAlloCount > 1
                    ){
                        // Rausgenommen, da die Abfrage oben nicht korrekt ist. Hier muss
                        // geoprüft werden ob mehr als eine Zuweisung ZEITGLEICH existieren und nicht
                        // ob 2 vorhanden sind.
                        #$bFalse = true;
                        #$this->debugMatchingCriterions($bFalse, 'DZ');
                    }

                    // wenn MZ  gebucht ist,  aber schon 1-2 DZ allos hat wird diese ausgeblendet
                    if(
                        (is_array($aRoomtypeDataOfInquiry) && $aRoomtypeDataOfInquiry['type'] == 2) &&
                        $iAlloDZCount > 0 &&
                        $iAlloCount >= 2
                    ){
                        $bFalse = true;
						$notAssignableReasons[] = self::t('Mehrbettzimmer-Belegung');
                        $this->debugMatchingCriterions($bFalse, 'MZ');
                    }
				}

				$aAllocation_[$key] = $aAllocation;

				$oInquiryAllocation = new Ext_TS_Inquiry($aAllocation['inquiry_id']);
				$oCustomerAllocation = $oInquiryAllocation->getCustomer();

				$this->addAllocationData($aAllocation_[$key]);

				$aAllocation_[$key]['halfStartDay'] = 0;
				$aAllocation_[$key]['halfEndDay'] = 0;

				if(
                    !$bParking &&
				    $aAllocation_[$key]['customer']['id'] != $this->oCustomer->id
                ){

					if(
					    $bOverlapping &&
						!in_array($this->oInquiry->id, $aRoomSharing) && // Zusammenreisende Schüler nicht prüfen
						$bAge == true &&
						(
							($iAge >= 18 && $aAllocation_[$key]['customer']['age'] < 18) ||
							($iAge < 18 && $aAllocation_[$key]['customer']['age'] >= 18)
						)

					) {
						$bFalse = true;
						$notAssignableReasons[] = self::t('Altersunterschied');
					    $this->debugMatchingCriterions($bFalse, '['.$aRoom['id'].'] Altersunterschied');
					}

					// [BB]
					// changed, only check if allocation time-frames are overlapping and do NOT at start-/end-day
					// [/BB]
					if(
					    $bOverlapping &&
						$bGender == true &&
						!in_array($this->oInquiry->id, $aRoomSharing) && // Zusammenreisende Schüler nicht prüfen
						($iGender != $oCustomerAllocation->gender)
					){
						$bFalse = true;
						$notAssignableReasons[] = self::t('Geschlecht');
					    $this->debugMatchingCriterions($bFalse, '['.$aRoom['id'].'] Geschlecht');
					}

					$aAllocation_[$key]['customer']['mother'] = $oCustomerAllocation->language;

				}

				$oMatchingData = $oInquiryAllocation->getMatchingData();

				if($oMatchingData->acc_vegetarian == 1){
					$iMatching_other_vegetarian = 1;
				}else{
					$iMatching_other_vegetarian = 0;
				}
				if(abs($iAge-$aAllocation_[$key]['customer']['age']) > 10){
					$iMatching_heterogen = 0;
				}else{
					$iMatching_heterogen = 1;
				}

			}

			if(!$bParking) {
				
                // Prüfen ob das Geschlecht der zugewiesenen Kunden mit den Geschlechtern der Zusammenreisenden zusammenpast
                // Nur Prüfen wenn Unterkunft Geschlechtertrennung hat
                if(
                    $bOverlapping &&
					$bFalse == false &&
                    $oProvider->ext_54 == 1
                ) {
                    $bFalse = $this->_checkRoomSharing($aAllocation_, 'gender');
					if($bFalse === true) {
						$notAssignableReasons[] = self::t('Geschlecht');
					}
                }

                // Prüfen ob das Geschlecht der zugewiesenen Kunden mit den Nationalitäten der Zusammenreisenden zusammenpast
                // Nur Prüfen wenn Unterkunft Nationalitätentrennung hat
                if(
                    $bOverlapping &&
					$bFalse == false &&
                    $bNationality == 1
                ) {
                    $bFalse = $this->_checkRoomSharing($aAllocation_, 'nationality', true);
					if($bFalse === true) {
						$notAssignableReasons[] = self::t('Nationalität');
					}
                }

				// Prüfen ob das Geschlecht der zugewiesenen Kunden mit den Muttersprachen der Zusammenreisenden zusammenpast
                // Nur Prüfen wenn Unterkunft Muttersprachentrennung hat
                if(
                    $bOverlapping &&
					$bFalse == false &&
                    $bMotherTongue == 1
                ) {
                    $bFalse = $this->_checkRoomSharing($aAllocation_, 'language', true);
					if($bFalse === true) {
						$notAssignableReasons[] = self::t('Muttersprache');
					}
                }

                if($iRoomtypeId != $aRoom['type_id']) {
                    $aRoom['wrongType'] = 1;

                    // Wenn der Raumtyp nicht passt darf man nicht zuweisen es sei denn
                    // der Raum ist Minderwertiger
                    $bCheckRoomCategory = $this->checkRoomCategory($iRoomCategory, $aRoom['type_category']);

                    if(!$bCheckRoomCategory){
                        $bFalse = true;
						$notAssignableReasons[] = self::t('Raumart');
                        #continue; // könnte man einblenden :)
                    }
                } else {
                    $aRoom['wrongType'] = 0;
                }
			}

			if($bFalse == true) {
				$aRoom['isAssignable'] = 0;
				$aRoom['not_assignable_reasons'] = implode(', ', array_unique($notAssignableReasons));
				//continue; 
			} else {        
				$aRoom['isAssignable'] = 1;
			}

			if($bOnlyCorrectRooms == true && $bFalse == true){
				continue;   
			}               
			                
			if(count($aAllocation_)<=0){
				$aAllocation_ = 0;
			}               
                            
			// Alle aktiven Zuweisungen dieses Raums speichern
			$aRoom['all_allocations'] = $iAlloCount;
			                
			$aBack_[$i] = $aRoom;
			$aBack_[$i]['matching_other_vegetarian'] = $iMatching_other_vegetarian;
			$aBack_[$i]['matching_other_heterogen'] = $iMatching_heterogen;
						
			$aBack_[$i]['allocation'] = $aAllocation_;
			                
			$i++;           
		}                   

		if(
			isset($_VARS['matching_debug_provider'][$idAccommodation])
		) {
			$this->bDebug = false;
		}
		
		return $aBack_ ;    
		                    
	}                       
	                        
	protected function _getMealList($aMealIds = array()) {      
		                    
		$aMealIds = (array)$aMealIds;
		                    
		$sCacheKey = implode('_', $aMealIds);
		                    
		if(!isset(self::$aCache['meal_list'][$sCacheKey])) {

			$oSchool = $this->getSchool();
			$oAccommodationUtil = new Ext_Thebing_Accommodation_Util($oSchool);
			$aMeals				= $oAccommodationUtil->getMealList(false, $aMealIds);

			self::$aCache['meal_list'][$sCacheKey] = $aMeals;
			                
		}                   
			                
		return self::$aCache['meal_list'][$sCacheKey];
                            
	}                       

	protected function debugMatchingCriterions($mValue, $sDescription, $bQuery=false){
     
		if($this->bDebug === true) {
			                
			__pout($sDescription);
                            
			if($bQuery) {   
				#$oDB = DB::getDefaultConnection();
				#__pout($oDB->getLastQuery());
			}               
			__pout($mValue);
		}
		
	}
	                        
	/*                      
	 * prüft anhand von der RoomtypeId und einer RoomCategory ob
	 * $iRoomtypeId auch einer anderen Category entspricht
	 */                     
	protected function checkRoomCategory($iRoomtypeId, $iRoomCategory){

		if($this->bIgnoreRoomtype) {
			return true;
		}

		$bBack = false;     
		                    
		switch($iRoomtypeId){
			case 0: // EZ   
				$bBack = true;
				break;      
			case 1: // DZ   
				if(         
					$iRoomCategory == 1 ||
					$iRoomCategory == 2
				){          
					$bBack = true;
				}           
				break;      
			case 2: // MZ  
				if($iRoomCategory == 2){
					$bBack = true;
				}           
				break;      
		}                   

		return $bBack;      
	}                       

	/**
	 * prüft ob das Geschlecht der zugewiesenen Kunden mit den Zusammenreisenden übereinstimmt
	 * 
	 * @param array $aAllocatedInquiries
	 * @param string $sField (gender, nationality, language)
	 * @param boolean $bCheckEqual
	 * @return boolean
	 */
	protected function _checkRoomSharing($aAllocatedInquiries, $sField = 'gender', $bCheckEqual=false) {

		$bFalse = false; // kein Fehler		
		$aRoomSharingInquiries = array();
		                    
		foreach((array)$this->aRoomSharingInquiries as $oRoomShareInquiry) {
			$oRoomShareCustomer = $oRoomShareInquiry->getCustomer();
			$aRoomSharingInquiries[$oRoomShareInquiry->id] = [
				'customer' => [
					'gender' => $oRoomShareCustomer->gender,
					'nationality' => $oRoomShareCustomer->nationality,
					'language' => $oRoomShareCustomer->language
				]
			];
		}

		// Man muss auch mit sich selber reisen, da beim hinzubuchen von extranächsten der komplette InquiryAccommodation Zeitraum geprüft wird
		if(is_object($this->oInquiry)) {
			$oCustomer = $this->oInquiry->getCustomer();
			$aRoomSharingInquiries[$this->oInquiry->id] = [
				'customer' => [
					'gender' => $oCustomer->gender,
					'nationality' => $oCustomer->nationality,
					'language' => $oCustomer->language
				]
			];
		}

		foreach($aAllocatedInquiries as $aAllocatedInquiry) {
            
			if(     
				!array_key_exists($aAllocatedInquiry['inquiry_id'], $aRoomSharingInquiries) &&
				count($aRoomSharingInquiries) > 0
			) {

				// Buchung reist NICHT mit der aktuellen Buchung zusammen -> geschlecht prüfen mit ALLEN zusammenreisenden
				foreach($aRoomSharingInquiries as $aRoomSharingInquiry) {
	
					if(
						(
							$bCheckEqual === true &&
							$aRoomSharingInquiry['customer'][$sField] == $aAllocatedInquiry['customer'][$sField]
						) ||
						(
							$bCheckEqual === false &&
							$aRoomSharingInquiry['customer'][$sField] != $aAllocatedInquiry['customer'][$sField]
						)
					) {
						$bFalse = true;
						$this->debugMatchingCriterions($bFalse, 'RoomSharing - '.$sField);
						return $bFalse;
					}
				}
				            
			}

		}

		return $bFalse;
	}      
	
	public function getDayData(DateTime $oDate){
		$oDate = clone $oDate;
		$oDate->setTime(0, 0, 0);
		$aDay = array(
				'day'		=> $oDate->format('d'),
				'month'		=> strftime('%B', $oDate->getTimestamp()),
				'year'		=> $oDate->format('Y'),
				'weekday'	=> $oDate->format('w'),
				'timestamp' => Ext_Thebing_Util::convertUTCDate($oDate->getTimestamp()),
				'db_date'	=> $oDate->format('Y-m-d'),
			);
		return $aDay;
	}

	/**
	 * @param array $aAllocation
	 * @return array|null
	 */
	public function getBulletStatus($aAllocation) {
		$aStatus = null;

		if($aAllocation['bed'] == 0) {
			$aStatus = [];
			$aStatus['background'] = Ext_Thebing_Util::getColor('red_font');
			$aStatus['title'] = L10N::t('Keine (interne) Zuweisung zu Bett', 'Thebing » Accommodation » Matching');
		}

		return $aStatus;
	}

	public function addAllocationData(&$aAllocation) {

		$sCacheKey = 'Ext_Thebing_Allocation::addAllocationData_'.$aAllocation['id'];
		
		$aCache = WDCache::get($sCacheKey);

		if($aCache === null) {

			if(!isset($aAllocation['from_date'])) {
				$aAllocation['from_date'] = date('Y-m-d', $aAllocation['from']);
			}
			if(!isset($aAllocation['until_date'])) {
				$aAllocation['until_date'] = date('Y-m-d', $aAllocation['to']);
			}

			$oDateFrom	= new DateTime($aAllocation['from_date']);
			$oDateUntil	= new DateTime($aAllocation['until_date']);

			$aAllocation['from'] = Ext_Thebing_Util::convertToGMT($aAllocation['from']);
			$aAllocation['to'] = Ext_Thebing_Util::convertToGMT($aAllocation['to']);
			$aAllocation['day_from'] = $this->getDayData($oDateFrom);
			$aAllocation['day_until'] = $this->getDayData($oDateUntil);

			$oInquiryAllocation = Ext_TS_Inquiry::getInstance($aAllocation['inquiry_id']);
			$oCustomerAllocation = $oInquiryAllocation->getCustomer();

			$oAllocation = new Ext_Thebing_Allocation();
			$oAllocation->setInquiryObject($oInquiryAllocation);
			$bOccupancy = $oAllocation->checkForDoubleOccupancy();
			$aAllocation['warning'] = $bOccupancy;
			$aAllocation['bullet_status'] = $this->getBulletStatus($aAllocation);
			
			// Diese Struktur muss für das JS immer da sein
			$aAllocation['inquiry'] = [
				'id' => null
			];

			/*
			 * @todo Dieses Array $aAllocation muss weg. Alles auf Objekte umstellen
			 */
			$allocation = \Ext_Thebing_Accommodation_Allocation::getInstance($aAllocation['id']);
			
			if($aAllocation['reservation'] !== null) {
				
				$this->addReservationData($aAllocation);

				$aAllocation['allocation_label'] = $aAllocation['reservation_comment'];
				$aAllocation['customer']['gender'] = $aAllocation['gender'];
				
			} elseif(
				is_object($oCustomerAllocation) &&
				!empty($aAllocation['inquiry_accommodation_id'])
			) {

				$oInquiryAccommodation = Ext_TS_Inquiry_Journey_Accommodation::getInstance($aAllocation['inquiry_accommodation_id']);

				$aAllocation['customer'] = $oCustomerAllocation->getShortArray($oDateFrom);
				$aAllocation['inquiry'] = $oInquiryAllocation->getShortArray();
				$aAllocation['allocation_label'] = $this->getAllocationLabel($oInquiryAccommodation, $oCustomerAllocation, $allocation);

			}

			if(System::d('debugmode')) {
				$aAllocation['debug_timezone'] = date_default_timezone_get();
				$aAllocation['debug_backtrace'] = Util::getBacktrace();
				$aAllocation['debug_created'] = date('c');
			}

			$aCache = $aAllocation;
			WDCache::set($sCacheKey, (60*60*24*7), $aAllocation, false, 'Ext_Thebing_Allocation::addAllocationData');
			
		}

		$aAllocation = $aCache;

		// Tooltip - Nach dem Caching

		if($aAllocation['reservation'] !== null) {
			$aAllocation['allocation_tooltip'] = $this->getReservationTooltip($aAllocation);
		}

	}

	public function getCriteria($sType = 'hostfamily', $bOnlyBookingFields = true, $oLanguage = null) {
		
		$aMatchingYesNo = Ext_Thebing_Util::getMatchingYesNoArray(System::d('systemlanguage'));
		
		$aCriteria = [
			'hard' => [],
			'soft' => []
		];

		if($bOnlyBookingFields === false) {
			$aCriteria['hard'][] = (new Criterion())->setAccommodationField('ext_35')->setLabel('Erwachsene');
			$aCriteria['hard'][] = (new Criterion())->setAccommodationField('ext_36')->setLabel('Jugendliche');
		}
		
		if($sType === 'hostfamily') {

			if($bOnlyBookingFields === false) {
				$aCriteria['hard'][] = (new Criterion())->setAccommodationField('ext_37')->setLabel('Männlich');
				$aCriteria['hard'][] = (new Criterion())->setAccommodationField('ext_38')->setLabel('Weiblich');
				$aCriteria['hard'][] = (new Criterion())->setAccommodationField('diverse')->setLabel('Divers');

			}
			
		}
			
		$aCriteria['hard'][] = (new Criterion())->setField('acc_smoker')->setAccommodationField('ext_39')->setLabel('Raucher')->setOptions($aMatchingYesNo);
		$aCriteria['hard'][] = (new Criterion())->setField('acc_vegetarian')->setAccommodationField('ext_40')->setLabel('Vegetarier')->setOptions($aMatchingYesNo);
		$aCriteria['hard'][] = (new Criterion())->setField('acc_muslim_diat')->setAccommodationField('ext_41')->setLabel('Muslim Diat')->setOptions($aMatchingYesNo);
				
		if($sType === 'hostfamily') {

			$aDistance = Ext_Thebing_Data::getDistance($oLanguage);
			$aFamilyAge = Ext_Thebing_Data::getFamilyAge($oLanguage);
			
			$aCriteria['soft'][] = (new Criterion())->setField('cats')->setAccommodationField('ext_42')->setLabel('Familie kann Katzen haben')->setOptions($aMatchingYesNo);
			$aCriteria['soft'][] = (new Criterion())->setField('dogs')->setAccommodationField('ext_43')->setLabel('Familie kann Hunde haben')->setOptions($aMatchingYesNo);
			$aCriteria['soft'][] = (new Criterion())->setField('pets')->setAccommodationField('ext_44')->setLabel('Familie kann andere Tiere haben')->setOptions($aMatchingYesNo);
			$aCriteria['soft'][] = (new Criterion())->setField('smoker')->setAccommodationField('ext_45')->setLabel('Raucherfamilie')->setOptions($aMatchingYesNo);
			$aCriteria['soft'][] = (new Criterion())->setField('distance_to_school')->setAccommodationField('ext_46')->setLabel('Entfernung zur Schule')->setAccommodationType('select')->setOptions($aDistance);
			$aCriteria['soft'][] = (new Criterion())->setField('air_conditioner')->setAccommodationField('ext_47')->setLabel('Familie muss Klimaanlage haben')->setOptions($aMatchingYesNo);
			$aCriteria['soft'][] = (new Criterion())->setField('bath')->setAccommodationField('ext_48')->setLabel('Kunde braucht eigenes Bad')->setOptions($aMatchingYesNo);
			$aCriteria['soft'][] = (new Criterion())->setField('family_age')->setAccommodationField('ext_49')->setLabel('Familienalter')->setAccommodationType('select')->setOptions($aFamilyAge);
			$aCriteria['soft'][] = (new Criterion())->setField('residential_area')->setAccommodationField('ext_50')->setLabel('Schüler möchte in folgender Wohnumgebung wohnen')->setAccommodationType('input')->setType('input');
			$aCriteria['soft'][] = (new Criterion())->setField('family_kids')->setAccommodationField('ext_51')->setLabel('Familie kann Kinder haben')->setOptions($aMatchingYesNo);
			$aCriteria['soft'][] = (new Criterion())->setField('internet')->setAccommodationField('ext_53')->setLabel('Kunde will Internet')->setOptions($aMatchingYesNo);

		} else {

			if($bOnlyBookingFields === false) {
				$aCriteria['hard'][] = (new Criterion())->setAccommodationField('ext_54')->setLabel('Trennung nach Geschlecht');
				$aCriteria['hard'][] = (new Criterion())->setAccommodationField('ext_55')->setLabel('Trennung nach Alter');
				$aCriteria['hard'][] = (new Criterion())->setAccommodationField('ext_56')->setLabel('Jede Nationalität nur einmal pro Zimmer');
				$aCriteria['hard'][] = (new Criterion())->setAccommodationField('ext_57')->setLabel('Jede Muttersprache nur einmal pro Zimmer');
			}
			
		}
		
		return $aCriteria;
	}
	
	/**
	 * Dekodiert Reservierungsinfos, falls noch nicht geschehen
	 * @param array $aAllocation
	 */
	protected function addReservationData(array &$aAllocation) {

		if(
			$aAllocation['reservation'] !== null &&
			is_string($aAllocation['reservation'])
		) {
			$aReservation = json_decode($aAllocation['reservation'], true);
			$aAllocation['reservation_date'] = $aReservation['date'];
			$aAllocation['reservation_comment'] = $aReservation['comment'];
			$aAllocation['gender'] = (int)$aReservation['gender'];
			$aAllocation['roomtype_id'] = (int)$aReservation['roomtype'];
			$aAllocation['meal_id'] = (int)$aReservation['board'];
			$aAllocation['category_id'] = (int)$aReservation['category'];
			$aAllocation['age'] = $aReservation['age'];
			$aAllocation['reservation'] = true;
		}

	}
	
	static public function t($string) {
		return L10N::t($string, 'Thebing » Accommodation » Matching');
	} 

	public static function getGenderIcon(int $iGender): string {
		return match ($iGender) {
			1 => '♂',
			2 => '♀',
			default => '⚥'
		};
	}

	public static function getAgeOptions(): array {
		return [
			'minor' => self::t('Minderjährig'),
			'adult' => self::t('Erwachsen')
		];
	}


	public function checkIfGenderMatchesWithRoom(&$notAssignableReasons, &$false, $room) :void {

		// @todo Auslagern?
		$genderMapping = [
			1 => 'male',
			2 => 'female',
			3 => 'diverse'
		];

		$genderSettings = array_intersect_key($room, array_flip($genderMapping));

		// Wenn keine Einstellung bei Raum, dann keine Überprüfung
		if(!in_array("1", $genderSettings)) {
			return;
		}

		if(!isset($genderMapping[$this->oCustomer->gender])) {
			$false = true;
			$notAssignableReasons[] = self::t('Schüler hat kein Geschlecht');
			$this->debugMatchingCriterions($false, '[' . $room['id'] . '] Geschlecht');
			return;
		}

		if ($room[$genderMapping[$this->oCustomer->gender]] == 0) {
			$false = true;
			$notAssignableReasons[] = self::t('Geschlecht (Raum)');
			$this->debugMatchingCriterions($false, '[' . $room['id'] . '] Geschlecht');
		}

	}

}
