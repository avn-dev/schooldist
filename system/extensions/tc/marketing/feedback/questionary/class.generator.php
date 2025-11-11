<?php

abstract class Ext_TC_Marketing_Feedback_Questionary_Generator {

	/**
	 * @var Ext_TC_Marketing_Feedback_Questionary
	 */
	protected $oQuestionary;

	/**
	 * @var Ext_TC_Basic|null
	 */
	protected $oRootObject;

	/**
	 * @var string
	 */
	protected $sLanguage;

	/**
	 * @var array
	 */
	protected $aSubDependencyFilter;

	/**
	 * @param Ext_TC_Basic|null $oRootObject Ext_TS_Inquiry|Ext_TA_Inquiry; wenn null werden alle Fragen generiert!
	 * @param Ext_TC_Marketing_Feedback_Questionary $oQuestionary
	 * @param string $sLanguage
	 */
	public function __construct($oRootObject, Ext_TC_Marketing_Feedback_Questionary $oQuestionary, $sLanguage) {
		$this->oRootObject = $oRootObject;
		$this->oQuestionary = $oQuestionary;
		$this->sLanguage = $sLanguage;
	}

	/**
	 * Nur Fragen anzeigen, bei welcher die angegebenen Abhängigkeiten übereinstimmen
	 *
	 * @param array $aDependency
	 */
	public function setSubDependencyFilter(array $aDependency = null) {
		$this->aSubDependencyFilter = $aDependency;
	}

	/**
	 * @return array
	 */
	public function generate() {

		$aResultArray = array();
		$aDeleteIds = array();
		$aDeleteKeys = array();
		$aLastLoop = null;

		$aQuestionaryChilds = $this->oQuestionary->getChilds();

		foreach($aQuestionaryChilds as $oQuestionaryChild) {
			if($oQuestionaryChild->parent_id == 0) {
				$aChilds = $oQuestionaryChild->getChilds();
				$this->addChild($oQuestionaryChild, $aResultArray, empty($aChilds));
				foreach($aChilds as $oChild) {
					$this->addChild($oChild, $aResultArray);
				}
			}
		}

		$aResultArray = array_values($aResultArray);
		$iResultArrayCount = count($aResultArray);
		$aResultArrayReverse = array_reverse($aResultArray);

		// Wenn der erste(reverse)/letzte(result array) Eintrag
		// eine Überschrift ist dann muss diese automatisch dieser Eintrag
		// LastLoop gesetzt werden da dieser keinen Eintrag mehr zuvor hat
		if(isset($aResultArrayReverse[0]['heading'])) {
			$aLastLoop = $aResultArrayReverse[0]['heading'];
		}

		// Sammelt alle Einträge
		// die nicht gewünscht sind da bspw.
		// die Heading keine Fragen enthält
		foreach($aResultArrayReverse as $iReverseKey => $aReverse) {

			$aHeading = $aReverse['heading'];

			if(
				// Ist der jetzige Eintrag eine Überschrift
				// und nicht der Parent des letzten Schleifendurchlauf
				// kann der jetzige Eintrag gelöscht werden
				isset($aHeading) &&
				!$aReverse['heading']['showAlways'] &&
				$aLastLoop !== null &&
				$aLastLoop['parentId'] !== $aHeading['id']
			) {

				// Merken der Parent-Id, da diese evtl. ebenfalls gelöscht werden könnte
				$aDeleteIds[$aHeading['parentId']] = true;
				$aDeleteKeys[$aHeading['id']] = ($iResultArrayCount - 1) - $iReverseKey;

			} elseif(
				// Wenn die jetzige Überschrift als gelöscht makiert wurde,
				// muss geprüft werden, ob alle Kinder ebenfalls gelöscht wurden.
				// Falls dies nicht der Fall ist, wird die aktuelle Überschrift nicht gelöscht.
				isset($aHeading) &&
				isset($aDeleteIds[$aHeading['id']])
			) {

				$bDelete = true;

				foreach($aResultArrayReverse as $aReverseTmp) {

					if(
						$aReverseTmp['heading']['parentId'] == $aHeading['id'] &&
						!isset($aDeleteKeys[$aReverseTmp['heading']['id']])
					) {
						$bDelete = false;
						break;
					}

				}

				if($bDelete) {
					$aDeleteKeys[$aHeading['id']] = ($iResultArrayCount - 1) - $iReverseKey;
				} else {
					unset($aDeleteIds[$aHeading['id']]);
				}

			}

			// Merkt sich Eintrag aus dem
			// jetzigen Schleifendurchlauf
			$aLastLoop = $aReverse;
			if(isset($aHeading)) {
				$aLastLoop = $aHeading;
			}

		}

		// Nimmt vorgemerkte zu löschende Einträge
		// wieder heraus da diese doch Fragen enthalten
		foreach($aResultArrayReverse as $aReverse) {

			if(
				!isset($aReverse['heading']) &&
				$aReverse['parentId'] !== 0 &&
				isset($aDeleteKeys[$aReverse['parentId']])
			) {
				unset($aDeleteKeys[$aReverse['parentId']]);
			}

		}

		// Löscht alle vorgemerkten
		// Einträge aus dem Array
		foreach($aDeleteKeys as $iDeleteKey) {
			unset($aResultArray[$iDeleteKey]);
		}

		// Keys neu sortieren
		$aResultArray = array_values($aResultArray);

		return $aResultArray;
	}

	/**
	 * Fügt ein Child anhand des Typens hinzu
	 *
	 * @param Ext_TC_Marketing_Feedback_Questionary_Child $oQuestionaryChild
	 * @param $aResultArray
	 * @param bool $bShowAlways
	 */
	public function addChild(Ext_TC_Marketing_Feedback_Questionary_Child $oQuestionaryChild, &$aResultArray, $bShowAlways = false) {

		if($oQuestionaryChild->type === 'heading') {
			$this->addHeading($oQuestionaryChild, $aResultArray, $bShowAlways);
		} else {
			$this->addQuestion($oQuestionaryChild, $aResultArray);
		}

	}

	/**
	 * Fügt eine Heading dem Result Array hinzu
	 *
	 * @param Ext_TC_Marketing_Feedback_Questionary_Child $oQuestionaryChild
	 * @param array $aResultArray
	 * @param bool $bShowAlways
	 */
	private function addHeading(Ext_TC_Marketing_Feedback_Questionary_Child $oQuestionaryChild, &$aResultArray, $bShowAlways = false) {

		// TODO Auf DTO umstellen
		$oHeading = $oQuestionaryChild->getHeading();
		$oChild = $oHeading->getChild();
		$aHeading = &$aResultArray[];
		$aHeading['heading']['id'] = (int)$oChild->id;
		$aHeading['heading']['text'] = (string)$oHeading->getName($this->sLanguage, false);
		$aHeading['heading']['type'] = (string)$oHeading->type;
		$aHeading['heading']['showAlways'] = (int)$bShowAlways;
		$aHeading['heading']['parentId'] = (int)$oChild->parent_id;

	}

	/**
	 * Fügt eine Question dem Result Array hinzu
	 *
	 * Hier werden alle Fragen des Fragebogens durchlaufen und dann bei jeder Frage
	 * geprüft, ob diese angezeigt werden dürfen.
	 *
	 * @param Ext_TC_Marketing_Feedback_Questionary_Child $oQuestionaryChild
	 * @param array $aResultArray
	 */
	private function addQuestion(Ext_TC_Marketing_Feedback_Questionary_Child $oQuestionaryChild, &$aResultArray) {

		$aDependencyConfiguration = $this->getDependencyConfiguration();
		$oQuestionGroup = $oQuestionaryChild->getQuestionGroup();
		$aGroupQuestions = $oQuestionGroup->getGroupQuestions();

		foreach($aGroupQuestions as $oGroupQuestion) {

			$oQuestion = $oGroupQuestion->getQuestion();

			// Nur Fragen anzeigen, bei welcher die angegebenen Abhängigkeiten übereinstimmen
			if(
				$this->aSubDependencyFilter !== null &&
				!isset($this->aSubDependencyFilter[$oQuestion->dependency_on])
			) {
				continue;
			}

			$iColumnAdded = 0;
			$aQuestion = &$aResultArray[];

			if(
				$this->oRootObject !== null &&
				isset($aDependencyConfiguration[$oQuestion->dependency_on])
			) {
				$oDependencyConfig = $aDependencyConfiguration[$oQuestion->dependency_on];
			} else {
				// Frage hat keine Abhängigkeit
				$oDependencyConfig = new Ext_TC_Marketing_Feedback_Question_ConfigDTO();
			}

			$aDependencyObjects = [];
			if($oDependencyConfig->bDependencies) {
				$aDependencyObjects = $oQuestion->dependency_objects;

				// Abhängigkeiten eingestellt, aber keine im Select: Fake-Eintrag für Schleife
				// Das wird für Lehrer benötigt, weil das Konzept hier keine Abhängigkeiten vorsah, was total bescheuert ist
				if(empty($aDependencyObjects)) {
					$aDependencyObjects[] = 0;
				}
			}

			// 1. Ebene mit Abhängigkeit (meistens Schulen, die dann eh bei checkDependency rausfliegen)
			foreach($aDependencyObjects as $iObjectId) {
				if(!$oDependencyConfig->bDependencyObject) {
					// Direkte Abhängigkeit zur WDBasic
					$oDependencyObject = $this->getWDBasic($oQuestion->dependency_on, $iObjectId);
				} else {
					// Manche Typen haben keine WDBasic oder werden hier gefaked
					$oDependencyObject = $this->getDependencyObject($oQuestion, $iObjectId);
				}

				// Gültige Abhängigkeit: Column für Abhängigkeit hinzufügen
				$bDependency = $this->checkDependency($oQuestion->dependency_on, $oDependencyObject);
				if($bDependency) {
					$this->addColumn($aQuestion, $oQuestion, $oDependencyObject);
					$iColumnAdded += 1;
				}
			}

			$aSubDependencyObjects = [];
			if($oDependencyConfig->bSubDependencies) {
				$aSubDependencyObjects = $this->getSubDependencies($oQuestion);
			}

			// 2. Ebene mit Abhängigkeit (in der Regel die korrekten Abhängigkeiten)
			// TODO Das ist doch total langsam, da hier bei 100 ausgewählten Einträgen 100 mal getInstance() aufgerufen wird
			foreach($aSubDependencyObjects as $iSubObjectId) {
				$oSubDependencyObject = $this->getSubWDBasic($oQuestion->dependency_on, $iSubObjectId);
				$bSubDependency = $this->checkSubDependency($oQuestion->dependency_on, $oSubDependencyObject);
				if($bSubDependency) {
					$this->addColumn($aQuestion, $oQuestion, $oSubDependencyObject);
					$iColumnAdded += 1;
				}
			}

			if(
				// Frage mit Abhängigkeit hat zutreffende Abhängigkeit
				$iColumnAdded > 0 || (
					// Frage ohne Abhängigkeit
					empty($aDependencyObjects) &&
					empty($aSubDependencyObjects)
				)
			) {
				// Generieren der Fragen
				// TODO Auf DTO umstellen
				$aQuestion['questionGroupQuestionId'] = (int)$oGroupQuestion->id;
				$aQuestion['questionId'] = (int)$oQuestion->id;
				$aQuestion['questionText'] = (string)$oQuestion->getQuestion($this->sLanguage);
				$aQuestion['questionType'] = (string)$oQuestion->question_type;
				$aQuestion['questionDependencyOn'] = $oQuestion->dependency_on;
				$aQuestion['questionRequired'] = (int)$oQuestionGroup->required_questions;
				$aQuestion['questionRatingId'] = (int)$oQuestion->rating_id;
				$aQuestion['parentId'] = (int)$oQuestionaryChild->parent_id;

				$this->addSpecificQuestionData($aQuestion, $oQuestion, $oQuestion->getRating());

				// Wenn keine Abhänigkeitsspalte angelegt wurde
				// wird hier eine leere Spalte angelegt
				if(!isset($aQuestion['columns'])) {
					$this->addColumn($aQuestion, $oQuestion);
				}
			} else {
				// Bevor das Array wirklich gefüllt wird, setzt bereits $this->addColumn hier bei Abhängigkeiten ein Array rein
				unset($aQuestion['columns']);
			}

			// Löscht die aktuelle Question sofern
			// diese nicht zu dem Schüler passt
			$iLastArrayKey = count($aResultArray) - 1;
			if($aResultArray[$iLastArrayKey] === null) {
				unset($aResultArray[$iLastArrayKey]);
				$aResultArray = array_values($aResultArray);
			}
		}

	}

	/**
	 * Gibt den richtigen Transfertyp String zurück,
	 * da die Agentur nicht mit ID's arbeitet
	 *
	 * @param int $iId
	 * @return string
	 * @throws InvalidArgumentException
	 */
	protected function getJourneyTransferType($iId) {

		switch($iId) {
			case '1':
				$sRetVal = 'arrival';
				break;
			case '2':
				$sRetVal = 'departure';
				break;
			case '3':
				$sRetVal = 'both';
				break;
			case '4':
				$sRetVal = 'none';
				break;
			default:
				throw new InvalidArgumentException();
		}

		return $sRetVal;
	}

	/**
	 * Generiert ein DependencyObject anhand des Typen
	 *
	 * @param Ext_TC_Marketing_Feedback_Question $oQuestion
	 * @param int|null $iId Wenn die Abhängigkeit ein Objekt ist, dann muss dieser Parameter genutzt werden.
	 * @return stdClass
	 * @throws InvalidArgumentException
	 */
	protected function getDependencyObject(Ext_TC_Marketing_Feedback_Question $oQuestion, $iId = null) {

		switch($oQuestion->dependency_on) {
			case 'transfer':
				$oDependencyObject = new stdClass();
				$oDependencyObject->id = $iId;
				break;
			default:
				throw new InvalidArgumentException('Invalid Dependency-Object Type');
		}

		return $oDependencyObject;
	}

	/**
	 * Gibt die zugehörige WDBasic Klasse zurück anhand
	 * des übergebenen Typen und dessen Instance Id
	 *
	 * @param $sType
	 * @param $iId
	 * @return mixed
	 * @throws InvalidArgumentException
	 */
	abstract protected function getWDBasic($sType, $iId);

	/**
	 * Gibt die zugehörige WDBasic Klasse zurück anhand
	 * des übergebenen Typen und dessen Instance Id
	 *
	 * @param string $sType
	 * @param integer $iId
	 * @return mixed
	 * @throws InvalidArgumentException
	 */
	abstract protected function getSubWDBasic($sType, $iId);

	/**
	 * Überprüft ob eine Abhänigkeit übereintrifft
	 *
	 * @param string $sType
	 * @param $oDependencyObject
	 * @return bool
	 * @throws InvalidArgumentException
	 * @see \Ext_TC_Marketing_Feedback_Questionary_Generator::getDependencyObject()
	 */
	abstract protected function checkDependency($sType, $oDependencyObject);

	/**
	 * Überprüft ob eine Sub-Abhänigkeit übereintrifft
	 *
	 * @param string $sType
	 * @param $oDependencyObject
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	abstract protected function checkSubDependency($sType, $oDependencyObject);

	/**
	 * @return Ext_TC_Marketing_Feedback_Question_ConfigDTO[]
	 */
	abstract public function getDependencyConfiguration();

	/**
	 * @param Ext_TC_Marketing_Feedback_Question $oQuestion
	 * @return array
	 */
	protected function getSubDependencies(Ext_TC_Marketing_Feedback_Question $oQuestion) {
		return $oQuestion->dependency_subobjects;
	}

	/**
	 * Fügt einen Spaltentitel hinzu
	 *
	 * @param $aQuestion
	 * @param $oQuestion
	 * @param null $oDependencyObject
	 */
	protected function addColumn(&$aQuestion, $oQuestion, $oDependencyObject = null) {

		$oColumn = &$aQuestion['columns'][];
		$oColumn['title'] = $this->getColumnTitle($oQuestion->dependency_on, $oDependencyObject);
		$oColumn['dependencyId'] = $this->getColumnDependencyId($oDependencyObject);

	}

	/**
	 * Gibt eine Dependency Id zurück
	 *
	 * @param $oDependencyObject
	 * @return mixed
	 */
	protected function getColumnDependencyId($oDependencyObject) {

		$iDependencyId = 0;
		if($oDependencyObject !== null) {
			$iDependencyId = $oDependencyObject->id;
		}

		return $iDependencyId;
	}

	/**
	 * Fügt spezifische Daten anhand des Fragetyps hinzu
	 *
	 * @param $aQuestion
	 * @param Ext_TC_Marketing_Feedback_Question $oQuestion
	 * @param Ext_TC_Marketing_Feedback_Rating $oRating
	 * @internal param $aQuestionRating
	 */
	protected function addSpecificQuestionData(&$aQuestion, Ext_TC_Marketing_Feedback_Question $oQuestion, Ext_TC_Marketing_Feedback_Rating $oRating) {

		$sQuestionType = $oQuestion->question_type;

		switch($sQuestionType) {
			case 'stars':
				$aQuestion['quantityStars'] = $oQuestion->quantity_stars;
				break;
			case 'yes_no':
				$aQuestion['options'][] = array('id' => 1, 'title' => Ext_TC_L10N::t('Ja', $this->sLanguage));
				$aQuestion['options'][] = array('id' => 0, 'title' => Ext_TC_L10N::t('Nein', $this->sLanguage));
				break;
			case 'rating':
				$aRatingChilds = $oRating->getChildElements();
				foreach($aRatingChilds as $oRatingChild) {
					$aQuestion['options'][] = array(
						'id' => $oRatingChild->rating,
						'title' => $oRatingChild->getName($this->sLanguage)
					);
				}
				break;

		}

	}

	/**
	 * Gibt eine Spaltenüberschrift zurück anhand
	 * des jeweiligen Typens
	 *
	 * @param string $sType
	 * @param $oDependencyObject
	 * @return mixed
	 * @throws InvalidArgumentException
	 */
	protected function getColumnTitle($sType, $oDependencyObject) {

		// Gibt leer zurück wenn es kein DependencyObject
		// zur Vergügung steht
		if(
			$oDependencyObject === null ||
			$sType === 'booking_type'
		) {
			return '';
		}

		switch($sType) {
			case 'course':
			case 'accommodation':
			case 'school':
			case 'course_category':
			case 'meal':
			case 'accommodation_category':
			case 'accommodation_provider':
				$sRetVal = $oDependencyObject->getName($this->sLanguage);
				break;
			case 'transfer':
				$sRetVal = $this->getTransferName($oDependencyObject->id);
				break;
			case 'teacher':
			case 'teacher_course':
				/** @var $oDependencyObject Ext_Thebing_Teacher */
				$sRetVal = $oDependencyObject->getName();
				break;
			default:
				throw new InvalidArgumentException('Invalid Column-Title Type "'. $sType . '"!');
		}

		return $sRetVal;
	}

	/**
	 * Gibt den Transfernamen anhand der
	 * übergebenen Typen Id zurück
	 *
	 * @param $sTypeId
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function getTransferName($sTypeId) {

		switch($sTypeId) {
			case "1":
				$sRetVal = Ext_TC_L10N::t('Anreise', $this->sLanguage);
				break;
			case "2":
				$sRetVal = Ext_TC_L10N::t('Abreise', $this->sLanguage);
				break;
			case "3":
				$sRetVal = Ext_TC_L10N::t('An- und Abreise', $this->sLanguage);
				break;
			case "4":
				$sRetVal = Ext_TC_L10N::t('Nicht gewünscht', $this->sLanguage);
				break;
			case "5":
				$sRetVal = Ext_TC_L10N::t('Individual', $this->sLanguage);
				break;
			default:
				throw new InvalidArgumentException('Invalid Transfer-Name Type');
		}

		return $sRetVal;
	}

}