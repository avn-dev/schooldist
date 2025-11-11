<?php

class Ext_TS_Marketing_Feedback_Questionary_Generator extends Ext_TC_Marketing_Feedback_Questionary_Generator {

	/**
	 * @var Ext_TS_Inquiry
	 */
	protected $oRootObject;

	/**
	 * @var array
	 */
	private $aDependencyTeachers;

	/**
	 * @inheritdoc
	 */
	public function getDependencyConfiguration() {

		$aConfig = [];

		$aConfig['course_category'] = new Ext_TC_Marketing_Feedback_Question_ConfigDTO();
		$aConfig['course_category']->bDependencies = true;
		$aConfig['course_category']->bSubDependencies = true;

		$aConfig['course'] = new Ext_TC_Marketing_Feedback_Question_ConfigDTO();
		$aConfig['course']->bDependencies = true;
		$aConfig['course']->bSubDependencies = true;

		$aConfig['accommodation_category'] = new Ext_TC_Marketing_Feedback_Question_ConfigDTO();
		$aConfig['accommodation_category']->bDependencies = true;
		$aConfig['accommodation_category']->bSubDependencies = true;

		$aConfig['meal'] = new Ext_TC_Marketing_Feedback_Question_ConfigDTO();
		$aConfig['meal']->bDependencies = true;
		$aConfig['meal']->bSubDependencies = true;

		$aConfig['transfer'] = new Ext_TC_Marketing_Feedback_Question_ConfigDTO();
		$aConfig['transfer']->bDependencies = true;
		$aConfig['transfer']->bDependencyObject = true; // Keine WDBasic, fixe Typen

		$aConfig['teacher'] = new Ext_TC_Marketing_Feedback_Question_ConfigDTO();
		$aConfig['teacher']->bDependencies = true;
		$aConfig['teacher']->bSubDependencies = true;
		$aConfig['teacher']->bDependencyObject = true; // Man wählt nichts aus und die Lehrer werden hier dazu gefaked

		$aConfig['teacher_course'] = new Ext_TC_Marketing_Feedback_Question_ConfigDTO();
		$aConfig['teacher_course']->bDependencies = true;
		$aConfig['teacher_course']->bSubDependencies = false; // 2. Ebene sind Kurse, aber es werden Lehrer angezeigt
		$aConfig['teacher_course']->bDependencyObject = true; // Lehrer werden hier dazu gefaked

		$aConfig['rooms'] = new Ext_TC_Marketing_Feedback_Question_ConfigDTO();
		$aConfig['rooms']->bDependencies = true;
		$aConfig['rooms']->bSubDependencies = true;

		$aConfig['accommodation_provider'] = new Ext_TC_Marketing_Feedback_Question_ConfigDTO();
		$aConfig['accommodation_provider']->bDependencies = true;
		$aConfig['accommodation_provider']->bSubDependencies = true;

		$aConfig['booking_type'] = new Ext_TC_Marketing_Feedback_Question_ConfigDTO();
		$aConfig['booking_type']->bDependencies = true;
		$aConfig['booking_type']->bDependencyObject = true; // Keine WDBasic, fixe Typen

		return $aConfig;

	}

	/**
	 * Gibt die zugehörige WDBasic Klasse zurück anhand
	 * des übergebenen Typen und dessen Instance Id
	 *
	 * @param $sType
	 * @param $iId
	 * @return Ext_Thebing_School|null
	 * @throws InvalidArgumentException
	 */
	protected function getWDBasic($sType, $iId) {

		switch($sType) {
			case 'rooms':
			case 'meal':
			case 'accommodation_provider':
			case 'accommodation_category':
			case 'course':
			case 'course_category':
				$oObject = Ext_Thebing_School::getInstance($iId);
				break;
//			case 'transfer':
//			case 'teacher':
//			case 'teacher_course':
//			case 'booking_type':
//				// In den anderen Fall mit $this->getDependencyObject() springen
//				$oObject = null;
//				break;
			default:
				throw new InvalidArgumentException('Invalid WDBasic Type');
		}

		return $oObject;
	}

	/**
	 * Gibt die zugehörige WDBasic Klasse zurück anhand
	 * des übergebenen Typen und dessen Instance Id
	 *
	 * @see \TsStatistic\Generator\Statistic\FeedbackSum::getDependencyLabel()
	 * @param string $sType
	 * @param integer $iId
	 * @return Ext_Thebing_Tuition_Course|Ext_Thebing_Tuition_Course_Category|Ext_Thebing_Accommodation_Category|Ext_Thebing_Accommodation_Meal|Ext_Thebing_Accommodation_Room|Ext_Thebing_Accommodation
	 * @throws InvalidArgumentException
	 */
	protected function getSubWDBasic($sType, $iId) {

		switch($sType) {
			case 'course':
				$oObject = Ext_Thebing_Tuition_Course::getInstance($iId);
				break;
			case 'course_category':
				$oObject = Ext_Thebing_Tuition_Course_Category::getInstance($iId);
				break;
			case 'accommodation_category':
				$oObject = Ext_Thebing_Accommodation_Category::getInstance($iId);
				break;
			case 'meal':
				$oObject = Ext_Thebing_Accommodation_Meal::getInstance($iId);
				break;
			case 'rooms':
				$oObject = Ext_Thebing_Accommodation_Room::getInstance($iId);
				break;
			case 'accommodation_provider':
				$oObject = Ext_Thebing_Accommodation::getInstance($iId);
				break;
			default:
				throw new InvalidArgumentException('Invalid SubWDBasic Type');
		}

		return $oObject;

	}

	/**
	 * Überprüft ob eine Abhänigkeit übereintrifft
	 *
	 * @param string $sType
	 * @param object $oDependencyObject Darf auch null sein, da der Buchungstyp zum Beispiel kein Objekt ist.
	 * @return bool
	 * @throws InvalidArgumentException
	 * @see \Ext_TS_Marketing_Feedback_Questionary_Generator::getDependencyObject()
	 */
	protected function checkDependency($sType, $oDependencyObject = null) {

		$oInquiry = $this->oRootObject;

		switch($sType) {
			case 'rooms':
			case 'meal':
			case 'accommodation_provider':
			case 'accommodation_category':
			case 'course':
			case 'course_category':
				// 1. Abhängigkeit Schule, daher niemals als Spalte hinzufügen
				return false;
				break;
			case 'transfer':
				foreach($oInquiry->getJourneys() as $oJourney) {
					foreach($oJourney->getUsedTransfers() as $oJourneyTransfer) {
						if($this->checkTransferDependency($oJourneyTransfer, $oDependencyObject)) {
							return true;
						}
					}
				}
				break;
			case 'teacher':
			case 'teacher_course':

				// In der getDependencyObject() werden jetzt direkt die ganzen Prüfungen gemacht
//				$aTeachers = &$oDependencyObject->aTeachers;
//				$aTeachersWithAttendance = [];
//
//				foreach($oInquiry->getJourneys() as $oJourney) {
//					foreach($oJourney->getUsedCourses() as $oJourneyCourse) {
//						/** @var Ext_Thebing_School_Tuition_Allocation[] $aAllocations */
//						$aAllocations = $oJourneyCourse->getJoinedObjectChilds('tuition_blocks', true);
//						foreach($aAllocations as $oAllocation) {
//
//							$oBlock = $oAllocation->getBlock();
//
//							if(in_array($oBlock->teacher_id, $aTeachersWithAttendance)) {
//								// Für Lehrer wurde bereits Anwesenheit ermittelt, muss also nicht nochmal durchlaufen
//								continue;
//							}
//
//							$oTeacher = $oAllocation->getBlock()->getTeacher();
//							$fAttendance = Ext_Thebing_Tuition_Attendance_Index::getAttendanceForInquiryAndTeacher($oInquiry, $oTeacher);
//
//							// Bei Lehrer abhängig vom Kurs: Wenn Kurs nicht ausgewählt, dann $fAttendance = 0, damit der Lehrer unten rausfliegt
//							// Eigentlich wäre das Teil von den Sub-Types in \Ext_TC_Marketing_Feedback_Questionary_Generator::addQuestion()
//							if($sType === 'teacher_course') {
//								if(!in_array($oJourneyCourse->course_id, $oDependencyObject->aCourses)) {
//									$fAttendance = 0;
//								}
//							}
//
//							if(
//								// Es gibt Schulen die benutzen die Anwesenheitsliste nicht, ist die aktuelle Schule so eine
//        						// dann ist die Abwesenheit immer null und muss im Feedback angezeigt werden! #9562
//								$fAttendance === null ||
//								$fAttendance > 0
//							) {
//								$aTeachersWithAttendance[] = $oTeacher->id;
//							}
//
//						}
//					}
//				}
//
//				// Alle Lehrer entfernen, für die keine Anwesenheit in dieser Buchung ermittelt werden konnte
//				$aTeachers = array_filter($aTeachers, function(Ext_Thebing_Teacher $oTeacher) use($aTeachersWithAttendance) {
//					return in_array($oTeacher->id, $aTeachersWithAttendance);
//				});

				// Wenn alle abgearbeitet sind, prüfe ob $oDependencyObject überhaupt noch einen Teacher hat.
				if(!empty($oDependencyObject->aTeachers)) {
				    return true;
				}

				break;
			case 'booking_type':

				$bAgencyInquiry = $oInquiry->hasAgency();

				// \Ext_TS_Marketing_Feedback_Questionary_Generator::getDependencyObject()
				if(
					(
						// Direktbucher
						$oDependencyObject->type === 1 &&
						$bAgencyInquiry === false
					) || (
						// Agenturbucher
						$oDependencyObject->type === 2 &&
						$bAgencyInquiry === true
					)
				) {
					return true;
				}

				break;
			default:
				throw new InvalidArgumentException('Invalid Check-Dependency Type');
		}

		return false;
	}

	/**
	 * Überprüft ob eine Sub-Abhänigkeit übereintrifft
	 *
	 * @param string $sType
	 * @param $oDependencyObject
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	protected function checkSubDependency($sType, $oDependencyObject) {

		$oInquiry = $this->oRootObject;

		if(
			$this->aSubDependencyFilter !== null &&
			$this->aSubDependencyFilter[$sType] != $oDependencyObject->id
		) {
			return false;
		}

		switch($sType) {
			case 'course_category':
				foreach($oInquiry->getJourneys() as $oJourney) {
					foreach($oJourney->getUsedCourses() as $oJourneyCourse) {
						$oCourseCategory = $oJourneyCourse->getCourse()->getCategory();
						if($oCourseCategory->id == $oDependencyObject->id) {
							return true;
						}
					}
				}
				break;
			case 'course':
			case 'teacher_course':
				foreach($oInquiry->getJourneys() as $oJourney) {
					foreach($oJourney->getUsedCourses() as $oJourneyCourse) {
						if($oJourneyCourse->getCourse()->id == $oDependencyObject->id) {
							return true;
						}
					}
				}
				break;
			case 'accommodation_category':
				foreach($oInquiry->getJourneys() as $oJourney) {
					foreach($oJourney->getUsedAccommodations() as $oJourneyAccommondation) {
						if($oJourneyAccommondation->getCategory()->id == $oDependencyObject->id) {
							return true;
						}
					}
				}
				break;
			case 'meal':
				foreach($oInquiry->getJourneys() as $oJourney) {
					foreach($oJourney->getUsedAccommodations() as $oJourneyAccommondation) {
						if($oJourneyAccommondation->getMeal()->id == $oDependencyObject->id) {
							return true;
						}
					}
				}
				break;
			case 'rooms':
				foreach($oInquiry->getJourneys() as $oJourney) {
					foreach($oJourney->getUsedAccommodations() as $oJourneyAccommondation) {
						foreach($oJourneyAccommondation->getRooms() as $oJourneyRoom) {
							if($oJourneyRoom->id == $oDependencyObject->id) {
								return true;
							}
						}
					}
				}
				break;
			case 'accommodation_provider':
				foreach($oInquiry->getJourneys() as $oJourney) {
					foreach($oJourney->getUsedAccommodations() as $oJourneyAccommondation) {
						foreach($oJourneyAccommondation->getAllocations() as $oAllocation) {
							if($oAllocation->getAccommodationProvider()->id == $oDependencyObject->id) {
								return true;
							}
						}
					}
				}
				break;
			default:
				throw new InvalidArgumentException('Invalid CheckSub-Dependency Type');
		}

		return false;
	}

	/**
	 * Fügt einen Spaltentitel hinzu
	 *
	 * @param $aQuestion
	 * @param $oQuestion
	 * @param null $oDependencyObject
	 */
	protected function addColumn(&$aQuestion, $oQuestion, $oDependencyObject = null) {

		switch($oQuestion->dependency_on) {
			case 'teacher':
			case 'teacher_course':
				// TODO Das ist total unsauber, was bei dieser Abhängigkeit passiert
				/** @var $oDependencyObject stdClass */
				foreach((array)$oDependencyObject->aTeachers as $oTeacher) {
					$oColumn = &$aQuestion['columns'][];
					$oColumn['title'] = $this->getColumnTitle($oQuestion->dependency_on, $oTeacher);
					$oColumn['dependencyId'] = $this->getColumnDependencyId($oTeacher);
				}
				break;
			// getColumnDependencyId bekommt den Wert null, da es kein abhängiges Objekt gibt und der Titel ist auch
			// ein leerer String. Dieser Fall ist da, damit die Antworten der Frage wieder richtig zum Frage zugeordnet werden können.
			// Ansonsten führt das zu dem Fehler das nicht alle Pflichtfelder ausgefüllt wurden und das Feedbackformular nicht abgeschickt wird.
			case 'booking_type':
				$oColumn = &$aQuestion['columns'][];
				$oColumn['title'] = $this->getColumnTitle($oQuestion->dependency_on, $oDependencyObject);
				$oColumn['dependencyId'] = $this->getColumnDependencyId(null);
				break;
			default:
				parent::addColumn($aQuestion, $oQuestion, $oDependencyObject);
				break;
		}

	}

	/**
	 * @inheritdoc
	 */
	protected function getDependencyObject(Ext_TC_Marketing_Feedback_Question $oQuestion, $iId = null) {

		switch($oQuestion->dependency_on) {
			case 'teacher':
			case 'teacher_course':
				$oDependencyObject = new stdClass();

				// Hier muss nur EINMAL pro Buchung geprüft werden, ob der Schüler den Lehrer irgendwie hatte
				if($this->aDependencyTeachers === null) {

					$aInquiryTeachers = $this->oRootObject->getTuitionTeachers();
					foreach($aInquiryTeachers as $aInquiryTeacher) {

						if(
							$this->aSubDependencyFilter !== null &&
							$this->aSubDependencyFilter[$oQuestion->dependency_on] != $aInquiryTeacher['teacher_id']
						) {
							continue;
						}

						// Lehrer abhängig von Kurs: Prüfen, ob der Lehrer irgendeinen Kurs hat, der in der Abhängigkeit vorkommt
						if($oQuestion->dependency_on === 'teacher_course') {
							$aFilter = array_filter(explode(',', $aInquiryTeacher['course_ids']), function($iCourseId) use($oDependencyObject) {
								return in_array($iCourseId, (array)$oDependencyObject->aCourses);
							});
							if(empty($aFilter)) {
								continue;
							}
						}

						$oTeacher = Ext_Thebing_Teacher::getInstance($aInquiryTeacher['teacher_id']);
						$fAttendance = Ext_Thebing_Tuition_Attendance_Index::getAttendanceForInquiryAndTeacher($this->oRootObject, $oTeacher);

						if(
							// Wenn gar keine Anwesenheit gespeichert wurde (Schule benutzt das Feature z.B: nicht), dann Lehrer anzeigen
							$fAttendance === null ||
							$fAttendance > 0
						) {
							$oDependencyObject->aTeachers[$oTeacher->id] = $oTeacher;
						}

					}

					$this->aDependencyTeachers = $oDependencyObject->aTeachers;

				} else {
					$oDependencyObject->aTeachers = $this->aDependencyTeachers;
				}

//				/** @var $oInquiry Ext_TS_Inquiry */
//				$oInquiry = $this->oInquiry;
//				foreach($oInquiry->getJourneys() as $oJourney) {
//					foreach($oJourney->getUsedCourses() as $oCourse) {
//						foreach($oCourse->getAllocations() as $iAllocationId) {
//							$oAllocation = Ext_Thebing_School_Tuition_Allocation::getInstance($iAllocationId);
//							$oTeacher = $oAllocation->getBlock()->getTeacher();
//							if($oTeacher->id > 0) {
//								$oDependencyObject->aTeachers[$oTeacher->id] = $oTeacher;
//							}
//						}
//					}
//				}
//
//				// Weiterreichen, weil Teacher ein atypischer Typ ist…
//				if($oQuestion->dependency_on === 'teacher_course') {
//					$oDependencyObject->aCourses = $oQuestion->dependency_subobjects;
//				}

				break;
			case 'booking_type':
				$oDependencyObject = new stdClass();
				$oDependencyObject->type = (int)$iId;
				break;
			default:
				$oDependencyObject = parent::getDependencyObject($oQuestion, $iId);
		}

		return $oDependencyObject;
	}

	/**
	 * Prüft ob der angegebene Transfer gebucht worden ist
	 *
	 * @param Ext_TS_Inquiry_Journey_Transfer $oJourneyTransfer
	 * @param $oDependencyObject
	 * @return bool
	 */
	protected function checkTransferDependency(Ext_TS_Inquiry_Journey_Transfer $oJourneyTransfer, $oDependencyObject) {

		$oJourney = $oJourneyTransfer->getJourney();

		switch($oDependencyObject->id) {
			case '1':
				$bRetVal = $oJourney->transfer_mode & $oJourney::TRANSFER_MODE_ARRIVAL;
				break;
			case '2':
				$bRetVal = $oJourney->transfer_mode & $oJourney::TRANSFER_MODE_DEPARTURE;
				break;
			case '3':
				$bRetVal = $oJourney->transfer_mode & $oJourney::TRANSFER_MODE_BOTH;
				break;
			case '4':
				$bRetVal = $oJourney->transfer_mode == $oJourney::TRANSFER_MODE_NONE;
				break;
			case '5':
				$bRetVal = $oJourneyTransfer->transfer_type == $oJourneyTransfer::TYPE_ADDITIONAL;
				break;
			default:
				$bRetVal = false;
				break;
		}

		return $bRetVal;

	}

	/**
	 * @inheritdoc
	 */
	protected function getSubDependencies(Ext_TC_Marketing_Feedback_Question $oQuestion) {

		if($oQuestion->dependency_on === 'accommodation_provider') {
			return $this->getAccommodationProviders($oQuestion);
		}

		return parent::getSubDependencies($oQuestion);

	}

	/**
	 * @param Ext_TC_Marketing_Feedback_Question $oQuestion
	 * @return array
	 */
	private function getAccommodationProviders(Ext_TC_Marketing_Feedback_Question $oQuestion) {

		if($oQuestion->accommodation_provider == 'not_all_provider') {
			return $oQuestion->dependency_subobjects;
		}

		if($this->oRootObject === null) {
			return [];
		}

		$aIds = [];
		$oSchool = $this->oRootObject->getSchool();

		switch($oQuestion->accommodation_provider) {
			case 'all_provider':
				$aAccommodationProviders = $oSchool->getAccommodationProvider(false);
				foreach($aAccommodationProviders as $oAccommodationProvider) {
					$aIds[] = $oAccommodationProvider->id;
				}
				break;

			case 'just_host_family':
			case 'just_other_accommodations':
				$oAccommodationRepo = new Ext_Thebing_Accommodation_Matching_AccommodationRepository(DB::getDefaultConnection(), (int)$oSchool->getId());
				if($oQuestion->accommodation_provider === 'just_host_family') {
					$oAccommodationRepo->enableTypeHostfamily();
				} else {
					$oAccommodationRepo->enableTypeOther();
				}
				$aResults = $oAccommodationRepo->getCollection();
				foreach($aResults as $aData) {
					$aIds[] = $aData['id'];
				}
				break;
		}

		return $aIds;

	}

}
