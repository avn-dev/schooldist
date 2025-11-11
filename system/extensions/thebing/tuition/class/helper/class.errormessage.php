<?php

class Ext_Thebing_Tuition_Class_Helper_ErrorMessage {
	
	/**
	 * @var Ext_Thebing_Tuition_Class 
	 */
	protected $_oWDBasic;
	/**
	 * @var Tc\Service\LanguageAbstract
	 */
	protected $_oL10N;

	protected $_aCustomMessages = [];

	/**
	 * @var bool 
	 */
	public $bFound = true;
	
	public function __construct(Ext_Thebing_Tuition_Class $oWDBasic, \Tc\Service\LanguageAbstract $oL10n = null) {
		$this->_oWDBasic = $oWDBasic;
		$this->_oL10N = $oL10n;
	}

	public function setCustomMessages(array $aMessages): static
	{
		$this->_aCustomMessages = $aMessages;
		return $this;
	}

	public function getErrorMessage($sError, $sField='', $sLabel='') {
		
		$sErrorMessage = '';
		$bTranslate = true;

		if (isset($this->_aCustomMessages[(string)$sError])) {
			$sErrorMessage = $this->_aCustomMessages[$sError];
			$this->bFound = true;
		} else {
			switch($sError)
			{
				//
				// Lehrer müssen alles zusammen in ein Case switchen da evt
				// eine ID bzw. der namen direkt als Platzhalterwert übergeben wird
				// keine ahnung warum... aber für #2767 muss das angepasst werden
				//
				case 'INVALID_TEACHER_LEVEL':
					if(empty($sErrorMessage)){
						$sErrorMessage = 'Der Lehrer "%s" unterrichtet das gewählte Level nicht.';
					}
				case 'TEACHER_HAS_ABSENCE':
					if(empty($sErrorMessage)){
						$sErrorMessage = 'Der Lehrer "%s" ist an den gewählten Tagen nicht vollständig anwesend.';
					}
				case 'INVALID_TEACHER_WORKTIME':
					if(empty($sErrorMessage)){
						$sErrorMessage = 'Der Lehrer "%s" steht nicht für die gewählte Unterrichtszeit zur Verfügung.';
					}
				case 'INVALID_TEACHER_COURSE_CATEGORY':
					if(empty($sErrorMessage)){
						$aErrorPlaceHolders = $this->_oWDBasic->aErrorPlaceholder;

						if(is_array($aErrorPlaceHolders[$sField])) {
							$iTeacherId	= reset($aErrorPlaceHolders[$sField]);
						} else {
							$iTeacherId	= $aErrorPlaceHolders[$sField];
						}

						$iCategoryId = $aErrorPlaceHolders[$sField.'[course_category]'];

						$sErrorMessage = $this->t('Der Lehrer "%teacher" unterrichtet die Kategorie "%category" nicht.');
						$oTeacher	= Ext_Thebing_Teacher::getInstance($iTeacherId);
						$oCategory = Ext_Thebing_Tuition_Course_Category::getInstance($iCategoryId);

						$sErrorMessage = str_replace('%category', $oCategory->getName(), $sErrorMessage);
						$sErrorMessage = str_replace('%teacher', $oTeacher->getName(), $sErrorMessage);

						return $sErrorMessage;
					}
				case 'TEACHER_ALLOCATED':
					if(empty($sErrorMessage)){
						$sErrorMessage = 'Der Lehrer "%s" steht nicht zur für die gewählte Unterrichtszeit zur Verfügung.';
					}
				case 'TEACHER_ALLOCATED_IN_FUTURE':
					if(empty($sErrorMessage)){
						$sErrorMessage = 'Der Lehrer "%s" steht in den Folgewochen nicht zur Verfügung.';
					}
				case 'TEACHER_PAYMENTS_EXISTS':
					if(empty($sErrorMessage)){
						$sErrorMessage = 'Es existieren noch Lehrerzahlungen.';
					}
				case 'TEACHER_PAYMENTS_EXISTS_IN_FUTURE':
					if(empty($sErrorMessage)){
						$sErrorMessage = 'Woche %s konnte nicht kopiert werden. Es existieren noch Lehrerzahlungen.';
					}
				case 'TEACHER_INVALID':
					if(empty($sErrorMessage)){
						$sErrorMessage = 'Der Lehrer "%teacher" ist nur gültig bis "%valid_date".';
					}

					$aErrorPlaceHolders = $this->_oWDBasic->aErrorPlaceholder;
					$mErrorPlaceHolder = $aErrorPlaceHolders[$sField];

					if(
						is_object($this->_oWDBasic) &&
						$this->_oWDBasic instanceof WDBasic &&
						is_numeric($mErrorPlaceHolder)
					)
					{

						$sErrorMessage = $this->t($sErrorMessage);

						if(
							isset($aErrorPlaceHolders[$sField])
						){
							$iTeacherId	= $aErrorPlaceHolders[$sField];
							$oTeacher	= Ext_Thebing_Teacher::getInstance($iTeacherId);
							// %teacher auf %s umswitchen damit es für alle Lehrer fehlermeldungen klappt
							$sErrorMessage = str_replace('%teacher', '%s', $sErrorMessage);
							$sErrorMessage = str_replace('%s', $oTeacher->getName(), $sErrorMessage);
							$oFormatDate = new Ext_Thebing_Gui2_Format_Date();
							$sValidUntil = $oFormatDate->formatByValue($oTeacher->valid_until);
							$sErrorMessage = str_replace('%valid_date', $sValidUntil, $sErrorMessage);

							return $sErrorMessage;
						}
					}
					break;
				//
				// ende der Lehrer switches!
				// #########################
				//
				case 'ENTITY_LOCKED':
					$sErrorMessage = 'Die Klasse wird gerade bereits von einer anderen Stelle aus bearbeitet. Bitte versuchen Sie es zu einem späteren Zeitpunkt erneut.';
					break;
				case 'ROOM_ALLOCATED':
					$sErrorMessage = 'Der Raum "%s" ist bereits belegt.';
					break;
				case 'ROOM_INVALID':
					$sErrorMessage = 'Der Raum "%room" ist nur gültig bis "%valid_date".';
					$sErrorMessage = $this->t($sErrorMessage);
					if(is_object($this->_oWDBasic) && $this->_oWDBasic instanceof WDBasic)
					{
						$aErrorPlaceHolders = $this->_oWDBasic->aErrorPlaceholder;
						if(
							isset($aErrorPlaceHolders[$sField])
						) {

							if(is_array($aErrorPlaceHolders[$sField])) {
								$iRoomId = reset($aErrorPlaceHolders[$sField]);
							} else {
								$iRoomId = $aErrorPlaceHolders[$sField];
							}

							$oRoom		= Ext_Thebing_Tuition_Classroom::getInstance($iRoomId);
							$sErrorMessage = str_replace('%room', $oRoom->getName(), $sErrorMessage);
							$oFormatDate = new Ext_Thebing_Gui2_Format_Date();
							$sValidUntil = $oFormatDate->formatByValue($oRoom->valid_until);
							$sErrorMessage = str_replace('%valid_date', $sValidUntil, $sErrorMessage);

							return $sErrorMessage;
						}
					}
					break;
				case 'ROOM_MULTIPLE_INCOMPATIBILITY':
					$sErrorMessage = 'Bei einer Mehrfachauswahl des Raums müssen bei allen Blöcken die gleichen Räume ausgewählt werden.';
					break;
				case 'ROOM_ALLOCATED_IN_FUTURE':
					$sErrorMessage = 'Der Raum "%s" steht in den Folgewochen nicht zur Verfügung.';
					break;
				case 'COURSE_NOT_AVAILABLE':
					$sErrorMessage = 'Der Kurs findet auf Grund von Ferien nicht zur gewählten Unterrichtszeit statt.';
					break;
				case 'BLOCK_SAVE_ERROR':
					$sErrorMessage = 'Der Block konnte nicht gespeichert werden.';
					break;
				case 'COPY_WEEK_ERROR':
					$sErrorMessage	= 'Es ist ein Fehler beim Kopieren der %s. Woche aufgetreten.';

					$aField			= explode('#', $sField);
					$sErrorAddon	= ' '.$this->getErrorMessage($aField[2], $aField[1]);
					break;
				case 'TEMPLATE_SAVE_ERROR':
					$sErrorMessage = 'Die Vorlage konnte nicht gespeichert werden.';
					break;
				case 'INVALID_UNTIL':
				case 'ATTENDANCE_FOUND':
					$oGui = new \Ext_TC_Gui2();
					$oGui->gui_description = $this->_oL10N->getContext();
					$oTuitionDataClass = new Ext_Thebing_Tuition_Gui2_Template($oGui);
					return $oTuitionDataClass->getErrorMessage($sError, $sField, $sLabel);
				case 'INCOMPATIBLE_COURSES':
					$sErrorMessage = $this->t('Die Änderungen können nicht durchgeführt werden. Die Kurse der folgenden Zuweisungen sind nicht in den Kursen der Klasse enthalten:');
					if(is_object($this->_oWDBasic) && $this->_oWDBasic instanceof WDBasic)
					{
						$aErrorPlaceHolders = $this->_oWDBasic->aErrorPlaceholder;
						if(
							array_key_exists('ktcl.courses', $aErrorPlaceHolders) &&
							isset($aErrorPlaceHolders['ktcl.courses']['incompatible'])
						)
						{
							$sErrorMessage .= '<br />'.$aErrorPlaceHolders['ktcl.courses']['incompatible'];
						}
					}
					return $sErrorMessage;
					break;
				case 'WEEK_REDUCE_ERROR':
					$sErrorMessage = 'Woche %s konnte nicht verkürzt werden. Es existieren noch Lehrerzahlungen.';
					break;
				case 'OTHER_SCHOOL_CLASS_NOT_CHANGABLE':
					$sErrorMessage = 'Klasse nicht editierbar!';
					break;
				case 'TUITION_ALLOCATIONS_FOUND':
					$sErrorMessage = $this->t('Die Einstellungen für Lektionen können nicht verändert werden, da die Klasse bereits aktive Zuweisungen in der Klassenplanung hat.');
					break;
				case 'LESSON_DURATION_EDITED_CURRENTLY':
					$sErrorMessage = $this->t('Die Lektionsdauer wurden vor kurzem angepasst, im Hintergrund laufen noch Prozesse ab. Bitte versuchen Sie es zu einem späteren Zeitpunkt erneut');
					break;
				case 'LESSON_DURATION_BY_EXISTING_ALLOCATIONS':
					$sErrorMessage = $this->t('Die Lektionsdauer wurden verändert. Bereits eingetragene Werte, die auf der alten Lektionsdauer basierten, werden angepasst.');
					break;
				case 'SAVE_CLASS_ERROR':
					$sError = 'Fehler beim Speichern der Klasse "%s".';
					break;
				case 'INVALID_COURSE':
					if(is_object($this->_oWDBasic) && $this->_oWDBasic instanceof WDBasic)
					{
						$aErrorPlaceHolders = $this->_oWDBasic->aErrorPlaceholder;
						if(
							isset($aErrorPlaceHolders['ktcl.courses']) &&
							isset($aErrorPlaceHolders['ktcl.courses']['invalid'])
						){
							$aInvalidCourseIds	= $aErrorPlaceHolders['ktcl.courses']['invalid'];
							$iCourseId			= reset($aInvalidCourseIds);
							$iKey				= key($aInvalidCourseIds);
							$oCourse			= Ext_Thebing_Tuition_Course::getInstance($iCourseId);
							$dValidDate			= $oCourse->valid_until;
							$oFormatDate		= new Ext_Thebing_Gui2_Format_Date();
							$sValidDate			= $oFormatDate->formatByValue($dValidDate);

							$sErrorMessage = 'Der Kurs "%course" ist nur gültig bis "%valid_date".';
							$sErrorMessage = $this->t($sErrorMessage);

							$sErrorMessage = str_replace('%course', $oCourse->getName(), $sErrorMessage);
							$sErrorMessage = str_replace('%valid_date', $sValidDate, $sErrorMessage);

							unset($this->_oWDBasic->aErrorPlaceholder['ktcl.courses']['invalid'][$iKey]);

							return $sErrorMessage;
						}
					}
					break;
				case 'ATTENDANCE_EXISTS':
					$sErrorMessage = 'Einträge in der Anwesenheit gefunden! Die Vorlage kann nicht verändert werden!';
					break;
				case 'ATTENDANCE_EXISTS_IN_FUTURE':
					$sErrorMessage = 'Woche %s konnte nicht kopiert werden. Einträge in der Anwesenheit gefunden! Die Vorlage kann nicht verändert werden!';
					break;
				case 'ATTENDANCE_EXISTS_FOR_DAYS':
					$sErrorMessage = 'Einträge in der Anwesenheit gefunden! Die Wochentage können nicht verändert werden!';
					break;
				case 'NO_ONLINE_ROOM_ALLOCATED_STUDENTS':
					$sErrorMessage = 'Es wurde kein Online-Klassenzimmer ausgewählt, allerdings sind Schüler zu einem Online-Klassenzimmer zugewiesen.';
					break;
				case 'NO_OFFLINE_ROOM_ALLOCATED_STUDENTS':
					$sErrorMessage = 'Es wurde kein Offline-Klassenzimmer ausgewählt, allerdings sind Schüler zu einem Offline-Klassenzimmer zugewiesen.';
					break;
				case 'DIFFERENT_LEVELS':
					$sErrorMessage = 'Bitte überprüfen Sie, ob die Level der Schüler übernommen werden sollen.';
					break;
				case 'SAVE_CLASS_EXCEPTION':
				case 'SAVE_CLASS_ERROR':
					$sErrorMessage = 'Die Klasse konnte nicht gespeichert werden.';
					break;
				case 'BLOCK_OVERLAPPING':
					$sErrorMessage = 'Die angelegten Blöcke können nicht zur selben Zeit stattfinden. Bitte korrigieren Sie die Eingaben.';
					break;
				case 'STUDENTS_OVERLAPPING':
					$sErrorMessage = 'Durch die Änderung würden Schüler zur selben Zeit mehreren Klassen zugewiesen sein. Bitte korrigieren Sie die Eingaben.';
					break;
				case 'CLASS_ALREADY_CONFIRMED':
					$sErrorMessage = 'Die Klasse ist bereits bestätigt.';
					break;
				default:
					$this->bFound = false;
					break;
			}
		}

		$sErrorReplaced = '';

		if($this->bFound) {
			if(
				is_object($this->_oWDBasic) && 
				$this->_oWDBasic instanceof WDBasic && 
				!empty($sErrorMessage)
			) {
				$aErrorPlaceHolders = $this->_oWDBasic->aErrorPlaceholder;

				if($bTranslate) {
					$sErrorReplaced		= $this->t($sErrorMessage);
				}
				
				if(array_key_exists($sField, $aErrorPlaceHolders))
				{
					$sField				= $aErrorPlaceHolders[$sField];
					$sErrorReplaced		= sprintf($sErrorReplaced, $sField);
				}
			}
		} else {
			$sErrorReplaced = $sErrorMessage;
		}
		
		$sErrorReplaced .= $sErrorAddon;
		
		return $sErrorReplaced;
				
	}
	
	public function t($sTranslate){
		
		if($this->_oL10N) {
			$sTranslate = $this->_oL10N->translate($sTranslate);
		} else {
			$sTranslate = L10N::t($sTranslate);
		}
		
		return $sTranslate;
	}
	
}
