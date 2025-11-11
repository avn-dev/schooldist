<?php

use Core\DTO\DateRange;
use Core\Helper\DateTime;

class Ext_TS_Inquiry_Journey_Holiday_Split {
    
    /**
     * @var Ext_TS_Inquiry_Journey 
     */
    protected $_oJourney;
    
    /**
     * @var Ext_Thebing_School 
     */
    protected $_oSchool;
    
	/**
	 * @var array
	 */
	protected $aSchoolHolidays;

	/**
	 * @var array
	 */
	protected $aServiceHolidays;

	/** @var Ext_TS_Inquiry_Holiday */
	protected $_oServiceHoliday;

    protected $_aOffsets            = array();
    protected $_aErrors             = array();
	protected $_aJourneyCourses		= array();
	protected $_aJourneyAccommodations = array();
    protected $_bServiceMoved       = false;
    protected $_bServiceSplitted    = false;

	public function __construct(Ext_TS_Inquiry_Journey $oJourney) {

		$this->_oJourney = $oJourney;
		$this->_oSchool = $oJourney->getSchool();

		// Schulferien als collection holen, ohne Zeitraumeingrenzung da wir diese Collection
		// für alle Service benutzen
		$this->aSchoolHolidays = array_map(function(Ext_Thebing_Absence $oHoliday) {
			return [
				'from' => $oHoliday->from,
				'until' => $oHoliday->until,
				'weeks' => self::getSchoolHolidayWeeks($oHoliday->from, $oHoliday->until),
				'service' => null
			];
		}, $this->_oSchool->getSchoolHolidays());

		// leere Service ferien definieren damit wir uns array castings sparren
		$this->aServiceHolidays = array(
			'Ext_TS_Inquiry_Journey_Course' => array(),
			'Ext_TS_Inquiry_Journey_Accommodation' => array()
		);
	}
    
    public function hasMoved(){
        return $this->_bServiceMoved;
    }
    
    public function hasSplittings(){
        return $this->_bServiceSplitted;
    }
    
    public function getErrors(){
        return $this->_aErrors;
    }
    
    /**
     * Setzt die Leistungsferien die man im SR eingeben kann und gruppiert diese um
     *
     * @param Ext_TS_Inquiry_Holiday $oHoliday
     * @param array $aHolidays
     */
    public function setServiceHolidays(Ext_TS_Inquiry_Holiday $oHoliday, $aHolidays) {
        
        $aFinalHolidayData = array();
 
        // Ferien aufsplitten nach dem Service
        // nur die Ferien eintragen die den Service betreffen
        foreach($aHolidays as $aHoliday) {
            if(!empty($aHoliday['course_ids'])) {
                foreach($aHoliday['course_ids'] as $iJourneyService) {
                    $aData = array(
                        'from' => $aHoliday['from'],
                        'until' => $aHoliday['until'],
                        'weeks' => $aHoliday['weeks'],
                        'service' => $iJourneyService
                    );
                    $aFinalHolidayData['Ext_TS_Inquiry_Journey_Course'][] = $aData;
                }
            }
   
            if(!empty($aHoliday['following_courses'])) {
                foreach($aHoliday['following_courses'] as $iJourneyService) {
	                // Sofern das Ferienende auf ein Montag fällt, muss die Woche
	                // um eine weitere Woche nach hinten verschoben werden
	                $iWeeks = (int)$aHoliday['weeks'];
					$dHolidayUntil = new \Core\Helper\DateTime($aHoliday['until']);
	                if($dHolidayUntil->format('N') == 1) {
						// Woche darf nur dann addiert werden, wenn dieser Montag nicht wiederum in Ferien (Schulferien) reinfällt
						$bMondayIsInHoliday = false;
						foreach($aHolidays as $aHoliday) {
							if($dHolidayUntil->isBetween(new DateTime($aHoliday['from']), new DateTime($aHoliday['until']))) {
								$bMondayIsInHoliday = true;
								break;
							}
						}

						if(!$bMondayIsInHoliday) {
							$iWeeks++;
						}
	                }

                    $this->_aOffsets['Ext_TS_Inquiry_Journey_Course'][$iJourneyService] = $iWeeks;
                }
            }
            
            if(!empty($aHoliday['accommodation_ids'])) {
                foreach($aHoliday['accommodation_ids'] as $iJourneyService) {
                    $aData = array(
                        'from' => $aHoliday['from'],
                        'until' => $aHoliday['until'],
                        'weeks' => $aHoliday['weeks'],
                        'service' => $iJourneyService
                    );
                    $aFinalHolidayData['Ext_TS_Inquiry_Journey_Accommodation'][] = $aData;
                }
            }
            
            if(!empty($aHoliday['following_accommodations'])) {
                foreach($aHoliday['following_accommodations'] as $iJourneyService) {
                    $this->_aOffsets['Ext_TS_Inquiry_Journey_Accommodation'][$iJourneyService] = (int)$aHoliday['weeks'];
                }
            }

        }

        $this->aServiceHolidays = $aFinalHolidayData;
        $this->_oServiceHoliday = $oHoliday;

    }
    
    /**
     * splittet alle Leistungen der Reise anhand der geladenen und gesetzten ferien
     */
    public function split(){
        
        $this->_aJourneyCourses = $this->_oJourney->getJoinedObjectChilds('courses', true);
        foreach($this->_aJourneyCourses as $oService){
            if($oService){
                $this->splitService($oService, $this->_aJourneyCourses);
            }
        }    
        
        $this->_aJourneyAccommodations = $this->_oJourney->getJoinedObjectChilds('accommodations', true);
        foreach($this->_aJourneyAccommodations as $oService){
            if($oService){
                $this->splitService($oService, $this->_aJourneyAccommodations);
            }
        }
        
    }
    
    /**
     * ein Service Splitten
     * @param Ext_TS_Inquiry_Journey_Service $oService
     */
    public function splitService(Ext_TS_Inquiry_Journey_Service $oService, &$aAllServices){


        if(
            // Damit muss vorhanden sein sonst kann nicht gesplittete werden
            $oService->from != "0000-00-00" &&
            $oService->until != "0000-00-00" &&
            // Nur aktive, es gibt ggf. gelöschte objekte da erst ganz am ende gespeichert wird und sie erst dort rausfallen würden
            $oService->isActive()
        ){

            if(
                // Manche Leistungen(Kurse) können auch in schulferien Stattfinden
                 $oService->splitByHolidays() &&
                // nur geänderte oder neue dürfen gesplittet werden
                // das wurde so abgesprochen um die performance hoch zu halten
                /*
                 * Es müssen IMMER alle Kurse betrachtet werden, da ein Kurs einen unveränderten Kurs
                 * in die Schulferien schieben könnte, welcher dann wiederum verschoben werden muss,
                 * was wiederum eine Teilung hervorrufen könnte usw.
                 */
                //$oService->isChanged() &&
                $oService instanceof Ext_TS_Inquiry_Journey_Course // Nur kurse werden von Schulferien beinflusst #3997
            ){
                $this->_splitService($oService, $this->aSchoolHolidays);
            }

            $sClass = $oService->getClassName();
            
            // Leistungsferien splitten
            // hier gibt es noch sonder fälle ( verschieben etc.. )
            if(isset($this->aServiceHolidays[$sClass])){
                $this->_splitService($oService, $this->aServiceHolidays[$sClass], $aAllServices);
            }

        }

    }
    
    public function modifyDateByWeekday($oDate, $iWeekDay, $sWeekDayType = 'startday'){
       
        $aDays = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
        $sDay = $aDays[$iWeekDay];
        
        $iDateWeekDay = $oDate->format('w');
        
        //if($iWeekDay !=  $iDateWeekDay){

            if($sWeekDayType == 'endday'){
                $oDate->modify('- 1 Week');
            }

            $oDate->modify('next '.$sDay);

        //}
        
    }
    
    protected function _checkServiceForHolidayErrors(Ext_TS_Inquiry_Journey_Service $oService){
        
		//$bErrorHolidayFrom = false;
		//$bErrorHolidayUntil = false;

		$oInquiry = $this->_oJourney->getInquiry();
		$iInquiry = $oInquiry->id;

		if($oService instanceof Ext_TS_Inquiry_Journey_Accommodation){
			$sKey = 'accommodation';
			$sParentField = 'accommodation_id';
			//$aStudentHolidayInfos = Ext_Thebing_Inquiry_Holidays::_getHolidaySplittings($oService->id, 'both', 'accommodation');
		} else if($oService instanceof Ext_TS_Inquiry_Journey_Course){
			$sKey = 'course';
			$sParentField = 'course_id';
			//$aStudentHolidayInfos = Ext_Thebing_Inquiry_Holidays::_getHolidaySplittings($oService->id, 'both', 'course');
		} else {
			throw new LogicException('Wrong service for '.__METHOD__.'()');
		}

        /*if(count($aStudentHolidayInfos) > 0){

            foreach((array)$aStudentHolidayInfos as $sStudentCourseHolidayInfo){
                // Splittung
                if(
                   $sStudentCourseHolidayInfo['inquiry_'.$sKey.'_id'] > 0 &&
                   $sStudentCourseHolidayInfo['inquiry_split_'.$sKey.'_id'] > 0
                ){
                    // linker part darf ende nicht ändern
                    if(
                        $sStudentCourseHolidayInfo['inquiry_'.$sKey.'_id'] == $oService->id && 
                        $oService->isChanged() &&
                        $oService->isChanged('until')
                    ){
                        $bErrorHolidayUntil = true;
                        break;
                    // rechter part darf anfang nicht ändern
                    } else if(
                        $sStudentCourseHolidayInfo['inquiry_split_'.$sKey.'_id'] == $oService->id && 
                        $oService->isChanged() &&
                        $oService->isChanged('from')
                    ){
                        $bErrorHolidayFrom = true;
                        break;
                    } 
                // Verschiebung
                } else if(
                   $sStudentCourseHolidayInfo['inquiry_'.$sKey.'_id'] > 0 &&
                   $sStudentCourseHolidayInfo['inquiry_split_'.$sKey.'_id'] <= 0
                ){
                    // wenn verschoben wurde ( nach rechts ) darf der anfang nicht verändert werden
                    if(
                        $sStudentCourseHolidayInfo['inquiry_'.$sKey.'_id'] == $oService->id && 
                        $oService->isChanged() &&
                        $oService->isChanged('from')
                    ){
                        $bErrorHolidayFrom = true;
                        break;
                    }
                }
            }          


            // Bestimmte Kurseinstellungen dürfen sich NIE ändern wenn Ferien gebucht sind
            if(       
                $bErrorHolidayUntil
            ){  
                $aError             = array();
                $aError['message']  = L10N::t('Für die Leistung ({service}) wurden Ferien eingetragen, das Enddatum kann nicht verändert werden.');
                $aError['message']  = str_replace('{service}', $oService->getInfo(), $aError['message']);
                $aError['input']    = array(
                    'id' => $sKey.'['.$iInquiry.']['.(int)$oService->id.'][until]'
                );
                $this->addError($aError);
            }         

            if(   
                $bErrorHolidayFrom
            ){
                $aError             = array();
                $aError['message']  = L10N::t('Für die Leistung ({service}) wurden Ferien eingetragen, das Stardatum kann nicht verändert werden.');
                $aError['message']  = str_replace('{service}', $oService->getInfo(), $aError['message']);
                $aError['input']    = array(
                    'id' => $sKey.'['.$iInquiry.']['.(int)$oService->id.'][from]'
                );
                $this->addError($aError);
            }         

            if(    
                $oService->id > 0 &&
                $oService->$sParentField != $oService->getOriginalData($sParentField)
            ){  
                $aError             = array();
                $aError['message']  = L10N::t('Für den Leistung ({service}) wurden Ferien eingetragen, sie kann nicht verändert werden.');
                $aError['message']  = str_replace('{service}', $oService->getInfo(), $aError['message']);
                $aError['input']    = array(
                    'id' => $sKey.'['.$iInquiry.']['.(int)$oService->id.']['.$sKey.'_id]'
                );
                $this->addError($aError);
            }         

            if(      
                $oService->id > 0 && // Bei readonly SR ist das nicht vorhanden und darf keinen Fehler verursachen					
                $oService->isChanged('visible')
            ){     
                $aError             = array();
                $aError['message']  = L10N::t('Für die Leistung ({service}) wurden Ferien eingetragen, sie kann nicht deaktiviert werden.');
                $aError['message']  = str_replace('{service}', $oService->getInfo(), $aError['message']);
                $aError['input']    = array(
                    'id' => $sKey.'['.$iInquiry.']['.(int)$oService->id.'][visible]'
                );
                $this->addError($aError);
            }   
            
            if( 
                $oService instanceof Ext_TS_Inquiry_Journey_Accommodation &&
                $oService->id > 0 && // Bei readonly SR ist das nicht vorhanden und darf keinen Fehler verursachen					
                $oService->isChanged('roomtype_id')
            ){     
                $aError             = array();
                $aError['message']  = L10N::t('Für die Unterkunft ({service}) wurden Ferien eingetragen, der Raum kann nicht verändert werden.');
                $aError['message']  = str_replace('{service}', $oService->getInfo(), $aError['message']);
                $aError['input']    = array(
                    'id' => $sKey.'['.$iInquiry.']['.(int)$oService->id.'][roomtype_id]'
                );
                $this->addError($aError);
            }   
            
            if(      
                $oService instanceof Ext_TS_Inquiry_Journey_Accommodation &&
                $oService->id > 0 && // Bei readonly SR ist das nicht vorhanden und darf keinen Fehler verursachen					
                $oService->isChanged('meal_id')
            ){     
                $aError             = array();
                $aError['message']  = L10N::t('Für die Unterkunft ({service}) wurden Ferien eingetragen, die Mahlzeit kann nicht verändert werden.');
                $aError['message']  = str_replace('{service}', $oService->getInfo(), $aError['message']);
                $aError['input']    = array(
                    'id' => $sKey.'['.$iInquiry.']['.(int)$oService->id.'][meal_id]'
                );
                $this->addError($aError);
            }   
        }*/

		if(
			$oService->exist() &&
			$oService->isChanged()
		) {
        	$aRelatedServices = $oService->getRelatedServices();
			$aRelatedServices = array_filter($aRelatedServices, function(Ext_TS_Inquiry_Journey_Service $oJourneyService) {
				// Muss gefiltert werden, da sonst bei jetzt neuen Kursbuchungen (durchs Splitten) sonst auch der Fehler kommen würde
				return $oJourneyService->exist();
			});

			// Nach Enddatum sortieren, damit man letzten Kurs hinten hat
			usort($aRelatedServices, function(Ext_TS_Inquiry_Journey_Service $oService1, Ext_TS_Inquiry_Journey_Service $oService2) {
				return new DateTime($oService1->from) > new DateTime($oService2->from);
			});

			// Hier kommt immer ein Eintrag zurück. daher müssen das mindestens zwei Leistungen sein
			// Gelöschte Leistungen kommen hier bereits nicht mehr vor
        	if(count($aRelatedServices) > 1) {

				if($oService->id == reset($aRelatedServices)->id) {
					// Bei der ersten Leistung dürfen Wochen und Startdatum verändert werden
					$aFields = ['until', 'visible'];
				} elseif($oService->id == end($aRelatedServices)->id) {
					// Bei der letzten Leistung dürfen Wochen und Enddatum verändert werden
					$aFields = ['from', 'visible'];
				} else {
					// Bei dazwischen liegenden Leistungen darf gar nichts verändert werden
					$aFields = ['from', 'until', 'weeks', 'visible'];
				}

				if($oService instanceof Ext_TS_Inquiry_Journey_Accommodation) {
					$aFields[] = 'roomtype_id';
					$aFields[] = 'meal_id';
				}

        		foreach($aFields as $sField) {
        			if($oService->isChanged($sField)) {
						$sMessage = L10N::t('Die Leistung ({service}) wurde durch Ferien geteilt, eine Veränderung ist nicht möglich.');
						$this->addError([
							'message' => str_replace('{service}', $oService->getInfo(), $sMessage),
							'input' => ['id' => $sKey.'['.$iInquiry.']['.(int)$oService->id.']['.$sField.']']
						]);
					}
				}

				// Irgendeine Kursbuchung der zusammenhängenden Kursbuchungen hat nun einen anderen Kurs
				// Wenn alle Kurse gleichzeitig geändert werden, funktioniert das hier wiederum
				foreach($aRelatedServices as $oRelatedService) {
					if($oRelatedService->$sParentField != $oService->$sParentField) {
						$sMessage = L10N::t('Die Leistung ({service}) wurde durch Ferien geteilt. Bitte ändern Sie alle zusammenhängenden Leistungen gleichzeitig oder löschen Sie die Ferien.');
						$this->addError([
							'message' => str_replace('{service}', $oService->getInfo(), $sMessage),
							'input' => ['id' => $sKey.'['.$iInquiry.']['.(int)$oService->id.']['.$sParentField.']']
						]);
					}
				}
			}
		}
    }

	/**
	 * @TODO $aHolidays ist ein Array mit komplett unterschiedlichen Items je Typ
	 *
	 * @param Ext_TS_Inquiry_Journey_Service $oService
	 * @param array $aHolidays
	 * @param array $aAllServices
	 * @return bool
	 */
    protected function _splitService(Ext_TS_Inquiry_Journey_Service $oService, &$aHolidays, &$aAllServices = array()){

        $this->_checkServiceForHolidayErrors($oService);
 
        if(!empty($this->_aErrors['error'])) {
           return false;
        }

		// Falsches Datumsformat muss hier abgefangen werden, da das erst in der WDBasic wirklich geprüft wird
		if(
			!DateTime::isDate($oService->from, 'Y-m-d') ||
			!DateTime::isDate($oService->until, 'Y-m-d')
		) {
			return false;
		}

        $sClass             = $oService->getClassName();

        $oServiceFrom       = new DateTime($oService->from);
        $oServiceUntil      = new DateTime($oService->until);

        // TODO Da das bei Kurs immer 1 und 5 ist, ist es fraglich, ob und wie das bei Kursstarttag != Montag funktioniert
		// Für Unterkünfte werden die Variablen hier bereits nicht mehr verwendet
		// Sollte generell entfernt werden und auf die Methoden umgestellt werden
        $iStartDay          = $oService->getServiceStartDay();
        $iEndDay            = $oService->getServiceEndDay();

        // Wenn es ein Offset gibt soll der Kurs zuersteinmal verschoben werden
        // nachfolgende Kurse verschieben bei ferienbuchen im Dialog
        // erst dann darf auf splittung geprüft werden
        $iOffset            = (int)$this->_aOffsets[$sClass][$oService->id];

        if($iOffset > 0){
            $oServiceFrom->modify('+ '.$iOffset.' Weeks');
            $oServiceUntil->modify('+ '.$iOffset.' Weeks');;
            $oService->from     = $oServiceFrom->format('Y-m-d');
            $oService->until    = $oServiceUntil->format('Y-m-d');
            // offset zurücksetzten da man ggf. rekrusiv in diese Methode kommt aber die verschiebung einmalig sein muss
            $this->_aOffsets[$sClass][$oService->id] = 0;
        }

        foreach($aHolidays as $aHoliday){

            // Bei Leistungsferien müssen die Ferien zur Leistung passen
            // daher checken wir die ID
            if(
               isset($aHoliday['service']) &&
               $aHoliday['service']  != $oService->id
            ){
                continue;
            }
            
			// Schulferien immer auf Leistungswoche erweitern, damit »Ferien in Leistung« korrekt erkannt wird
			if(
				!isset($aHoliday['service']) &&
				$oService instanceof Ext_TS_Inquiry_Journey_Course
			) {
				// Nur Schulferien dürfen erweitert werden
				$oDateRange = new DateRange(new DateTime($aHoliday['from']), new DateTime($aHoliday['until']));
				self::expandDateRange($oDateRange, $oService->getSchool()->course_startday);
				$aHoliday['from'] = $oDateRange->from->format('Y-m-d');
				$aHoliday['until'] = $oDateRange->until->format('Y-m-d');
				$aHoliday['weeks'] = self::getSchoolHolidayWeeks($oDateRange->from, $oDateRange->until);
			}

            $sFrom          = $aHoliday['from'];
            $sUntil         = $aHoliday['until'];
            $iWeeks         = (int)$aHoliday['weeks'];
            $oHolidayFrom   = new DateTime($sFrom);
            $oHolidayUntil  = new DateTime($sUntil);
            
            $this->checkForMerge($oService, $aAllServices, $oHolidayFrom, $oHolidayUntil);

            // Wenn die Leistung in den Ferien liegt muss die Leistung "verschoben" werden
            if(Ext_TC_Util::between($oServiceFrom, $oHolidayFrom, $oHolidayUntil)){

                $this->checkForErrors($oService, $oHolidayFrom, $oHolidayUntil, $aAllServices);
                
                // Makieren das verschoben wurde
                $this->_bServiceMoved = true;
                
                // wenn keine Wochen da sind sind das Schulferien hier müssen die Wochen errechnet werden
                if($iWeeks <= 0){
                    // hierfür LEISTUNGSSTART + Ferienende nehmen!
                    $oDiff = $oServiceFrom->diff($oHolidayUntil, true);
                    $iDays = $oDiff->days + 1;
                    $iWeeks = ceil($iDays/7);
                }

                // Leistung verschieben nach anzahl der Ferien Wochen
                $oServiceFrom->modify('+ '.$iWeeks.' Weeks');
                $oServiceUntil->modify('+ '.$iWeeks.' Weeks');

                // Offset bereits inkludiert, altes Verhalten
                $aServiceOriginalData = $oService->getData();

                $oService->from     = $oServiceFrom->format('Y-m-d');
                $oService->until    = $oServiceUntil->format('Y-m-d');

				$this->setSplittingData($aHoliday, $aServiceOriginalData, $oService, null);

                $this->splitService($oService, $aAllServices);
            // wenn die Ferien IN der Leistung liegt muss die Leistung
            // gesplittet werden und dann direkt wieder geprüft werden
            } else if( Ext_TC_Util::between($oHolidayFrom, $oServiceFrom, $oServiceUntil)){

                $this->checkForErrors($oService, $oHolidayFrom, $oHolidayUntil, $aAllServices);

                $aData          = $oService->getData();
                $aData['id']    = 0;

				// Offset bereits inkludiert, altes Verhalten
				$aServiceOriginalData = $oService->getData();

				/** @var Ext_TS_Inquiry_Journey_Service $oNewService */
                $oNewService    = $sClass::getObjectFromArray($aData);
                $sKey = $oService instanceof Ext_TS_Inquiry_Journey_Accommodation ? 'accommodations' : 'courses';
                $this->_oJourney->setJoinedObjectChild($sKey, $oNewService);
                
                // Das ende für die aktuelle Leistung errechnen und setzten
				$dServiceUntilOriginal = $oServiceUntil;
                $oServiceUntil = clone $oHolidayFrom;

                // End-Wochentag errechnen, nicht bei Unterkünften
				if(!$oService instanceof Ext_TS_Inquiry_Journey_Accommodation) {
					$this->modifyDateByWeekday($oServiceUntil, $iEndDay, 'endday');
				}

                $oDiff = $oServiceFrom->diff($oServiceUntil);
                $iServiceDays = $oDiff->days;
                $iServiceWeeks = ceil($iServiceDays/7);

				/*
				 * Hier darf nicht einfach $oService->weeks benutzt werden,
				 * da man ansonsten mit zwei verschiedenen Basen rechnen würde.
				 * Der Kunde kann nämlich trotz Wochenangabe die eigentlichen
				 * Datumsfelder auch verändern, sodass sich die tatsächliche
				 * Differenz unterscheiden könnte. Das ist eigentlich ein Benutzerfehler,
				 * aber wenn gesplittet wird, wird $oService->weeks quasi dann korrigiert.
				 */
				$oDiff = $oServiceFrom->diff($dServiceUntilOriginal);
				$iOriginalServiceWeeks = ceil($oDiff->days / 7);

                $iNewServiceWeeks = $iOriginalServiceWeeks - $iServiceWeeks;

                if($iServiceWeeks <= 0){
                    $oService->active = 0;
                    $oService->weeks    = 1;
                } else {

					/*
					 * Wenn die Differenz 0 ergibt, hat das ceil() ggf. Probleme gemacht
					 * aufgrund von zu geringem Zeitraum (z.B. bei Unterkünften).
					 * 0 darf es aber nicht geben, da dann die Datumsangaben alle falsch sind.
					 * Stattdessen wird hier dann manuell eine Woche abgezogen und ergänzt.
					 * Ob das aber für alles richtig ist, das weiß auch niemand (Tests?).
					 */
					if($iNewServiceWeeks == 0) {
						$iNewServiceWeeks = 1;
						$iServiceWeeks -= 1;
					}

                    // neuerrrechnete Service werte Setzten
                    $oService->weeks    = $iServiceWeeks;

                }
                $oService->until    = $oServiceUntil->format('Y-m-d');
				$oService->transients['field_state_holiday_split'] = true;

                $oNewFrom = clone $oHolidayUntil;

				// Starttag anpassen, falls es nicht der Tag nach dem Endtag ist
				// Dies darf sich bei Unterkünften nicht nach dem Start der Unterkunfswoche richten! #5016
				if(!$oService instanceof Ext_TS_Inquiry_Journey_Accommodation) {
					$this->modifyDateByWeekday($oNewFrom, $iStartDay, 'startday');
				}

				$oNewUntil = clone $oNewFrom;

				if($oService instanceof Ext_TS_Inquiry_Journey_Course) {
					// Bei Kursen eine Woche draufrechnen und Endtag verschieben
					$oNewUntil->modify('+ '.$iNewServiceWeeks.' Weeks');
					$this->modifyDateByWeekday($oNewUntil, $iEndDay, 'endday'); // endtag anpassen falls es nicht der Tag vor dem Starttag ist
				} else {
					// Bei Unterkünften Differenz der Nächte addieren, da eine Unterkunftswoche alles Mögliche sein kann
					// Hiernach können sich Unterkünfte zwar überschneiden, aber eine Verschiebung dieser gibt es auch nicht
					$oDiff = $oServiceUntil->diff($dServiceUntilOriginal);
					$oNewUntil->add(new DateInterval('P'.$oDiff->days.'D'));
				}

                $oNewService->from  = $oNewFrom->format('Y-m-d');
                $oNewService->until = $oNewUntil->format('Y-m-d');
                $oNewService->weeks = $iNewServiceWeeks;
				$oNewService->transients = $oService->transients;
				$oNewService->transients['field_state_holiday_split'] = true;
  
                $this->setSplittingData($aHoliday, $aServiceOriginalData, $oService, $oNewService);

                $this->_bServiceSplitted = true;

                $this->splitService($oNewService, $aAllServices);
                // das hier nicht wegen endlosschleife das ende evt = ferienstart entspricht
                // auserdem verschiebt sich nur der rechte part wodurch er nicht nochmal hier rein muss
                //$this->splitService($oService, $aAllServices);
            }
        }
        
    }
    
    public function checkForErrors(Ext_TS_Inquiry_Journey_Service $oService, $oHolidayFrom, $oHolidayUntil, &$aAllServices){
        
        $oDateFormat    = new Ext_Thebing_Gui2_Format_Date();
            
        $sHolidayTime   = $oDateFormat->formatByValue($oHolidayFrom->format('Y-m-d')).' – '.$oDateFormat->formatByValue($oHolidayUntil->format('Y-m-d'));
            
        // #4329 checkpaymentstatus macht was anderes wie gedacht
        // wenn ich ein Datum übergebe dann prüft er in den zeiträumen wo dieser Zeitraum NICHT stattfindet ist aber fallsch
        // er muss schauen ob im ferien zeitraum was da ist
        //$aPayments      = $oService->checkPaymentStatus($oHolidayFrom->format('Y-m-d'), $oHolidayUntil->format('Y-m-d'));
        //if(!empty($aPayments)){
        //    $sMessage = L10N::t('Die Leistung {service} wurde bereits bezahlt. Es können daher keine Ferien ({holiday}) eingetragen werden!');
        //    $sMessage = str_replace(array('{service}', '{holiday}'), array($oService->getInfo(), $sHolidayTime), $sMessage);
        //    $this->addError($sMessage, 'error');
        //}
        
        // #4459 passiert nun im saver nach dem Splitten und mit hint + anpassung
        //$bMatching      = $oService->checkAllocations();
        //if($bMatching){
        //    $sMessage = L10N::t('Die Leistung {service} hat bereits Zuweisungen. Es können daher keine Ferien ({holiday}) eingetragen werden!');
        //    $sMessage = str_replace(array('{service}', '{holiday}'), array($oService->getInfo(), $sHolidayTime), $sMessage);
        //    $this->addError($sMessage, 'error');
        //}
        
        // Hinmeldung nur wenn es Schulferien sind
        // das ist der Fall wenn dieses Array nicht übergeben wird
        if(
        	empty($aAllServices) &&
			System::wd()->getInterface() === 'backend'
		) {
            $sMessage = L10N::t('Die Leistung "{service}" kann nicht während Schulferien ({holiday}) gebucht werden. Der Leistungsstart wurde auf den ersten Starttag nach den Ferien verlegt.');
            $sMessage = str_replace(array('{service}', '{holiday}'), array($oService->getInfo(), $sHolidayTime), $sMessage);
            $this->addError($sMessage, 'hint');
        }
    }
    
    /**
     * prüft ob ein Kurs verschmolzen werden muss
     * das passiert wenn es einen anderen kurs gibt der 1:1 die gleichen Daten hat und ebenfalls in den Ferien anfängt
     * dann wird er verbunden und der aktuelle kurs um seine wochen verlängert
     * @param type $oService 
     * @param type $aAllServices
     * @param type $oHolidayFrom
     * @param type $oHolidayUntil
     */
    public function checkForMerge($oService, &$aAllServices, $oHolidayFrom, $oHolidayUntil){
        
        foreach($aAllServices as $iKey => $oCurrentService){
            
            // Das ganze passiert nur bei gespeicherten Objecten
            // die nicht dem aktuellen entsprechen
            // und die daten 1:1 gleich sind
            if(
                $oCurrentService->id > 0 &&
                $oCurrentService->id != $oService->id &&
                $oService->isSameWithoutTimeData($oCurrentService)
            ){
                $oCurrentStart = new DateTime($oCurrentService->from);
                if(Ext_TC_Util::between($oCurrentStart, $oHolidayFrom, $oHolidayUntil)){
                    $oEnd = new DateTime($oService->until);
                    $oEnd->modify('+ '.$oCurrentService->weeks.' Weeks');
                    $this->modifyDateByWeekday($oEnd, $oService->getServiceEndDay(), 'endday');
                    $oService->until = $oEnd->format('Y-m-d');
                    $oService->weeks += $oCurrentService->weeks;
					$sKey = $oCurrentService instanceof Ext_TS_Inquiry_Journey_Accommodation ? 'accommodations' : 'courses';
                    $this->_oJourney->removeJoinedObjectChildByKey($sKey, $oCurrentService->id);
                    unset($aAllServices[$iKey]);
                }
            }
            
        }
    }

    public function setSplittingData(array $aHoliday, array $aOriginalData, $oOldService, $oNewService = null) {
    	if(!isset($aHoliday['service'])) {
			// Eintrag für Schulferien wird hier erzeugt
    		/** @var Ext_TS_Inquiry_Holiday $oHoliday */
    		$oHoliday = $this->_oJourney->getInquiry()->getJoinedObjectChild('holidays');
			$oHoliday->type = 'school';
			$oHoliday->weeks = $aHoliday['weeks'];
			$oHoliday->from = $aHoliday['from'];
			$oHoliday->until = $aHoliday['until'];
			$oHoliday->addSplitting($aOriginalData, $oOldService, $oNewService);
		} else {
			$this->_oServiceHoliday->addSplitting($aOriginalData, $oOldService, $oNewService);
		}
	}
    
    public function addError($aError, $sType = 'error'){
        if(!is_array($aError)){
            $aError = array('type' => $sType, 'message' => $aError);
        }
        $this->_aErrors[] = $aError;
    }

	/**
	 * Schulferien/Leistungszeitraum mit Leistungswochen erweitern
	 *
	 * Wenn die Ferien bspw. am Dienstag starten, aber der Kurs am Montag beginnt,
	 * werden die Ferien auf Montag-Freitag gesetzt, damit das Splitting die Ferien
	 * korrekt erkennt. Ohne dieses Erweitern könnte es komische gesplittete Daten
	 * und Wochen geben (bspw. Startdatum größer als Enddatum).
	 *
	 * @param DateRange $oDateRange
	 * @param int $iStartDay
	 * @param bool $bBlockWeek Auf komplette Blockwoche (7 Tage) statt der üblichen 5 Tage erweitern
	 */
	public static function expandDateRange(DateRange $oDateRange, $iStartDay, $bBlockWeek = false) {

		if(!$bBlockWeek) {
			$aDays = Ext_Thebing_Util::getCourseWeekDays($iStartDay);
		} else {
			$aDays = Ext_Thebing_Util::getBlockWeekDays($iStartDay);
		}

		$iFirstDay = min($aDays);
		$iLastDay = max($aDays);

		if($oDateRange->from->format('N') > $iFirstDay) {
			$oDateRange->from->modify('last '.Ext_Thebing_Util::convertWeekdayToEngWeekday($iFirstDay));
		}

		if($oDateRange->until->format('N') < $iLastDay) {
			$oDateRange->until->modify('next '.Ext_Thebing_Util::convertWeekdayToEngWeekday($iLastDay));
		}

	}

	/**
	 * Wochen der Schulferien ermitteln für SR-Eintrag (nur für Anzeige)
	 *
	 * @param $mFrom
	 * @param $mUntil
	 * @return float
	 * @throws Exception
	 */
	public static function getSchoolHolidayWeeks($mFrom, $mUntil) {

		if(
			!$mFrom instanceof \DateTime ||
			!$mUntil instanceof \DateTime
		) {
			$mFrom = new DateTime($mFrom);
			$mUntil = new DateTime($mUntil);
		}

		return ceil($mFrom->diff($mUntil)->days / 7);

	}

}
