<?php


class Ext_Thebing_Inquiry_Placeholder extends Ext_TS_Inquiry_Placeholder_Abstract {

	// Placeholder that should NOT be replaced! /////////////////
	protected $aSpecialPlaceholder = array(
		'receipt_amount',
		'receipt_amount_paid',
		'receipt_amount_balance',
		'receipt_comment',
		'receipt_method',
		'receipt_date',
		'transfer_communication',
		'transfer_communication',
		'transfer_provider_title',
		'transfer_provider_firstname',
		'transfer_provider_lastname',
		'current_page',
		'total_pages',
		'user_login_code' // Wird in der TsMobile-API ersetzt
	);

	/////////////////////////////////////////////////////////////

	/**
	 * @var Ext_TS_Inquiry
	 */
	public $_oInquiry;
	public $_oCustomer;

	/** @var Ext_Thebing_Inquiry_Document */
	public $_oDocument;

	/** @var Ext_Thebing_Inquiry_Document_Version */
	public $_oVersion;
	public $_oAgencyStaff;

	/** @var Ext_TS_Inquiry_Journey_Insurance|Ext_TS_Enquiry_Combination_Insurance */
	public $oJourneyInsurance;

	// Transferobjecte
	protected	$_oTransferArrival;
	protected	$_oTransferDeparture;
	protected	$_oTransferAdditional;

	/**
	 * @var Ext_TS_Inquiry_Journey_Transfer
	 */
	public $oJourneyTransfer;

	/**
	 * @var Ext_TS_Inquiry_Journey_Course
	 */
	protected	$_oJourneyCourse;
	protected	$_oCourse;
	public		$_oJourneyAccommodation;
	public		$_oFamily;
	private $_oAllocation; // TODO: Entfernen

	protected	$_aInquiryCourses = array();
	protected	$_aJourneyAccomondations = array();
	protected	$_bGroupLoop = false;
	protected   $_bCourseLoop = false;
	protected	$_aAccommodationAllocation = array();

	/**
	 * @var Ext_Thebing_School
	 */
	protected	$_oSchool;
	protected	$_aLanguages = array();

	protected	$_iSchoolForFormat = 0;

	private static $iLoopCount = 0;

	protected static $_aCacheVersionPlaceholderData = array();

	/**
	 *
	 * @var Ext_Thebing_School_Tuition_Allocation
	 */
	protected $_oTuitionAllocation = null;

	public function __construct($iInquiryId = 0, $iCustomer = 0, $iSchoolForFormat = 0) {

		if(is_object($iInquiryId)){
			$iInquiryId = $iInquiryId->getId();
		}
		$this->config($iInquiryId, $iCustomer);

		$this->_iSchoolForFormat = (int)$iSchoolForFormat;

		// Setzt $this->_iSchoolId, wichtig für Formate!
		parent::__construct();

	}

	/*
	 * Setzt die Configurationsparameter für die Inquiry-Placeholder
	 */
	public function config($iInquiryId = 0, $iCustomer = 0){

		$oInquiry = Ext_TS_Inquiry::getInstance((int)$iInquiryId);

		$this->_oInquiry				= $oInquiry;

		if($iCustomer <= 0){
			$this->_oCustomer = $this->_oInquiry->getCustomer();
		} else {
			$this->_oCustomer			= Ext_TS_Inquiry_Contact_Traveller::getInstance((int) $iCustomer);
		}

		$this->_aInquiryCourses			= $this->_oInquiry->getCourses(true, false);
		$this->_aJourneyAccomondations	= $this->_oInquiry->getAccommodations(true, false);

		// Transferobjecte
		$this->_oTransferArrival		= $this->_oInquiry->getTransfers('arrival', true);
		$this->_oTransferDeparture		= $this->_oInquiry->getTransfers('departure', true);

		// Wenn kein individueller Platzhalter durch die Schleife gesetzt wird, wird der 1. besste
		// gesetzt, damit die Platzhalter auch ohne Schleife funktionieren
		$this->_setAdditionalTransfer();


		$this->_oSchool					= $this->_oInquiry->getSchool();

		if(
			is_object($this->_oSchool) &&
			$this->_oSchool instanceof Ext_Thebing_School
		){
			$this->_aLanguages				= $this->_oSchool->getLanguageList();
		}else{
			$this->_oSchool = Ext_Thebing_School::getSchoolFromSession();
		}
	}

	protected function _setAdditionalTransfer($oTransfer = null) {

		if($this->_oInquiry instanceof Ext_TS_Inquiry_Abstract) {
			if($oTransfer instanceof Ext_TS_Service_Interface_Transfer){
				$this->_oTransferAdditional = $oTransfer;
			} else {
				$aAdditionalTransfers = $this->_oInquiry->getTransfers('additional', false);
				if(!empty($aAdditionalTransfers)) {
					$this->_oTransferAdditional = reset($aAdditionalTransfers);
				}
			}
			$this->oJourneyTransfer = $this->_oTransferAdditional;
		}
	}

	/**
	 * @TODO Hier muss alles mal so umgestellt werden, dass die einzelnen Platzhalter nur bei Bedarf ausgeführt werden
	 *
	 * @param string $sText
	 * @param array $aPlaceholderData
	 * @return string
	 */
	protected function _helperReplaceForPdf($sText, $aPlaceholderData) {

		$sPlaceholder = $aPlaceholderData['placeholder'];

		$sModifier	= '';
		if(!empty($aPlaceholderData['modifier'])) {
			$sModifier = $aPlaceholderData['modifier'];
		}

		$oDocument = $this->_oDocument;
		$oParentDocument = $oDocument->getParentDocument();
		$oVersion = $this->_oVersion;

		/**
		 * @var $oVersion Ext_Thebing_Inquiry_Document_Version
		 */

		$oSchool = $this->_oInquiry->getSchool();
		$iSchoolId = $oSchool->id;
		$iCurrencyId = $this->_oInquiry->getCurrency();

		$sDocumentNumber = $oDocument->document_number;
		$sMainDocumentNumber = $oParentDocument->document_number;

		$mToday	= time();

		$oFormatDate = new Ext_Thebing_Gui2_Format_Date();

		// Für Gruppenschleift, muss auch die inquiry_id als cache_key gesetzt werden
		// Da pro Gruppenmitglied die Beträge geholt werden (teilweise)
		$sCacheKey = $oVersion->id . '_' . $this->_oInquiry->id . '_' . $this->_bGroupLoop;

		$aCachedPlaceholder = self::$_aCacheVersionPlaceholderData[$sCacheKey];

		if(empty($aCachedPlaceholder)) {

			$oVersion->bCalculateTax	= true;

			if(
				!$this->_oInquiry->hasGroup() ||
				/* Da wir bei den Gruppen-Anfragen nur ein Anfragen-Datensatz (anders als bei den Buchungen)
				 * haben, brauch hier kein Gruppen-Betrag ausgerechnet werden */
				$this->_oInquiry instanceof Ext_TS_Enquiry
			) {
				$mAmout = $oVersion->getAmount(true, true, 'brutto');
				$mAmoutNet = $oVersion->getAmount(true, true, 'netto');
				$mAmoutInitalcost = $oVersion->getAmount(false, true, 'brutto');
			} else {
				$mAmout	= $oVersion->getGroupAmount(true, true, 'brutto');
				$mAmoutNet = $oVersion->getGroupAmount(true, true, 'netto');
				$mAmoutInitalcost = $oVersion->getGroupAmount(false, true, 'brutto');
			}

			$mAmoutProvision = 0;
			if($mAmoutNet > 0) {
				$mAmoutProvision = $mAmout - $mAmoutNet;
			}

//			$oService = new Ext_Thebing_Inquiry_Document_Service_ServiceAmount($oDocument, 'course');
//			$oService->bOnlyDiscount = true;

			$mAmoutCourse = $oVersion->getItemAmount('course', array('amount_type' => 'brutto'));
			$mAmoutCourseNet = $oVersion->getItemAmount('course', array('amount_type' => 'netto'));
//			$mAmountCourseDiscount = $oService->getAmount(); // #10824
			$mAmoutAccommodation = $oVersion->getItemAmount('accommodation', array('amount_type' => 'brutto'));
			$mAmoutAccommodationNet = $oVersion->getItemAmount('accommodation', array('amount_type' => 'netto'));
			$mAmoutTransfer = $oVersion->getItemAmount('transfer', array('amount_type' => 'brutto'));
			$mAmoutTransferNet = $oVersion->getItemAmount('transfer', array('amount_type' => 'netto'));

			$oVersion->bCalculateTax = false;
			$mAmoutExlTax = $oVersion->getAmount(true, true, 'brutto');
			$mAmoutNetExlTax = $oVersion->getAmount(true, true, 'netto');
			$mAmoutInitalcostExlTax = $oVersion->getAmount(false, true, 'brutto');
			$mAmoutProvisionExlTax = 0;
			if($mAmoutNetExlTax > 0) {
				$mAmoutProvisionExlTax		= $mAmoutExlTax - $mAmoutNetExlTax;
			}
			$mAmoutCourseExlTax	= $oVersion->getItemAmount('course', array('amount_type' => 'brutto'));
			$mAmoutCourseNetExlTax = $oVersion->getItemAmount('course', array('amount_type' => 'netto'));
			$mAmoutAccommodationExlTax = $oVersion->getItemAmount('accommodation', array('amount_type' => 'brutto'));
			$mAmoutAccommodationNetExlTax = $oVersion->getItemAmount('accommodation', array('amount_type' => 'netto'));
			$mAmoutTransferExlTax = $oVersion->getItemAmount('transfer', array('amount_type' => 'brutto'));
			$mAmoutTransferNetExlTax = $oVersion->getItemAmount('transfer', array('amount_type' => 'netto'));

			$mAmoutPrepay = null;
			$mAmoutFinalpay = null;
			$mDatePrepay = null;
			$mDateFinalpay = null;

			foreach($oVersion->getPaymentTerms() as $oPaymentTerm) {
				if(
					$mDatePrepay === null &&
					$oPaymentTerm->type === 'deposit' ||
					$oPaymentTerm->type === 'installment'
				) {
					// Alte Platzhalter machen mit neuen Zahlungsbedingungen keinen Sinn mehr (#8838)
					$mAmoutPrepay = $oPaymentTerm->amount;
					$mDatePrepay = $oPaymentTerm->date;
				} elseif($oPaymentTerm->type === 'final') {
					$mAmoutFinalpay = $oPaymentTerm->amount;
					$mDateFinalpay = $oPaymentTerm->date;
				}
			}

			$oInquiry = $oDocument->getInquiry();
			$mAmoutPayed = $oInquiry->amount_payed;

			// formatieren
			$mAmout	= Ext_Thebing_Format::Number($mAmout, $iCurrencyId, $iSchoolId);
			$mAmoutNet = Ext_Thebing_Format::Number($mAmoutNet, $iCurrencyId, $iSchoolId);
			$mAmoutProvision = Ext_Thebing_Format::Number($mAmoutProvision, $iCurrencyId, $iSchoolId);
			$mAmoutInitalcost = Ext_Thebing_Format::Number($mAmoutInitalcost, $iCurrencyId, $iSchoolId);
			$mAmoutCourse = Ext_Thebing_Format::Number($mAmoutCourse,	$iCurrencyId, $iSchoolId);
			$mAmoutCourseNet = Ext_Thebing_Format::Number($mAmoutCourseNet, $iCurrencyId, $iSchoolId);
			$mAmoutAccommodation = Ext_Thebing_Format::Number($mAmoutAccommodation, $iCurrencyId, $iSchoolId);
			$mAmoutAccommodationNet	= Ext_Thebing_Format::Number($mAmoutAccommodationNet, $iCurrencyId, $iSchoolId);
			$mAmoutTransfer	= Ext_Thebing_Format::Number($mAmoutTransfer, $iCurrencyId, $iSchoolId);
			$mAmoutTransferNet = Ext_Thebing_Format::Number($mAmoutTransferNet, $iCurrencyId, $iSchoolId);

			$mAmoutExlTax = Ext_Thebing_Format::Number($mAmoutExlTax, $iCurrencyId, $iSchoolId);
			$mAmoutNetExlTax = Ext_Thebing_Format::Number($mAmoutNetExlTax,	$iCurrencyId, $iSchoolId);
			$mAmoutProvisionExlTax = Ext_Thebing_Format::Number($mAmoutProvisionExlTax,	$iCurrencyId, $iSchoolId);
			$mAmoutInitalcostExlTax = Ext_Thebing_Format::Number($mAmoutInitalcostExlTax, $iCurrencyId, $iSchoolId);
			$mAmoutCourseExlTax	= Ext_Thebing_Format::Number($mAmoutCourseExlTax, $iCurrencyId, $iSchoolId);
			$mAmoutCourseNetExlTax = Ext_Thebing_Format::Number($mAmoutCourseNetExlTax, $iCurrencyId, $iSchoolId);
//			$mAmountCourseDiscount = Ext_Thebing_Format::Number($mAmountCourseDiscount, $iCurrencyId, $iSchoolId);
			$mAmoutAccommodationExlTax = Ext_Thebing_Format::Number($mAmoutAccommodationExlTax, $iCurrencyId, $iSchoolId);
			$mAmoutAccommodationNetExlTax = Ext_Thebing_Format::Number($mAmoutAccommodationNetExlTax, $iCurrencyId, $iSchoolId);
			$mAmoutTransferExlTax = Ext_Thebing_Format::Number($mAmoutTransferExlTax, $iCurrencyId, $iSchoolId);
			$mAmoutTransferNetExlTax = Ext_Thebing_Format::Number($mAmoutTransferNetExlTax,	$iCurrencyId, $iSchoolId);

//			$mAmoutCredit = Ext_Thebing_Format::Number($mAmoutCredit, $iCurrencyId, $iSchoolId);
			$mAmoutFinalpay	= Ext_Thebing_Format::Number($mAmoutFinalpay, $iCurrencyId, $iSchoolId);
			$mAmoutPrepay = Ext_Thebing_Format::Number($mAmoutPrepay, $iCurrencyId, $iSchoolId);
			$mAmoutPayed = Ext_Thebing_Format::Number($mAmoutPayed, $iCurrencyId, $iSchoolId);
			$mDatePrepay = $oFormatDate->format($mDatePrepay);
			$mDateFinalpay = $oFormatDate->format($mDateFinalpay);
			$mToday	= Ext_Thebing_Format::LocalDate($mToday, $iSchoolId);

			self::$_aCacheVersionPlaceholderData[$sCacheKey]['amount'] = $mAmout;
			self::$_aCacheVersionPlaceholderData[$sCacheKey]['amount_agency'] = $mAmoutNet;
			self::$_aCacheVersionPlaceholderData[$sCacheKey]['amount_initalcost'] = $mAmoutInitalcost;
			self::$_aCacheVersionPlaceholderData[$sCacheKey]['amount_provision'] = $mAmoutProvision;
			self::$_aCacheVersionPlaceholderData[$sCacheKey]['amount_course'] = $mAmoutCourse;
			self::$_aCacheVersionPlaceholderData[$sCacheKey]['amount_course_net'] = $mAmoutCourseNet;
			self::$_aCacheVersionPlaceholderData[$sCacheKey]['amount_accommodation'] = $mAmoutAccommodation;
			self::$_aCacheVersionPlaceholderData[$sCacheKey]['amount_accommodation_net'] = $mAmoutAccommodationNet;
			self::$_aCacheVersionPlaceholderData[$sCacheKey]['amount_transfer']	= $mAmoutTransfer;
			self::$_aCacheVersionPlaceholderData[$sCacheKey]['amount_transfer_net'] = $mAmoutTransferNet;

			self::$_aCacheVersionPlaceholderData[$sCacheKey]['amount_exl_tax'] = $mAmoutExlTax;
			self::$_aCacheVersionPlaceholderData[$sCacheKey]['amount_agency_exl_tax'] = $mAmoutNetExlTax;
			self::$_aCacheVersionPlaceholderData[$sCacheKey]['amount_initalcost_exl_tax'] = $mAmoutInitalcostExlTax;
			self::$_aCacheVersionPlaceholderData[$sCacheKey]['amount_provision_exl_tax'] = $mAmoutProvisionExlTax;
			self::$_aCacheVersionPlaceholderData[$sCacheKey]['amount_course_exl_tax'] = $mAmoutCourseExlTax;
			self::$_aCacheVersionPlaceholderData[$sCacheKey]['amount_course_net_exl_tax'] = $mAmoutCourseNetExlTax;
//			self::$_aCacheVersionPlaceholderData[$sCacheKey]['amount_course_discount'] = $mAmountCourseDiscount;
			self::$_aCacheVersionPlaceholderData[$sCacheKey]['amount_accommodation_exl_tax'] = $mAmoutAccommodationExlTax;
			self::$_aCacheVersionPlaceholderData[$sCacheKey]['amount_accommodation_net_exl_tax'] = $mAmoutAccommodationNetExlTax;
			self::$_aCacheVersionPlaceholderData[$sCacheKey]['amount_transfer_exl_tax']	= $mAmoutTransferExlTax;
			self::$_aCacheVersionPlaceholderData[$sCacheKey]['amount_transfer_net_exl_tax']	= $mAmoutTransferNetExlTax;

//			self::$_aCacheVersionPlaceholderData[$sCacheKey]['amount_credit'] = $mAmoutCredit;
			self::$_aCacheVersionPlaceholderData[$sCacheKey]['amount_finalpay'] = $mAmoutFinalpay;
			self::$_aCacheVersionPlaceholderData[$sCacheKey]['amount_prepay'] = $mAmoutPrepay;
			self::$_aCacheVersionPlaceholderData[$sCacheKey]['date_prepay']	= $mDatePrepay;
			self::$_aCacheVersionPlaceholderData[$sCacheKey]['date_finalpay'] = $mDateFinalpay;
			self::$_aCacheVersionPlaceholderData[$sCacheKey]['date_today'] = $mToday;
			self::$_aCacheVersionPlaceholderData[$sCacheKey]['amount_paid'] = $mAmoutPayed;


		} else {

			$mAmout	= $aCachedPlaceholder['amount'];
			$mAmoutNet = $aCachedPlaceholder['amount_agency'];
			$mAmoutProvision = $aCachedPlaceholder['amount_provision'];
			$mAmoutInitalcost = $aCachedPlaceholder['amount_initalcost'];
			$mAmoutCourse = $aCachedPlaceholder['amount_course'];
			$mAmoutCourseNet = $aCachedPlaceholder['amount_course_net'];
			$mAmoutAccommodation = $aCachedPlaceholder['amount_accommodation'];
			$mAmoutAccommodationNet = $aCachedPlaceholder['amount_accommodation_net'];
			$mAmoutTransfer	= $aCachedPlaceholder['amount_transfer'];
			$mAmoutTransferNet = $aCachedPlaceholder['amount_transfer_net'];

			$mAmoutExlTax = $aCachedPlaceholder['amount_exl_tax'];
			$mAmoutNetExlTax = $aCachedPlaceholder['amount_agency_exl_tax'];
			$mAmoutProvisionExlTax = $aCachedPlaceholder['amount_initalcost_exl_tax'];
			$mAmoutInitalcostExlTax = $aCachedPlaceholder['amount_provision_exl_tax'];
			$mAmoutCourseExlTax	= $aCachedPlaceholder['amount_course_exl_tax'];
			$mAmoutCourseNetExlTax = $aCachedPlaceholder['amount_course_net_exl_tax'];
//			$mAmountCourseDiscount = $aCachedPlaceholder['amount_course_discount'];
			$mAmoutAccommodationExlTax = $aCachedPlaceholder['amount_accommodation_exl_tax'];
			$mAmoutAccommodationNetExlTax = $aCachedPlaceholder['amount_accommodation_net_exl_tax'];
			$mAmoutTransferExlTax = $aCachedPlaceholder['amount_transfer_exl_tax'];
			$mAmoutTransferNetExlTax = $aCachedPlaceholder['amount_transfer_net_exl_tax'];

			//$mAmoutCredit = $aCachedPlaceholder['amount_credit'];
			$mAmoutFinalpay	= $aCachedPlaceholder['amount_finalpay'];
			$mAmoutPrepay = $aCachedPlaceholder['amount_prepay'];
			$mDatePrepay = $aCachedPlaceholder['date_prepay'];
			$mDateFinalpay = $aCachedPlaceholder['date_finalpay'];
			$mToday	= $aCachedPlaceholder['date_today'];
			$mAmoutPayed = $aCachedPlaceholder['amount_paid'];

		}

		$sValue = null;

		switch ($sPlaceholder) {
			/*
			 * Beträge
			 */
			case 'pdf_amount':
				$sValue = $mAmout;
				break;
			case 'pdf_amount_net':
				$sValue = $mAmoutNet;
				break;
			case 'pdf_amount_provison':
				$sValue = $mAmoutProvision;
				break;
			case 'pdf_amount_initalcost':
				$sValue = $mAmoutInitalcost;
				break;
			/*
			 * Beträge inc. Steuern
			 */
			case 'pdf_amount_incl_vat':
				$sValue = $mAmout;
				break;
			case 'pdf_amount_net_incl_vat':
				$sValue = $mAmoutNet;
				break;
			case 'pdf_amount_vat':
				$fAmountVat = $oVersion->getOnlyTaxAmount();
				$sAmountVat	= Ext_Thebing_Format::Number($fAmountVat, $iCurrencyId, $iSchoolId);
				$sValue = $sAmountVat;
				break;
			case 'pdf_amount_provison_incl_vat':
				$sValue = $mAmoutProvision;
				break;
			case 'pdf_amount_initalcost_incl_vat':
				$sValue = $mAmoutInitalcost;
				break;
			case 'pdf_amount_course_incl_vat':
				$sValue = $mAmoutCourse;
				break;
			case 'pdf_amount_course_net_incl_vat':
				$sValue = $mAmoutCourseNet;
				break;
//			case 'pdf_amount_course_discount':
//					$sValue = $mAmountCourseDiscount;
//				break;
			case 'pdf_amount_accommodation_incl_vat':
				$sValue = $mAmoutAccommodation;
				break;
			case 'pdf_amount_accommodation_net_incl_vat':
				$sValue = $mAmoutAccommodationNet;
				break;
			case 'pdf_amount_transfer_incl_vat':
				$sValue = $mAmoutTransfer;
				break;
			case 'pdf_amount_transfer_net_incl_vat':
				$sValue = $mAmoutTransferNet;
				break;
			/*
			 * Beträge excl. Steuern
			 */
			case 'pdf_amount_excl_vat':
				$sValue = $mAmoutExlTax;
				break;
			case 'pdf_amount_net_excl_vat':
				$sValue = $mAmoutNetExlTax;
				break;
			case 'pdf_amount_provison_excl_vat':
				$sValue = $mAmoutProvisionExlTax;
				break;
			case 'pdf_amount_initalcost_excl_vat':
				$sValue = $mAmoutInitalcostExlTax;
				break;
			case 'pdf_amount_course_excl_vat':
				$sValue = $mAmoutCourseExlTax;
				break;
			case 'pdf_amount_course_net_excl_vat':
				$sValue = $mAmoutCourseNetExlTax;
				break;
			case 'pdf_amount_accommodation_excl_vat':
				$sValue = $mAmoutAccommodationExlTax;
				break;
			case 'pdf_amount_accommodation_net_excl_vat':
				$sValue = $mAmoutAccommodationNetExlTax;
				break;
			case 'pdf_amount_transfer_excl_vat':
				$sValue = $mAmoutTransferExlTax;
				break;
			case 'pdf_amount_transfer_net_excl_vat':
				$sValue = $mAmoutTransferNetExlTax;
				break;
			/*
			 * Sontige
			 */
			case 'invoicenumber_pdf':
			case 'pdf_document_number':
				$sValue = $sDocumentNumber;
				break;
			case 'pdf_main_document_number':
				$sValue = $sMainDocumentNumber;
				break;
			case 'pdf_today':
				$sValue = $mToday;
				break;
			case 'pdf_amount_prepay':
				$sValue = $mAmoutPrepay;
				break;
//			case 'pdf_amount_reminder':
//					$sValue = $mAmoutReminder;
//				break;
//			case 'pdf_amount_credit':
//					$sValue = $mAmoutCredit;
//				break;
			case 'pdf_amount_finalpay':
				$sValue = $mAmoutFinalpay;
				break;
			case 'pdf_amount_paid':
				$sValue = $mAmoutPayed;
				break;
			case 'pdf_date_prepay':
				$sValue = $mDatePrepay;
				break;
			case 'pdf_date_finalpay':
				$sValue = $mDateFinalpay;
				break;
			case 'user_password':
				$oCustomer = $this->_oInquiry->getCustomer();
				$oLoginData = $oCustomer->getLoginData(true);
				$sValue = $oLoginData->generatePassword();
				break;
			case 'booker_password':
				$oCustomer = $this->_oInquiry->getBooker();
				$oLoginData = $oCustomer->getLoginData(true);
				$sValue = $oLoginData->generatePassword();
				break;
			default:
				$sValue = null;
				$aHookData = [
					'placeholder' => $sPlaceholder,
					'pdf' => true,
					'value' => &$sValue,
					'class' => $this,
				];

				System::wd()->executeHook('ts_inquiry_placeholder_replace', $aHookData);

				break;
		}

		return $sValue;
	}

	public function replace($sText = '', $iPlaceholderLib = 1, $iOptionalId = 0) {

		$sText = parent::replace($sText, $iPlaceholderLib, $iOptionalId);

		$iMatches = preg_match_all('@\{(if )?(((?!pdf\_)[^/][^ \|}{]*)(\|([^\|}{:]+)(\:(.+?))?)?)(\s*(&lt;|&gt;|eq|neq|==|<|>|!=)?\s*(.*?))?\}@ims', $sText, $aMatches);
		$iMatchPdf = preg_match_all('@\{(if )?((pdf\_[^/][^ \|}{]*)(\|([^\|}{:]+)(\:(.+?))?)?)(\s*(&lt;|&gt;|eq|neq|==|<|>|!=)\s*(.*?))?\}@ims', $sText, $aMatches);

		// Smarty erst nachträglich ersetzen
		if(
			//$this->bInitialReplace === false &&
			$iMatches > 0 &&
			empty($iMatchPdf)
		) {
			$this->replaceSmarty($sText);
		}

		return $sText;
	}

	public function replaceForPdf($oDocument, $oVersion, $sText) {

		/*
		 * Da es für das nachträgliche Ersetzen von Platzhaltern noch nicht vernünftiges gibt, baue ich das hier ein
		 * für das Passwort.
		 * Notwendig bei Mehrfachgenerierung eines Dokumentes mit diesem Platzhalter.
		 * Das muss hier oben stehen, da es der einzige übrige Platzhalter sein und nicht erkannt wird wegen den
		 * eckigen Klammern.
		 */
		$sText = $this->replaceFinalOutput($sText);

		if(strpos($sText, '[user_password]') !== false) {
			$oCustomer = $this->_oInquiry->getCustomer();
			$oLoginData = $oCustomer->getLoginData(true);
			$newPassword = $oLoginData->generatePassword();
			$sText = str_replace('[user_password]', $newPassword, $sText);
		}

		if(strpos($sText, '[booker_password]') !== false) {
			$oBooker = $this->_oInquiry->getBooker();
			$oLoginData = $oBooker->getLoginData(true);
			$newPassword = $oLoginData->generatePassword();
			$sText = str_replace('[booker_password]', $newPassword, $sText);
		}

		$aPlaceholderList = $this->_getAllPlaceholders($sText);

		if(empty($aPlaceholderList)){
			return $sText;
		}

		// Loop: Neue Zahlungsbedigungen
		$sText = preg_replace_callback(
			'@\{pdf_start_loop_paymentterms\|document:(\d+)}(.*?)\{pdf_end_loop_tuition_paymentterms\|document:\d+}@ims',
			[$this, '_helperPdfReplacePaymentTerms'],
			$sText
		);

		$bUnfoundPlaceholders = false;
		foreach((array)$aPlaceholderList as $sPlaceholder => $aPlaceholders){
			foreach((array)$aPlaceholders as $sPlaceholderString => $aPlaceholderData){

				$iDocument = 0;

				if(
					$aPlaceholderData['modifier'] === 'document' &&
					$aPlaceholderData['parameter'] > 0
				) {
					$oDocument = Ext_Thebing_Inquiry_Document::getInstance((int)$aPlaceholderData['parameter']);
				}

				$aData = [];
				$aData['placeholder'] = $sPlaceholder;
				$aData['complete'] = $aPlaceholderData['complete'];
				$aData['modifier'] = $aPlaceholderData['modifier'];
				$this->_oDocument		= $oDocument;
				$this->_oVersion		= $oVersion;

				$sValue = $this->_helperReplaceForPdf($sText, $aData);

				if($sValue !== null) {
					$sText = str_replace('{'.$aPlaceholderData['complete'].'}', $sValue, $sText);
				} else {
					$bUnfoundPlaceholders = true;
				}

			}
		}

		if($bUnfoundPlaceholders === true) {
			$this->replaceSmarty($sText);
		}
		return $sText;

	}

	public function replaceFinalOutput($text): string
	{
		// Findet [user_password] und [user_password:1234]
		preg_match_all('/\[user_password(?::(\d+))?\]/', $text, $matches);

		if (!empty($matches[0])) {

			$this->addMonitoringEntry('user_password');

			// Sichergehen dass nur Kontakte betroffen sind die irgendwie mit der Buchung zusammenhängen (Gruppen)
			if ($this->_oInquiry->hasGroup()) {
				$inquiries = $this->_oInquiry->getGroup()->getMembers();
				$possibleContactIds = array_map(fn ($inquiry) => $inquiry->getCustomer()->id, $inquiries);
			} else {
				$possibleContactIds = [$this->_oInquiry->getCustomer()->id];
			}

			foreach ($matches[0] as $index => $placeholder) {

				if (str_contains($placeholder, ':')) {
					$contactId = (int)$matches[1][$index];
					$contact = \Ext_TS_Contact::getInstance($contactId);
				} else {
					$contact = $this->_oInquiry->getCustomer();
				}

				if ($contact->exist() && in_array($contact->id, $possibleContactIds)) {
					$loginData = $contact->getLoginData(true);
					$password = $loginData->generatePassword();
					if(!empty($password)) {
						$text = str_replace($placeholder, $password, $text);
					}
				}
			}
		}
		$matches = [];
		preg_match_all('/\[booker_password]/', $text, $matches);
		if (!empty($matches[0])) {
			$contact = $this->_oInquiry->getBooker();
			if($contact) {
				$loginData = $contact->getLoginData(true);
				$password = $loginData->generatePassword();
				if(!empty($password)) {
					$text = str_replace('[booker_password]', $password, $text);
				}
			}
		}

		return $text;
	}

	public function setDocumentVersion(Ext_Thebing_Inquiry_Document_Version $version) {
		$this->_oDocument = $version->getDocument();
		$this->_oVersion = $version;
	}

	protected function _helperReplaceVars($sText, $iOptionalId = 0) {

		$sText = preg_replace_callback('@\{start_loop_group_members\}(.*?)\{end_loop_group_members\}@ims',array( $this, "_helperReplaceGroupLoop"),$sText);

		$sText = preg_replace_callback('@\{start_loop_courses\}(.*?)\{end_loop_courses\}@ims',array( $this, "_helperReplaceCourseLoop"),$sText);

		$sText = preg_replace_callback('@\{start_loop_accommodations\}(.*?)\{end_loop_accommodations\}@ims',array( $this, "_helperReplaceAccommodationLoop"),$sText);

		$sText = preg_replace_callback('@\{start_loop_insurances\}(.*?)\{end_loop_insurances\}@ims',array( $this, "_helperReplaceInsurancesLoop"),$sText);

		$sText = preg_replace_callback('@\{start_loop_documents\}(.*?)\{end_loop_documents\}@ims',array( $this, "_helperReplaceDocumentLoop"),$sText);

		$sText = preg_replace_callback('@\{start_loop_accommodation_allocations\}(.*?)\{end_loop_accommodation_allocations\}@ims',array( $this, "_helperReplaceAccommodationAllocationLoop"),$sText);

		$sText = preg_replace_callback('@\{start_loop_roommate\}(.*?)\{end_loop_roommate\}@ims',array( $this, "_helperReplaceRoommateLoop"),$sText);

		$sText = preg_replace_callback('@\{start_loop_individual_transfer\}(.*?)\{end_loop_individual_transfer\}@ims',array( $this, "_helperReplaceTransferLoop"),$sText);

		$sText = preg_replace_callback('@\{start_loop_course_weeks}(.*?)\{end_loop_course_weeks}@ims',array( $this, "_helperReplaceCourseWeeksLoop"),$sText);

		$sText = preg_replace_callback('@\{start_first_course_week}(.*?)\{end_first_course_week}@ims',array($this, "_helperReplaceFirstCourseWeek"),$sText);

		$sText = preg_replace_callback('@\{start_latest_course_week}(.*?)\{end_latest_course_week}@ims',array($this, "_helperReplaceLatestCourseWeek"),$sText);

		$sText = preg_replace_callback('@\{start_loop_tuition_blocks}(.*?)\{end_loop_tuition_blocks}@ims',array( $this, "_helperReplaceTuitionAllocationLoop"),$sText);

		// Standardschleifen die überall verfügbar sind
		$sText = parent::_helperReplaceVars($sText, $iOptionalId);

		return $sText;

	}

	protected function _helperPdfReplacePaymentTerms($aText) {

		$sText = '';
		$aPaymentTerms = $this->_oVersion->getPaymentTerms();

		foreach($aPaymentTerms as $oPaymentTerm) {
			$sDate = Ext_Thebing_Format::LocalDate($oPaymentTerm->date, $this->_oSchool->id);
			$sAmount = Ext_Thebing_Format::Number($oPaymentTerm->amount, $this->_oInquiry->getCurrency(), $this->_oSchool->id);
			$sTmp = str_replace('{pdf_paymentterm_type|document:'.$aText[1].'}', $oPaymentTerm->type, $aText[2]);
			$sTmp = str_replace('{pdf_paymentterm_date|document:'.$aText[1].'}', $sDate, $sTmp);
			$sTmp = str_replace('{pdf_paymentterm_amount|document:'.$aText[1].'}', $sAmount, $sTmp);
			$sText .= $sTmp;
		}

		return $sText;

	}

	protected function _helperReplaceRoommateLoop($aText){

		$this->addMonitoringEntry('start_loop_roommate');

		$sText			= '';

		$oInquiry		= $this->_oInquiry;

		$oAccommodationProvider = null;
//		if($this->_oAllocation instanceof Ext_Thebing_Accommodation_Allocation) {
//			// Wenn eine Unterkunftszuweisung vorhanden ist, dann sollen hier nur zusammenreisende Kunden erscheinen,
//			// die dem selben Unterkunftsanbieter zugewiesen sind
//			$oAccommodationProvider = $this->_oAllocation->getAccommodationProvider();
//		}

		$aRoommates		= $oInquiry->getRoommates(true, $oAccommodationProvider);

//		$oFormat		= new Ext_Thebing_Gui2_Format_Date(false, $oSchool->id);
//
//		$oDate			= new WDDate();

		foreach((array)$aRoommates as $aRoommateData){
			$sTempText = $aText[1];

			$oContact = Ext_TS_Inquiry_Contact_Traveller::getInstance($aRoommateData['customer_id']);
			//$oAccommodationAllocation = Ext_Thebing_Accommodation_Allocation::getInstance($aRoommateData['accommodation_allocation_id']);

//			// Format umwandeln
//			$oDate->set($aRoommateData['overlapping_start'], WDDate::DB_DATETIME);
//			$sOverlappingStart = $oDate->get(WDDate::DB_DATE);
//
//			$oDate->set($aRoommateData['overlapping_end'], WDDate::DB_DATETIME);
//			$sOverlappingEnd = $oDate->get(WDDate::DB_DATE);
//
//			// Overlapping Platzhalter zusammenbauen
//			$sOverlapping =  $aRoommateData['overlapping_days'] . ' ' . $this->getLanguageObject()->translate('Tage');
//			$sOverlapping .= ' (' . $oFormat->formatByValue($sOverlappingStart);
//			$sOverlapping .= ' - ' . $oFormat->formatByValue($sOverlappingEnd) . ')';
//
//			$sTempText = str_replace('{allocation_overlapping}', $sOverlapping, $sTempText);

			// Neues Platzhalter-Objekt erzeugen, um Platzhalter für diesen Roommate zu ersetzen
			$oTempPlaceholder = new Ext_Thebing_Inquiry_Placeholder($aRoommateData['inquiry_id'], $aRoommateData['customer_id']);

			// @todo Sprache setzen
			$oTempPlaceholder->sTemplateLanguage = $this->sTemplateLanguage;

			$sText .= $oTempPlaceholder->replace($sTempText);
		}


		return $sText;

	}

	/**
	 * Schleife läuft alle individuallen Transfere durch
	 * @param type $aText
	 * @return type
	 */
	protected function _helperReplaceTransferLoop($aText){

		$this->addMonitoringEntry('start_loop_individual_transfer');

		$oInquiry = $this->_oInquiry;

		// Alle I
		$aTransfers = $oInquiry->getTransfers('additional');

		$sText = "";

		foreach((array)$aTransfers as $oTransfer){
			$this->_setAdditionalTransfer($oTransfer);
			$sText .= $this->_helperReplaceVars($aText[1]);
		}

		$this->_setAdditionalTransfer();

		return $sText;

	}

	protected function _helperReplaceAccommodationAllocationLoop($aText){

		$this->addMonitoringEntry('start_loop_accommodation_allocations');

		$oInquiry = $this->_oInquiry;
		$iInquiryAccommodation = 0;
		if($this->_oJourneyAccommodation instanceof Ext_TS_Service_Interface_Accommodation){
			$iInquiryAccommodation = $this->_oJourneyAccommodation->id;
		}

		$aAllocations = Ext_Thebing_Allocation::getAllocationByInquiryId($oInquiry->id, $iInquiryAccommodation, true);

		$sText = "";

		foreach((array)$aAllocations as $aAllocation){
			$this->_aAccommodationAllocation = $aAllocation;
			$this->_oFamily = Ext_Thebing_Accommodation::getInstance((int)$aAllocation['family_id']);
			$sText .= $this->_helperReplaceVars($aText[1]);
		}

		$this->_aAccommodationAllocation = array();
		$this->_oFamily  = null;

		return $sText;

	}

	protected function _helperReplaceDocumentLoop($aText) {

		$this->addMonitoringEntry('start_loop_documents');

		$oInquiry = $this->_oInquiry;

		$sText = "";

		if(is_object($oInquiry)){

			$aDocuments = $oInquiry->getDocuments('invoice', true, true);

			foreach((array)$aDocuments as $oDocument){
				$this->_oDocument = $oDocument;
				$sText .= $this->_helperReplaceVars($aText[1]);
			}

		}

		// wieder reseten damit nicht schleifen platzhalter normal ersetzt werden
		$this->_oDocument = null;

		return $sText;

	}

	protected function _helperReplaceGroupLoop($aText) {

		$this->addMonitoringEntry('start_loop_group_members');

		$oOriginalInquiry = $this->_oInquiry;
		$oOriginalCustomer = $this->_oCustomer;
		$aOriginalAccommodationAllocation = $this->_aAccommodationAllocation;

		$sText = '';
		$aInquiries = array();

		if(is_object($oOriginalInquiry)){
			if($oOriginalInquiry->hasGroup()){
				$oGroup = $oOriginalInquiry->getGroup();
				$aInquiries = $oGroup->getMembers();
			}

			foreach((array)$aInquiries as $oMember){

//				if($oMember instanceof Ext_TS_Inquiry) {
				$this->_oInquiry	= $oMember;
				$this->_oCustomer	= $oMember->getCustomer();
//				} else {
//					//Bei Anfragen nicht das Inquiry Objekt tauschen, das ist da einmalig
//
//					//Bei getMembers() kommt bei Anfragen eine Liste von Contacts
//					$this->_oCustomer = $oMember;
//
//					//Gruppenmitglied in das Angebot setzen
//					$this->_oInquiry->setTraveller($oMember);
//				}

				$aAllocations = Ext_Thebing_Allocation::getAllocationByInquiryId($this->_oInquiry->id, 0, true);
				$this->_aAccommodationAllocation = reset($aAllocations);

				$this->_bGroupLoop = true;
				$sText .= $this->_helperReplaceVars($aText[1]);

			}
		}

		$this->_bGroupLoop = false;
		$this->_oInquiry = $oOriginalInquiry;
		$this->_oCustomer = $oOriginalCustomer;
		$this->_aAccommodationAllocation = $aOriginalAccommodationAllocation;

		return $sText;

	}

	protected function _helperReplaceCourseLoop($aText) {

		$this->addMonitoringEntry('start_loop_courses');

		$oInquiry = $this->_oInquiry;

		$aCourses = $oInquiry->getCourses(false);

		$mInquiryCourseBefore	= $this->_oJourneyCourse;
		$mCourseBefore			= $this->_oCourse;

		$sText = "";
		if(!empty($aCourses)){
			foreach((array)$aCourses as $aCourse){
				$oJourneyCourse = $oInquiry->getServiceObject('course', $aCourse['id']);
				$this->_oJourneyCourse	= $oJourneyCourse;
				$this->_oCourse			= null;
				$this->_bCourseLoop     = true;
				$sText .= $this->_helperReplaceVars($aText[1], $aCourse['id']);
			}
		}

		//Wieder zurücksetzen auf die Werte vor dem Loop, wichtig bei Examen wo im construct der InquiryCourse und Kurs gesetzt wird
		$this->_oJourneyCourse	= $mInquiryCourseBefore;
		$this->_oCourse			= $mCourseBefore;
		$this->_bCourseLoop     = false;

		return $sText;
	}

	/**
	 * Kurswochen durchlaufen
	 *
	 * @param array $aText
	 */
	protected function _helperReplaceCourseWeeksLoop($aText)
	{
		$this->addMonitoringEntry('start_loop_course_weeks');

		$oJourneyCourse = $this->_getInquiryCourse();

		$sText = '';

		if($oJourneyCourse instanceof Ext_TS_Inquiry_Journey_Course)
		{
			$iWeeks			= $oJourneyCourse->weeks;
			
			$coursePeriod = new \Carbon\CarbonPeriod($oJourneyCourse->from, '1 week', $oJourneyCourse->until);

			foreach($coursePeriod as $i=>$coursePeriodDate) {
				$oJourneyCourse->iCurrentWeek = $i+1;//aktuelle Woche des Kurses setzen
				$sText .= $this->_helperReplaceVars($aText[1]);
			}

			$oJourneyCourse->iCurrentWeek = null;
		}
		else
		{
			throw new Exception('course week loop is only supported for journey course!');
		}

		return $sText;
	}

	/**
	 * Ersetzt Platzhalter im Kurszusammenhang
	 * @param array $aText
	 * @param array $aCourseData
	 * @param Ext_TS_Inquiry_Journey_Course $oJourneyCourse
	 * @return string
	 */
	protected function _helperReplaceCourseWeek($aText, $aCourseData, $oJourneyCourse) {

		$sText = '';

		// Kurs setzen und Platzhalter des Fake-Loops ersetzen
		if($oJourneyCourse instanceof Ext_TS_Inquiry_Journey_Course) {
			$oOriginalJourneyCourse = $this->_oJourneyCourse;
			$mOriginalCurrentWeek = $oJourneyCourse->iCurrentWeek;

			$this->_oJourneyCourse = $oJourneyCourse;

			// Richtige Woche (anhand spätestem Block über Zuweisung) setzen
			$aWeeks = $oJourneyCourse->getCourseWeeksWithDates();
			foreach($aWeeks as $iWeek => $oDate) {
				if($oDate == $aCourseData['week']) {
					$oJourneyCourse->iCurrentWeek = $iWeek;
				}
			}

			$sText = $this->_helperReplaceVars($aText[1]);

			$this->_oJourneyCourse = $oOriginalJourneyCourse;
			$oJourneyCourse->iCurrentWeek = $mOriginalCurrentWeek;
		}

		return $sText;
	}

	/**
	 * Erste Kurswoche durchlaufen (Fake-Loop)
	 * @param array $aText
	 * @return string
	 */
	protected function _helperReplaceFirstCourseWeek($aText) {

		$this->addMonitoringEntry('start_first_course_week');

		// Liefert den letzten Kurs (spätester Block über die Zuweisung)
		$aCourseData = $this->_oInquiry->getFirstJourneyCourseByAllocation();
		$oJourneyCourse = $aCourseData['course'];

		$sText = $this->_helperReplaceCourseWeek($aText, $aCourseData, $oJourneyCourse);

		return $sText;
	}

	/**
	 * Letzte Kurswoche durchlaufen (Fake-Loop)
	 * @param array $aText
	 * @return string
	 */
	protected function _helperReplaceLatestCourseWeek($aText) {

		$this->addMonitoringEntry('start_latest_course_week');

		// Liefert den letzten Kurs (spätester Block über die Zuweisung)
		$aCourseData = $this->_oInquiry->getLatestJourneyCourseByAllocation();
		$oJourneyCourse = $aCourseData['course'];

		$sText = $this->_helperReplaceCourseWeek($aText, $aCourseData, $oJourneyCourse);

		return $sText;
	}

	/**
	 * Klassenplanung Zuweisungen eines Kurses durchlaufen
	 *
	 * @param array $aText
	 */
	protected function _helperReplaceTuitionAllocationLoop($aText)
	{
		global $_VARS;

		$this->addMonitoringEntry('start_loop_tuition_blocks');

		$sText			= '';
		$oJourneyCourse = $this->_getInquiryCourse();

		if($oJourneyCourse instanceof Ext_TS_Inquiry_Journey_Course)
		{
			$oTuitionAllocationBefore	= $this->_oTuitionAllocation;
			$oJourneyCourseBefore		= $this->_oJourneyCourse;
			$oCourseBefore				= $this->_oCourse;

			// Wenn keine Woche (course_week_loop), dann muss diese aus dem Filter geholt werden… #5483
			// TODO $aFilter ist seit Jahren kaputt, aber es stört niemanden?
			$aFilter = array();
			if(
				$this->_oJourneyCourse->iCurrentWeek == null &&
				!empty($_VARS['filter']['week_filter'])
			) {
				$aFilter['`ktb`.`week`'] = $_VARS['filter']['week_filter'];
			}
			$weeks = $oJourneyCourse->getCourseWeeksWithDates();

			$allocations = $oJourneyCourse->getJoinedObjectChilds('tuition_blocks');
			@usort($allocations, array('Ext_Thebing_School_Tuition_Allocation', 'sortAllocationsByTime')); // @ wg. PHP-Bug: https://bugs.php.net/bug.php?id=50688
			// Eventuelle nicht gelöschte Zuweisungen ignorieren und wenn iCurrentWeek gesetzt,
			// als Filter benutzen um in einer course week loop nicht jedes mal alle allocations aufzurufen
			$allocations = array_filter($allocations, function($allocation) use ($weeks, $oJourneyCourse) {
				$block = $allocation->getBlock();
				return $block->active &&
					isset($weeks[$oJourneyCourse->iCurrentWeek]) &&
					$block->week == $weeks[$oJourneyCourse->iCurrentWeek]->format('Y-m-d');
			});

			foreach ($allocations as $allocation) {
				$this->setTuitionAllocation($allocation);
				$sText .= $this->_helperReplaceVars($aText[1]);
			}

			$this->_oTuitionAllocation	= $oTuitionAllocationBefore;
			$this->_oJourneyCourse		= $oJourneyCourseBefore;
			$this->_oCourse				= $oCourseBefore;
		}
		else
		{
			throw new Exception('block loop is only supported for journey course!');
		}

		return $sText;
	}


	protected function _helperReplaceAccommodationLoop($aText) {

		$this->addMonitoringEntry('start_loop_accommodations');

		$oInquiry = $this->_oInquiry;

		$aAccommdations = $oInquiry->getAccommodations(false, false);
		$sText = '';

		foreach($aAccommdations as $aAccommdation) {
			$oJourneyAccommocation = $oInquiry->getServiceObject('accommodation', $aAccommdation['id']);
			$this->_oJourneyAccommodation = $oJourneyAccommocation;
			$sText .= $this->_helperReplaceVars($aText[1], $aAccommdation['id']);
		}

		$this->_oJourneyAccommodation = null;
		$this->_oJourneyCourse = null;

		return $sText;
	}

	protected function _helperReplaceInsurancesLoop($aText){
		$oInquiry = $this->_oInquiry;

		$this->addMonitoringEntry('start_loop_insurances');

		$aInsurances = $oInquiry->getInsurances(true);

		$sText = "";

		if(!empty($aInsurances)){
			foreach((array) $aInsurances as $oInsurance){
				$this->oJourneyInsurance = $oInsurance;
				$sText .= $this->_helperReplaceVars($aText[1], $oInsurance->id);
			}
		}

		$this->oJourneyInsurance = null;

		return $sText;
	}

	/**
	 * PDF Templates
	 */
	public function searchPlaceholderValue($sField, $iOptionalParentId, $aPlaceholder=array()) {
		global $_VARS;

		$sField					= strtolower($sField);

		//Inquiry Object muss vorhanden sein
		$oInquiry				= $this->_oInquiry;
		// Schulobjekt
		$oSchool				= $this->_oSchool;

		// Transferobjecte
		$oTransferArrival = $this->_oTransferArrival;
		$oTransferDeparture = $this->_oTransferDeparture;
		$oTransferAdditional = $this->_oTransferAdditional;


		if(
			!is_object($oInquiry) ||
			$oInquiry->id <= 0
		) {
			//Muss auch gehen wenn nur ein Customer angegeben ist
			return false;
		}

		//Kundenobject holen
		if(is_object($this->_oCustomer)){
			$oContact = $this->_oCustomer;
		} else {
			$oContact = $oInquiry->getCustomer();
		}

		// Platzhalter die zu zu einer bestimmten Familie ersetzt werden
		// aber es noch KEINE aktuelle Familie gibt. Die gibt es wenn die Schleife der Zuweisungen durchgegangen wird!
//		if(
//			$this->_oAllocation instanceof Ext_Thebing_Accommodation_Allocation &&
//			is_null($this->_oFamily)
//		){
//			$oFamily = $this->_oAllocation->getAccommodationProvider();
//			if($oFamily instanceof Ext_Thebing_Accommodation){
//				$this->_oFamily = $oFamily;
//			}else{
//				$this->_oFamily = NULL;
//			}
//
//		}

		// Es wird ein Kunde benötigt
		if(!is_object($oContact)) {
			return false;
		}

		// Gruppe (falls vorhanden)
		$oGroup = $oInquiry->getGroup();

		$mAdditional = null;
		$sFormat = false;
		$bNotYetFound = false;

		if($this->_iSchoolForFormat > 0) {
			$iSchoolForFormat = $this->_iSchoolForFormat;
		} else {
			$iSchoolForFormat = $oSchool->id;
		}

		$sModifier	= '';

		if(!empty($aPlaceholder['modifier']))
		{
			$sModifier = $aPlaceholder['modifier'];
		}

		//Kundensprache
		$sDisplayLanguage = $this->getLanguage();

		// PDF Platzhalter mit Modifier versehen falls Document angeben ist
		if(
			strpos($sField, 'pdf_') !== false
		){

			if(
				$this->_oDocument &&
				$this->_oDocument->id > 0 &&
				empty($sModifier)
			){
				$sField = $sField.'|document:'.(int)$this->_oDocument->id;
			} else if(!empty($sModifier)){
				$sField = $sField.'|'.$sModifier.':'.$aPlaceholder['parameter'];
			}

			$sField = '{'.$sField.'}';

			return $sField;

		} elseif($this->_getModifierLanguage($aPlaceholder, $this->_aLanguages)) {
			//Modifiersprache
			$sDisplayLanguage = $this->_getModifierLanguage($aPlaceholder, $this->_aLanguages);
		}

		$oLanguage = new Tc\Service\Language\Frontend($sDisplayLanguage);

		// Array for Yes/No Answers that should be compared with DB
		$aYesNo = array(
			0 => '',
			1 => $oLanguage->translate('Nein'),
			2 => $oLanguage->translate('Ja')
		);
		$aYesNo2 = array_slice($aYesNo, 1, 2);

		switch ($sField) {
			case 'amount_course_incl_vat':
			case 'amount_course_net_incl_vat':
			case 'amount_accommodation_incl_vat':
			case 'amount_accommodation_net_incl_vat':

				$iCurrencyId = $oInquiry->getCurrency();
				$aInvoiceTypes = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice');

				$fProformaAmount = 0;
				$bHasInvoice = false;

				$fAmountInclVatAll = 0;
				$aInquiryDocuments = $oInquiry->getDocuments('all', true, true);

				foreach($aInquiryDocuments as $oDocument) {

					if(!in_array($oDocument->type, $aInvoiceTypes)) {
						continue;
					}

					$sAmountType = 'brutto';
					if(
						$sField === 'amount_course_net_incl_vat' ||
						$sField === 'amount_accommodation_net_incl_vat'
					) {
						/* Sofern das Dokument ein Brutto Dokument ist und
						 * eine CreditNote existiert, müssen die Berechnungen
						 * auf der CreditNote ausgeführt werden. */
						$oCreditNote = $oDocument->getCreditNote();
						if(
							!$oDocument->isNetto() &&
							$oCreditNote !== null
						) {
							$oDocument = $oCreditNote;
						}
						$sAmountType = 'netto';
					}

					$oLastVersion = $oDocument->getLastVersion();

					$sItemType = 'course';
					if(
						$sField === 'amount_accommodation_incl_vat' ||
						$sField === 'amount_accommodation_net_incl_vat'
					) {
						$sItemType = 'accommodation';
					}
					$mAmount = $oLastVersion->getItemAmount($sItemType, array('amount_type' => $sAmountType, 'special' => true));

					$fAmountInclVatAll += $mAmount;

					// #6032 - Proformabetrag merken um diesen ggf. später wieder abzuziehen
					if(strpos($oDocument->type, 'proforma') !== false) {
						$fProformaAmount += $mAmount;
					} else {
						// Wenn es zu der Buchung eine Rechnung gibt, dürfen Proformabeträge nicht addiert werden
						$bHasInvoice = true;
					}

				}

				if($bHasInvoice) {
					// Proformabeträge wieder abziehen, sobald es eine Rechnung gibt
					$fAmountInclVatAll -= $fProformaAmount;
				}

				$mAmountInclVatAllFinal = Ext_Thebing_Format::Number($fAmountInclVatAll, $iCurrencyId, $oSchool->id);

				return $mAmountInclVatAllFinal;
//			case 'amount_course_discount':
//
//
//
//				#$oService = new Ext_Thebing_Inquiry_Document_Service_ServiceAmount(, 'course');
//				$oService->bOnlyDiscount = true;
//				return $oService->getAmount();
			case 'invoice_positions':
			case 'invoice_positions_net':

				if($this->_oDocument instanceof Ext_Thebing_Inquiry_Document){
					$oDocument = $this->_oDocument;
					$oVersion = $oDocument->getLastVersion();
				} else {
					$iDocument = Ext_Thebing_Inquiry_Document_Search::search($oInquiry->id, 'invoice');

					if($iDocument > 0){
						$oDocument = new Ext_Thebing_Inquiry_Document($iDocument);
						$oVersion = $oDocument->getLastVersion();
					} else {
						$oDocument = $oInquiry->newDocument();
						$oVersion = $oDocument->newVersion();
					}
				}

				$aData = (array)$oVersion->getItems($oDocument);

				$iWidth = 420;
				$bNet = false;
				if($sField == 'invoice_positions_net'){
					$bNet = true;
				}
				if($bNet){
					$iWidth = 300;
				}
				$sHtml = '';
				$sHtml .= '<table border="1">';
				$sHtml .= '<tr>';
				$sHtml .= '<th width="'.$iWidth.'">';
				$sHtml .= $oLanguage->translate('Position');
				$sHtml .= '</th>';
				$sHtml .= '<th width="60">';
				$sHtml .= $oLanguage->translate('Amount');
				$sHtml .= '</th>';
				if($bNet){
					$sHtml .= '<th width="60">';
					$sHtml .= $oLanguage->translate('Amount Provision');
					$sHtml .= '</th>';
					$sHtml .= '<th width="60">';
					$sHtml .= $oLanguage->translate('Amount Net');
					$sHtml .= '</th>';
				}

				$sHtml .= '</tr>';

				$iAmount = 0;
				$iAmountNet = 0;
				$iAmountProv = 0;

				$iDiscountAmountAll = 0;
				$iDiscountAmountNetAll = 0;
				$iDiscountAmountProvAll = 0;

				foreach($aData as $aPosition){
					if($aPosition['description'] == "" || $aPosition['calculate'] != 1){
						continue;
					}
					$iAmount += Ext_Thebing_Format::convertFloat($aPosition['amount'], $iSchoolForFormat);
					$iAmountNet += Ext_Thebing_Format::convertFloat($aPosition['amount_net'], $iSchoolForFormat);
					$iAmountProv += Ext_Thebing_Format::convertFloat($aPosition['amount_provision'], $iSchoolForFormat);



					$sHtml .= '<tr>';
					$sHtml .= '<td width="'.$iWidth.'">';
					$sHtml .= $aPosition['description'];
					$sHtml .= '</td>';
					$sHtml .= '<td width="60" style="text-align: right;">';
					$sHtml .= Ext_Thebing_Format::Number(Ext_Thebing_Format::convertFloat($aPosition['amount'], $iSchoolForFormat), $oInquiry->getCurrency(), $iSchoolForFormat);
					$sHtml .= '</td>';
					if($bNet){

						$sHtml .= '<td width="60" style="text-align: right;">';
						$sHtml .= Ext_Thebing_Format::Number(Ext_Thebing_Format::convertFloat($aPosition['amount_provision'], $iSchoolForFormat), $oInquiry->getCurrency(), $iSchoolForFormat);
						$sHtml .= '</td>';
						$sHtml .= '<td width="60" style="text-align: right;">';
						$sHtml .= Ext_Thebing_Format::Number(Ext_Thebing_Format::convertFloat($aPosition['amount_net'], $iSchoolForFormat), $oInquiry->getCurrency(), $iSchoolForFormat);
						$sHtml .= '</td>';
					}
					$sHtml .= '</tr>';


					$iDiscountPercent = Ext_Thebing_Format::convertFloat($aPosition['amount_discount'], $iSchoolForFormat);
					if($iDiscountPercent > 0 && $aPosition['amount'] > 0){
						// Discountspalte

						$fAmountDiscount = Ext_Thebing_Format::convertFloat((Ext_Thebing_Format::convertFloat($aPosition['amount'], $iSchoolForFormat) / 100) * $iDiscountPercent);
						$fAmountDiscountProvision = Ext_Thebing_Format::convertFloat((Ext_Thebing_Format::convertFloat($aPosition['amount_provision'], $iSchoolForFormat) / 100) * $iDiscountPercent);
						$fAmountDiscountnet = Ext_Thebing_Format::convertFloat((Ext_Thebing_Format::convertFloat($aPosition['amount_net'], $iSchoolForFormat) / 100) * $iDiscountPercent);

						$iDiscountAmountAll += $fAmountDiscount;
						$iDiscountAmountNetAll += $fAmountDiscountnet;
						$iDiscountAmountProvAll += $fAmountDiscountProvision;

						$sHtml .= '<tr>';
						$sHtml .= '<td width="'.$iWidth.'">';
						$sHtml .= $aPosition['description_discount'];
						$sHtml .= '</td>';
						$sHtml .= '<td width="60" style="text-align: right;">';
						$sHtml .= Ext_Thebing_Format::Number($fAmountDiscount * (-1), $oInquiry->getCurrency(), $iSchoolForFormat);
						$sHtml .= '</td>';
						if($bNet){

							$sHtml .= '<td width="60" style="text-align: right;">';
							$sHtml .= Ext_Thebing_Format::Number($fAmountDiscountProvision * (-1), $oInquiry->getCurrency(), $iSchoolForFormat);
							$sHtml .= '</td>';
							$sHtml .= '<td width="60" style="text-align: right;">';
							$sHtml .= Ext_Thebing_Format::Number($fAmountDiscountnet * (-1), $oInquiry->getCurrency(), $iSchoolForFormat);
							$sHtml .= '</td>';
						}
						$sHtml .= '</tr>';
					}
				}

				$sHtml .= '<tr>';
				$sHtml .= '<td width="'.$iWidth.'">';
				$sHtml .= $oLanguage->translate('Total Amount');
				$sHtml .= '</td>';
				$sHtml .= '<td width="60" style="text-align: right;">';
				$sHtml .= Ext_Thebing_Format::Number($iAmount - $iDiscountAmountAll, $oInquiry->getCurrency(), $iSchoolForFormat);
				$sHtml .= '</td>';
				if($bNet){

					$sHtml .= '<td width="60" style="text-align: right;">';
					$sHtml .= Ext_Thebing_Format::Number($iAmountNet - $iDiscountAmountNetAll, $oInquiry->getCurrency(), $iSchoolForFormat);
					$sHtml .= '</td>';
					$sHtml .= '<td width="60" style="text-align: right;">';
					$sHtml .= Ext_Thebing_Format::Number($iAmountProv - $iDiscountAmountProvAll, $oInquiry->getCurrency(), $iSchoolForFormat);
					$sHtml .= '</td>';
				}
				$sHtml .= '</tr>';
				$sHtml .= '</table>';
				return $sHtml;

			//KUNDE
			case 'customernumber':

				if(!$this->_oCustomer instanceof Ext_TS_Inquiry_Contact_Abstract){
					$this->_oCustomer = $this->_oInquiry->getCustomer();
				}

				return $this->_oCustomer->getCustomerNumber();
			case 'today':

				$sReturn = time();

				$sFormat = 'date';
				break;
			case 'date_entry':

				if($this->_oDocument instanceof Ext_Thebing_Inquiry_Document){
					$sReturn = $this->_oDocument->created;
				} else {
					$sReturn = $oInquiry->created;
				}
				$sFormat = 'date';

				// Wenn das Dokument noch nicht gespeichert wurde (Initialersetzung), dann nochmal den Platzhalter ausgeben.
				if(
					$sReturn === 0 &&
					$this->bInitialReplace === true
				) {
					$sReturn = '{date_entry}';
					$sFormat = null;
				}

				break;
			case 'firstname':
			case 'surname':
			case 'lastname':
			case 'age':
			case 'salutation':
			case 'birthdate':
			case 'gender':
			case 'nationality':
			case 'mothertongue':
			case 'address':
			case 'address_addon':
			case 'zip':
			case 'city':
			case 'state':
			case 'country':
			case 'phone_home':
			case 'phone_mobile':
			case 'phone_office':
			case 'fax':
			case 'email':
			case 'other':
				//AGENTUR
			case 'agency':
			case 'agency_number':
			case 'agency_user_firstname':
			case 'agency_abbreviation':
			case 'agency_address':
			case 'agency_zip':
			case 'agency_city':
			case 'agency_country':
			case 'agency_groups':
			case 'agency_category':
			case 'agency_tax_number':
			case 'agency_person':
			case 'agency_state':
			case 'agency_note':
			case 'agency_payment_terms':
			case 'agency_account_holder':
			case 'agency_bank_name':
			case 'agency_bank_code':
			case 'agency_account_number':
			case 'agency_swift':
			case 'agency_iban':
				//AGENTUR Mitarbeiter
			case 'agency_staffmember_salutation':
			case 'agency_staffmember_firstname':
			case 'agency_staffmember_surname':
			case 'agency_staffmember_email':
			case 'agency_staffmember_phone':
			case 'agency_staffmember_fax':
			case 'agency_staffmember_skype':
			case 'agency_staffmember_department':
			case 'agency_staffmember_responsability':
				//Schule
			case 'school_name':
			case 'school_abbreviation':
			case 'school_address':
			case 'school_address_addon':
			case 'school_zip':
			case 'school_city':
			case 'school_url':
			case 'school_phone2':
			case 'school_email':
			case 'school_country':
			case 'school_phone':
			case 'school_bank_name':
			case 'school_bank_code':
			case 'school_bank_address':
			case 'school_account_holder':
			case 'school_account_number':
			case 'school_iban':
			case 'school_bic':
			case 'school_swift':
			case 'school_fax':
				//Buchungsgebundene
			case 'social_security_number':
				//Dokumentbezogene
			case 'document_number':
			case 'main_document_number':
			case 'system_user_name':
			case 'system_user_firstname':
			case 'system_user_surname':
			case 'system_user_email':
			case 'system_user_phone':
			case 'system_user_fax':
			case 'document_type':
			//case 'document_date':
				return parent::searchPlaceholderValue($sField, $iOptionalParentId, $aPlaceholder);
			case 'user_name':
				if ($this->_oCustomer) {
					$oLoginData = $this->_oCustomer->getLoginData(true);
				} else {
					$oLoginData = $oContact->getLoginData(true);
				}
				return $oLoginData->nickname;
			case 'user_password':
				if ($this->_bGroupLoop && $this->_oCustomer) {
					return sprintf('[user_password:%d]', $this->_oCustomer->id);
				}
				return '[user_password]';
			case 'booker_user_name':
				return $oInquiry->getBooker()?->getLoginData(true)->nickname??'';
			case 'booker_password':
				return '[booker_password]';
			case 'billing_address':
			case 'company':
			case 'billing_zip':
			case 'billing_city':
			case 'billing_state':
				$address = $oInquiry->getBooker()?->getAddress('billing');
				$key = str_replace('billing_', '', $sField);
				return $address?->$key??'';
			case 'billing_country':
				$aCountry = Ext_Thebing_Data::getCountryList(true, false, $this->sTemplateLanguage);
				return (string)($aCountry[$oInquiry->getBooker()?->getAddress('billing')->country_iso] ?? '');
			case 'billing_name':
				return $oInquiry->getBooker()?->firstname??'';
			case 'billing_surname':
				return $oInquiry->getBooker()?->lastname??'';
			case 'customer_state':
				$sState = $oInquiry->getState();
				return $sState;
			case 'emergency_contact_person':
				$oEmergencyContact = $oInquiry->getEmergencyContact();
				return $oEmergencyContact->getName();
			case 'emergency_contact_phone':
				$oEmergencyContact = $oInquiry->getEmergencyContact();
				return (string)$oEmergencyContact->getFirstPhoneNumber();
			case 'emergency_contact_email':
				$oEmergencyContact = $oInquiry->getEmergencyContact();
				return $oEmergencyContact->getEmail();
			case 'sales_person':
				$oSalesPerson = $oInquiry->getSalesPerson();
				if($oSalesPerson) {
					return $oSalesPerson->getName();
				}
				return '';
			case 'amount_credit':
				return Ext_Thebing_Format::Number($oInquiry->amount_credit, $oInquiry->getCurrency(), $iSchoolForFormat);
			## START Visum
			case 'customer_need_visum':
				$oVisa = $oInquiry->getVisaData();
				if($oVisa->required){
					return $oLanguage->translate('Ja');
				}
				return $oLanguage->translate('Nein');
			case 'passnummer':
			case 'passport_number':
				$oVisa = $oInquiry->getVisaData();
				return $oVisa->passport_number;
			case 'passport_valid_from':
			case 'start_date':
				$oVisa = $oInquiry->getVisaData();
				$sReturn = $oVisa->passport_date_of_issue;
				$sFormat = 'date';
				break;
			case 'passport_valid_until':
			case 'expiration_date':
				$oVisa = $oInquiry->getVisaData();
				$sReturn = $oVisa->passport_due_date;
				$sFormat = 'date';
				break;
			case 'visa_id':
				$oVisa = $oInquiry->getVisaData();
				$sReturn = $oVisa->servis_id;
				break;
			case 'visa_mail_tracking_number':
				$oVisa = $oInquiry->getVisaData();
				$sReturn = $oVisa->tracking_number;
				break;
			case 'visa_status':
				$oVisa = $oInquiry->getVisaData();
				$oTmpVisa = $oVisa->getVisa();
				$sReturn = $oTmpVisa->name;
				break;
			case 'visa_valid_from':
				$oVisa = $oInquiry->getVisaData();
				$sReturn = $oVisa->date_from;
				$sFormat = 'date';
				break;
			case 'visa_valid_until':
				$oVisa = $oInquiry->getVisaData();
				$sReturn = $oVisa->date_until;
				$sFormat = 'date';
				break;
			case 'visa_valid_duration':
				$oVisa = $oInquiry->getVisaData();
				if(
					$sModifier === 'days' &&
					\Core\Helper\DateTime::isDate($oVisa->date_from, 'Y-m-d') &&
					\Core\Helper\DateTime::isDate($oVisa->date_until, 'Y-m-d')
				) {
					$dFrom = new DateTime($oVisa->date_from);
					$dUntil = new DateTime($oVisa->date_until);
					$sReturn = $dFrom->diff($dUntil)->days + 1;
				}
				break;
			## ENDE

			## START Gruppen
			case 'group_name':
				if($oGroup){
					return $oGroup->name;
				}
				return '';
				break;
			case 'group_number':
				if($oGroup){
					return $oGroup->number;
				}
				return '';
				break;
			case 'group_address':
				if($oGroup){
					return $oGroup->address;
				}
				return '';
				break;
			case 'group_customers':
				if($oGroup){
					$aGoupInquirys = $oGroup->getMembers();
					$sGroupCustomers = '';
					$t = 1;
					foreach($aGoupInquirys as $oGroupInquiry){

						if($oGroupInquiry instanceof Ext_TS_Inquiry)
						{
							$oGroupCustomer = $oGroupInquiry->getCustomer();
						}
						else
						{
							//Bei Anfragen ist der Mitglied dirent das Contact Objekt
							$oGroupCustomer = $oGroupInquiry;
						}

						$sGroupCustomers .= $oGroupCustomer->lastname." ".$oGroupCustomer->firstname;
						if($t < count($aGoupInquirys)){
							$sGroupCustomers .= ", ";
						}
						$t++;
					}
					return $sGroupCustomers;
				}
				return '';
				break;
			case 'group_city':
				if($oGroup){
					return $oGroup->city;
				}
				return '';
				break;
			case 'group_zip':
				if($oGroup){
					return $oGroup->plz;
				}
				return '';
				break;
			case 'group_country':
				if($oGroup){
					$aCountries	= Ext_Thebing_Data::getCountryList(true, true);
					return $aCountries[$oGroup->country];
				}
				return '';
				break;
			case 'group_address_addon':
				if($oGroup){
					return $oGroup->address_addon;
				}
				return '';
				break;
			case 'group_state':
				if($oGroup){
					return $oGroup->state;
				}
				return '';
				break;
			case 'group_count_member':
				if($oGroup){
					return $oGroup->countAllMembers();
				}
				return '';
				break;
			case 'group_count_leader':
				if($oGroup){
					return $oGroup->countGuides();
				}
				return '';
				break;
			case 'group_count_member_exkl_leader':
			case 'group_count_member_excl_leader':
				if($oGroup){
					return $oGroup->countAllMembers() - $oGroup->countGuides();
				}
				return '';
				break;
			case 'group_contact_firstname':
				if($oGroup){
					$oContactContact = Ext_TS_Inquiry_Contact_Traveller::getInstance($oGroup->contact_id);
					return $oContactContact->firstname;
				}
				return '';
				break;
			case 'group_contact_surname':
				if($oGroup){
					$oContactContact = Ext_TS_Inquiry_Contact_Traveller::getInstance($oGroup->contact_id);
					return $oContactContact->lastname;
				}
				return '';
				break;
			## ENDE

			case 'profession':
				return $oInquiry->profession;
			case 'comment_transfer_arr':
			case 'arrival_comment':
				$sComment = '';
				if($oTransferArrival instanceof Ext_TS_Service_Interface_Transfer) {
					$sComment = (string)$oTransferArrival->comment;
				}
				return $sComment;
				break;
			case 'comment_transfer_dep':
			case 'departure_comment':
				$sComment = '';
				if($oTransferDeparture instanceof Ext_TS_Service_Interface_Transfer) {
					$sComment = (string)$oTransferDeparture->comment;
				}
				return $sComment;
				break;
			//KURS
			case 'course':
			case 'course_abbreviation':
				$oCourse = $this->_getCourse($sDisplayLanguage);

				if($sField === 'course_abbreviation') {
					return $oCourse->getShortName();
				}

				return $oCourse->getName($sDisplayLanguage);
			case 'course_language':
				$oJourneyCourse = $this->_getInquiryCourse();
				if($oJourneyCourse instanceof Ext_TS_Inquiry_Journey_Course) {
					return $oJourneyCourse->getCourseLanguageName($sDisplayLanguage);
				}
				return '';
			case 'course_week':
				$oJourneyCourse = $this->_getInquiryCourse();
				if($oJourneyCourse instanceof Ext_TS_Inquiry_Journey_Course)
				{
					return $oJourneyCourse->iCurrentWeek;
				}
				break;
			case 'course_weeks':
				$oJourneyCourse = $this->_getInquiryCourse();
				return $oJourneyCourse->weeks;

			case 'week_date_from':

				$oFormatDate = new Ext_Thebing_Gui2_Format_Date();

				$oWeekDate = $this->_getInquiryCourseWeekDate();

				if($oWeekDate !== false) {
					return $oFormatDate->format($oWeekDate->format('Y-m-d'));
				}

				return '';
			case 'attendance_filter_week':

				$oWeekDate = $this->_getInquiryCourseWeekDate();

				if($oWeekDate !== false) {

					// Einzelne Woche oder Zeitraum
					if(isset($_VARS['filter']['week_filter'])) {
						$oFilterFrom = new DateTime($_VARS['filter']['week_filter']);
						$oFilterUntil = new DateTime($_VARS['filter']['week_filter']);
					} elseif(
						isset($_VARS['filter']['week_from_filter']) &&
						isset($_VARS['filter']['week_until_filter'])
					) {
						$oFilterFrom = new DateTime($_VARS['filter']['week_from_filter']);
						$oFilterUntil = new DateTime($_VARS['filter']['week_until_filter']);
					}

					if(
						$oFilterFrom &&
						$oFilterUntil
					) {
						if(
							$oWeekDate >= $oFilterFrom &&
							$oWeekDate <= $oFilterUntil
						) {
							return 1;
						}
					}

				}

				return 0;
			// Liefert den aktuellen Status der Kurswoche
			case 'course_week_status':
				$oJourneyCourse = $this->_getInquiryCourse();

				if(!$oJourneyCourse instanceof Ext_TS_Inquiry_Journey_Course) {
					return '';
				}

				$status = $oJourneyCourse->getTuitionCourseWeekStatus();

				return (string)$status;

			case 'course_week_is_first':
				$oJourneyCourse = $this->_getInquiryCourse();

				if(!$oJourneyCourse instanceof Ext_TS_Inquiry_Journey_Course) {
					return '';
				}

				if($oJourneyCourse->iCurrentWeek != null) {
					// Im Wochenloop ist das aktuelle Datum das Datum des Durchlaufs!
					// Die aktuelle Kurswoche wird durch den Loop gesetzt
					$iCourseWeekIterator = (int)$oJourneyCourse->iCurrentWeek;
				} else {
					// Aktuelles Datum ist tatsächliches Datum
					// Aktuelle Kurswoche wird anhand dieses Datums ermittelt
					$iCourseWeekIterator = (int)$oJourneyCourse->getTuitionIndexValue('current_week', new \WDDate());
				}

				return $iCourseWeekIterator === 1;

			case 'course_category':
				$oCourse		= $this->_getCourse($sDisplayLanguage);

				$sCategoryName	= '';
				if($oCourse instanceof Ext_Thebing_Course_Util) {
					$oCourse = $oCourse->getCourseObject();
				}

				if($oCourse instanceof Ext_Thebing_Tuition_Course){
					$oCategory		= $oCourse->getCategory();

					$sCategoryName	= $oCategory->getName($sDisplayLanguage);
				}

				return $sCategoryName;
			case 'course_max_students':
				$oCourse = $this->_getCourse($sDisplayLanguage);
				return $oCourse->maximum_students;
			case 'date_course_start':
				$sFormat = 'date';

				$oJourneyCourse = $this->_getInquiryCourse();
				$sFrom			= $oJourneyCourse->getFrom();

				if(WDDate::isDate($sFrom, WDDate::DB_DATE)) {
					$oDate = new WDDate($sFrom, WDDate::DB_DATE);
				} else {
					return '';
				}

				$sReturn = $oDate->get(WDDate::TIMESTAMP);

				break;
			case 'date_course_end':
				$sFormat = 'date';

				$oJourneyCourse = $this->_getInquiryCourse();
				$sUntil			= $oJourneyCourse->getUntil();

				if(WDDate::isDate($sUntil, WDDate::DB_DATE)) {
					$oDate = new WDDate($sUntil, WDDate::DB_DATE);
				}else{
					return '';
				}

				$sReturn = $oDate->get(WDDate::TIMESTAMP);

				break;
			case 'date_first_course_start':

				$sFormat = 'date';
				// DB-Date Format damit man das in Abfragen verwenden kann
				$sReturn = $this->_oInquiry->getFirstCourseStart(false);

				break;
			case 'date_last_course_end':

				$sFormat = 'date';
				// DB-Date Format damit man das in Abfragen verwenden kann
				$sReturn = $this->_oInquiry->getLastCourseEnd(false);

				break;
			case 'total_course_weeks':
			case 'total_course_weeks_absolute':
				if($oInquiry instanceof Ext_TS_Inquiry) {
					$sReturn = $oInquiry->getTuitionIndexValue('total_course_weeks');
				}
				$sReturn = $sReturn ?? 0;
				break;
			case 'total_course_weeks_relative':
				if($oInquiry instanceof Ext_TS_Inquiry) {
					$sReturn = $oInquiry->getTuitionIndexValue('total_course_duration');
				}
				break;
			case 'lessons_per_week':

				$iSchool = $oSchool->id;

				$oCourse = $this->_getCourse($sDisplayLanguage);

				if($oCourse instanceof Ext_Thebing_Course_Util) {
					$oCourse = $oCourse->getCourseObject();
				}

				if(
					$oCourse instanceof Ext_Thebing_Tuition_Course &&
					!$oCourse->isPerUnitCourse()
				) {
					if ($oCourse->isCombinationCourse()) {
						$aLessons = array_map(fn ($oCourseTmp) => $oCourseTmp->getLessons(), $oCourse->getChildCourses());
					} else {
						$aLessons = [$oCourse->getLessons()];
					}

					$aLessonCount = array_map(fn (\TsTuition\Dto\CourseLessons $lessons) => \Illuminate\Support\Arr::first($lessons->getLessons()), $aLessons);

					$iLessonCount = array_sum($aLessonCount);
				} else {
					// Bei Lektionskursen soll die Gesamtzahl aller Lektionen angezeigt werden #5974
					$iLessonCount = $this->searchPlaceholderValue('lessons_amount', $iOptionalParentId, $aPlaceholder);
				}

				$sValue = Ext_Thebing_Format::Number($iLessonCount, null, $iSchool, false);

				return $sValue;
			case 'normal_level':
				$oJourneyCourse = $this->_getInquiryCourse();

				$sLevel = '';

				if($oJourneyCourse instanceof Ext_TS_Inquiry_Journey_Course) {
					$oLevel = $oJourneyCourse->getLevel();
				} elseif($oJourneyCourse instanceof Ext_TS_Enquiry_Combination_Course) {
					$oLevel = $oJourneyCourse->getCourseLevel();
				}

				if($oLevel) {
					$sLevel = $oLevel->getName($this->sTemplateLanguage);
				}

				return (string)$sLevel;
			//UNTERKUNFT
			case 'accommodation_weeks':
				$this->setServiceObject('accommodation');

				if($this->_oJourneyAccommodation instanceof Ext_TS_Service_Interface_Accommodation) {
					$oAccommodation = $this->_oJourneyAccommodation;
					return $oAccommodation->weeks;
				}

				return (string)($this->_aJourneyAccomondations[0]->weeks ?? '');
			case 'date_accommodation_end':
				$this->setServiceObject('accommodation');

				$sFormat = 'date';
				if($this->_oJourneyAccommodation instanceof Ext_TS_Service_Interface_Accommodation) {
					$oAccommodation = $this->_oJourneyAccommodation;
					if(!is_null($oAccommodation->until)) {
						$oDate = new WDDate($oAccommodation->until, WDDate::DB_DATE);
					} else {
						return '';
					}
				} else {
					if(
						isset($this->_aJourneyAccomondations[0]->until) &&
						!is_null($this->_aJourneyAccomondations[0]->until)
					) {
						$oDate = new WDDate($this->_aJourneyAccomondations[0]->until, WDDate::DB_DATE);
					} else {
						return '';
					}
				}
				$sReturn = $oDate->get(WDDate::TIMESTAMP);

				break;
			case 'date_last_accommodation_end':
				$sFormat = 'date';
				$sReturn = $this->_oInquiry->getLastAccommodationEnd();
				break;
			case 'date_first_accommodation_start':
				$sFormat = 'date';
				$sReturn = $this->_oInquiry->getFirstAccommodationStart();
				break;
			case 'date_accommodation_start':
				$this->setServiceObject('accommodation');

				$sFormat = 'date';
				if($this->_oJourneyAccommodation instanceof Ext_TS_Service_Interface_Accommodation) {
					$oAccommodation = $this->_oJourneyAccommodation;
					if(!is_null($oAccommodation->from)) {
						$oDate = new WDDate($oAccommodation->from, WDDate::DB_DATE);
					} else {
						return '';
					}
				} else {
					if(isset($this->_aJourneyAccomondations[0]) && !is_null($this->_aJourneyAccomondations[0])) {
						if($this->_aJourneyAccomondations[0] instanceof Ext_TS_Service_Interface_Accommodation) {
							$sDateFrom = $this->_aJourneyAccomondations[0]->from;
						} else {
							$sDateFrom = $this->_aJourneyAccomondations[0];
						}
						$oDate = new WDDate($sDateFrom, WDDate::DB_DATE);
					} else {
						return '';
					}
				}
				$sReturn = $oDate->get(WDDate::TIMESTAMP);

				break;
			case 'roomtype':
			case 'roomtype_full':
				$this->setServiceObject('accommodation');

				if ($this->_oJourneyAccommodation instanceof Ext_TS_Inquiry_Journey_Accommodation) {
					return $this->_oJourneyAccommodation->getRoomType()->getName($sDisplayLanguage, $sField === 'roomtype');
				}
				return '';
			case 'accommodation_meal':
			case 'accommodation_meal_full':
				$this->setServiceObject('accommodation');

				if ($this->_oJourneyAccommodation instanceof Ext_TS_Inquiry_Journey_Accommodation) {
					return $this->_oJourneyAccommodation->getMeal()->getName($sDisplayLanguage, $sField === 'accommodation_meal');
				}
				return '';
			case 'accommodation_category':
				$this->setServiceObject('accommodation');

				if ($this->_oJourneyAccommodation instanceof Ext_TS_Inquiry_Journey_Accommodation) {
					return $this->_oJourneyAccommodation->getCategory()->getName($sDisplayLanguage);
				}
				return '';
			//TRANSFER
			case 'transfer_booked':
				$aTransfers = $oInquiry->getTransfers();
				if(empty($aTransfers)) {
					return 0;
				}

				return 1;
			case 'booked_transfer_key': // Deprecated
				// Wird leider von diversen Schulen mit diesen Schrott-Schlüsseln verwendet
				$aMapping = [
					Ext_TS_Inquiry_Journey::TRANSFER_MODE_NONE => 'no',
					Ext_TS_Inquiry_Journey::TRANSFER_MODE_ARRIVAL => 'arrival',
					Ext_TS_Inquiry_Journey::TRANSFER_MODE_DEPARTURE => 'departure',
					Ext_TS_Inquiry_Journey::TRANSFER_MODE_BOTH => 'arr_dep',
				];
				$oJourney = $oInquiry->getJourney();
				return $aMapping[$oJourney->transfer_mode];
			case 'tsp_transfer':
			case 'booked_transfer':
				$oJourney = $oInquiry->getJourney();
				$aTransfer = Ext_Thebing_Data::getTransferList($oLanguage);
				return (string)$aTransfer[$oJourney->transfer_mode];
			case 'tsp_comment':
			case 'transfer_comment':
				$oJourney = $oInquiry->getJourney();
				// ohne (string) kommt hier NULL und dann wird der Platzhalter nicht ersetzt
				return (string)$oJourney->transfer_comment;
			case 'transfer_type_key':
				$sReturn = '';

				if($this->oJourneyTransfer) {
					$iTransferId = $this->oJourneyTransfer->id;
				} elseif($this->oGui) {
					$iTransferId = (int)$this->getOption('document_communication_selected_id_single');
				}
				if(
					$iTransferId <= 0 &&
					isset($_VARS['save']['selected_ids'])
				) {
					/*
					 * Das ergibt immer die erste ID, ist also nicht wirklich korrekt!
					 * (aber es war vorher so drin und keine Ahnung ob das ggf. irgendwo gebraucht wird ...)
					 */
					$iTransferId = (int)$_VARS['save']['selected_ids']; // 123_456_789
				}

				if($iTransferId > 0) {
					if($iTransferId == $oTransferArrival->id) {
						$sReturn = $oTransferArrival->transfer_type;
					} elseif($iTransferId == $oTransferDeparture->id) {
						$sReturn = $oTransferDeparture->transfer_type;
					} elseif($iTransferId == $oTransferAdditional->id) {
						$sReturn = $oTransferAdditional->transfer_type;
					}
				}
				return $sReturn;

			case 'transfer_type':
				$sReturn = '';

				if($this->oJourneyTransfer) {
					$iTransferId = $this->oJourneyTransfer->id;
				} elseif($this->oGui) {
					$iTransferId = (int)$this->getOption('document_communication_selected_id_single');
				}

				if(
					$iTransferId <= 0 &&
					isset($_VARS['save']['selected_ids'])
				) {
					/*
					 * Das ergibt immer die erste ID, ist also nicht wirklich korrekt!
					 * (aber es war vorher so drin und keine Ahnung ob das ggf. irgendwo gebraucht wird ...)
					 */
					$iTransferId = (int)$_VARS['save']['selected_ids']; // 123_456_789
				}
				if($iTransferId > 0) {
					$oTransferType = new Ext_Thebing_Gui2_Format_Transfer_Type();
					if($iTransferId == $oTransferArrival->id) {
						$sReturn = $oTransferType->format($oTransferArrival->transfer_type);
					} elseif($iTransferId == $oTransferDeparture->id) {
						$sReturn = $oTransferType->format($oTransferDeparture->transfer_type);
					} elseif($iTransferId == $oTransferAdditional->id) {
						$sReturn = $oTransferType->format($oTransferAdditional->transfer_type);
					}
				}
				return $sReturn;

			case 'tsp_airline':
			case 'tsp_airline_arrival':
			case 'arrival_airline':
				if($oTransferArrival instanceof Ext_TS_Service_Interface_Transfer){
					return $oTransferArrival->airline;
				}
				return '';
			case 'tsp_airline_dep':
			case 'tsp_airline_departure':
			case 'departure_airline':
			case 'departurel_airline':
				if($oTransferDeparture instanceof Ext_TS_Service_Interface_Transfer){
					return $oTransferDeparture->airline;
				}
				return '';
			case 'date_arrival':
			case 'arrival_date':
				if($oTransferArrival instanceof Ext_TS_Service_Interface_Transfer) {
					$sFormat = 'date';
					if(\Core\Helper\DateTime::isDate($oTransferArrival->transfer_date, 'Y-m-d')) {
						$oDate = new WDDate($oTransferArrival->transfer_date, WDDate::DB_DATE);
					} else {
						return '';
					}
					$sReturn = $oDate->get(WDDate::TIMESTAMP);
				}else{
					return '';
				}
				break;
			case 'date_departure':
			case 'departure_date':
				if($oTransferDeparture instanceof Ext_TS_Service_Interface_Transfer) {
					$sFormat = 'date';
					if(\Core\Helper\DateTime::isDate($oTransferDeparture->transfer_date, 'Y-m-d')) {
						$oDate = new WDDate($oTransferDeparture->transfer_date, WDDate::DB_DATE);
					} else {
						return '';
					}
					$sReturn = $oDate->get(WDDate::TIMESTAMP);
				}else{
					return '';
				}
				break;
			case 'time_arrival':
			case 'arrival_time':
				if($oTransferArrival instanceof Ext_TS_Service_Interface_Transfer){
					$oFormat = new Ext_Thebing_Gui2_Format_Time();
					return $oFormat->format($oTransferArrival->transfer_time);
				}
				return '';
			case 'arrival_pickup_time':
				if($oTransferArrival instanceof Ext_TS_Service_Interface_Transfer){
					$oFormat = new Ext_Thebing_Gui2_Format_Time();
					return $oFormat->format($oTransferArrival->pickup);
				}
				return '';
			case 'time_departure':
			case 'departure_time':
				if($oTransferDeparture instanceof Ext_TS_Service_Interface_Transfer){
					$oFormat = new Ext_Thebing_Gui2_Format_Time();
					return $oFormat->format($oTransferDeparture->transfer_time);
				}
				return '';
			case 'departure_pickup_time':
				if($oTransferDeparture instanceof Ext_TS_Service_Interface_Transfer){
					$oFormat = new Ext_Thebing_Gui2_Format_Time();
					return $oFormat->format($oTransferDeparture->pickup);
				}
				return '';
			case 'tsp_flightnumber_arrival':
			case 'arrival_flightnumber':
				if($oTransferArrival instanceof Ext_TS_Service_Interface_Transfer){
					return $oTransferArrival->flightnumber;
				}
				return '';
			case 'tsp_flightnumber_departure':
			case 'departure_flightnumber':
				if($oTransferDeparture instanceof Ext_TS_Service_Interface_Transfer){
					return $oTransferDeparture->flightnumber;
				}
				return '';
			case 'tsp_airport_arrival':
			case 'arrival_pick_up':
			case 'tsp_location_arrival':
				if($oTransferArrival instanceof Ext_TS_Inquiry_Journey_Transfer) {
					return (string)$oTransferArrival->getStartLocation($oLanguage);
				}
				return '';
			case 'arrival_drop_off':
				if($oTransferArrival instanceof Ext_TS_Inquiry_Journey_Transfer) {
					return (string)$oTransferArrival->getEndLocation($oLanguage);
				}
				return '';
				break;
			case 'arrival_pick_up_additional':
				if(
					$oTransferArrival instanceof Ext_TS_Inquiry_Journey_Transfer &&
					$oTransferArrival->start_additional > 0
				) {
					$oAdditionalLocation = Ext_TS_Transfer_Location_Terminal::getInstance($oTransferArrival->start_additional);
					return $oAdditionalLocation->getName($sDisplayLanguage);
				}
				return '';
			case 'arrival_drop_off_additional':
				if(
					$oTransferArrival instanceof Ext_TS_Inquiry_Journey_Transfer &&
					$oTransferArrival->end_additional > 0
				) {
					$oAdditionalLocation = Ext_TS_Transfer_Location_Terminal::getInstance($oTransferArrival->end_additional);
					return $oAdditionalLocation->getName($sDisplayLanguage);
				}
				return '';
			case 'departure_pick_up':
				if($oTransferDeparture instanceof Ext_TS_Inquiry_Journey_Transfer) {
					return (string)$oTransferDeparture->getStartLocation($oLanguage);
				}
				return '';
			case 'tsp_airport_departure':
			case 'departure_drop_off':
			case 'tsp_location_departure':
				if($oTransferDeparture instanceof Ext_TS_Inquiry_Journey_Transfer) {
					return (string)$oTransferDeparture->getEndLocation($oLanguage);
				}
				return '';
			case 'departure_pick_up_additional':
				if(
					$oTransferDeparture instanceof Ext_TS_Inquiry_Journey_Transfer &&
					$oTransferDeparture->start_additional
				) {
					$oAdditionalLocation = Ext_TS_Transfer_Location_Terminal::getInstance($oTransferDeparture->start_additional);
					return $oAdditionalLocation->getName($sDisplayLanguage);
				}
				return '';
			case 'departure_drop_off_additional':
				if(
					$oTransferDeparture instanceof Ext_TS_Inquiry_Journey_Transfer &&
					$oTransferDeparture->end_additional > 0
				) {
					$oAdditionalLocation = Ext_TS_Transfer_Location_Terminal::getInstance($oTransferDeparture->end_additional);
					return $oAdditionalLocation->getName($sDisplayLanguage);
				}
				return '';
			case 'transfer_company_arrival':
			case 'arrival_transfer_company':
				if($oTransferArrival instanceof Ext_TS_Service_Interface_Transfer){
					$oFormat = new Ext_Thebing_Gui2_Format_Transfer_ProviderName();
					$aTempData = array();
					$aTempData['provider_type'] = $oTransferArrival->provider_type;
					$aTempData['provider_id'] = $oTransferArrival->provider_id;
					return $oFormat->format($aTempData, $aTempData, $aTempData);
				}
				return '';
			case 'arrival_transfer_company_phone':
				if($oTransferArrival instanceof Ext_TS_Service_Interface_Transfer){
					$oCompany = Ext_Thebing_Pickup_Company::getInstance($oTransferArrival->provider_id);
					return $oCompany->tel;
				}
				return '';
			case 'arrival_transfer_company_mobile':
				if($oTransferArrival instanceof Ext_TS_Service_Interface_Transfer){
					$oCompany = Ext_Thebing_Pickup_Company::getInstance($oTransferArrival->provider_id);
					return $oCompany->handy;
				}
				return '';
			case 'arrival_transfer_driver_emergency':
				if($oTransferArrival instanceof Ext_TS_Service_Interface_Transfer){
					$oDriver = Ext_Thebing_Pickup_Company_Driver::getInstance($oTransferArrival->driver_id);
					return $oDriver->emergency_number;
				}
				return '';
			case 'transfer_company_departure':
			case 'departure_transfer_company':
				if($oTransferDeparture instanceof Ext_TS_Service_Interface_Transfer){
					$oFormat = new Ext_Thebing_Gui2_Format_Transfer_ProviderName();
					$aTempData = array();
					$aTempData['provider_type'] = $oTransferDeparture->provider_type;
					$aTempData['provider_id'] = $oTransferDeparture->provider_id;
					return $oFormat->format($aTempData, $aTempData, $aTempData);
				}
				return '';
			case 'departure_transfer_company_phone':
				if($oTransferDeparture instanceof Ext_TS_Service_Interface_Transfer){
					$oCompany = Ext_Thebing_Pickup_Company::getInstance($oTransferDeparture->provider_id);
					return $oCompany->tel;
				}
				return '';
			case 'departure_transfer_company_mobile':
				if($oTransferDeparture instanceof Ext_TS_Service_Interface_Transfer){
					$oCompany = Ext_Thebing_Pickup_Company::getInstance($oTransferDeparture->provider_id);
					return $oCompany->handy;
				}
				return '';
			case 'departure_transfer_driver_emergency':
				if($oTransferDeparture instanceof Ext_TS_Service_Interface_Transfer){
					$oDriver = Ext_Thebing_Pickup_Company_Driver::getInstance($oTransferDeparture->driver_id);
					return $oDriver->emergency_number;
				}
				return '';
			case 'transfer_driver_arrival':
			case 'arrival_transfer_driver':
				$driver = '';
				if($oTransferArrival instanceof Ext_TS_Service_Interface_Transfer){
					$oFormat = new Ext_Thebing_Gui2_Format_Transfer_Driver();
					$aTempData = array();
					$aTempData['provider_id'] = $oTransferArrival->provider_id;
					$aTempData['inquiry_transfer_id'] = $oTransferArrival->id;
					$driver = (string)$oFormat->format($oTransferArrival->driver_id, $aTempData, $aTempData);
				}
				return $driver;
			case 'transfer_driver_departure':
			case 'departure_transfer_driver':
				if($oTransferDeparture instanceof Ext_TS_Service_Interface_Transfer){
					$oFormat = new Ext_Thebing_Gui2_Format_Transfer_Driver();
					$aTempData = array();
					$aTempData['provider_id'] = $oTransferDeparture->provider_id;
					$aTempData['inquiry_transfer_id'] = $oTransferDeparture->id;
					return $oFormat->format($oTransferDeparture->driver_id, $aTempData, $aTempData);
				}
				return '';
			// Individueller Transfer
			case 'individual_transfer_comment':
				if($oTransferAdditional instanceof Ext_TS_Service_Interface_Transfer){
					return $oTransferAdditional->comment;
				}
				return '';
			case 'individual_transfer_time':
				if($oTransferAdditional instanceof Ext_TS_Service_Interface_Transfer){
					$oTimeFormat = new Ext_Thebing_Gui2_Format_Time();
					return $oTimeFormat->format($oTransferAdditional->pickup);
				}
				return '';
			case 'individual_transfer_date':
				if($oTransferAdditional instanceof Ext_TS_Service_Interface_Transfer){
					$sFormat = 'date';
					if(!is_null($oTransferAdditional->transfer_date)) {
						$oDate = new WDDate($oTransferAdditional->transfer_date, WDDate::DB_DATE);
					} else {
						return '';
					}
					$sReturn = $oDate->get(WDDate::TIMESTAMP);
				}else{
					return '';
				}
				break;
			case 'individual_transfer_pick_up_location':
				if($oTransferAdditional instanceof Ext_TS_Service_Interface_Transfer){
					return $oTransferAdditional->getLocationName('start', false, $this->getLanguageObject());
				}
				return '';
			case 'individual_transfer_drop_off_location':
				if($oTransferAdditional instanceof Ext_TS_Service_Interface_Transfer){
					return $oTransferAdditional->getLocationName('end', false, $this->getLanguageObject());
				}
				return '';
			case 'individual_transfer_pick_up_location_additional':
				if($oTransferAdditional instanceof Ext_TS_Service_Interface_Transfer){
					return $oTransferAdditional->getTerminalName('start');
				}
				return '';
			case 'individual_transfer_drop_off_location_additional':
				if($oTransferAdditional instanceof Ext_TS_Service_Interface_Transfer){
					return $oTransferAdditional->getTerminalName('end');
				}
				return '';
			## ENDE
			/*
			case 'agency_open_payments':
				// Tabelle mit einer Übersicht der zahlungen
				return Ext_TS_Inquiry::getAgencyOpenPayment($oInquiry->id, false);
				break;
			*/
			case 'amount_net':
			case 'amount_net_all':

				if(strpos($sField, '_all') !== false)
				{
					$bLastDocument = false;
				}
				else
				{
					$bLastDocument = true;
				}

				$iAmount = $oInquiry->getNetAmount($bLastDocument);
				return Ext_Thebing_Format::Number($iAmount, $oInquiry->getCurrency(), $iSchoolForFormat);
			case 'amount_gross':
			case 'amount_gross_all':

				if(strpos($sField, '_all') !== false) {
					$bLastDocument = false;
				} else {
					$bLastDocument = true;
				}

				$sReturn = $oInquiry->getVersionAmount('gross', $bLastDocument);
				$sFormat = 'amount';
				
				break;
				
			case 'amount_all':
				
				$iAmount = $oInquiry->getAmount();
				$sReturn += $oInquiry->getAmount(true);
				$sFormat = 'amount';
				
				break;

			case 'amount':

				$oVersion = $oInquiry->getLatestInvoiceVersion();

 				if($oVersion instanceof Ext_Thebing_Inquiry_Document_Version) {
					$sReturn = $oVersion->getAmount();
					$sFormat = 'amount';

				}

				break;

			case 'amount_open_all':
			case 'amount_open_all_cent':

				$fAmount = $oInquiry->getOpenPaymentAmount();

				if($sField === 'amount_open_all_cent') {
					return $fAmount * 100;
				} else {
					
					$sReturn = $fAmount;
					$sFormat = 'amount';
					
					#return Ext_Thebing_Format::Number($fAmount, $oInquiry->getCurrency(), $iSchoolForFormat);
				}
				
				break;
				
			case 'amount_prepay': // In neuen Platzhaltern nicht übernehmen
			case 'amount_prepay_all': // In neuen Platzhaltern nicht übernehmen
			case 'amount_finalpay': // Wird das zukünfig noch gebraucht?
			case 'amount_finalpay_all': // In neuen Platzhaltern nicht übernehmen
			case 'date_prepay': // In neuen Platzhaltern nicht übernehmen
			case 'date_finalpay': // Wird das zukünfig noch gebraucht?

				$aFilter = ['final'];
				if(strpos($sField, 'prepay') !== false) {
					$aFilter = ['deposit', 'installment'];
				}

				if(strpos($sField, '_all') === false) {
					// Nur bei amount-Platzhaltern
					$aDocuments = array(Ext_Thebing_Inquiry_Document_Search::search($oInquiry->id, 'invoice', false, true));
				} else {
					$aDocuments	= $oInquiry->getDocuments($oInquiry->has_invoice ? 'invoice_without_proforma' : 'invoice', true, true);
				}

				$fAmount = 0;
				foreach($aDocuments as $oDocument) {
					if(!$oDocument instanceof Ext_Thebing_Inquiry_Document) {
						continue;
					}

					$oVersion = $oDocument->getLastVersion();
					$aPaymentTerms = array_filter($oVersion->getPaymentTerms(), function(Ext_TS_Document_Version_PaymentTerm $oPaymentTerm) use($aFilter) {
						return in_array($oPaymentTerm->type, $aFilter);
					});

					// Bei Anzahlung: Erstbester Eintrag (final ist immer einer), da die alten Platzhalter keinen Sinn mehr machen (#8838)
					$oPaymentTerm = reset($aPaymentTerms); /** @var Ext_TS_Document_Version_PaymentTerm $oPaymentTerm */

					// Bei Datum direkt rausspringen, hier darf kein return passieren
					if(strpos($sField, 'date') !== false) {
						$sReturn = ($oPaymentTerm->date ?? null);
						$sFormat = 'date';
						break 2;
					}

					$fAmount += ($oPaymentTerm->amount ?? 0);

				}

				$sReturn = $fAmount;
				$sFormat = 'amount';
				
				break;
				
			//PAYMENTS
			case 'amount_initalcost_all':
				return Ext_Thebing_Format::Number((float)$oInquiry->getAmount(true), $oInquiry->getCurrency(), $iSchoolForFormat);
			case 'amount_initalcost':

				$oVersion = $oInquiry->getLatestInvoiceVersion();

				if($oVersion instanceof Ext_Thebing_Inquiry_Document_Version)
				{
					$fAmount = $oVersion->getAmount(false, true);

					return Ext_Thebing_Format::Number($fAmount, $oInquiry->getCurrency(), $iSchoolForFormat);
				}

				break;
//			case 'amount_reminder':
//				return Ext_Thebing_Format::Number($oInquiry->getPaymentDueAmount(), $oInquiry->getCurrency(), $iSchoolForFormat);
			case 'amount_paid':
				$sReturn = $oInquiry->amount_payed;
				$sFormat = 'amount';
				break;
			case 'provision':
			case 'provision_all':

				if(strpos($sField, '_all') !== false)
				{
					$bLastDocument = false;
				}
				else
				{
					$bLastDocument = true;
				}

				$iProv = $oInquiry->getProvisionAmount($bLastDocument);
				return Ext_Thebing_Format::Number($iProv, $oInquiry->getCurrency(), $iSchoolForFormat);
			case 'public_holidays':
			case 'public_holidays_booking':

				$sHolidays = '';

				if($oInquiry instanceof Ext_TS_Inquiry){

					$oPeriod = $oInquiry->getCompleteServiceTimeframe();

//					if($aData['from'] > 0 && $aData['until'] > 0){
					if ($oPeriod) {

						$oDateFrom = $oPeriod->start;
//						$oDateFrom = new DateTime();
//						$oDateFrom->setTimestamp($aData['from']);

						$oDateUntil = $oPeriod->end;
//						$oDateUntil = new DateTime();
//						$oDateUntil->setTimestamp($aData['until']);

						// wenn die Feiertage für das ganze Jahr angezeigt werden sollen
						if($sField == 'public_holidays') {
							// z.B. 01.01.2013
							$iYear = $oDateFrom->format('Y');
							$oDateFrom->setDate($iYear, 1, 1);
							// z.B. 31.12.2013
							$iYear = $oDateUntil->format('Y');
							$oDateUntil->setDate($iYear, 12, 31);
						}

						$oHolidayHelper = new Ext_TS_School_Helper_Holidays($oSchool);

						$aHolidays = $oHolidayHelper->getHolidays($oDateFrom, $oDateUntil, false);

						// Werte formatieren
						foreach($aHolidays as $oHolidayDatetime) {
							$sHolidays .= $oHolidayHelper->formatDate($oHolidayDatetime);
							$sHolidays .= ', ';
						}

					}
				}

				$sHolidays = rtrim($sHolidays, ', ');

				return $sHolidays;

			case 'inbox_name':
				$oInbox = $oInquiry->getInbox();
				$sReturn = $oInbox->name;
				break;

			case 'inbox_key':
				$oInbox = $oInquiry->getInbox();
				$sReturn = $oInbox->short;
				break;

			//UNTERKUNFT

			case 'accommodation_allocation_start':
				$this->setAccommodationAllocation();

				$sReturn = '';
				if(
					!empty($this->_aAccommodationAllocation) &&
					!empty($this->_aAccommodationAllocation['from'])
				) {
					$dAllocationFrom = \Core\Helper\DateTime::createFromLocalTimestamp($this->_aAccommodationAllocation['from']);
					$sReturn = $dAllocationFrom->format('Y-m-d');
					$sFormat = 'date';
				} else {
					// Das ist wegen möglicher Kompatibilität noch drin, sollte aber eigentlich überall ersetzt sein…
					if(
						$this->_oAllocation instanceof Ext_Thebing_Accommodation_Allocation &&
						!empty($this->_oAllocation->from)
					) {
						$dAllocationFrom = \Core\Helper\DateTime::createFromLocalTimestamp($this->_oAllocation->from);
						$sReturn = $dAllocationFrom->format('Y-m-d');
						$sFormat = 'date';
					}
				}

				break;

			case 'accommodation_allocation_end':
				$this->setAccommodationAllocation();

				$sReturn = '';
				if(
					!empty($this->_aAccommodationAllocation) &&
					!empty($this->_aAccommodationAllocation['until'])
				) {
					$dAllocationUntil = \Core\Helper\DateTime::createFromLocalTimestamp($this->_aAccommodationAllocation['until']);
					$sReturn = $dAllocationUntil->format('Y-m-d');
					$sFormat = 'date';
				} else {
					// Das ist wegen möglicher Kompatibilität noch drin, sollte aber eigentlich überall ersetzt sein…
					if(
						$this->_oAllocation instanceof Ext_Thebing_Accommodation_Allocation &&
						!empty($this->_oAllocation->until)
					) {
						$dAllocationUntil = \Core\Helper\DateTime::createFromLocalTimestamp($this->_oAllocation->until);
						$sReturn = $dAllocationUntil->format('Y-m-d');
						$sFormat = 'date';
					}
				}

				break;

			case 'accommodation_assigned_nights':
			case 'accomodation_amount_assigned_nights':
				$this->setAccommodationAllocation();

				if(!empty($this->_aAccommodationAllocation)) {
					$oAllocation = Ext_Thebing_Accommodation_Allocation::getInstance($this->_aAccommodationAllocation['id']);
					return $oAllocation->getNights();
				}

				return '';
			case 'accommodation_assigned_weeks':
				$this->setAccommodationAllocation();

				if(!empty($this->_aAccommodationAllocation)) {
					$oAllocation = Ext_Thebing_Accommodation_Allocation::getInstance($this->_aAccommodationAllocation['id']);
					return $oAllocation->getWeeks();
				}

				return '';
			case 'accommodation_expected_provider_payment':
			case 'accommodation_expected_provider_payment_transfer':
			case 'accommodation_expected_provider_payment_with_transfer':
				$this->setAccommodationAllocation();

				if(empty($this->_aAccommodationAllocation)) {
					return '';
				}

				$oAllocation = Ext_Thebing_Accommodation_Allocation::getInstance($this->_aAccommodationAllocation['id']);

				if(strpos($sField, 'payment_transfer') !== false) {
					$fAmount = $oAllocation->getExpectedCostsAmountTransfer();
				} elseif(strpos($sField, 'payment_with_transfer') !== false) {
					$fAmount = $oAllocation->getExpectedCostsAmountWithTransfer();
				} else {
					$fAmount = $oAllocation->getExpectedCostsAmount();
				}

				return Ext_Thebing_Format::Number($fAmount, $this->_oSchool->getAccommodationCurrency(), $this->_oSchool->id);

			case 'accommodation_room':
				$this->setAccommodationAllocation();

				if(!empty($this->_aAccommodationAllocation)) {
					$oRoom = Ext_Thebing_Accommodation_Room::getInstance($this->_aAccommodationAllocation['room_id']);
				} else {
					// Das ist wegen möglicher Kompatibilität noch drin, sollte aber eigentlich überall ersetzt sein…
					if($this->_oAllocation instanceof Ext_Thebing_Accommodation_Allocation){
						$oRoom = Ext_Thebing_Accommodation_Room::getInstance($this->_oAllocation->room_id);
					}
				}

				if(
					isset($oRoom) &&
					$oRoom instanceof Ext_Thebing_Accommodation_Room
				) {
					return $oRoom->name;
				}

				return '';
			case 'accommodation_bed':
				$this->setAccommodationAllocation();

				if(!empty($this->_aAccommodationAllocation)) {
					return $this->_aAccommodationAllocation['bed'];
				} elseif($this->_oAllocation instanceof Ext_Thebing_Accommodation_Allocation) {
					// Das ist wegen möglicher Kompatibilität noch drin, sollte aber eigentlich überall ersetzt sein…
					return $this->_oAllocation->bed;
				}
				break;
			case 'accommodation_provider_name':
				// Name der aktuell zugewiesenen Familie
				$this->setAccommodationAllocation();

				if($this->_oFamily instanceof Ext_Thebing_Accommodation) {
					return (string)$this->_oFamily->ext_33;
				} else if(!empty($this->_aAccommodationAllocation)) {
					$aAccommodation = Ext_Thebing_Accommodation_Util::getAccommodationProvidersFromId((int)$this->_aAccommodationAllocation['family_id']);
					$aAccommodation = $aAccommodation[0];
					return (string)$aAccommodation['ext_33'];
				} else {
					return '';
				}
				break;
			case 'accommodation_provider_email':
				$this->setAccommodationAllocation();

				if($this->_oFamily instanceof Ext_Thebing_Accommodation) {
					return $this->_oFamily->email;
				} else if(!empty($this->_aAccommodationAllocation)) {
					$aAccommodation = Ext_Thebing_Accommodation_Util::getAccommodationProvidersFromId((int)$this->_aAccommodationAllocation['family_id']);
					$aAccommodation = $aAccommodation[0];
					return $aAccommodation['email'];
				} else {
					return '';
				}
				break;

			case 'accommodation_contact_firstname':
				$this->setAccommodationAllocation();

				if($this->_oFamily instanceof Ext_Thebing_Accommodation) {
					return $this->_oFamily->ext_103;
				} elseif(!empty($this->_aAccommodationAllocation)) {
					$aAccommodation = Ext_Thebing_Accommodation_Util::getAccommodationProvidersFromId((int)$this->_aAccommodationAllocation['family_id']);
					$aAccommodation = $aAccommodation[0];
					return $aAccommodation['ext_103'];
				} else {
					return '';
				}
				break;
			case 'accommodation_contact_lastname':
				$this->setAccommodationAllocation();

				if($this->_oFamily instanceof Ext_Thebing_Accommodation) {
					return $this->_oFamily->ext_104;
				} elseif(!empty($this->_aAccommodationAllocation)) {
					$aAccommodation = Ext_Thebing_Accommodation_Util::getAccommodationProvidersFromId((int)$this->_aAccommodationAllocation['family_id']);
					$aAccommodation = $aAccommodation[0];
					return $aAccommodation['ext_104'];
				} else {
					return '';
				}
				break;
			case 'accommodation_contact_salutation':
				$this->setAccommodationAllocation();

				if($this->_oFamily instanceof Ext_Thebing_Accommodation) {
					$sName = $this->_oFamily->ext_105;
				} elseif(!empty($this->_aAccommodationAllocation)) {
					$aAccommodation = Ext_Thebing_Accommodation_Util::getAccommodationProvidersFromId((int)$this->_aAccommodationAllocation['family_id']);
					$aAccommodation = $aAccommodation[0];
					$sName = $aAccommodation['ext_105'];
				} else {
					$aAccommodation = Ext_Thebing_Accommodation_Util::getAccommodationProviderFromInquiryId($oInquiry->getField('id'));
					$sName = ($aAccommodation['ext_105'] ?? '');
				}

				if($sName == 1) {
					return $oLanguage->translate('Herr');
				} elseif($sName == 2) {
					return $oLanguage->translate('Frau');
				} else {
					return '';
				}

				break;
			case 'accommodation_address':
				$this->setAccommodationAllocation();

				if($this->_oFamily instanceof Ext_Thebing_Accommodation) {
					return (string)$this->_oFamily->ext_63;
				} elseif(!empty($this->_aAccommodationAllocation)) {
					$aAccommodation = Ext_Thebing_Accommodation_Util::getAccommodationProvidersFromId((int)$this->_aAccommodationAllocation['family_id']);
					$aAccommodation = $aAccommodation[0];
					return (string)$aAccommodation['ext_63'];
				}

				return '';
				break;
			case 'accommodation_address_addon':
				$this->setAccommodationAllocation();

				if($this->_oFamily instanceof Ext_Thebing_Accommodation) {
					return (string)$this->_oFamily->address_addon;
				} elseif(!empty($this->_aAccommodationAllocation)) {
					$aAccommodation = Ext_Thebing_Accommodation_Util::getAccommodationProvidersFromId((int)$this->_aAccommodationAllocation['family_id']);
					$aAccommodation = $aAccommodation[0];
					return (string)$aAccommodation['address_addon'];
				}
				return '';
				break;
			case 'accommodation_plz':
			case 'accommodation_zip':
				$this->setAccommodationAllocation();

				if($this->_oFamily instanceof Ext_Thebing_Accommodation) {
					return (string)$this->_oFamily->ext_64;
				} elseif(!empty($this->_aAccommodationAllocation)) {
					$aAccommodation = Ext_Thebing_Accommodation_Util::getAccommodationProvidersFromId((int)$this->_aAccommodationAllocation['family_id']);
					$aAccommodation = $aAccommodation[0];
					return (string)$aAccommodation['ext_64'];
				}
				return '';
				break;
			case 'accommodation_city':
				$this->setAccommodationAllocation();

				if($this->_oFamily instanceof Ext_Thebing_Accommodation) {
					return $this->_oFamily->ext_65;
				} elseif(!empty($this->_aAccommodationAllocation)) {
					$aAccommodation = Ext_Thebing_Accommodation_Util::getAccommodationProvidersFromId((int)$this->_aAccommodationAllocation['family_id']);
					$aAccommodation = $aAccommodation[0];
					return $aAccommodation['ext_65'];
				}

				return '';
			case 'accommodation_country':
				$this->setAccommodationAllocation();

				if($this->_oFamily instanceof Ext_Thebing_Accommodation) {
					return (string)$this->_oFamily->ext_66;
				} elseif(!empty($this->_aAccommodationAllocation)) {
					$aAccommodation = Ext_Thebing_Accommodation_Util::getAccommodationProvidersFromId((int)$this->_aAccommodationAllocation['family_id']);
					$aAccommodation = $aAccommodation[0];
					return (string)$aAccommodation['ext_66'];
				}

				return '';
			case 'accommodation_state':
				$this->setAccommodationAllocation();

				if($this->_oFamily instanceof Ext_Thebing_Accommodation){
					return $this->_oFamily->ext_99;
				}else if(!empty($this->_aAccommodationAllocation)) {
					$aAccommodation = Ext_Thebing_Accommodation_Util::getAccommodationProvidersFromId((int)$this->_aAccommodationAllocation['family_id']);
					$aAccommodation = $aAccommodation[0];
					return $aAccommodation['ext_99'];
				}
				return '';
			case 'accommodation_phone':
				$this->setAccommodationAllocation();

				if($this->_oFamily instanceof Ext_Thebing_Accommodation) {
					return $this->_oFamily->ext_67;
				} elseif(!empty($this->_aAccommodationAllocation)) {
					$aAccommodation = Ext_Thebing_Accommodation_Util::getAccommodationProvidersFromId((int)$this->_aAccommodationAllocation['family_id']);
					$aAccommodation = $aAccommodation[0];
					return $aAccommodation['ext_67'];
				}
				return '';
			case 'accommodation_phone2':
				$this->setAccommodationAllocation();

				if($this->_oFamily instanceof Ext_Thebing_Accommodation) {
					return $this->_oFamily->ext_76;
				} elseif(!empty($this->_aAccommodationAllocation)) {
					$aAccommodation = Ext_Thebing_Accommodation_Util::getAccommodationProvidersFromId((int)$this->_aAccommodationAllocation['family_id']);
					$aAccommodation = $aAccommodation[0];
					return $aAccommodation['ext_76'];
				}
				return '';
			case 'accommodation_mobile':
				$this->setAccommodationAllocation();

				if($this->_oFamily instanceof Ext_Thebing_Accommodation) {
					return $this->_oFamily->ext_77;
				} elseif(!empty($this->_aAccommodationAllocation)) {
					$aAccommodation = Ext_Thebing_Accommodation_Util::getAccommodationProvidersFromId((int)$this->_aAccommodationAllocation['family_id']);
					$aAccommodation = $aAccommodation[0];
					return $aAccommodation['ext_77'];
				}
				return '';
			case 'accommodation_informations':
			case 'accommodation_description':
				$this->setAccommodationAllocation();

				if($this->_oFamily instanceof Ext_Thebing_Accommodation) {
					return $this->_oFamily->getFamilyDescription($sDisplayLanguage);
				} elseif(!empty($this->_aAccommodationAllocation)) {
					$aAccommodation = Ext_Thebing_Accommodation_Util::getAccommodationProvidersFromId((int)$this->_aAccommodationAllocation['family_id']);
					$aAccommodation = $aAccommodation[0];
					return $aAccommodation['family_description_'.$sDisplayLanguage];
				}
				return '';
			case 'accommodation_route':
				$this->setAccommodationAllocation();

				if($this->_oFamily instanceof Ext_Thebing_Accommodation) {
					return $this->_oFamily->getWayDescription($sDisplayLanguage);
				} elseif(!empty($this->_aAccommodationAllocation)) {
					$aAccommodation = Ext_Thebing_Accommodation_Util::getAccommodationProvidersFromId((int)$this->_aAccommodationAllocation['family_id']);
					$aAccommodation = $aAccommodation[0];
					return $aAccommodation['way_description_'.$sDisplayLanguage];
				}
				return '';
			case 'accommodation_googlemaps':
				$this->setAccommodationAllocation();

				if($this->_oFamily instanceof Ext_Thebing_Accommodation) {
					return $this->_oFamily->ext_109;
				} elseif(!empty($this->_aAccommodationAllocation)) {
					$aAccommodation = Ext_Thebing_Accommodation_Util::getAccommodationProvidersFromId((int)$this->_aAccommodationAllocation['family_id']);
					$aAccommodation = $aAccommodation[0];
					return $aAccommodation['ext_109'];
				}
				return '';
			case 'customer_family_comment': // @TODO: Löschen
			case 'accommodation_comment': // Es gibt bei der Unterkunftsbuchung auch ein Kommentarfeld
			case 'matching_comment':
				return nl2br($oInquiry->getMatchingData()->acc_comment);
			case 'customer_family_comment_2': // @TODO: Löschen
			case 'accommodation_comment_2': // Es gibt bei der Unterkunftsbuchung auch ein Kommentarfeld
			case 'matching_comment_2':
				return nl2br($oInquiry->getMatchingData()->acc_comment2);
			case 'customer_family_share_with': // @TODO: Löschen
			case 'accommodation_share_with':
				$aRoomcharingCustomers = $oInquiry->loadRoomSharingCustomers();

				$aCustomers = array();
				foreach((array)$aRoomcharingCustomers as $aRoomsharing){
					$oTempInquiry = Ext_TS_Inquiry::getInstance($aRoomsharing['share_id']);
					$oTempCustomer = $oTempInquiry->getCustomer();
					// String zusammenbauen mit allen gemeinsam reisenen Schülern
					$aCustomers[] = $oTempCustomer->name;
				}

				return implode(', ', $aCustomers);
			case 'student_criteria_smoker':
			case 'accommodation_smoker': // Alter Platzhalter
				return (string)($aYesNo[$oInquiry->getMatchingData()->acc_smoker] ?? '');
			case 'student_criteria_vegetarian':
			case 'accommodation_vegetarian': // Alter Platzhalter
				return (string)($aYesNo[$oInquiry->getMatchingData()->acc_vegetarian] ?? '');
			case 'student_criteria_muslim_diet':
				return (string)$aYesNo[$oInquiry->getMatchingData()->acc_muslim_diat];
			case 'accommodation_criteria_cats':
			case 'accommodation_cats': // @TODO Löschen
				$this->setAccommodationAllocation();
				if($this->_oFamily instanceof Ext_Thebing_Accommodation) {
					return (string)$aYesNo2[$this->_oFamily->ext_42] ?? '';
				}
				return '';
			case 'accommodation_criteria_dogs':
			case 'accommodation_dogs': // @TODO Löschen
				$this->setAccommodationAllocation();
				if($this->_oFamily instanceof Ext_Thebing_Accommodation) {
					return (string)$aYesNo2[$this->_oFamily->ext_43] ?? '';
				}
				return '';
			case 'accommodation_criteria_other_pets':
			case 'accommodation_other_pets': // @TODO Löschen
				$this->setAccommodationAllocation();
				if($this->_oFamily instanceof Ext_Thebing_Accommodation) {
					return (string)$aYesNo2[$this->_oFamily->ext_44] ?? '';
				}
				return '';
			case 'accommodation_criteria_smoker':
				$this->setAccommodationAllocation();
				if($this->_oFamily instanceof Ext_Thebing_Accommodation) {
					return $aYesNo2[$this->_oFamily->ext_45] ?? '';
				}
				return '';
			case 'accommodation_criteria_air_conditioner':
			case 'accommodation_air_condition': // @TODO Löschen
				$this->setAccommodationAllocation();
				if($this->_oFamily instanceof Ext_Thebing_Accommodation) {
					return $aYesNo2[$this->_oFamily->ext_47] ?? '';
				}
				return '';
			case 'accommodation_criteria_internet':
			case 'accommodation_internet': // @TODO Löschen
				$this->setAccommodationAllocation();
				if($this->_oFamily instanceof Ext_Thebing_Accommodation) {
					return $aYesNo2[$this->_oFamily->ext_53] ?? '';
				}
				return '';
			case 'accommodation_criteria_bath':
			case 'accommodation_bath': // @TODO Löschen
				$this->setAccommodationAllocation();
				if($this->_oFamily instanceof Ext_Thebing_Accommodation) {
					return $aYesNo2[$this->_oFamily->ext_48] ?? '';
				}
				return '';
			case 'accommodation_criteria_children':
			case 'accommodation_children': // @TODO Löschen
				$this->setAccommodationAllocation();
				if($this->_oFamily instanceof Ext_Thebing_Accommodation) {
					return $aYesNo2[$this->_oFamily->ext_51] ?? '';
				}
				return '';
			case 'student_criteria_allergies':
			case 'accommodation_allergies': // Alter Platzhalter
				$this->setAccommodationAllocation();
				return $oInquiry->getMatchingData()->acc_allergies;
			case 'student_preference_family_age':
			case 'customer_family_age': // Alter Platzhalter
				return match ((int)$oInquiry->getMatchingData()->family_age) {
					1 => $oLanguage->translate('young'),
					2 => $oLanguage->translate('middle'),
					3 => $oLanguage->translate('old'),
					default => ''
				};
			case 'student_preference_air_conditioner':
			case 'customer_family_airconditioner': // Alter Platzhalter
				if (!empty($oInquiry->getMatchingData()->air_conditioner)) {
					return $aYesNo[$oInquiry->getMatchingData()->air_conditioner];
				}
				return '';
			case 'student_preference_bath':
			case 'customer_family_bath': // Alter Platzhalter
				if (!empty($oInquiry->getMatchingData()->bath)) {
					return $aYesNo[$oInquiry->getMatchingData()->bath];
				}
				return '';
			case 'student_preference_cats':
			case 'customer_family_cats': // Alter Platzhalter
				if (!empty($oInquiry->getMatchingData()->cats)) {
					return $aYesNo[$oInquiry->getMatchingData()->cats];
				}
				return '';
			case 'student_preference_distance':
			case 'customer_family_distance': // Alter Platzhalter
				return match ((int)$oInquiry->getMatchingData()->distance_to_school) {
					1 => $oLanguage->translate('close'),
					2 => $oLanguage->translate('medium'),
					3 => $oLanguage->translate('far'),
					default => ''
				};
			case 'student_preference_dogs':
			case 'customer_family_dogs':  // Alter Platzhalter
				if (!empty($oInquiry->getMatchingData()->dogs)) {
					return $aYesNo[$oInquiry->getMatchingData()->dogs];
				}
				return '';
			case 'student_preference_internet':
			case 'customer_family_internet': // Alter Platzhalter
				if (!empty($oInquiry->getMatchingData()->internet)) {
					return $aYesNo[$oInquiry->getMatchingData()->internet];
				}
				return '';
			case 'student_preference_children':
			case 'customer_family_kids': // Alter Platzhalter
				if (!empty($oInquiry->getMatchingData()->family_kids)) {
					return $aYesNo[$oInquiry->getMatchingData()->family_kids];
				}
				return '';
			case 'student_preference_pets':
			case 'customer_family_pets': // Alter Platzhalter
				if (!empty($oInquiry->getMatchingData()->pets)) {
					return $aYesNo[$oInquiry->getMatchingData()->pets];
				}
				return '';
			case 'student_preference_smoker':
			case 'customer_family_smoker':  // Alter Platzhalter
				if (!empty($oInquiry->getMatchingData()->smoker)) {
					return $aYesNo[$oInquiry->getMatchingData()->smoker];
				}
				return '';
			case 'voucher_id':
				$sReturn = $oInquiry->voucher_id;
				break;

			//LINKS
			case 'link_feedback':
				// DEPRECATED -> Kann gar nicht mehr funktionieren, weil es für jeden Fragebogen immer ein Questionary_Process
				// geben muss (siehe Ext_TC_Frontend_Combination_Feedback _default()) und hier ja keiner erstellt wird.
				// -> kann auch nicht nach dem erstellen benutzt werden, weil hier ?r= pro Buchung- und beim Questionary_Process
				// ein random String ist.
				$sHash = md5($oInquiry->id);
				$oSchool = $oInquiry->getSchool();
				if($sHash != ""){
					if($oSchool->url_feedback != ""){
						$sFeedbackUrl = $oSchool->url_feedback.'?r='.$sHash;
					}else {
						$sFeedbackUrl = "No Feedback Url";
					}
				} else {
					$sFeedbackUrl = "No Customer Data for Feedback";
				}
				$sReturn = $sFeedbackUrl;
				break;
			case 'link_placementtest_direct':
			
				$sPlacementTestUrl = '';
			
				/** @var Ext_Thebing_Placementtests_ResultsRepository $oPlacementTestResultRepository */
				$oPlacementTestResultRepository = Ext_Thebing_Placementtests_Results::getRepository();

				if($oInquiry->exist()) {

					$iInquiryId = $oInquiry->id;

					$sLinkKey = '';
					$dNow = new \DateTime();

					$oPlacementTestResult = $oPlacementTestResultRepository->getPlacementtestPerInquiryId($iInquiryId);

					if($oPlacementTestResult === null) {

						$oPlacementTestResult = new Ext_Thebing_Placementtests_Results();
						$sLinkKey = $oPlacementTestResult->getUniqueKey();

						$placementtest = \TsTuition\Entity\Placementtest::getInstance($oSchool->default_placementtest_id);
						$oPlacementTestResult->active = 1;
						$oPlacementTestResult->inquiry_id = $iInquiryId;
						$oPlacementTestResult->invited = $dNow->format('Y-m-d H:i:s');
						$oPlacementTestResult->key = $sLinkKey;
						$oPlacementTestResult->level_id = 0;
						$oPlacementTestResult->placementtest_date = '0000-00-00';
						$oPlacementTestResult->placementtest_id = $placementtest->id;
						$oPlacementTestResult->courselanguage_id = $placementtest->courselanguage_id;
						$oPlacementTestResult->save();

					} else {

						if(
							$oPlacementTestResult->answered !== false &&
							$oPlacementTestResult->answered != '0000-00-00 00:00:00'
						) {

							$sLinkKey = null;

						} else {

							$sLinkKey = $oPlacementTestResult->key;

							// TODO: Das müsste irgendwann entfernt werden
							if(empty($sLinkKey)) {
								$sLinkKey = $oPlacementTestResult->getUniqueKey();
								$oPlacementTestResult->key = $sLinkKey;
							}

							$oPlacementTestResult->invited = $dNow->format('Y-m-d H:i:s');
							$oPlacementTestResult->save();

						}

					}
					
					if($sLinkKey) {
						$oSchool = $oInquiry->getSchool();
						$sPlacementTestUrl = $oSchool->url_placementtest;
						if(strpos($sPlacementTestUrl, '?') === false) {
							$sPlacementTestUrl .= '?r=';
						} else {
							$sPlacementTestUrl .= '&r=';
						}
					
						$sPlacementTestUrl .= $sLinkKey;
					}
					
				}
			
				$sReturn = $sPlacementTestUrl;
				break;
			case 'payment_process_key':
				$sReturn = '';
				if ($oInquiry instanceof Ext_TS_Inquiry) {
					$sReturn = $oInquiry->generatePaymentProcessKey((string)$sModifier);
				}
				break;
			case 'attendance_per_week':

				if(
					is_object($this->_oJourneyCourse) &&
					$this->_oJourneyCourse instanceof Ext_TS_Inquiry_Journey_Course
				) {
					$sReturn = Ext_Thebing_Tuition_Attendance::getAttendanceTableForInquiryCourse($this->_oJourneyCourse, $oLanguage);
				} else if($oInquiry instanceof Ext_TS_Inquiry) {

					$aCourses = (array)$oInquiry->getCourses(true);
					$sReturn = '';

					foreach($aCourses as $oJourneyCourse) {
						$sReturn .= Ext_Thebing_Tuition_Attendance::getAttendanceTableForInquiryCourse($oJourneyCourse, $oLanguage);
					}
				}

				break;
			case 'last_level': // Letztes internes Level (nach Wochen)
			case 'highest_level': // Höchstes internes Level (nach SORTIERUNG)
				$aOptions = array('language' => $sDisplayLanguage);

				$oInquiryCourse = null;
				if(
					$this->_bCourseLoop &&
					$this->_oJourneyCourse instanceof Ext_TS_Inquiry_Journey_Course
				) {
					// Loop
					$oInquiryCourse = $this->_oJourneyCourse;
					// @todo Kurssprache direkt aus Buchung holen
					$aOptions['courselanguage_id'] = $oInquiryCourse->getLevelGroup()->id;
				}

				if($sField == 'last_level') {
					$sUntil	= $_VARS['filter']['search_time_until_1'] ?? null;
					if(!empty($sUntil)){
						$aOptions['date_until'] = Ext_Thebing_Format::ConvertDate($sUntil, null, true);
					}
				} elseif($sField === 'highest_level') {
					// Das höchste Level soll nicht zeitbezogen errechnet werden
					$aOptions['order'] = " `ktul`.`position` DESC ";
				}

				$sLastLevel = (string)$oInquiry->getLastLevel('name', $oInquiryCourse, $aOptions);
				return $sLastLevel;

			case 'lesson_amount':
			case 'lessons_amount':

				// Wenn keine Kurs ID angegeben ist, dann den ersten Kurs nehmen
				if($iOptionalParentId <= 0) {
					$aCourses = $oInquiry->getCourses(false);
					$iOptionalParentId = $aCourses[0]['id'];
				}

				if($this->_oJourneyCourse instanceof Ext_TS_Service_Interface_Course) {
					$iOptionalParentId = $this->_oJourneyCourse->id;
				}

				$sReturn  = $this->getLessionAmount($iOptionalParentId, $oSchool);

				break;

			case 'lessons_amount_total':
				if($this->_oJourneyCourse instanceof Ext_TS_Service_Interface_Course) {
					$iOptionalParentId = $this->_oJourneyCourse->id;
				}
				if($iOptionalParentId > 0 && $this->_bCourseLoop){
					$sReturn  = $this->getLessionAmount($iOptionalParentId, $oSchool);
				} else {
					$sReturn  = $this->getLessionTotalAmount($oInquiry, $oSchool);
				}
				break;
			case 'lessons_attended':
				$aCourses = array();
				if(
					is_object($this->_oJourneyCourse) &&
					$this->_oJourneyCourse instanceof Ext_TS_Inquiry_Journey_Course &&
					$this->_bCourseLoop
				) {
					$aCourses[] = $this->_oJourneyCourse;
				} else if($oInquiry instanceof Ext_TS_Inquiry) {
					$aCourses = (array)$oInquiry->getCourses(true);
				}
				$fLessons = Ext_Thebing_Tuition_Attendance::getAttendanceLessonsForInquiryCourses($aCourses);
				$sReturn = Ext_Thebing_Format::Number($fLessons, false);
				break;

			/*
						case 'lessons_missed':
							$iTotal = 0;
							if($this->_oJourneyCourse instanceof Ext_TS_Service_Interface_Course) {
								$iTotal = $this->getLessionAmount($this->_oJourneyCourse->id, $oSchool, false);
							} else {
								$iTotal = $this->getLessionTotalAmount($oInquiry, $oSchool, false);
							}
							$fAttended = 0.0;
							if(
								is_object($this->_oJourneyCourse) &&
								$this->_oJourneyCourse instanceof Ext_TS_Inquiry_Journey_Course &&
								$this->_bCourseLoop
							) {
								$fAttended = (float)Ext_Thebing_Tuition_Attendance::getAttendanceLessonsForInquiryCourses([$this->_oJourneyCourse]);
							} else if($oInquiry instanceof Ext_TS_Inquiry) {
								$fAttended = (float)Ext_Thebing_Tuition_Attendance::getAttendanceLessonsForInquiryCourses((array)$oInquiry->getCourses(true));
							}
							return Ext_Thebing_Format::Number(min(0, $iTotal-$fAttended), false);
			*/

			case 'insurance':
			{
				$this->setServiceObject('insurance');

				if($this->oJourneyInsurance instanceof Ext_TS_Service_Interface_Insurance) {
					$sReturn = $this->oJourneyInsurance->getInsuranceName($sDisplayLanguage);
				} else {
					$sReturn = '';
				}

				break;
			}
			case 'insurance_provider':
			{
				$this->setServiceObject('insurance');

				if($this->oJourneyInsurance instanceof Ext_TS_Service_Interface_Insurance) {
					$oProvider = $this->oJourneyInsurance->getInsuranceProvider();
					$sReturn = $oProvider->company;
				} else {
					$sReturn = '';
				}

				break;
			}
			case 'date_insurance_start':
			{
				$this->setServiceObject('insurance');

				if($this->oJourneyInsurance instanceof Ext_TS_Service_Interface_Insurance) {
					$sFormat = 'date';
					$sReturn = $this->oJourneyInsurance->getInsuranceStart();
				} else {
					$sReturn = '';
				}

				break;
			}
			case 'date_insurance_end':
			{
				$this->setServiceObject('insurance');

				if($this->oJourneyInsurance instanceof Ext_TS_Service_Interface_Insurance) {
					$sFormat = 'date';
					$sReturn = $this->oJourneyInsurance->getInsuranceEnd();
				} else {
					$sReturn = '';
				}

				break;
			}
			case 'insurance_price':
			{
				$this->setServiceObject('insurance');

				if($this->oJourneyInsurance instanceof Ext_TS_Service_Interface_Insurance) {
					$oSchool = $this->getSchool();
					$oCurrency = Ext_Thebing_Currency::getInstance($this->_oInquiry->getCurrency());
					$dStartDate = new DateTime($this->oJourneyInsurance->from);
					$dEndDate = new DateTime($this->oJourneyInsurance->until);
					$fPrice = $this->oJourneyInsurance->getInsurance()->getInsurancePriceForPeriod(
						$oSchool,
						$oCurrency,
						$dStartDate,
						$dEndDate,
						$this->oJourneyInsurance->weeks
					);
					$sReturn = (string)Ext_Thebing_Format::Number($fPrice, $oCurrency->id, $oSchool->id, true, 2);

				} else {
					$sReturn = '';
				}

				break;
			}
			case 'class_name':

				if($this->_oTuitionAllocation !== null)
				{
					$oAllocation = $this->_oTuitionAllocation;
				}
				else
				{
					$oAllocation = $this->_getFirstAllocation();
				}

				$oBlock			= $oAllocation->getBlock();
				$oClass			= $oBlock->getClass();
				$sClassName		= $oClass->getName();
				$sReturn		= $sClassName;

				break;
			case 'class_content':
				if($this->_oTuitionAllocation !== null) {
					$oAllocation = $this->_oTuitionAllocation;
				} else {
					$oAllocation = $this->_getFirstAllocation();
				}

				$oBlock	= $oAllocation->getBlock();
				$oFormat = new Ext_Thebing_Gui2_Format_School_Tuition_Block_Description();
				$sReturn = $oFormat->format($oBlock->description);

				break;
			case 'level':
				if($this->_oTuitionAllocation !== null)
				{
					$iLevel			= $this->_oTuitionAllocation->getProgress();
					$oLevel			= Ext_Thebing_Tuition_Level::getInstance($iLevel);
					$sLevelName		= $oLevel->name_short;
					$sReturn		= $sLevelName;
				}
				break;
			case 'teacher_firstname':
			case 'teacher_lastname':

				if($this->_oTuitionAllocation !== null)
				{
					$oAllocation = $this->_oTuitionAllocation;
				}
				else
				{
					$oAllocation = $this->_getFirstAllocation(array(
						'`ktb`.`teacher_id`' => array(
							'operator' => '!=',
							'value' => '0',
						)
					));
				}

				$oBlock			= $oAllocation->getBlock();
				$oTeacher		= $oBlock->getTeacher();
				$sField			= str_replace('teacher_', '', $sField);
				$sReturn		= $oTeacher->$sField;

				break;
			case 'attendance_score':
			case 'score':

				$sReturn = '';

				$this->_getInquiryCourse();

				if(empty($sModifier))
				{
					if($this->_oTuitionAllocation !== null)
					{
						$oAttendance	= $this->_oTuitionAllocation->getAttendance();
					}
					else
					{
						$oAttendance		= $this->_getFirstAttendance(array(
							'`kta`.`score`' => array(
								'operator' => '!=',
								'value' => '',
							)
						));
					}

					if($oAttendance)
					{
						$sReturn				= $oAttendance->score;
					}

					if (is_null($sReturn)) {
						$sReturn = '';
					}

				}
				else if($oInquiry instanceof Ext_TS_Inquiry)
				{
					$oAttendanceIndex = new Ext_Thebing_Tuition_Attendance_Index();

					if($this->_oJourneyCourse !== null)
					{
						$aSearch = array(
							'journey_course_id' => (int)$this->_oJourneyCourse->id
						);
					}
					else
					{
						$aSearch = array(
							'inquiry_id' => (int)$oInquiry->id
						);
					}

					$sReturn = $oAttendanceIndex->calculateAverageScore($aSearch);
				}


				break;
			case 'attendance_note':
			case 'note':

				$this->_getInquiryCourse();

				if($this->_oTuitionAllocation !== null)
				{
					$oAttendance	= $this->_oTuitionAllocation->getAttendance();
				}
				else
				{
					$oAttendance		= $this->_getFirstAttendance(array(
						'`kta`.`comment`' => array(
							'operator' => '!=',
							'value' => '',
						)
					));
				}

				if($oAttendance)
				{
					$sReturn		= $oAttendance->comment;
				}

				break;
			case 'course_attendance':
			case 'course_attendance_expected':
				if(
					is_object($this->_oJourneyCourse) &&
					$this->_oJourneyCourse instanceof Ext_TS_Inquiry_Journey_Course
				) {
					$iDecimalPlaces = $sModifier === 'round' ? (int)$aPlaceholder['parameter'] : null;
					$bExpected = $sField === 'course_attendance_expected';
					$fAttendance = Ext_Thebing_Tuition_Attendance::getAttendanceForInquiryCourse($this->_oJourneyCourse->id, $bExpected);
					$oFormatPercent = new Ext_Thebing_Gui2_Format_Tuition_Attendance_Percent($oLanguage, $iDecimalPlaces);
					$sReturn = $oFormatPercent->format($fAttendance);
				}
				break;
			case 'student_attendance':
			case 'student_attendance_expected':

				if($oInquiry instanceof Ext_TS_Inquiry) {
					$iDecimalPlaces = $sModifier === 'round' ? (int)$aPlaceholder['parameter'] : null;
					$bExpected = $sField === 'student_attendance_expected';
					$fAttendance = Ext_Thebing_Tuition_Attendance::getAttendanceForInquiry($oInquiry->id, $bExpected);
					$oFormatPercent = new Ext_Thebing_Gui2_Format_Tuition_Attendance_Percent($oLanguage, $iDecimalPlaces);
					$sReturn = $oFormatPercent->format($fAttendance);
				}

				break;

			case 'student_attendance_overall':

				if($this->_oCustomer instanceof Ext_TS_Inquiry_Contact_Abstract) {
					$iDecimalPlaces = $sModifier === 'round' ? (int)$aPlaceholder['parameter'] : null;
					$fAttendance = Ext_Thebing_Tuition_Attendance_Index::getAttendanceForInquiryContact($this->_oCustomer);
					$oFormatPercent = new Ext_Thebing_Gui2_Format_Tuition_Attendance_Percent($oLanguage, $iDecimalPlaces);
					$sReturn = $oFormatPercent->format($fAttendance);
				}

				break;
			case 'tuition_allocated_days':
			case 'attendance_days_present_completely':
			case 'attendance_days_absent_completely':
			case 'attendance_days_absent_partially':

				$oJourneyCourse = null;
				if(
					$this->_bCourseLoop &&
					$this->_oJourneyCourse instanceof Ext_TS_Inquiry_Journey_Course
				) {
					$oJourneyCourse = $this->_oJourneyCourse;
				}

				if($sField === 'tuition_allocated_days') {
					$aClassDays = $oInquiry->getClassDays(null, null, $oJourneyCourse);
					$sReturn = count($aClassDays);
				} else {
					$sType = 'present';
					if($sField === 'attendance_days_absent_completely') {
						$sType = 'absent';
					} elseif($sField === 'attendance_days_absent_partially') {
						$sType = 'absent_partial';
					}

					$aDays = Ext_Thebing_Tuition_Attendance::getPresentOrAbsentDays($sType, $oInquiry, null, null, $oJourneyCourse);
					$sReturn = count($aDays);
				}

				break;

			case 'holiday_days':

				$aHolidayDays = $oInquiry->getHolidayDays();
				$sReturn = count($aHolidayDays);

				break;

			case 'weekdays_times':

				$sReturn = '';
				if($this->_oTuitionAllocation instanceof Ext_Thebing_School_Tuition_Allocation) {

					$aDayNames	= Ext_Thebing_Util::getLocaleDays($sDisplayLanguage);

					$oBlock		= $this->_oTuitionAllocation->getBlock();
					$aDays		= $oBlock->days;
					$oTemplate	= $oBlock->getTemplate();
					$aTimes		= array();

					foreach($aDays as $iDay) {
						$sDay = $aDayNames[$iDay];

						$aTimes[] = $sDay . ' ' . $oTemplate->getShortTime('from') . ' - ' . $oTemplate->getShortTime('until');
					}

					$sReturn = implode('<br />', $aTimes);
				}

				break;

			case 'weekdays':

				if($this->_oTuitionAllocation instanceof Ext_Thebing_School_Tuition_Allocation)
				{

					$oBlock			= $this->_oTuitionAllocation->getBlock();
					$aBlockDays		= $oBlock->days;
					$sReturn		= Ext_TC_Util::bindDays($aBlockDays, $sDisplayLanguage);
				}

				break;

			case 'tuition_block_dates':

				if($this->_oTuitionAllocation instanceof Ext_Thebing_School_Tuition_Allocation) {

					$oBlock = $this->_oTuitionAllocation->getBlock();
					$aDates	= $oBlock->getDaysAsDateTimeObjects();

					$aReturn = [];
					foreach($aDates as $dDate) {
						$sDay = $aDayNames[$iDay];

						$aReturn[] = Ext_Thebing_Format::LocalDate($dDate);
					}

					$sReturn = implode('<br />', $aReturn);
				}

				break;

			case 'times':

				if($this->_oTuitionAllocation instanceof Ext_Thebing_School_Tuition_Allocation)
				{
					$oBlock		= $this->_oTuitionAllocation->getBlock();
					$oTemplate	= $oBlock->getTemplate();

					$sReturn = $oTemplate->getShortTime('from') . ' - ' . $oTemplate->getShortTime('until');
				}

				break;

			case 'classroom':

				if($this->_oTuitionAllocation instanceof Ext_Thebing_School_Tuition_Allocation)
				{
					$oRoom		= $this->_oTuitionAllocation->getRoom();
					$sReturn = $oRoom->getName();
				}

				break;

			case 'floor':

				if($this->_oTuitionAllocation instanceof Ext_Thebing_School_Tuition_Allocation)
				{
					$oRoom		= $this->_oTuitionAllocation->getRoom();
					$oFloor		= $oRoom->getFloor();

					$sReturn = $oFloor->title;
				}

				break;

			case 'building':

				if($this->_oTuitionAllocation instanceof Ext_Thebing_School_Tuition_Allocation)
				{
					$oRoom		= $this->_oTuitionAllocation->getRoom();
					$oFloor		= $oRoom->getFloor();
					$oBuilding	= $oFloor->getBuilding();

					$sReturn = $oBuilding->title;
				}

				break;

			// TODO Redundant in \Ext_TS_Inquiry_Placeholder_Abstract::searchPlaceholderValue()
			case 'document_address_type':
			case 'document_addressee':
			case 'document_firstname':
			case 'document_surname':
			case 'document_lastname':
			case 'document_salutation':
			case 'document_address':
			case 'document_address_addon':
			case 'document_zip':
			case 'document_city':
			case 'document_state':
			case 'document_country':
			case 'document_company':
				$aAddressData = $this->_aAdditionalData['document_address'];
				$sReturn = (string)$this->_helperReplaceDocumentPlaceholders($sField, $aAddressData[0], $oInquiry);
				break;
			case 'course_note':

				$oJourneyCourse = $this->_getInquiryCourse();

				if($oJourneyCourse instanceof Ext_TS_Inquiry_Journey_Course) {
					return $oJourneyCourse->comment;
				} else {
					return '';
				}

			case 'referrer':

				$oReferrer = $this->_oInquiry->getReferrer();

				if($oReferrer instanceof Ext_TS_Referrer) {
					$sReturn = $oReferrer->getName($sDisplayLanguage);
				} else {
					$sReturn = '';
				}
				break;
			case 'enquiry_comment_course_category':
				return $this->_oInquiry->enquiry_course_category;
			case 'enquiry_comment_course_intensity':
				return $this->_oInquiry->enquiry_course_intensity;
			case 'enquiry_comment_accommodation_category':
				return $this->_oInquiry->enquiry_accommodation_category;
			case 'enquiry_comment_accommodation_room':
				return $this->_oInquiry->enquiry_accommodation_room;
			case 'enquiry_comment_accommodation_meal':
				return $this->_oInquiry->enquiry_accommodation_meal;
			case 'enquiry_comment_transfer_category':
				return $this->_oInquiry->enquiry_transfer_category;
			case 'enquiry_comment_transfer_location':
				return $this->_oInquiry->enquiry_transfer_location;
			case 'promotion_code':
				return (string)$this->_oInquiry->promotion;
			case 'total_amount_lessons_category':

				$iCategoryId = false;
				if (!empty($aPlaceholder['modifier']) && $aPlaceholder['modifier'] === 'id') {
					$iCategoryId = (int)$aPlaceholder['parameter'];
				}

				$sReturn = $oInquiry->getAllocatedLessonsCount($iCategoryId);

				break;
			default:
				$bNotYetFound = true;
				break;
		}

		# START Flex Placeholder #
		if($bNotYetFound) {

			$sPlaceholder = $sField;

			$sFlexCategorie = Ext_TC_Flexibility::getPlaceholderCategory($sPlaceholder);

			$bNotYetFound = false;
			switch($sFlexCategorie) {

				// Schulen
				case 'schools':

					$sReturn = '';
					$oSchool = $oInquiry->getSchool();
					if($oSchool){
						$aValue     = Ext_TC_Flexibility::getPlaceholderValue($sPlaceholder, $oSchool->id, true, $sDisplayLanguage);
						$sReturn    = $this->convertFlexPlaceholderInfo($aValue, $sFormat);
					}

					break;

				// Tuition » Courses
				// Student Record » Kursbuchung
				case 'tuition_courses':
				case 'student_record_journey_course':

					// if Placeholder not in Loop get the first ID
					if($iOptionalParentId <= 0){
						$aJourneyCourses = $oInquiry->getCourses();
						$oJourneyCourse = reset($aJourneyCourses);
					} else {
						// get Inquiry Course
						$oJourneyCourse = $this->_oInquiry->getServiceObject('course', $iOptionalParentId);
					}

					if($sFlexCategorie === 'student_record_journey_course') {
						$iId = $oJourneyCourse->id;
					} else {
						$iId = $oJourneyCourse->course_id;
					}

					$aValue = Ext_TC_Flexibility::getPlaceholderValue($sPlaceholder, $iId, true, $sDisplayLanguage);
					$sReturn = $this->convertFlexPlaceholderInfo($aValue, $sFormat);

					break;

				// Tuition » Course Categories
				case 'tuition_course_categories':

					// if Placeholder not in Loop get the first ID
					if($iOptionalParentId <= 0){
						$oCourse = $oInquiry->getCourse();
						// category id
						$iId = $oCourse->getField('category_id');
					} else {
						// get Inquiry Course
						$oCourse 	= $this->_oInquiry->getServiceObject('course', $iOptionalParentId);
						$oCourse 	= Ext_Thebing_Tuition_Course::getInstance($oCourse->course_id);
						// category id
						$iId = $oCourse->category_id;
					}

					$aValue = Ext_TC_Flexibility::getPlaceholderValue($sPlaceholder, $iId, true, $sDisplayLanguage);
					$sReturn = $this->convertFlexPlaceholderInfo($aValue, $sFormat);

					break;

				// Hier ist die OptionalParentId immer die Id des Elements
				case 'teachers':
					if($iOptionalParentId > 0){
						$iId = $iOptionalParentId;
					}else{
						$iId = $oInquiry->id;
					}
					$sSql = "SELECT
								*
							FROM
								`ts_teachers` AS `kt` INNER JOIN
								`kolumbus_tuition_blocks` AS `ktb` ON
									`ktb`.`teacher_id` = `kt`.`id` INNER JOIN
								`kolumbus_tuition_blocks_inquiries_courses` AS `ktbic` ON
									`ktbic`.`block_id` = `ktb`.`id`INNER JOIN
								`ts_inquiries_journeys_courses` AS `kic` ON
									`kic`.`id` = `ktbic`.`inquiry_course_id` INNER JOIN
								`ts_inquiries_journeys` `ts_i_j` ON
									`ts_i_j`.`id` = `kic`.`journey_id` AND
									`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
									`ts_i_j`.`active` = 1
							WHERE
								`ts_i_j`.`inquiry_id` = :inquiry_id
					";

					$aSql['inquiry_id'] = $iId;
					$aData = DB::getPreparedQueryData($sSql,$aSql);
					$sValue = '';
					if(!empty($aData)){
						// Teacher ID of first Course
						$iTeacherId = $aData[0]['teacher_id'];
						$aValue = Ext_TC_Flexibility::getPlaceholderValue($sPlaceholder, $iTeacherId, true, $sDisplayLanguage);
						$sReturn = $this->convertFlexPlaceholderInfo($aValue, $sFormat);
					}

					break;
				case 'transfer_providers':

					if($iOptionalParentId > 0){
						$iId = $iOptionalParentId;
					}else{
						$iId = $oInquiry->tsp_provider;
					}
					$aValue = Ext_TC_Flexibility::getPlaceholderValue($sPlaceholder, $iId, true, $sDisplayLanguage);
					$sReturn = $this->convertFlexPlaceholderInfo($aValue, $sFormat);
					break;

				// Agencys
				case 'agencies':
					if($oInquiry->agency_id > 0){
						$iId = $oInquiry->agency_id;
						$aValue = Ext_TC_Flexibility::getPlaceholderValue($sPlaceholder, $iId, true, $sDisplayLanguage);
						$sReturn = $this->convertFlexPlaceholderInfo($aValue, $sFormat);
					} else {
						$sReturn = '';
					}
					break;

				// Unterkunftskategorien
				// @TODO meal_id? Funktioniert das hier überhaupt?
				// @TODO $this->_setJourneyAccommodation() einbauen? #4909
				case 'accommodations':

					$this->setServiceObject('accommodation');

					if(
						$iOptionalParentId <= 0 &&
						!$this->_oJourneyAccommodation instanceof Ext_TS_Service_Interface_Accommodation
					) {
						$aAccommdations = $oInquiry->getAccommodations(false);
						$iId = $aAccommdations[0]['meal_id'];
					} else {
						$iId = $this->_oJourneyAccommodation->accommodation_id;
					}

					$aValue = Ext_TC_Flexibility::getPlaceholderValue($sPlaceholder, $iId, true, $sDisplayLanguage);
					$sReturn = $this->convertFlexPlaceholderInfo($aValue, $sFormat);
					break;
				// Verpflegung
				case 'meals':
					if(
						$iOptionalParentId <= 0 &&
						!$this->_oJourneyAccommodation instanceof Ext_TS_Service_Interface_Accommodation
					) {
						$aAccommdations = $oInquiry->getAccommodations(false);
						$iId = $aAccommdations[0]['meal_id'];
					} else {
						$iId = $this->_oJourneyAccommodation->meal_id;
					}

					$aValue = Ext_TC_Flexibility::getPlaceholderValue($sPlaceholder, $iId, true, $sDisplayLanguage);
					$sReturn = $this->convertFlexPlaceholderInfo($aValue, $sFormat);
					break;
				// Raumarten
				case 'roomtypes':
					if(
						$iOptionalParentId <= 0 &&
						!$this->_oJourneyAccommodation instanceof Ext_TS_Service_Interface_Accommodation
					) {
						$aAccommdations = $oInquiry->getAccommodations(false);
						$iId = $aAccommdations[0]['roomtype_id'];
					} else {
						$iId = $this->_oJourneyAccommodation->roomtype_id;
					}

					$aValue = Ext_TC_Flexibility::getPlaceholderValue($sPlaceholder, $iId, true, $oContact->getLanguage());
					$sReturn = $this->convertFlexPlaceholderInfo($aValue, $sFormat);
					break;

				// Raumarten
				case 'airports':
					$iId = $oInquiry->tsp_airport;
					$aValue = Ext_TC_Flexibility::getPlaceholderValue($sPlaceholder, $iId, true, $sDisplayLanguage);
					$sReturn = $this->convertFlexPlaceholderInfo($aValue, $sFormat);
					break;

				// Inquiry - Student Record
				case 'student_record':
					$iId = $oInquiry->id;
					$aValue = Ext_TC_Flexibility::getPlaceholderValue($sPlaceholder, $iId, true, $sDisplayLanguage);
					$sReturn = $this->convertFlexPlaceholderInfo($aValue, $sFormat);
					break;

				// Klassenzimmer von der ersten zuweisung
				case 'tuition_course_classrooms':

					if(
						isset($this->_oTuitionAllocation) &&
						$this->_oTuitionAllocation instanceof Ext_Thebing_School_Tuition_Allocation
					) {
						$room = $this->_oTuitionAllocation->getRoom();
						$iId = $room->id;
					} else {
						$aBlock = $oInquiry->getFirstTuitionAllocation();
						$iId = $aBlock['classroomid'];
					}

					$aValue = Ext_TC_Flexibility::getPlaceholderValue($sPlaceholder, $iId, true, $sDisplayLanguage);
					$sReturn = $this->convertFlexPlaceholderInfo($aValue, $sFormat);
					break;

				// Unterkunftsanbieter
				case 'accommodation_providers':
					$this->setAccommodationAllocation();

					$iId = $this->_aAccommodationAllocation['family_id'];

					$aValue = Ext_TC_Flexibility::getPlaceholderValue($sPlaceholder, $iId, true, $sDisplayLanguage);
					$sReturn = $this->convertFlexPlaceholderInfo($aValue, $sFormat);

					break;

				case 'roomdata':
					$this->setAccommodationAllocation();

					$iId = $this->_aAccommodationAllocation['room_id'];

					$aValue = Ext_TC_Flexibility::getPlaceholderValue($sPlaceholder, $iId, true, $sDisplayLanguage);
					$sReturn = $this->convertFlexPlaceholderInfo($aValue, $sFormat);

					break;

				case 'tuition_attendance':

					if($this->_oInquiry instanceof Ext_TS_Inquiry) {

						$aValues = [];
						$aAllocationIds = $this->_getTuitionAllocationIds($sModifier);
						foreach($aAllocationIds as $iAllocationId) {
							$aValues[] = (array)Ext_TC_Flexibility::getPlaceholderValue($sPlaceholder, $iAllocationId, true, $sDisplayLanguage);
						}

						if($sModifier == 'average') {
							$aFlexValues = array();

							foreach($aValues as $aFlexData) {
								$aFlexValues[] = $aFlexData['value'];
							}

							$sReturn	= Ext_Thebing_Util::getAverageFromFormattedValue($aFlexValues);
						} else {
							$sReturn	= $this->convertFlexPlaceholderInfo($aValues[0], $sFormat);
						}
					}

					break;

				case 'inquiries_groups':
				case 'groups':

					if($oGroup !== null && $oGroup->exist()) {
						$aValue = Ext_TC_Flexibility::getPlaceholderValue($sPlaceholder, $oGroup->id, true, $sDisplayLanguage);
						$sReturn = $this->convertFlexPlaceholderInfo($aValue, $sFormat);
						break;
					}

					break;

				case 'enquiries':

					$aValue = Ext_TC_Flexibility::getPlaceholderValue($sPlaceholder, $this->_oInquiry->id, true, $sDisplayLanguage);
					$sReturn = $this->convertFlexPlaceholderInfo($aValue, $sFormat);

					break;

				case 'admin_users':

					$oUser = Access::getInstance();
					if($oUser instanceof Access_Backend) {
						$aValue = Ext_TC_Flexibility::getPlaceholderValue($sPlaceholder, $oUser->id, true, $sDisplayLanguage);
						$sReturn = $this->convertFlexPlaceholderInfo($aValue, $sFormat);
						break;
					}

					break;

				default:
					$bNotYetFound = true;
					break;
			}
		}

		# END Flex Palceholder #

		# START CourseCategories Placeholder #
		if($bNotYetFound){
			if(substr($sField,0,30)=='total_amount_lessons_category_') {

				$iCategoryId	= (int)substr($sField,30);
				$sReturn		= $oInquiry->getAllocatedLessonsCount($iCategoryId);

				$bNotYetFound = false;
			}
			if(substr($sField,0,18)=='last_level_course_') {
				$oInquiryCourse = null;
				if($this->_oJourneyCourse instanceof Ext_TS_Service_Interface_Course){
					//Loop
					$oInquiryCourse = $this->_oJourneyCourse;
				}

				$iCourseId		= (int)substr($sField ,18);

				$aOptions = array(
					'course_id' => $iCourseId,
					'language' => $this->getLanguage()
				);

				$oCourse = Ext_Thebing_Tuition_Course::getInstance($iCourseId);
				$oLevelGroup = $oCourse->getLevelgroup();
				// @todo Kurssprache direkt aus Buchung holen
				$aOptions['courselanguage_id'] = (int)$oLevelGroup->id;

				$sReturn		= $oInquiry->getLastLevel('name', $oInquiryCourse, $aOptions);

				$bNotYetFound = false;
			}
			if(substr($sField, 0, 14) == 'link_feedback_') {
				$iQuestionaryId = (int)substr($sField, 14);
				$sReturn = '[FEEDBACKLINK:'.$iQuestionaryId.':'.$this->_oInquiry->getJourney()->id.']';
				$bNotYetFound = false;
			}
		}

		if($bNotYetFound) {
			$aHookData = [
				'placeholder' => $sField,
				'pdf' => false,
				'value' => &$sReturn,
				'format' => &$sFormat,
				'language' => $sDisplayLanguage,
				'additional' => $mAdditional,
				'class' => $this,
			];

			System::wd()->executeHook('ts_inquiry_placeholder_replace', $aHookData);
		}

		if(!empty($sFormat)) {
			$mReturn = array(
				'value'		=> $sReturn,
				'format'	=> $sFormat,
				'language'	=> $sDisplayLanguage,
				'additional' => $mAdditional
			);
		} else {
			$mReturn = $sReturn ?? null;
		}

		return $mReturn;
	}

	public function getAllTags(){
		$aPlaceholder = self::getAllAvailableTags();
		foreach($aPlaceholder as $iKey => $sValue){
			preg_match('/{(.*)}/ms', $sValue, $aValue);
			$aTemp = explode('}', $aValue[1]);
			$aPlaceholder[$iKey] = $aTemp[0];
		}
		return $aPlaceholder;
	}

	/*
	 * Liefert die Flex Platzhalter zu einer Categorie zurück
	 */
	public static function getAllFlexTags($aCategories = null) {

		// Platzhalter aller
		if($aCategories === null) {
			$aCategories = array();
			$aSections = Ext_TC_Flexibility::getFlexSections(false);
			foreach((array)$aSections as $aSectionData){
				$aCategories[] = $aSectionData['category'];
			}
		}

		$aCategories = array_unique($aCategories);

		$aFlexFields = Ext_TC_Flexibility::getSectionFieldData($aCategories, true);

		$aPlaceholder = array();

		foreach((array)$aFlexFields as $oFlexField){
			$sPlaceholder = $oFlexField->placeholder;
			if(!empty($sPlaceholder)){
				$aPlaceholder[$sPlaceholder] = '{'.$sPlaceholder.'} <b>'.$oFlexField->description.'</b>';
			}
		}

		return $aPlaceholder;
	}

	/**
	 * Gibt die Menge aller Lektionen aller Kurse zurück
	 *
	 * @param Ext_TS_Inquiry_Abstract $oInquiry
	 * @param Ext_Thebing_School $oSchool
	 * @return string
	 */
	public function getLessionTotalAmount(Ext_TS_Inquiry_Abstract $oInquiry, Ext_Thebing_School $oSchool, $bFormat = true){

		$aCourses = $oInquiry->getCourses(false);
		$iLessons = 0;
		foreach($aCourses as $aCourse){
			$iOptionalParentId = $aCourse['id'];
			$iLessons += $this->getLessionAmount($iOptionalParentId, $oSchool, false);
		}

		$sReturn = $iLessons;
		if ($bFormat) {
			$sReturn  = Ext_Thebing_Format::Number($iLessons, null, $oSchool->id, false);
		}

		return $sReturn;

	}

	/**
	 *gibt die Menge der Lektionen zurück für den Angegeben Schleifen Kurs oder dem Ersten Kurs
	 * @param int $iJourneyCourseId
	 * @param Ext_Thebing_School $oSchool
	 * @param boolean $bFormat
	 * @return string
	 */
	public function getLessionAmount($iJourneyCourseId, Ext_Thebing_School $oSchool, $bFormat = true){

		$sReturn = '';

		$sCourseTable = $this->_oInquiry->getServiceTable('course');

		$sSql = " 
			SELECT
				SUM(`ts_ijclc`.`absolute`) `lessons_amount`
			FROM
				#course_table `kic` INNER JOIN
				`ts_tuition_courses_programs_services` `ts_tcps` ON
					`ts_tcps`.`program_id` = `kic`.`program_id` AND
					`ts_tcps`.`type` = '".\TsTuition\Entity\Course\Program\Service::TYPE_COURSE."' AND
					`ts_tcps`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys_courses_lessons_contingent` `ts_ijclc` ON
					`ts_ijclc`.`journey_course_id` = `kic`.`id` AND
					`ts_ijclc`.`program_service_id` = `ts_tcps`.`id` INNER JOIN   
				`kolumbus_tuition_courses` `ktc` ON
					`ktc`.`id` = `ts_tcps`.`type_id` AND
					`ktc`.`active` = 1 
			WHERE
				`kic`.`id` = :inquiry_course_id AND
				`kic`.`active` = 1
			GROUP BY
				`kic`.`id`
		";

		$aSql = array(
			'inquiry_course_id' => $iJourneyCourseId,
			'course_table'		=> $sCourseTable,
		);

		$iLessons = (float)DB::getQueryOne($sSql, $aSql);

		if($iJourneyCourseId > 0){
			$sReturn        = $iLessons;
			if($bFormat){
				$sReturn     = Ext_Thebing_Format::Number($iLessons, null, $oSchool->id, false);
			}
		}

		return $sReturn;
	}

	public static function getAllAvailableTags($iPlaceholderLib = 1, $bShowHeadlines = false, $aFilter=array(), $mTemplateType='') {
		global $_VARS;

		$oSchool						= Ext_Thebing_School::getSchoolFromSession();
//		$aCourseCategoriesPlaceholders	= $oSchool->getCourseCategoriesList('placeholder');
//		$aCombinationCoursePlaceholders	= $oSchool->getCombinationCoursePlaceholder();
		$sType = $_VARS['type'];

		$aFeedbackPlaceholders = self::getFeedbackPlaceholders();

		// Bei den Templates (GUI) gibt es dieses $_VARS['type'] nicht, aber $sTemplateType…
		if(
			empty($sType) &&
			$mTemplateType === 'document_studentrecord_additional_pdf'
		) {
			$sType = 'additional_document';
		}

		$aPlaceholder = array();

		// GENERAL
		if($bShowHeadlines) {
			$aHeadline = array();
			$aHeadline[0] = L10N::t('Generelle Platzhalter', 'Thebing » Placeholder');
			$aHeadline[1] = '';
			$aHeadline['type'] = 'headline';
			$aPlaceholder[] = $aHeadline;
		}

		$aPlaceholder[] = '{today}<b>'.L10N::t('Heute', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{date_entry}<b>'.L10N::t('Erstellungsdatum', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{system_user_name}<b>'.L10N::t('Benutzername (angemeldeter Benutzer)', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{system_user_firstname}<b>'.L10N::t('Vorname (angemeldeter Benutzer)', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{system_user_surname}<b>'.L10N::t('Nachname (angemeldeter Benutzer)', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{system_user_email}<b>'.L10N::t('E-Mail (angemeldeter Benutzer)', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{system_user_phone}<b>'.L10N::t('Telefon (angemeldeter Benutzer)', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{system_user_fax}<b>'.L10N::t('Fax (angemeldeter Benutzer)', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{school_name}<b>'.L10N::t('Schule', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{school_abbreviation}<b>'.L10N::t('Schule (Abkürzung)', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{school_address}<b>'.L10N::t('Adresse der Schule', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{school_address_addon}<b>'.L10N::t('Adresszusatz der Schule', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{school_zip}<b>'.L10N::t('PLZ der Schule', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{school_city}<b>'.L10N::t('Stadt der Schule', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{school_country}<b>'.L10N::t('Land der Schule', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{school_url}<b>'.L10N::t('URL der Schule', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{school_phone}<b>'.L10N::t('Telefon der Schule', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{school_phone2}<b>'.L10N::t('Telefon 2 der Schule', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{school_fax}<b>'.L10N::t('Fax der Schule', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{school_email}<b>'.L10N::t('E-Mail der Schule', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{school_bank_name}<b>'.L10N::t('Name der Bank der Schule', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{school_bank_code}<b>'.L10N::t('Bankleitzahl der Bank der Schule', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{school_bank_address}<b>'.L10N::t('Adresse der Bank der Schule', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{school_account_holder}<b>'.L10N::t('Kontoinhaber der Schule', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{school_account_number}<b>'.L10N::t('Kontonummer der Schule', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{school_iban}<b>'.L10N::t('IBAN der Schule', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{school_swift}<b>'.L10N::t('Swift der Schule', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{public_holidays}<b>'.L10N::t('Feiertage', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{public_holidays_booking}<b>'.L10N::t('Feiertage innerhalb des Buchungszeitraumes', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{inbox_name}<b>'.L10N::t('Inboxname', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{inbox_key}<b>'.L10N::t('Inbox-Key', 'Thebing » Placeholder').'</b>';

		// PDF platzhalter
		if(
			!$aFilter['communication'] && (
				!isset($aFilter['pdf_placeholder'])	||
				$aFilter['pdf_placeholder'] !== false
			)
		) {

			if($bShowHeadlines){
				$aHeadline = array();
				$aHeadline[0] = L10N::t('PDF Platzhalter', 'Thebing » Placeholder');
				$aHeadline[1] = '';
				$aHeadline['type'] = 'headline';
				$aPlaceholder[] = $aHeadline;
			}

			$aPlaceholder[] = '{pdf_document_number}<b>'.L10N::t('Buchungsnummer für PDFs', 'Thebing » Placeholder').'</b>';

			if($sType != 'additional_document') {
				$aPlaceholder[] = '{pdf_main_document_number}<b>'.L10N::t('Zeigt die Nummer des Ursprungsdokumentes an. (Rechnungen)', 'Thebing » Placeholder').'</b>';
				//$aPlaceholder[] = '{main_document_number}<b>'.L10N::t('Zeigt die Nummer des Ursprungsdokumentes an. (Dokumente)', 'Thebing » Placeholder').'</b>';
			}
			$aPlaceholder[] = '{pdf_today}<b>'.L10N::t('Aktuelles Datum für PDFs', 'Thebing » Placeholder').'</b>';

			$aPlaceholder[] = '{pdf_amount}<b>'.L10N::t('Bruttosumme für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_amount_net}<b>'.L10N::t('Nettosumme für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_amount_initalcost}<b>'.L10N::t('Vorortkosten für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_amount_provison}<b>'.L10N::t('Provision für PDFs', 'Thebing » Placeholder').'</b>';

			$aPlaceholder[] = '{pdf_amount_incl_vat}<b>'.L10N::t('Bruttosumme (inkl. Steuern) für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_amount_net_incl_vat}<b>'.L10N::t('Nettosumme (inkl. Steuern) für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_amount_vat}<b>'.L10N::t('Steuersumme für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_amount_initalcost_incl_vat}<b>'.L10N::t('Vorortkosten (inkl. Steuern) für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_amount_provison_incl_vat}<b>'.L10N::t('Provision (inkl. Steuern) für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_amount_course_incl_vat}<b>'.L10N::t('Brutto Kurssumme (inkl. Steuern) für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_amount_course_net_incl_vat}<b>'.L10N::t('Netto Kurssumme (inkl. Steuern) für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_amount_accommodation_incl_vat}<b>'.L10N::t('Brutto Unterkunftssumme (inkl. Steuern) für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_amount_accommodation_net_incl_vat}<b>'.L10N::t('Netto Unterkunftssumme (inkl. Steuern) für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_amount_transfer_incl_vat}<b>'.L10N::t('Brutto Transfersumme (inkl. Steuern) für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_amount_transfer_net_incl_vat}<b>'.L10N::t('Netto Transfersumme (inkl. Steuern) für PDFs', 'Thebing » Placeholder').'</b>';


			$aPlaceholder[] = '{pdf_amount_excl_vat}<b>'.L10N::t('Bruttosumme (excl. Steuern) für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_amount_net_excl_vat}<b>'.L10N::t('Nettosumme (excl. Steuern) für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_amount_initalcost_excl_vat}<b>'.L10N::t('Vorortkosten (excl. Steuern) für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_amount_provison_excl_vat}<b>'.L10N::t('Provision (excl. Steuern) für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_amount_course_excl_vat}<b>'.L10N::t('Brutto Kurssumme (excl. Steuern) für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_amount_course_net_excl_vat}<b>'.L10N::t('Netto Kurssumme (excl. Steuern) für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_amount_accommodation_excl_vat}<b>'.L10N::t('Brutto Unterkunftssumme (excl. Steuern) für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_amount_accommodation_net_excl_vat}<b>'.L10N::t('Netto Unterkunftssumme (excl. Steuern) für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_amount_transfer_excl_vat}<b>'.L10N::t('Brutto Transfersumme (excl. Steuern) für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_amount_transfer_net_excl_vat}<b>'.L10N::t('Netto Transfersumme (excl. Steuern) für PDFs', 'Thebing » Placeholder').'</b>';

			//$aPlaceholder[] = '{pdf_amount_reminder}<b>'.L10N::t('Restsumme für PDFs', 'Thebing » Placeholder').'</b>';
			//$aPlaceholder[] = '{pdf_amount_credit}<b>'.L10N::t('Guthaben für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_amount_finalpay}<b>'.L10N::t('Restzahlungsbetrag für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_amount_prepay}<b>'.L10N::t('Anzahlungssumme für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_amount_paid}<b>'.L10N::t('Summe der Zahlungen für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_date_prepay}<b>'.L10N::t('Anzahlungsdatum für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_date_finalpay}<b>'.L10N::t('Restzahlungsdatum für PDFs', 'Thebing » Placeholder').'</b>';

			$aPlaceholder[] = '{pdf_start_loop_paymentterms}.....{pdf_end_loop_tuition_paymentterms}<b>'.L10N::t('Durchläuft alle Zahlungsbedingungen', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_paymentterm_type}<b>'.L10N::t('Typ der Zahlungsbedingung (nur im Loop)', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_paymentterm_date}<b>'.L10N::t('Datum der Zahlungsbedingung (nur im Loop)', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{pdf_paymentterm_amount}<b>'.L10N::t('Betrag der Zahlungsbedingung (nur im Loop)', 'Thebing » Placeholder').'</b>';

		}

		// Dokumente Platzhalter (bezogen auf gewählter Adresse)
		if(!$aFilter['communication']) {

			if($bShowHeadlines){
				$aHeadline = array();
				$aHeadline[0] = L10N::t('Dokumente', 'Thebing » Placeholder');
				$aHeadline[1] = '';
				$aHeadline['type'] = 'headline';
				$aPlaceholder[] = $aHeadline;
			}

			//$aPlaceholder[] = '{document_addressee}<b>'.L10N::t('Adressat', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{document_firstname}<b>'.L10N::t('Vorname', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{document_surname}<b>'.L10N::t('Nachname', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{document_salutation}<b>'.L10N::t('Anrede', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{document_address}<b>'.L10N::t('Adresse ', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{document_address_addon}<b>'.L10N::t('Adresszusatz', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{document_zip}<b>'.L10N::t('PLZ', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{document_city}<b>'.L10N::t('Stadt', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{document_state}<b>'.L10N::t('Bundesland', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{document_country}<b>'.L10N::t('Land', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{document_company}<b>'.L10N::t('Firma', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{document_address_type}<b>'.L10N::t('Adressat-Typ', 'Thebing » Placeholder').'</b>';
			//$aPlaceholder[] = '{document_date}<b>'.L10N::t('Datum des Dokumentes', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{document_type}<b>'.L10N::t('Typ des Dokumentes', 'Thebing » Placeholder').'</b>';

		}

		// Receipt platzhalter
		if(
			!$aFilter['communication'] && (
				!isset($aFilter['receipt_placeholder']) ||
				$aFilter['receipt_placeholder'] !== false
			)
		){
			if($bShowHeadlines){
				$aHeadline = array();
				$aHeadline[0] = L10N::t('Quittung Platzhalter', 'Thebing » Placeholder');
				$aHeadline[1] = '';
				$aHeadline['type'] = 'headline';
				$aPlaceholder[] = $aHeadline;
			}

			$aPlaceholder[] = '{receipt_amount_paid}<b>'.L10N::t('bezahlter Betrag für Quittung', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{receipt_comment}<b>'.L10N::t('Kommentar für Quittung', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{receipt_method}<b>'.L10N::t('Methode für Quittung', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{receipt_date}<b>'.L10N::t('Datum der Quittung', 'Thebing » Placeholder').'</b>';
		}

		//Nummern
		if(!$aFilter['number']){
			if($bShowHeadlines){
				$aHeadline = array();
				$aHeadline[0] = L10N::t('Nummern', 'Thebing » Placeholder');
				$aHeadline[1] = '';
				$aHeadline['type'] = 'headline';
				$aPlaceholder[] = $aHeadline;
			}
			if(!$aFilter['email']){
				$aPlaceholder[] = '{document_number}<b>'.L10N::t('Buchungsnummer für E-Mails', 'Thebing » Placeholder').'</b>';

				if($sType == 'additional_document') {
					$aPlaceholder[] = '{main_document_number}<b>'.L10N::t('Zeigt die Nummer des Ursprungsdokumentes an. (Dokumente)', 'Thebing » Placeholder').'</b>';
				}
			}
			$aPlaceholder[] = '{customernumber}<b>'.L10N::t('Kundennummer', 'Thebing » Placeholder').'</b>';
		}

		//Daten des Kunden
		if($bShowHeadlines){
			$aHeadline = array();
			$aHeadline[0] = L10N::t('Kunden Daten', 'Thebing » Placeholder');
			$aHeadline[1] = '';
			$aHeadline['type'] = 'headline';
			$aPlaceholder[] = $aHeadline;
		}
		$aPlaceholder[] = '{firstname}<b>'.L10N::t('Vorname', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{surname}<b>'.L10N::t('Nachname', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{user_name}<b>'.L10N::t('Benutzername', 'Thebing » Placeholder').'</b>';
		if(
			is_array($mTemplateType) &&
			in_array('mobile_app_forgotten_password', $mTemplateType)
		) {
			$aPlaceholder[] = '{user_login_code}<b>'.L10N::t('Login-Code (Passwort vergessen)', 'Thebing » Placeholder').'</b>';
		} else {
			$aPlaceholder[] = '{user_password}<b>'.L10N::t('Neues Password', 'Thebing » Placeholder').'</b>';
		}
		$aPlaceholder[] = '{booker_user_name}<b>'.L10N::t('Name des Buchungskontaktes', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{booker_password}<b>'.L10N::t('Passwort des Buchungskontaktes', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{age}<b>'.L10N::t('Alter', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{salutation}<b>'.L10N::t('Anrede', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{birthdate}<b>'.L10N::t('Geburtsdatum', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{gender}<b>'.L10N::t('Geschlecht', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{nationality}<b>'.L10N::t('Nationalität', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{mothertongue}<b>'.L10N::t('Muttersprache', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{address}<b>'.L10N::t('Addresse', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{address_addon}<b>'.L10N::t('Adresszusatz', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{zip}<b>'.L10N::t('PLZ', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{city}<b>'.L10N::t('Stadt', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{state}<b>'.L10N::t('Bundesland', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{country}<b>'.L10N::t('Land', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{phone_home}<b>'.L10N::t('Telefon', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{phone_mobile}<b>'.L10N::t('Handy', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{phone_office}<b>'.L10N::t('Telefon Büro', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{fax}<b>'.L10N::t('Fax', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{email}<b>'.L10N::t('E-Mail', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{customer_need_visum}<b>'.L10N::t('Kunde: Wir ein Visum benötigt?', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{social_security_number}<b>'.L10N::t('Solzialversicherungsnummer', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{arrival_comment}<b>'.L10N::t('Anreise', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{departure_comment}<b>'.L10N::t('Abreise', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{other}<b>'.L10N::t('Kunde: Sonstiges', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{profession}<b>'.L10N::t('Beruf', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{company}<b>'.L10N::t('Firma', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{billing_address}<b>'.L10N::t('Rechnungsdaten: Addresse', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{billing_zip}<b>'.L10N::t('Rechnungsdaten: Zip', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{billing_city}<b>'.L10N::t('Rechnungsdaten: Stadt', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{billing_country}<b>'.L10N::t('Rechnungsdaten: Land', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{billing_name}<b>'.L10N::t('Rechnungsdaten: Vorname', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{billing_surname}<b>'.L10N::t('Rechnungsdaten: Nachname', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{billing_state}<b>'.L10N::t('Rechnungsdaten: Bundesland', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{customer_state}<b>'.L10N::t('Status d. Schülers', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{emergency_contact_person}<b>'.L10N::t('Notfallkontakt Person', 'Thebing  Placeholder').'</b>';
		$aPlaceholder[] = '{emergency_contact_phone}<b>'.L10N::t('Notfallkontakt Telefon', 'Thebing  Placeholder').'</b>';
		$aPlaceholder[] = '{emergency_contact_email}<b>'.L10N::t('Notfallkontakt E-Mail', 'Thebing  Placeholder').'</b>';
		$aPlaceholder[] = '{sales_person}<b>'.L10N::t('Vertriebsmitarbeiter', 'Thebing Placeholder').'</b>';

		// Visa
		if(
			!isset($aFilter['visa']) ||
			$aFilter['visa'] !== false
		){
			if($bShowHeadlines){
				$aHeadline = array();
				$aHeadline[0] = L10N::t('Visum', 'Thebing » Placeholder');
				$aHeadline[1] = '';
				$aHeadline['type'] = 'headline';
				$aPlaceholder[] = $aHeadline;
			}
			$aPlaceholder[] = '{passport_number}<b>'.L10N::t('Passnummer', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{passport_valid_from}<b>'.L10N::t('Startdatum', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{passport_valid_until}<b>'.L10N::t('Ablaufdatum', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{visa_id}<b>'.L10N::t('Sevis ID', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{visa_mail_tracking_number}<b>'.L10N::t('Mail tracking number', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{visa_status}<b>'.L10N::t('Visa Status', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{visa_valid_from}<b>'.L10N::t('Visum gültig von', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{visa_valid_until}<b>'.L10N::t('Visum gültig bis', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{visa_valid_duration|days}<b>'.L10N::t('Visum - Gültigkeitsdauer', 'Thebing » Placeholder').'</b>';
		}

		if(
			!isset($aFilter['group']) ||
			$aFilter['group'] !== false
		){
			//Gruppen Daten
			if($bShowHeadlines){
				$aHeadline = array();
				$aHeadline[0] = L10N::t('Gruppen Daten', 'Thebing » Placeholder');
				$aHeadline[1] = '';
				$aHeadline['type'] = 'headline';
				$aPlaceholder[] = $aHeadline;
			}
			$aPlaceholder[] = '{group_name}<b>'.L10N::t('Gruppe: Namen (kurz)', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{group_number}<b>'.L10N::t('Gruppe: Nummer', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{group_address}<b>'.L10N::t('Gruppe: Anschrift', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{group_customers}<b>'.L10N::t('Gruppe: Mitglieder', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{group_city}<b>'.L10N::t('Gruppe: Stadt', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{group_zip}<b>'.L10N::t('Gruppe: PLZ', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{group_country}<b>'.L10N::t('Gruppe: Land', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{group_address_addon}<b>'.L10N::t('Gruppe: Adresse Zusatz', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{group_state}<b>'.L10N::t('Gruppe: Staat', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{group_count_member}<b>'.L10N::t('Gruppe: Anzahl Mitglieder', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{group_count_leader}<b>'.L10N::t('Gruppe: Anzahl Gruppenleiter', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{group_count_member_excl_leader}<b>'.L10N::t('Gruppe: Anzahl Mitglieder ohne Gruppenleiter', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{group_contact_firstname}<b>'.L10N::t('Gruppe: Kontaktvorname', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{group_contact_surname}<b>'.L10N::t('Gruppe: Kontaktname', 'Thebing » Placeholder').'</b>';
//			$aPlaceholder[] = '{start_loop_group_members}.....{end_loop_group_members}<b>'.L10N::t('Durchläuft alle Kunden der Gruppe, der komplette Text sowie die Platzhalter, die dazwischen stehen, werden jeweils wiederholt', 'Thebing » Placeholder').'</b>';
		}

		if(
			!isset($aFilter['course']) ||
			$aFilter['course'] !== false
		){
			//Kurse und Anwesenheit
			if($bShowHeadlines){
				$aHeadline = array();
				$aHeadline[0] = L10N::t('Kurse und Anwesenheit', 'Thebing » Placeholder');
				$aHeadline[1] = '';
				$aHeadline['type'] = 'headline';
				$aPlaceholder[] = $aHeadline;
			}
			$aPlaceholder[] = '{start_loop_courses}.....{end_loop_courses}<b>'.L10N::t('Durchläuft alle gebuchten Kurse, Der Komplette Text sowie die Platzhalter die dazwischen stehen werden jeweils wiederholt', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{course}<b>'.L10N::t('Kurs: Name', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{course_abbreviation}<b>'.L10N::t('Kurs: Abkürzung', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{course_language}<b>'.L10N::t('Kurs: Sprache', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{course_weeks}<b>'.L10N::t('Kurs: Wochenanzahl', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{course_category}<b>'.L10N::t('Kurs: Kategorie', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{course_max_students}<b>'.L10N::t('Kurs: Maximale Schüleranzahl', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{date_course_start}<b>'.L10N::t('Kurs: Start', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{date_course_end}<b>'.L10N::t('Kurs: Ende', 'Thebing » Placeholder').'</b>';
			//$aPlaceholder[] = '{eduleave}<b>'.L10N::t('Bildungsurlaub', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{lessons_per_week}<b>'.L10N::t('Lektionen pro Woche', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{tuition_allocated_days}<b>'.L10N::t('Anzahl der in der Klassenplanung zugewiesenen Tage', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{attendance_days_present_completely}<b>'.L10N::t('Anzahl der Tage kompletter Anwesenheit', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{attendance_days_absent_completely}<b>'.L10N::t('Anzahl der Tage kompletter Abwesenheit', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{attendance_days_absent_partially}<b>'.L10N::t('Anzahl der Tage teilweiser Abwesenheit', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{holiday_days}<b>'.L10N::t('Anzahl der Ferientage (während der Kurswoche)', 'Thebing » Placeholder').'</b>';
			//$aPlaceholder[] = '{block_attendance}<b>'.L10N::t('Anwesenheit pro Zuweisung', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{course_attendance}<b>'.L10N::t('Anwesenheit pro Kurs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{course_attendance_expected}<b>'.L10N::t('Erwartete Anwesenheit pro Kurs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{student_attendance}<b>'.L10N::t('Anwesenheit der Buchung', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{student_attendance_expected}<b>'.L10N::t('Erwartete Anwesenheit der Buchung', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{student_attendance_overall}<b>'.L10N::t('Anwesenheit über alle Buchungen', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{attendance_per_week}<b>'.L10N::t('Anwesenheit pro Woche', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{normal_level}<b>'.L10N::t('Kurs: Level', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{last_level}<b>'.L10N::t('Sprachniveau eines Schülers am Ende des Kurses (interne Niveau) ', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{highest_level}<b>'.L10N::t('Ermittelt das höchste Sprachniveau eines Schülers oder Kurses (interne Niveau) ', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{lessons_amount}<b>'.L10N::t('Gesamtanzahl der Lektionen', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{lessons_amount_total}<b>'.L10N::t('Gesamtanzahl der Lektionen aller Kurse', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{lessons_attended}<b>'.L10N::t('Anzahl der besuchten Lektionen', 'Thebing » Placeholder').'</b>';
			//$aPlaceholder[] = '{lessons_missed}<b>'.L10N::t('Anzahl der verpassten Lektionen', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{date_first_course_start}<b>'.L10N::t('Startdatum des ersten Kurses', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{date_last_course_end}<b>'.L10N::t('Enddatum des letzten Kurses', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{total_course_weeks_absolute}<b>'.L10N::t('Gesamtzahl der gebuchten Kurswochen (absolut)', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{total_course_weeks_relative}<b>'.L10N::t('Gesamtzahl der gebuchten Kurswochen (relativ)', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{start_loop_course_weeks}.....{end_loop_course_weeks}<b>'.L10N::t('Durchläuft alle Wochen des gebuchten Kurses, Der Komplette Text sowie die Platzhalter die dazwischen stehen werden jeweils wiederholt', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{week_date_from}<b>'.L10N::t('Gibt innerhalb der Kurswochen-Schleife das Datum des Wochenanfangs aus.').'</b>';
			$aPlaceholder[] = '{start_first_course_week}.....{end_first_course_week}<b>'.L10N::t('Durchläuft die erste Woche des Kurses mit der ersten Zuweisung. Der komplette Text, sowie die Platzhalter, die dazwischen stehen, werden jeweils wiederholt.', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{start_latest_course_week}.....{end_latest_course_week}<b>'.L10N::t('Durchläuft die letzte Woche des Kurses mit der spätesten Zuweisung. Der komplette Text, sowie die Platzhalter, die dazwischen stehen, werden jeweils wiederholt.', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{start_loop_tuition_blocks}.....{end_loop_tuition_blocks}<b>'.L10N::t('Durchläuft vom Kurs alle Zuweisungen in der Klassenplanung, Der Komplette Text sowie die Platzhalter die dazwischen stehen werden jeweils wiederholt', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{course_week_status}<b>'.L10N::t('Aktueller Status der Kurswoche').' (first, last, current, next, inner)</b>';
			$aPlaceholder[] = '{course_week_is_first}<b>'.L10N::t('Aktuelle Kurswoche ist erste Woche (true, false)').'</b>';
			$aPlaceholder[] = '{attendance_filter_week}<b>'.L10N::t('Gibt 1 zurück, wenn die aktuelle Kurswoche in den in der Anwesenheit ausgewählten Wochenzeitraum passt.').'</b>';
			$aPlaceholder[] = '{class_name}<b>'.L10N::t('Name der Klasse', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{class_content}<b>'.L10N::t('Inhalt der Klasse', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{teacher_firstname}<b>'.L10N::t('Vorname des Lehrers', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{teacher_lastname}<b>'.L10N::t('Nachname des Lehrers', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{attendance_note}<b>'.L10N::t('Kommentar in der Anwesenheit', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{attendance_score}<b>'.L10N::t('Score in der Anwesenheit', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{weekdays}<b>'.L10N::t('Tage an denen die Klasse / der Block geplant ist', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{times}<b>'.L10N::t(' Uhrzeiten an denen die Klasse / der Block stattfindet', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{weekdays_times}<b>'.L10N::t('Tag und Uhrzeit an denen die Klasse / der Block stattfindet', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{tuition_block_dates}<b>'.L10N::t('Daten an denen die Klasse / der Block stattfindet', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{classroom}<b>'.L10N::t('Raum in der die Klasse / der Block stattfindet', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{building}<b>'.L10N::t('Gebäude in der die Klasse / der Block stattfindet', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{floor}<b>'.L10N::t('Etage in der die Klasse / der Block stattfindet', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{course_note}<b>'.L10N::t('Kurskommentar', 'Thebing » Placeholder').'</b>';

			$aCategoryModifiers = array_map(fn ($data) => 'id:'.$data['id'], $oSchool->getCourseCategoriesList());
			$aPlaceholder[] = '{total_amount_lessons_category}<b>'.sprintf(L10N::t('Gesamtanzahl aller Lektionen (Kategorie eingrenzbar mit Modifier: %s)', 'Thebing » Placeholder'), implode(', ', $aCategoryModifiers)).'</b>';

//			$aPlaceholder = array_merge($aPlaceholder,$aCourseCategoriesPlaceholders);
//			$aPlaceholder = array_merge($aPlaceholder,$aCombinationCoursePlaceholders);
		}

		if(
			!isset($aFilter['accommodation']) ||
			$aFilter['accommodation'] !== false
		){
			//Unterkunft
			if($bShowHeadlines){
				$aHeadline = array();
				$aHeadline[0] = L10N::t('Unterkunft', 'Thebing » Placeholder');
				$aHeadline[1] = '';
				$aHeadline['type'] = 'headline';
				$aPlaceholder[] = $aHeadline;
			}
			$aPlaceholder[] = '{start_loop_accommodations} ..... {end_loop_accommodations}<b>'.L10N::t('Durchläuft alle gebuchten Unterkunfte. Der Komplette Text sowie die Platzhalter die dazwischen stehen werden jeweils wiederholt', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{start_loop_roommate} ..... {end_loop_roommate}<b>'.L10N::t('Durchläuft alle Zuweisungen bei der Familien. die Zeitgleich vorliegen (nur bestimmte Platzhalter), Der Komplette Text sowie die Platzhalter die dazwischen stehen werden jeweils wiederholt', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{start_selected_entries} ..... {end_selected_entries}<b>'.L10N::t('Durchläuft alle markierten Einträge. Der Komplette Text sowie die Platzhalter die dazwischen stehen werden jeweils wiederholt', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_weeks}<b>'.L10N::t('Unterkunft: Wochen', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{date_accommodation_end}<b>'.L10N::t('Unterkunft: Ende', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{date_accommodation_start}<b>'.L10N::t('Unterkunft: Start', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{date_first_accommodation_start}<b>'.L10N::t('Startdatum der ersten Unterkunft', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{date_last_accommodation_end}<b>'.L10N::t('Enddatum der letzten Unterkunft', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{roomtype}<b>'.L10N::t('Unterkunft: Raumtype', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{roomtype_full}<b>'.L10N::t('Unterkunft: Raumtype Ganzername', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_meal}<b>'.L10N::t('Unterkunft: Verpflegung', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_meal_full}<b>'.L10N::t('Unterkunft: Verpflegung (lang)', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_category}<b>'.L10N::t('Unterkunft: Kategorie', 'Thebing » Placeholder').'</b>';
			//$aPlaceholder[] = '{allocation_overlapping}<b>'.L10N::t('Zuweisung: Zeitüberschneidung von in selber Familie zugewiesenen Personen (nur roommate Schleife)', 'Thebing » Placeholder').'</b>';
		}

		// Alte GUI
		//$aPlaceholder[] = '{student_data_start}{student_data_end}<b>'.L10N::t('Unterkunftkommunikation: Liste der Kunden', 'Thebing » Placeholder').'</b>';

		if(
			!isset($aFilter['transfer']) ||
			$aFilter['transfer'] !== false
		){
			//An/Abreise
			if($bShowHeadlines){
				$aHeadline = array();
				$aHeadline[0] = L10N::t('Transfer An-/Abreise', 'Thebing » Placeholder');
				$aHeadline[1] = '';
				$aHeadline['type'] = 'headline';
				$aPlaceholder[] = $aHeadline;
			}
			$aPlaceholder[] = '{transfer_booked}<b>'.L10N::t('Transfer: Gebucht?', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{booked_transfer}<b>'.L10N::t('Transfer: Art', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{transfer_comment}<b>'.L10N::t('Transfer: Kommentar', 'Thebing » Placeholder').'</b>';

			$aPlaceholder[] = '{arrival_airline}<b>'.L10N::t('Transfer-Ankunft: Fluggesellschaft', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{departure_airline}<b>'.L10N::t('Transfer-Abflug: Fluggesellschaft', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{arrival_date}<b>'.L10N::t('Transfer-Ankunft: Datum', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{departure_date}<b>'.L10N::t('Transfer-Abflug: Datum', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{arrival_time}<b>'.L10N::t('Transfer-Ankunft: Uhrzeit', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{arrival_pickup_time}<b>'.L10N::t('Transfer-Anreise: Uhrzeit für die Abholung', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{departure_time}<b>'.L10N::t('Transfer-Abflug: Uhrzeit', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{departure_pickup_time}<b>'.L10N::t('Transfer-Abflug: Uhrzeit für die Abholung', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{arrival_flightnumber}<b>'.L10N::t('Transfer-Ankunft: Flugnummer', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{departure_flightnumber}<b>'.L10N::t('Transfer-Abflug: Flugnummer', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{arrival_pick_up}<b>'.L10N::t('Transfer-Ankunft: Aufnahme', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{arrival_drop_off}<b>'.L10N::t('Transfer-Ankunft: Abgabe', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{arrival_pick_up_additional}<b>'.L10N::t('Transfer-Anreise: Treffpunkt am Anreiseort', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{arrival_drop_off_additional}<b>'.L10N::t('Transfer-Anreise: Treffpunkt am Abreiseort', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{departure_pick_up}<b>'.L10N::t('Transfer-Abflug: Aufnahme', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{departure_drop_off}<b>'.L10N::t('Transfer-Abflug: Abgabe', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{departure_pick_up_additional}<b>'.L10N::t('Transfer-Abreise: Treffpunkt am Anreiseort', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{departure_drop_off_additional}<b>'.L10N::t('Transfer-Abreise: Treffpunkt am Abreiseort', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{arrival_airline}<b>'.L10N::t('Transfer-Ankunft: Fluggesellschaft', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{transfer_type}<b>'.L10N::t('Transfer: Typ', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{transfer_type_key}<b>'.L10N::t('Transfer: gibt den Typ Schlüssel als Integer zurück 0 steht für Individueller Transfer, 1 für Anreise und 2 für Abreise', 'Thebing » Placeholder').'</b>';

			$aPlaceholder[] = '{arrival_transfer_company}<b>'.L10N::t('Ankunft: Transfer Anbieter', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{arrival_transfer_company_phone}<b>'.L10N::t('Ankunft: Transfer Anbieter Telefon', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{arrival_transfer_company_mobile}<b>'.L10N::t('Ankunft: Transfer Anbieter Handy', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{arrival_transfer_driver}<b>'.L10N::t('Ankunft: Transfer Fahrer', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{arrival_transfer_driver_emergency}<b>'.L10N::t('Ankunft: Transfer Fahrer Notfall', 'Thebing » Placeholder').'</b>';

			$aPlaceholder[] = '{departure_transfer_company}<b>'.L10N::t('Abflug: Transfer Anbieter', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{departure_transfer_company_phone}<b>'.L10N::t('Abflug: Transfer Anbieter Telefon', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{departure_transfer_company_mobile}<b>'.L10N::t('Abflug: Transfer Anbieter Handy', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{departure_transfer_driver}<b>'.L10N::t('Abflug: Transfer Fahrer', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{departure_transfer_driver_emergency}<b>'.L10N::t('Abflug: Transfer Fahrer Notfall', 'Thebing » Placeholder').'</b>';

			// Individueller Transfer
			if($bShowHeadlines){
				$aHeadline = array();
				$aHeadline[0] = L10N::t('Individueller Transfer', 'Thebing » Placeholder');
				$aHeadline[1] = '';
				$aHeadline['type'] = 'headline';
				$aPlaceholder[] = $aHeadline;
			}

			$aPlaceholder[] = '{start_loop_individual_transfer}.....{end_loop_individual_transfer}<b>'.L10N::t('Durchläuft alle gebuchten Versicherungen, der komplette Text sowie die Platzhalter, die dazwischen stehen, werden jeweils wiederholt', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{individual_transfer_date}<b>'.L10N::t('Transfer-Individuell: Datum', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{individual_transfer_time}<b>'.L10N::t('Transfer-Individuell: Zeit', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{individual_transfer_pick_up_location}<b>'.L10N::t('Transfer-Individuell: Abreise', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{individual_transfer_drop_off_location}<b>'.L10N::t('Transfer-Individuell: Ankunft', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{individual_transfer_pick_up_location_additional}<b>'.L10N::t('Transfer-Individuell: Abreise Treffpunkt', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{individual_transfer_drop_off_location_additional}<b>'.L10N::t('Transfer-Individuell: Ankunft Treffpunkt', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{individual_transfer_comment}<b>'.L10N::t('Transfer-Individuell: Kommentar', 'Thebing » Placeholder').'</b>';






		}
		/*
				//Transferkommunikation
				if($bShowHeadlines){
					$aHeadline = array();
					$aHeadline[0] = L10N::t('Transferkommunikation', 'Thebing » Placeholder');
					$aHeadline[1] = '';
					$aHeadline['type'] = 'headline';
					$aPlaceholder[] = $aHeadline;
				}

				$aPlaceholder[] = '{transfer_communication}<b>'.L10N::t('Transferkommunikation: Tabelle', 'Thebing » Placeholder').'</b>';
				$aPlaceholder[] = '{transfer_provider_title}<b>'.L10N::t('Transferkommunikation: Provider Titel', 'Thebing » Placeholder').'</b>';
				$aPlaceholder[] = '{transfer_provider_firstname}<b>'.L10N::t('Transferkommunikation: Provider Vorname', 'Thebing » Placeholder').'</b>';
				$aPlaceholder[] = '{transfer_provider_lastname}<b>'.L10N::t('Transferkommunikation: Provider Nachname', 'Thebing » Placeholder').'</b>';
		*/

		//Agenturen
		if($bShowHeadlines){
			$aHeadline = array();
			$aHeadline[0] = L10N::t('Agentur Daten', 'Thebing » Placeholder');
			$aHeadline[1] = '';
			$aHeadline['type'] = 'headline';
			$aPlaceholder[] = $aHeadline;
		}
		$aPlaceholder[] = '{agency}<b>'.					L10N::t('Agentur: Name', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{agency_number}<b>'.				L10N::t('Agentur: Nummer', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{agency_abbreviation}<b>'.		L10N::t('Agentur: Abkürzung', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{agency_address}<b>'.			L10N::t('Agentur: Addresse', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{agency_zip}<b>'.				L10N::t('Agentur: ZIP', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{agency_city}<b>'.				L10N::t('Agentur: Stadt', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{agency_country}<b>'.			L10N::t('Agentur: Land', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{agency_groups}<b>'.				L10N::t('Agentur: Gruppe', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{agency_category}<b>'.			L10N::t('Agentur: Kategorie', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{agency_tax_number}<b>'.			L10N::t('Agentur: Steuernummer', 'Thebing » Placeholder').'</b>';
		//$aPlaceholder[] = '{agency_person}<b>'.				L10N::t('Agentur: Kontaktperson', 'Thebing » Placeholder').'</b>';
		//$aPlaceholder[] = '{agency_user_firstname}<b>'.		L10N::t('Agentur: Kontaktperson Vorname', 'Thebing » Placeholder').'</b>';
		//$aPlaceholder[] = '{agency_user_surname}<b>'.		L10N::t('Agentur: Kontaktperson Nachname', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{agency_state}<b>'.				L10N::t('Agentur: Staat', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{agency_note}<b>'.				L10N::t('Agentur: Kommentar', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{agency_payment_terms}<b>'.		L10N::t('Agentur: Bezahlinformation', 'Thebing » Placeholder').'</b>';
		//$aPlaceholder[] = '{agency_open_payments}<b>'.		L10N::t('Fällige Zahlungen bei der Agentur', 'Thebing » Placeholder').'</b>';

		$aPlaceholder[] = '{agency_staffmember_salutation}<b>'.		L10N::t('Agenturansprechpartner Anrede', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{agency_staffmember_firstname}<b>'.		L10N::t('Agenturansprechpartner Vorname', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{agency_staffmember_surname}<b>'.		L10N::t('Agenturansprechpartner Name', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{agency_staffmember_email}<b>'.			L10N::t('Agenturansprechpartner E-Mail', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{agency_staffmember_phone}<b>'.			L10N::t('Agenturansprechpartner Telefon', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{agency_staffmember_fax}<b>'.			L10N::t('Agenturansprechpartner Fax', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{agency_staffmember_skype}<b>'.			L10N::t('Agenturansprechpartner Skype', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{agency_staffmember_department}<b>'.		L10N::t('Agenturansprechpartner Department', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{agency_staffmember_responsability}<b>'.	L10N::t('Agenturansprechpartner Zuständichkeit', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{start_loop_agency_staffmembers}.....{end_loop_agency_staffmembers}<b>'.L10N::t('Durchläuft alle Agenturansprechpartner', 'Thebing » Placeholder').'</b>';
		$aPlaceholder[] = '{start_loop_students}.....{end_loop_students}<b>'.L10N::t('Durchläuft alle Kunden der Agentur', 'Thebing » Placeholder').'</b>';


		if(
			!isset($aFilter['agency_bank']) ||
			$aFilter['agency_bank'] !== false
		){
			//Agenturen Bankinformationen
			if($bShowHeadlines){
				$aHeadline = array();
				$aHeadline[0] = L10N::t('Agentur Bankinformationen', Ext_Thebing_Agency_Gui2::getDescriptionPart());
				$aHeadline[1] = '';
				$aHeadline['type'] = 'headline';
				$aPlaceholder[] = $aHeadline;
			}
			$aPlaceholder[] = '{agency_account_holder}<b>'.L10N::t('Kontoinhaber', Ext_Thebing_Agency_Gui2::getDescriptionPart()).'</b>';
			$aPlaceholder[] = '{agency_bank_name}<b>'.L10N::t('Name der Bank', Ext_Thebing_Agency_Gui2::getDescriptionPart()).'</b>';
			$aPlaceholder[] = '{agency_bank_code}<b>'.L10N::t('BLZ', Ext_Thebing_Agency_Gui2::getDescriptionPart()).'</b>';
			$aPlaceholder[] = '{agency_account_number}<b>'.L10N::t('Kontonummer', Ext_Thebing_Agency_Gui2::getDescriptionPart()).'</b>';
			$aPlaceholder[] = '{agency_swift}<b>'.L10N::t('SWIFT', Ext_Thebing_Agency_Gui2::getDescriptionPart()).'</b>';
			$aPlaceholder[] = '{agency_iban}<b>'.L10N::t('IBAN', Ext_Thebing_Agency_Gui2::getDescriptionPart()).'</b>';
		}


		/* Wird hier nicht mehr benötigt, funktioniert aber trotzdem falls gewünscht :)
				//Agentur Kontaktperson
				if($bShowHeadlines){
					$aHeadline = array();
					$aHeadline[0] = L10N::t('Agentur Kontaktperson', 'Thebing » Placeholder');
					$aHeadline[1] = '';
					$aHeadline['type'] = 'headline';
					$aPlaceholder[] = $aHeadline;
				}

				$aPlaceholder[] = '{agency_user_firstname}<b>'.L10N::t('Vorname Agenturkontaktperson', 'Thebing » Placeholder').'</b>';
				$aPlaceholder[] = '{agency_user_surname}<b>'.L10N::t('Nachname Agenturkontaktperson', 'Thebing » Placeholder').'</b>';
		*/


		if(
			!isset($aFilter['invoice']) ||
			$aFilter['invoice'] !== false
		){
			//Rechnung Daten
			if($bShowHeadlines){
				$aHeadline = array();
				$aHeadline[0] = L10N::t('Rechnung Daten', 'Thebing » Placeholder');
				$aHeadline[1] = '';
				$aHeadline['type'] = 'headline';
				$aPlaceholder[] = $aHeadline;
			}
			$aPlaceholder[] = '{amount_net}<b>'.L10N::t('Nettobetrag der letzen Rechnung', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{amount_net_all}<b>'.L10N::t('Nettobetrag aller Rechnungen', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{amount_gross}<b>'.L10N::t('Bruttobetrag der letzen Rechnung', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{amount_gross_all}<b>'.L10N::t('Bruttobetrag aller Rechnungen', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{amount}<b>'.L10N::t('Gesamtbetrag der letzen Rechnung', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{amount_all}<b>'.L10N::t('Gesamtbetrag aller Rechnungen', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{amount_open_all}<b>'.L10N::t('Offener Betrag aller Rechnungen basierend auf Hauptrechnungen', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{amount_paid}<b>'.L10N::t('Bezahlter Betrag', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{amount_prepay}<b>'.L10N::t('Anzahlungsbetrag der letzten Rechnung', 'Thebing » Placeholder').'</b>';
			//$aPlaceholder[] = '{amount_prepay_all}<b>'.L10N::t('Anzahlungsbetrag aller Rechnungen', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{date_prepay}<b>'.L10N::t('Anzahlungsdatum der letzten Rechnung', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{amount_finalpay}<b>'.L10N::t('Restzahlungsbetrag der letzten Rechnung', 'Thebing » Placeholder').'</b>';
			//$aPlaceholder[] = '{amount_finalpay_all}<b>'.L10N::t('Restzahlungsbetrag aller Rechnungen', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{date_finalpay}<b>'.L10N::t('Restzahlungsdatum der letzten Rechnung', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{amount_initalcost}<b>'.L10N::t('Betrag der Vor-Ort-Gebühren der letzten Rechnung', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{amount_initalcost_all}<b>'.L10N::t('Betrag der Vor-Ort-Gebühren aller Rechnungen', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{amount_credit}<b>'.L10N::t('Gutschrift', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{amount_course_incl_vat}<b>'.L10N::t('Brutto Kurssumme aller Rechnungen (inkl. Steuern) für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{amount_course_net_incl_vat}<b>'.L10N::t('Netto Kurssumme aller Rechnungen (inkl. Steuern) für PDFs', 'Thebing » Placeholder').'</b>';
//			$aPlaceholder[] = '{amount_course_discount}<b>'.L10N::t('Brutto Kursrabatt der letzten Rechnung', 'Thebing » Placeholder').'</b>';
//			$aPlaceholder[] = '{amount_course_discount_all}<b>'.L10N::t('Brutto Kursrabatt aller Rechnungen', 'Thebing » Placeholder');
			$aPlaceholder[] = '{amount_accommodation_incl_vat}<b>'.L10N::t('Brutto Unterkunftssumme aller Rechnungen (inkl. Steuern) für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{amount_accommodation_net_incl_vat}<b>'.L10N::t('Netto Unterkunftssumme aller Rechnungen (inkl. Steuern) für PDFs', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{provision}<b>'.L10N::t('Provision der letzten Rechnung', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{provision_all}<b>'.L10N::t('Provision aller Rechnungen', 'Thebing » Placeholder').'</b>';
		}

		//Unterkunftsanbieter
		if(
			!isset($aFilter['accommodation_provider']) ||
			$aFilter['accommodation_provider'] !== false
		){
			if($bShowHeadlines){
				$aHeadline = array();
				$aHeadline[0] = L10N::t('Unterkunftsanbieter Daten', 'Thebing » Placeholder');
				$aHeadline[1] = '';
				$aHeadline['type'] = 'headline';
				$aPlaceholder[] = $aHeadline;
			}
			$aPlaceholder[] = '{accommodation_room}<b>'.L10N::t('Unterkunft: Raum der ersten Belegung', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_bed}<b>'.L10N::t('Unterkunft: Bettnummer der ersten Belegung', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_provider_name}<b>'.L10N::t('Unterkunft: Provider', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_provider_email}<b>'.L10N::t('Unterkunft: Provider E-Mail', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_contact_firstname}<b>'.L10N::t('Unterkunft: Ansprechpartner (Vorname)', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_contact_lastname}<b>'.L10N::t('Unterkunft: Ansprechpartner (Nachname)', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_contact_salutation}<b>'.L10N::t('Unterkunft: Ansprechpartner (Salutation)', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_address}<b>'.L10N::t('Unterkunft: Adresse', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_address_addon}<b>'.L10N::t('Unterkunft: Adresszusatz', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_zip}<b>'.L10N::t('Unterkunft: PLZ', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_city}<b>'.L10N::t('Unterkunft: Stadt', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_state}<b>'.L10N::t('Unterkunft: Bundesland', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_country}<b>'.L10N::t('Unterkunft: Land', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_phone}<b>'.L10N::t('Unterkunft: Telefon', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_phone2}<b>'.L10N::t('Unterkunft: Telefon 2', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_mobile}<b>'.L10N::t('Unterkunft: Mobiltelefon', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_description}<b>'.L10N::t('Unterkunft: Beschreibung', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_route}<b>'.L10N::t('Unterkunft: Anfahrt', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_googlemaps}<b>'.L10N::t('Unterkunft: Link zu Google Maps', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_criteria_cats}<b>'.L10N::t('Unterkunft: Anbieter hat Katzen', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_criteria_dogs}<b>'.L10N::t('Unterkunft: Anbieter hat Hunde', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_criteria_other_pets}<b>'.L10N::t('Unterkunft: Anbieter hat andere Haustiere', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_criteria_smoker}<b>'.L10N::t('Unterkunft: Mitglieder die rauchen', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_criteria_air_conditioner}<b>'.L10N::t('Unterkunft: Anbieter hat Klimaanlage', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_criteria_internet}<b>'.L10N::t('Unterkunft: Anbieter hat Internet', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_criteria_bath}<b>'.L10N::t('Unterkunft: Anbieter bietet eigenes Bad', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_criteria_children}<b>'.L10N::t('Unterkunft: Anbieter hat Kinder', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{student_preference_family_age}<b>'.L10N::t('Alter der Familie', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{voucher_id}<b>'.L10N::t('Voucher ID', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_allocation_start}<b>'.L10N::t('Startdatum der Zuweisung', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_allocation_end}<b>'.L10N::t('Enddatum der Zuweisung', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_assigned_nights}<b>'.L10N::t('Zuweisung: Anzahl der Nächte in der Unterkunft', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_assigned_weeks}<b>'.L10N::t('Zuweisung: Anzahl der Wochen in der Unterkunft', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_expected_provider_payment}<b>'.L10N::t('Zuweisung: Erwartete Kosten für die Unterkunft', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_expected_provider_payment_transfer}<b>'.L10N::t('Zuweisung: Erwartete Kosten für den Transfer (wenn Unterkunftsanbieter Transferanbieter ist)', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_expected_provider_payment_with_transfer}<b>'.L10N::t('Zuweisung: Erwartete Kosten für die Unterkunft (Summe der beiden oberen Platzhalter)', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{start_loop_accommodation_allocations}.....{end_loop_accommodation_allocations}<b>'.L10N::t('Durchläuft alle Unterkunftszuweisungen, der komplette Text sowie die Platzhalter, die dazwischen stehen, werden jeweils wiederholt', 'Thebing » Placeholder').'</b>';
			/*$aPlaceholder[] = '{accommodation_member_salutation}<b>'.L10N::t('Unterkunftsanbieter: Zugehörige Anrede', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_member_firstname}<b>'.L10N::t('Unterkunftsanbieter: Zugehörige Vorname', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_member_surname}<b>'.L10N::t('Unterkunftsanbieter: Zugehörige Name', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_member_birthdate}<b>'.L10N::t('Unterkunftsanbieter: Zugehörige Geburtsdatum', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_member_email}<b>'.L10N::t('Unterkunftsanbieter: Zugehörige E-mail', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_member_phone}<b>'.L10N::t('Unterkunftsanbieter: Zugehörige Telefon', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_member_fax}<b>'.L10N::t('Unterkunftsanbieter: Zugehörige Fax', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_member_skype}<b>'.L10N::t('Unterkunftsanbieter: Zugehörige Skype', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{accommodation_member_relation}<b>'.L10N::t('Unterkunftsanbieter: Zugehörige Relation', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{start_loop_accommodation_member}.....{end_loop_accommodation_member}<b>'.L10N::t('Unterkunftsanbieter: Liste aller Zugehörigen', 'Thebing » Placeholder').'</b>';*/

		}

		if(
			!isset($aFilter['accommodation_preference']) ||
			$aFilter['accommodation_preference'] !== false
		){
			//Student preferences regarding the accommodation
			if($bShowHeadlines){
				$aHeadline = array();
				$aHeadline[0] = L10N::t('Unterkunfts Vorlieben', 'Thebing » Placeholder');
				$aHeadline[1] = '';
				$aHeadline['type'] = 'headline';
				$aPlaceholder[] = $aHeadline;
			}

			$aPlaceholder[] = '{matching_comment}<b>'.L10N::t('Matching: Kommentar', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{matching_comment_2}<b>'.L10N::t('Matching: Kommentar 2', 'Thebing » Placeholder').'</b>';

			$aPlaceholder[] = '{student_criteria_smoker}<b>'.L10N::t('Schüler: Raucher', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{student_criteria_vegetarian}<b>'.L10N::t('Schüler: Vegetarier', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{student_criteria_muslim_diet}<b>'.L10N::t('Schüler: Muslimische Diät', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{student_criteria_allergies}<b>'.L10N::t('Schüler: Allergien', 'Thebing » Placeholder').'</b>';

			$aPlaceholder[] = '{student_preference_cats}<b>'.L10N::t('Familie mit Katze ok', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{student_preference_dogs}<b>'.L10N::t('Familie mit Hund ok', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{student_preference_pets}<b>'.L10N::t('Familie mit anderen Haustieren ok', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{student_preference_smoker}<b>'.L10N::t('Unterkunft: Raucher', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{student_preference_distance}<b>'.L10N::t('Entfernung zur Schule', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{student_preference_air_conditioner}<b>'.L10N::t('Familie mit Klimaanlage gewünscht', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{student_preference_bath}<b>'.L10N::t('Schüler möchte ein eigenes Bad', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{student_preference_children}<b>'.L10N::t('Familie mit Kindern ok', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{student_preference_internet}<b>'.L10N::t('Schüler möchte Internet', 'Thebing » Placeholder').'</b>';

			$aPlaceholder[] = '{accommodation_share_with}<b>'.L10N::t('Unterkunft: Teilen mit', 'Thebing » Placeholder').'</b>';
		}

		if(
			!isset($aFilter['links']) ||
			$aFilter['links'] !== false
		){
			//Links to web
			if($bShowHeadlines){
				$aHeadline = array();
				$aHeadline[0] = L10N::t('Links', 'Thebing » Placeholder');
				$aHeadline[1] = '';
				$aHeadline['type'] = 'headline';
				$aPlaceholder[] = $aHeadline;
			}
			$aPlaceholder[] = '{link_feedback}<b>'.L10N::t('Link zum Feedback', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{payment_process_key|next}}<b>'.L10N::t('Key für Zahlungsprozess erzeugen', 'Thebing » Placeholder').'</b>';
		}

		if(
			!isset($aFilter['insurance']) ||
			$aFilter['insurance'] !== false
		){
			//Versicherungen
			if($bShowHeadlines){
				$aHeadline = array();
				$aHeadline[0] = L10N::t('Versicherungen', 'Thebing » Placeholder');
				$aHeadline[1] = '';
				$aHeadline['type'] = 'headline';
				$aPlaceholder[] = $aHeadline;
			}

			$aPlaceholder[] = '{start_loop_insurances}.....{end_loop_insurances}<b>'.L10N::t('Durchläuft alle gebuchten Versicherungen, der komplette Text sowie die Platzhalter, die dazwischen stehen, werden jeweils wiederholt', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{insurance}<b>'.L10N::t('Versicherung: Name', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{insurance_provider}<b>'.L10N::t('Versicherung: Anbieter', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{date_insurance_start}<b>'.L10N::t('Versicherung: Start', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{date_insurance_end}<b>'.L10N::t('Versicherung: Ende', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{insurance_price}<b>'.L10N::t('Versicherung: Preis', 'Thebing » Placeholder').'</b>';
		}

		// Feedbackformular
		if($bShowHeadlines){
			$aHeadline = array();
			$aHeadline[0] = L10N::t('Feedbackformulare', 'Thebing » Placeholder');
			$aHeadline[1] = '';
			$aHeadline['type'] = 'headline';
			$aPlaceholder[] = $aHeadline;
		}
		$aPlaceholder = array_merge($aPlaceholder, $aFeedbackPlaceholders);

		return $aPlaceholder;

	}

	public function getPlaceholders($sType = '') {}

	public function displayPlaceholderTable($iPlaceholderLib = 1, $aFilter = array(), $mTemplateType=false)
	{
		$aPlaceholderFlex = self::getAllFlexTags();

		$aFilter['communication'] = $this->bCommunication;
		$aPlaceholder = self::getAllAvailableTags($iPlaceholderLib, true, $aFilter, $mTemplateType);

		$aParentPlaceholders = $aLine = array();

		$aPlaceholder = self::clearPlaceholders($aPlaceholder);

		$aParentPlaceholders = array_merge($aParentPlaceholders, $aPlaceholder);

		if(!empty($aPlaceholderFlex))
		{
			$aSection = array(
				array(
					'type'	=> 'headline',
					0		=> L10N::t('Flexibility Placeholder')
				)
			);

			$aPlaceholderFlex = array_merge($aSection, $aPlaceholderFlex);

			$aPlaceholder = self::clearPlaceholders($aPlaceholderFlex);

			$aParentPlaceholders = array_merge($aParentPlaceholders, $aPlaceholder);
		}

		return parent::printPlaceholderList($aParentPlaceholders,$aFilter);
	}


	/*
	 * Funktion bereinige die Platzhalter Liste, entfernt
	 */
	public static function clearPlaceholders($aPlaceholder)
	{
		$aCleanPlaceholders = $aLine = array();

		foreach((array)$aPlaceholder as $sKey => $mTemp)
		{
			if(is_array($mTemp) && $mTemp['type'] = 'headline')
			{
				if(!empty($aLine))
				{
					$aCleanPlaceholders[] = $aLine;

					$aLine = array();
				}

				$aLine['section'] = $mTemp[0];
			}
			else
			{
				$iPosB = strpos($mTemp, '<b>');

				if($iPosB === false)
				{
					$iPosB = strlen($mTemp);
				}

				$sPlaceholder = substr($mTemp, 0, $iPosB);
				//$sPlaceholder = str_replace(array('{', '}'), '', $sPlaceholder);
				$sPlaceholder = trim($sPlaceholder);
				$sPlaceholder = ltrim($sPlaceholder, '{');
				$sPlaceholder = rtrim($sPlaceholder, '}');
				$sPlaceholder = trim($sPlaceholder);

				$aLine['placeholders'][$sPlaceholder] = substr($mTemp, $iPosB);
			}
		}

		// Add last section
		if(!empty($aLine))
		{
			$aCleanPlaceholders[] = $aLine;

			$aLine = array();
		}

		return $aCleanPlaceholders;
	}

	/**
	 * Achtung, Methode kann auch diese alte Util-Klasse zurückliefern!
	 *
	 * @param string $sDisplayLanguage
	 * @return Ext_Thebing_Tuition_Course|Ext_Thebing_Course_Util
	 */
	protected function _getCourse($sDisplayLanguage = ''){

		if($this->_oCourse instanceof Ext_Thebing_Tuition_Course){
			$oCourse = $this->_oCourse;
		}
		elseif($this->_oJourneyCourse instanceof Ext_TS_Service_Interface_Course) {
			$oJourneyCourse = $this->_oJourneyCourse;
			$oCourse		= Ext_Thebing_Tuition_Course::getInstance($oJourneyCourse->course_id);
		}else{
			// TODO Das muss dringend entfernt werden, da hier Platzhalter mal den einen oder anderen Wert erwarten
			$oCourse = $this->_oInquiry->getCourse($sDisplayLanguage);
		}

		return $oCourse;
	}

	protected function _getInquiryCourse() {

		// Analog zu setServiceObject(): Selektierter Eintrag aus GUI
		if(!$this->_oJourneyCourse instanceof Ext_TS_Service_Interface_Course) {
			list($oGui) = $this->getGuiAndSelectedIds();

			// In Anwesenheitsliste: Kurs aus selektiertem Eintrag ziehen
			if(
				$oGui instanceof Ext_Gui2 &&
				$oGui->getOption('selected_object_from_encoded_data') &&
				strpos($oGui->name, 'ts_attendance') !== false
			) {
				$mSelectedIdsData = $this->getOption('document_selected_ids_decoded');

				if(!empty($mSelectedIdsData['id'])) {
					$oAllocation = Ext_Thebing_School_Tuition_Allocation::getInstance($mSelectedIdsData['id']);
					$this->setTuitionAllocation($oAllocation);
				}
			}
		}

		if(!$this->_oJourneyCourse instanceof Ext_TS_Service_Interface_Course){

			$aInquiriesCourses	= $this->_oInquiry->getCourses(false);

			$iInquiryCourse		= 0;

			if(!empty($aInquiriesCourses)){
				$aInquiryCourse = reset($aInquiriesCourses);
				$iInquiryCourse	= (int)$aInquiryCourse['id'];
			}

			$oJourneyCourse		= $this->_oInquiry->getServiceObject('course', $iInquiryCourse);

			$this->_oJourneyCourse = $oJourneyCourse;
		}

		return $this->_oJourneyCourse;
	}

	/**
	 *
	 * @return Ext_TS_Inquiry_Contact_Abstract
	 */
	public function getCustomer()
	{
		return $this->_oCustomer;
	}

	/**
	 *
	 * @return Ext_Thebing_Agency
	 */
	public function getAgency()
	{
		$oAgency = $this->_oInquiry->getAgency();

		return $oAgency;
	}

	/**
	 *
	 * @return Ext_Thebing_Agency_Contact
	 */
	public function getAgencyMasterContact()
	{
		$oInquiry		= $this->_oInquiry;
		$oMasterContact = $oInquiry->getAgencyContact();

		return $oMasterContact;
	}

	/**
	 *
	 * @return Ext_Thebing_School
	 */
	public function getSchool()
	{
		$oSchool = $this->_oInquiry->getSchool();

		return $oSchool;
	}

	/**
	 *
	 * @return Ext_Thebing_Inquiry
	 */
	public function getMainObject()
	{
		return $this->_oInquiry;
	}

	/**
	 * Eine Zuweisung aus der Klassenplanung setzen
	 *
	 * @param Ext_Thebing_School_Tuition_Allocation $oAllocation
	 */
	public function setTuitionAllocation(Ext_Thebing_School_Tuition_Allocation $oAllocation) {

		$this->_oTuitionAllocation	= $oAllocation;

		$this->_oJourneyCourse		= $oAllocation->getJourneyCourse();

		$this->_oCourse				= $oAllocation->getCourse();
	}

	/**
	 * Anhand der gesetzten Objekte und welcher Modifier benutzt wurde Zuweisungs-IDS laden
	 *
	 * @param string $sModifier
	 * @return array
	 */
	protected function _getTuitionAllocationIds($sModifier)
	{
		if(
			$this->_oTuitionAllocation !== null &&
			$sModifier != 'average'
		)
		{
			// Wenn nicht Durschnitts-Modifier benutzt wurde und Klassenplanung-Zuweisung gesetzt wurde
			$mId	= array($this->_oTuitionAllocation->id);
		}
		else
		{
			if($this->_oJourneyCourse !== null)
			{
				// Aus ehemalig Ext_TS_Inquiry_Journey_Course::getTuitionAllocationIds() extrahiert
				if($this->_oJourneyCourse->iCurrentWeek !== null) {
					$dWeek = $this->_oJourneyCourse->getFrom();
					$oDate = new WDDate($dWeek, WDDate::DB_DATE);
					$oDate->set(1, WDDate::WEEKDAY);// Blockwoche fängt immer Montags an...
					$dWeek = $oDate->get(WDDate::DB_DATE);
					$aFilter['`ktb`.`week`'] = $dWeek;
				}

				$mId = Ext_Thebing_School_Tuition_Allocation::getAllocationsByInquiryCourse($this->id, $aFilter ?? []);
			}
			else
			{
				$mId	= $this->_oInquiry->getTuitionAllocationIds();
			}
		}

		return $mId;
	}

	/**
	 * Total bescheuert aber was solls, das erst beste Ergebnis holen falls bestimmte Platzhalter nicht in der
	 * Schleife benutzt wurden...
	 *
	 * @param array $aFilter
	 *
	 * @return Ext_Thebing_Tuition_Attendance
	 */
	protected function _getFirstAttendance($aFilter)
	{
		$iAttendanceId			= 0;

		if($this->_oInquiry instanceof Ext_TS_Inquiry)
		{
			$oAttendanceIndex	= new Ext_Thebing_Tuition_Attendance_Index();

			$aFilter['`ts_i_j`.`inquiry_id`'] = $this->_oInquiry->id;
		}
		elseif($this->_oJourneyCourse instanceof Ext_TS_Inquiry_Journey_Course)
		{
			$aFilter['`ts_i_j_c`.`id`'] = $this->_oJourneyCourse->id;
		}

		$iAttendanceId		= (int)$oAttendanceIndex->search($aFilter, 1);

		$oAttendance = Ext_Thebing_Tuition_Attendance::getInstance($iAttendanceId);

		return $oAttendance;
	}

	/**
	 *
	 * @return Ext_Thebing_School_Tuition_Allocation
	 */
	protected function _getFirstAllocation(array $aFilter = array())
	{
		$aIds = array();

		if($this->_oJourneyCourse instanceof Ext_TS_Inquiry_Journey_Course)
		{
			$aIds = Ext_Thebing_School_Tuition_Allocation::getAllocationsByInquiryCourse($this->_oJourneyCourse->id, $aFilter);
		}
		elseif($this->_oInquiry instanceof Ext_TS_Inquiry)
		{
			$aIds = Ext_Thebing_School_Tuition_Allocation::getAllocationIdsByInquiry($this->_oInquiry, $aFilter);
		}

		$iId = (int)reset($aIds);

		$oAllocation = Ext_Thebing_School_Tuition_Allocation::getInstance($iId);

		return $oAllocation;
	}

	/**
	 * Dokumentendialog wird in Dokumenten-GUI geöffnet, Kommunikationsdialog und Massendokumente-Dialog direkt in der GUI
	 *
	 * @return array
	 */
	protected function getGuiAndSelectedIds() {
		global $_VARS;

		$oGui = null;
		$aSelectedIds = array();

		if($this->oGui instanceof Ext_Gui2) {
			if(
				$this->bCommunication ||
				empty($_VARS['parent_gui_id'])
			) {
				$oGui = $this->oGui;
				$aSelectedIds = $_VARS['id'][0];
			} else {
				$oGui = $this->oGui->getParent();
				// Fallback
				if(empty($oGui)) {
					$oGui = $this->oGui->getParentClass();
				}
				$aSelectedIds = $_VARS['parent_gui_id'][0];
			}
		}

		return array($oGui, $aSelectedIds);
	}

	/**
	 * Service-Objekt bestimmen: In der GUI ausgewählt, erstbestes Objekt, sonstiger Zauber…
	 *
	 * Das wurde bisher immer über $iOptionalId oder direkte Injection der Objekte gemacht
	 * (redundant von Kommunikation und Dokumenten-Dialog), aber irgendwie funktionierte
	 * das alles nicht so richtig. Vor allem in der Unterkunftskommunikation oder
	 * Versicherungsliste wurde sich trotzdem die erstbeste Leistung geholt, obwohl man
	 * nicht die erste von einem Kunden auswählte.
	 *
	 * @TODO Das muss alles mal ordentlich refaktorisiert werden!
	 * Diese »Abhängigkeit zu ausgewähltem GUI-Eintrag« sowie die Loops sind ein einziges Chaos
	 * und das alles muss irgendwie zusammen funktionieren. Hier müsste bei den TC-Platzhaltern
	 * einfach ermöglicht werden, dass man das erste Objekt bei fehlendem Loop irgendwie
	 * überschreiben kann, denn die Thebing-Platzhalter sind nicht mehr zu retten.
	 *
	 * Fälle in der Schulsoftware:
	 * - Unterkunftskommunikation
	 * - Versicherungsliste
	 * - Transferliste (aber nicht der Transfer, sondern Unterkunftsdaten mit Methode)
	 * - Anwesenheit (Zuweisung, aber nur bei Kommunikation – setTuitionAllocation())
	 * - ???
	 *
	 * @param string $sType
	 * @throws Exception
	 */
	protected function setServiceObject($sType) {

		if($sType === 'accommodation') {
			$sObjectKey = '_oJourneyAccommodation';
			$sInstanceOfClass = 'Ext_TS_Service_Interface_Accommodation';
			$sMethodForAllServices = 'getAccommodations';
		} elseif($sType === 'insurance') {
			$sObjectKey = 'oJourneyInsurance';
			$sInstanceOfClass = 'Ext_TS_Service_Interface_Insurance';
			$sMethodForAllServices = 'getInsurances';
		} else {
			throw new InvalidArgumentException('Unknown type "'.$sType.'"');
		}

		if(!$this->$sObjectKey instanceof $sInstanceOfClass) {

			list($oGui, $aSelectedIds) = $this->getGuiAndSelectedIds();

			// Sonderfall Transferliste: Wenn Zuweisung vorhanden, soll diese verwendet werden
			if($oGui->name === 'ts_transfer') {
				$this->setAccommodationAllocation(false); // false, damit erste Unterkunftsbuchung weiter genommen wird, nicht erste mit Zuweisung
				if(
					$sType === 'accommodation' &&
					!empty($this->_aAccommodationAllocation['inquiry_accommodation_id'])
				) {
					$this->$sObjectKey = Ext_TS_Inquiry_Journey_Accommodation::getInstance($this->_aAccommodationAllocation['inquiry_accommodation_id']);
				}
				return; // Darf nicht in den ID-Fall unten reinlaufen
			}

			// Wenn Flag gesetzt: ID aus enkodierter GUI ziehen
			if(
				$oGui instanceof Ext_Gui2 &&
				$oGui->getOption('selected_object_from_encoded_data') === $sType
			) {

				$mSelectedIdsData = $this->getOption('document_selected_ids_decoded');

				// $mSelectedIdsData ist ein Array, da es alle dekodierten Werte enthält
				if(isset($mSelectedIdsData['id'])) {
					// Dies hier ist wichtig bei Massendokumenten, damit die richtigen Daten geladen werden
					$iId = $mSelectedIdsData['id'];
					$oObject = $this->_oInquiry->getServiceObject($sType, $iId);

				} else {
					// Erstbestes Item aus $aSelectedIds holen
					// Das wird u.a. gemacht, wenn nur ein Eintrag ausgewählt wird und Platzhalter sofort ersetzt werden
					$oParentData = $oGui->getDataObject();
					$oReflect = new ReflectionMethod($oParentData, '_getWDBasicObject');
					$oReflect->setAccessible(true); // Da ->_getWDBasicObject() protected ist, aber ->oWDBasic ebenso nicht geladen…

					// Hinweis: $_VARS['parent_gui_id'] ist enkodiert, aber _getWDBasicObject dekodiert das automatisch
					$oObject = $oReflect->invoke($oParentData, $aSelectedIds);
				}

				if($oObject instanceof $sInstanceOfClass) {
					$this->$sObjectKey = $oObject;
				}
			}

			// Wenn keine GUI oder nichts gefunden: Erstbestes Item holen
			if(!$this->$sObjectKey instanceof $sInstanceOfClass) {
				$aServiceObjects = $this->_oInquiry->$sMethodForAllServices();
				if(!empty($aServiceObjects)) {
					$this->$sObjectKey = reset($aServiceObjects);
				}
			}
		}

	}
	/**
	 * Methode funktioniert quasi wie setServiceObject(), aber Allocations sind was Eigenes (und auch kein Objekt)
	 *
	 * @see Ext_Thebing_Inquiry_Placeholder::setServiceObject()
	 *
	 * @param bool $bSetFirstAllocation
	 */
	protected function setAccommodationAllocation($bSetFirstAllocation = true) {

		// Da $this->_aAccommodationAllocation auch vom Loop gesetzt wird,
		// 	sollte diese Methode den Wert dann nicht überschreiben.
		if(empty($this->_aAccommodationAllocation)) {

			list($oGui, $aSelectedIds) = $this->getGuiAndSelectedIds();

			if(
				$oGui instanceof Ext_Gui2 &&
				$oGui->getOption('selected_object_from_encoded_data') === 'accommodation'
			) {
				// Werte werden in der Dokumentenklasse (Document und GUI2) gesetzt
				$mSelectedIdsData = $this->getOption('document_selected_ids_decoded');

				$sEncodedField = 'allocation_id';
				if($oGui->name === 'ts_transfer') {
					$sEncodedField = 'inquiry_transfer_id';
				}

				if(isset($mSelectedIdsData[$sEncodedField])) {
					// Dies hier ist wichtig bei Massendokumenten, damit die richtigen Daten geladen werden
					$iAllocationId = $mSelectedIdsData[$sEncodedField];
				} else {
					// Erstbeste ID nehmen
					// Das wird u.a. gemacht, wenn nur ein Eintrag ausgewählt wird und Platzhalter sofort ersetzt werden
					$aSelectedIds = (array)$oGui->decodeId($aSelectedIds, $sEncodedField);
					$iAllocationId = reset($aSelectedIds);
				}

				// Sonderfall Transferliste: Hier sollen die Daten der Zuweisung benutzt werden, wo der Transfer reinfällt
				if($oGui->name === 'ts_transfer') {
					$oJourneyTransfer = Ext_TS_Inquiry_Journey_Transfer::getInstance($iAllocationId);
					$iAllocationId = null;
					$oAllocation = $oJourneyTransfer->getAccommodationAllocationWithinTransferDate();
					if($oAllocation !== null) {
						$iAllocationId = $oAllocation->id;
					}
				}

				$aAllocations = Ext_Thebing_Allocation::getAllocationByInquiryId($this->_oInquiry->id, 0, true);
				foreach($aAllocations as $aAllocation) {
					if($aAllocation['id'] == $iAllocationId) {
						$this->_aAccommodationAllocation = $aAllocation;
						break;
					}
				}

			}
		}

		// Erstbeste Zuweisung holen
		if(
			$bSetFirstAllocation &&
			empty($this->_aAccommodationAllocation)
		) {
			$aAllocations = Ext_Thebing_Allocation::getAllocationByInquiryId($this->_oInquiry->id, 0, true);
			$this->_aAccommodationAllocation = reset($aAllocations);
		}

		if(
			// Nicht bei Gruppen setzen, da ansonsten jeder Durchlauf dieselbe Familie hätte (warum gibt es $this->_oFamily überhaupt?)
			!$this->_bGroupLoop &&
			empty($this->_oFamily) &&
			!empty($this->_aAccommodationAllocation)
		) {
			$this->_oFamily = Ext_Thebing_Accommodation::getInstance($this->_aAccommodationAllocation['family_id']);
		}
	}

	/**
	 * Gibt das Datum der aktuellen Kurswoche zurück
	 * @return int|boolean
	 */
	protected function _getInquiryCourseWeekDate() {

		$oJourneyCourse = $this->_getInquiryCourse();

		if(!$oJourneyCourse instanceof Ext_TS_Inquiry_Journey_Course) {
			return false;
		}

		if($oJourneyCourse->iCurrentWeek != null) {
			// Im Wochenloop ist das aktuelle Datum das Datum des Durchlaufs!
			// Die aktuelle Kurswoche wird durch den Loop gesetzt
			$iCourseWeekIterator = $oJourneyCourse->iCurrentWeek;
		} else {
			// Aktuelles Datum ist tatsächliches Datum
			// Aktuelle Kurswoche wird anhand dieses Datums ermittelt
			$iCourseWeekIterator = $oJourneyCourse->getTuitionIndexValue('current_week', new WDDate());
		}

		// Array mit Kurswochen mit ihrem Startdatum in der Woche
		$aCourseWeeksWithDates = $oJourneyCourse->getCourseWeeksWithDates();

		if(isset($aCourseWeeksWithDates[$iCourseWeekIterator])) {
			return $aCourseWeeksWithDates[$iCourseWeekIterator];
		}

		return false;
	}

	/**
	 * Gibt alle Platzhalter der Feedbackformulare zurück
	 *
	 * @return array
	 */
	private static function getFeedbackPlaceholders() {

		$aRetVal = array();
		$aQuestionaries = Ext_TS_Marketing_Feedback_Questionary::getRepository()->findAll();
		foreach($aQuestionaries as $oQuestionary) {
			$sHtml	= '{link_feedback_'.$oQuestionary->id.'}';
			$sHtml .= '<b>'.L10N::t('Link des Feedback Formulars "%s"', 'Thebing » Placeholder').'</b>';
			$sHtml  = str_replace('%s', $oQuestionary->name, $sHtml);
			$aRetVal[] = $sHtml;
		}

		return $aRetVal;
	}

	public function getRootEntity() {
		
		if(
			$this->_oVersion instanceof \Ext_TC_Basic &&
			method_exists($this->_oVersion, 'getPlaceholderObject')
		) {
			$oRootEntity = $this->_oVersion;
		} elseif(
			$this->_oInquiry instanceof \Ext_TC_Basic &&
			method_exists($this->_oInquiry, 'getPlaceholderObject')
		) {
			$oRootEntity = $this->_oInquiry;
		}
		
		return $oRootEntity;
	}

}
