<?php

use TsRegistrationForm\Events\FormSaved;
use TsRegistrationForm\Helper\BuildInquiryHelper;
use TsRegistrationForm\Interfaces\RegistrationCombination;
use Smarty\Smarty;

abstract class Ext_TS_Frontend_Combination_Inquiry_Abstract extends Ext_TC_Frontend_Combination_Abstract implements RegistrationCombination {

	/**
	 * @var string
	 */
	const MAIL_APPLICATION_NAME = '';

	/**
	 * Muss mit form_object.js (/media/js/form_new.js) übereinstimmen!
	 *
	 * @see Ext_TS_Frontend_Combination_Inquiry_Abstract::getTemplateFiles()
	 * @var string
	 */
	const THEBING_JQUERY_NAME = 'jQueryThebing';

	/**
	 * Muss mit form_object.js (/media/js/form_new.js) übereinstimmen!
	 *
	 * @see Ext_TS_Frontend_Combination_Inquiry_Abstract::getTemplateFiles()
	 * @var string
	 */
	const THEBING_JS_NAMESPACE = 'Thebing';

	/**
	 * @var Ext_TS_Frontend_Combination_Inquiry_Helper_Services
	 */
	protected $oServiceHelper;

	/**
	 * Erstelltes Inquiry-Objekt nach Erstellung (nur dann!)
	 *
	 * @var Ext_TS_Inquiry_Abstract
	 */
	protected $oInquiry;

	/**
	 * Bezahldaten für Bezahlungen, die SOFORT notwendig sind, da Version erst im ParallelProcessing generiert wird (PayPal)
	 *
	 * @var array
	 */
	protected $aDocumentItems = [];

	/**
	 * @return Ext_TS_Inquiry_Abstract|array
	 */
	abstract protected function createObject();

	/**
	 * @return string
	 */
	abstract protected function getTypeForDocument();

	/**
	 * @return string
	 */
	abstract public function getTypeForNumberrange();

	/**
	 * @inheritdoc
	 */
	public function __construct(Ext_TC_Frontend_Combination $oCombination, Smarty $oSmarty = null) {
		parent::__construct($oCombination, $oSmarty);
		// TODO Service-Helper in Kombination cachen!
		$this->oServiceHelper = new Ext_TS_Frontend_Combination_Inquiry_Helper_Services($this->requireForm(), $this->requireSchool(), $this->requireLanguage());
	}

	/**
	 * @inheritdoc
	 */
	protected function _default() {

		$oForm = $this->requireForm();
		$oSchool = $this->requireSchool();
		$oFormProxy = new Ext_Thebing_Form_Proxy($oForm);
		$oSchoolProxy = new Ext_Thebing_School_Proxy($oSchool);
		$sLanguage = $this->requireLanguage();
		$sOnloadJsEvent = $this->getOnloadJsEvent();

		$sFormUuid = 'form_'.$oFormProxy->getId().'_'.$oSchoolProxy->getId().'_'.$sLanguage.'_';
		$sFormUuid = uniqid($sFormUuid, true);
		$sFormUuid = str_replace('.', '_', $sFormUuid);

		$this->_assign('oForm', $oFormProxy);
		$this->_assign('oSchool', $oSchoolProxy);
		$this->_assign('sLanguage', $sLanguage);
		$this->_assign('sOnloadJsEvent', $sOnloadJsEvent);
		$this->_assign('sJQueryName', self::THEBING_JQUERY_NAME);
		$this->_assign('sFormUuid', $sFormUuid);
		$this->_assign('sFormIdentifier', $sFormUuid);
		$this->_assign('sView', 'form');
		$this->_assign('aTranslations', [
			'yes' => Ext_TC_Placeholder_Abstract::translateFrontend('Yes', $sLanguage),
			'no' => Ext_TC_Placeholder_Abstract::translateFrontend('No', $sLanguage)
		]);

		$sDatepickerFormat = $oSchoolProxy->getDateFormat('jquery');
		$this->_assign('sDatepickerFormat', $sDatepickerFormat);

		// Da task und diese komplett eigene Frontend-Controller-Implementierung irgendwie unbrauchbar ist, geht das nur so
		if($this->_oRequest->get('return') === 'payment') {
			$this->handlePaymentReturn();
		}

		// Muss gesetzt werden, damit Formular die CSS-Datei findet
		$this->_oSmarty->setTemplateDir(Ext_TS_Frontend_Template_Gui2_Data::getTemplatePath());

	}

	/**
	 * Onlinezahlung: Rückleitung vom Zahlungsdienstleister zum Formular
	 */
	protected function handlePaymentReturn() {

		$oForm = $this->requireForm();
		$sLanguage = $this->requireLanguage();

		// Zahlung: Zahlung generieren und Confirm-Message modifizieren
		$oPaymentHandler = $this->getPaymentProviderHandler();
		if($oPaymentHandler === null) {
			return;
		}

		try {
			$bSuccess = $oPaymentHandler->executePayment();
			$this->_assign('sMessage', $oForm->getTranslation('paymentsuccess', $sLanguage));
			if(!$bSuccess) {
				$this->_assign('sMessage', $oForm->getTranslation('paymenterror', $sLanguage));
			}
		} catch(Exception $e) {
			$this->_assign('sMessage', $oForm->getTranslation('errorinternal', $sLanguage));
			$this->log('Exception within payment return (execution)!', [
				'form' => $oForm->id,
				'exception' => $e,
				'trace' => $e->getTrace(),
			], true);
		}

		$this->_assign('sView', 'payment_return');

	}

	/**
	 * AJAX-Request für Preisberechnung und Feriensplittung
	 *
	 * @return array
	 */
	protected function _pricesAjax() {

		$aResult = [];
		$oForm = $this->requireForm();

		$this->_oRequest->attributes->set('combination', $this);

		try {

			$oForm = $this->requireForm();
			$oSchool = $this->requireSchool();
//			$oCurrency = $oForm->getSelectedCurrency($this->getRequest());
			$oCurrency = Ext_Thebing_Currency::getInstance($oSchool->getCurrency());

			$oInquiry = $this->createInquiryObject();
			$oJourney = $oInquiry->getJourney();

			$oForm->getSelectedCourses($this->getRequest(), $oSchool, $oJourney);
			$oForm->getSelectedAccommodations($this->getRequest(), $oSchool, $oJourney);
			$aTransfers = $oForm->getSelectedTransfers($this->getRequest(), $oSchool, $oJourney);
			$oForm->getSelectedInsurances($this->getRequest(), $oSchool, $oJourney);

			// Muss zwingend gesetzt werden für buildItems()
			$iTransferMode = $oJourney::TRANSFER_MODE_NONE;
			foreach($aTransfers as $oJourneyTransfer) {
				$iTransferMode |= $oJourneyTransfer->transfer_type;
			}
			$oJourney->transfer_mode = $iTransferMode;

			$oPriceHelper = new Ext_TS_Frontend_Combination_Inquiry_Helper_Prices($this, $oCurrency);
			$oPriceHelper->setInquiry($oInquiry, $oForm->getSelectedFees($this->getRequest(), $oSchool));

			$aResult = $oPriceHelper->getPriceBlockData();

			// Feriensplittung
			if($this->_oRequest->get('check_services')) {
				$iStartTime = microtime(true);
				$aResult['container_change'] = $this->checkServices($oPriceHelper->getInquiry());
				$aResult['container_change_time'] = microtime(true) - $iStartTime;
			}

		} catch(Exception $e) {

			$this->log(
				'Exception during price calculation!', [
					'form' => $oForm->id,
					'exception' => $e,
					'trace' => $e->getTrace(),
				],
				true
			);

			$aResult['internal_error'] = true;

		}

		return $aResult;

	}

	/**
	 * Feriensplittung bei Kursen überprüfen
	 *
	 * @param Ext_TS_Inquiry $oInquiry
	 * @return array
	 */
	protected function checkServices($oInquiry) {

		$aReturn = [];

		$oJourney = $oInquiry->getJourney();
		if(empty($oJourney->getJoinedObjectChilds('courses', true))) {
			return $aReturn;
		}

		$oForm = $this->requireForm();
		$oCourseBlock = $oForm->getFixedBlock(Ext_Thebing_Form_Page_Block::TYPE_COURSES);

		$oSplitter = new Ext_TS_Inquiry_Journey_Holiday_Split($oJourney);
		$oSplitter->split();

		if(
			$oSplitter->hasMoved() ||
			$oSplitter->hasSplittings()
		) {
			// Rückwärts-Mapping (wurde bisher nie gebraucht)
			$aMapping = [
				Ext_Thebing_Form_Page_Block_Virtual_Courses_Coursetype::class => 'course_id',
				Ext_Thebing_Form_Page_Block_Virtual_Courses_Level::class => 'level_id',
				Ext_Thebing_Form_Page_Block_Virtual_Courses_Units::class => 'units',
				Ext_Thebing_Form_Page_Block_Virtual_Courses_Startdate::class => 'from',
				Ext_Thebing_Form_Page_Block_Virtual_Courses_Duration::class => 'weeks',
				Ext_Thebing_Form_Page_Block_Virtual_Courses_Enddate::class => 'until',
			];

			$oVirtualCourseBlock = $oForm->getFixedBlock(Ext_Thebing_Form_Page_Block::TYPE_COURSES);
			$aVirtualCourseBlockChilds = $oVirtualCourseBlock->getChildBlocks()[0]->getChildBlocks();

			$aReturn['courses'] = [];
			foreach($oJourney->getJoinedObjectChilds('courses', true) as $oJourneyCourse) {
				$aCourse = [];
				foreach($aVirtualCourseBlockChilds as $oChildBlock) {
					if(isset($aMapping[get_class($oChildBlock)])) {
						$sField = $aMapping[get_class($oChildBlock)];
						$sValue = $oJourneyCourse->$sField;
						if($sField === 'from' || $sField === 'until') {
							$sValue = (new DateTime($sValue))->format('Ymd');
						}
						$aCourse[$oChildBlock->getInputDataIdentifier()] = $sValue;
					}
				}
				$aReturn['courses'][] = $aCourse;
				$aReturn['messages']['courses'] = $oCourseBlock->getTranslation('serviceChanged');
			}
		}

		return $aReturn;

	}

	/**
	 * Ajax Callback: Absenden des Formulars
	 *
	 * @return mixed[]
	 */
	protected function _submitAjax() {

		$aResult = [];
		$aResult['result'] = 'error';
		$aResult['block_errors'] = [];
		$aResult['form_errors'] = [];
		$bErrors = false;

		$oForm = null;
		$sLanguage = null;

		$this->log('Submit', [
			'request' => $this->_oRequest->getAll()
		], false);

		try {

			$oForm = $this->requireForm();
			$oSchool = $this->requireSchool();
			$sLanguage = $this->requireLanguage();

			$this->_oRequest->attributes->set('combination', $this);

//			$this->initializeGlobalState($sLanguage);

			$aErrors = $oForm->validateFormInput($this->_oRequest, $oSchool, $sLanguage);

			if(isset($aErrors['block_errors'])) {
				$aResult['block_errors'] = $aErrors['block_errors'];
				if(count($aResult['block_errors']) > 0) {
					$bErrors = true;
				}
				unset($aErrors['block_errors']);
			}

			if(isset($aErrors['form_errors'])) {
				$aResult['form_errors'] = $aErrors['form_errors'];
				if(count($aResult['form_errors']) > 0) {
					$bErrors = true;
				}
				unset($aErrors['form_errors']);
			}

			// TODO Besser lösen
			if(!$bErrors) {
				$aNameBlocks = $oForm->getFilteredBlocks(function(Ext_Thebing_Form_Page_Block $oBlock) {
					return (
						(
							$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT
						) && (
							$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_FIRSTNAME ||
							$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_LASTNAME
						)
					);
				});
				$sFullname = '';
				foreach($aNameBlocks as $oNameBlock) {
					$sFullname .= $oNameBlock->getFormInputValue($this->_oRequest);
					$sFullname = trim($sFullname);
				}
				if(strlen($sFullname) < 1) {
					$aResult['form_errors'][] = $oForm->getTranslation('error', $sLanguage);
					$bErrors = true;
				}
			}

			if(!$bErrors) {

				$aErrors = $this->prepareCreateObject();

				if(!empty($aErrors['block_errors'])) {
					$aResult['block_errors'] = $aErrors['block_errors'];
				}
				if(!empty($aErrors['form_errors'])) {
					$aResult['form_errors'] = $aErrors['form_errors'];
				}

				if(
					empty($aErrors['block_errors']) &&
					empty($aErrors['form_errors'])
				) {

					$this->log('Submit: Success', [
						'form_id' => $oForm->id,
						'inquiry_id' => $this->oInquiry->id
					], false);

					$aResult['success_message'] = $this->getSuccessMessage();
					$aResult['result'] = 'success'; // Muss wegen möglicher Exception darunter stehen

				}

			} else {

				$this->log('Submit: Errors', [
					'form' => $oForm->id,
					'result' => $aResult,
					'unexpected' => $aErrors,
				], false);

			}

		} catch(\Throwable $e) {

			$this->log('Exception during form submit!', [
				'form' => $oForm->id,
				'exception' => $e,
				'trace' => $e->getTrace(),
			], true);

			if(
				empty($aResult['block_errors']) &&
				empty($aResult['form_errors']) &&
				$oForm instanceof Ext_Thebing_Form &&
				$sLanguage !== null
			) {
				$aResult['form_errors'][] = $oForm->getTranslation('errorinternal', $sLanguage);
			}

			$aResult['internal_error'] = true;

		}

		return $aResult;

	}

	/**
	 * Speichern des Objekts vorbereiten
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function prepareCreateObject() {

//		$oSchool = $this->requireSchool();
//
//		// Nummernkreis sperren (wird mehrfach probiert, siehe $this->lockNumberrange())
//		$oNumberrange = Ext_Thebing_Inquiry_Document_Numberrange::getObject($this->getTypeForNumberrange(), false, $oSchool->id);
//		if(!$this->lockNumberrange($oNumberrange)) {
//			// Löst ein internal error im Formular aus
//			throw new RuntimeException('Failed to acquire numberrange lock!');
//		}

		try {

			DB::begin(__CLASS__);

			$mInquiry = $this->createObject();
			if($mInquiry instanceof Ext_TS_Inquiry_Abstract) {
				DB::commit(__CLASS__);
			} else {
				DB::rollback(__CLASS__);
				return $mInquiry;
			}

		} catch(Exception $e) {

			DB::rollback(__CLASS__);
			throw $e;

		}

//		$oNumberrange->removeLock();

		Core\Facade\SequentialProcessing::execute();
		//Ext_Gui2_Index_Stack::executeCache();
		Ext_Gui2_Index_Stack::save(true); // Prio 0-Einträge im Stack speichern

		return [];

	}

//	/**
//	 * Nummernkreis sperren
//	 *
//	 * @return Ext_Thebing_Inquiry_Document_Numberrange
//	 */
//	public function lockNumberrange() {
//
//		$oSchool = $this->requireSchool();
//		$oNumberrange = Ext_Thebing_Inquiry_Document_Numberrange::getObject($this->getTypeForNumberrange(), false, $oSchool->id);
//
//		if($oNumberrange->acquireLock()) {
//			return $oNumberrange;
//		}
//
//		return null;
//
//		for($i = 0; $i < 10; $i++) {
//			if($oNumberrange->acquireLock()) {
//				return true;
//			}
//
//			// Direkt abbrechen, damit das nicht ewig lädt und ggf. dann doch nicht klappt
//			if(Ext_Thebing_Util::isDevSystem()) {
//				return false;
//			}
//
//			sleep(3);
//		}
//
//		return false;
//
//	}

	/**
	 * {@inheritdoc}
	 */
	public function getTemplateFiles() {

		$oForm = $this->requireForm();
		$oSchool = $this->requireSchool();
		$sLanguage = $this->requireLanguage();
		$sPath = \Ext_TS_Frontend_Template_Gui2_Data::getTemplatePath(false);

		$aFiles = $oForm->getDownloadFileList($oSchool, $sLanguage);

		$aFiles['form_object.js'] = $sPath.'/js/form_new.js';
		$aFiles['form_loading.gif'] = $sPath.'/img/form-ajax-loader.gif';
		$aFiles['info_icon.png'] = $sPath.'/img/help.png';
		$aFiles['printer.png'] = $sPath.'/img/printer.png';

		return $aFiles;

	}

	/**
	 * Gibt das Objekt für das ausgewählte Formular zurück.
	 *
	 * @throws RuntimeException
	 * @return Ext_Thebing_Form
	 */
	public function requireForm() {

		$iFormId = $this->getIdFromIndex('items_form');
		$oForm = Ext_Thebing_Form::getInstance($iFormId);

		if(
			!($oForm instanceof Ext_Thebing_Form) ||
			!$oForm->id
		) {
			throw new RuntimeException('Failed to load form with id '.$iFormId);
		}

		// Kombination setzen, damit man beim Validieren einen Logger hat
		$oForm->oCombination = $this;

		// Aktuell erstmal nicht den Caching-Helper, die Blöcke nutzen dann WDCache
		//$oForm->oCachingHelper = $this->oCachingHelper;

		return $oForm;

	}

	public function getForm(): Ext_Thebing_Form {
		return $this->requireForm();
	}

	/**
	 * Gibt das Objekt für die ausgewählte Schule zurück.
	 *
	 * @throws RuntimeException
	 * @return Ext_Thebing_School
	 */
	public function requireSchool() {

		$iSchoolId = $this->getIdFromIndex('items_school');
		$oSchool = Ext_Thebing_School::getInstance($iSchoolId);

		if(
			!($oSchool instanceof Ext_Thebing_School) ||
			!$oSchool->id
		) {
			$sMsg = 'Failed to load school with id %1$d';
			$sMsg = sprintf($sMsg, $iSchoolId);
			throw new RuntimeException($sMsg);
		}

		return $oSchool;

	}

	public function getSchool(): \Ext_Thebing_School {
		return $this->requireSchool();
	}

	/**
	 * Gibt den Code für die ausgewählte Sprache zurück
	 *
	 * @throws RuntimeException
	 * @return string
	 */
	public function requireLanguage() {

		$sLanguage = $this->getStringFromIndex('items_language');
		$oForm = $this->requireForm();

		$aSelectedLanguages = $oForm->getSelectedLanguages();

		if(!in_array($sLanguage, $aSelectedLanguages)) {
			$sLanguage = $oForm->default_language;
		}

		if(empty($sLanguage)) {
			$sMsg = 'Failed to load language code';
			throw new RuntimeException($sMsg);
		}

		return $sLanguage;

	}

	public function getLanguage(): \Tc\Service\Language\Frontend {
		return new \Tc\Service\Language\Frontend($this->requireLanguage());
	}

	/**
	 * Gibt den Javascript-Code zurück der als Onload-Event genutzt werden soll.
	 *
	 * @return string
	 */
	private function getOnloadJsEvent() {

		return '
			'.self::THEBING_JQUERY_NAME.'().ready(function() {
				'.self::THEBING_JQUERY_NAME.'("form[data-dynamic-form]").each(function(iIndex, oForm) {
					'.self::THEBING_JS_NAMESPACE.'.initializeForm(oForm);
				});
			});
		';

	}

	/**
	 * Gibt die ID zurück die unter dem angegebenen Index gespeichert ist.
	 *
	 * Es wird genau eine ID zurück gegeben oder 0 bei einem Fehler bzw. wenn der Index nicht existiert.
	 *
	 * Sollte unter dem Index ein Array gespeichert sein wird der erste Wert als ID zurück gegeben.
	 *
	 * @param string $sIndex
	 * @return integer
	 */
	private function getIdFromIndex($sIndex) {

		$sIndex = (string)$sIndex;
		$mValue = $this->_oCombination->$sIndex;

		if(is_array($mValue)) {
			$mValue = reset($mValue);
		}

		$iId = (int)$mValue;
		return $iId;

	}

	/**
	 * Gibt den String zurück der unter dem angegebenen Index gespeichert ist.
	 *
	 * Wenn ein Fehler auftritt oder der Index nicht existiert wird ein leerer String zurück gegeben.
	 *
	 * Sollte unter dem Index ein Array gespeichert sein wird der erste Wert als String zurück gegeben.
	 *
	 * @param string $sIndex
	 * @return string
	 */
	private function getStringFromIndex($sIndex) {

		$sIndex = (string)$sIndex;
		$mValue = $this->_oCombination->$sIndex;

		if(is_array($mValue)) {
			$mValue = reset($mValue);
		}

		$sValue = (string)$mValue;
		return $sValue;

	}

	/**
	 * Inquiry-Objekt erzeugen (für Anmeldeformular und Preisberechnung)
	 *
	 * @return Ext_TS_Inquiry
	 */
	public function createInquiryObject() {

		$oHelper = new BuildInquiryHelper($this);
		return $oHelper->createInquiryObject();

	}

	/**
	 * Befüllt das Objekt mit den aktuellen Eingabedaten des Formulars.
	 *
	 * @param Ext_TS_Inquiry_Abstract|Ext_TS_Inquiry $oInquiry
	 */
	protected function setObjectDataFromBlocks(Ext_TS_Inquiry_Abstract $oInquiry) {

		$oForm = $this->requireForm();
		$oSchool = $this->requireSchool();

		$oDateFormat = new Ext_Thebing_Gui2_Format_Date('frontend_date_format', $oSchool->id);
		$oContact = $oInquiry->getCustomer();
		$oAddress = $oContact->getAddress('contact');
		$oAddressBilling = $oContact->getAddress('billing');
		$addressBillingContacts = $oAddressBilling->getJoinTableObjects('contacts');
		$addressBillingContact = reset($addressBillingContacts);

		$booker = $oInquiry->getBooker();

		$iStudentStatusId = $oForm->getSchoolSetting($oSchool, 'student_status_id');
		if(!empty($iStudentStatusId)) {
			$oInquiry->status_id = $iStudentStatusId;
		}

		$aBlocks = $oForm->getInputBlocks();
		foreach($aBlocks as $oBlock) {

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_ADDITIONAL_PLACEOFBIRTH
			) {
				$oContact->setDetail('place_of_birth', $oBlock->getFormInputValue($this->_oRequest));
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_ADDITIONAL_COUNTRYOFBIRTH
			) {
				$oContact->setDetail('country_of_birth', $oBlock->getFormInputValue($this->_oRequest));
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_ADDITIONAL_JOB
			) {
				$oInquiry->profession = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_ADDITIONAL_SOCIAL_NUMBER
			) {
				$oInquiry->social_security_number = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_ADDITIONAL_TAX_NUMBER
			) {
				$oContact->setDetail('tax_code', $oBlock->getFormInputValue($this->_oRequest));
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_ADDITIONAL_VAT_ID
			) {
				$oContact->setDetail('vat_number', $oBlock->getFormInputValue($this->_oRequest));
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_SELECT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_REFERER_ID
			) {
				$oInquiry->referer_id = (int)$oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			// Entspricht SUBTYPE_TEXTAREA_NOTICE
//			if(
//				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA &&
//				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_ENQUIRY_STUDENT_COMMENT
//			) {
//				$oInquiry->comment = $oBlock->getFormInputValue($this->_oRequest);
//				continue;
//			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_FIRSTNAME
			) {
				$oContact->firstname = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_LASTNAME
			) {
				$oContact->lastname = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_ADDRESS
			) {
				$oAddress->address = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_ADDRESS_ADDON
			) {
				$oAddress->address_addon = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_ZIP
			) {
				$oAddress->zip = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_CITY
			) {
				$oAddress->city = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_STATE
			) {
				$oAddress->state = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_PHONE
			) {
				$oContact->setDetail(
					Ext_TC_Contact_Detail::TYPE_PHONE_PRIVATE,
					$oBlock->getFormInputValue($this->_oRequest)
				);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_PHONE_OFFICE
			) {
				$oContact->setDetail(
					Ext_TC_Contact_Detail::TYPE_PHONE_OFFICE,
					$oBlock->getFormInputValue($this->_oRequest)
				);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_PHONE_MOBILE
			) {
				$oContact->setDetail(
					Ext_TC_Contact_Detail::TYPE_PHONE_MOBILE,
					$oBlock->getFormInputValue($this->_oRequest)
				);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_FAX
			) {
				$oContact->setDetail(
					Ext_TC_Contact_Detail::TYPE_FAX,
					$oBlock->getFormInputValue($this->_oRequest)
				);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_EMAIL
			) {
				$oContact->email = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_INVOICE_COMPANY
			) {
				$oAddressBilling->company = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_INVOICE_FIRSTNAME
			) {
				if ($booker != null) {
					$booker->firstname = $oBlock->getFormInputValue($this->_oRequest);
				}
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_INVOICE_LASTNAME
			) {
				if ($booker != null) {
					$booker->lastname = $oBlock->getFormInputValue($this->_oRequest);
				}
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_INVOICE_ADDRESS
			) {
				$oAddressBilling->address = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_INVOICE_ZIP
			) {
				$oAddressBilling->zip = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_INVOICE_TAX_NUMBER
			) {
				$addressBillingContact->setDetail('tax_code', $oBlock->getFormInputValue($this->_oRequest));
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_INVOICE_CITY
			) {
				$oAddressBilling->city = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_INVOICE_STATE
			) {
				$oAddressBilling->state = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_INVOICE_EMAIL
			) {
				if ($booker != null) {
					$booker->email = $oBlock->getFormInputValue($this->_oRequest);
				}
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_INVOICE_PHONE
			) {
				if ($booker != null) {
					$booker->setDetail('phone_private', $oBlock->getFormInputValue($this->_oRequest));
				}
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_INVOICE_VAT_ID
			) {
				$addressBillingContact->setDetail('vat_number', $oBlock->getFormInputValue($this->_oRequest));
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_INVOICE_RECIPIENT_ID
			) {
				$addressBillingContact->setDetail('recipient_code', $oBlock->getFormInputValue($this->_oRequest));
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_DATE &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_DATE_BIRTHDATE
			) {
				$sBirthdate = $oBlock->getFormInputValue($this->_oRequest);
				$oContact->birthday = $oDateFormat->convert($sBirthdate);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_SELECT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_SEX
			) {
				$oContact->gender = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_SELECT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_NATIONALITY
			) {
				$oContact->nationality = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_SELECT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MOTHERTONGE
			) {
				$oContact->language = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_SELECT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_CONTACT_COUNTRY
			) {
				$oAddress->country_iso = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_SELECT &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_INVOICE_COUNTRY
			) {
				$oAddressBilling->country_iso = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

			// Wird in setContactData() auch nochmal gesetzt, wenn der Block nicht existiert
			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_CHECKBOX_NEWSLETTER
			) {
				$oContact->setDetail(
					Ext_TS_Contact::DETAIL_NEWSLETTER,
					$oBlock->getFormInputValue($this->_oRequest)
				);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_NOTICE
			) {
				$oContact->setDetail(
					Ext_TS_Contact::DETAIL_COMMENT,
					$oBlock->getFormInputValue($this->_oRequest)
				);
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX &&
				$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_CHECKBOX_TOS
			) {
				$oContact->setDetail(Ext_TS_Contact::DETAIL_TOS, $oBlock->getFormInputValue($this->_oRequest));
				continue;
			}

			if(
				$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT &&
				$oBlock->set_type == Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_PROMOTION_CODE
			) {
				$oInquiry->promotion = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

		}

	}

	/**
	 * @param Ext_TS_Contact $oContact
	 * @param string $sLanguage
	 */
	protected function setContactData(Ext_TS_Contact $oContact, $sLanguage) {

		$oForm = $this->requireForm();

		$oContact->corresponding_language = $sLanguage;
		$oContact->bCheckGender = false;

		$aCheckboxBlocks = $oForm->getFilteredBlocks(function(Ext_Thebing_Form_Page_Block $oBlock) {
			return $oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_CHECKBOX_NEWSLETTER;
		});

		// Wenn Block nicht existiert, dann Newsletter setzen, da Checkbox immer angetickt
		if(empty($aCheckboxBlocks)) {
			$oContact->setDetail('newsletter', 1);
		}

	}

	/**
	 * Gibt die aktuellen Eingabewerte des Formulars für alle Flex-Felder zurück.
	 *
	 * Array-Keys sind die Flex-IDs, Values der jeweilige Eingabewert im Formular.
	 *
	 * @return mixed[]
	 */
	protected function getFlexValuesFromBlocks() {

		$oForm = $this->requireForm();
		$aFlexValues = [];

		$aBlocks = $oForm->getInputBlocks();
		foreach($aBlocks as $oBlock) {

			if(
				$oBlock->isFlexFieldBlock() &&
				!$oBlock->isFlexFieldUploadBlock()
			) {
				$aFlexValues[$oBlock->getFlexFieldId()] = $oBlock->getFormInputValue($this->_oRequest);
				continue;
			}

		}

		return $aFlexValues;

	}

	protected function setJourneyDataFromBlocks(Ext_TS_Inquiry_Journey $oJourney) {

		$aErrors = [];
		$oForm = $this->requireForm();
		$oSchool = $this->requireSchool();
		$sLanguage = $this->requireLanguage();
		$iTransferMode = $oJourney->transfer_mode;

		$aSelectedCourses = $oForm->getSelectedCourses($this->_oRequest, $oSchool, $oJourney);
		$aSelectedAccommodations = $oForm->getSelectedAccommodations($this->_oRequest, $oSchool, $oJourney);
		$aSelectedTransfers = $oForm->getSelectedTransfers($this->_oRequest, $oSchool, $oJourney);
		$aSelectedInsurances = $oForm->getSelectedInsurances($this->_oRequest, $oSchool, $oJourney);

		foreach($aSelectedTransfers as $oJourneyTransfer) {
			// Transfermodus für gesamte Buchung bestimmen
			$iTransferMode |= $oJourneyTransfer->transfer_type;
		}

		// Muss hier gesetzt werden, da Referenz mit Magic get/set nicht funktioniert
		$oJourney->transfer_mode = $iTransferMode;

		// Pflichtfelder (Blöcke) prüfen
		$this->checkRequiredFixedBlock($oForm->getFixedBlock(Ext_Thebing_Form_Page_Block::TYPE_COURSES), $aSelectedCourses, $aErrors);
		$this->checkRequiredFixedBlock($oForm->getFixedBlock(Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS), $aSelectedAccommodations, $aErrors);
		$this->checkRequiredFixedBlock($oForm->getFixedBlock(Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS), $aSelectedTransfers, $aErrors);
		$this->checkRequiredFixedBlock($oForm->getFixedBlock(Ext_Thebing_Form_Page_Block::TYPE_INSURANCES), $aSelectedInsurances, $aErrors);

		// Je nach Einstellung muss es Kurs oder Unterkunft geben, unabhängig von den Einstellungen in den Blöcken
		if(
			empty($aErrors) &&
			$oForm->acc_depending_on_course &&
			empty($aSelectedCourses) &&
			!empty($aSelectedAccommodations)
		) {
			$aErrors['form_errors'][] = $oForm->getTranslation('error', $sLanguage);
		}

		if(
			$this instanceof Ext_TS_Frontend_Combination_Enquiry &&
			empty($aSelectedCourses) &&
			empty($aSelectedAccommodations)  &&
			empty($aSelectedTransfers) &&
			empty($aSelectedInsurances)
		) {
			$aErrors['no_services'] = true;
		}

		return $aErrors;

	}

	/**
	 * {@inheritdoc}
	 */
	public function executeInitializeData() {

		parent::executeInitializeData();

		$oForm = $this->requireForm();
		$oSchool = $this->requireSchool();
		$sLanguage = $this->requireLanguage();

		$iIgnoreCache = $oForm->ignore_cache;
		$oForm->ignore_cache = 1;

		$oForm->getFilteredBlocks(function(Ext_Thebing_Form_Page_Block $oBlock) use ($oSchool, $sLanguage) {
			$oBlock->getInputDataAttributes($oSchool, $sLanguage);
			$oBlock->getBlockDataAttributes($oSchool, $sLanguage);
			return false;
		});

		$oForm->ignore_cache = $iIgnoreCache;

	}

//	/**
//	 * Wird zu Beginn jedes Requests aufgerufen um benötigte globale Einstellungen zu setzen.
//	 *
//	 * @param string $sLanguage
//	 */
//	private function initializeGlobalState($sLanguage) {
//
//		// Wird in manchen Fällen bei der Preisberechnung benötigt (siehe #9090)
//		\System::setInterfaceLanguage($sLanguage);
//
//		// zur Sicherheit leeren ...
//		Ext_Thebing_Inquiry_Document_Version::clearAdditionalCostCache();
//
//	}

	/**
	 * Dokument vorbereiten für PP-Generierung
	 *
	 * @param bool $bCreateDocument
	 */
	protected function prepareBackgroundTask($bCreateDocument = true) {

		$oForm = $this->requireForm();
		$oSchool = $this->requireSchool();
		$sLanguage = $this->requireLanguage();

		// Enquiry: Wenn kein Dokument generiert wird, wird E-Mail direkt ins PP eingetragen
		if(!$bCreateDocument) {

			// Event abschicken
			FormSaved::dispatch($this, $this->oInquiry);

			/*$aStackData = [
				'combination_id' => $this->_oCombination->id,
				'object' => get_class($this->oInquiry),
				'object_id' => $this->oInquiry->id,
				'document_type' => $this->getTypeForDocument(),
			];

			Core\Entity\ParallelProcessing\Stack::getRepository()
				->writeToStack('ts-registration-form/mail-task', $aStackData, 2);*/

			return;

		}

		// Items bereits hier bauen, da diese für eine mögliche Onlinezahlung benötigt werden
		$aItems = $this->buildDocumentVersionItems($this->oInquiry);

		$this->aDocumentItems = $aItems;
		
		// Nur Anzahlungsbetrag bei Onlinezahlung verwenden
		if(
			// Checkbox ist nur bei Zahlungsanbieter sichtbar, daher auch prüfen
			!empty($oForm->getSchoolSetting($oSchool, 'payment_provider')) &&
			$oForm->getSchoolSetting($oSchool, 'pay_deposit')
		) {
			// Zahlungsbedingungen generieren, um erste Anzahlung zu ermitteln
			$oPaymentConditionService = new Ext_TS_Document_PaymentCondition($this->oInquiry, true);
			$oPaymentConditionService->setDocumentDate(date('Y-m-d'));
			$aPaymentTermRows = $oPaymentConditionService->generateRows($aItems);

			if(!empty($aPaymentTermRows)) {
				$oPaymentTermRow = reset($aPaymentTermRows);
				$this->aDocumentItems = [[
					'amount' => $oPaymentTermRow->fAmount, // Bezahlung
					'amount_with_tax' => $oPaymentTermRow->fAmount, // Platzhalter
					'description' => Ext_TC_Placeholder_Abstract::translateFrontend('Anzahlung', $sLanguage),
					'tax_category' => 0
				]];
			}
		}

		$oNumberrange = \Ext_Thebing_Inquiry_Document_Numberrange::getObject($this->getTypeForNumberrange(), false, $oSchool->id);

		$aStackData = [
			'combination_id' => (int)$this->_oCombination->id,
			'object' => get_class($this->oInquiry),
			'object_id' => (int)$this->oInquiry->id,
			'generate_document' => $bCreateDocument,
			'document_type' => $this->getTypeForDocument(),
			'document_items' => $aItems,
			'document_date' => date('Y-m-d'), // Version und Zahlungsbedingungen, da $oInquiry->created unbrauchbar
			'numberrange_id' => (int)$oNumberrange->id
		];

		Core\Entity\ParallelProcessing\Stack::getRepository()
			->writeToStack('ts-registration-form/inquiry-task', $aStackData, 2);

	}

	/**
	 * Items generieren
	 *
	 * Diese Methode wird auch vom prices-Request verwendet, allerdings werden die Versicherungspreise manuell errechnet,
	 * da das momentan leider nur alles mit IDs funktioniert!
	 *
	 * @param Ext_TS_Inquiry_Abstract $oInquiry
	 * @return array
	 */
	public function buildDocumentVersionItems(Ext_TS_Inquiry_Abstract $oInquiry) {

		$oForm = $this->requireForm();
		$oSchool = $this->requireSchool();
		$oHelper = new BuildInquiryHelper($this);

		$sType = $this->getTypeForDocument();
		$aAdditionalFees = $oForm->getSelectedFees($this->_oRequest, $oSchool);

		$oInquiry->transients['fees'] = $aAdditionalFees;

		return $oHelper->buildDocumentVersionItems($oInquiry, $sType);

	}

	/**
	 * Für Leistung prüfen, ob auch etwas ausgewählt wurde bei diesem Block, wenn Block Pflichtfeld ist
	 *
	 * @param Ext_Thebing_Form_Page_Block $oBlock
	 * @param array $aSelectedServices
	 * @param array $aErrors
	 */
	protected function checkRequiredFixedBlock(Ext_Thebing_Form_Page_Block $oBlock = null, array $aSelectedServices, array &$aErrors) {

		if($oBlock === null) {
			return;
		}

		$oForm = $this->requireForm();
		$sLanguage = $this->requireLanguage();

		if(
			empty($aSelectedServices) &&
			$oBlock instanceof Ext_Thebing_Form_Page_Block &&
			$oBlock->required
		) {
			$oCourseTypeBlock = $oForm->getFirstChildBlockOfFixedBlock($oBlock);
			$aErrors['block_errors'][$oCourseTypeBlock->getInputBlockName()] = [
				'message' => $oBlock->getTranslation('error', $sLanguage),
				'value' => '0',
				'algorithm' => 'SelectOptionsBlacklist'
			];
		}

	}

	/**
	 * Währung aus Formular oder Standardwährung oder Schule
	 *
	 * @return Ext_Thebing_Currency|null
	 */
	public function getFormCurrency() {

		$oForm = $this->requireForm();
//		$oCurrency = $oForm->getSelectedCurrency($this->_oRequest);

		// Preisblock ist keine Pflicht
//		if($oCurrency === null) {
			$oSchool = $this->requireSchool();
			$oCurrency = Ext_Thebing_Currency::getInstance($oSchool->getCurrency());
//		}

		return $oCurrency;

	}

	/**
	 * @return Ext_TS_Frontend_Combination_Inquiry_Helper_Services
	 */
	public function getServiceHelper() {
		return $this->oServiceHelper;
	}

	/**
	 * Success-Meldung (AJAX Submit): Platzhalter ersetzen
	 *
	 * @return string
	 */
	public function getSuccessMessage() {

		$oForm = $this->requireForm();
		$sLanguage = $this->requireLanguage();
		$oSchool = $this->requireSchool();

		$sConfirmationMessage = $oForm->getTranslation('success', $sLanguage);

		// Amount-Platzhalter ersetzen
		if(strpos($sConfirmationMessage, '{amount') !== false) {
			$fAmount = array_sum(array_map(function($aItem) {
				return $aItem['amount_with_tax'];
			}, $this->aDocumentItems));

			$sAmount = Ext_Thebing_Format::Number($fAmount, $this->oInquiry->getCurrency(), $oSchool->id, true, 2);
			$sConfirmationMessage = str_replace('{amount}', $sAmount, $sConfirmationMessage);
			$sConfirmationMessage = str_replace('{amount_cent}', round($fAmount * 100), $sConfirmationMessage); // Benötigt für Flywire (keiner braucht sonst so etwas)
			
		}

		$sConfirmationMessage = str_replace('{booking_key}', $this->oInquiry->id.'0001', $sConfirmationMessage);
		
		// Zahlung: Zahlung generieren und Confirm-Message modifizieren
		$oPaymentHandler = $this->getPaymentProviderHandler();
		if($oPaymentHandler !== null) {
			$oPaymentHandler->createPayment($this->oInquiry, $this->aDocumentItems, $sConfirmationMessage);
		}

		return $sConfirmationMessage;

	}

	/**
	 * @return \TsFrontend\Handler\Payment\Legacy\AbstractPayment|null
	 */
	private function getPaymentProviderHandler() {

		$oForm = $this->requireForm();
		$oSchool = $this->requireSchool();

		$oHandler = null;
		$sHandler = $oForm->getSchoolSetting($oSchool, 'payment_provider');

		if(
			!empty($sHandler) &&
			isset(\TsFrontend\Handler\Payment\Legacy\AbstractPayment::CLASSES[$sHandler])
		) {
			$sClass = \TsFrontend\Handler\Payment\Legacy\AbstractPayment::CLASSES[$sHandler];
			$oHandler = new $sClass($this);
		}

		return $oHandler;

	}

}
