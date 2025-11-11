<?php

/**
 * @todo: from und until umstellen -> mysql date
 */

use Communication\Interfaces\Model\CommunicationSubObject;
use Tc\Service\LanguageAbstract;
use TsRegistrationForm\Interfaces\RegistrationInquiryService;

/**
 * @property int $id
 * @property int $journey_id
 * @property string $changed (TIMESTAMP)
 * @property string $created (TIMESTAMP)
 * @property int $accommodation_id
 * @property int $roomtype_id
 * @property int $meal_id
 * @property string $acc_share_with
 * @property int $weeks
 * @property string $from (DATE)
 * @property string $until (DATE)
 * @property string $comment
 * @property int $calculate
 * @property int $visible
 * @property int $active
 * @property int $creator_id
 * @property int $for_matching
 * @property int $program_change
 * @property string $from_time (TIME)
 * @property string $until_time (TIME)
 * @property int $groups_accommodation_id
 */
class Ext_TS_Inquiry_Journey_Accommodation extends Ext_TS_Inquiry_Journey_Service implements Ext_TS_Service_Interface_Accommodation, RegistrationInquiryService, \Communication\Interfaces\Model\HasCommunication {

	use \Ts\Traits\LineItems\Accommodation;
	
 	protected $_sTable = 'ts_inquiries_journeys_accommodations'; // 1
	protected $_sTableAlias = 'ts_ija';

 	protected static $_sStaticTable = 'ts_inquiries_journeys_accommodations';

	/**
	 * @var null|Ext_TS_Service_Accommodation_Helper_Extranights
	 */
	protected $_oExtraNightHelper = null;

	protected $_aFormat = array(
//								'journey_id' => array(
//									'required'=>true,
//									'validate'=>'INT_POSITIVE',
//									'not_changeable' => true
//									),
		'from' => array(
			'validate' => 'DATE',
			#'required'=>true,  //#5103
		),
		'until' => array(
			'validate' => 'DATE',
			#'required'=>true, //#5103
		),
		'from_time' => array(
			'format' => 'TIME',
			'validate' => 'TIME'
			),
		'until_time' => array(
			'format' => 'TIME',
			'validate' => 'TIME'
			),
		'weeks' => array(
			'validate' => 'INT_POSITIVE',
			'required' => true
			),
		'accommodation_id' => array(
			'validate' => 'INT_POSITIVE',
			'required' => true
			),
		'roomtype_id' => array(
			'validate' => 'INT_POSITIVE',
			'required' => true
			),
		'meal_id' => array(
			'validate' => 'INT_POSITIVE',
			'required' => true
			)
	);

	protected $_aJoinedObjects = array(
        'accounting_payments_active' => array(
			'class' => 'Ext_Thebing_Accommodation_Payment',
			'key' => 'inquiry_accommodation_id',
			'check_active' => true,
			'type' => 'child'
        ),
		'category' => array(
			'class' => \Ext_Thebing_Accommodation_Category::class,
			'key' => 'accommodation_id',
			'check_active' => true
        ),
		'roomtype' => array(
			'class' => \Ext_Thebing_Accommodation_Roomtype::class,
			'key' => 'roomtype_id',
			'check_active' => true
        ),
		'meal' => array(
			'class' => \Ext_Thebing_Accommodation_Meal::class,
			'key' => 'meal_id',
			'check_active' => true
        ),
		'additionalservices' => [
			'class' => Ext_TS_Inquiry_Journey_Additionalservice::class,
			'key' => 'relation_id',
			'static_key_fields' => ['relation' => 'accommodation'],
			'check_active' => true,
			'type' => 'child',
			'on_delete' => 'cascade'
		]
    );

	protected $_aJoinTables = [
		'additionalservices' => [
			'table' => 'ts_inquiries_journeys_additionalservices',
			'foreign_key_field' => 'additionalservice_id',
	 		'primary_key_field' => 'relation_id',
			'static_key_fields' => ['relation' => 'accommodation'],
			'class' => 'Ext_Thebing_School_Additionalcost',
			'check_active' => true,
			'readonly' => true,
			'autoload' => false
		],
		// Wird nur bei Anfragen verwendet
		'travellers' => [
			'table' => 'ts_inquiries_journeys_accommodations_to_travellers',
			'foreign_key_field' => 'contact_id',
			'primary_key_field' => 'journey_accommodation_id',
			'class' => Ext_TS_Inquiry_Contact_Traveller::class,
			'autoload' => false
		]
	];
	
	protected $sInfoTemplateType = 'accommodation';
	
	protected $_sPlaceholderClass = 'Ext_TS_Inquiry_Journey_Accommodation_Placeholder';

	public function isParking(): bool {
	    return $this->getCategory()->isParking();
    }

	/**
	 * Löscht alle Unterkunftszuweisungen
	 *
	 * @param bool $bRemoveComplete
	 * @param bool $bSystem
	 */
	public function deleteAllocations($bRemoveComplete = false, $bSystem = false) {
		$aAlloccations = Ext_Thebing_Allocation::getAllocationByInquiryId( $this->inquiry_id, $this->id, true, true);

		foreach((array)$aAlloccations as $aAllocation) {
			$oAllocation = Ext_Thebing_Accommodation_Allocation::getInstance((int)$aAllocation['id']);
			$oAllocation->deleteMatching($bRemoveComplete, $bSystem);
		}
	}

	/**
	 * Es werden NUR die Matching Einträge gelöscht (wieder freigegeben) die auf die NEUE Kombination passt
	 */
	public function deleteUnfittingAllocations($aOriginalAcco) {

		// Alle inaktiven/aktiven Zuweisungen
		$aAlloccations = Ext_Thebing_Allocation::getAllocationByInquiryId( $this->inquiry_id, $this->id, true, true );

		foreach ((array)$aAlloccations as $aAllo) {
			// Prüfen ob Zuweisung noch aktiv bleiben darf

			$oInquiryAllocation = Ext_Thebing_Accommodation_Allocation::getInstance((int)$aAllo['id']);

			if(
				$this->active == 0 ||
				$this->visible == 0
			) {
				//wenn inaktiv/gelöscht dann lösche alle Zuweisungen
				$oInquiryAllocation->deleteMatching(false, true);
				continue;
			}

			// Originaldaten um heraus zu finden was sich verändert hat
			if($oInquiryAllocation->room_id > 0) {

				$oRoom = $oInquiryAllocation->getRoom();
				$oRoomType = $oRoom->getType();
				$oFamily = $oRoom->getProvider();

				$aMeals = $oFamily->meals;

				$aAccommodationCategories = $oFamily->getCategories();
				$aAccommodationCategories = array_map(
					function(Ext_Thebing_Accommodation_Category $oAccommodationCategory) {
						return $oAccommodationCategory->id;
					},
					$aAccommodationCategories
				);

				// Prüfen ob die neuen Kombinationsmöglichkeiten immernoch gültig sind
				// oder ob gelöscht werden muss
				if(
					(
						$aOriginalAcco['meal_id'] != $this->meal_id && // Malzeit hat sich verändert
						!in_array($this->meal_id, $aMeals) // Neue Malzeit passt nicht mehr
					) || (
						$aOriginalAcco['roomtype_id'] != $this->roomtype_id && // Roomtype hat sich verändert
						$this->roomtype_id != $oRoomType->id // Raum passt nicht mehr
					) || (
						$aOriginalAcco['accommodation_id'] != $this->accommodation_id && // Kategorie hat sich verändert
						!in_array($this->accommodation_id, $aAccommodationCategories)
					)

				) {
					// Zuweisung muss aus Matching gelöscht werden
					$oInquiryAllocation->deleteMatching(false, true);
				}

			}

		}

	}

	public function deleteAllAllocations(){
		$aAlloccations = Ext_Thebing_Allocation::getAllocationByInquiryId( $this->inquiry_id, $this->id, true, true );


	}

	/**
	 * Get the accommodation object
	 * @deprecated 
	 * @return Ext_Thebing_Accommodation_Category
	 */
	public function getAccommodation() {
		return $this->getCategory();
	}

	/**
	 * Check if the Course has Changed
	 * This Method must called bevor you call the save() Methode
	 * @param null $oAcco
	 * @param string $sModus
	 * @return bool|string
	 */
	public function checkForChange(Ext_Thebing_Inquiry_Group_Accommodation $oAcco = null, $sModus = 'complete'){

		if($this->id <= 0) {
			return 'new';
		}

		if($this->active == 0) {
			return 'delete';
		}

		if($oAcco == null) {
			$aOriginalData = $this->getOriginalData();
		} else {
			$aOriginalData = $oAcco->getData();
		}

		if($sModus == 'complete'){
			if(
				(int)$this->accommodation_id	!= (int)$aOriginalData['accommodation_id'] ||
				(int)$this->roomtype_id			!= (int)$aOriginalData['roomtype_id'] ||
				(int)$this->meal_id				!= (int)$aOriginalData['meal_id'] ||
				$this->from						!= $aOriginalData['from'] ||
				$this->until					!= $aOriginalData['until'] ||
				(int)$this->weeks				!= (int)$aOriginalData['weeks'] ||
				(int)$this->visible				!= (int)$aOriginalData['visible'] ||
				$this->comment					!= $aOriginalData['comment']
			){
				return 'edit';
			} elseif ($oAcco !== null) {
				// Zusatzleistungen der Gruppen-Unterkunft mit Zusatzleistungen der Unterkunftsbuchung vergleichen
				$journeyGroupAdditionalServiceIds = [];
				foreach ($oAcco->additionalservices as $additionalserviceData) {
					$journeyGroupAdditionalServiceIds[] = $additionalserviceData['additionalservice_id'];
				}
				$journeyAccommodationAdditionalServiceIds = [];
				foreach ($this->getJoinedObjectChilds('additionalservices') as $additionalservice) {
					$journeyAccommodationAdditionalServiceIds[] = $additionalservice->additionalservice_id;
				}
				// Sortieren für den Vergleich
				sort($journeyGroupAdditionalServiceIds);
				sort($journeyAccommodationAdditionalServiceIds);
				if ($journeyGroupAdditionalServiceIds !== $journeyAccommodationAdditionalServiceIds) {
					return 'edit';
				}
			}
		} else if($sModus == 'only_time'){
			if(
				$this->from						!= $aOriginalData['from'] ||
				$this->until					!= $aOriginalData['until'] ||
				(int)$this->weeks				!= (int)$aOriginalData['weeks']
			){
				return 'edit';
			}
		}

		return false;

	}

 	public static function saveAccommodations($iInquiry, $aAccommodations, $bSortArray = true) {

		if($iInquiry > 0) {

			$oInquiry = new Ext_TS_Inquiry($iInquiry);
			$oSchool = $oInquiry->getSchool();
			if($oSchool->id > 0){
				$aAccommdationNew = array();
				if($bSortArray){
					foreach((array)$aAccommodations as $sKey => $aAccommodation) {
						foreach((array)$aAccommodation as $iKey => $mValue){
							$aAccommdationNew[$iKey][$sKey] = $mValue;
						}
					}
				} else {
					$aAccommdationNew = $aAccommodations;
				}

				foreach((array)$aAccommdationNew as $iKey => $aAccommodation){
					$bError = false;

					$oAccommodation = new Ext_TS_Inquiry_Journey_Accommodation();

					foreach($aAccommodation as $sField => $sValue) {

						if(
							$sField == 'from' ||
							$sField == 'until'
						) {
							$sValue = Ext_Thebing_Format::ConvertDate($sValue, $oSchool->id);
							if((int)$sValue <= 0){
								$bError = true;
							}
						}

						if($sField == 'accommodation_id' && $sValue <= 0){
							$bError = true;
						}
						if($sField == 'roomtype_id' && $sValue <= 0){
							$bError = true;
						}
						if($sField == 'meal_id' && $sValue <= 0){
							$bError = true;
						}
						if($sValue == ""){
							continue;
						}
						$oAccommodation->$sField = $sValue;
					}
					$oAccommodation->inquiry_id = $iInquiry;
					if($bError == false){
						$oAccommodation->save();

						$aAccosIds[$iKey] = $oAccommodation->id;
					}
				}
			}
			return $aAccosIds;
		} else {
			throw new Exception(" No Inquiry Data ");
		}
		return false;
	}


	public function getAccommodationName($short = false, $lang = ''){

		if (empty($lang)) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			$lang = $oSchool->getInterfaceLanguage();
		}
		$sSql = "SELECT
							*
						FROM
							#table
						WHERE
							`active` = 1
						AND
							`id` = :id
						";
		$aSql = array (
			'table' => 'kolumbus_accommodations_categories',
			'id' => (int)$this->accommodation_id
			);

		$aResult = DB :: getPreparedQueryData($sSql, $aSql);

		if(!empty($aResult)){
			if(!$short) {
				$sField = 'name_'.$lang;
			} else {
				$sField = 'short_'.$lang;
			}

			return $aResult[0][$sField];
		}else{
			return null;
		}

	}

	public function getAccommodationCategoryWithRoomTypeAndMeal($bShort = true, $sInterfaceLanguage = false){

		$oAccommodationCategory = Ext_Thebing_Accommodation_Category::getInstance($this->accommodation_id);
		$oAccommodationRoomType	= Ext_Thebing_Accommodation_Roomtype::getInstance($this->roomtype_id);
		$oAccommodationMeal		= Ext_Thebing_Accommodation_Meal::getInstance($this->meal_id);

		if(!$sInterfaceLanguage){
			$oSchool				= Ext_Thebing_School::getSchoolFromSession();
			$sInterfaceLanguage		= $oSchool->getInterfaceLanguage();	
		}
		
		$sFunction = 'getShortName';

		if(!$bShort){
			$sFunction = 'getName';
		}

		$sName = '';

		if($oAccommodationCategory->id>0){
			$sName .= $oAccommodationCategory->$sFunction($sInterfaceLanguage);
		}
		if($oAccommodationRoomType->id>0){
			$sName .= ' / ';
			$sName .= $oAccommodationRoomType->$sFunction($sInterfaceLanguage);
		}
		if($oAccommodationMeal->id>0){
			$sName .= ' / ';
			$sName .= $oAccommodationMeal->$sFunction($sInterfaceLanguage);
		}


		return $sName;
	}

	/**
	 * @return \DatePeriod
	 */
	public function getAllocatedPeriod() {

	    $aAllocations = (array)Ext_Thebing_Allocation::getAllocationByInquiryId( $this->inquiry_id, $this->id, true, false);

		// wenn keine belegungen mus auch nichts angepasst werden
		if(empty($aAllocations)){
			return null;
		}

		$dMin = null;
		$dMax = null;

	    foreach ($aAllocations as $iKey => $aAllocation) {
			$dFrom = new \Core\Helper\DateTime($aAllocation['date_from']);
			$dUntil = new \Core\Helper\DateTime($aAllocation['date_until']);
			
			if($dMin !== null) {
				$dMin = min($dMin, $dFrom);				
			} else {
				$dMin = $dFrom;
			}

			$dMax = max($dMax, $dUntil);
			
		}

		$oInterval = new DateInterval('P1D');

		$oDatePeriod = new DatePeriod($dMin, $oInterval, $dMax);

		return $oDatePeriod;
	}
	
	/**
	 * @TODO Was ist das hier wieder für eine Methode? An 100 Stellen wird an Zuweisungen rumgepfuscht
	 *
	 * @param array $aNewAcco
	 * @param array $aOldAcco
	 * @param bool $bDeleteAllocationComplete
	 */
	public function saveMatchingChange($aNewAcco, $aOldAcco, $bDeleteAllocationComplete = false){

		$oAllocation = new Ext_Thebing_Allocation();
		$oAllocation->setAccommodation($this->id);
		$oAllocation->iInquiry = $this->inquiry_id;

		$dNewFrom = new \Core\Helper\DateTime($aNewAcco['from']);
		$dNewUntil = new \Core\Helper\DateTime($aNewAcco['until']);
		
		$dCurrentFrom = new \Core\Helper\DateTime($aOldAcco['from']);
		$dCurrentUntil = new \Core\Helper\DateTime($aOldAcco['until']);

		// inaktive , aktive Zuweisungen
	    $aAllos = Ext_Thebing_Allocation::getAllocationByInquiryId( $this->inquiry_id, $this->id, true, true );

		// wenn keine belegungen mus auch nichts angepasst werden
		if(empty($aAllos)){
			return true;
		}

	    foreach ((array)$aAllos as $iKey => $aAllo) {

    	    // löschen von inaktiven, aktiven Zuweisungen, wenn komplett ausserhalb des Zeitraumes
    	    // sonst des früheste und späteste Allocation-Datum ermitteln
    	   $oCurrentAllocation = Ext_Thebing_Accommodation_Allocation::getInstance($aAllo['id']);

		   if ($bDeleteAllocationComplete == true) {
				$oCurrentAllocation->delete();
				continue;
			}

			$dAllocationFrom = new \Core\Helper\DateTime($aAllo['date_from']);
			$dAllocationUntil = new \Core\Helper\DateTime($aAllo['date_until']);

			if (
    	        (
        	        (
        	            $dAllocationFrom < $dAllocationUntil &&
        	            $dAllocationUntil <= $dNewFrom
        	        ) || (
        	            $dAllocationFrom >= $dNewUntil &&
        	            $dAllocationUntil > $dAllocationFrom
        	        )
        	    ) && (
        	        ($aAllo['room_id'] == 0) || true
        	    )
    	    ) {
				$oCurrentAllocation->delete(false, true);
				// TODO: Muss der Eintrag dann nicht aus dem Array entfernt werden, damit er weiter unten nicht nochmal gesaved wird?
				unset($aAllos[$iKey]);
    		    continue;
    	    }

			if (
				$dCurrentFrom == 0 ||
				$dCurrentFrom > $dAllocationFrom
			) {
			    $dCurrentFrom = $dAllocationFrom;
			}
			if (
				$dCurrentUntil == 0 ||
				$dCurrentUntil < $dAllocationUntil
			) {
			    $dCurrentUntil = $dAllocationUntil;
			}
	    }

	    // inaktive, aktive Zuweisungen ggf. verkürzen
	    foreach ((array)$aAllos as $iKey => $aAllo) {

			$oCurrentAllocation = Ext_Thebing_Accommodation_Allocation::getInstance($aAllo['id']);

			$dAllocationFrom = new \Core\Helper\DateTime($aAllo['date_from']);
			$dAllocationUntil = new \Core\Helper\DateTime($aAllo['date_until']);

			if(
				$dAllocationFrom < $dNewFrom &&
				$dAllocationUntil > $dNewUntil
			){
				// Matching verkürzte von VORN und HINTEN
				$oCurrentAllocation->updateTime($dNewFrom, $dNewUntil);
			}elseif ($dAllocationFrom < $dNewFrom) {
				// matching verkürzen von VORN
				$oCurrentAllocation->updateTime($dNewFrom, $dAllocationUntil);
			}elseif ($dAllocationUntil > $dNewUntil) {
				// matching verkürzen von HINTEN
				$oCurrentAllocation->updateTime($dAllocationFrom, $dNewUntil);
			}

			// Wurde verändert für Kommunikation merken
			if($oCurrentAllocation->id > 0) {
				$oCurrentAllocation->allocation_changed = time();
				$oCurrentAllocation->save();
			}
		}

		// setzt eine inactive (neu zuzuweisende) Allocation VOR die bestehenden,
		// wenn neuer Zeitraum vor der frühesten Allocation beginnt
		if (
				$dNewFrom < $dCurrentFrom //&&
				#(int)$iAllosEnd == (int)$iNewUntil // rausgenommen da, wenn vorne verlängert wird u hinten verkürzt, sonst keine inaktive eingetragen wird.
				&&
				(
					//Ein Kontakt muss aber immer noch stattfinden, sonst wird verlängert/verkürzt obwohl keinerlei Zusammenhang mehr besteht, siehe #1712
					$dNewFrom <= $dCurrentUntil &&
					$dNewUntil >= $dCurrentFrom
				)
		) {
			$oAllocation->saveInactiveAllocation($dNewFrom, $dCurrentFrom);
		}

		// setzt eine inactive (neu zuzuweisende) Allocation HINTER die bestehenden
		// wenn neuer Zeitraum nach der spätesten Allocation endet
		if (
				$dNewUntil > $dCurrentUntil //&&
				#(int)$iAllosStart == (int)$iNewFrom // rausgenommen da, wenn vorne verkürzt wird u hinten verlängert, sonst keine inaktive eingetragen wird.
				&&
				(
					//Ein Kontakt muss aber immer noch stattfinden, sonst wird verlängert/verkürzt obwohl keinerlei Zusammenhang mehr besteht, siehe #1712
					$dNewFrom <= $dCurrentUntil &&
					$dNewUntil >= $dCurrentFrom
				)
		) {
			$oAllocation->saveInactiveAllocation($dCurrentUntil, $dNewUntil);
		}

	}

 	public static function getAccommodationNameForEditData($sAccommodation, $sRoom, $sMeal, $sFrom, $sUntil, $iWeeks, $iSchoolId){
		$sFrom = Ext_Thebing_Format::LocalDate($sFrom, $iSchoolId);
		$sUntil = Ext_Thebing_Format::LocalDate($sUntil, $iSchoolId);
		$sWeeks = "";
		if($iWeeks == 1){
			$sWeeks = Ext_Thebing_L10N::t('Woche');
		} else if($iWeeks > 1){
			$sWeeks = Ext_Thebing_L10N::t('Wochen');
		}
		return $sAccommodation. ', ' . $sRoom . ' / ' . $sMeal . ', ' . $sFrom . " - " . $sUntil . " (" . $iWeeks . " " . $sWeeks . ")";
	}
	
	public function getInfoString($sLanguage)
	{
		$oAccommodationCategory = Ext_Thebing_Accommodation_Category::getInstance($this->accommodation_id);
		$oAccommodationRoomType	= Ext_Thebing_Accommodation_Roomtype::getInstance($this->roomtype_id);
		$oAccommodationMeal		= Ext_Thebing_Accommodation_Meal::getInstance($this->meal_id);
		
		$sCategory				= $oAccommodationCategory->getName($sLanguage);
		$sRoomType				= $oAccommodationRoomType->getName($sLanguage);
		$sMeal					= $oAccommodationMeal->getName($sLanguage);
		
		$oSchool				= $this->getSchool();
		$iSchool				= $oSchool->getId();
		
		$sInfo					= self::getAccommodationNameForEditData($sCategory, $sRoomType, $sMeal, $this->from, $this->until, $this->weeks, $iSchool);
		
		return $sInfo;
	}
	
	public function getIndexInfoString($sLanguage, $bShort = true)
	{
		$sInfo					= $this->getAccommodationCategoryWithRoomTypeAndMeal($bShort, $sLanguage);
		return $sInfo;
	}


    public static function getRoomSharingListByInquiryId ( $iInquiryId = 0 ) {
        if ( (int)$iInquiryId == 0 ) {
            return array();
        }
        $aList = array();
        $sSQL = "
        	SELECT
        		`share_id`
        	FROM
        		`kolumbus_roomsharing`
        	WHERE
        		`master_id` = :master_id
        ";
        $aSQL = array(
            "master_id" => (int)$iInquiryId
        );
        $aList = DB::getPreparedQueryData(
            $sSQL,
            $aSQL
        );
        return $aList;
	}

	public function  __get($sName){
		if($sName == 'name'){
			$sName = '';
			$oInquiry = $this->getInquiry();
			if($oInquiry){
				$sName = $oInquiry->getCustomer()->name;
}
			return $sName;
		}
		else{
			return parent::__get($sName);
		}
	}

	public function hasActiveAllocations(): bool {
		return !empty($this->getAllocations());
	}

	public function getFirstAllocation($bWithInactive = false, $bWithDeleted = false, $bUseInstanceCache = true): ?Ext_Thebing_Accommodation_Allocation {
		return \Illuminate\Support\Arr::first($this->getAllocations($bWithInactive, $bWithDeleted, $bUseInstanceCache));
	}

	/**
	 * Liefert alle Zuweisungen diese Unterkunftsbuchung
	 *
	 * @param bool $bWithInactive
	 * @param bool $bWithDeleted
	 * @param bool $bUseInstanceCache
	 * @return Ext_Thebing_Accommodation_Allocation[]
	 */
	public function getAllocations($bWithInactive = false, $bWithDeleted = false, $bUseInstanceCache = true) {

		$aBack = array();
		$sAdditional = "";
		
		if(!$bWithDeleted) {
			$sAdditional .= " AND `status` = 0 ";
		}

		if(!$bWithInactive) {
			// Vorher stand (nur) hier active mit drin, aber das muss generell abgefragt werden?
			$sAdditional .= " AND `room_id` != 0 ";
		}

		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_accommodations_allocations`
			WHERE
				`inquiry_accommodation_id` = :inquiry_accommodation_id AND
				`active` = 1
			" . $sAdditional;
		$aSql = array();
		$aSql['inquiry_accommodation_id'] = (int)$this->id;

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		foreach((array)$aResult as $aData) {
			if($bUseInstanceCache) {
				$aBack[] = Ext_Thebing_Accommodation_Allocation::getInstance($aData['id']);
			} else {
				// Da die WDBasic keine Änderungen in den Instanz-Cache schreibt, ist das hier eigentlich der richtige Weg…
				$aBack[] = new Ext_Thebing_Accommodation_Allocation($aData['id']);
			}

		}

		return $aBack;

	}

	/**
	 * Beschreibung für ExtraWoche
	 *
	 * @param int $iExtraNightsCurrent
	 * @param string $sDisplayLanguage
	 * @return string
	 */
	public function getExtraWeekInfo($iExtraWeeks, LanguageAbstract $oLanguage, $sPeriod = '') {

		//$oInquiry = Ext_TS_Inquiry::getInstance($this->inquiry_id);
		$oInquiry = $this->getInquiry(); // das kann ja auch eine Enquiry sein ...

		$sName = (int)$iExtraWeeks.' ';

		if($iExtraWeeks == 1) {
			$sName .= $oLanguage->translate('extra Woche');
		} else {
			$sName .= $oLanguage->translate('extra Wochen');
		}

		// Gruppen Guide checken und Amount löschen
		if(
			$oInquiry->hasGroup() &&
			$oInquiry->isGuide() &&
			$oInquiry->getJourneyTravellerOption('free_all')
		) {
			// Gratis-Gruppen-Guides extra aufführen in Maske
			$sName .= ' '.$oLanguage->translate('gratis');
		}

		$sName .= ' ('.$this->getInfo($oInquiry->getSchool()->id, $oLanguage, true, $sPeriod).')';

		return $sName;

	}

	/**
	 * @param int $iAdditionalCostId
	 * @param int $iWeeks
	 * @param int $iAccommodationCount
	 * @param Tc\Service\LanguageAbstract $oLanguage
	 * @return string
	 */
	public function getAdditionalCostInfo($iAdditionalCostId, $iWeeks, $iAccommodationCount, Tc\Service\LanguageAbstract $oLanguage) {

		$oInquiry = $this->getInquiry();
		$sAccommodationDescription = $this->getAccommodationCategoryWithRoomTypeAndMeal(true, $oLanguage->getLanguage());
		$oAdditionalCost = Ext_Thebing_School_Additionalcost::getInstance($iAdditionalCostId);

		$sCostName = $oAdditionalCost->getName($oLanguage->getLanguage());

		if(
			$iAccommodationCount > 1 ||
			$oAdditionalCost->charge == 'semi'
		) {

			if($oAdditionalCost->calculate == 2) {

				$sWeeks = $oLanguage->translate('Woche');
				if($iWeeks > 1) {
					$sWeeks = $oLanguage->translate('Wochen');
				}

				$sCostName .= ' ('.$iWeeks.' '.$sWeeks.' ';

			} elseif($oAdditionalCost->calculate == 3) {

				$sWeeks = $oLanguage->translate('Nacht');
				if($iWeeks > 1) {
					$sWeeks = $oLanguage->translate('Nächte');
				}

				$sCostName .= ' ('.$iWeeks.' '.$sWeeks.' ';

			} else {
				$sCostName .= ' (';
			}

			$sCostName .= $sAccommodationDescription . ')';

		}

		// Gruppen Guide checken und Amount löschen
		if(
			$oInquiry->hasGroup() &&
			$oInquiry->isGuide() &&
			(
				$oInquiry->getJourneyTravellerOption('free_accommodation_fee') ||
				$oInquiry->getJourneyTravellerOption('free_all')
			)
		) {
			// Gratis-Gruppen-Guides extra aufführen in Maske
			$sCostName .= ' ('.$oLanguage->translate('gratis').')';
		}

		return $sCostName;
	}

	/**
	 * Beschreibung für ExtraNacht
	 *
	 * @param int $iExtraNightsCurrent
	 * @param string $mLanguage
	 * @return string
	 */
	public function getExtraNightInfo($iExtraNightsCurrent, LanguageAbstract $mLanguage, $sPeriod = '') {
		
		//$oInquiry = Ext_TS_Inquiry::getInstance($this->inquiry_id);
		$oInquiry = $this->getInquiry(); // das kann ja auch eine Enquiry sein ...

		$sName = (int) $iExtraNightsCurrent.' ';

		if($iExtraNightsCurrent == 1) {
			$sName .= $mLanguage->translate('extra Nacht');
		} else{
			$sName .= $mLanguage->translate('extra Nächte');
		}

		// Gruppen Guide checken und Amount löschen
		if(
			$oInquiry->hasGroup() &&
			$oInquiry->isGuide() &&
			$oInquiry->getJourneyTravellerOption('free_all')
		) {
			// Gratis-Gruppen-Guides extra aufführen in Maske
			$sName .= ' '.$mLanguage->translate('gratis');
		}

		$sName .= ' ('.$this->getInfo($oInquiry->getSchool()->id, $mLanguage, true, $sPeriod).')';

		return $sName;

	}

	/**
	 * Beschreibender Text dieses Kurses
	 */
	public function getInfo($iSchoolId = false, $sDisplayLanguage = false, $bShort = false, $sType = 'accommodation') {

		$oSchool = Ext_Thebing_School::getInstance($iSchoolId);
		
		$sTemplate = $oSchool->getPositionTemplate('accommodation');
		
		if($this->_oExtraNightHelper !== null) {
			$sFrom = $this->_oExtraNightHelper->getRealFrom($sType);
			$sUntil = $this->_oExtraNightHelper->getRealUntil($sType);
		} else {
			$sFrom = $this->from;
			$sUntil = $this->until;
		}

		if($sDisplayLanguage instanceof Tc\Service\LanguageAbstract) {
			$sDisplayLanguage = $sDisplayLanguage->getLanguage();
		}
		
		$aParams = array(
			'school_id'			=> $iSchoolId,
			'language'			=> $sDisplayLanguage,
			'from'				=> $sFrom,
			'until'				=> $sUntil,
			'accommodation_id'	=> $this->accommodation_id,
			'roomtype_id'		=> $this->roomtype_id,
			'meal_id'			=> $this->meal_id,
			'weeks'				=> $this->weeks,
			'format'			=> false,
			'inquiry_id'		=> (int)$this->inquiry_id
		);

		$sName = self::getOutputInfo($aParams, $bShort);

		return $sName;
	}

	/**
	 * @TODO Hat Redundanz mit getAccommodationCategoryWithRoomTypeAndMeal()
	 * @see getAccommodationCategoryWithRoomTypeAndMeal()
	 *
	 * @deprecated
	 * @param array $aParams
	 * @param bool $bShort
	 * @return string
	 */
	public static function getOutputInfo(array $aParams, $bShort = false) {

		if(empty($aParams['language'])) {
			// TODO Wird das noch gebraucht? Denn ansonsten könnte es auch eine school_id in $aParams geben…
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			$aParams['language'] = $oSchool->fetchInterfaceLanguage();
		}

		$oAccommodationCategory = Ext_Thebing_Accommodation_Category::getInstance($aParams['accommodation_id']);
		$oAccommodationRoomType = Ext_Thebing_Accommodation_Roomtype::getInstance($aParams['roomtype_id']);
		$oAccommodationMeal = Ext_Thebing_Accommodation_Meal::getInstance($aParams['meal_id']);

		$sName = '';

		if(!$bShort) {

			$sName .= $aParams['weeks'] . ' ';

			if($aParams['weeks'] == 1) {
				$sName .= \Ext_TC_Placeholder_Abstract::translateFrontend('Woche', $aParams['language']);
			} else {
				$sName .= \Ext_TC_Placeholder_Abstract::translateFrontend('Wochen', $aParams['language']);
			}

			$sName .= ' '.$oAccommodationCategory->getName($aParams['language']);
			$sName .= ' ('.$oAccommodationRoomType->getShortName($aParams['language']).' / ';
			$sName .= $oAccommodationMeal->getShortName($aParams['language']).')';

		} else {
			$sName .= $oAccommodationCategory->getShortName($aParams['language']).' / ';
			$sName .= $oAccommodationRoomType->getShortName($aParams['language']).' / ';
			$sName .= $oAccommodationMeal->getShortName($aParams['language']).':';
		}

		$sName .= ' ';

		if($aParams['format'])
		{
			$sFormat = Ext_Thebing_Format::getDateFormat($aParams['school_id']);

			if(WDDate::isDate($aParams['from'], WDDate::DB_DATE))
			{
				$oDate = new WDDate($aParams['from'], WDDate::DB_DATE);
			}
			else
			{
				$oDate = new WDDate((int)$aParams['from']);
			}

			$sName .= $oDate->get(WDDate::STRFTIME, $aParams['format']) . ' - ';

			if(WDDate::isDate($aParams['until'], WDDate::DB_DATE))
			{
				$oDate = new WDDate($aParams['until'], WDDate::DB_DATE);
			}
			else
			{
				$oDate = new WDDate((int)$aParams['until']);
			}

			$sName .= $oDate->get(WDDate::STRFTIME, $aParams['format']);
		}
		else
		{
			$sName .= Ext_Thebing_Format::LocalDate($aParams['from'], $aParams['school_id']) . ' - ';
			$sName .= Ext_Thebing_Format::LocalDate($aParams['until'], $aParams['school_id']);
		}

		if(!empty($aParams['inquiry']))
		{
			$oInquiry = $aParams['inquiry'];
		}
		elseif(!empty($aParams['inquiry_id']))
		{
			$oInquiry = Ext_TS_Inquiry::getInstance($aParams['inquiry_id']);
		}

		// Gratis-Gruppen-Guides extra aufführen in Maske
		if(
			is_object($oInquiry) &&
			$oInquiry instanceof Ext_TS_Inquiry_Abstract &&
			$oInquiry->hasGroup() &&
			$oInquiry->hasTraveller() &&
			$oInquiry->isGuide() &&
			(
				$oInquiry->getJourneyTravellerOption('free_accommodation') ||
				$oInquiry->getJourneyTravellerOption('free_all')
			)
		) {
			$sName .= ' (' . \Ext_TC_Placeholder_Abstract::translateFrontend('gratis', $aParams['language']) . ')';
		}

		return $sName;

	}

	/*
	 * Beschreibender Text, wenn der Kurs als Special auf der Rechnung erscheint
	 */
	public function getSpecialInfo($iSchoolId, $sDisplayLanguage) {

		$oFrontendLanguage = new \Tc\Service\Language\Frontend($sDisplayLanguage);
		
		$sName = $this->getLineItemDescription($oFrontendLanguage);

		$sName = $oFrontendLanguage->translate('Vergünstigung für:') . ' ' . $sName;

		return $sName;
	}

	public function __toString() {
		return $this->getAccommodationName();
    }


	/*
	 * Löscht Inaktive Zuweisungen
	 * TODO Hier darf nicht gelöscht werden muss mit Flag gelöscht werden
	 */
	public function deleteInactivAllocations(){

		$sSql = "DELETE FROM
						`kolumbus_accommodations_allocations`
					WHERE
						`inquiry_accommodation_id` = :inquiry_accommodation_id  AND
						`active` = 1 AND
						`room_id` = 0
				";

		$aSql								= array();
		$aSql['inquiry_accommodation_id']	= (int)$this->id;
		DB::executePreparedQuery($sSql, $aSql);

	}

	/*
	 * Setzt den Flag für durch das System gelöschte Zuweisungen zurück
	 */
	public function resetSystemDeletedAllocations(){
		$sSql = "UPDATE
						`kolumbus_accommodations_allocations`
					SET
						`status` = 1
					WHERE
						`inquiry_accommodation_id` = :id AND
						`status` = 2
				";

		$aSql = array();
		$aSql['id'] = (int)$this->id;
		DB::executePreparedQuery($sSql, $aSql);
	}

    /**
     * Gibt die Unterkunftskategorie zurück.
	 *
     * @return Ext_Thebing_Accommodation_Category 
     */
	public function getCategory() {
		return $this->getJoinedObject('category');
	}

	/**
	 * Liefert alle Unterkunftskosten die automatisch dem Schüler berechnet werden.
	 *
	 * @return Ext_Thebing_School_Additionalcost[]
	 */
	public function getAdditionalCosts() {

		$additionalCosts = [];

		$sqlQuery = "
			SELECT
				`kolumbus_costs_id`
			FROM
				`kolumbus_costs_accommodations` `kca` INNER JOIN
				`kolumbus_costs` `kc` ON
					`kc`.`id` = `kca`.`kolumbus_costs_id` AND
					`kc`.`idSchool` = :school_id AND
					`kc`.`active` = 1
			WHERE
				`customer_db_8_id` = :category_id AND
				`roomtype_id` = :roomtype_id AND
				`meal_id` = :meal_id AND
				`kc`.`charge` = 'auto' AND
				`kc`.`credit_provider` IN (0, ".(int) Ext_Thebing_School_Additionalcost::CREDIT_PROVIDER_ALL.")
		";

		$sqlData = array(
			'category_id' => (int)$this->accommodation_id,
			'roomtype_id' => (int)$this->roomtype_id,
			'meal_id' => (int)$this->meal_id,
			'school_id' => (int)$this->getSchool()->id,
		);

		$aResult = (array)DB::getQueryCol($sqlQuery, $sqlData);	

		foreach($aResult as $iCostId){
			$additionalCosts[] = Ext_Thebing_School_Additionalcost::getInstance($iCostId);
		}
		
		$this->checkAdditionalServicesValidity($additionalCosts);

		// Manuell gebuchte Zusatzleistungen
		$semiAutomaticCosts = $this->getJoinTableObjects('additionalservices');
		if(!empty($semiAutomaticCosts)) {
			foreach($semiAutomaticCosts as $semiAutomaticCost) {
				// Nur gebuchte Zusatzleistungen ergänzen, die auch dem Schüler berechnet werden sollen
				if($semiAutomaticCost->credit_provider != \Ext_Thebing_School_Additionalcost::CREDIT_PROVIDER_ONLY_PROVIDER) {
					$additionalCosts[] = $semiAutomaticCost;
				}
			}
		}
		
		return $additionalCosts;
	}

	/**
	 * @return Ext_Thebing_Accommodation_Roomtype
	 */
	public function getRoomType() {
		return Ext_Thebing_Accommodation_Roomtype::getInstance((int)$this->roomtype_id);
	}

	/**
	 * @return Ext_Thebing_Accommodation_Room[]
	 */
	public function getRooms() {

		$aAllocations = Ext_Thebing_Allocation::getAllocationByInquiryId($this->getInquiry()->id, $this->id, true);

		return array_values(array_map(
			function(array $aAllocation) {
				return Ext_Thebing_Accommodation_Room::getInstance($aAllocation['room_id']);
			},
			$aAllocations
		));

	}

	/**
	 * @return Ext_Thebing_Accommodation_Meal 
	 */
	public function getMeal() {
		return Ext_Thebing_Accommodation_Meal::getInstance((int)$this->meal_id);
	}

	public function validate($bThrowExceptions = false) {
		
		$aErrors = parent::validate($bThrowExceptions);
		
		if(
			$aErrors === true
		){
			$aErrors = array();
		}
		
		$oCategory = $this->getJoinedObject('category');

		if(
            $this->isActive() &&
			!$oCategory->isValid($this->until)
		){
			$aErrors[$this->_sTableAlias.'.accommodation_id'][] = 'ACCOMMODATION_CATEGORY_NOT_VALID';
		}
		
		$oRoomType = $this->getRoomType();
		
		if(
            $this->isActive() &&
			!$oRoomType->isValid($this->until)
		){
			$aErrors[$this->_sTableAlias.'.roomtype_id'][] = 'ROOMTYPE_NOT_VALID';
		}
		
		$oMeal = $this->getMeal();
		
		if(
            $this->isActive() &&
			!$oMeal->isValid($this->until)
		){
			$aErrors[$this->_sTableAlias.'.meal_id'][] = 'MEAL_NOT_VALID';
		}

		// Zwingend notwendig zum Vermeiden von »Geisterzuweisungen« #17972
		if (
			!$this->isActive() &&
			!empty($this->getAllocations())
		) {
			$aErrors[$this->_sTableAlias.'.accommodation_id'][] = 'ACCOMMODATION_ALLOCATIONS_EXISTS';
		}
		
//        if(is_array($aErrors)){
//            // %s ggf. durch namen ersetzen
//            foreach($aErrors as $sKeyFull => $aErrorList){
//                if(is_array($aErrorList)){
//                    $sKey = explode('.', $sKeyFull);
//                    $sKey = end($sKey);
//                    foreach($aErrorList as $iKey => $sError){
//                        if($sKey == 'roomtype_id'){
//                            $oRoomType  = $this->getRoomType();
//                            $sName      = $oRoomType->getName();
//                        } else if($sKey == 'meal_id'){
//                            $oMeal      = $this->getMeal();
//                            $sName      = $oMeal->getName();
//                        } else if($sKey == 'accommodation_id'){
//                            $oCategory  = $this->getJoinedObject('category');
//                            $sName      = $oCategory->getName();
//                        } else {
//                            $sName      = '%s';
//                        }
//                        $sError     = str_replace('%s', $sName, $sError);
//                        $aErrors[$sKeyFull][$iKey] = $sError;
//                    }
//                }
//            }
//        }
        
		if(empty($aErrors)){
			$aErrors = true;
		}
		
		return $aErrors;
	}

	/**
	 * @param string $sUntil
	 * @param array $aFilter
	 * @return array
	 */
	public static function getInvalidEntriesByFilter($sUntil, $aFilter) {

		$aSql = [];
		$sWhere = "";
		
		if(
			// Beim Löschen von valid_until ist $sValidUntil leer
			!\Core\Helper\DateTime::isDate($sUntil, 'Y-m-d') ||
			$sUntil === '0000-00-00'
		) {
			return [];
		}

		if(!\Core\Helper\DateTime::isDate($sUntil, 'Y-m-d')) {
			throw new InvalidArgumentException('Invalid date given: '.$sUntil);
		}
		
		$aFilter = (array)$aFilter;
		$iCounter = 0;
		foreach($aFilter as $sKey => $mValue) {
			$sWhere .= ' AND `ts_ija`.#filter_key_' . $iCounter . ' = :filter_value_' . $iCounter;
			$aSql['filter_key_'.$iCounter] = $sKey;
			$aSql['filter_value_'.$iCounter] = $mValue;
			
			$iCounter++;
		}
		
		$aSql['until'] = $sUntil;
		
		$sSql = "
			SELECT
				`ts_ija`.`id`
			FROM
				`ts_inquiries_journeys_accommodations` `ts_ija` INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`id` = `ts_ija`.`journey_id` AND
					`ts_ij`.`active` = 1 AND
				    `ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' INNER JOIN
				`ts_inquiries` `ts_i` ON
					`ts_i`.`id` = `ts_ij`.`id` AND
					`ts_i`.`active` = 1
			WHERE
				`ts_ija`.`active` = 1 AND
				`ts_ija`.`until` > :until
				{$sWhere}
		";
		
		$aIds = (array)DB::getQueryCol($sSql, $aSql);

		return $aIds;
	}
	
	/**
	 * @return Ext_Thebing_Accommodation_Payment[]
	 */
	public function getPayments()
	{
		return (array)$this->getJoinedObjectChilds('accounting_payments_active');
	}
	
	public function getKey()
	{
		return 'accommodation';
	}
	
	public function checkPaymentStatus($sFilterFrom = '', $sFilterUntil = '', $aNewAccommodationData = null){

		if($aNewAccommodationData === null)
		{
			$iAccommodationId   = $this->getOriginalData('accommodation_id');
			$iRoomtype          = $this->getOriginalData('roomtype_id');
			$iMeal              = $this->getOriginalData('meal_id');
		} else {
            $iAccommodationId   = $aNewAccommodationData['accommodation_id'];
            $iRoomtype          = $aNewAccommodationData['roomtype_id'];
            $iMeal              = $aNewAccommodationData['meal_id'];
        }

		$aPayments = array();
		// #2139 (#2338)
		// wenn man eine Unterkunft ändert und diese bezahlt ist
		// wurde bisher nur ein Fehler geworfen wenn der Zeitraum mit der Bezahlung in die quere kam
		// es muss jedoch auch ein Fehler kommen wenn man die art der unterkunft ändern will
		// da die bezahlung und das matching mit dieser info arbeiten und die daten sonst fehlerhaft werden
        // 
        // #4445 raum und verpflegung sollen auch geprüft werden
        //
		if(
            $this->_checkOriginalDataChangeHelper($iAccommodationId, $this->accommodation_id) ||
            $this->_checkOriginalDataChangeHelper($iRoomtype, $this->roomtype_id) ||
            $this->_checkOriginalDataChangeHelper($iMeal, $this->meal_id) ||
            // wurde gelöscht oder deaktiviert ( bei neu anlegen machts nichts da get Payments nichts liefern wird )
            $this->visible == 0 ||
            $this->active == 0
		){
			$aPayments += $this->getPayments();
		} else {
			$aPayments += parent::checkPaymentStatus($sFilterFrom, $sFilterUntil);
		}
		
		return $aPayments;
	}

    protected function _checkOriginalDataChangeHelper($iOriginal, $iCurrent){
        if(// Raumart hat sich verändert
            (
                $iOriginal > 0 ||
                $iCurrent > 0    
            ) &&
            $iOriginal != $iCurrent    
        ){
            return true;
        }
        return false;
    }

	/**
	 * Helper-Klasse für Extranächte setzen.
	 *
	 * @param null|Ext_TS_Service_Accommodation_Helper_Extranights $oHelper
	 */
	public function setExtranightHelper(Ext_TS_Service_Accommodation_Helper_Extranights $oHelper = null) {
		$this->_oExtraNightHelper = $oHelper;
	}

    public function isEmpty(){

		// Nur die drei Werte, da Datumsangaben immer vom Kurs kopiert werden
        if(
            $this->accommodation_id <= 0 && 
            $this->roomtype_id <= 0 && 
            $this->meal_id <= 0
        ) {
            return true;
        }
        
        return false;
    }
	
    /**
     * ermittelt den Wochenstarttag
     * @return int
     */
    public function getServiceStartDay(){
		// TODO Ext_TC_Util::convertWeekdayToInt()
		$sStart = $this->getCategory()->getAccommodationStart($this->getSchool());

        if($sStart == 'sa'){
            return 6;
        } else if($sStart == 'so'){
            return 0;
        } else {
            return 1;
        }
    }
    
    /**
     *  ermitellt den End Wochentag
     * @return int
     */
    public function getServiceEndDay(){
        $oSchool    = $this->getSchool();
        $iStart     = $this->getServiceStartDay();
        $iNights    = $this->getCategory()->getAccommodationInclusiveNights($oSchool);
        $iStart     = $iStart + $iNights;
        if($iStart > 6){
            $iStart = $iStart - 7;
        }
        return $iStart;
    }

	/**
	 * Prüfen, ob die vorhandenen Zuweisungen der Unterkunftsbuchung korrekt sind
	 *
	 * @return bool
	 */
	public function checkAllocationContext(bool $checkPlausibility=true) {

		// Auch inaktive Zuweisungen holen, da ganzer Zeitraum geprüft wird
		$allocations = $this->getAllocations(true, false, false);

		// Wenn keine Zuweisungen vorhanden sind, muss auch nichts geprüft werden
		if(empty($allocations)) {
			return true;
		}

		// Brauchbare DateTime-Objekte von den Zuweisungen holen
		$aAllocationDates = Ext_TC_Util::getDateTimeTuples($allocations);

		// Sortierung ist wichtig, da von der frühsten bis zur spätesten Zuweisung iteriert wird
		$sortedAllocations = Ext_Thebing_Allocation::sortAllocationsByDate($allocations);

		$oAccommodationFrom = new DateTime($this->from);
		$oAccommodationUntil = new DateTime($this->until);

		$oFirstStartDate = $aAllocationDates[reset($sortedAllocations)->id][0];
		$oLastEndDate = $aAllocationDates[end($sortedAllocations)->id][1];

		// Erste Prüfung: Ist der Zeitraum der Unterkunftsbuchung durch Zuweisungen abgedeckt?
		if(
			$oAccommodationFrom != $oFirstStartDate ||
			$oAccommodationUntil != $oLastEndDate
		) {
			return false;
		}

		// Zweite Prüfung: Ist der Zeitraum lückenlos zugewiesen?
		// Es wird einfach geprüft, ob das Startdatum der nächsten Zuweisung das Enddatum der letzten Zuweisung ist
		// Somit werden Lücken gefunden; genauso wie doppelte Zuweisungen erkannt werden können
		$oTmpLastDate = null;
		$aTmpAllocationDates = array();
		foreach($sortedAllocations as $oAllocation) {

			// Prüfen, ob Zuweisung mit dem gleichen Zeitraum schon existiert
			// Bewusst nicht DateTime benutzen, da dies mit in_array() wohl nicht funktionieren kann (unterschiedliche Objekte)
			if(
				in_array($oAllocation->from, $aTmpAllocationDates) &&
				in_array($oAllocation->until, $aTmpAllocationDates)
			) {
				return false;
			} else {
				$aTmpAllocationDates[] = $oAllocation->from;
				$aTmpAllocationDates[] = $oAllocation->until;
			}

			// Prüfen, ob Zeiträume aufeinander folgen
			if($oTmpLastDate === null) {
				// Beim ersten Durchlauf nur Enddatum der aktuellen Zuweisung setzen
				$oTmpLastDate = $aAllocationDates[$oAllocation->id][1];
			} else {
				if($aAllocationDates[$oAllocation->id][0] != $oTmpLastDate) {
					// Startdatum der aktuellen Zuweisung ist nicht Enddatum der letzten Zuweisung
					return false;
				}

				// Letztes Datum auf aktuelles Datum setzen für nächsten Durchlauf
				$oTmpLastDate = $aAllocationDates[$oAllocation->id][1];
			}

		}

		// Prüfen. ob jede einzelne Zuweisung für den Zeitraum im Raum (plus Bett) plausibel ist
		if($checkPlausibility === true) {
			foreach($sortedAllocations as $oAllocation) {
				if(!$oAllocation->checkAllocationPlausibility()) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Errechnet den Unterkunftspreis inkl. Extranächte.
	 *
	 * Die Methode berücksichtigt den Frühbucherrabatt ausgehend vom aktuellen Datum.
	 *
	 * Der zurückgegebene Preis beinhaltet die Umsatzsteuer sowie den Preis für Extranächte und Extrawochen,
	 * weitere Zusatzkosten werden nicht beachtet da diese nicht von der Unterkunft alleine sondern der gesamten
	 * Buchung abhängen.
	 *
	 * @uses Ext_TS_Inquiry_Journey_Accommodation::getAccommodationPriceWithoutExtraNights()
	 * @uses Ext_TS_Inquiry_Journey_Accommodation::getAccommodationPriceForExtraNights()
	 * @return float
	 */
	/*public function getAccommodationPriceWithExtraNights() {

		return $this->getAccommodationPriceWithoutExtraNights() + $this->getAccommodationPriceForExtraNights();

	}*/

	/**
	 * Errechnet den Unterkunftspreis exkl. Extranächte.
	 *
	 * Die Methode berücksichtigt den Frühbucherrabatt ausgehend vom aktuellen Datum.
	 *
	 * Der zurückgegebene Preis beinhaltet die Umsatzsteuer, weitere Zusatzkosten werden nicht beachtet da diese
	 * entweder nicht berechnet werden sollen (Extranächte und Extrawochen) oder nicht von der Unterkunft alleine
	 * sondern der gesamten Buchung abhängen.
	 *
	 * @see Ext_TS_Inquiry_Journey_Accommodation::getAccommodationPriceForExtraNights()
	 * @return float
	 */
	/*public function getAccommodationPriceWithoutExtraNights() {

		// Die Preisberechnung funktioniert nur wenn eine ID gesetzt ist, da an diversen Stellen abgefragt wird ob
		// die ID > 0 ist und ansonsten einfach nichts berechnet wird
		$iOrigId = $this->_aData['id'];
		if($iOrigId < 1) {
			$this->_aData['id'] = PHP_INT_MAX;
		}

		$oAmount = new Ext_Thebing_Accommodation_Amount();
		$oAmount->setInquiryAccommodation($this);
		$oAmount->setDiscountTime(time()); // Für Frühbucherrabatt

		$fPrice = $oAmount->calculate();
		$fPrice = $this->addVatToPrice($fPrice);

		// Die ID wieder auf die original ID zurücksetzen
		$this->_aData['id'] = $iOrigId;

		return (float)$fPrice;

	}*/

	/**
	 * Errechnet den Unterkunftspreis für die Extranächte.
	 *
	 * Die Methode berücksichtigt den Frühbucherrabatt ausgehend vom aktuellen Datum.
	 *
	 * Der zurückgegebene Preis beinhaltet die Umsatzsteuer und NUR den Preis für Extranächte und Extrawochen,
	 * der Grundpreis ist NICHT enthalten.
	 *
	 * @see Ext_TS_Inquiry_Journey_Accommodation::getAccommodationPriceWithoutExtraNights()
	 * @return float
	 */
	/*public function getAccommodationPriceForExtraNights() {

		// Die Preisberechnung funktioniert nur wenn eine ID gesetzt ist, da an diversen Stellen abgefragt wird ob
		// die ID > 0 ist und ansonsten einfach nichts berechnet wird
		$iOrigId = $this->_aData['id'];
		if($iOrigId < 1) {
			$this->_aData['id'] = PHP_INT_MAX;
		}

		$oAmount = new Ext_Thebing_Accommodation_Amount();
		$oAmount->setInquiryAccommodation($this);
		$oAmount->setDiscountTime(time()); // Für Frühbucherrabatt

		$fPrice = $oAmount->calculateExtraNight() + $oAmount->calculateExtraWeek();
		$fPrice = $this->addVatToPrice($fPrice);

		// Die ID wieder auf die original ID zurücksetzen
		$this->_aData['id'] = $iOrigId;

		return (float)$fPrice;

	}*/

	/**
	 * Gibt die Anzahl der Extranächte zurück.
	 *
	 * Extrawochen sind in der Anzahl der Extranächte enthalten.
	 *
	 * @return int
	 */
	/*public function getExtraNightCount() {

		// Die Preisberechnung funktioniert nur wenn eine ID gesetzt ist, da an diversen Stellen abgefragt wird ob
		// die ID > 0 ist und ansonsten einfach nichts berechnet wird
		$iOrigId = $this->_aData['id'];
		if($iOrigId < 1) {
			$this->_aData['id'] = PHP_INT_MAX;
		}

		$mExtraNights = $this->getInquiry()->getExtraNightsWithWeeks('forCalculate', $this);
		$iExtraNights = 0;

		// Die ID wieder auf die original ID zurücksetzen
		$this->_aData['id'] = $iOrigId;

		if(!is_array($mExtraNights)) {
			return $iExtraNights;
		}

		foreach($mExtraNights as $mExtraNight) {
			if(
				is_array($mExtraNight) &&
				array_key_exists('nights', $mExtraNight)
			) {
				$iExtraNights += (int)$mExtraNight['nights'];
			}
		}

		return $iExtraNights;

	}*/

	/**
	 * @param float $fPrice
	 * @return float
	 */
	/*private function addVatToPrice($fPrice) {

		$fPrice = (float)$fPrice;

		$oInquiry = $this->getInquiry();
		$oJourney = $oInquiry->getJourney();
		$oSchool = Ext_Thebing_School::createSchoolObjectFromArgument($oJourney->school_id);

		if($oSchool->getTaxStatus() != Ext_Thebing_School::TAX_EXCLUSIVE) {
			return $fPrice;
		}

		$iTaxRate = 0;
		$iTaxCategory = Ext_TS_Vat::getDefaultCombination('Ext_Thebing_Accommodation', $this->id, $oSchool);
		if($iTaxCategory > 0) {
			$iTaxRate = Ext_TS_Vat::getTaxRate($iTaxCategory, $oSchool->id);
		}

		$aTax = Ext_TS_Vat::calculateExclusiveTaxes($fPrice, $iTaxRate);
		$fPrice += (float)$aTax['amount'];

		return $fPrice;

	}*/

	public function getRegistrationFormData(): array {

		$dFrom = \Ext_Thebing_Util::convertDateStringToDateOrNull($this->from);
		$dUntil = \Ext_Thebing_Util::convertDateStringToDateOrNull($this->until);

		return [
			'accommodation' => !empty($this->accommodation_id) ? (int)$this->accommodation_id : null,
			'roomtype' => !empty($this->roomtype_id) ? (int)$this->roomtype_id : null,
			'board' => !empty($this->meal_id) ? (int)$this->meal_id : null,
			'start' => $dFrom !== null ? 'date:'.$dFrom->toDateString() : null,
			'end' => $dUntil !== null ? 'date:'.$dUntil->toDateString() : null,
			'additional_services' => array_values(array_map(function (Ext_Thebing_School_Additionalcost $oFee) {
				// getRegistrationFormData-Aufruf nicht möglich, da falsches Objekt
				return ['fee' => $oFee->id];
			}, $this->getJoinTableObjects('additionalservices')))
		];

	}
	
	public function getProviderRequestStatus() {
		
		$sqlQuery = "
			SELECT
				`ts_arr`.`accommodation_provider_id`,
				MAX(`ts_arr`.`sent`) `sent`,	
				MAX(`ts_arr`.`accepted`) `accepted`,	
				MAX(`ts_arr`.`rejected`) `rejected`
			FROM
				`ts_accommodation_requests` `ts_ar` JOIN
				`ts_accommodation_requests_recipients` `ts_arr` ON
					`ts_ar`.`id` = `ts_arr`.`request_id`
			WHERE 
				`ts_ar`.`inquiry_accommodation_id` = :inquiry_accommodation_id
			GROUP BY
				`ts_arr`.`accommodation_provider_id`
		";
		$sqlParam = [
			'inquiry_accommodation_id' => (int)$this->id
		];
		$providerStatus = \DB::getQueryRowsAssoc($sqlQuery, $sqlParam);

		return $providerStatus;
	}
	
	public function getPossibleProviders(bool $ignoreCategory=false, bool $ignoreRoomtype=false) {
		
		$inquiry = $this->getInquiry();
		
		$matching = new Ext_Thebing_Matching();
		$matching->setFrom(new DateTime($this->from));
		$matching->setUntil(new DateTime($this->until));

		$matching->oAccommodation = $this;
		$matching->bIgnoreCategory = $ignoreCategory;#(bool)$request->get('ignore_category');
		$matching->bIgnoreRoomtype = $ignoreRoomtype;#(bool)$request->get('ignore_roomtype');

		if($this->getCategory()->type_id == Ext_Thebing_Accommodation_Category::TYPE_HOSTFAMILY) {
			$providers = $matching->getMatchedFamilie($inquiry);
		} else {
			$providers = $matching->getOtherMatched($inquiry);
		}
		
		return $providers;
	}

	public function getCategoryName() {

		$this->getCategory()->getName();
	}

	public function getCommunicationDefaultApplication(): string
	{
		return \TsAccommodation\Communication\Application\Communication\CustomerAgency::class;
	}

	public function getCommunicationLabel(\Tc\Service\LanguageAbstract $l10n): string
	{
		return ''; // TODO;
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
