<?php

/**
 * @TODO An keiner einzigen Stelle wird delete() verwendet, sodass cascade on_delete nicht funktioniert!
 */
class Ext_TS_Inquiry_Saver_Journey extends Ext_TS_Inquiry_Saver_Abstract {
    
    /**
     * @var Ext_TS_Inquiry_Journey
     */
    protected $_oObject;
    
    /**
     * @var Ext_TS_Inquiry
     */
    protected $_oInquiry;

    protected $_aCourses;
    protected $_aAccommodations;
    protected $_aTransfers;
    protected $_aInsurances;
    
    protected $_iCheckCourseSave = 0;
    protected $_iCheckAccommodationSave = 0;
    protected $_iCheckTransferSave = 0;
    
    protected $_bCurrencyChanged = false;
    protected $_oPrice;
    
	protected $_aDeleted = null;
	protected $aCourseFlexFields;
	
	/**
	 * @var Ext_TS_Inquiry_Journey_Service[]
	 */
	protected $aAdditionalServices;

	public static $aKeyMapping = array();

	public function __construct(MVC_Request $oRequest, Ext_Gui2 $oGui, $bSave = true) {
		parent::__construct($oRequest, $oGui, $bSave);
		$this->aCourseFlexFields = new SplObjectStorage();
		$this->aAdditionalServices = new SplObjectStorage();
	}

	public function setObject(Ext_TC_Basic $oObject, $sAlias = '') {

		if(!$oObject instanceof Ext_TS_Inquiry_Journey) {
			throw new RuntimeException('The parameter must be an instanceof "\Ext_TS_Inquiry_Journey".');
		}

        parent::setObject($oObject, $sAlias);
        
        //$this->_oInquiry = $oObject->getInquiry();
        
		$this->_aDeleted = $this->_oRequest->input('deleted');
		
        $this->prepareFlags();
        $this->prepareCourses();
        $this->prepareAccommodations();
        $this->prepareTransfers();
		$this->prepareInsurances();
		$this->prepareActivities();
    }

	public function _finish($bSave) {

		// Flex-Felder pro Kursbuchung speichern
		if(
			$bSave &&
			!empty($this->aCourseFlexFields)
		) {
			$aCourses = $this->_oObject->getJoinedObjectChilds('courses');
			foreach($aCourses as $oJourneyCourse) {
				if($this->aCourseFlexFields->contains($oJourneyCourse)) {
					Ext_TC_Flexibility::saveData($this->aCourseFlexFields[$oJourneyCourse], $oJourneyCourse->id);
				}
			}
		}
		
		if(
			$bSave &&
			!empty($this->aAdditionalServices)
		) {

			$oPersister = WDBasic_Persister::getInstance();
			
			// Alle gespeicherten holen
			$aSavedAdditionalServices = $this->_oObject->getJoinedObjectChilds('additionalservices');
			
			foreach($this->aAdditionalServices as $oJourneyService) {
				
				if($oJourneyService instanceof Ext_TS_Inquiry_Journey_Accommodation) {
					$sRelation = 'accommodation';
				} else {
					$sRelation = 'course';
				}
				
				$aAdditionalServiceIds = $this->aAdditionalServices->offsetGet($oJourneyService);
				
				foreach($aAdditionalServiceIds as $iAdditionalServiceId) {

					$bFound = false;
					foreach($aSavedAdditionalServices as $iSavedAdditionalService=>$oSavedAdditionalService) {
					
						if(
							$oSavedAdditionalService->additionalservice_id == $iAdditionalServiceId &&
							$oSavedAdditionalService->relation == $sRelation &&
							$oSavedAdditionalService->relation_id == $oJourneyService->id
						) {
							$bFound = true;
							break;
						}
					}
					
					if($bFound !== true) {
						$oJourneyAdditionalService = $this->_oObject->getJoinedObjectChild('additionalservices');
						$oJourneyAdditionalService->additionalservice_id = $iAdditionalServiceId;
						$oJourneyAdditionalService->relation = $sRelation;
						$oJourneyAdditionalService->relation_id = $oJourneyService->id;
						$oPersister->attach($oJourneyAdditionalService);
					} else {
						unset($aSavedAdditionalServices[$iSavedAdditionalService]);
					}
					
				}

			}
			
			$oPersister->save();

			foreach($aSavedAdditionalServices as $oSavedAdditionalService) {
				$oSavedAdditionalService->delete();
			}
			
		}

		parent::_finish($bSave);

	}

	/**
	 * Inquiry ins Objekt setzen, da sich die Klasse früher die Inquiry über JoinedObjects geholt hat.
	 * Da die WDBasic aber keine Instanzen in den Cache setzt, war bei neuen Buchungen das Objekt einfach leer.
	 *
	 * @param Ext_TS_Inquiry $oInquiry
	 */
	public function setInquiry(Ext_TS_Inquiry $oInquiry) {
		$this->_oInquiry = $oInquiry;
	}

    public function prepareFlags(){

		// Beim Speichern von Ferien dürfen die Leistungen nicht gespeichert werden
        if($this->_oRequest->get('dontSaveCourseAndAcco') > 0){
            $this->_iCheckCourseSave			= 0;
            $this->_iCheckAccommodationSave     = 0;
        } else {
            $this->_iCheckCourseSave			= Ext_Thebing_Inquiry_Group::checkForSaveData('course', $this->_oInquiry->group_id);
            $this->_iCheckAccommodationSave     = Ext_Thebing_Inquiry_Group::checkForSaveData('accommodation', $this->_oInquiry->group_id);
        }
        
        $this->_iCheckTransferSave              = Ext_Thebing_Inquiry_Group::checkForSaveData('transfer', $this->_oInquiry->group_id);
        
		$this->_bCurrencyChanged                = $this->_oInquiry->checkIfCurrencyChanged();
        
		$this->_oPrice                          = new Ext_Thebing_Price($this->_oObject->getSchool(), null, null, 'en');
        
    }
    
    ###
    ### KURSE
    ###
    
    public function prepareCourses() {

        if($this->_iCheckCourseSave !== 0) {

            $aCourses = (array)$this->_oRequest->input('course');
            //$aModules = (array)$this->_oRequest->get('module');

			$iCount = 0;
			
            foreach($aCourses as $iJourneyCourse => $aCourseData){ 

				/** @var Ext_TS_Inquiry_Journey_Course $oJourneyCourse */
                $oJourneyCourse = $this->_oObject->getJoinedObjectChild('courses', $iJourneyCourse);

                //Hiddenfelder ausblenden
                if(isset($aCourseData['weeks_hidden'])){
                    $aCourseData['weeks'] = $aCourseData['weeks_hidden'];
					unset($aCourseData['weeks_hidden']);
                }
                if(isset($aCourseData['from_hidden'])){
                    $aCourseData['from'] = $aCourseData['from_hidden'];
					unset($aCourseData['from_hidden']);
                }
                if(isset($aCourseData['until_hidden'])){
                    $aCourseData['until'] = $aCourseData['until_hidden'];
					unset($aCourseData['until_hidden']);
                }

                if(isset($aCourseData['flex'])) {
                	// Die zweite ID hat keine Relevanz und wird von studentlists.js auch nicht behandelt
                	$this->aCourseFlexFields[$oJourneyCourse] = reset($aCourseData['flex']);
                	unset($aCourseData['flex']);
				}

				if(!empty($aCourseData['additionalservices'])) {
					$this->aAdditionalServices[$oJourneyCourse] = $aCourseData['additionalservices'];
					unset($aCourseData['additionalservices']);
				}
				
                foreach($aCourseData as $sColumn => $mValue) {

                    // Dient nur als Filter
                    if(in_array($sColumn, ['category_id', 'units_dummy'])) {
                        continue;
                    }

                    // Wenn Gruppeneinstellungen zeiträume speichern nicht erlauben dann nicht speichern
                    if(
                        $this->_iCheckCourseSave == 2 &&
                        in_array($sColumn, array('from', 'until'))
                    ){
                        continue;
                    }

                    // Kommentar speichern soll immer möglich sein
                    if(
                        $this->_iCheckCourseSave == 3 &&
                        !in_array($sColumn, array('visible', 'course_id', 'level_id', 'units', 'weeks', 'from', 'until', 'flexible_tuition', 'comment'))
                    ){
                        continue;
                    }

                    if(
                        in_array($sColumn, array('from', 'until'))
                    ) {
                        $mValue = Ext_Thebing_Format::ConvertDate($mValue, $this->_oSchoolForFormat->id, 1, true);
                    } elseif(
                        in_array($sColumn, array('units'))
                    ) {
                        $mValue = Ext_Thebing_Format::convertFloat($mValue);
                    } elseif($sColumn === 'automatic_renewal_cancel') {
						// Checkbox zu Datum, aber nur, wenn noch nicht gesetzt
						if ($mValue && !$oJourneyCourse->automatic_renewal_cancellation) {
							$oJourneyCourse->automatic_renewal_cancellation = \Carbon\Carbon::now()->toDateString();
							// \n mit vsprintf in einer Zeile, weil die WDBasic irgendein trim() oder sonst etwas fabriziert
							$oJourneyCourse->comment .= (!empty($oJourneyCourse->comment) ? "\n" : '').vsprintf(\L10N::t('Gekündigt durch "%s" am %s.', \Ext_Thebing_Inquiry_Gui2::TRANSLATION_PATH), [
								\System::getCurrentUser()->getName(),
								Ext_Thebing_Format::LocalDate($oJourneyCourse->automatic_renewal_cancellation, $this->_oSchoolForFormat->id)
							]);
						} elseif(!$mValue) {
							$oJourneyCourse->automatic_renewal_cancellation = null;
						}

						continue;
					}

                    $oJourneyCourse->$sColumn = $mValue;
                }

				if (
					$oJourneyCourse->isEmpty() ||
					$oJourneyCourse->getOriginalData('course_id') != $oJourneyCourse->course_id
				) {
					// TODO was ist mit Änderungen am Zeitraum?
					try {
						$oJourneyCourse->deleteCoOp();
					} catch (\Ts\Exception\Inquiry\Course\ExistingJobAllocation $e) {
						$this->addError($this->_oGui->t('Der Kurs kann nicht geändert oder gelöscht werden da bereits finale Jobzuweisungen existieren.'));
						continue;
					}
				}

                if($oJourneyCourse->isEmpty()) {
					$this->_oObject->removeJoinedObjectChildByKey('courses', $iJourneyCourse);
                } else {
					$oJourneyCourse->adjustData();
				}

				if (
					$oJourneyCourse->exist() &&
					$oJourneyCourse->getOriginalData('automatic_renewal_cancellation') !== $oJourneyCourse->automatic_renewal_cancellation
				) {
					// Auf Nachholtermine prüfen und das Enddatum ggf. anpassen
					$lessonsCatchUp = new \TsTuition\Service\CourseLessonsCatchUpService($oJourneyCourse);
					$lessonsCatchUp->fill();

					// Das darf hier nicht passieren und passiert bereits in der Ext_Thebing_Inquiry_Gui2 wenn die Buchung
					// erfolgreich gespeichert wurde.
					// Tuition-Index aktualisieren damit eventuelle Nachholtermine eingetragen werden
					//\Core\Facade\SequentialProcessing::add('ts/tuition-index', $this->_oInquiry);
				}

				++$iCount;
            } 
            
            // kurse löschen die nicht gespeichert wurden
            $aAllCourses = $this->_oObject->getJoinedObjectChilds('courses', true);
            foreach($aAllCourses as $oInquiryCourse){

				if($this->_checkDeletedEntries('course', $oInquiryCourse) === true) {
					$oInquiryCourse->active = 0;
				}
            }
        }

		// Wird bereits in der prepareHolidays() gemacht (analog zu Unterkünften)
		//$this->checkCourseData();
    }

	/**
	 * Separat Kursdaten (primär: Änderungen) prüfen, da dies nach dem Splitten durch Ferien passieren muss
	 * Zuvor wurde dieser Teil direkt in prepareCourses() gemacht, aber damit wurden keine Änderungen durch Ferien erkannt
	 */
	public function checkCourseData() {
		/* @var Ext_TS_Inquiry_Journey_Course[] $aAllCourses */
		$aAllCourses = $this->_oObject->getJoinedObjectChilds('courses', true);

  		foreach($aAllCourses as $oInquiryCourse) {
			if(true) { //if($this->_oInquiry->isConfirmed()) {

				$sStatus = $oInquiryCourse->checkForChange();

				if(
					$this->_bCurrencyChanged ||
					$sStatus
				) {
					if(!$sStatus) {
						$sStatus = 'edit';
					}

					Ext_Thebing_Inquiry_Document_Version::setChange($this->_oInquiry->id, $oInquiryCourse->id, 'course', $sStatus);
					$aAdditionalCourseCostList = $this->_oPrice->getAdditionalCourseCostList($oInquiryCourse->course_id);
					foreach((array)$aAdditionalCourseCostList as $aCost){
						Ext_Thebing_Inquiry_Document_Version::setChange($this->_oInquiry->id, $aCost['id'], 'additional_course', $sStatus, $oInquiryCourse->id);
					}
				}
			}
		}
	}

    ###
    ### Unterkunft
    ###

    public function prepareAccommodations(){
        
        if($this->_iCheckAccommodationSave !== 0){

            $aAccommodations = $this->_oRequest->input('accommodation');

            $aCurrentAccommodationsIds = array();

            if(is_array($aAccommodations)){
                
                foreach($aAccommodations as $iJourneyAccommodation => $aAccommodationData){ 

					/** @var $oJourneyAccommodation Ext_TS_Inquiry_Journey_Accommodation */
                    $oJourneyAccommodation          = $this->_oObject->getJoinedObjectChild('accommodations', $iJourneyAccommodation);
                    $aCurrentAccommodationsIds[]    = $oJourneyAccommodation->id;

					if(!empty($aAccommodationData['additionalservices'])) {
						$this->aAdditionalServices[$oJourneyAccommodation] = $aAccommodationData['additionalservices'];
						unset($aAccommodationData['additionalservices']);
					}
					
                    foreach($aAccommodationData as $sColumn => $mValue){

                        // Wenn Gruppeneinstellungen zeiträume speichern nicht erlauben dann nicht speichern
                        if(
                            $this->_iCheckAccommodationSave == 2 &&
                            in_array($sColumn, array('from', 'until', 'from_time', 'until_time'))
                        ){
                            continue;
                        }

                        // Kommentar speichern soll immer möglich sein
                        if(
                            $this->_iCheckAccommodationSave == 3 &&
                            !in_array($sColumn, array('visible', 'accommodation_id', 'roomtype_id', 'meal_id', 'units', 'weeks', 'from', 'until', 'comment'))
                        ){
                            continue;
                        }

                        if(
                            in_array($sColumn, array('from', 'until'))
                        ){
                            $mValue = Ext_Thebing_Format::ConvertDate($mValue, $this->_oSchoolForFormat->id, 1, true);
                        }

                        $oJourneyAccommodation->$sColumn = $mValue;
                    }

					// Unterkünfte werden bereits hier gelöscht!
					// @TODO Ist das hier an der Stelle korrekt oder sollte das in checkAccommodationData() passieren (Matching)?
                    if($oJourneyAccommodation->isEmpty()) {
                        $this->_oObject->removeJoinedObjectChildByKey('accommodations', $iJourneyAccommodation);

						// Matching hier löschen, da ansonsten aktive Zuweisungen in der Datenbank verbleiben
						$oJourneyAccommodation->deleteAllocations(true, true);
                    }

                }
            }

            // Wird in der prepareHolidays in der inquiry savere gemacht darf nicht doppelt passiern da sonst matching einträge verdoppelt werden
            //$this->checkAccommodationData($aCurrentAccommodationsIds);
            
        }
        
    }

    public function checkAccommodationData(){
        
        // übermittelte ids merken um später rauszufinden welche es nun nicht mehr gibt
        $aAccommodations = $this->_oRequest->input('accommodation');
        $aCurrentAccommodationsIds = array();

        if(is_array($aAccommodations)){
            foreach($aAccommodations as $iJourneyAccommodation => $aAccommodationData){ 
                $aCurrentAccommodationsIds[]    = $iJourneyAccommodation;
            }
        }
        
        // kurse löschen die nicht gespeichert wurden
		// Achtung: Unterkünfte werden bereits in prepareAccommodations() gelöscht!
		// Beim Löschen einer Unterkunft kommt die gelöschte Unterkunft hier niemals rein.
        $aAllAccommodations = $this->_oObject->getJoinedObjectChilds('accommodations', true);
        if(is_array($aAllAccommodations)){
			
			/* @var $oJourneyAccommodation Ext_TS_Inquiry_Journey_Accommodation */
            foreach($aAllAccommodations as $oJourneyAccommodation){
				/** @var Ext_TS_Inquiry_Journey_Accommodation $oJourneyAccommodation */

				$bRefreshMatching = $bChangeMatching = $bDeleteMatching = $bHardCriteriaChanged = false;

				if($this->_checkDeletedEntries('accommodation', $oJourneyAccommodation) === true) {
					$oJourneyAccommodation->active = 0;
				}

				if(true) { //if($this->_oInquiry->isConfirmed()) {

					$sStatus = $oJourneyAccommodation->checkForChange();
					$aOriginalAcco = $oJourneyAccommodation->getOriginalData();

					$aAllocations = [];
					if($oJourneyAccommodation->id > 0) {
						$aAllocations = Ext_Thebing_Allocation::getAllocationByInquiryId($this->_oInquiry->id, $oJourneyAccommodation->id, true, true, true);
					}

					if(!empty($aAllocations)) {

						// Alle Zuweisungen durchlaufen und Änderungen der harten Kriterien überprüfen
						foreach($aAllocations as $oAllocation) {
							// Inaktive Zuweisungen ignorieren
							if($oAllocation->room_id != 0) {
								if(!$oAllocation->compareHardMatchingCriteria()) {
									$bHardCriteriaChanged = true;
								}
							}
						}

						// Hat Priorität: Hier werden die Zuweisungen immer gelöscht
						if($bHardCriteriaChanged) {

							// Wenn harte Kriterien verändert wurden und nicht mehr passen: Warnung anzeigen
							if(!$this->_oRequest->get('ignore_errors')) {
								$aError = array(
									'type' => 'hint',
									'message' => $this->_oGui->t('Die harten Kriterien der Zuweisungsdetails haben sich geändert. Vorhandene Zuweisungen, die nicht mit den neuen Einstellungen übereinstimmen, werden gelöscht.')
								);

								$this->addError($aError);
							}

						} elseif(
                            $sStatus &&
                            $aOriginalAcco['id'] > 0 &&
                            $oJourneyAccommodation->active == 1 &&
                            $oJourneyAccommodation->visible == 1 &&
                            $oJourneyAccommodation->accommodation_id == $aOriginalAcco['accommodation_id'] &&
                            $oJourneyAccommodation->roomtype_id == $aOriginalAcco['roomtype_id'] &&
                            $oJourneyAccommodation->meal_id == $aOriginalAcco['meal_id']
                        ){ 
                            // Matching anpassen
                            $aOldAcco = array('from'=>$aOriginalAcco['from'], 'until' => $aOriginalAcco['until']);
                            $aNewAcco = array('from'=>$oJourneyAccommodation->from, 'until' => $oJourneyAccommodation->until);

							$dNewFrom = new \Core\Helper\DateTime($aNewAcco['from']);
							$dNewUntil = new \Core\Helper\DateTime($aNewAcco['until']);
							
							/*
							 * Das ist nur relevant, wenn es in der Änderung des Zeitraums schon Zuweisungen gab
							 * Wenn der neue Zeitraum mit den vorhandenen Zuweisungen übereinstimmt, gibt es kein Problem.
							 * Hier kann null zurückkommen, dann gibt es keine oder nur inaktive Zuweisungen.
							 */
							$oDatePeriod = $oJourneyAccommodation->getAllocatedPeriod();
							
							// Wenn Zuweisungen in Buchungszeitraum liegen
							if(
								$oDatePeriod === null ||
								$dNewFrom <= $oDatePeriod->getStartDate() &&
								$dNewUntil >= $oDatePeriod->getEndDate()
							) {
								$bRefreshMatching = true;
							} else {
								$bChangeMatching = true;
							}

							if(!$this->_oRequest->get('ignore_errors')) {
								// Muss auch bei nur inaktiven Zuweisungen kommen, da die saveMatchingChange() unten aufgerufen werden muss
								$aError = array(
									'type' => 'hint',
									'message' => $this->_oGui->t('Die Daten der Unterkunft haben sich geändert. Die Unterkunftszuweisung wird angepasst!')
								);

								$this->addError($aError);
							}

                        } elseif($sStatus) {
                            // Hier werden die Matching Einträge gelöscht die NICHT mehr auf die
                            // NEUE Kombination passt

                            if(!$this->_oRequest->get('ignore_errors')) {
                                $aError = array(
                                    'type' => 'hint',
                                    'message' => $this->_oGui->t('Die Unterkunftszuweisung entspricht nicht mehr den Wünschen des Kunden! Die Unterkunftszuweisung wird gelöscht!')
                                );

                                $this->addError($aError);
                            }

                            $bDeleteMatching = true;

                            #$oJourneyAccommodation->deleteUnfittingAllocations($aOriginalAcco);
                        }

                        // Wenn die unteren Methoden die Zuweisungen lustig klonen, löschen usw., gibt es mal wieder doppelte UAB-Zahlungen
						if(
							$this->_oRequest->get('ignore_errors') == 1 &&
							!$this->hasErrors() && (
								$bHardCriteriaChanged ||
								$bRefreshMatching || // TODO Keine Ahnung, ob das richtig ist
								$bChangeMatching ||
								$bDeleteMatching
							)
					  	) {
							$bError = $bHardCriteriaChanged || $bDeleteMatching; // Bei diesen Aktionen darf es keine Payments geben
							if($bChangeMatching) {
								// Bestätigte Zuweisungen werden bei Veränderung gelöscht, das darf nicht mit Payments passieren (sonst Exception)
								// Sonstige Änderungen würden durch die validatePayment() abgefangen werden.
								foreach($oJourneyAccommodation->getAllocations() as $oAllocation) {
									if($oAllocation->checkConfirmed()) {
										$bError = true;
										break;
									}
								}
							}

							if(
								$bError &&
								!empty($oJourneyAccommodation->getPayments())
							) {
								$sError = 'Es existieren noch Zahlungen zu dieser Unterkunftsbuchung. Die Unterkunftszuweisung kann nicht verändert werden.';
								if($bChangeMatching) {
									// Den Zusammenhang dürfte kein Kunde verstehen, aber vielleicht möchte man das auch gar nicht…
									$sError = 'Es existieren noch Zahlungen zu dieser Unterkunftsbuchung. Bitte löschen Sie die Bestätigung der Unterkunft.';
								}

								$this->addError($sError);
							}
						}

                        if(
                            $this->_oRequest->get('ignore_errors') == 1 &&
                            !$this->hasErrors()
                        ) {
							if($bHardCriteriaChanged) {
								// Wenn harte Kriterien verändert wurden und bestätigt: Alle Zuweisungen löschen
								foreach($aAllocations as $oAllocation) {
									$oAllocation->deleteMatching(false, true);
								}
							} else {
								if(
									$bRefreshMatching === true ||
									$bChangeMatching === true
								) {
									$oJourneyAccommodation->saveMatchingChange($aNewAcco, $aOldAcco);
								}

								if($bDeleteMatching === true) {
									$oJourneyAccommodation->deleteUnfittingAllocations($aOriginalAcco);
								}
							}

                        }

						if(
							!$this->hasErrors() &&
							!$oJourneyAccommodation->checkAllocationContext()
						) {
							$this->addError('Es besteht ein fataler Fehler bei den Zuweisungen dieser Unterkunftsbuchung. Bitte löschen Sie alle Zuweisungen oder kontaktieren Sie den Thebing-Support.');
							Ext_TC_Util::reportError('Fataler Fehler bei Unterkunftszuweisungen', __METHOD__.'(), Inquiry-Accommodation: '.$oJourneyAccommodation->id);
						}

                    }

                    if(
                        $this->_bCurrencyChanged ||
                        $sStatus
                    ){
                        if(!$sStatus){
                            $sStatus = 'edit';
                        }

                        Ext_Thebing_Inquiry_Document_Version::setChange($this->_oInquiry->id, $oJourneyAccommodation->id, 'accommodation', $sStatus);
                        Ext_Thebing_Inquiry_Document_Version::setChange($this->_oInquiry->id, $oJourneyAccommodation->id, 'extra_nights', $sStatus);
                        Ext_Thebing_Inquiry_Document_Version::setChange($this->_oInquiry->id, $oJourneyAccommodation->id, 'extra_weeks', $sStatus);
                        // und das die zusatzkosten verändert wurden ( da sie zur unterkunft gehören )
                        $aAdditionalAccommodationList	= (array)$oJourneyAccommodation->getAdditionalCosts();

                        foreach((array)$aAdditionalAccommodationList as $oCost){
                            $iCostId = (int)$oCost->id;
                            Ext_Thebing_Inquiry_Document_Version::setChange($this->_oInquiry->id, $iCostId, 'additional_accommodation', $sStatus, $oJourneyAccommodation->id);
                        }
                    }

                }
            }
        }
    }

    /**
     * @throws Exception
     */
    public function prepareTransfers() {

        $aTransfers = (array)$this->_oRequest->input('transfer');
 
        $aCurrentTransferIds = array();
        
        foreach($aTransfers as $iJourneyTransfer => $aTransfer){

            // Transfer speichern
            if(isset($aTransfer['start'])){
                // Bei vorhandenen Zahlungen ist das Feld disabled
                $aStartTemp		= explode('_', $aTransfer['start']);

                $aTransfer['start_type']	= $aStartTemp[0];
                $aTransfer['start']			= (int)$aStartTemp[1];
            } 

            if(isset($aTransfer['end'])){
                $aEndTemp		= explode('_', $aTransfer['end']);

                $aTransfer['end_type']		= $aEndTemp[0];
                $aTransfer['end']			= (int)$aEndTemp[1];
            } 
            
            if($aTransfer['start_type'] != 'location'){
                $aTransfer['start_additional'] = 0;
            } 
            if($aTransfer['end_type'] != 'location'){
                $aTransfer['end_additional'] = 0;
            } 

            ## START "Gebucht" flag setzten
            $aTransfer['booked'] = 0;

            if(
				$this->_oObject->transfer_mode & $this->_oObject::TRANSFER_MODE_ARRIVAL &&
                $aTransfer['transfer_type'] == 1
            ){
                $aTransfer['booked'] = 1;
            } else if(
				$this->_oObject->transfer_mode & $this->_oObject::TRANSFER_MODE_DEPARTURE &&
                $aTransfer['transfer_type'] == 2
            ){
                $aTransfer['booked'] = 1;
            } else if(
                $aTransfer['transfer_type'] == 0
            ){
                $aTransfer['booked'] = 1;
            }
            ## ENDE
        
            $oJourneyTransfer               = $this->_oObject->getJoinedObjectChild('transfers', $iJourneyTransfer);
            $aCurrentTransferIds[]          = $oJourneyTransfer->id;

            foreach($aTransfer as $sColumn => $mValue){

                // Wenn Gruppeneinstellungen zeiträume speichern nicht erlauben dann nicht speichern
                if(
                    $this->_iCheckTransferSave == 2 &&
                    in_array($sColumn, array('transfer_date', 'transfer_time', 'pickup'))
                ){
                    continue;
                }

                if(
                    in_array($sColumn, array('transfer_date'))
                ){
                    $mValue = Ext_Thebing_Format::ConvertDate($mValue, $this->_oSchoolForFormat->id, 1, true);
                }

                $oJourneyTransfer->$sColumn = $mValue;
            }
			
            if($oJourneyTransfer->isEmpty()) {
                $this->_oObject->removeJoinedObjectChildByKey('transfers', $iJourneyTransfer);
            }
    
        }

		// Transferart wurde geändert
		$bTransferTypeChanged = $this->_oObject->transfer_mode != $this->_oObject->getOriginalData('transfer_mode');

        // kurse löschen die nicht gespeichert wurden
        $aAllTransfers = $this->_oObject->getJoinedObjectChilds('transfers', true);

        /** @var Ext_TS_Inquiry_Journey_Transfer $oTransfer */
        foreach($aAllTransfers as $oTransfer) {
			
			if($this->_checkDeletedEntries('transfer', $oTransfer) === true) {
				$oTransfer->active = 0;
			}

			$oTransfer->setChanged($bTransferTypeChanged || $this->_bCurrencyChanged);

        }

    }

    /**
     * @throws Exception
     */
    public function prepareInsurances() {

        $aInsurances = $this->_oRequest->input('insurance');
		$aOriginalInsuranceIndexMap = [];

        // Da Versicherungen aus irgendeinem Grund anders aufgebaut sind, werden wir sie hier umformatieren damit das
        // Speichern bei allen 4 Sachen gleich läuft
        $aFinalInsurances = [];
        if(is_array($aInsurances)) {
            foreach($aInsurances as $aInsuranceData) {
                foreach($aInsuranceData as $sColumn => $aInsuranceList) {
                    $iLastNewInsurancesKey = 0;
                    foreach($aInsuranceList as $iIndex => $mValue) {
                        $iInsuranceKey = (int)$aInsuranceData['update'][$iIndex];						
                        if($iInsuranceKey == 0) { // neue Versicherung
                            $iInsuranceKey = $iLastNewInsurancesKey;
                        }
                        $aFinalInsurances[$iInsuranceKey][$sColumn] = $mValue;
						$aOriginalInsuranceIndexMap[$iInsuranceKey] = $iIndex;
                        $iLastNewInsurancesKey--;
                    }
                }
            }
        }

        $aCurrentInsurancesIds = [];

        foreach($aFinalInsurances as $iJourneyInsurance => $aInsuranceData) {

            /** @var Ext_TS_Inquiry_Journey_Insurance $oJourneyInsurance */
            $oJourneyInsurance = $this->_oObject->getJoinedObjectChild('insurances', $iJourneyInsurance);
            $aCurrentInsurancesIds[] = $oJourneyInsurance->id;

            foreach($aInsuranceData as $sColumn => $mValue) {

                if(in_array($sColumn, ['update'])) {
                    continue;
                }

                if($sColumn == 'id') {
                    $sColumn = 'insurance_id';
                }

                if(
                	$sColumn === 'weeks' &&
					empty($mValue)
				) {
					$mValue = null;
				}

                if(in_array($sColumn, ['from', 'until'])) {
                    $mValue = Ext_Thebing_Format::ConvertDate($mValue, $this->_oSchoolForFormat->id, 1, true);
                }

                $oJourneyInsurance->$sColumn = $mValue;

            }

			$bCheckForInsuranceChange = $oJourneyInsurance->checkForChange();

			if(
				$bCheckForInsuranceChange != false &&
				$bCheckForInsuranceChange != 'new'
			) {
                if($oJourneyInsurance->info_customer != '0000-00-00 00:00:00') {
                    $oJourneyInsurance->changes_info_customer = 1;
                }
                if($oJourneyInsurance->info_provider != '0000-00-00 00:00:00') {
                    $oJourneyInsurance->changes_info_provider = 1;
                }
                if($oJourneyInsurance->confirm != '0000-00-00 00:00:00') {
                    $oJourneyInsurance->changes_confirm = 1;
                }
			}

            if($oJourneyInsurance->isEmpty()) {
                $this->_oObject->removeJoinedObjectChildByKey('insurances', $iJourneyInsurance);
				continue;
            }


			self::$aKeyMapping['insurance'][$iJourneyInsurance] = $aOriginalInsuranceIndexMap[$iJourneyInsurance];


			if(true) { //if($this->_oInquiry->isConfirmed()) {

				$sStatus = $oJourneyInsurance->checkForChange();

				if(
					$this->_bCurrencyChanged ||
					$sStatus
				) {
					if(!$sStatus) {
						$sStatus = 'edit';
					}
					Ext_Thebing_Inquiry_Document_Version::setChange($this->_oInquiry->id, $oJourneyInsurance->id, 'insurance', $sStatus);
				}

			}

        }

		$aAllInsurances = $this->_oObject->getJoinedObjectChilds('insurances', true);
		foreach($aAllInsurances as $oJourneyInsurance) {
			if($this->_checkDeletedEntries('insurance', $oJourneyInsurance) === true) {
				$oJourneyInsurance->active = 0;
			}
		}

	}

	/**
	 * @throws Exception
	 */
	public function prepareActivities() {

		$aActivities = $this->_oRequest->input('activity', []);

		$aCurrentActivitiesIds = [];

		foreach($aActivities as $iJourneyActivity => $aActivityData) {

			/** @var Ext_TS_Inquiry_Journey_Activity $oJourneyActivity */
			$oJourneyActivity = $this->_oObject->getJoinedObjectChild('activities', $iJourneyActivity);
			$aCurrentActivitiesIds[] = $oJourneyActivity->id;

			foreach($aActivityData as $sColumn => $mValue) {

				if(in_array($sColumn, ['update'])) {
					continue;
				}

				if($sColumn == 'id') {
					$sColumn = 'activity_id';
				}

				if(in_array($sColumn, ['from', 'until'])) {
					$mValue = Ext_Thebing_Format::ConvertDate($mValue, $this->_oSchoolForFormat->id, 1, true);
				}

				$oJourneyActivity->$sColumn = $mValue;

			}

			if($oJourneyActivity->isEmpty()) {
				$this->_oObject->removeJoinedObjectChildByKey('activities', $iJourneyActivity);
				$oJourneyActivity->deleteAllocations();
				continue;
			}

			$sStatus = $oJourneyActivity->checkForChange();

			if(
				$this->_bCurrencyChanged ||
				$sStatus
			) {
				if(!$sStatus) {
					$sStatus = 'edit';
				}
				Ext_Thebing_Inquiry_Document_Version::setChange($this->_oInquiry->id, $oJourneyActivity->id, 'activity', $sStatus);
			}

		}

		$aAllActivities = $this->_oObject->getJoinedObjectChilds('activities', true);
		foreach($aAllActivities as $oJourneyActivity) {
			if($this->_checkDeletedEntries('activity', $oJourneyActivity) === true) {
				$oJourneyActivity->active = 0;
				$oJourneyActivity->deleteAllocations();
			}
		}

	}

    /**
     * prüft, ob ein Element gelöscht werden soll
     *
     * @param string $sKey
     * @param WDBasic $oWDBasic
     * @return bool
     */
	protected function _checkDeletedEntries($sKey, WDBasic $oWDBasic) {
		
		if(
			$oWDBasic->id > 0 &&
			!empty($this->_aDeleted)
		) {
			if(
				isset($this->_aDeleted[$sKey][$oWDBasic->id]) &&
				reset($this->_aDeleted[$sKey][$oWDBasic->id]) == 1	
			) {							
				return true;
			}
		}
		
		return false;
	}

	public function addJourneySaveWarnings(&$aWarnings) {

		$aDeletedAllocations = $aMovedAllocations = $aDeletedHolidayAllocations = [];
		$aCourses = $this->_oObject->getJoinedObjectChilds('courses', true); /** @var Ext_TS_Inquiry_Journey_Course[] $aCourses */

		$oFormatAllocation = function(Ext_Thebing_School_Tuition_Allocation $oAllocation, Ext_Thebing_School_Tuition_Allocation $oAllocation2=null) {
			$oBlock = $oAllocation->getBlock();
			$sReturn = $this->_oGui->t('Woche').' ';
			$sReturn .= Ext_Thebing_Format::LocalDate($oBlock->week).' ';
			if($oAllocation2 !== null) {
				$sReturn .= '→ '.Ext_Thebing_Format::LocalDate($oAllocation2->getBlock()->week).' ';
			}
			$sReturn .= '('.$this->_oGui->t('Klasse').' ';
			$sReturn .= $oBlock->getClass()->getName().')';
			return $sReturn;
		};

		foreach($aCourses as $oJourneyCourse) {
			if(!empty($oJourneyCourse->aErrors['course_adjusted_allocations_deletions'])) {
				foreach($oJourneyCourse->aErrors['course_adjusted_allocations_deletions'] as $oAllocation) {
					$aDeletedAllocations[] = $oFormatAllocation($oAllocation);
				}
			}

			if(!empty($oJourneyCourse->aErrors['course_adjusted_allocations_holiday_adjustments'])) {
				foreach($oJourneyCourse->aErrors['course_adjusted_allocations_holiday_adjustments'] as $aAllocations) {
					$aMovedAllocations[] = $oFormatAllocation($aAllocations[0], $aAllocations[1]);
				}
			}

			if(!empty($oJourneyCourse->aErrors['course_adjusted_allocations_holiday_deletions'])) {
				foreach($oJourneyCourse->aErrors['course_adjusted_allocations_holiday_deletions'] as $oAllocation) {
					$aDeletedHolidayAllocations[] = $oFormatAllocation($oAllocation);
				}
			}
		}

		if(!empty($aDeletedAllocations)) {
			$aWarnings[] = $this->_oGui->t('Die folgenden Zuweisungen von Kursen wurden gelöscht, da der Zeitraum verändert wurde:').' '.join(', ', $aDeletedAllocations);
		}

		if(!empty($aMovedAllocations)) {
			$aWarnings[] = $this->_oGui->t('Die folgenden Zuweisungen von Kursen wurden aufgrund von Ferien verschoben:').' '.join(', ', $aMovedAllocations);
		}

		if(!empty($aDeletedHolidayAllocations)) {
			$aWarnings[] = $this->_oGui->t('Die folgenden Zuweisungen von Kursen wurden aufgrund von Ferien <strong>gelöscht</strong>, da der Schüler bereits im Ziel-Zeitraum <strong>zugewiesen ist</strong>:').' '.join(', ', $aDeletedHolidayAllocations);
		}

	}

}
