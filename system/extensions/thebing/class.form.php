<?php

use Illuminate\Support\Arr;
use Core\DTO\DateRange;

/**
 * @property int|string $id
 * @property integer $active
 * @property integer $creator_id
 * @property integer $user_id
 * @property integer $client_id
 * @property string $type Art des Formulars, siehe Ext_Thebing_Form::TYPE_* Konstanten
 * @property string $purpose
 * @property string $title
 * @property string $default_language Standardsprache des Formulars (siehe Ext_Thebing_Data::getSystemLanguages())
 * @property integer $use_css Individuelles CSS Ja/Nein (1/0)
 * @property integer $use_prices Hat immer den Wert 1 (siehe #7474)
 * @property string $inbox gewählte Inbox (siehe Ext_Thebing_Client::getInboxList())
 * @property string $individual_template_path
 * @property integer[] $schools Liste aller ausgewählten Schulen (nur IDs)
 *                              (siehe Ext_Thebing_Form::getSelectedSchools())
 * @property string[] $languages Liste aller ausgewählten Sprachen (nur Sprach-Codes)
 *                               (siehe Ext_Thebing_Form::getSelectedLanguages())
 * @property integer $ignore_cache
 * @property int $acc_depending_on_course
 */
class Ext_Thebing_Form extends Ext_Thebing_Basic {

	/**
	 * Anmeldeformular
	 *
	 * @see Ext_Thebing_Form_Gui2::getFormTypes()
	 * @var string
	 */
	const TYPE_REGISTRATION = 'registration';

	/**
	 * Neues Anmeldeformular (Frontend Combinations)
	 *
	 * @see Ext_Thebing_Form_Gui2::getFormTypes()
	 * @var string
	 */
	const TYPE_REGISTRATION_NEW = 'registration_new';

	/**
	 * Anmeldeformular V3: Vue.js
	 */
	const TYPE_REGISTRATION_V3 = 'registration_v3';

	/**
	 * Contact Portal
	 */
	const TYPE_CONTACT_PORTAL = 'student_login';

	/**
	 * Anfrageformular
	 *
	 * @see Ext_Thebing_Form_Gui2::getFormTypes()
	 * @var string
	 */
	const TYPE_ENQUIRY = 'enquiry';

	const PURPOSE_NEW = 'new';

	const PURPOSE_TEMPLATE = 'template';

	const PURPOSE_EDIT = 'edit';

	const PURPOSE_CONFIRM = 'confirm';

	protected $_sTable = 'kolumbus_forms';

	protected $_sTableAlias = 'kf';

	protected $_aJoinTables = array(
//		'schools' => array(
//			'table' => 'kolumbus_forms_schools',
//			'foreign_key_field' => 'school_id',
//			'primary_key_field' => 'form_id',
//			'cloneable' => true
//		),
//		'selected_schools' => array(
//			'table' => 'kolumbus_forms_schools',
//			'class' => 'Ext_Thebing_School',
//			'foreign_key_field' => 'school_id',
//			'primary_key_field' => 'form_id',
//			'readonly' => true, // readonly, sonst Probleme beim Speichern weil "schools" die selben Daten enthält
//			'cloneable' => false
//		),
		'languages' => array(
			'table' => 'kolumbus_forms_languages',
			'foreign_key_field' => 'language',
			'primary_key_field' => 'form_id',
			'cloneable' => true
		),
		'translations' => [
			'table' => 'kolumbus_forms_translations',
			'foreign_key_field' => ['language', 'field', 'content'],
			'primary_key_field' => 'item_id',
			'static_key_fields' => ['item' => 'form']
		],
		'school_settings' => [
			'table' => 'kolumbus_forms_schools',
			'primary_key_field' => 'form_id'
		]
	);

	protected $_aJoinedObjects = array(
		'pages' => array(
			'class' => 'Ext_Thebing_Form_Page',
			'key' => 'form_id',
			'type' => 'child',
			'check_active' => true,
			'cloneable' => true,
			'on_delete' => 'cascade',
			'orderby' => 'position',
			'orderby_type' => 'ASC'
		)
	);

	/**
	 * Kombination, damit man einen Logger hier hat
	 *
	 * @var Ext_TS_Frontend_Combination_Inquiry_Abstract
	 */
	public $oCombination;

	/**
	 * Wenn dieser Wert gesetzt wird können Blöcke generierte Daten cachen.
	 *
	 * Der Wert wird nicht in der Datenbank gespeichert und soll im jeweiligen Kontext aktiviert werden wenn
	 * Bedarf besteht.
	 *
	 * Die Abfrage ob der Cache letztendlich verwendet werden soll passiert über die Methode useCache().
	 *
	 * @see Ext_Thebing_Form::$ignore_cache
	 * @see Ext_Thebing_Form::useCache()
	 * @var null|Ext_TC_Frontend_Combination_Helper_Caching
	 */
	public $oCachingHelper = null;

	public function __get($sName) {

		if (str_starts_with($sName, 'translation_')) {
			[, $sField, $sLanguage] = explode('_', $sName, 3);
			$mValue = data_get(Arr::first($this->translations, fn(array $row) => $row['field'] === $sField && $row['language'] === $sLanguage), 'content');
		} elseif (str_starts_with($sName, 'school_settings_')) {
			[,, $iSchoolId, $sField] =  explode('_', $sName, 4);
			$mValue = data_get(Arr::first($this->school_settings, fn(array $row) => $row['school_id'] == $iSchoolId), $sField);
		} elseif($sName === 'schools') {
			$mValue = array_column($this->school_settings, 'school_id');
		} else {
			$mValue = parent::__get($sName);
		}

		return $mValue;

	}

	public function __set($sName, $mValue) {

		if (str_starts_with($sName, 'translation_')) {

			$bFound = false;
			[, $sField, $sLanguage] = explode('_', $sName, 3);

			$this->translations = array_map(function (array $row) use ($mValue, $sField, $sLanguage, &$bFound) {
				if ($row['field'] === $sField && $row['language'] === $sLanguage) {
					$row['content'] = $mValue;
					$bFound = true;
				}
				return $row;
			}, $this->translations);

			if (!$bFound) {
				$this->translations = [
					...$this->translations,
					[
						'id' => 0,
						'field' => $sField,
						'language' => $sLanguage,
						'content' => $mValue
					]
				];
			}

		} elseif(str_starts_with($sName, 'school_settings_')) {

			$bFound = false;
			[,, $iSchoolId, $sField] =  explode('_', $sName, 4);

			$this->school_settings = array_map(function (array $row) use ($mValue, $iSchoolId, $sField, &$bFound) {
				if ($row['school_id'] == $iSchoolId) {
					$row[$sField] = $mValue;
					$bFound = true;
				}
				return $row;
			}, $this->school_settings);

			// Darf nicht passieren, sonst werden Schulen unlöschbar durch $schools
			if (!$bFound) {
				//$this->school_settings = [...$this->school_settings, ['school_id' => $iSchoolId, $sField => $mValue]];
			}

		} elseif($sName === 'schools') {
			$aSchoolIds = $this->schools;
			foreach ($mValue as $iSchoolId) {
				$aRow = Arr::first($this->school_settings, fn(array $row) => $row['school_id'] == $iSchoolId);
				if (empty($aRow)) {
					$this->school_settings = [...$this->school_settings, ['school_id' => $iSchoolId]];
				}
			}

			$this->school_settings = array_filter($this->school_settings, fn(array $row) => !in_array($row['school_id'], array_diff($aSchoolIds, $mValue)));
		} else {
			parent::__set($sName, $mValue);
		}

	}

	public function manipulateSqlParts(&$aSqlParts, $sView = null) {

		parent::manipulateSqlParts($aSqlParts, $sView);

		$aSqlParts['select'] .= ",
			GROUP_CONCAT(DISTINCT `school_settings`.`school_id`) `schools`,
			GROUP_CONCAT(DISTINCT `tc_fc`.`key` SEPARATOR ', ') `combinations`,
			GROUP_CONCAT(DISTINCT `languages`.`language` SEPARATOR '{|}') `languages`
		";

		$aSqlParts['from'] .= " LEFT JOIN
			`tc_frontend_combinations_items` `tc_fci` ON
				`tc_fci`.`item` = 'form' AND
				`tc_fci`.`item_value` = `kf`.`id` LEFT JOIN
			`tc_frontend_combinations` `tc_fc` ON
				`tc_fc`.`id` = `tc_fci`.`combination_id` AND
				`tc_fc`.`active` = 1
		";

	}

	/**
	 * @return null|Ext_Thebing_Client_Inbox
	 */
	public function getInbox() {

		$oInbox = null;

		$oTempInbox = new Ext_Thebing_Client_Inbox();
		$aList = $oTempInbox->getArrayList();

		if(
			!empty($aList) &&
			$this->inbox != ''
		) {
			foreach($aList as $aData) {
				if($aData['short'] == $this->inbox) {
					$oInbox = Ext_Thebing_Client_Inbox::getInstance($aData['id']);
					break;
				}
			}
		}

		return $oInbox;

	}

	/**
	 * Gibt die Liste der ausgewählten Sprachen zurück.
	 *
	 * @see Ext_Thebing_Data::getSystemLanguages()
	 * @return string[]
	 */
	public function getSelectedLanguages() {

		$aSelectedLanguages = $this->languages;

		if(!is_array($aSelectedLanguages)) {
			$aSelectedLanguages = array();
		}

		return $aSelectedLanguages;

	}

	/**
	 * Gibt die Liste der ausgewählten Schulen zurück.
	 *
	 * @return Ext_Thebing_School[]
	 */
	public function getSelectedSchools() {

		return array_map(fn($iSchoolId) => Ext_Thebing_School::getInstance($iSchoolId), $this->schools);

	}

	/**
	 * Gibt die Liste der ausgewählten Währungen zurück.
	 *
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @return mixed[]
	 */
	public function getSelectedCurrencies($mSchool) {

		$oSchool = Ext_Thebing_School::createSchoolObjectFromArgument($mSchool);
		$aAvailableCurrencies = $oSchool->getSchoolCurrencyList();
		$aFormCurrencies = $this->_aCurrencies[$oSchool->id];
		$aSelectCurrencies = array();

		foreach($aFormCurrencies as $iFormCurrency) {
			if(isset($aAvailableCurrencies[$iFormCurrency])) {
				$aSelectCurrencies[$iFormCurrency] = $aAvailableCurrencies[$iFormCurrency];
			}
		}

		return $aSelectCurrencies;

	}

	/**
	 * Gibt die Liste aller Formularseiten zurück.
	 *
	 * @return Ext_Thebing_Form_Page[]
	 */
	public function getPages() {

		$aPages = $this->getJoinedObjectChilds('pages', true);
		return $aPages;

	}

	/**
	 * Gibt die angegebene Übersetzung zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * Die Übersetzungen sind in "kolumbus_forms_translations" gespeichert, "item" = "form".
	 *
	 * @param string $sKey
	 * @param \Tc\Service\Language\Frontend|string $mLanguage
	 * @return string
	 */
	public function getTranslation($sKey, $mLanguage = null) {

		// V2
		if(empty($mLanguage)) {
			$mLanguage = $this->default_language;
		}

		if($mLanguage instanceof \Tc\Service\Language\Frontend) {
			$oLanguage = $mLanguage;
			$sLanguage = $mLanguage->getLanguage();
		} else {
			$oLanguage = new \Tc\Service\Language\Frontend($mLanguage);
			$oLanguage->setContext(\TsRegistrationForm\Generator\CombinationGenerator::FRONTEND_CONTEXT);
			$sLanguage = $mLanguage;
		}

		$sTranslation = $this->{'translation_'.$sKey.'_'.$sLanguage};

		// Default Translation
		if (empty($sTranslation)) {
			$aFields = $this->getTranslationFields();
			if(!empty($aFields[$sKey]['frontend'])) {
				return $oLanguage->translate($aFields[$sKey]['frontend']);
			}
			return '';
		}

		return (string)$sTranslation;

	}

	/**
	 * @return array
	 */
	public function getTranslationFields() {

		return [
			'error' => ['backend' => '%s: Fehlermeldung - Pflichtfelder', 'frontend' => 'There are errors on the form. Please review your input before continuing.', 'type' => 'input'],
			'errorrequired' => ['backend' => '%s: Fehlermeldung - Pflichtfeld (einzelnes Feld)', 'frontend' => 'This field is required.', 'type' => 'input'],
			'success' => ['backend' => '%s: Bestätigungsmeldung', 'frontend' => '', 'type' => 'html'],
			'nextbtn' => ['backend' => '%s: "Weiter" Button', 'frontend' => 'Next', 'type' => 'input'],
			'prevbtn' => ['backend' => '%s:"Zurück" Button', 'frontend' => 'Back', 'type' => 'input'],
			'sendbtn' => ['backend' => '%s: "Senden" Button', 'frontend' => 'Book Now', 'type' => 'input'],
			'sendquotebtn' => ['backend' => '%s: Anfrage-Button', 'frontend' => 'Get a Quote', 'type' => 'input'],
			'defaultdd' => ['backend' => '%s: Leere Select-Optionen', 'frontend' => '', 'type' => 'input', 'required' => false],
			'paymentsuccess' => ['backend' => '%s: Zahlung Bestätigungsmeldung', 'frontend' => '', 'type' => 'html'],
			'paymenterror' => ['backend' => '%s: Zahlung Fehlermeldung (abgebrochen, fehlgeschlagen oder bereits bezahlt)', 'frontend' => '', 'type' => 'html'],
			'errorinternal' => ['backend' => '%s: Fehlermeldung - Interner Fehler', 'frontend' => 'Internal error - Please contact the Support Team!', 'type' => 'input', 'hide' => true],
			'extension' => ['backend' => '%s: Ungültiges Dateiformat {extensions}', 'frontend' => 'Invalid file type. Allowed types: {extensions}', 'type' => 'input', 'hide' => true],
			'extensionsize' => ['backend' => '%s: Maximale Dateigröße überschritten', 'frontend' => 'Maximum file size exceeded.', 'type' => 'input', 'hide' => true],
			'filebrowse' => ['backend' => '', 'frontend' => 'Browse', 'type' => 'input', 'hide' => true],
			'filechoose' => ['backend' => '', 'frontend' => 'Choose file', 'type' => 'input', 'hide' => true],
			'filedownload' => ['backend' => '', 'frontend' => 'Download', 'type' => 'input', 'hide' => true],
			'paymenterror2' => ['backend' => '', 'frontend' => 'Please select and perform a valid payment method.', 'type' => 'input', 'hide' => true],
			'paymentlocked' => ['backend' => '', 'frontend' => 'All required form fields needs to be filled out completely. Please review the form to be able to perform a payment.', 'type' => 'input', 'hide' => true],
			'paymentauthorized' => ['backend' => '', 'frontend' => 'Payment has been authorized successfully! Please proceed with your booking.', 'type' => 'input', 'hide' => true],
			'paymentoptional' => ['backend' => '', 'frontend' => 'The payment is optional in this step.', 'type' => 'input', 'hide' => true],
			'choose_one' => ['backend' => '', 'frontend' => 'Please select at least one item.', 'type' => 'input', 'hide' => true],
			'error_key' => ['backend' => '', 'frontend' => 'This form requires a valid session to be processed. Please ask the Support Team for a valid link.', 'type' => 'input', 'hide' => true],
			'error_email' => ['backend' => '', 'frontend' => 'Please enter a valid e-mail address.', 'type' => 'input', 'hide' => true],
			'error_time' => ['backend' => '', 'frontend' => 'Please enter a valid 24-hour clock format (hh:mm).', 'type' => 'input', 'hide' => true]
		];

	}

	/**
	 * Gibt die angegebene Übersetzung zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * Sollte ein Wert nicht existieren oder nicht gesetzt sein wird ein leerer String zurück gegeben.
	 *
	 * @see Ext_Thebing_Form::getTranslation()
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sKey
	 * @param string $sLanguage
	 * @return string
	 */
	public function getSchoolDependentTranslation($mSchool, $sKey, $sLanguage = null) {

		$sKey = (string)$sKey;

		$oSchool = Ext_Thebing_School::createSchoolObjectFromArgument($mSchool);
		$iSchoolID = $oSchool->id;

		$sTranslationKey = $sKey.$iSchoolID;

		return $this->getTranslation($sTranslationKey, $sLanguage);

	}

	/**
	 * Gibt die Daten-Attribute zur Verwendung im HTML zurück.
	 *
	 * @uses Ext_Thebing_Form::getFormDataAttributesArray()
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return string
	 */
	public function getFormDataAttributes($mSchool, $sLanguage = null) {

		$oSchool = Ext_Thebing_School::createSchoolObjectFromArgument($mSchool);
		$sLanguage = $this->getDynamicLanguage($sLanguage);
		$sAttributes = '';

		$aData = $this->getFormDataAttributesArray($mSchool, $sLanguage);
		if(count($aData) > 0) {
			$sData = htmlentities(json_encode($aData, JSON_FORCE_OBJECT));
			$sAttributes .= ' data-dynamic-config="'.$sData.'" ';
		}

		$sAttributes .= ' data-dynamic-form="'.$this->id.'" ';
		$sAttributes .= ' data-dynamic-date-format="'.htmlentities($oSchool->date_format_long).'" ';
		$sAttributes .= ' data-validateable="form" ';
		$sAttributes .= ' data-message-error-internal="'.htmlentities($this->getTranslation('errorinternal')).'"';
		$sAttributes .= ' data-datetime-loaded="'.gmdate('c').'"'; // Eingebaut für cachende Systeme, zur Info für uns

		$sAttributes = trim($sAttributes);

		if(strlen($sAttributes) > 0) {
			$sAttributes = ' '.$sAttributes.' ';
		}

		return $sAttributes;

	}

	/**
	 * Gibt die Daten-Attribute als Array zurück.
	 *
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return mixed[]
	 */
	public function getFormDataAttributesArray($mSchool, $sLanguage = null) {

		return array();

	}

	/**
	 * Gibt eine Liste mit allen Blöcken des Formular zurück.
	 *
	 * Die Liste wird durch die angegebene Callback-Funktion gefiltert, wenn die Funktion true zurück gibt wird
	 * der Block in die zurückgegebene Liste aufgenommen, ansonsten nicht.
	 *
	 * Es werden rekursiv auch alle Kind-Blöcke von Blöcken durchlaufen.
	 *
	 * @param Closure|int $filter
	 * @return Ext_Thebing_Form_Page_Block[]
	 */
	public function getFilteredBlocks(Closure|int $filter) {

		$aBlocks = array();

		if (is_int($filter)) {
			$filter = fn(Ext_Thebing_Form_Page_Block $oBlock) => (int)$oBlock->block_id === $filter;
		}

		$oCallbackWalkBlocks = function($aCurrentBlocks) use(&$oCallbackWalkBlocks, &$aBlocks, &$filter) {
			foreach($aCurrentBlocks as $oBlock) {
				if($filter($oBlock)) {
					$aBlocks[] = $oBlock;
				}
				$aChildBlocks = $oBlock->getChildBlocks();
				$oCallbackWalkBlocks($aChildBlocks);
			}
		};

		foreach($this->getPages() as $oPage) {
			$aPageBlocks = $oPage->getBlocks();
			$oCallbackWalkBlocks($aPageBlocks);
		}

		return $aBlocks;

	}

	/**
	 * @TODO Migrieren auf getFilteredBlocks()
	 * @deprecated
	 */
	public function createFilteredBlocksCallbackType($iType) {

		return function (Ext_Thebing_Form_Page_Block $oBlock) use ($iType) {
			return $oBlock->block_id == $iType;
		};

	}

	/**
	 * Liefert anhand des Typs einen der Blöcke, die im Formular nur einmal vorkommen können
	 *
	 * @param int $iType
	 * @return Ext_Thebing_Form_Page_Block|null
	 * @see Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INSURANCES
	 * @see Ext_Thebing_Form_Page_Block::TYPE_FEES
	 * @see Ext_Thebing_Form_Page_Block::TYPE_COURSES
	 * @see Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS
	 */
	public function getFixedBlock(int $iType, $bThrowException = true) {

		if($iType == Ext_Thebing_Form_Page_Block::TYPE_PRICES) {
			throw new InvalidArgumentException('There can be more than one price block');
		}

		$aBlocks = $this->getFilteredBlocks($iType);

		if(empty($aBlocks)) {
			return null;
		}

		if($bThrowException && count($aBlocks) > 1) {
			throw new RuntimeException('getFixedBlock('.$iType.') returned more than one block!');
		}

		return reset($aBlocks);

	}

	/**
	 * Liefert den ersten virtuellen Kind-Block aus einem der fixierten Blöcke (Kurse usw.)
	 *
	 * Das ist notwendig für Fehlermeldungen, da die Container keine Fehler anzeigen können.
	 * Diese Methode hier überspringt dabei bereits den virtuellen Container,
	 * wo die ganzen virtuellen Blöcke wiederum drin liegen.
	 *
	 * @param Ext_Thebing_Form_Page_Block $oFixedBlock
	 * @return Ext_Thebing_Form_Page_Block_Virtual_Abstract
	 */
	public function getFirstChildBlockOfFixedBlock(Ext_Thebing_Form_Page_Block $oFixedBlock) {

		$aChildBlocks = $oFixedBlock->getChildBlocks();
		$aChildBlocks = reset($aChildBlocks)->getChildBlocks();
		$oBlock = reset($aChildBlocks);

		if(empty($oBlock)) {
			throw new RuntimeException('Could not find corresponding child block (for errors) for block '.$oFixedBlock->id);
		}

		return $oBlock;

	}

	/**
	 * Gibt die Liste mit aktuell vom Benutzer ausgewählten Kursen zurück.
	 *
	 * Zur Verwendung während Submit/Ajax-Requests.
	 *
	 * Nicht vollständig ausgewählte Kurse sowie ungültige Einträge (z.B. wenn eine Auswahl gar nicht zur
	 * angegebenen Schule gehört) werden ignoriert.
	 *
	 * @param MVC_Request $oRequest
	 * @param Ext_Thebing_School $oSchool
	 * @param Ext_TS_Inquiry_Journey $oJourney Kurse mit übergebener Journey verknüpfen
	 * @return Ext_TS_Inquiry_Journey_Course[]
	 */
	public function getSelectedCourses(MVC_Request $oRequest, Ext_Thebing_School $oSchool, Ext_TS_Inquiry_Journey $oJourney = null) {

		$aBlocks = $this->getFilteredBlocks(function(Ext_Thebing_Form_Page_Block $oBlock) {
			return $oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_COURSES;
		});

		/** @var Ext_TS_Inquiry_Journey_Course[] $aSelectedCourses */
		$aSelectedCourses = [];
		$aServiceTimes = collect();

		foreach($aBlocks as $oBlock) {

			if(
				!$oRequest->exists($oBlock->getInputDataIdentifier()) ||
				!is_array($oRequest->input($oBlock->getInputDataIdentifier()))
			) {
				continue;
			}

			foreach($oRequest->input($oBlock->getInputDataIdentifier()) as $aCourse) {

				if(
					!isset($aCourse[Ext_Thebing_Form_Page_Block_Virtual_Courses_Coursetype::SUBTYPE]) ||
					//!isset($aCourse[Ext_Thebing_Form_Page_Block_Virtual_Courses_Level::SUBTYPE]) ||
					!isset($aCourse[Ext_Thebing_Form_Page_Block_Virtual_Courses_Startdate::SUBTYPE]) ||
					!isset($aCourse[Ext_Thebing_Form_Page_Block_Virtual_Courses_Duration::SUBTYPE]) ||
					!isset($aCourse[Ext_Thebing_Form_Page_Block_Virtual_Courses_Enddate::SUBTYPE])
				) {
					continue;
				}

				$iCourse = (int)$aCourse[Ext_Thebing_Form_Page_Block_Virtual_Courses_Coursetype::SUBTYPE];
				$iLevel = (int)$aCourse[Ext_Thebing_Form_Page_Block_Virtual_Courses_Level::SUBTYPE];
				$sStartDate = $aCourse[Ext_Thebing_Form_Page_Block_Virtual_Courses_Startdate::SUBTYPE];
				$iDuration = $aCourse[Ext_Thebing_Form_Page_Block_Virtual_Courses_Duration::SUBTYPE];
				$sEndDate = $aCourse[Ext_Thebing_Form_Page_Block_Virtual_Courses_Enddate::SUBTYPE];
				$oCourse = Ext_Thebing_Tuition_Course::getInstance($iCourse);
				$oLevel = Ext_Thebing_Tuition_Level::getInstance($iLevel);
				$dStartDate = null;
				if(strlen(trim($sStartDate)) > 0) {
					$dStartDate = DateTime::createFromFormat('Ymd', $sStartDate);
				}
				$dEndDate = null;
				if(strlen(trim($sEndDate)) > 0) {
					$dEndDate = DateTime::createFromFormat('Ymd', $sEndDate);
				}

				$iUnits = 0;
				if(isset($aCourse[Ext_Thebing_Form_Page_Block_Virtual_Courses_Units::SUBTYPE])) {
					$iUnits = $aCourse[Ext_Thebing_Form_Page_Block_Virtual_Courses_Units::SUBTYPE];
				}
				if(
					$oCourse->getType() === 'unit' &&
					$iUnits < Ext_Thebing_Form_Page_Block_Virtual_Courses_Units::UNITS_MIN ||
					$iUnits > Ext_Thebing_Form_Page_Block_Virtual_Courses_Units::UNITS_MAX
				) {
					continue;
				}

				// zurücksetzen damit ein möglicherweise in einem versteckten Feld gesendeter Wert ignoriert wird
				if($oCourse->getType() !== 'unit') {
					$iUnits = 0;
				}

				if(
					$oCourse->id < 1 ||
					$oCourse->school_id != $oSchool->id || (
						$iLevel > 1 &&
						!in_array($oSchool->id, $oLevel->schools)
					) ||
					!($dStartDate instanceof DateTime) ||
					!($dEndDate instanceof DateTime)
				) {
					continue;
				}

				$oJourneyCourse = new Ext_TS_Inquiry_Journey_Course();
				$oJourneyCourse->course_id = $oCourse->id;
				$oJourneyCourse->program_id = $oCourse->getFirstProgram()->id;
				$oJourneyCourse->level_id = $oLevel->id;
				$oJourneyCourse->units = $iUnits;
				$oJourneyCourse->weeks = $iDuration;
				$oJourneyCourse->from = $dStartDate->format('Y-m-d');
				$oJourneyCourse->until = $dEndDate->format('Y-m-d');
				$oJourneyCourse->calculate = 1;
				$oJourneyCourse->visible = 1;
				$oJourneyCourse->for_tuition = 1;

				$aServiceTimes->push($dStartDate);
				$aServiceTimes->push($dEndDate);

				if($oJourney instanceof Ext_TS_Inquiry_Journey) {
					$oJourney->setJoinedObjectChild('courses', $oJourneyCourse);
				}

				$aSelectedCourses[] = $oJourneyCourse;

			}

		}

		// Prüfen, ob zwischen zwei Kurse Schulferien liegen und diese in der Kursstruktur verknüpfen
		if(
			$oJourney instanceof Ext_TS_Inquiry_Journey &&
			$aServiceTimes->isNotEmpty()
		) {
			$helper = new \TsRegistrationForm\Helper\BuildInquiryHelper($oRequest->attributes->get('combination'));
			$helper->mergeCourseHolidays($oJourney->getInquiry(), new DateRange($aServiceTimes->min(), $aServiceTimes->max()));
		}

		return $aSelectedCourses;

	}

	/**
	 * Gibt die Liste mit aktuell vom Benutzer ausgewählten Unterkünften zurück.
	 *
	 * Zur Verwendung während Submit/Ajax-Requests.
	 *
	 * Nicht vollständig ausgewählte Unterkünfte sowie ungültige Einträge (z.B. wenn eine Auswahl gar nicht zur
	 * angegebenen Schule gehört) werden ignoriert.
	 *
	 * @param MVC_Request $oRequest
	 * @param Ext_Thebing_School $oSchool
	 * @param Ext_TS_Inquiry_Journey $oJourney
	 * @return Ext_TS_Inquiry_Journey_Accommodation[]
	 */
	public function getSelectedAccommodations(MVC_Request $oRequest, Ext_Thebing_School $oSchool, Ext_TS_Inquiry_Journey $oJourney = null) {

		$aBlocks = $this->getFilteredBlocks(function(Ext_Thebing_Form_Page_Block $oBlock) {
			return ($oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS);
		});

		$aSelectedAccommodations = [];

		foreach($aBlocks as $oBlock) {

			if(
				!$oRequest->exists($oBlock->getInputDataIdentifier()) ||
				!is_array($oRequest->input($oBlock->getInputDataIdentifier()))
			) {
				continue;
			}

			foreach($oRequest->input($oBlock->getInputDataIdentifier()) as $aAccommodation) {

				if(
					!isset($aAccommodation[Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Accommodationtype::SUBTYPE]) ||
					!isset($aAccommodation[Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Roomtype::SUBTYPE]) ||
					!isset($aAccommodation[Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Meals::SUBTYPE]) ||
					!isset($aAccommodation[Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Startdate::SUBTYPE]) ||
					!isset($aAccommodation[Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Duration::SUBTYPE]) ||
					!isset($aAccommodation[Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Enddate::SUBTYPE])
				) {
					continue;
				}

				$iAccommodation = $aAccommodation[Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Accommodationtype::SUBTYPE];
				$iRoomType = $aAccommodation[Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Roomtype::SUBTYPE];
				$iMeals = $aAccommodation[Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Meals::SUBTYPE];
				$sStartDate = $aAccommodation[Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Startdate::SUBTYPE];
				$iDuration = $aAccommodation[Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Duration::SUBTYPE];
				$sEndDate = $aAccommodation[Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Enddate::SUBTYPE];
				$oAccommodation = Ext_Thebing_Accommodation_Category::getInstance($iAccommodation);
				$oRoomType = Ext_Thebing_Accommodation_Roomtype::getInstance($iRoomType);
				$oMealType = Ext_Thebing_Accommodation_Meal::getInstance($iMeals);
				$dStartDate = null;
				if(strlen(trim($sStartDate)) > 0) {
					$dStartDate = DateTime::createFromFormat('Ymd', $sStartDate);
				}
				$dEndDate = null;
				if(strlen(trim($sEndDate)) > 0) {
					$dEndDate = DateTime::createFromFormat('Ymd', $sEndDate);
				}

				if(
					!$oAccommodation->exist() ||
					!$oAccommodation->belongsToSchool($oSchool) ||
					!$oRoomType->exist() ||
					!$oRoomType->belongsToSchool($oSchool) ||
					!$oMealType->exist() ||
					!$oMealType->belongsToSchool($oSchool) ||
					!($dStartDate instanceof DateTime) ||
					!($dEndDate instanceof DateTime)
				) {
					continue;
				}

				$oJourneyAccommodation = new Ext_TS_Inquiry_Journey_Accommodation();
				$oJourneyAccommodation->accommodation_id = $oAccommodation->id;
				$oJourneyAccommodation->roomtype_id = $oRoomType->id;
				$oJourneyAccommodation->meal_id = $oMealType->id;
				$oJourneyAccommodation->weeks = $iDuration;
				$oJourneyAccommodation->from = $dStartDate->format('Y-m-d');
				$oJourneyAccommodation->until = $dEndDate->format('Y-m-d');
				$oJourneyAccommodation->calculate = 1;
				$oJourneyAccommodation->visible = 1;
				$oJourneyAccommodation->for_matching = 1;

				if($oJourney instanceof Ext_TS_Inquiry_Journey) {
					$oJourney->setJoinedObjectChild('accommodations', $oJourneyAccommodation);
				}

				$aSelectedAccommodations[] = $oJourneyAccommodation;

			}

		}

		return $aSelectedAccommodations;

	}

	/**
	 * Gibt die Liste mit aktuell vom Benutzer ausgewählten Versicherungen zurück.
	 *
	 * Zur Verwendung während Submit/Ajax-Requests.
	 *
	 * Nicht vollständig ausgewählte Transfers sowie ungültige Einträge (z.B. wenn eine Auswahl gar nicht zur
	 * angegebenen Schule gehört) werden ignoriert.
	 *
	 * @param MVC_Request $oRequest
	 * @param Ext_Thebing_School $oSchool
	 * @param Ext_TS_Inquiry_Journey $oJourney
	 * @return Ext_TS_Inquiry_Journey_Transfer[]
	 */
	public function getSelectedTransfers(MVC_Request $oRequest, Ext_Thebing_School $oSchool, Ext_TS_Inquiry_Journey $oJourney = null) {

		$aBlocks = $this->getFilteredBlocks(function(Ext_Thebing_Form_Page_Block $oBlock) {
			return ($oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS);
		});

		$oDateFormat = new Ext_Thebing_Gui2_Format_Date('frontend_date_format', $oSchool->id);
		$aSelectedTransfers = array();

		foreach($aBlocks as $oBlock) {

			$sTransferType = null;
			$sArrivalFrom = null;
			$sArrivalTo = null;
			$sArrivalDate = null;
			$dArrivalDate = null;
			$sArrivalAirline = null;
			$sArrivalFlightnumber = null;
			$sArrivalTime = null;
			$sArrivalNotice = null;
			$sDepartureFrom = null;
			$sDepartureTo = null;
			$sDepartureDate = null;
			$dDepartureDate = null;
			$sDepartureAirline = null;
			$sDepartureFlightnumber = null;
			$sDepartureTime = null;
			$sDepartureNotice = null;

			$aChildBlocks = $oBlock->getFilteredChildBlocks(function() { return true; });
			foreach($aChildBlocks as $oChildBlock) {

				if($oChildBlock instanceof Ext_Thebing_Form_Page_Block_Virtual_Transfers_Transfertype) {
					$sTransferType = $oChildBlock->getFormInputValue($oRequest);
				}

				if($oChildBlock instanceof Ext_Thebing_Form_Page_Block_Virtual_Transfers_Arrival_Transferfrom) {
					$sArrivalFrom = $oChildBlock->getFormInputValue($oRequest);
				}

				if($oChildBlock instanceof Ext_Thebing_Form_Page_Block_Virtual_Transfers_Arrival_Transferto) {
					$sArrivalTo = $oChildBlock->getFormInputValue($oRequest);
				}

				if($oChildBlock instanceof Ext_Thebing_Form_Page_Block_Virtual_Transfers_Arrival_Date) {
					$sArrivalDate = $oChildBlock->getFormInputValue($oRequest);
					if(strlen(trim($sArrivalDate)) > 0) {
						$dArrivalDate = DateTime::createFromFormat('Y-m-d', $oDateFormat->convert($sArrivalDate));
					}
				}

				if($oChildBlock instanceof Ext_Thebing_Form_Page_Block_Virtual_Transfers_Arrival_Airline) {
					$sArrivalAirline = $oChildBlock->getFormInputValue($oRequest);
				}

				if($oChildBlock instanceof Ext_Thebing_Form_Page_Block_Virtual_Transfers_Arrival_Flightnumber) {
					$sArrivalFlightnumber = $oChildBlock->getFormInputValue($oRequest);
				}

				if($oChildBlock instanceof Ext_Thebing_Form_Page_Block_Virtual_Transfers_Arrival_Time) {
					$sArrivalTime = $oChildBlock->getFormInputValue($oRequest);
				}

				if($oChildBlock instanceof Ext_Thebing_Form_Page_Block_Virtual_Transfers_Arrival_Notice) {
					$sArrivalNotice = $oChildBlock->getFormInputValue($oRequest);
				}

				if($oChildBlock instanceof Ext_Thebing_Form_Page_Block_Virtual_Transfers_Departure_Transferfrom) {
					$sDepartureFrom = $oChildBlock->getFormInputValue($oRequest);
				}

				if($oChildBlock instanceof Ext_Thebing_Form_Page_Block_Virtual_Transfers_Departure_Transferto) {
					$sDepartureTo = $oChildBlock->getFormInputValue($oRequest);
				}

				if($oChildBlock instanceof Ext_Thebing_Form_Page_Block_Virtual_Transfers_Departure_Date) {
					$sDepartureDate = $oChildBlock->getFormInputValue($oRequest);
					if(strlen(trim($sDepartureDate)) > 0) {
						$dDepartureDate = DateTime::createFromFormat('Y-m-d', $oDateFormat->convert($sDepartureDate));
					}
				}

				if($oChildBlock instanceof Ext_Thebing_Form_Page_Block_Virtual_Transfers_Departure_Airline) {
					$sDepartureAirline = $oChildBlock->getFormInputValue($oRequest);
				}

				if($oChildBlock instanceof Ext_Thebing_Form_Page_Block_Virtual_Transfers_Departure_Flightnumber) {
					$sDepartureFlightnumber = $oChildBlock->getFormInputValue($oRequest);
				}

				if($oChildBlock instanceof Ext_Thebing_Form_Page_Block_Virtual_Transfers_Departure_Time) {
					$sDepartureTime = $oChildBlock->getFormInputValue($oRequest);
				}

				if($oChildBlock instanceof Ext_Thebing_Form_Page_Block_Virtual_Transfers_Departure_Notice) {
					$sDepartureNotice = $oChildBlock->getFormInputValue($oRequest);
				}

			}

			// Wenn An- und Abreise ausgewählt ist muss auch beides angegeben werden
			if(
				$sTransferType == 'arr_dep' &&
				(
					$sArrivalFrom === null ||
					$sArrivalTo === null ||
					!($dArrivalDate instanceof DateTime) ||
					$sDepartureFrom === null ||
					$sDepartureTo === null ||
					!($dDepartureDate instanceof DateTime)
				)
			) {
				continue;
			}

			if(
				in_array($sTransferType, ['arrival', 'arr_dep']) &&
				$sArrivalFrom !== null &&
				$sArrivalTo !== null &&
				($dArrivalDate instanceof DateTime)
			) {

				$aFrom = explode('_', $sArrivalFrom);
				$aTo = explode('_', $sArrivalTo);

				$oJourneyTransfer = new Ext_TS_Inquiry_Journey_Transfer();
				$oJourneyTransfer->start = $aFrom[1];
				$oJourneyTransfer->end = $aTo[1];
				$oJourneyTransfer->start_type = $aFrom[0];
				$oJourneyTransfer->end_type = $aTo[0];
				$oJourneyTransfer->transfer_type = Ext_TS_Inquiry_Journey_Transfer::TYPE_ARRIVAL;
				$oJourneyTransfer->transfer_date = $dArrivalDate->format('Y-m-d');
				$oJourneyTransfer->transfer_time = $sArrivalTime; // TODO NULL?
				$oJourneyTransfer->airline = $sArrivalAirline;
				$oJourneyTransfer->flightnumber = $sArrivalFlightnumber;
				$oJourneyTransfer->comment = $sArrivalNotice;
				$oJourneyTransfer->booked = 1;

				if($oJourney instanceof Ext_TS_Inquiry_Journey) {
					$oJourney->setJoinedObjectChild('transfers', $oJourneyTransfer);
				}

				$aSelectedTransfers[] = $oJourneyTransfer;

			}

			if(
				in_array($sTransferType, ['departure', 'arr_dep']) &&
				$sDepartureFrom !== null &&
				$sDepartureTo !== null &&
				($dDepartureDate instanceof DateTime)
			) {

				$aFrom = explode('_', $sDepartureFrom);
				$aTo = explode('_', $sDepartureTo);

				$oJourneyTransfer = new Ext_TS_Inquiry_Journey_Transfer();
				$oJourneyTransfer->start = $aFrom[1];
				$oJourneyTransfer->end = $aTo[1];
				$oJourneyTransfer->start_type = $aFrom[0];
				$oJourneyTransfer->end_type = $aTo[0];
				$oJourneyTransfer->transfer_type = Ext_TS_Inquiry_Journey_Transfer::TYPE_DEPARTURE;
				$oJourneyTransfer->transfer_date = $dDepartureDate->format('Y-m-d');
				$oJourneyTransfer->transfer_time = $sDepartureTime; // TODO NULL?
				$oJourneyTransfer->airline = $sDepartureAirline;
				$oJourneyTransfer->flightnumber = $sDepartureFlightnumber;
				$oJourneyTransfer->comment = $sDepartureNotice;
				$oJourneyTransfer->booked = 1;

				if($oJourney instanceof Ext_TS_Inquiry_Journey) {
					$oJourney->setJoinedObjectChild('transfers', $oJourneyTransfer);
				}

				$aSelectedTransfers[] = $oJourneyTransfer;

			}

		}

		return $aSelectedTransfers;

	}

	/**
	 * Gibt die Liste mit aktuell vom Benutzer ausgewählten Versicherungen zurück.
	 *
	 * Zur Verwendung während Submit/Ajax-Requests.
	 *
	 * Nicht vollständig ausgewählte Versicherungen sowie ungültige Einträge (z.B. wenn eine Auswahl gar nicht zur
	 * angegebenen Schule gehört) werden ignoriert.
	 *
	 * Format eines Eintrags:
	 *
	 * array(
	 *     'insurance' => <Ext_Thebing_Insurance>,
	 *     'start_date' => <DateTime>,
	 *     'duration' => <integer>,
	 *     'end_date' => <DateTime>
	 * )
	 *
	 * @param MVC_Request $oRequest
	 * @param Ext_Thebing_School $oSchool
	 * @param Ext_TS_Inquiry_Journey $oJourney
	 * @return Ext_TS_Inquiry_Journey_Insurance[]
	 */
	public function getSelectedInsurances(MVC_Request $oRequest, Ext_Thebing_School $oSchool, Ext_TS_Inquiry_Journey $oJourney = null) {

		$aBlocks = $this->getFilteredBlocks(function(Ext_Thebing_Form_Page_Block $oBlock) {
			return ($oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INSURANCES);
		});

		$oDateFormat = new Ext_Thebing_Gui2_Format_Date('frontend_date_format', $oSchool->id);
		$aSelectedInsurances = array();

		foreach($aBlocks as $oBlock) {

			if(
				!$oRequest->exists($oBlock->getInputDataIdentifier()) ||
				!is_array($oRequest->input($oBlock->getInputDataIdentifier()))
			) {
				continue;
			}

			foreach($oRequest->input($oBlock->getInputDataIdentifier()) as $aInsurance) {

				if(
					!isset($aInsurance[Ext_Thebing_Form_Page_Block_Virtual_Insurances_Insurancetype::SUBTYPE]) ||
					!isset($aInsurance[Ext_Thebing_Form_Page_Block_Virtual_Insurances_Startdate::SUBTYPE]) ||
					!isset($aInsurance[Ext_Thebing_Form_Page_Block_Virtual_Insurances_Enddate::SUBTYPE])
				) {
					continue;
				}

				$iInsurance = $aInsurance[Ext_Thebing_Form_Page_Block_Virtual_Insurances_Insurancetype::SUBTYPE];
				$sStartDate = $aInsurance[Ext_Thebing_Form_Page_Block_Virtual_Insurances_Startdate::SUBTYPE];
				$oInsurance = Ext_Thebing_Insurance::getInstance($iInsurance);
				$dStartDate = null;
				if(strlen($sStartDate) > 0) {
					$dStartDate = DateTime::createFromFormat('Y-m-d', $oDateFormat->convert($sStartDate));
				}

				if(
					$oInsurance->id < 1 ||
					!($dStartDate instanceof DateTime)
				) {
					continue;
				}

				$iDuration = 0;
				$dEndDate = null;

				if($oInsurance->isWeekInsurance()) {
					if(!isset($aInsurance[Ext_Thebing_Form_Page_Block_Virtual_Insurances_Duration::SUBTYPE])) {
						continue;
					}
					$iDuration = (int)$aInsurance[Ext_Thebing_Form_Page_Block_Virtual_Insurances_Duration::SUBTYPE];
					if($iDuration <= 0) {
						continue;
					}
					$dEndDate = clone $dStartDate;
					$dEndDate->add(new \DateInterval('P'.(($iDuration * 7) - 1).'D'));
				} else {
					if(!isset($aInsurance[Ext_Thebing_Form_Page_Block_Virtual_Insurances_Enddate::SUBTYPE])) {
						continue;
					}
					$sEndDate = $aInsurance[Ext_Thebing_Form_Page_Block_Virtual_Insurances_Enddate::SUBTYPE];
					if(strlen($sEndDate) > 0) {
						$dEndDate = DateTime::createFromFormat('Y-m-d', $oDateFormat->convert($sEndDate));
					}
				}

				if(!($dEndDate instanceof DateTime)) {
					continue;
				}

				$oJourneyInsurance = new Ext_TS_Inquiry_Journey_Insurance();
				$oJourneyInsurance->insurance_id = $oInsurance->id;
				$oJourneyInsurance->from = $dStartDate->format('Y-m-d');
				$oJourneyInsurance->weeks = $iDuration;
				$oJourneyInsurance->until = $dEndDate->format('Y-m-d');
				$oJourneyInsurance->visible = 1;

				if($oJourney instanceof Ext_TS_Inquiry_Journey) {
					$oJourney->setJoinedObjectChild('insurances', $oJourneyInsurance);
				}

				$aSelectedInsurances[] = $oJourneyInsurance;

			}

		}

		return $aSelectedInsurances;

	}

	/**
	 * Gibt die aktuell vom Benutzer ausgewählte Währung zurück.
	 *
	 * Zur Verwendung während Submit/Ajax-Requests.
	 *
	 * @TODO Hier müsste eingebaut werden, dass geschaut wird, ob die Währung auch im Formular eingestellt ist
	 *
	 * @param MVC_Request $oRequest
	 * @return null|Ext_Thebing_Currency
	 */
	/*public function getSelectedCurrency(MVC_Request $oRequest) {

		$aBlocks = $this->getFilteredBlocks(function(Ext_Thebing_Form_Page_Block $oBlock) {
			return ($oBlock instanceof Ext_Thebing_Form_Page_Block_Virtual_Prices_Currency);
		});

		$oSelectedCurrency = null;

		foreach($aBlocks as $oBlock) {

			if(!$oRequest->exists($oBlock->getInputBlockName())) {
				continue;
			}

			$oCurrency = Ext_Thebing_Currency::getInstance((int)$oRequest->input($oBlock->getInputBlockName()));
			if($oCurrency->id > 0) {
				$oSelectedCurrency = $oCurrency;
				break;
			}

		}

		return $oSelectedCurrency;

	}*/

	/**
	 * Gibt die aktuell vom Benutzer ausgewählten zusätzlichen generellen Gebühren zurück
	 *
	 * @param MVC_Request $oRequest
	 * @param Ext_Thebing_School $oSchool
	 * @return Ext_Thebing_School_Additionalcost[]
	 */
	public function getSelectedFees(MVC_Request $oRequest, Ext_Thebing_School $oSchool) {

		$aCosts = [];
		$oBlock = $this->getFixedBlock(Ext_Thebing_Form_Page_Block::TYPE_FEES);

		if($oBlock === null) {
			return [];
		}

		if(
			!$oRequest->exists($oBlock->getInputDataIdentifier()) ||
			!is_array($oRequest->input($oBlock->getInputDataIdentifier()))
		) {
			return [];
		}

		foreach($oRequest->input($oBlock->getInputDataIdentifier()) as $aCost) {

			if(!isset($aCost[Ext_Thebing_Form_Page_Block_Virtual_Costs_Cost::SUBTYPE])) {
				continue;
			}

			$iCostId = (int)$aCost[Ext_Thebing_Form_Page_Block_Virtual_Costs_Cost::SUBTYPE];
			$oCost = Ext_Thebing_School_Additionalcost::getInstance($iCostId);

			if(!$oCost->exist()) {
				continue;
			}

			if($oCost->idSchool != $oSchool->id) {
				throw new RuntimeException('Cost "'.$oCost->id.'" does not belong to school "'.$oSchool->id.'"');
			}

			$aCosts[] = $oCost;

		}

		return $aCosts;

	}

	/**
	 * Gibt eine Liste mit den Dateien aller Download-Blöcke zurück.
	 *
	 * Es werden nur Download-Blöcke berücksichtigt bei denen die Datei für die angegebene Schule und die angegebene
	 * Sprache verfügbar ist.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * Das Rückgabe-Array enthält als Keys den Download-Key und als Value jeweils den Pfad zur Datei ausgehend vom
	 * Document-Root. Das Array kann direkt in den Kombinationen für die Methode "getTemplateFiles()" genutzt werden.
	 *
	 * @uses Ext_Thebing_Form_Page_Block::isAvailable()
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return mixed[]
	 */
	public function getDownloadFileList($mSchool, $sLanguage = null) {

		$oSchool = Ext_Thebing_School::createSchoolObjectFromArgument($mSchool);
		$sLanguage = (string)$sLanguage;
		$aFiles = array();

		$aBlocks = $this->getFilteredBlocks(
			function(Ext_Thebing_Form_Page_Block $oBlock) use($oSchool, $sLanguage) {
				return (
					$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_DOWNLOAD &&
					$oBlock->isAvailable($oSchool, $sLanguage)
				);
			}
		);

		foreach($aBlocks as $oBlock) {

			$aSettings = $oBlock->getSettings();
			if(!isset($aSettings['file_'.$sLanguage])) {
				continue;
			}

			$iFileId = $aSettings['file_'.$sLanguage];
			$oFile = Ext_Thebing_Upload_File::getInstance($iFileId);
			if($oFile->id < 1) {
				continue;
			}

			$sName = $oBlock->getInputBlockName();
			$aFiles[$sName] = $oFile->getPath(false);

		}

		return $aFiles;

	}

	/**
	 * Gibt einen gültigen Sprach-Code zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * @param string $sLanguage
	 * @return string
	 */
	protected function getDynamicLanguage($sLanguage) {

		$sLanguage = (string)$sLanguage;

		if(strlen($sLanguage) < 1) {
			$sLanguage = $this->default_language;
		}

		return $sLanguage;

	}

	/**
	 * Gibt alle Blöcke des Formulars zurück die beim Absenden des Formulars validiert werden müssen.
	 *
	 * @see Ext_Thebing_Form_Page_Block::validateFormInput()
	 * @return Ext_Thebing_Form_Page_Block[]
	 */
	public function getValidateableBlocks() {

		$aBlocks = $this->getFilteredBlocks(function(Ext_Thebing_Form_Page_Block $oBlock) {
			return $oBlock->canValidate();
		});

		return $aBlocks;

	}

	/**
	 * Gibt alle Blöcke zurück bei denen Benutzereingaben erfolgen.
	 *
	 * @return Ext_Thebing_Form_Page_Block[]
	 */
	public function getInputBlocks() {

		$aBlocks = $this->getFilteredBlocks(function(Ext_Thebing_Form_Page_Block $oBlock) {
			return $oBlock->isInputBlock();
		});

		return $aBlocks;

	}

	/**
	 * Validiert die Formulareingaben für das gesamte Formular.
	 *
	 * Zur Verwendung während Submit/Ajax-Requests.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * Format des Rückgabe-Arrays:
	 *
	 * array(
	 *     'block_errors' => array(
	 *         <string (block-identifier)> => array(
	 *             'value' => <string (value)>
	 *             'message' => <string (nachricht)>
	 *         )
	 *     ),
	 *     'form_errors' => array(
	 *         <string (nachricht)>
	 *     )
	 * )
	 *
	 * @param MVC_Request $oRequest
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return mixed[]
	 */
	public function validateFormInput(MVC_Request $oRequest, $mSchool, $sLanguage = null) {

		$aBlocks = $this->getFilteredBlocks(function(Ext_Thebing_Form_Page_Block $oBlock) {
			return $oBlock->canValidate();
		});
		$aResult = array(
			'block_errors' => array(),
			'form_errors' => array()
		);

		foreach($aBlocks as $oBlock) {

			$aBlockValidationResult = $oBlock->validateFormInput($oRequest, $mSchool, $sLanguage);

			if(isset($aBlockValidationResult['block_errors'])) {
				foreach($aBlockValidationResult['block_errors'] as $sIdentifier => $aErrorData) {
					$aResult['block_errors'][$sIdentifier] = $aErrorData;
				}
			}

			if(isset($aBlockValidationResult['form_errors'])) {
				foreach($aBlockValidationResult['form_errors'] as $sMessage) {
					$aResult['form_errors'] = $sMessage;
				}
			}

		}

		return $aResult;

	}

	/**
	 * Gibt die Daten-Attribute für die Erfolgs-Seite zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return string
	 */
	public function getSuccessPageDataAttributes($mSchool, $sLanguage = null) {

		return ' data-form-navigation="success" ';

	}

	/**
	 * Gibt den Titel für die Erfolgs-Seite zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * Eine eigene Einstellung gibt es dafür aktuell nicht, deswegen wird einfach der Text des
	 * Absenden-Buttons genommen.
	 *
	 * @param string $sLanguage
	 * @return string
	 */
	public function getSuccessPageTitle($sLanguage = null) {

		$sLanguage = $this->getDynamicLanguage($sLanguage);
		return $this->getTranslation('sendbtn', $sLanguage);

	}

	/**
	 * Gibt den Text für die Erfolgs-Seite zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * @param string $sLanguage
	 * @return string
	 */
	public function getSuccessPageText($sLanguage = null) {

		return $this->getTranslation('success', $sLanguage);

	}

	/**
	 * Gibt die Fehlermeldung für eine falsche Dateierweiterung (Uploads) zurück
	 *
	 * @param \Tc\Service\Language\Frontend|string $mLanguage
	 * @return string
	 */
	public function getFileExtensionError($mLanguage = null) {
		$sError = $this->getTranslation('extension', $mLanguage);
		$sError = str_replace('{extensions}', join(', ', Ext_Thebing_Form_Page_Block::VALIDATION_TYPE_UPLOAD_ALLOWED_EXTENSIONS), $sError);
		return $sError;
	}

	/**
	 * Gibt true zurück wenn Daten für die Formulargenerierung aus dem Cache geladen werden sollen, ansonsten false.
	 *
	 * @return boolean
	 */
	public function useCache() {
		return !$this->ignore_cache;
	}

	/**
	 * Erstellt eine Formular-Seite falls noch keine existiert (ohne funktioniert der Dialog nicht korrekt)
	 */
	public function checkPages() {

		$aPages = $this->getPages();
		if(!empty($aPages)) {
			return;
		}

		/** @var Ext_Thebing_Form_Page $oPage */
		$oPage = $this->getJoinedObjectChild('pages');
		$oPage->type = 'booking';
		foreach((array)$this->languages as $sLanguage) {
			$sField = 'title_'.$sLanguage;
			$oPage->$sField = '...';
		}
		$oPage->save();

		// Cache löschen, sonst wird die neu erstellte Seite nicht gefunden
		unset($this->_aJoinTablesLoaded['pages']);

	}

	/**
	 * Übersetzungen speichern.
	 *
	 * @param mixed[] $aTranslations
	 */
	protected function saveTranslations(&$aTranslations) {

		if(DB::getLastTransactionPoint() === null) {
			throw new RuntimeException(__METHOD__.': Not in a transaction!');
		}

		// Alte Übersetzungen löschen
		$sSQL = "
			DELETE FROM
				`kolumbus_forms_translations`
			WHERE
				`item` = 'form' AND
				`item_id` = :iFormID
		";
		$aSQL = array(
			'iFormID' => $this->id
		);
		DB::executePreparedQuery($sSQL, $aSQL);

		// Neue Übersetzungen speichern
		foreach((array)$aTranslations as $sField => $aLanguages) {
			foreach((array)$aLanguages as $sLanguage => $sContent) {

				if (
					$sField === 'success' &&
					$this->type === self::TYPE_REGISTRATION_V3
				) {
					$oPurifier = new \Core\Service\HtmlPurifier(\Core\Service\HtmlPurifier::SET_FRONTEND);
					$sContent = $oPurifier->purify($sContent);
					// Da damals das Konzept von JoinedObjects scheinbar unbekannt war, muss das irgendwie zurück geschrieben werden für den Dialog
					$aTranslations[$sField][$sLanguage] = $sContent;
				}

				$oTranslation = new Ext_Thebing_Form_Translation();
				$oTranslation->item = 'form';
				$oTranslation->item_id = $this->id;
				$oTranslation->language = $sLanguage;
				$oTranslation->field = $sField;
				$oTranslation->content = $sContent;
				$oTranslation->save();

			}
		}

	}

	/**
	 * Währungseinstellungen speichern.
	 *
	 * @param mixed[] $aCurrencies
	 */
	protected function saveCurrencyData($aCurrencies) {

		// Währungseinstellungen für nicht gewählte Schulen verwerfen
		foreach(array_keys((array)$aCurrencies) as $iSchoolID) {
			if(!in_array($iSchoolID, $this->schools)) {
				unset($aCurrencies[$iSchoolID]);
			}
		}

		// Aktuelle/Alte Währungseinstellungen löschen
		$sSQL = "
			DELETE FROM
				`kolumbus_forms_currencies`
			WHERE
				`form_id` = :iFormID
		";
		$aSQL = array(
			'iFormID' => $this->id
		);
		DB::executePreparedQuery($sSQL, $aSQL);

		// Neue Währungseinstellungen speichern
		foreach((array)$aCurrencies as $iSchoolID => $aData) {
			foreach((array)$aData as $iCurrencyID) {
				$aInsert = array(
					'form_id' => $this->id,
					'school_id' => $iSchoolID,
					'currency_id' => $iCurrencyID
				);
				DB::insertData('kolumbus_forms_currencies', $aInsert);
			}
		}

	}

	/**
	 * Einstellungen pro Schule speichern.
	 *
	 * @param mixed[] $aSettings
	 */
	protected function saveSchoolSettings($aSettings) {

		foreach((array)$aSettings as $iSchoolID => $aData) {

			if($aData['show_sum_vat'] == 0) {
				$aData['show_positions_vat'] = 0;
			}

			DB::updateData('kolumbus_forms_schools', $aData, ['form_id' => $this->id, 'school_id' => $iSchoolID]);

		}

	}

	/**
	 * @inheritdoc
	 */
	public function validate($bThrowExceptions = false) {

		$mValidate = parent::validate($bThrowExceptions);

		if ($mValidate === true) {

			// Prüfen, ob alle Sprachen des Forms auch in den ausgewählten Templates vorkommen
			// (beim Duplizieren des Formulars sind die Einstellungen unter Umständen nicht geladen)
			foreach ($this->school_settings as $aSchoolSetting) {
				if ($aSchoolSetting['tpl_id'] > 0) {
					$oTemplate = Ext_Thebing_Pdf_Template::getInstance($aSchoolSetting['tpl_id']);
					$aDiff = array_diff($this->languages, (array)$oTemplate->languages);
					if (!empty($aDiff)) {
						$mValidate = [
							'school_settings_'.$aSchoolSetting['school_id'].'_tpl_id' => 'MISSING_TEMPLATE_LANGUAGES'
						];
					}
				}
			}

		}

		return $mValidate;
	}

	/**
	 * Gibt die angegebene schulspezifische Einstellung zurück.
	 *
	 * Sollte ein Wert nicht existieren oder nicht gesetzt sein wird ein leerer String zurück gegeben.
	 *
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sKey
	 * @return string
	 */
	public function getSchoolSetting($mSchool, $sKey) {

		$sKey = (string)$sKey;

		$oSchool = Ext_Thebing_School::createSchoolObjectFromArgument($mSchool);
		$iSchoolID = $oSchool->id;

		return $this->{'school_settings_'.$iSchoolID.'_'.$sKey};

	}

	/**
	 * Formular erzeugt neue Buchung und basiert nicht auf einer bestehenden
	 *
	 * @see purpose
	 * @return bool
	 */
	public function isCreatingBooking(): bool {
		return $this->purpose === self::PURPOSE_NEW || $this->purpose === self::PURPOSE_TEMPLATE;
	}

	public function hasInvalidBlocksForEditPurpose(): bool {

		return (
			!$this->isCreatingBooking() &&
			!empty($this->getFilteredBlocks(function (\Ext_Thebing_Form_Page_Block $b) {
				return (
					in_array($b->block_id, \Ext_Thebing_Form_Page_Block::TYPES_SERVICES) &&
					!in_array($b->block_id, [\Ext_Thebing_Form_Page_Block::TYPE_COURSES, \Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS, \Ext_Thebing_Form_Page_Block::TYPE_ACTIVITY])
				);
			}))
		);

	}

}
