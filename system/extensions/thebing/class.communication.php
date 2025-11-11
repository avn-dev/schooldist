<?php

use TsTuition\Service\HalloAiApiService;

/**
 * Klasse für den variablen Kommunikationsdialog
 *
 * Je nach Anwendungen werden E-Mails an Kunden, Agenturen, Benutzer,
 * Transferanbeiter, Unterkunftsanbieter oder Lehrer geschickt
 *
 * @author Mark Koopmann
 */
class Ext_Thebing_Communication extends Ext_TC_Communication {

	/*
	 * Eigenschaften für Ext_TC_Communication
	 */
	
	protected static $_aApplicationAllocations = array(
		'booking' => \Ts\Handler\Communication\Booking::class,
		'invoice' => \TsAccounting\Handler\Communication\Invoice::class,
		'tuition_allocation' => \TsTuition\Handler\Communication\Allocation::class,
		'job_opportunity_allocation' => \TsCompany\Handler\Communication\JobOpportunity\StudentAllocation::class,
	);
	
	/**
	 * @var string
	 */
	protected static $_sIdTag = 'COMMUNICATION_';

	/**
	 * @var string
	 */
	protected static $_sFileDir	= '/communication/attachments/';

	/**
	 * @var string
	 */
	protected static $_sL10NDescription	= 'Thebing » Communication';

	/**
	 * @var array
	 */
	protected $_aRecipientInputs = [
		'to' => 'An',
		'cc' => 'CC',
		'bcc' => 'BCC'
	];

	/**
	 * @var array
	 */
	protected $_aOriginalSelectedIds = array();

	/**
	 * @var null|Ext_Gui2
	 */
	protected $_oGui = null;

	/**
	 * @var null|Ext_Thebing_School
	 */
	protected $_oSchool	= null;

	/**
	 * @var null|Ext_TS_Inquiry
	 */
	protected $_oInquiry = null;

	/**
	 * @var null|Ext_Thebing_Agency
	 */
	protected $_oAgency = null;

	/**
	 * @var array
	 */
	protected $_aItems = array();

	/**
	 * @var array
	 */
	protected $aAccountingProviderAttachments = [];

	/**
	 * @var null|string
	 */
	protected $_sDialogTitle = null;

	/**
	 * @var null|string
	 */
	protected $_sDialogTitleMultiple = null;

	/**
	 * @var null|array
	 */
	protected $_aTemplateOptions = null;

	/**
	 * @var string|array
	 */
	protected $_aTemplateSelector = 'getCommunicationTemplateKey';

	/**
	 * @var bool
	 */
	protected $_bEditContent = true;

	/**
	 * @var string
	 */
	protected $_sSendTo = 'selection';

	/**
	 * Kundenmails
	 *
	 * @var null|bool
	 */
	protected $_bStudentEmails = null;

	/**
	 * TransferProvider
	 *
	 * @var null|bool
	 */
	protected $_bProviderEmails	= null;

	/**
	 * Agenturen
	 *
	 * @var null|bool
	 */
	protected $_bAgencyEmails = null;

	/**
	 * @var null|bool
	 */
	protected $_bOtherEmails = null;

	/**
	 * Lehrer
	 *
	 * @var null|bool
	 */
	protected $_bTeacherEmails = null;

	/**
	 * Gibt an ob Entweder Schüler ODER Agentur Mails angezeigt werden ODER beides
	 *
	 * @var null|bool
	 */
	protected $_bStudentAndAgencyEmails	= null;

	/**
	 * @var null|bool
	 */
	protected $_bInquiryInvoiceAttachments = null;

	/**
	 * @var null|bool
	 */
	protected $_bInquiryOtherAttachments = null;

	/**
	 * @var null|bool
	 */
	protected $_bSchoolAttachments = null;

	/**
	 * @var int
	 */
	protected $_iSchoolAttachmentSection = 4;

	/**
	 * @var null|bool
	 */
	protected $_bContractAttachments = null;

	/**
	 * @var null|bool
	 */
	protected $_bAgencyOpenPaymentsAttachment = null;

	/**
	 * @var null|bool
	 */
	protected $_bCreditnoteAttachments = null;

	/**
	 * @var null|bool
	 */
	protected $_bEnquiryAttachments = null;

	/**
	 * @var null|bool
	 */
	protected $bAccountingProviderAttachments = null;

	/**
	 * @var null|bool
	 */
	protected $_bAccommodationAttachments = null;

	/**
	 * Flag für Excel-Anhänge (Studenteninformationen)
	 *
	 * @var bool
	 */
	protected $_bStudentsFile = false;

	/**
	 * Excel-Anhang
	 *
	 * @var null
	 */
	protected $_oExcel = null;

	/**
	 * Excel Object
	 *
	 * @var null
	 */
	protected $_oExport = null;

	/**
	 * @var null|bool
	 */
	protected $_bReplacePlaceholderInDialog	= true;

	/**
	 * @var null|bool
	 */
	protected $_bMultiSelection = null;

	/**
	 * @var bool
	 */
	protected $bMultipleAccountingProviderAttachments = false;

	/**
	 * @var string
	 */
	protected $_sOriginalApplication = '';

	/**
	 * Das war vorher $_SESSION und machte Probleme, sobald ein zweiter Dialog geöffnet wurde
	 *
	 * @var array
	 */
	protected $aDialogPassData = [];

	/**
	 * @param bool $sApplication
	 * @return array
	 */
	public static function getFlags($sApplication=false) { 

		$aFlags = array();

		// Inquiry
		$aFlags['payment_reminder']	= L10N::t("Zahlungserinnerung - Kunde", self::$_sL10NDescription);

		// Insurances
		$aFlags['customer_info'] = L10N::t("Versicherung bestätigt - Kunde", self::$_sL10NDescription);
		$aFlags['provider_info'] = L10N::t("Versicherung bestätigt - Anbieter", self::$_sL10NDescription);

		// Contract_Version
		$aFlags['contract_sent'] = L10N::t("Vertrag versendet", self::$_sL10NDescription);

		// Unterkunftskommunikation Kunde/Agentur
		$aFlags['accommodation_arrival_requested']	= L10N::t("Kunde/Agentur anfragen - Anreiseinformationen", self::$_sL10NDescription);
		$aFlags['accommodation_confirmed_customer']	= L10N::t("Kunde/Agentur bestätigen - Unterkunft", self::$_sL10NDescription);

		// Unterkunftskommunikation Unterkunft
		$aFlags['accommodation_confirmed_provider']	= L10N::t("Unterkunft bestätigen - Kundendaten", self::$_sL10NDescription);
		$aFlags['accommodation_confirmed_transfer']	= L10N::t("Unterkunft bestätigen - Transfer", self::$_sL10NDescription);

		// Unterkunftskommunikation
		$aFlags['accommodation_canceled_provider']	= L10N::t("Unterkunft abgesagt - Unterkunft", self::$_sL10NDescription);
		$aFlags['accommodation_canceled_customer']	= L10N::t("Kunde/Agentur abgesagt - Unterkunft", self::$_sL10NDescription);

		// Transferkommunikation
		$aFlags['transfer_provider_request'] = L10N::t("Transfer anfragen - Provider", self::$_sL10NDescription);
		$aFlags['transfer_provider_confirm'] = L10N::t("Transfer bestätigen - Provider", self::$_sL10NDescription);
		$aFlags['transfer_customer_accommodation_information'] = L10N::t("Transfer bestätigen - Unterkunft", self::$_sL10NDescription);
		$aFlags['transfer_customer_agency_information'] = L10N::t("Transfer bestätigen - Kunde/Agentur", self::$_sL10NDescription);

		// Feedbackformular
		$aFlags['inquiry_feedback_invited'] = L10N::t("Feedbackformular gesendet", self::$_sL10NDescription);

		// Placement Test
		$aFlags['inquiry_placementtest_invited'] = L10N::t("Einstufungstest gesendet", self::$_sL10NDescription);

		if (\TcExternalApps\Service\AppService::hasApp(\TsTuition\Handler\HalloAiApp::APP_NAME)) {
			// Placement Test über Hallo.ai
			$aFlags['inquiry_placementtest_halloai'] = L10N::t("Einstufungstest über Hallo.ai", self::$_sL10NDescription);
		}

		// Anwesenheitserinnerung
		$aFlags['attendance_warning'] = L10N::t("Anwesenheitswarnung", self::$_sL10NDescription);

		if($sApplication !== false) {
			$aFlags = self::checkFlagApplicationCombination($aFlags, $sApplication);
		}

		return $aFlags;
	}

	public static function getAllSelectFlags($bSelectOptions = true, $aTypes = array()) {
		
		$aFlags = self::getFlags();
		
		return $aFlags;
	}
	
	/*
	 * Prüft ob Flags in einer Application zur Verfügung stehen dürfen
	 */
	public static function checkFlagApplicationCombination($aFlags, $sApplication) {

		$aApplication = (array)$sApplication;

		foreach($aApplication as $sApplication) {

			foreach ($aFlags as $sKey => $sTemp) {

				$aAllowedApplication = array();

				switch ($sKey) {

					case 'accommodation_confirmed_customer':
						$aAllowedApplication[] = 'accommodation_communication_customer_agency'; //
						$aAllowedApplication[] = 'accommodation_communication_agency'; //
						// History
						$aAllowedApplication[] = 'accommodation_communication_history_customer_confirmed'; //
						$aAllowedApplication[] = 'accommodation_communication_history_agency_confirmed'; //
						break;

					case 'payment_reminder':
						$aAllowedApplication[] = 'simple_view';
						$aAllowedApplication[] = 'arrival_list';
						$aAllowedApplication[] = 'departure_list';
						$aAllowedApplication[] = 'client_payment';
						$aAllowedApplication[] = 'inbox';
						$aAllowedApplication[] = 'visum_list';
						$aAllowedApplication[] = 'cronjob';
						break;

					case 'accommodation_confirmed_provider':
						$aAllowedApplication[] = 'accommodation_communication_history_accommodation_confirmed'; //
						// Kein break
					case 'accommodation_confirmed_transfer':
						$aAllowedApplication[] = 'accommodation_communication_provider'; //
						break;

					case 'accommodation_arrival_requested':
						$aAllowedApplication[] = 'accommodation_communication_customer_agency'; //
						$aAllowedApplication[] = 'accommodation_communication_agency'; //
						break;
					
					case 'transfer_provider_request':
						$aAllowedApplication[] = 'transfer_provider_request'; //
						break;
					
					case 'transfer_provider_confirm':
						$aAllowedApplication[] = 'transfer_provider_confirm'; //
						break;
					
					case 'transfer_customer_accommodation_information':
						$aAllowedApplication[] = 'transfer_customer_accommodation_information'; //
						break;
					
					case 'transfer_customer_agency_information':
						$aAllowedApplication[] = 'transfer_customer_agency_information'; //
						$aAllowedApplication[] = 'transfer_agency_information'; //
						break;
					case 'contract_sent':
						$aAllowedApplication[] = 'contract_teacher';
						$aAllowedApplication[] = 'contract_accommodation';
						break;

					case 'customer_info':
						$aAllowedApplication[] = 'insurance_customer';
						break;

					case 'provider_info':
						$aAllowedApplication[] = 'insurance_provider';
						break;

					case 'accommodation_canceled_provider':
						$aAllowedApplication[] = 'accommodation_communication_history_accommodation_canceled'; //
						break;

					case 'accommodation_canceled_customer':
						$aAllowedApplication[] = 'accommodation_communication_history_customer_canceled'; //
						$aAllowedApplication[] = 'accommodation_communication_history_agency_canceled'; //
						break;

					case 'inquiry_feedback_invited':
						$aAllowedApplication[] = 'simple_view';
						$aAllowedApplication[] = 'arrival_list';
						$aAllowedApplication[] = 'departure_list';
						$aAllowedApplication[] = 'inbox';
						$aAllowedApplication[] = 'feedback_list';
						$aAllowedApplication[] = 'visum_list';
						$aAllowedApplication[] = 'cronjob';
						$aAllowedApplication[] = 'tuition_attendance';
						break;
					case 'inquiry_placementtest_invited':
					case 'inquiry_placementtest_halloai':
						$aAllowedApplication[] = 'simple_view';
						$aAllowedApplication[] = 'arrival_list';
						$aAllowedApplication[] = 'departure_list';
						$aAllowedApplication[] = 'inbox';
						$aAllowedApplication[] = 'placement_test';
						$aAllowedApplication[] = 'feedback_list';
						$aAllowedApplication[] = 'visum_list';
						$aAllowedApplication[] = 'enquiry';
						$aAllowedApplication[] = 'cronjob';
						break;
					case 'attendance_warning':
						$aAllowedApplication[] = 'tuition_attendance';
						break;
					default:
					break;
				}

				if(!in_array($sApplication, $aAllowedApplication)){
					unset($aFlags[$sKey]);
				}

			}
		}

		return $aFlags;
	}

	/**
	 * @param string|bool $sApplication
	 * @param bool $bForSelect
	 * @return array
	 */
	public static function getApplications($sApplication=false, $bForSelect=false) {
	
		$aApplications = array();
		$aApplications['simple_view'] = L10N::t('Schüler » Einfache Schülerliste', self::$_sL10NDescription);
		$aApplications['arrival_list'] = L10N::t('Schüler » Willkommensliste', self::$_sL10NDescription);
		$aApplications['departure_list'] = L10N::t('Schüler » Abreiseliste', self::$_sL10NDescription);
		$aApplications['feedback_list'] = L10N::t('Schüler » Feedbackliste', self::$_sL10NDescription);
		$aApplications['visum_list'] = L10N::t('Schüler » Visa', self::$_sL10NDescription);

		$aApplications['inbox'] = L10N::t('Buchungen', self::$_sL10NDescription);

		$aApplications['tuition_attendance'] = L10N::t('Unterricht » Anwesenheit', self::$_sL10NDescription);
		$aApplications['placement_test'] = L10N::t('Unterricht » Ergebnisse des Einstufungstests', self::$_sL10NDescription);

		// START Unterkunftskommunikation
		$aApplications['accommodation_communication_customer_agency'] = L10N::t('Unterkunft » Kunden informieren', self::$_sL10NDescription);
		// Transfer Fake Template da es möglich sein soll bei Transferkomm. 2 Templates auswählen zu können -.-
		$aApplications['accommodation_communication_agency'] = L10N::t('Unterkunft » Agentur informieren', self::$_sL10NDescription);
		$aApplications['accommodation_communication_provider'] = L10N::t('Unterkunft » Unterkunft informieren', self::$_sL10NDescription);
		$aApplications['accommodation_communication_provider_requests'] = L10N::t('Unterkunft » Verfügbarkeit anfragen', self::$_sL10NDescription);
			
		// History Gui
		// Cancel applications
		$aApplications['accommodation_communication_history_accommodation_canceled'] = L10N::t('Unterkunft » History Unterkunft absagen', self::$_sL10NDescription);
		$aApplications['accommodation_communication_history_customer_canceled'] = L10N::t('Unterkunft » History Kunde absagen', self::$_sL10NDescription);
		// Transfer Fake Template da es möglich sein soll bei Transferkomm. 2 Templates auswählen zu können -.- (History)
		$aApplications['accommodation_communication_history_agency_canceled'] = L10N::t('Unterkunft » History Agentur absagen', self::$_sL10NDescription);
		
		// Werden nur noch Intern benutzt, da Application umgeschaltet wird
		if(!$bForSelect) {
			// Bestätigen applications sind nötig um communikations ID richtig raus zu finden
			// Brauchen wir für den Titel des Dialogs
			$aApplications['accommodation_communication_history_accommodation_confirmed'] = L10N::t('Unterkunft » History Unterkunft bestätigt', self::$_sL10NDescription);
			$aApplications['accommodation_communication_history_customer_confirmed'] = L10N::t('Unterkunft » History Kunde bestätigt', self::$_sL10NDescription);
			$aApplications['accommodation_communication_history_agency_confirmed'] = L10N::t('Unterkunft » History Agentur bestätigt', self::$_sL10NDescription);
		}

		$aApplications['accommodation_resources_provider'] = L10N::t('Unterkunft » Resourcen » Anbieter', self::$_sL10NDescription);

		//$aApplications['marketing_companies'] = L10N::t('Marketing » Firmen', self::$_sL10NDescription);

		if(Ext_Thebing_Access::hasRight('thebing_marketing_agency_crm')) {
			$aApplications['marketing_agencies'] = L10N::t('Marketing » Agencies', self::$_sL10NDescription);
			$aApplications['marketing_agencies_contact'] = L10N::t('Marketing » Agencies-contact', self::$_sL10NDescription);
		}

		$aApplications['agencies_payments'] = L10N::t('Buchhaltung » Agenturzahlungen', self::$_sL10NDescription);

		$aApplications['tuition_teacher'] = L10N::t('Lehrerverwaltung » Lehrer', self::$_sL10NDescription);

		$aApplications['accounting_teacher'] = L10N::t('Buchhaltung » Lehrer', self::$_sL10NDescription);
		$aApplications['accounting_accommodation'] = L10N::t('Buchhaltung » Unterkunftsanbieter', self::$_sL10NDescription);
		$aApplications['accounting_transfer'] = L10N::t('Buchhaltung » Transferanbieter', self::$_sL10NDescription);

		$aApplications['insurance_provider'] = L10N::t('Versicherungen » Anbieter informieren', self::$_sL10NDescription);
		$aApplications['insurance_customer'] = L10N::t('Versicherungen » Kunde informieren', self::$_sL10NDescription);

		$aApplications['client_payment'] = L10N::t('Buchhaltung » Kundenzahlungen', self::$_sL10NDescription);

		// Transfer
		$aApplications['transfer_provider_request'] = L10N::t('Transfer » Anfragen Provider', self::$_sL10NDescription);
		$aApplications['transfer_provider_confirm']	= L10N::t('Transfer » Bestätigen Provider', self::$_sL10NDescription);
		$aApplications['transfer_customer_accommodation_information'] = L10N::t('Transfer » Bestätigen Unterkunft', self::$_sL10NDescription);
		$aApplications['transfer_customer_agency_information'] = L10N::t('Transfer » Bestätigen Kunde', self::$_sL10NDescription);

		// Transfer Fake Template da es möglich sein soll bei Transferkomm. 2 Templates auswählen zu können -.-
		$aApplications['transfer_agency_information'] = L10N::t('Transfer » Bestätigen Agentur', self::$_sL10NDescription);

		$aApplications['enquiry'] = L10N::t('Anfragen', self::$_sL10NDescription);

		$aApplications['contract_teacher'] = L10N::t('Lehrerverträge', self::$_sL10NDescription);
		$aApplications['contract_accommodation'] = L10N::t('Unterkunftsverträge', self::$_sL10NDescription);

		// Cronjob Mail
		$aApplications['cronjob'] = L10N::t('Admin » Automatische E-Mails', self::$_sL10NDescription);
		$aApplications['mobile_app_forgotten_password'] = L10N::t('Mobile App » Passwort vergessen', self::$_sL10NDescription);

		$aApplications['activity'] = L10N::t('Aktivitäten', self::$_sL10NDescription);

		if($sApplication) {
			return $aApplications[$sApplication] ?? null;
		}

		asort($aApplications);

		return $aApplications;
	}

	/**
	 * @param array $aRecipients
	 * @param string $sName
	 * @param string $sObject Objekt (E-Mail) zu dem kommuniziert wird
	 * @param int $iId ID (E-Mail) zu oben
	 * @param mixed $mSelectedId IDs des übergebenen/ausgewöhlten Objekts (application)
	 * @param string $sEmail
	 * @param string $sLanguage
	 * @param array|null $aAdditional
	 */
	public function addToSessionIds(&$aRecipients, $sName, $sObject, $iId, $mSelectedId, $sEmail, $sLanguage, array $aAdditional=null) {

		$bCheck = Util::checkEmailMx($sEmail);
		
		if($bCheck) {

			// Gleiches Objekt suchen
			$iSessionIndex = 0;
			foreach((array)$this->aDialogPassData['recipients'] as $iKey => $aItem) {
				if(
					$aItem['object'] == $sObject &&
					$aItem['object_id'] == $iId &&
					$aItem['email'] == $sEmail &&
					$aItem['name'] == $sName
				) {
					$iSessionIndex = $iKey;
					break;
				}
			}

			if($iSessionIndex == 0) {
				$iSessionIndex = count($this->aDialogPassData['recipients']);
				$iSessionIndex++;
			}

			$aRecipient = [
				'object' => $sObject,
				'object_id' => (int)$iId,
				'email' => $sEmail,
				'language' => $sLanguage,
				'name' => $sName,
				'additional' => $aAdditional,
				'selected_id' => []
			];
			
			if(is_array($mSelectedId)) {
				$aRecipient['selected_id'] = $mSelectedId;
			} else {

				// Wenn der Eintrag schon da ist, ID ergänzen zu den vorhandenen
				if(!empty($this->aDialogPassData['recipients'][$iSessionIndex])) {
					$aRecipient['selected_id'] = (array)$this->aDialogPassData['recipients'][$iSessionIndex]['selected_id'];
				}
				
				$aRecipient['selected_id'][] = (int)$mSelectedId;
			}

			$this->aDialogPassData['recipients'][$iSessionIndex] = $aRecipient;
			$aRecipients[$iSessionIndex] = $sName;

		}

	}

	/**
	 * @param array $aRecipients
	 * @param string $sObject
	 * @param int $iId
	 * @param mixed $mSelectedId
	 * @param string $sLanguage
	 * @return array
	 */
	public function convertToSessionIds($aRecipients, $sObject, $iId, $mSelectedId, $sLanguage) {

		$aTemp = $aRecipients;
		$aRecipients = array();

		foreach((array)$aTemp as $mKey => $mInfos) {

			if(is_array($mInfos)) {
				$sName	= $mInfos['name'];
				$sEmail = $mInfos['email'];
			} else {
				$sName	= $mInfos;
				$sEmail	= $mKey;
			}
			
			$this->addToSessionIds($aRecipients, $sName, $sObject, $iId, $mSelectedId, $sEmail, $sLanguage);

		}

		return (array)$aRecipients;
	}

	/**
	 * Liefert das Platzhalterobject
	 *
	 * @param string $sObject
	 * @param mixed $oRecipient
	 * @param string $sApplication
	 * @param array $mSelectedIds
	 * @param array $aAdditional
	 * @return Ext_Thebing_Agency_Placeholder|Ext_Thebing_Inquiry_Placeholder|null
	 * @throws Exception
	 */
	protected function _getPlaceholderObject($sObject, Ext_Thebing_Communication_RecipientDTO $oRecipient, $sApplication, $mSelectedIds = array(), $aAdditional = array()) {

		if(!is_array($mSelectedIds)) {
			$aSelectedIds = array($mSelectedIds);
		} else{
			$aSelectedIds = $mSelectedIds;
		}

		$iObjectId = (int) $oRecipient->iObjectId;
		// @todo Parameter $sObject entfernen und immer in $oRecipient übergeben
		if($oRecipient->sObject !== null) {
			$sObject = $oRecipient->sObject;
		}

		$oPlaceholder = null;

		if(count($aSelectedIds) <= 0) {
			return $oPlaceholder;
		}

		$iSelectedId = reset($aSelectedIds);

		switch($sApplication) {
			case 'accommodation_communication_customer_agency':
			case 'accommodation_communication_provider':
			case 'accommodation_communication_history_accommodation_confirmed':
			case 'accommodation_communication_history_customer_confirmed':
			case 'accommodation_communication_history_agency_canceled':
			case 'accommodation_communication_history_customer_canceled':
			case 'accommodation_communication_history_accommodation_canceled':
				if(count($aSelectedIds) > 0) {
					// Alle markierten Einträge als Inquiryid sammeln
					$aInquiryIds = array();
					foreach((array) $aSelectedIds as $iId) {
						$oInquiryAccommodation = Ext_Thebing_Accommodation_Allocation::getInstance($iId);
						$oInquiry = $oInquiryAccommodation->getInquiry();
						if($oInquiry->id > 0) {
							// Inquiries ausschließen, die nicht zur selben Agentur gehören
							if(
								$sApplication == 'accommodation_communication_customer_agency' &&
								$sObject == 'Ext_Thebing_Agency' &&
								$oInquiry->agency_id != $iObjectId
							) {
								continue;
							}
							$aInquiryIds[] = $oInquiry->id;
						}
					}
					
					// 2 Buchungen von einer Inquiry ausschließen
					$aInquiryIds = array_unique($aInquiryIds);
					$iSelectedId = reset($aSelectedIds);

					$oInquiryAccommodationAllocation = Ext_Thebing_Accommodation_Allocation::getInstance($iSelectedId);
					$oInquiry = $oInquiryAccommodationAllocation->getInquiry();
					/* @var $oInquiry Ext_TS_Inquiry */
					
					if($sObject == 'Ext_Thebing_Agency') {
						// Wenn an Argenturen gesendet wird, dann die Platzhalter für Agenturen verwenden
						$oPlaceholder = new Ext_Thebing_Agency_Placeholder($iObjectId, 'agency');
					} else {

						// Wenn an Kunden geschickt wird
						$oPlaceholder = new Ext_Thebing_Inquiry_Placeholder($oInquiry->id);
						
						$oCustomer = $oInquiry->getCustomer();
						$sLanguage = $oCustomer->getLanguage();
						
						$oPlaceholder->sTemplateLanguage = $sLanguage;
						// Bei einem eintrag muss die Family gesetzt werden
//						if(count($aSelectedIds) == 1){
//							// Passende Familie zum Eintrag setzen (Einzelkommunikation)
//							// Siehe document_selected_ids_decoded hier und entsprechende Methoden in der Platzhalter-Klasse
//							$oPlaceholder->_oAllocation = $oInquiryAccommodationAllocation;
//						}

					}
					// Alle markierten Einträge der Liste der Platzhalterklasse übergeben für den "entries" Loop
					$oPlaceholder->setEntriesData($aInquiryIds, 'Ext_TS_Inquiry');
				}
				break;
			case 'insurance_provider':
			case 'insurance_customer':
				$oInquiryInsurance = Ext_TS_Inquiry_Journey_Insurance::getInstance($iSelectedId);
				$oInquiry = $oInquiryInsurance->getInquiry();
				$oPlaceholder = new Ext_Thebing_Inquiry_Placeholder($oInquiry->id);
				// Einzelkommunikation: Passender selektierter Eintrag
				$oPlaceholder->oJourneyInsurance = $oInquiryInsurance;
				break;
			case 'transfer_provider_request':
			case 'transfer_provider_confirm':
			case 'transfer_customer_accommodation_information':
			case 'transfer_customer_agency_information':
				if(
					!empty($sObject) &&
					$iObjectId > 0
				){
					$oTransfer = Ext_TS_Inquiry_Journey_Transfer::getInstance($iSelectedId);

					if($sObject == 'Ext_Thebing_Agency_Contact'){
						// Agenturobjecte senden wurde nicht 
						//$oPlaceholder = new Ext_Thebing_Agency_Placeholder($iObjectId);
						$oPlaceholder = new Ext_Thebing_Inquiry_Placeholder($oTransfer->inquiry_id, 0);
					} else{
						$oPlaceholder = new Ext_Thebing_Inquiry_Placeholder($oTransfer->inquiry_id, 0);
					}
					$oPlaceholder->oJourneyTransfer = $oTransfer;
				}
				break;
			case 'customer':
				//Bei Schüleranfragen ist die selected_id schon die richtige InquiryID
				$oPlaceholder = new Ext_Thebing_Inquiry_Placeholder($iSelectedId, 0);
				break;
//			case 'enquiry':
//				$oEnquiry = self::_getObjectFromApplication($sApplication, $iSelectedId);
//				$oPlaceholder = new Ext_TS_Enquiry_Placeholder($oEnquiry);
//				break;
			case 'tuition_teacher':
				$oPlaceholder = new Ext_Thebing_Teacher_Placeholder($iSelectedId, 0);
				break;
			case 'marketing_agencies':
				if($sObject == 'Ext_Thebing_Agency_Contact') {
					$oPlaceholder = new Ext_Thebing_Agency_Placeholder($iObjectId, 'contact');
				} else {
					$oPlaceholder = new Ext_Thebing_Agency_Placeholder($iObjectId, 'agency');
				}
				break;
			/*case 'marketing_companies':
				if($sObject == 'Ext_Thebing_Agency_Contact') {
					$oPlaceholder = new Ext_Thebing_Agency_Placeholder($iObjectId, 'contact');
				} else {
					$oPlaceholder = new Ext_Thebing_Agency_Placeholder($iObjectId, 'agency');
				}
				break;*/
			case 'agencies_payments':
				$oAgencyPayment = Ext_Thebing_Agency_Payment::getInstance($iObjectId);
				$iObjectId = $oAgencyPayment->agency_id;
				$oPlaceholder = new Ext_Thebing_Agency_Placeholder($iObjectId, 'agency');
				break;
			case 'marketing_agencies_contact':
				$oPlaceholder = new Ext_Thebing_Agency_Placeholder($iObjectId, 'contact');
				break;
			case 'simple_view':
			case 'arrival_list':
			case 'visum_list':
			case 'departure_list':
			case 'tuition_attendance':
			case 'placement_test':
			case 'feedback_list':
			case 'client_payment':
			case 'enquiry':

				if($sObject != 'Ext_Thebing_Agency_Contact') {
					$oPlaceholder = new Ext_Thebing_Inquiry_Placeholder($iSelectedId);
				} else {
					$oAgencyContact = Ext_Thebing_Agency_Contact::getInstance((int)$oRecipient->iObjectId);
					$oAgency = $oAgencyContact->getParentObject();
					$oPlaceholder = new Ext_Thebing_Agency_Placeholder($oAgency->id, 'agency');
					$oPlaceholder->_oAgencyStaff = $oAgencyContact;

					$aRecipientSelectedIds = $oRecipient->aSelectedIds;
					if(!empty($aRecipientSelectedIds)) {
						$oPlaceholder->aInquiryIds = (array)$aRecipientSelectedIds;
						$oPlaceholder->iSingleInquiryId = $oRecipient->iSelectedId;
					}
				}

				// Einzelkommunikation: Selektierte Zuweisung (Kurs) setzen
				if($sApplication === 'tuition_attendance') {
					// Zwingend prüfen, sonst wird ein leeres Kursobjekt gesetzt
					if(!empty($aAdditional['allocation_id'])) {
						$oAllocation = Ext_Thebing_School_Tuition_Allocation::getInstance($aAdditional['allocation_id']);
						$oPlaceholder->setTuitionAllocation($oAllocation);
					}
				}
				break;
			case 'inbox':
				// TODO #15271
				$oPlaceholder = new Ext_Thebing_Inquiry_Placeholder($iSelectedId);
				break;
			case 'contract_teacher':
			case 'contract_accommodation':
				$oPlaceholder = new Ext_Thebing_Contract_Placeholder($iObjectId);
				break;
			case 'accommodation_resources_provider':
				// Fake-Vertrag erzeugen, damit die Platzhalterklasse funktioniert (hier kam nie jemand auf die Idee das zu trennen)
				$oContract = new Ext_Thebing_Contract();
				$oContract->item = 'accommodation';
				$oContract->item_id = $iObjectId;
				$oVersion = new Ext_Thebing_Contract_Version();
				$oVersion->setJoinedObject('kcont', $oContract);
				$oPlaceholder = new Ext_Thebing_Contract_Placeholder($oVersion);
				break;
			case 'accounting_teacher':
			case 'accounting_accommodation':
			case 'accounting_transfer':

				if(empty($aAdditional['original_id'])) {
					throw new RuntimeException($sApplication.': Missing $aAdditional params');
				}

				if($sApplication === 'accounting_teacher') {
					$oTeacher = Ext_Thebing_Teacher::getInstance($iObjectId);
					$oPaymentGrouping = Ext_TS_Accounting_Provider_Grouping_Teacher::getInstance($aAdditional['original_id']);
					$oPlaceholder = new Ext_TS_Accounting_Provider_Grouping_Teacher_Placeholder($oTeacher, $oPaymentGrouping);
				} elseif($sApplication === 'accounting_accommodation') {
					$oAccommodation = Ext_Thebing_Accommodation::getInstance($iObjectId);
					$oPaymentGrouping = Ext_TS_Accounting_Provider_Grouping_Accommodation::getInstance($aAdditional['original_id']);
					$oPlaceholder = new Ext_TS_Accounting_Provider_Grouping_Accommodation_Placeholder($oAccommodation, $oPaymentGrouping);
				} elseif($sApplication === 'accounting_transfer') {
					$oPaymentGrouping = Ext_TS_Accounting_Provider_Grouping_Transfer::getInstance($aAdditional['original_id']);
					$oProvider = $oPaymentGrouping->getProvider();
					if($oProvider->id != $iObjectId) {
						throw new RuntimeException('Wrong provider returned for accounting_transfer?');
					}
					$oPlaceholder = new Ext_TS_Accounting_Provider_Grouping_Transfer_Placeholder($oProvider, $oPaymentGrouping);
				}
				break;
			case 'activity':
				/** @var \TsActivities\Entity\Activity\BlockTraveller $oBlockTraveller */
				$oBlockTraveller = self::getObjectFromApplication($sApplication, $iSelectedId);
				$oPlaceholder = $oBlockTraveller->getPlaceholderObject();
				break;
			case '':

			default:
				break;
		}

		if($oPlaceholder !== null) {

			$oPlaceholder->bCommunication = true;
			$oPlaceholder->oGui = $this->_oGui;

			if($oPlaceholder instanceof \Ext_Thebing_Placeholder) {
				// Dekodierte IDs der GUI übergeben, damit Platzhalterklasse die richtigen Werte pro Zeile setzt (Massenkommunikation)
				if($oRecipient->hasValue('aDecodedData')) {
					$oPlaceholder->setOption('document_selected_ids_decoded', $oRecipient->aDecodedData);
				} else {
					$oPlaceholder->unsetOption('document_selected_ids_decoded');
				}

				if($oRecipient->hasValue('iSelectedId')) {
					$oPlaceholder->setOption('document_communication_selected_id_single', (int)$oRecipient->iSelectedId);
				} else {
					$oPlaceholder->unsetOption('document_communication_selected_id_single');
				}
			}
			
		}

		return $oPlaceholder;
	}

	/**
	 * ACHTUNG: Redundanz mit getPlaceholderObject
	 *
	 * @see self::getPlaceholderObject
	 * @param $sApplication
	 * @return string
	 */
	public static function getPlaceholderClass($sApplication) {

		$sClass = '';
		
		switch($sApplication) {
			case 'simple_view':
			case 'arrival_list':
			case 'visum_list':
			case 'departure_list':
			case 'feedback_list';
			case 'tuition_attendance':
			case 'placement_test':
			case 'client_payment':
			case 'inbox':
			case 'customer':
			case 'accommodation_communication_customer_agency':
			case 'accommodation_communication_provider':
			case 'accommodation_communication_history_accommodation_confirmed':
			case 'accommodation_communication_history_accommodation_canceled':
			case 'accommodation_communication_history_customer_confirmed':
			case 'accommodation_communication_history_customer_canceled':
			case 'accommodation_communication_history_agency_canceled':
			case 'accommodation_communication_history_agency_confirmed':
			case 'accommodation_communication_agency':
			case 'insurance_provider':
			case 'insurance_customer':
			case 'transfer_provider_request':
			case 'transfer_provider_confirm':
			case 'transfer_agency_information':
			case 'transfer_customer_accommodation_information':
			case 'transfer_customer_agency_information':
			case 'mobile_app_forgotten_password':
			case 'enquiry':
				$sClass = 'Ext_Thebing_Inquiry_Placeholder';
				break;
			case 'marketing_agencies':			
				$sClass = 'Ext_Thebing_Agency_Placeholder';
				break;		
			case 'agencies_payments':
				$sClass = 'Ext_Thebing_Agency_Placeholder';
				break;
			case 'marketing_agencies_contact':			
				$sClass = 'Ext_Thebing_Agency_Placeholder';
				break;		
			case 'contract_teacher':
			case 'contract_accommodation':
			case 'accommodation_resources_provider':
				$sClass = 'Ext_Thebing_Contract_Placeholder';
				break;
			case 'tuition_teacher':
				$sClass = 'Ext_Thebing_Teacher_Placeholder';
				break;
//			case 'enquiry':
//				$sClass = 'Ext_TS_Enquiry_Placeholder';
//				break;
			case 'accounting_teacher':
				$sClass = 'Ext_TS_Accounting_Provider_Grouping_Teacher_Placeholder';
				break;
			case 'accounting_accommodation':
				$sClass = 'Ext_TS_Accounting_Provider_Grouping_Accommodation_Placeholder';
				break;
			case 'accounting_transfer':
				$sClass = 'Ext_TS_Accounting_Provider_Grouping_Transfer_Placeholder';
				break;
			case 'activity':
				/** @var \TsActivities\Entity\Activity\BlockTraveller $oBlockTraveller */
				$oBlockTraveller = self::getObjectFromApplication($sApplication, 0);
				$sClass = get_class($oBlockTraveller->getPlaceholderObject());
				break;
			default:
				break;
		}

		return $sClass;
	}

	/**
	 * @param string $sApplication
	 * @param array $aSelectedIds
	 * @throws Exception
	 */
	protected function _getApplicationSettings($sApplication, $aSelectedIds) {
		global $_VARS;

		$aSelectedIds = (array)$aSelectedIds;
		$iSelectedId = reset($aSelectedIds);

		// Default Templateoptionen
		$this->_aTemplateOptions = [
			'default' => [
				'label' => L10N::t('Vorlage', self::$_sL10NDescription),
				'application' => $sApplication
			]
		];

		/**
		 * Switch über die verschiedenen Anwendungen der Kommunikation
		 *
		 * Hier wird per Flags festgelegt, welche Optionen zur Verfügung stehen
		 * und es wird das immer benötigte Schulobjekt geholt.
		 */
		switch($sApplication) {
			case 'insurance_provider':

				$this->_bProviderEmails = true;
				$this->_bInquiryOtherAttachments = true;

				$oInquiryInsurance = Ext_TS_Inquiry_Journey_Insurance::getInstance($iSelectedId);
				$this->_oInquiry = $oInquiryInsurance->getInquiry();
				$this->_oSchool = $this->_oInquiry->getSchool();

				break;
			case 'insurance_customer':

				$this->_bStudentEmails = true;
				$this->_bAgencyEmails = true;
				$this->_bOtherEmails = true;
				$this->_bInquiryOtherAttachments = true;

				$oInquiryInsurance = Ext_TS_Inquiry_Journey_Insurance::getInstance($iSelectedId);
				$this->_oInquiry = $oInquiryInsurance->getInquiry();
				$this->_oSchool = $this->_oInquiry->getSchool();

				$this->_sSendTo = 'objects';
				
				break;
			case 'transfer_provider_request':

				// Anfragen an Transfer Provider (Hier ist die Inquiry_transfer_id erforderlich!)
				$this->_bProviderEmails					= true;
				
				$this->_bInquiryOtherAttachments		= true;
		
				$this->_bStudentsFile					= true;  //aktivieren von Schülerinformatioen als Excel-Anhang  (false als default?)

				$this->_sSendTo							= 'objects';

				// Inquiry des ERSTEN Kunden
				$oTransfer								= Ext_TS_Inquiry_Journey_Transfer::getInstance($iSelectedId);
				$this->_oInquiry						= $oTransfer->getInquiry();
				$this->_oSchool							= $this->_oInquiry->getSchool();

				break;
			case 'transfer_provider_confirm':

				// Mailgestätigung an Transferanbieter
				$this->_bProviderEmails					= true;

				$this->_bInquiryOtherAttachments		= true;
				
				$this->_sSendTo							= 'objects';

				$this->_bStudentsFile					= true;  //aktivieren von Schülerinformatioen als Excel-Anhang  (false als default?)

				// Inquiry des ERSTEN Kunden
				$oTransfer								= Ext_TS_Inquiry_Journey_Transfer::getInstance($iSelectedId);
				$this->_oInquiry						= $oTransfer->getInquiry();
				$this->_oSchool							= $this->_oInquiry->getSchool();

				break;
			case 'transfer_customer_accommodation_information':

				// Mailbestätigung an Familien die Transfer erwarten
				$this->_bProviderEmails					= true;

				$this->_bInquiryOtherAttachments		= true;
				
				$this->_sSendTo							= 'objects';

				// Inquiry des ERSTEN Kunden
				$oTransfer								= Ext_TS_Inquiry_Journey_Transfer::getInstance($iSelectedId);
				$this->_oInquiry						= $oTransfer->getInquiry();
				$this->_oSchool							= $this->_oInquiry->getSchool();

				break;
			case 'transfer_customer_agency_information':

				// Mailgestätigung an Kunde bzw. Agentur über Transfer
				$this->_bAgencyEmails					= true;
				$this->_bStudentEmails					= true;

				$this->_bInquiryOtherAttachments		= true;
				
				$this->_bStudentAndAgencyEmails			= true;
				
				$this->_sSendTo							= 'objects';

				// Inquiry des ERSTEN Kunden
				$oTransfer								= Ext_TS_Inquiry_Journey_Transfer::getInstance($iSelectedId);
				$this->_oInquiry						= $oTransfer->getInquiry();
				$this->_oSchool							= $this->_oInquiry->getSchool();

				$aCustomerTemplate = array('customer'=>array(
												'label'=>L10N::t('Vorlage, Kunde', self::$_sL10NDescription),
												'application'=>$sApplication
											));

				// START Prüfen ob Kunden ODER Agentur ODER beides zur verfügung stehen soll
					$bShowAgencyTemplateSelect = false;
					$bShowCustomerTemplateSelect = false;

					foreach((array)$aSelectedIds as $iTransferId){
						$oTempTransfer = Ext_TS_Inquiry_Journey_Transfer::getInstance($iTransferId);
						$oTempInquiry = $oTempTransfer->getInquiry();

						if((int)$oTempInquiry->agency_id > 0){
							$bShowAgencyTemplateSelect = true;
						//}elseif((int)$oTempInquiry->agency_id == 0){
						// $bShowCustomerTemplateSelect = true;
						//}
						}
						if(true){
							$bShowCustomerTemplateSelect = true;
						}
					}

					$aCustomerTemplate = array();
					if($bShowCustomerTemplateSelect){
						$aCustomerTemplate = array(
								'customer'=>array(
										'label'=>L10N::t('Vorlage, Kunde', self::$_sL10NDescription),
										'application'=>$sApplication
								)
							);
					}

					$aAgencyTemplate = array();
					if($bShowAgencyTemplateSelect){
						$aAgencyTemplate = array(
								'agency'=>array(
									'label'=>L10N::t('Vorlage, Agentur', self::$_sL10NDescription),
									//'application'=>'accommodation_communication_agency' !! Warum Acc. ? es handelt sich doch um Agenturen!
									'application'=>'transfer_agency_information'
								)
							);
					}
				// ENDE

				// Individuelle Templateauswahl
				$this->_aTemplateOptions = $aCustomerTemplate + $aAgencyTemplate;

				break;

			/*
			 * Diese Case wird nur für den Fallback in der Unterkunftskommunikation verwendet in dem Fall, 
			 * dass eine Buchung noch keine Zuweisung hat
			 */
			case 'customer':

				$this->_bStudentEmails					= true;
				$this->_bAgencyEmails					= true;
				$this->_bOtherEmails					= true;

				$this->_bSchoolAttachments				= true;
				$this->_iSchoolAttachmentSection		= 3;

				$this->_sSendTo = 'objects';

				// _switchApplication() switchted die Application genau nur in dem Fall,
				//	aber diese Anhänge dürfen hier nicht dabei sein!
				if(
					$_VARS['additional'] === 'accommodation_communication_customer_agency'
				) {
					$this->_bInquiryOtherAttachments = true;
				} else {
					$this->_bInquiryInvoiceAttachments = true;
					$this->_bInquiryOtherAttachments = true;
				}

				$this->_oInquiry						= Ext_TS_Inquiry::getInstance($iSelectedId);
				$this->_oCustomer						= $this->_oInquiry->getCustomer();
				$this->_oSchool							= $this->_oCustomer->getSchool();

				$this->_sDialogTitle					= L10N::t('Kommunikation "{name}"', self::$_sL10NDescription);
				$this->_sDialogTitleMultiple			= L10N::t('Kunden » Kommunikation', self::$_sL10NDescription);
				
				foreach($this->_aTemplateOptions as $sType => $aOption)
				{
					$this->_aTemplateOptions[$sType]['application'] = 'accommodation_communication_customer_agency';
				}
				
				break;
			case 'enquiry':
				$this->_bStudentEmails					= true;
				$this->_bInquiryOtherAttachments		= true;
				$this->_bAgencyEmails					= true;
				$this->_bOtherEmails					= true;

				$this->_bSchoolAttachments				= true;
				$this->_bEnquiryAttachments				= true;
				$this->_iSchoolAttachmentSection		= 3;
				
				$this->_sSendTo							= 'objects';

				$this->_oInquiry						= Ext_TS_Inquiry::getInstance($iSelectedId);
				$this->_oSchool							= $this->_oInquiry->getSchool();

				$this->_sDialogTitle					= L10N::t('Kommunikation "{name}"', self::$_sL10NDescription);
				$this->_sDialogTitleMultiple			= L10N::t('Anfragen » Kommunikation', self::$_sL10NDescription);

				break;
			case 'tuition_teacher':
				$this->_bTeacherEmails					= true;
				$this->_sSendTo							= 'objects';
				$this->_bContractAttachments			= true;
				
				$this->_oTeacher						= Ext_Thebing_Teacher::getInstance($iSelectedId);
				$this->_oSchool							= Ext_Thebing_School::getSchoolFromSession();
				
				$this->_sDialogTitle					= L10N::t('Kommunikation "{lastname}"', self::$_sL10NDescription);

				foreach($aSelectedIds as $iSelectedId){
					$oTeacher = Ext_Thebing_Teacher::getInstance($iSelectedId);
					$aDocuments = [
						...$oTeacher->getContracts(),
						...$oTeacher->getDocumentsOfTypes('additional_document')
					];

					foreach($aDocuments as $oDocument){
						$oVersion = null;
						if ($oDocument instanceof \Ext_Thebing_Contract) {
							$oVersion = $oDocument->getLatestVersion();
						} else if ($oDocument instanceof \Ext_Thebing_Inquiry_Document) {
							$oVersion = $oDocument->getLastVersion();
						}

						if($oVersion){
							$this->_aItems[]	= $oVersion;
						}
					}
				}

				break;
			case 'marketing_agencies':
				$this->_bAgencyEmails					= true;
				$this->_sSendTo							= 'objects';
				$this->_bCreditnoteAttachments			= true;
				
				// Bei Multi auswahl ist hier nur die ERSTE Agentur
				$this->_oAgency							= Ext_Thebing_Agency::getInstance($iSelectedId);
				$this->_oSchool							= Ext_Thebing_School::getSchoolFromSession();

				$this->_sDialogTitle					= L10N::t('Kommunikation "{ext_1}"', self::$_sL10NDescription);
				$this->_sDialogTitleMultiple			= L10N::t('Marketing » Kommunikation', self::$_sL10NDescription);

				break;
			case 'agencies_payments':
				$this->_bAgencyEmails					= true;
				$this->_sSendTo							= 'objects';

				//$this->_oAgency						= Ext_Thebing_Customer::getInstance($iSelectedId);
				$this->_oSchool							= Ext_Thebing_School::getSchoolFromSession();

				$this->_sDialogTitle					= L10N::t('Kommunikation', self::$_sL10NDescription);
				break;
			case 'marketing_agencies_contact':
				$this->_bAgencyEmails					= true;
				//$this->_sSendTo							= 'objects';

				//$this->_oAgency						= Ext_Thebing_Customer::getInstance($iSelectedId);
				$this->_oSchool							= Ext_Thebing_School::getSchoolFromSession();

				$this->_sDialogTitle					= L10N::t('Kommunikation "{firstname}"', self::$_sL10NDescription);
				$this->_sDialogTitleMultiple			= L10N::t('Marketing » Kommunikation', self::$_sL10NDescription);

				break;

			case 'contract_teacher':

				$this->_bTeacherEmails					= true;

				$this->_bContractAttachments			= true;

				$oVersion			= Ext_Thebing_Contract_Version::getInstance($iSelectedId);
				$this->_aItems[]	= $oVersion;
				$oContract			= $oVersion->getContract();
				$this->_oSchool		= Ext_Thebing_School::getInstance($oContract->school_id);

				$this->_sSendTo = 'objects';

				break;

			case 'contract_accommodation':

				$this->_bProviderEmails					= true;

				$this->_bContractAttachments			= true;

				$oVersion			= Ext_Thebing_Contract_Version::getInstance($iSelectedId);
				$this->_aItems[]	= $oVersion;
				$oContract			= $oVersion->getContract();
				$this->_oSchool		= Ext_Thebing_School::getInstance($oContract->school_id);

				break;

			case 'accounting_teacher':
			case 'accounting_accommodation':
			case 'accounting_transfer':
				// Wir gehen mal davon aus, dass wir in der Liste der bezahlten Anbieterbezahlungen sind…
				$this->bAccountingProviderAttachments = true;
				foreach($aSelectedIds as $iKey => $iSelectedId) {
					if($sApplication === 'accounting_teacher') {
						$this->_bTeacherEmails	= true;
						$oItem = Ext_Thebing_Teacher::getInstance($iSelectedId);
						$oGrouping = Ext_TS_Accounting_Provider_Grouping_Teacher::getInstance($this->_aOriginalSelectedIds[$iKey]);
					} elseif($sApplication === 'accounting_accommodation') {
						$this->_bProviderEmails	= true;
						$oItem = Ext_Thebing_Accommodation::getInstance($iSelectedId);
						$oGrouping = Ext_TS_Accounting_Provider_Grouping_Accommodation::getInstance($this->_aOriginalSelectedIds[$iKey]);
					} elseif($sApplication === 'accounting_transfer') {
						$this->_bProviderEmails	= true;
						$oGrouping = Ext_TS_Accounting_Provider_Grouping_Transfer::getInstance($this->_aOriginalSelectedIds[$iKey]);
						$oItem = $oGrouping->getProvider();
						if($oItem->id != $iSelectedId) {
							throw new RuntimeException('Wrong provider returned for accounting_transfer?');
						}
					}

					/** @var Ext_Thebing_Teacher|Ext_Thebing_Accommodation|Ext_Thebing_Pickup_Company $oItem */
					if($oItem instanceof Ext_Thebing_Accommodation) {
						$this->_oSchool = Ext_Thebing_School::getSchoolFromSession();
					} else {
						$this->_oSchool = $oItem->getSchool();
					}

					/** @var Ext_TS_Accounting_Provider_Grouping_Abstract $oGrouping */
					$sFile = $oGrouping->file;
					if(!empty($sFile)) {
						$sPath = Ext_TC_Util::getPathWithRoot('storage/'.$oGrouping->file);
						$aAccountingFileInfo = pathinfo($sPath);
						$this->aAccountingProviderAttachments[$iSelectedId][] = [
							'path' => $sPath,
							'basename' => $aAccountingFileInfo['basename']
						];
					} else {
						// Immer befüllen, da man wissen muss, ob mehr als eine Gruppierung ausgewählt wurde
						$this->aAccountingProviderAttachments[$iSelectedId] = [];
					}
				}
				break;

			case 'accommodation_resources_provider':

				$this->_bProviderEmails = true;

				if(!Ext_Thebing_System::isAllSchools()) {
					$this->_oSchool = Ext_Thebing_School::getSchoolFromSession();
				} else {
					$oAccommodation = Ext_Thebing_Accommodation::getInstance(reset($aSelectedIds));
					$this->_oSchool = Ext_Thebing_School::getInstance(reset($oAccommodation->schools));
				}

				$this->_sDialogTitle = L10N::t('Kommunikation "{ext_33}"', self::$_sL10NDescription);
				//$this->_sDialogTitleMultiple = L10N::t('Kommunikation', self::$_sL10NDescription);

				break;

			case 'accommodation_communication_customer_agency':
			case 'accommodation_communication_history_customer_confirmed':
			case 'accommodation_communication_history_customer_canceled':
				$this->_bStudentEmails					= true;
				$this->_bAgencyEmails					= true;
				$this->_bOtherEmails					= true;
				
				$this->_bInquiryOtherAttachments		= true;
				
				$this->_bSchoolAttachments				= true;
				$this->_bStudentAndAgencyEmails			= true;

				if(count($aSelectedIds) == 1) {
					$this->_bAccommodationAttachments		= true;
				}

				$this->_sSendTo							= 'objects';

				$oAccommodationAllocation				= Ext_Thebing_Accommodation_Allocation::getInstance($iSelectedId);

				$oInquiryAmmommodation					= $oAccommodationAllocation->getInquiryAccommodation();

				$this->_oInquiry						= $oInquiryAmmommodation->getInquiry();
				$this->_oSchool							= $this->_oInquiry->getSchool();



				// START Prüfen ob Kunden ODER Agentur ODER beides zur verfügung stehen soll
					$bShowAgencyTemplateSelect = false;
					$bShowCustomerTemplateSelect = false;
					foreach((array)$aSelectedIds as $iAllocationId){
						$oTempAccommodationAllocation	= Ext_Thebing_Accommodation_Allocation::getInstance($iAllocationId);
						$oTempInquiryAmmommodation			= $oTempAccommodationAllocation->getInquiryAccommodation();
						$oTempInquiry = $oTempInquiryAmmommodation->getInquiry();
						if((int)$oTempInquiry->agency_id > 0){
							$bShowAgencyTemplateSelect = true;
						}

						$bShowCustomerTemplateSelect = true;
					}

					if($sApplication=='accommodation_communication_history_customer_confirmed'){
						if($bShowCustomerTemplateSelect){
							$sApplicationForTemplate = 'accommodation_communication_customer_agency';
						}
					}else{
						$sApplicationForTemplate = $sApplication;
					}

					$aCustomerTemplate = array();
					if($bShowCustomerTemplateSelect){
						$aCustomerTemplate = array(
								'customer'=>array(
										'label'=>L10N::t('Vorlage, Kunde', self::$_sL10NDescription),
										'application'=>$sApplicationForTemplate
								)
							);
					}

					$aAgencyTemplate = array();
					if($bShowAgencyTemplateSelect){
						$aAgencyTemplate = array(
								'agency'=>array(
									'label'=>L10N::t('Vorlage, Agentur', self::$_sL10NDescription),
									'application'=>'accommodation_communication_agency'
								)
							);
					}
				// ENDE

				// Individuelle Templateauswahl
				$this->_aTemplateOptions = $aCustomerTemplate + $aAgencyTemplate;

				break;

			case 'accommodation_communication_provider':
			case 'accommodation_communication_history_accommodation_confirmed':
			case 'accommodation_communication_history_accommodation_canceled':
				// (History Gui)Unterkunft an Unterkunft bestätigen
				$this->_bProviderEmails					= true;
				$this->_bReplacePlaceholderInDialog		= true;
				
				$this->_bInquiryOtherAttachments		= true;
				
				$this->_bSchoolAttachments				= true;

				$oAccommodationAllocation				= Ext_Thebing_Accommodation_Allocation::getInstance($iSelectedId);

				$oInquiryAmmommodation					= $oAccommodationAllocation->getInquiryAccommodation();

				$this->_oInquiry						= $oInquiryAmmommodation->getInquiry();

				$this->_oSchool							= $this->_oInquiry->getSchool();

				if($sApplication=='accommodation_communication_history_accommodation_confirmed'){
					$this->_aTemplateOptions['default']['application'] = 'accommodation_communication_provider';
				}

				//$this->_bStudentsFile					= true;  //aktivieren von Schülerinformatioen als Excel-Anhang  (false als default?)


				break;
			case 'activity':
				/** @var \TsActivities\Entity\Activity\BlockTraveller $oBlockTraveller */
				$oBlockTraveller = self::getObjectFromApplication($sApplication, $iSelectedId);
				$this->_sSendTo = 'objects';
				$this->_bStudentEmails = true;
				$this->_bOtherEmails = true;
				$this->_oInquiry = $oBlockTraveller->getInquiry();
				$this->_oSchool = $this->_oInquiry->getSchool();
				break;
			case 'client_payment':
				$this->_bAgencyOpenPaymentsAttachment	= true;
			case 'tuition_attendance':
			case 'placement_test':
			case 'simple_view':
			case 'arrival_list':
			case 'visum_list':
			case 'departure_list':
			case 'inbox':
			default:

				$this->_sSendTo							= 'objects'; // Noch nicht 100% getestet
				
				$this->_bStudentEmails					= true;
				$this->_bAgencyEmails					= true;
				$this->_bOtherEmails					= true;

				$this->_bInquiryOtherAttachments		= true;
				$this->_bInquiryInvoiceAttachments		= true;
				
				$this->_bSchoolAttachments				= true;

				$this->_oInquiry						= Ext_TS_Inquiry::getInstance($iSelectedId);
				$this->_oSchool							= $this->_oInquiry->getSchool();

				#$this->_sDialogTitle					= L10N::t('Kommunikation "{customer_name}"', self::$_sL10NDescription);
				$this->_sDialogTitle					= $this->_getDialogTitleByApplication($sApplication, $aSelectedIds);
				$this->_sDialogTitleMultiple			= L10N::t('Buchungen » Kommunikation', self::$_sL10NDescription);

				break;
		}
	}

	/**
	 * @param array $aSelectedIds
	 * @param string $sApplication
	 * @return array
	 * @throws Exception
	 */
	protected function _convertSelectedIds($aSelectedIds, $sApplication) {

		$aBack = [];

		foreach((array)$aSelectedIds as $iId) {

			// Originale ID in $aAdditional setzen, damit die Verbindung überall bestehen bleibt
			$aAdditional = ['original_id' => $iId];

			switch($sApplication) {
				case 'placement_test':
					$iNewId = $this->_oGui->decodeId($iId, 'inquiry_id');
					break;
				case 'feedback_list':
					$oFeedbackProcess = Ext_TS_Marketing_Feedback_Questionary_Process::getInstance($iId);
					$iNewId = $oFeedbackProcess->getInquiry()->id;
					break;
				case 'accommodation_communication_customer_agency':
				case 'accommodation_communication_provider':
					$iNewId = $this->_oGui->decodeId($iId, 'allocation_id');
					break;
				case 'customer':
					$iNewId = $this->_oGui->decodeId($iId, 'inquiry_id');
					if(is_null($iNewId)){
						// Unterkunfts. Komm zu Einträgen ohne Familienzuweisung
						$iNewId = $this->_oGui->decodeId($iId, 'accommodation_inquiry_id');
					}
					break;
				case 'enquiry':
					$iNewId = $this->_oGui->decodeId($iId);
					break;
				case 'insurance_customer':
				case 'insurance_provider':
					$iNewId = $this->_oGui->decodeId($iId, 'id');
					break;
				case 'tuition_attendance':
					$iNewId = $this->_oGui->decodeId($iId, 'inquiry_id');
					$aAdditional['allocation_id'] = $this->_oGui->decodeId($iId, 'id');
					break;
				case 'accounting_teacher':
					$oGrouping = Ext_TS_Accounting_Provider_Grouping_Teacher::getInstance($iId);
					$iNewId = $oGrouping->teacher_id;
					break;
				case 'accounting_accommodation':
					$oGrouping = Ext_TS_Accounting_Provider_Grouping_Accommodation::getInstance($iId);
					$iNewId = $oGrouping->accommodation_id;
					break;
				case 'accounting_transfer':
					$oGrouping = Ext_TS_Accounting_Provider_Grouping_Transfer::getInstance($iId);
					$iNewId = $oGrouping->provider_id;
					$aAdditional['provider_type'] = $oGrouping->provider_type;
					break;
				default:
					$iNewId = (int)$iId;
					break;
			}

			if(!is_null($iNewId)) {
				$aBack['decoded_ids'][] = (int)$iNewId;
				$aBack['additional'][] = $aAdditional; // Da man so schlau war, nur auf aussagekräftigen IDs aufzubauen…
				// Alle enkodierten Daten für die ID separat holen
				$aBack['decoded_data'][$iNewId] = $this->_oGui->decodeId($iId);
			}

		}

		return $aBack;
	}

	/**
	 * @param $oGui
	 * @param array $aSelectedIds
	 * @param string $sApplication
	 * @param array $_VARS
	 * @return Ext_Gui2_Dialog
	 */
	public static function getDialog(&$oGui, $aSelectedIds, $sApplication, $_VARS) {

		$oDummyDialog = new Ext_Gui2_Dialog();
		$oDummyDialog->additional = $sApplication;
		
		$oCommunication = new self($oDummyDialog, $aSelectedIds);
		$oCommunication->_oGui = $oGui;

		return $oCommunication->_getDialog($aSelectedIds, $sApplication, $_VARS);
	}

	/**
	 * Switched die Application, falls z.B. über ein Icon mehrere Appliccations angesprochen werden können
	 *
	 * @param string $sApplication
	 * @param array $aSelectedIds
	 * @return string
	 */
	protected function _switchApplication($sApplication, $aSelectedIds) {

		switch($sApplication) {
			case 'accommodation_communication_customer_agency':
				// Hat ein Eintrag noch keine Zuweisung zur Familie, wird hier die Inquiry Kommunikation benötigt
				if(empty($aSelectedIds)) {
					$this->_sOriginalApplication = $sApplication;
					$sApplication = 'customer';
				}
				break;
		}

		return $sApplication;
	}


	/**
	 * Liefert Informationen über den dialog
	 *
	 * @param string $sApplication
	 * @param string $sOriginalApplication
	 * @param array $aSelectedIds
	 * @param array $aOriginalSelectedIds
	 * @return array
	 */
	protected function _getDialogInfo($sApplication, $sOriginalApplication, $aSelectedIds, $aOriginalSelectedIds) {

		$aInfo = array();

		switch($sApplication) {
			case 'accommodation_communication_customer_agency':
				// In dieser Kommunikation sollen pro markierten Kunden eine Mail geschickt werden
				// Wenn es Kunden ohne inquiry_allocation_id gibt dann Hinweis anzeigen
				if(count($aOriginalSelectedIds) > count($aSelectedIds)) {
					$oClass = new stdClass();
					$oClass->info = L10N::t('Nicht alle markierten Kunden sind gematched.', self::$_sL10NDescription);
					$oClass->type = 'hint';
					$aInfo[] = $oClass;
				}
				break;
		}

		return $aInfo;
	}

	/**
	 * @param array $aSelectedIds
	 * @param string $sApplication
	 * @param array $_VARS
	 * @return Ext_Gui2_Dialog
	 * @throws Exception
	 */
	protected function _getDialog($aSelectedIds, $sApplication, $_VARS) {
		global $user_data;

		$aOriginalSelectedIds = $aSelectedIds;
		$sOriginalApplication = $sApplication;
		$this->_aOriginalSelectedIds = $aOriginalSelectedIds;

		$aDecoded = $this->_convertSelectedIds($aSelectedIds, $sApplication);
		$aSelectedIds = $aDecoded['decoded_ids'];

		// Falls mehrere Applications über ein Icon gesteuert werden müssen
		$sApplicationTemp = $this->_switchApplication($sApplication, $aSelectedIds);	
		if($sApplicationTemp != $sApplication) {
			$sApplication = $sApplicationTemp;
			$aDecoded = $this->_convertSelectedIds($aOriginalSelectedIds, $sApplication);
			$aSelectedIds = $aDecoded['decoded_ids'];
		}

		// Zusätzliche Infos für die IDs, da IDs ja so viele Infos haben können…
		$aSelectedIdsAdditional = $aDecoded['additional'];

		/**
		 * Session mit den Empfängerdaten zurücksetzen
		 */
		$this->aDialogPassData['recipients'] = [];

		$aSelectedIds	= (array)$aSelectedIds;
		$iSelectedId	= reset($aSelectedIds);

		/**
		 * Wenn mehrere Items ausgewählt wurden, dann werden die Felder für Betreff und Inhalt nicht angezeigt
		 */
		if(count($aSelectedIds) > 1) {
			$this->_bMultiSelection = true;
		} else {
			$this->_bMultiSelection = false;
		}
	
		/**
		 * Default Titel des Dialogs
		 */
		$this->_sDialogTitle = self::getApplications($sApplication);
		$this->_sDialogTitleMultiple = self::getApplications($sApplication);

		/**
		 * Holt sich die Flags und sonstigen Einstellungen der ausgwählten Anwendung
		 */
		$this->_getApplicationSettings($sApplication, $aSelectedIds);

		/**
		 * Initialisiert das Dialog Objekt
		 */
		$oCommunicationDialog = $this->_oGui->createDialog($this->_sDialogTitle, '', $this->_sDialogTitleMultiple);

		/**
		 * Dialog ID definieren
		 */
		$oCommunicationDialog->id = self::$_sIdTag.implode('_', (array)$aOriginalSelectedIds);
		$oCommunicationDialog->sDialogIDTag = self::$_sIdTag;

		/**
		 * Sprache für alle Items raussuchen
		 *
		 * Diese Auswahl wird für die Templateliste benötigt.
		 * Jedes Objekt muss die Methode getLanguage() haben!
		 */
		$aTemplateLanguages = array();
		$aObjectLanguages	= array();
		$sMailCheckCustomer	= '';
	

		foreach((array)$aSelectedIds as $iKey => $iId) {

			// Object auf dem die GUI basiert
			$oObject = self::_getObjectFromApplication($sApplication, $iId, $aSelectedIdsAdditional[$iKey]);

			// Sprache in der zu dem Obj. kommuniziert werden soll
			$sTempLanguage = $this->_getLanguageFromObject($oObject, $sApplication);

			$aObjectLanguages[$iId] = $sTempLanguage;
			$aTemplateLanguages[$sTempLanguage] = $sTempLanguage;

		}

		// Gibt es mehr als EINE Sprache, muss die Schulsprache genommen werden, da sonst die Vorschau nicht korrekt funktioniert (T-4012)
		if(count($aTemplateLanguages) > 1){
			$aTemplateLanguages = array();
			$sSchoolLanguage = $this->_oSchool->getLanguage();
			
			$aTemplateLanguages[$sSchoolLanguage] = $sSchoolLanguage;
			
			// Empfänger erhalten auch alle die Schulsprache
			foreach($aObjectLanguages as $iId => $sTempLanguage){
				$aObjectLanguages[$iId] = $sSchoolLanguage;
			}
			
			// Hauptsprache setzen
			$this->aDialogPassData['master_lang'] = $sSchoolLanguage;
		} else {
			// Bei Mehrfachauswahl muss das trotzdem gesetzt werden, da die Sprache bei Mehrfachauswahl nicht nochmal in die Platzhalter gesetzt wird #9796
			$this->aDialogPassData['master_lang'] = reset($aTemplateLanguages);
		}
		
		// Fehler die das öffnen des Kommunikationsdialoges verhindern
		$aCommunicationError = $this->getCommunicationError($aSelectedIds, $sApplication, $aSelectedIdsAdditional);

		if( !empty($aCommunicationError['message']) ){
			return $this->_oGui->_oData->getErrorDialog($aCommunicationError['message']);
		}elseif($aCommunicationError['dialog'] instanceof Ext_Gui2_Dialog){
			return $aCommunicationError['dialog'];
		}

		$sLanguage = $sTempLanguage;

		/**
		 * Tabs erstellen
		 */
		$oCommunicationTab = $oCommunicationDialog->createTab(L10N::t('Kommunikation', self::$_sL10NDescription));
		$oHistoryTab = $oCommunicationDialog->createTab(L10N::t('Historie', self::$_sL10NDescription));
		$oHistoryTab->class = 'communication_history';

		/**
		 * Ausgewählte Template IDs bearbeiten
		 */
		$aTemplateIds = array();
		if(!empty($_VARS['save']['template_id'])) {
			foreach((array)$_VARS['save']['template_id'] as $sKey=>$iValue) {
				$aTemplateIds[$sKey] = (int)$iValue;
			}
		}

		$iFirstTemplateId = 0;
		if(!empty($aTemplateIds)) {
			$iFirstTemplateId = reset($aTemplateIds);
		}

		$oTemplate = new Ext_Thebing_Email_Template($iFirstTemplateId);

		// Dialoghinweise einblenden
		$aInfo = $this->_getDialogInfo($sApplication, $sOriginalApplication, $aSelectedIds, $aOriginalSelectedIds);
		foreach((array)$aInfo as $oInfoClass){
			$oErrorDialog = $this->_oGui->createDialog();
			$oError = $oErrorDialog->createNotification(L10N::t('Achtung', self::$_sL10NDescription), $oInfoClass->info, $oInfoClass->type);
			$oCommunicationTab->setElement($oError);
		}

		$oCommunicationTab->setElement($oCommunicationDialog->createSaveField('hidden', array(
			'db_column'		=> 'application',
			'value'			=> $sApplication
		)));

		foreach((array)$this->_aTemplateOptions as $sKey => $aOptions) {

			// Wenn keine Schule gesetzt und in All Schools: Mögliche Templates müssen alle Schulen haben (z.B. für Agenturen in All Schools)
			if(
				Ext_Thebing_System::isAllSchools() &&
				!$this->_oSchool->exist()
			) {
				$aSchoolIds = array_column(Ext_Thebing_Client::getFirstClient()->getSchools(), 'id');
			} else {
				$aSchoolIds = [$this->_oSchool->id];
			}

			$aTemplates = $oTemplate->getList($aSchoolIds, $aOptions['application'], $aTemplateLanguages);

			// Wenn kein Template für diese Sprache da ist, Fehler ausgeben
			if(empty($aTemplates)) {
				// Prüfen ob es ggf. Templates gibt, aber nicht in der Sprache des Schülers (irgendeine Sprache)
				$aTempTemplateLanguages = $this->_oSchool->getLanguageList(); 
				$aTempTemplates = $oTemplate->getList($aSchoolIds, $aOptions['application'], $aTempTemplateLanguages);

				if(!empty($aTempTemplates)) {
					$sError = L10N::t('Es wurden Vorlagen gefunden. Jedoch nicht in der Korrespondenzsprache dieser Empfänger.', self::$_sL10NDescription);		
				} else {
					$sError = L10N::t('Es ist keine Vorlage für die Korrespondenzsprache diese Empfänger verfügbar.', self::$_sL10NDescription);
				}
				
				return $this->_oGui->getDataObject()->getErrorDialog($sError);
				
			}

			$aTemplateSelect = Ext_Thebing_Util::addEmptyItem($aTemplates, L10N::t('Bitte wählen', self::$_sL10NDescription));

			// Wenn nur ein Template gefunden wurde, dieses vorauswählen
			if(
				empty($aTemplateIds[$sKey]) &&
				count($aTemplates) == 1
			) {
				reset($aTemplates);
				$aTemplateIds[$sKey] = key($aTemplates);
				$oTemplate = new Ext_Thebing_Email_Template($aTemplateIds[$sKey]);
			}

			$oCommunicationTab->setElement($oCommunicationDialog->createRow($aOptions['label'], 'select', array('db_column'=>'template_id', 'class'=>'template_select_communication', 'name'=>'save[template_id]['.$sKey.']', 'select_options' => $aTemplateSelect, 'default_value'=>$aTemplateIds[$sKey] ?? null)));

		}

		// Felder erst anzeigen, wenn Template ausgewählt ist
		if($oTemplate->id > 0) {

			// Bei All Schools soll explizit auch die Einstellung aus All Schools verwendet werden
			$iSchoolForIdentities = $this->_oSchool->id;
			if(
				!$this->_oSchool->exist() ||
				Ext_Thebing_System::isAllSchools()
			) {
				$iSchoolForIdentities = 0;
			}

			/**
			 * Setz die Absenderidentität
			 */
			$oUser = Ext_Thebing_User::getInstance($user_data['id']);
			$aIdentities = $oUser->getCommunicationIdentities($iSchoolForIdentities, true);

			$iDefaultIdentity = $user_data['id'];
			// Wenn das Template eine Default Identity hat und der User diese nutzen darf
			if(
				$oTemplate->default_identity_id > 0
				/*
					Soll laut ticket 2501 übersehen werden
						&& isset($aIdentities[$oTemplate->default_identity_id])
				*/
			) {
				$iDefaultIdentity = $oTemplate->default_identity_id;

				$oClient = Ext_Thebing_Client::getInstance();
				$aUsers = $oClient->getUsers(true);

				$aIdentities[$oTemplate->default_identity_id] = $aUsers[$oTemplate->default_identity_id];
			}

			$oCommunicationTab->setElement($oCommunicationDialog->createRow(L10N::t('Absender', self::$_sL10NDescription), 'select', array('db_column'=>'identity_id', 'select_options' => $aIdentities, 'default_value'=>$iDefaultIdentity)));

			/**
			 * Empfänger
			 */

			// Default recipients
			$aRecipients = array();
			$aRecipients['to'] = '';
			$aRecipients['cc'] = $oTemplate->cc;
			$aRecipients['bcc'] = $oTemplate->bcc;

			$aRecipientOptions = array();
			$aRecipientOptions['student'] = array();
			$aRecipientOptions['emergency'] = array();
			$aRecipientOptions['other'] = array();
			$aRecipientOptions['provider'] = array();
			$aRecipientOptions['agency'] = array();
			$aRecipientOptions['teacher'] = array();

			/**
			 * Durchlaufe alle Items und hole alle Empfänger je nach Anwendung
			 * Nur wenn es Empfänger für die einzelnen Items gibt
			 */
			foreach((array)$aSelectedIds as $iKey => $iId) {

				$oObject = self::_getObjectFromApplication($sApplication, $iId, $aSelectedIdsAdditional[$iKey]);

				$sLanguage = $aObjectLanguages[$iId];

				switch($sApplication) {
					case 'insurance_provider':

						/** @var Ext_TS_Inquiry_Journey_Insurance $oObject */
						$oProvider = $oObject->getInsuranceProvider();

						$aTemp = $oProvider->getRecipients();
						$aRecipientOptions['provider'] += $this->convertToSessionIds($aTemp, 'Ext_TS_Inquiry_Journey_Insurance', $oProvider->id, $iId, $sLanguage);

						break;
					case 'insurance_customer':

						/** @var Ext_TS_Inquiry_Journey_Insurance $oObject */
						$oInquiry = $oObject->getInquiry();

						$aTemp = $oInquiry->getCustomerEmails(false);
						$aRecipientOptions['student'] += $this->convertToSessionIds($aTemp, 'Ext_TS_Inquiry_Journey_Insurance', $iId, $iId, $sLanguage);

						$this->addOtherContacts($oInquiry, $aRecipientOptions['emergency'], $sLanguage);

						// TODO Das ist doch so nicht richtig, da die Einträge niemals zum Provider zugewiesen werden
						$aOther = $this->getInquiryProviderContacts($oInquiry);
						$aRecipientOptions['other'] += $this->convertToSessionIds($aOther, 'Ext_TS_Inquiry_Journey_Insurance', $iId, $iId, $sLanguage);

						if($oInquiry->hasAgency()) {
							$oAgency = $oInquiry->getAgency();
							
							$aContacts = $oInquiry->getAgencyContactsWithValidEmails();
							foreach((array)$aContacts as $oContact) {
								// Der Text einer Option im Multiselect der Emailadressen der Agenturen
								$sOptionText = $oAgency->ext_2 . ': ' . $oContact->name . ' (' . $oContact->email . ')';
								$this->addToSessionIds($aRecipientOptions['agency'], $sOptionText, 'Ext_Thebing_Agency_Contact', $oContact->id, $iId, $oContact->email, $sLanguage);
							}
						}

						break;
					case 'transfer_provider_confirm':

						$aTransferProvider = Ext_TS_Inquiry_Journey_Transfer::getAllProviderConfirmMails(array($iId));

						foreach((array)$aTransferProvider as $aItem) {
							$sName = $aItem['name'].' ('.$aItem['email'].')';
							$this->addToSessionIds($aRecipientOptions['provider'], $sName, $aItem['object'], $aItem['object_id'], $iId, $aItem['email'], $sLanguage);
						}
						break;
					case 'transfer_customer_accommodation_information':
						$oJourneyTransfer = Ext_TS_Inquiry_Journey_Transfer::getInstance($iId);
						$aTransferAcc = $oJourneyTransfer->getMatchingAccommodationProvidersMails();

						foreach((array)$aTransferAcc as $aItem) {
							$this->addToSessionIds($aRecipientOptions['provider'], $aItem['name'], $aItem['object'], $aItem['object_id'], $iId, $aItem['email'], $sLanguage);
						}
						break;
					case 'transfer_customer_agency_information':
						$aTransferAgency = Ext_TS_Inquiry_Journey_Transfer::getAllCustomerConfirmMails(array($iId), 'agency');

						$aTransferCustomer = Ext_TS_Inquiry_Journey_Transfer::getAllCustomerConfirmMails(array($iId), 'customer', $this->_bStudentAndAgencyEmails);

						foreach((array)$aTransferAgency as $aItem) {
							$this->addToSessionIds($aRecipientOptions['agency'], $aItem['name'], $aItem['object'], $aItem['object_id'], $iId, $aItem['email'], $sLanguage);
						}

						foreach((array)$aTransferCustomer as $aItem) {
							$this->addToSessionIds($aRecipientOptions['student'], $aItem['name'], $aItem['object'], $aItem['object_id'], $iId, $aItem['email'], $sLanguage);
						}
						break;
					case 'customer':

						$oInquiry = Ext_TS_Inquiry::getInstance($iId);

						$aMailCustomer = $oInquiry->getCustomerEmails(false);

						foreach((array)$aMailCustomer as $aMailData){
							$this->addToSessionIds($aRecipientOptions['student'], $aMailData['name'], $aMailData['object'], $aMailData['object_id'], $iId, $aMailData['email'], $sLanguage);
						}

						$this->addOtherContacts($oInquiry, $aRecipientOptions['emergency'], $sLanguage);

						if($oInquiry->agency_id > 0) {
							
							$bFor = null;
							
							if($this->_sOriginalApplication == 'accommodation_communication_customer_agency') {
								$bFor = 'accommodation';
							}
							
							$aContacts = $oInquiry->getAgencyContactsWithValidEmails($bFor);

							foreach((array)$aContacts as $oContact) {
								$this->addToSessionIds($aRecipientOptions['agency'], $oContact->name_description, 'Ext_Thebing_Agency_Contact', $oContact->id, $iId, $oContact->email, $sLanguage);
							}
						}

						break;
//					case 'enquiry':
//						$oEnquiry	= Ext_TS_Enquiry::getInstance($iId);
//
//						$aMailCustomer = $oEnquiry->getCustomerEmails();
//
//						foreach((array)$aMailCustomer as $aMailData){
//							$this->addToSessionIds($aRecipientOptions['student'], $aMailData['name'], $aMailData['object'], $aMailData['object_id'], $iId, $aMailData['email'], $sLanguage);
//						}
//
//						if($oEnquiry->agency_id > 0) {
//							$aContacts = $oEnquiry->getAgencyContactsWithValidEmails();
//							foreach((array)$aContacts as $oContact) {
//								$this->addToSessionIds($aRecipientOptions['agency'], $oContact->name_description, 'Ext_Thebing_Agency_Contact', $oContact->id, $iId, $oContact->email, $sLanguage);
//							}
//						}
//						break;
					case 'tuition_teacher':
						$oTeacher = Ext_Thebing_Teacher::getInstance((int)$iId);
						
						$this->addToSessionIds($aRecipientOptions['teacher'], $oTeacher->getEmailformatForCommunication(), 'Ext_Thebing_Teacher', $oTeacher->id, $iId, $oTeacher->email, $sLanguage);
						break;
					case 'marketing_agencies':
						/** @var Ext_Thebing_Agency $oAgency */
						$oAgency = self::getObjectFromApplication('marketing_agencies', $iId);
						$this->addAgencyContacts($oAgency, $aRecipientOptions['agency'], $iId, $sLanguage);
						break;
					case 'agencies_payments':
						/** @var Ext_Thebing_Agency_Payment $oAgencyPayment */
						$oAgencyPayment = self::getObjectFromApplication('agencies_payments', $iId);
						$oAgency = $oAgencyPayment->getAgency();
						$this->addAgencyContacts($oAgency, $aRecipientOptions['agency'], $oAgency->id, $sLanguage); // selected_id war schon immer $oAgency->id
						break;
					case 'marketing_agencies_contact':
						/** @var Ext_Thebing_Agency_Contact $oAgencyContact */
						$oAgencyContact = self::getObjectFromApplication('marketing_agencies_contact', $iId);
						$sName = sprintf('%s (%s)', $oAgencyContact->getName(), $oAgencyContact->email);
						$this->addToSessionIds($aRecipientOptions['agency'], $sName, get_class($oAgencyContact), $oAgencyContact->id, $iId, $oAgencyContact->email, $sLanguage);
						break;
					case 'contract_teacher':

						$oVersion = Ext_Thebing_Contract_Version::getInstance($iId);
						$oContract = $oVersion->getContract();
						$oItem = $oContract->getItemObject();
						
						if(strpos($oItem->email, 'noemail') === false){
							$this->addToSessionIds($aRecipientOptions['teacher'], $oItem->getEmailformatForCommunication(), 'Ext_Thebing_Teacher', $oItem->id, $iId, $oItem->email, $sLanguage);
						}

						break;

					case 'contract_accommodation':

						$oVersion = Ext_Thebing_Contract_Version::getInstance($iId);
						$oContract = $oVersion->getContract();
						$oItem = $oContract->getItemObject();

						$sName = $oItem->name.' ('.$oItem->email.')';
						
						$this->addToSessionIds($aRecipientOptions['provider'], $sName, 'Ext_Thebing_Accommodation', $oItem->id, $iId, $oItem->email, $sLanguage);

						$members = $oItem->getMembersWithEmail();
						foreach ($members as $member) {
							$name = sprintf('%s - %s: %s (%s)', $oItem->getName(), \L10N::t('Zugehöriger'), $member->getName(), $member->email);
							$this->addToSessionIds($aRecipientOptions['provider'], $name, $member::class, $member->id, $iId, $member->email, $sLanguage);
						}

						break;

					case 'accounting_teacher':
					case 'accounting_accommodation':
					case 'accounting_transfer':

						$sType = 'provider';
						if($sApplication === 'accounting_teacher') {
							$sType = 'teacher';
						}
						$sMail = $oObject->email;
						$sName = $oObject->getName().' ('.$sMail.')';
						if(!empty($sMail)) {
							$this->addToSessionIds($aRecipientOptions[$sType], $sName, get_class($oObject), $oObject->id, $iId, $sMail, $sLanguage, $aSelectedIdsAdditional[$iKey]);
						}
						break;

					case 'tuition_attendance':
					case 'placement_test':
					case 'simple_view':
					case 'arrival_list':
					case 'feedback_list':
					case 'visum_list':
					case 'departure_list':
					case 'client_payment':
					case 'inbox':
					case 'enquiry':

						$oInquiry = Ext_TS_Inquiry::getInstance($iId);

						$aMailCustomer = $oInquiry->getCustomerEmails(false);
						
						foreach((array)$aMailCustomer as $aMailData) {
							$this->addToSessionIds($aRecipientOptions['student'], $aMailData['name'], $aMailData['object'], $aMailData['object_id'], $iId, $aMailData['email'], $sLanguage);
						}

						$this->addOtherContacts($oInquiry, $aRecipientOptions['emergency'], $sLanguage);

						// TODO Das ist doch so nicht richtig, da die Einträge niemals zum Provider zugewiesen werden
						$aOther = $this->getInquiryProviderContacts($oInquiry);
						$aRecipientOptions['other'] += $this->convertToSessionIds($aOther, 'Ext_TS_Inquiry', $iId, $iId, $sLanguage);

						if($oInquiry->hasAgency()) {
							$aContacts = $oInquiry->getAgencyContactsWithValidEmails('reminder');
							foreach((array)$aContacts as $oContact) {
								$this->addToSessionIds($aRecipientOptions['agency'], $oContact->name_description, 'Ext_Thebing_Agency_Contact', $oContact->id, $iId, $oContact->email, $sLanguage);
							}
						}

						if(
							$oInquiry->isSponsored() &&
							$oInquiry->sponsor_id != 0
						) {
							$aContacts = $oInquiry->getSponsorContactsWithValidEmails();
							foreach($aContacts as $oContact) {
								$this->addToSessionIds($aRecipientOptions['other'], $oContact->getCommunicationLabel(), 'Ext_TS_Contact', $oContact->id, $iId, $oContact->email, $sLanguage);
							}
						}

						$salesPerson = $oInquiry->getSalesPerson();

						if($salesPerson != null) {
							$this->addToSessionIds($aRecipientOptions['other'], L10N::t('Vertriebsmitarbeiter').': '.$salesPerson->name, 'Ext_Thebing_User', $salesPerson->id, $iId, $salesPerson->email, $sLanguage);
						}

						$booker = $oInquiry->getBooker();
						$bookerName = L10N::t('Rechnungskontakt');
						if (!empty($booker->lastname)) {
							$bookerName .= ': '.$booker->lastname;
							if (!empty($booker->firstname)) {
								$bookerName .= ', '.$booker->firstname;
							}
						} elseif (!empty($booker->firstname)) {
							$bookerName .= ': '.$booker->firstname;
						}

						if (!empty($booker->email)) {
							$bookerName .= ' ('.$booker->email.')';
						}

						if($booker != null) {
							$this->addToSessionIds($aRecipientOptions['other'], $bookerName, Ext_TS_Inquiry_Contact_Booker::class, $booker->id, $iId, $booker->email, $sLanguage);
						}

						break;

					case 'accommodation_resources_provider':

						$oAccommodation = Ext_Thebing_Accommodation::getInstance($iId);
						$sName = $oAccommodation->name.' ('.$oAccommodation->email.')';
						$this->addToSessionIds($aRecipientOptions['provider'], $sName, 'Ext_Thebing_Accommodation', $oAccommodation->id, $iId, $oAccommodation->email, $sLanguage);

						$members = $oAccommodation->getMembersWithEmail();
						foreach ($members as $member) {
							$name = sprintf('%s - %s: %s (%s)', $oAccommodation->getName(), \L10N::t('Zugehöriger'), $member->getName(), $member->email);
							$this->addToSessionIds($aRecipientOptions['provider'], $name, $member::class, $member->id, $iId, $member->email, $sLanguage);
						}

						break;

					case 'accommodation_communication_customer_agency':

						$aMailAgency	= $oObject->getMail4Communication('agency');
						$allocation = \Ext_Thebing_Accommodation_Allocation::getInstance($iId);
						$inquiry = $allocation->getInquiry();

						$aMailCustomer = $inquiry->getCustomerEmails(false);

						$this->addOtherContacts($inquiry, $aRecipientOptions['emergency'], $sLanguage, $iId);

						if(!empty($aMailAgency)){
							foreach((array)$aMailAgency as $aMailData){
								$this->addToSessionIds($aRecipientOptions['agency'], $aMailData['name'], $aMailData['object'], $aMailData['object_id'], $iId, $aMailData['email'], $sLanguage);
							}
						}

						if(!empty($aMailCustomer)){
							foreach((array)$aMailCustomer as $aMailData){
								$this->addToSessionIds($aRecipientOptions['student'], $aMailData['name'], $aMailData['object'], $aMailData['object_id'], $iId, $aMailData['email'], $sLanguage);
							}
						}

						break;
					case 'accommodation_communication_history_customer_confirmed':
					case 'accommodation_communication_history_customer_canceled';

						$aMailCustomer	= $oObject->getMail4Communication('customer', $this->_bStudentAndAgencyEmails);
						$aMailAgency	= $oObject->getMail4Communication('agency');

						if(!empty($aMailAgency)){
							foreach((array)$aMailAgency as $aMailData){
								$this->addToSessionIds($aRecipientOptions['agency'], $aMailData['name'], $aMailData['object'], $aMailData['object_id'], $iId, $aMailData['email'], $sLanguage);
							}
						}

						if(!empty($aMailCustomer)){
							foreach((array)$aMailCustomer as $aMailData){
								$this->addToSessionIds($aRecipientOptions['student'], $aMailData['name'], $aMailData['object'], $aMailData['object_id'], $iId, $aMailData['email'], $sLanguage);
						}
						}

						break;

					case 'accommodation_communication_provider':
					case 'accommodation_communication_history_accommodation_confirmed':
					case 'accommodation_communication_history_accommodation_canceled':

						$aMailAcc	= $oObject->getMail4Communication('accommodation');

						if(!empty($aMailAcc)){
							$this->addToSessionIds($aRecipientOptions['provider'], $aMailAcc['name'], $aMailAcc['object'], $aMailAcc['object_id'], $iId, $aMailAcc['email'], $sLanguage);

							/* @var \Ext_Thebing_Accommodation $accommodation */
							$accommodation = \Factory::getInstance($aMailAcc['object'], $aMailAcc['object_id']);
							$members = $accommodation->getMembersWithEmail();
							foreach ($members as $member) {
								$name = sprintf('%s - %s: %s (%s)', $accommodation->getName(), \L10N::t('Zugehöriger'), $member->getName(), $member->email);
								$this->addToSessionIds($aRecipientOptions['provider'], $name, $member::class, $member->id, $iId, $member->email, $sLanguage);
							}
						}

						break;
					case 'activity':

						/** @var \TsActivities\Entity\Activity\BlockTraveller $oObject */
						$oTraveller = $oObject->getContact();
						$aEmails = $oTraveller->getEmails();
						$oInquiry = $oObject->getJourneyActivity()->getJourney()->getInquiry();

						$this->_oGui->setMainObject($oInquiry->id, get_class($oInquiry));

						foreach($aEmails as $aEmail) {
							$this->addToSessionIds($aRecipientOptions['student'], $aEmail['name'], get_class($oObject), $oObject->id, $iId, $aEmail['email'], $sLanguage);
						}

						// Provider (Aktivität)
						$oProvider = $oObject->getBlock()->getProvider();
						$oProviderContact = $oObject->getBlock()->getProvider()->getContact();
						if (Util::checkEmailMx($oProviderContact->email)) {
							$aEmail = [$oProviderContact->email => sprintf('%s: %s (%s)',  L10N::t('Anbieter'), $oProvider->getName(), $oProviderContact->email)];
							$aRecipientOptions['other'] += $this->convertToSessionIds($aEmail, get_class($oProvider), $oProvider->id, $iId, $sLanguage);
						}

						// Provider (Unterkunft)
						foreach ($this->getInquiryProviderContacts($oInquiry) as $aProvider) {
							if ($aProvider['object'] === Ext_Thebing_Accommodation::class) {
								$aEmail = [$aProvider['email'] => $aProvider['name']];
								$aRecipientOptions['other'] += $this->convertToSessionIds($aEmail, $aProvider['object'], $aProvider['object_id'], $iId, $sLanguage);
							}
						}

						break;
					default:
						break;
				}
			}

			// Bereitet die Emailiste für den Dialog vor
			$aRecipientOptions = $this->formatRecipients($aRecipientOptions);

			/**
			 * Empfänger ermitteln, falls es Empfänger für die gewählte Gruppe von Items gibt
			 */
			switch($sApplication) {
				case 'transfer_provider_request':
					// Empfänger vorbereiten

					$aTransferProvider = Ext_TS_Inquiry_Journey_Transfer::getAllProviderMails($aSelectedIds);

					foreach((array)$aTransferProvider as $aItem) {
						$this->addToSessionIds($aRecipientOptions['provider'], $aItem['name'], $aItem['object'], $aItem['object_id'], $aSelectedIds, $aItem['email'], $sLanguage);
					}

					break;
			}

			// Excel Documente erstellen
			// TODO Das Dokument wird bei JEDEM Öffnen des Dialogs generiert!
			switch($sApplication){
				case 'transfer_provider_request':
				case 'transfer_provider_confirm':
				case 'transfer_customer_agency_information':
					// Excel Anhang
					$sStudentsFile = $this->getExcelPickupDocument($aSelectedIds);
					break;
				case 'accommodation_communication_provider':
					// Excel Anhang
					//$sStudentsFile = $this->getExcelAccommodationDocument($aSelectedIds);
					break;

			}

			foreach((array)$this->_aRecipientInputs as $sKey=>$sValue) {

				$oFieldset = $oCommunicationDialog->create('fieldset');
				$oFieldset->class = 'simple_editor_container';

				$oFieldset->setElement($oCommunicationDialog->createRow(L10N::t($sValue, self::$_sL10NDescription), 'textarea', array('db_column'=>$sKey, 'id'=>'save_'.$sKey, 'default_value'=>$aRecipients[$sKey], 'style'=>'height: 40px;width: 750px;', 'class'=>'txt simple_editor')));
				$oDiv = $oCommunicationDialog->create('div');
				$oDiv->class = 'recipient';
				$oDiv->id = 'recipient_'.$sKey;
				if($sKey != 'to') {
					$oDiv->style = 'display: none;';
				}

				if(
					$this->_bStudentEmails &&
					!empty($aRecipientOptions['student'])
				) {
					$oDiv->setElement($oCommunicationDialog->createRow(L10N::t('Schüler', self::$_sL10NDescription), 'select', array('db_column'=>$sKey.'_student', 'multiple'=>3, 'jquery_multiple'=>1, 'style'=>'height: 65px;width: 750px;', 'select_options'=>$aRecipientOptions['student'])));
					if(!empty($aRecipientOptions['emergency'])) {
						$oDiv->setElement($oCommunicationDialog->createRow(L10N::t('Weitere Kontakte', self::$_sL10NDescription), 'select', array('db_column'=>$sKey.'_emergency', 'multiple'=>3, 'jquery_multiple'=>1, 'style'=>'height: 65px;width: 750px;', 'select_options'=>$aRecipientOptions['emergency'])));
					}
				}

				if(
					$this->_bOtherEmails &&
					!empty($aRecipientOptions['other'])
				) {
					$oDiv->setElement($oCommunicationDialog->createRow(L10N::t('Sonstige', self::$_sL10NDescription), 'select',
						[
							'db_column'=>$sKey.'_other',
							'multiple'=>3,
							'jquery_multiple'=>1,
							'style'=>'height: 65px;width: 750px;',
							'select_options'=>$aRecipientOptions['other'],
							'searchable' => true
						]
					));
				}

				if(
					$this->_bAgencyEmails &&
					!empty($aRecipientOptions['agency'])
				) {
					$oDiv->setElement($oCommunicationDialog->createRow(L10N::t('Agenturen', self::$_sL10NDescription), 'select', array('db_column'=>$sKey.'_agency', 'multiple'=>3, 'jquery_multiple'=>1, 'style'=>'height: 65px;width: 750px;', 'select_options'=>$aRecipientOptions['agency'], 'searchable' => true)));
				}

				if(
					$this->_bProviderEmails &&
					!empty($aRecipientOptions['provider'])
				) {
					$oDiv->setElement($oCommunicationDialog->createRow(L10N::t('Anbieter', self::$_sL10NDescription), 'select', array('db_column'=>$sKey.'_provider', 'multiple'=>3, 'jquery_multiple'=>1, 'style'=>'height: 65px;width: 750px;', 'select_options'=>$aRecipientOptions['provider'])));
				}

				if(
					$this->_bTeacherEmails &&
					!empty($aRecipientOptions['teacher'])
				) {
					$oDiv->setElement($oCommunicationDialog->createRow(L10N::t('Lehrer', self::$_sL10NDescription), 'select', array('db_column'=>$sKey.'_teacher', 'multiple'=>3, 'jquery_multiple'=>1, 'style'=>'height: 65px;width: 750px;', 'select_options'=>$aRecipientOptions['teacher'])));
				}

				$oFieldset->setElement($oDiv);
				$oCommunicationTab->setElement($oFieldset);

			}

			// Mail Inhalt und Signatur pro Template ---------------------------------------------------
			$oH3 = $oCommunicationDialog->create('h4');
			$oH3->setElement(L10N::t('Inhalt', self::$_sL10NDescription)); 
			$oCommunicationTab->setElement($oH3);

			$oTabArea = $oCommunicationDialog->createTabArea();

			// Counter für eindeutige Felder
			$iCount = 0;
			
			foreach((array)$this->_aTemplateOptions as $sKey => $aOption){

				$iTemplateId = $aTemplateIds[$sKey];

				if($iTemplateId <= 0){
					continue;
				}

				$oTemplate = Ext_Thebing_Email_Template::getInstance($iTemplateId);

				$sSubject = $oTemplate->__get('subject_'.$sLanguage);
				$sContent = $oTemplate->__get('content_'.$sLanguage);

				/**
				 * Platzhalter ersetzen
				 */
				if(
					$this->_bReplacePlaceholderInDialog &&
					!$this->_bMultiSelection
				) {

					$sObject = self::_getObjectFromApplication($sApplication);

					// Es gibt nur eine SelectedId da keine multi-komm
					$aTemp = array($iSelectedId);
					
					$oRecipient = new Ext_Thebing_Communication_RecipientDTO;
					$oRecipient->iObjectId = $iSelectedId;

					$aSelectedIdAdditional = [];
					if(!empty($aSelectedIdsAdditional)) {
						$aSelectedIdAdditional = reset($aSelectedIdsAdditional);
					}
					
					$oPlaceholder = $this->_getPlaceholderObject($sObject, $oRecipient, $sApplication, $aTemp, $aSelectedIdAdditional);
					$oPlaceholder->sTemplateLanguage = $sLanguage;
					$oPlaceholder->bInitialReplace = true;

					if(is_object($oPlaceholder)) {
						$sSubject = $oPlaceholder->replace($sSubject);
						$sContent = $oPlaceholder->replace($sContent);
					}

				}

				$oTemplateUser = Ext_Thebing_User::getInstance($iDefaultIdentity);

				// Signatur
				if($oTemplate->html == 1) {
					$sType = 'html';
					$sSignatureKey = 'signature_email_html_'.$sLanguage.'_'.$this->_oSchool->id;
				} else {
					$sType = 'textarea';
					$sSignatureKey = 'signature_email_text_'.$sLanguage.'_'.$this->_oSchool->id;
				}

				$oTabContent = $oTabArea->createTab($aOption['label']);

				$oDiv = new Ext_Gui2_Html_Div();
				$oDiv->setElement($oCommunicationDialog->createRow(L10N::t('Betreff', self::$_sL10NDescription), 'input', array('db_column'=>'subject]['. $iCount . '_' . $oTemplate->id, 'style'=>'width: 750px;', 'default_value'=>$sSubject, 'required' => true)));
				$oDiv->setElement($oCommunicationDialog->createRow(L10N::t('Inhalt', self::$_sL10NDescription), $sType, array('db_column'=>'content]['. $iCount . '_' . $oTemplate->id, 'style'=>'width: 750px;height:380px;', 'default_value'=>$sContent)));

				$sSignature = $oTemplateUser->$sSignatureKey;

				$oDiv->setElement($oCommunicationDialog->createRow(L10N::t('Signatur', self::$_sL10NDescription), $sType, array('db_column'=>'signature]['. $iCount . '_' . $oTemplate->id, 'style'=>'width: 750px;height:60px;', 'default_value'=>$sSignature)));

				// Templatekategory
				$oHidden		= $oCommunicationDialog->create('input');
				$oHidden->type	= 'hidden';
				$oHidden->id	= 'save[template_category]['. $iCount . '_' . $oTemplate->id.']';
				$oHidden->name	= 'save[template_category]['. $iCount . '_' . $oTemplate->id.']';
				$oHidden->value	= $sKey;
				$oCommunicationTab->setElement($oHidden);

				$oTabContent->setElement($oDiv);
				
				$iCount++;
			}

			$oCommunicationTab->setElement($oTabArea);

			// Anhänge
			$oH3 = $oCommunicationDialog->create('h4');
			$oH3->setElement(L10N::t('Anhänge', self::$_sL10NDescription));
			$oCommunicationTab->setElement($oH3);
	
			/**
			 * Wenn Anhänge verfügbar sind
			 */
			if(
				$this->_bInquiryInvoiceAttachments ||
				$this->_bInquiryOtherAttachments ||
				$this->_bSchoolAttachments ||
				$this->_bContractAttachments ||
				$this->_bAccommodationAttachments ||
				$this->_bStudentsFile ||
				$this->_bAgencyOpenPaymentsAttachment ||
				$this->_bCreditnoteAttachments ||
				$this->_bEnquiryAttachments ||
				$this->bAccountingProviderAttachments
			) {

				if($this->_bStudentsFile) {
					
					$sStudentsFileUrl = '/'.str_replace(\Util::getDocumentRoot(), '', $sStudentsFile);
					$sStudentsFilePreview = ' <a href="'.$sStudentsFileUrl.'" onclick="window.open(this.href);return false;">'.$this->_oGui->t('Vorschau').' <i class= "fa fa-file-excel-o"></i></a>';
					$this->aDialogPassData['document_students'] = $sStudentsFile;

					$oCommunicationTab->setElement(
						$oCommunicationDialog->createMultiRow(
							$this->_oGui->t('Schülerdaten anhängen (Excel)'),
							[
								'items' => [
									[
										'db_column' => 'document_students',
										'input' => 'checkbox',
										'text_after' => $sStudentsFilePreview,
										'value' => '1'
									],
								]
							]
						)
					);
				
				}

				/**
				 * Rechnungen und Dokumente der Buchung
				 * Nur verfügbar, wenn nur ein Eintrag ausgewählt ist
				 */
				if(
					$this->_bInquiryInvoiceAttachments &&
					!$this->_bMultiSelection &&
					$this->_oInquiry
				) {
					$aInvoices = $this->_oInquiry->getDocuments('invoice_with_creditnote');
					$aInvoices = Ext_Thebing_Inquiry_Document::prepareDocumentsForSelect($aInvoices, 'version_id');
					if(!empty($aInvoices)) {
						$oCommunicationTab->setElement($oCommunicationDialog->createRow(L10N::t('Rechnungen', self::$_sL10NDescription), 'select', array('db_column'=>'invoices', 'multiple'=>3, 'jquery_multiple'=>1, 'style'=>'width: 750px; height: 65px;', 'select_options'=>$aInvoices)));
					}

					// Quittungen Kunde
					$aReceipts = $this->_oInquiry->getUniqueGroupDocuments('receipt_customer');
					$aReceipts = Ext_Thebing_Inquiry_Document::prepareDocumentsForSelect($aReceipts, 'version_id');
					if(!empty($aReceipts)) {
						$oCommunicationTab->setElement($oCommunicationDialog->createRow(L10N::t('Kunden Quittungen', self::$_sL10NDescription), 'select', array('db_column'=>'receipts_customer', 'multiple'=>3, 'jquery_multiple'=>1, 'style'=>'width: 750px; height: 65px;', 'select_options'=>$aReceipts)));
					}

					// Quittungen Agentur
					$aReceipts = $this->_oInquiry->getUniqueGroupDocuments('receipt_agency');
					$aReceipts = Ext_Thebing_Inquiry_Document::prepareDocumentsForSelect($aReceipts, 'version_id');
					if(!empty($aReceipts)) {
						$oCommunicationTab->setElement($oCommunicationDialog->createRow(L10N::t('Quittungen', self::$_sL10NDescription), 'select', array('db_column'=>'receipts_agency', 'multiple'=>3, 'jquery_multiple'=>1, 'style'=>'width: 750px; height: 65px;', 'select_options'=>$aReceipts)));
					}

					// Übersicht Zahlungen je Rechnung (Kunde + übersicht )
					$aReceipts = $this->_oInquiry->getDocuments(array('document_payment_customer', 'document_payment_overview_customer'));
					$aReceipts = Ext_Thebing_Inquiry_Document::prepareDocumentsForSelect($aReceipts, 'version_id');
					if(!empty($aReceipts)) {
						$oCommunicationTab->setElement($oCommunicationDialog->createRow(L10N::t('Kundenzahlungen je Rechnung', self::$_sL10NDescription), 'select', array('db_column'=>'document_payments_customer', 'multiple'=>3, 'jquery_multiple'=>1, 'style'=>'width: 750px; height: 65px;', 'select_options'=>$aReceipts)));
					}

					// Übersicht Zahlungen je Rechnung (Agentur + übersicht )
					$aReceipts = $this->_oInquiry->getDocuments(array('document_payment_agency', 'document_payment_overview_agency'));
					$aReceipts = Ext_Thebing_Inquiry_Document::prepareDocumentsForSelect($aReceipts, 'version_id');
					if(!empty($aReceipts)) {
						$oCommunicationTab->setElement($oCommunicationDialog->createRow(L10N::t('Agenturzahlungen je Rechnung', self::$_sL10NDescription), 'select', array('db_column'=>'document_payments_agency', 'multiple'=>3, 'jquery_multiple'=>1, 'style'=>'width: 750px; height: 65px;', 'select_options'=>$aReceipts)));
					}
					
					// Anhänge Examen
					$aDocuments = $this->_oInquiry->getDocuments('examination');
					$aDocuments = Ext_Thebing_Inquiry_Document::prepareDocumentsForSelect($aDocuments, 'version_id');
					if(!empty($aDocuments)) {
						$oCommunicationTab->setElement($oCommunicationDialog->createRow(L10N::t('Prüfungen', self::$_sL10NDescription), 'select', array('db_column'=>'documents', 'multiple'=>3, 'jquery_multiple'=>1, 'style'=>'width: 750px; height: 65px;', 'select_options'=>$aDocuments)));
					}

				}

				// Additional Documents
				if(
					$this->_bInquiryOtherAttachments &&
					!$this->_bMultiSelection && (
						$this->_oInquiry
					)
				) {
//					if($this->_oEnquiry) {
//						$aDocuments = $this->_oEnquiry->getDocuments('additional_document');
//						$sLabel = L10N::t('Dokumente der Anfrage', self::$_sL10NDescription);
//					} else {
						$aDocuments = $this->_oInquiry->getDocuments('additional_document');
						$sLabel = L10N::t('Dokumente der Buchung', self::$_sL10NDescription);
//					}

					$aDocuments = Ext_Thebing_Inquiry_Document::prepareDocumentsForSelect($aDocuments, 'version_id');
					if(!empty($aDocuments)) {
						$oCommunicationTab->setElement($oCommunicationDialog->createRow($sLabel, 'select', array('db_column'=>'documents', 'multiple'=>3, 'jquery_multiple'=>1, 'style'=>'width: 750px; height: 65px;', 'select_options'=>$aDocuments)));
					}
					
					/*
					 * Dateien der gebuchten Kurssprachen
					 * @todo Auslagern
					 */
					$courses = $this->_oInquiry->getCourses(true);
					$courseLanguages = [];
					foreach($courses as $course) {
						$courseLanguage = $course->getJoinedObject('course_language');
						$courseLanguages[$courseLanguage->id] = $courseLanguage;
					}
					
					$courseLanguagesFiles = [];
					foreach($courseLanguages as $courseLanguage) {
						$courseLanguagesFiles += $courseLanguage->getFiles('Communication');
					}

					if(!empty($courseLanguagesFiles)) {
						$courseLanguageFileOptions = [];
						foreach($courseLanguagesFiles as $courseLanguagesFile) {
							$courseLanguageFileOptions[$courseLanguagesFile->getUrl()] = $courseLanguagesFile->file;
						}
						// Nutzt "document_family", da Struktur allgemein für Dateien in Storage ist.
						$oCommunicationTab->setElement($oCommunicationDialog->createRow(L10N::t('Dateien der Kurssprachen', self::$_sL10NDescription), 'select', array('db_column'=>'document_family', 'multiple'=>3, 'jquery_multiple'=>1, 'style'=>'width: 750px; height: 65px;', 'select_options'=>$courseLanguageFileOptions)));
					}
					
				}

				// Anhänge Agentur Zahlungsübersicht
				if($this->_bAgencyOpenPaymentsAttachment) { 
					$aDocument = array('agency_payment_overview_fake_path' => L10N::t('Agentur Zahlungsübersicht', self::$_sL10NDescription));
					if(!empty($aDocument)) {
						$oCommunicationTab->setElement($oCommunicationDialog->createRow(L10N::t('Agentur Zahlungsübersicht', self::$_sL10NDescription), 'select', array('db_column'=>'agency_payment_overview', 'multiple'=>3, 'jquery_multiple'=>1, 'style'=>'width: 750px; height: 65px;', 'select_options'=>$aDocument)));
					}
				}

				// Familien Dokumente
				if(
					(
						$this->_bInquiryOtherAttachments &&
						!$this->_bMultiSelection &&
						$this->_oInquiry
					) || (
						$this->_bAccommodationAttachments
					)
				) {
					$aFamilieDocs = $this->_oInquiry->getAvailableFamilieDocuments($sLanguage);
					$aPictures = $this->_oInquiry->getFamiliePicturePdf();

					$aDocuments = (array)$aFamilieDocs + (array)$aPictures;

					if(!empty($aDocuments)) {
						$oCommunicationTab->setElement($oCommunicationDialog->createRow(L10N::t('Dokumente der Unterkunftsprovider', self::$_sL10NDescription), 'select', array('db_column'=>'document_family', 'multiple'=>3, 'jquery_multiple'=>1, 'style'=>'width: 750px; height: 65px;', 'select_options'=>$aDocuments, 'searchable'=>1)));
					}
				}

				// Schuldokumente
				if($this->_bSchoolAttachments) {
					$aSchoolFiles = $this->_oSchool->getSchoolFiles($this->_iSchoolAttachmentSection, $sLanguage);

					foreach((array)$aSchoolFiles as $iSchoolFile=>$aSchoolFile) {
						$aSchoolFiles[$iSchoolFile]['id'] = $aSchoolFile['id'];
					}
					$aSchoolFiles = Ext_Thebing_Util::convertArrayForSelect($aSchoolFiles, 'description', 'id');
		
					if(!empty($aSchoolFiles)) {
						$oCommunicationTab->setElement($oCommunicationDialog->createRow(L10N::t('Dokumente der Schule', self::$_sL10NDescription), 'select', array('db_column'=>'school_files', 'multiple'=>3, 'jquery_multiple'=>1, 'style'=>'width: 750px; height: 65px;', 'select_options'=>$aSchoolFiles, 'searchable'=>1)));
					}
				}
				
				// Angebotsdokumente
				if(
					$this->_bEnquiryAttachments &&
					!$this->_bMultiSelection
				) {
					$oSearch = new Ext_Thebing_Inquiry_Document_Search($this->_oInquiry->id);
					$oSearch->addJourneyDocuments();
					$oSearch->setType('offer');
					$aDocuments = $oSearch->searchDocument();
					$aDocuments = Ext_Thebing_Inquiry_Document::prepareDocumentsForSelect($aDocuments, 'version_id');
					if(!empty($aDocuments)) {
						$oCommunicationTab->setElement($oCommunicationDialog->createRow(L10N::t('Dokumente der Anfrage', self::$_sL10NDescription), 'select', array('db_column'=>'documents', 'multiple'=>3, 'jquery_multiple'=>1, 'style'=>'width: 750px; height: 65px;', 'select_options'=>$aDocuments, 'searchable'=>1)));
					}
				}

				// Vertragsdokumente
				if(
					$this->_bContractAttachments &&
					!$this->_bMultiSelection // #2792
				) {

					$aContractDocuments = $aAdditionalDocuments = [];
					foreach($this->_aItems as $oItem){
						if ($oItem instanceof \Ext_Thebing_Contract_Version) {
							$aContractDocuments[$oItem->id] = $oItem->getLabel();
						} else if ($oItem instanceof \Ext_Thebing_Inquiry_Document_Version) {
							$aAdditionalDocuments[$oItem->id] = $oItem->getLabel();
						}
					}

					if(!empty($aContractDocuments)) {
						$oCommunicationTab->setElement($oCommunicationDialog->createRow(L10N::t('Verträge', self::$_sL10NDescription), 'select', array('db_column'=>'contracts', 'multiple'=>3, 'jquery_multiple'=>1, 'style'=>'width: 750px; height: 65px;', 'select_options'=>$aContractDocuments, 'searchable'=>1)));
					}

					if(!empty($aAdditionalDocuments)) {
						$oCommunicationTab->setElement($oCommunicationDialog->createRow(L10N::t('Dokumente', self::$_sL10NDescription), 'select', array('db_column'=>'teacher_documents', 'multiple'=>3, 'jquery_multiple'=>1, 'style'=>'width: 750px; height: 65px;', 'select_options'=>$aAdditionalDocuments, 'searchable'=>1)));
					}

				}

				if(
					$this->_bCreditnoteAttachments &&
					!$this->_bMultiSelection &&
					is_object($this->_oAgency)
				) {
					$aCreditnotes = $this->_oAgency->getCreditnotesForSelect();
					/*
					 * Einträge herausfiltern zu denen keine Datei existiert (diese können sowieso nicht
					 * an die E-Mail angehangen werden) (#9658)
					 */
					foreach(array_keys($aCreditnotes) as $iCreditnoteVersionId) {
						$oVersion = Ext_Thebing_Accounting_Manual_Version::getInstance($iCreditnoteVersionId);
						// nur Dateien anhängen die einen Pfad gesetzt haben
						if(empty($oVersion->path)) {
							unset($aCreditnotes[$iCreditnoteVersionId]);
							continue;
						}
						$sVersionPath = $oVersion->getPath(true);
						// nur Dateien anhängen die wirklich existieren
						if(!is_file($sVersionPath)) {
							unset($aCreditnotes[$iCreditnoteVersionId]);
							continue;
						}
					}
					// Wenn Einträge vorhanden sind das Select-Feld generieren
					if(!empty($aCreditnotes)) {
						$oCommunicationTab->setElement($oCommunicationDialog->createRow(L10N::t('Creditnotes', self::$_sL10NDescription), 'select', array('db_column'=>'creditnotes', 'multiple'=>3, 'jquery_multiple'=>1, 'style'=>'width: 750px; height: 65px;', 'select_options'=>$aCreditnotes, 'searchable'=>1)));
					}
				}

				if(
					$this->bAccountingProviderAttachments &&
					!empty($this->aAccountingProviderAttachments)
				) {
					$aReceipts = [];
					if(count(array_unique($aSelectedIds)) === 1) {
						foreach($this->aAccountingProviderAttachments as $aAccountingProviderAttachments) {
							foreach($aAccountingProviderAttachments as $aAccountingProviderAttachment) {
								$aReceipts[$aAccountingProviderAttachment['path']] = $aAccountingProviderAttachment['basename'];
							}
						}
					} else {
						$aReceipts['multiple_document_attachment'] = $this->_oGui->t('Bezahlbelege');
					}

					if(!empty($aReceipts)) {
						$oCommunicationTab->setElement($oCommunicationDialog->createRow(L10N::t('Belege', self::$_sL10NDescription), 'select', array(
							'db_column' => 'accounting_provider',
							'multiple' => 3,
							'jquery_multiple' => 1,
							'style' => 'width: 750px; height: 65px;',
							'select_options' => $aReceipts,
							'searchable' => 1
						)));
					}

				}

			}

			// Anhänge die in der Mailvorlage gespeichert sind
			$aMailAttachments = $oTemplate->getAttachments($sLanguage);
			$aMailAttachments = Ext_Thebing_Util::convertArrayForSelect($aMailAttachments, 'attachment', 'id');
			if(!empty($aMailAttachments)){
				$oCommunicationTab->setElement($oCommunicationDialog->createRow(L10N::t('Vorlagenanhänge', self::$_sL10NDescription), 'select', array('db_column'=>'template_files', 'multiple'=>3, 'jquery_multiple'=>1, 'style'=>'width: 750px; height: 65px;', 'select_options'=>$aMailAttachments, 'searchable'=>1)));
			}
			
			$oUpload = new Ext_Gui2_Dialog_Upload(
				$this->_oGui,
				L10N::t('Uploads', self::$_sL10NDescription),
				$oCommunicationDialog,
				'attachments',
				'',
				$this->_oSchool->getSchoolFileDir(false).self::$_sFileDir,
				false,
				$aOptions = array(
					'style' => 'margin:0px;'
				)
			);
			$oUpload->multiple = 1;
			$oUpload->bShowSaveMessage = 0;
			$oCommunicationTab->setElement($oUpload);

			/**
			 * Markierungen
			 */
			$aFlags = self::getFlags($sApplication);
			$aAllFlags = self::getFlags();

			if($oTemplate->flags) {

				$oH3 = $oCommunicationDialog->create('h4');
				$oH3->setElement(L10N::t('Markierungen', self::$_sL10NDescription));
				$oCommunicationTab->setElement($oH3);


				foreach((array)$oTemplate->flags as $sFlag) {
					if(isset($aFlags[$sFlag])) {
						$oCommunicationTab->setElement($oCommunicationDialog->createRow($aFlags[$sFlag], 'checkbox', array('db_column'=>'flag', 'db_alias'=>$sFlag, 'default_value'=>1, 'checked'=>'checked')));
					} else {
						if(!isset($aAllFlags[$sFlag])) {
							Ext_Thebing_Util::reportError('Communication flag "'.$sFlag.'" is missing');
						}
					}
				}

			}

		}

		// Hidden Felder
		$oHidden		= $oCommunicationDialog->create('input');
		$oHidden->type	= 'hidden';
		$oHidden->id	= 'saveid[selected_ids]';
		$oHidden->name	= 'save[selected_ids]';
		$oHidden->value	= implode('_', $aOriginalSelectedIds);
		$oCommunicationTab->setElement($oHidden);

		$oHidden = $oCommunicationDialog->create('input');
		$oHidden->type = 'hidden';
		$oHidden->name = 'save[dialog_pass_data]';
		$oHidden->value	= htmlentities(json_encode($this->aDialogPassData));
		$oCommunicationTab->setElement($oHidden);

		$oCommunicationDialog->setElement($oCommunicationTab);

		// History Tab nicht bei Mehrfachkommunikation anzeigen
		if(
			Ext_Thebing_Access::hasRight('thebing_gui_email_history') &&
			!$this->_bMultiSelection
		) {
			
			$oHistoryPage = Ext_TC_Factory::executeStatic('Ext_TC_Communication_Gui2_Data', 'getPage', [true, $oCommunicationDialog->getDataObject()->getGui()]);
			
			$aHistoryPageElements = $oHistoryPage->getElements();
			$oHistoryPageGui = reset($aHistoryPageElements);

			$this->addRelationsToHistoryGui($oHistoryPageGui, $aSelectedIds, $aSelectedIdsAdditional, $sApplication);
			
//			$oHistoryGui = $this->_getHistoryHtml($aSelectedIds, $aSelectedIdsAdditional, $sApplication);
//			$oHistoryPage = Ext_TC_Communication_Gui2_Data::buildPage($oHistoryGui);
			$oHistoryTab->setElement($oHistoryPage);
			$oCommunicationDialog->setElement($oHistoryTab);
		}

		$oCommunicationDialog->save_button = false;

		$aButton = array(
			'label'			=> L10N::t('E-Mail absenden', self::$_sL10NDescription),
			'task'			=> 'saveDialog',
			'action'		=> 'communication',
			'additional'	=> $sApplication
		);
		$oCommunicationDialog->aButtons = array($aButton);

		return $oCommunicationDialog;

	}

	public static function saveDialogData($oGui, $aSelectedIds, $aData, $aVars) {
		
		$oDummyDialog = new Ext_Gui2_Dialog();

		$oCommunication = new self($oDummyDialog, $aSelectedIds);
		$oCommunication->_oGui = $oGui;

		return $oCommunication->_saveDialog($aSelectedIds, $aData, $aVars);
	}

	/**
	 * Sendet die E-Mail ab
	 * @Todo: $_VARS mit $aVars austauschen!
	 *
	 * @param array $aSelectedIds
	 * @param array $aData
	 * @param array $aVars
	 * @return array
	 * @throws Exception
	 */
	public function _saveDialog($aSelectedIds, $aData, $aVars) {
		global $user_data, $_VARS;

		//weil die dialog_id immer sortiert wird, müssen wir hier auch sortieren wegen den uploads
		//@todo gui_data task "upload" umschreiben, nicht mit $iSelectedId arbeiten sondern mit der reinen
		//dialog_id, abchecken ob bei openDialog die SESSION richtig geleert wird...
		sort($aSelectedIds);
	
		$aOriginalSelectedIds = $aSelectedIds;
		$this->_aOriginalSelectedIds = $aOriginalSelectedIds;
		$iOriginalSelectedId = reset($aSelectedIds);

		$sApplication = $aData['additional'];

		$aDecoded = $this->_convertSelectedIds($aSelectedIds, $sApplication);
		$aSelectedIds = $aDecoded['decoded_ids'];

		// Falls mehrere Applications über ein Icon gesteuert werden müssen
		$sApplicationTemp = $this->_switchApplication($sApplication, $aSelectedIds);
		if($sApplicationTemp != $sApplication){
			$sApplication = $sApplicationTemp;
			$aDecoded = $this->_convertSelectedIds($aOriginalSelectedIds, $sApplication);
			$aSelectedIds = $aDecoded['decoded_ids'];
		}

		/**
		 * Flags vorbereiten
		 */
		$aFlags = array();
		foreach((array)($aVars['save']['flag'] ?? []) as $sKey=>$iValue) {
			if($iValue == 1) {
				$aFlags[] = $sKey;
			}
		}
		
		$aErrors = array();
		$iTab = 0;

		$aSelectedIds = (array)$aSelectedIds;
		$aSelectedIdsAdditional = $aDecoded['additional'];

		if(count($aSelectedIds) > 1) {
			$this->_bMultiSelection = true;
		} else {
			$this->_bMultiSelection = false;
		}

		$this->_getApplicationSettings($sApplication, $aSelectedIds);

		$this->aDialogPassData = json_decode($aVars['save']['dialog_pass_data'], true);
		if(!is_array($this->aDialogPassData)) {
			throw new \RuntimeException('dialog_pass_data is not an array');
		}

		/**
		 * Prüfen, ob alle Vorlagen ausgewählt wurden
		 */
		$bTemplates = true;
		$aTemplates = array();
		foreach((array)$this->_aTemplateOptions as $sKey=>$aOptions) {
			if(
				!is_numeric($aVars['save']['template_id'][$sKey]) ||
				$aVars['save']['template_id'][$sKey] == 0
			) {

				$bTemplates = false;
			} else {

				$aTemplates[$sKey] = new Ext_Thebing_Email_Template($aVars['save']['template_id'][$sKey]);
			}
		}

		$oUserSender = Ext_Thebing_User::getInstance($aVars['save']['identity_id']);
		if(!$oUserSender->exist()) {
			// Fallback, sollte aber eigentlich nicht vorkommen
			$oUserSender = System::getCurrentUser();
		}

		// Eingaben überprüfen und E-Mails vorbereiten
		if(!$bTemplates) {
			$aErrors[] = L10N::t('Bitte wählen Sie eine Vorlage aus.', self::$_sL10NDescription);
		} else {

			$oMailContent = $this->_getDefaultMailContent($_VARS, new stdClass());
			// Inhalt der E-Mail darf nicht leer sein
			$sMailContent = trim($oMailContent->content);
			if(empty($sMailContent)) {
				$aErrors[] = L10N::t('Der Inhalt der E-Mail ist leer.', self::$_sL10NDescription);
			}

			// Empfänger vorbereiten
			$aRecipients['to'] = $this->decodeRecipients($aVars['save']['to']);
			$aRecipients['cc'] = $this->decodeRecipients($aVars['save']['cc']);
			$aRecipients['bcc']	= $this->decodeRecipients($aVars['save']['bcc']);

			if(
				empty($aRecipients['to']) ||
				empty($aRecipients['to'][0])
			) {
				$aErrors[] = L10N::t('Bitte wählen Sie mindestens einen Empfänger aus.', self::$_sL10NDescription);
			}

			// Wenn bis hierhin noch kein Fehler aufgetreten ist
			if(empty($aErrors)) {

				// Objekte ermitteln
				$aObjects = array();
				$aObjectRecipients = array();
				$aGlobalRecipients = array();
				$bGrossCustomer = false;
				$bNetDocument = false;

				/*
				 * to,cc,bcc durchlaufen
				 *
				 * Sollte ein normaler Kunde bzw. eine manuelle E-Mail
				 * Adresse vorhanden sein, wird $bGrossCustomer auf true gesetzt,
				 * da bei Versendung von evtl. Netto-Dokumenten eine Meldung kommen soll.
				 */ 
				foreach((array)$this->_aRecipientInputs as $sKey=>$sValue) {

					/* @var $oItem Ext_Thebing_Communication_RecipientDTO */
					foreach((array)$aRecipients[$sKey] as $oItem) {

						if($this->_sSendTo == 'selection') {
							
							if(
								$oItem->sObject &&
								$oItem->iObjectId > 0 &&
								$sKey == 'to'
							) {
								foreach((array)$oItem->aSelectedIds as $iId) {
									$aObjects[$oItem->sObject][$iId] = clone $oItem;
									$aObjects[$oItem->sObject][$iId]->aSelectedIds = array($iId);
									$aObjectRecipients[$oItem->sObject][$iId][$sKey][] = $oItem;
								}
							} else {
								$bGrossCustomer = true;
								$aGlobalRecipients[$sKey][] = $oItem;
							}

						} else {

							if(
								$oItem->sObject &&
								$oItem->iObjectId > 0 &&
								$sKey == 'to'
							) {
								$aObjects[$oItem->sObject][$oItem->iObjectId] = clone $oItem;
								$aObjectRecipients[$oItem->sObject][$oItem->iObjectId][$sKey][] = $oItem;
							} else {
								$bGrossCustomer = true;
								$aGlobalRecipients[$sKey][] = $oItem;
							}

						}

						if($oItem->sObject == 'Ext_TS_Inquiry_Contact_Traveller') {
							$bGrossCustomer = true;
						}

					}

				}

				/*
				 * Sollte in der Schuleinstellung die Einstellung 'Nettodokumente an Nicht-Agentur Adresse'
				 * auf 'Warnung nur auf Hauptempfängerfeld beziehen' gestellt sein, darf nur das Hauptempfängerfeld
				 * beachtet werden.
				 */
				if($this->_oSchool->net_email_warning == 'main_reception_field') {
					if(
						!isset($aGlobalRecipients['to']) && // Input (manuell und kein Objekt)
						!isset($aObjectRecipients['Ext_TS_Inquiry_Contact_Traveller']) // Select (Objekt)
					) {
						$bGrossCustomer = false;
					}
				}
				/*
				 * Sollte in der Schuleinstellung die Einstellung 'Nettodokumente an Nicht-Agentur Adresse'
				 * auf 'Warnung nicht anzeigen, wenn Empfänger der Versender ist' gestellt sein, darf als
				 * Empfänger nur der Sender eingetragen sein, damit die Warnung unterdrückt wird.
				 */
				elseif($this->_oSchool->net_email_warning == 'recipient_is_sender') {
					foreach($aGlobalRecipients as $aRecipientTypes) {
						foreach($aRecipientTypes as $oRecipient) {
							if($oUserSender->email == $oRecipient->sEmail) {
								$bGrossCustomer = false;
							} else {
								$bGrossCustomer = true;
								break 2;
							}
						}
					}
				}

				if($bGrossCustomer) {
					// Hier wird überprüft, ob Netto Dokumente versendet werden
					$aInvoices = (array)($_VARS['save']['invoices'] ?? []);
					foreach($aInvoices as $iDocumentVersionId) {
						$oInvoice = Ext_Thebing_Inquiry_Document_Version::getInstance((int)$iDocumentVersionId)->getDocument();
						if(
							$oInvoice->isNetto() ||
							strpos($oInvoice->type, 'creditnote') !== false
						) {
							$bNetDocument = true;
							break;
						}
					}
					if(!empty($_VARS['save']['document_payments_agency'])) {
						$bNetDocument = true;
					}
				}

				if(
					(
						/*
						 * Sollte in der Schuleinstellung die Einstellung 'Nettodokumente an Nicht-Agentur Adresse'
						 * auf 'Warnung immer anzeigen' oder 'Warnung nur auf Hauptempfängerfeld beziehen' (und es nicht nur Agenturen als
						 * Hauptempfänger geben sollte) stehen, muss die Meldung angezeigt werden.
						 *
						 */
							$this->_oSchool->net_email_warning == 'always' ||
						$this->_oSchool->net_email_warning == 'main_reception_field'
					) &&
					$bGrossCustomer &&
					$bNetDocument &&
					$_VARS['ignore_errors'] != 1
				) {
					$aTransfer['action'] = 'saveDialogCallback';
					$aTransfer['data']['show_skip_errors_checkbox'] = 1;
					$aTransfer['data']['action'] = 'communication';
					$aTransfer['error'][] = array(
						'type' => 'hint',
						'message' => L10N::t('Die E-Mail beinhaltet Netto Dokumente, der Adressat entspricht aber nicht der Agentur. Möchten Sie diese wirklich versenden?', self::$_sL10NDescription),
						'hintMessage' => L10N::t('Ja, ich bin mir sicher!', self::$_sL10NDescription)
					);
					return $aTransfer;
				}

				// Array mit Mail Informationen
				$aEmails = array();

				if(empty($aObjects)) {

					/**
					 * TODO: Was hier passiert sehe ich selber! - Aber WARUM soll das so sein???? mf
					 * Falls kein Objekt vorhanden ist, an alle Objekte versenden
					 */
					foreach((array)$aSelectedIds as $iKey => $iId) {
						$oObject = $this->_getObjectFromApplication($sApplication, $iId, $aSelectedIdsAdditional[$iKey]);
						$oRecipient = new Ext_Thebing_Communication_RecipientDTO;
						$oRecipient->aSelectedIds = [$iId];
						if (
							// TODO bei Lehrer-/Transfer-/Unterkunftszahlungen wird die original_id aus aAdditional gebraucht, wenn
							// man hier aber einen Empfänger manuell einträgt kommt ein Fehler. Ich habe das erstmal nur auf
							// die beiden Bereiche beschränkt da hier die Fehler gemeldet wurden. Evtl. macht es aber auch woanders noch Sinn
							in_array($sApplication, ['accounting_teacher', 'accounting_accommodation', 'accounting_transfer']) &&
							!empty($aSelectedIdsAdditional[$iKey])
						) {
							$oRecipient->iObjectId = $iId;
							$oRecipient->aAdditional = $aSelectedIdsAdditional[$iKey];
						}
						$aObjects[get_class($oObject)][$iId] = $oRecipient;
					}

				}

				/**
				 * E-Mails vorbereiten
				 * 1. Prüfen, ob Objekt vorhanden ist
				 * 2. Pro Object eine E-Mail
				 * 3. Empfänger pro E-Mail festlegen
				 * 4. Wenn kein Objekt, prüfen ob Inhalt übergeben wurde -> ggf. Fehler zurückgeben
				 * 5. Wenn kein Inhalt übergeben wurde und Objekt vorhanden ist, Inhalt aus Template holen und Platzhalter ersetzen
				 */
				// Schleife der Empfänger Typen
				foreach((array)$aObjects as $sObject => $aItems) {

					// Schleife der Empfängerobjekte eines Typs
					/* @var $mValue Ext_Thebing_Communication_RecipientDTO */
					foreach((array)$aItems as $iId => $mValue) {

						$iRecipientId = (int)$mValue->iObjectId;	# Id des Empfänger Objectes
						$sRecipient	= $mValue->sObject;			# Object des Empfängers
						$aSelectedIdsTemp = $mValue->aSelectedIds;		# Ausgewählte Einträge dieses Empfängers
						$aSelectedIdsAdditionalConcrete = $mValue->aAdditional;

						$oObject = self::_getObjectFromApplication($sObject, $iId);

						if ($mValue->hasValue('sLanguage')) {
							$sLanguage = $mValue->sLanguage;
						} else {
							// Eigentlich müsste der Fallback hier nicht nötig sein, aber zur Sicherheit drinne
							$sLanguage = $this->_getLanguageFromObject($oObject, $sApplication);
						}

						#$sLanguage = $this->getManualRecipientLanguage($_VARS);
						
						$aEmailRecipients = array_merge_recursive((array)$aObjectRecipients[$sObject][$iId], $aGlobalRecipients);

						// Empfänger sollen nur einmal vorkommen
						foreach($aEmailRecipients as $sEmailRecipientKey => $aEmailRecipient) {
							$aEmailRecipients[$sEmailRecipientKey] = array_unique($aEmailRecipients[$sEmailRecipientKey]);
						}

						// Mailinhalt
						$oMailContent = new stdClass();

						if(
							isset($aVars['save']['subject']) &&
							isset($aVars['save']['content'])
						) {

							// All Templates durchgehen und das passende für en aktuellen Empfängertyp zu ermitteln
							foreach((array)$aVars['save']['subject'] as $sTemplateid => $sValue){

								$aTemp = explode('_', $sTemplateid);
								$iTemplateid = (int)$aTemp[1];
						
								if($iTemplateid <= 0) {
									continue;
								}
								
								$oTempTemplate = Ext_Thebing_Email_Template::getInstance($iTemplateid);

								$sSubject = $sValue;
								$sContent = $aVars['save']['content'][$sTemplateid];
								$sSignature = $aVars['save']['signature'][$sTemplateid];
								$sTemplateCategory	= $aVars['save']['template_category'][$sTemplateid];
		
								if(!$this->_checkTemplateType($sObject, $sTemplateCategory, $sApplication)){
									continue;
								}

								if(empty($sSubject)) {
									$aErrors[] = L10N::t('Bitte geben Sie einen Betreff ein.', self::$_sL10NDescription);
								}

								if(empty($sContent)) {
									$aErrors[] = L10N::t('Bitte geben Sie einen Inhalt ein.', self::$_sL10NDescription);
								}

								// Platzhalter ersetzen?
								$bReplacePlaceholder = false;

//								if(
//									!$this->_bReplacePlaceholderInDialog ||
//									$this->_bMultiSelection
//								) {
//									$bReplacePlaceholder = true;
//								}

								$oMailContent->subject = $sSubject;
								$oMailContent->content = $sContent;
								$oMailContent->signature = $sSignature;
								$oMailContent->template	= $oTempTemplate;
//								$oMailContent->replace = (bool)$bReplacePlaceholder;

								break;

							}

						}

						/*
						 * Wenn kein Template gefunden wurde (manuell hinzugefügte Adressen) dann immer an
						 * das 1. beste schicken
						 */
						if(!($oMailContent->template instanceof Ext_Thebing_Email_Template)) {
							$oMailContent = $this->_getDefaultMailContent($aVars, $oMailContent);
						}

						// Individuelle Anhänge für diesen Empfänger
						$aAttachments = $this->getIndividualAttachments($sRecipient, $iRecipientId, $aSelectedIds, $aVars);

						// Jetzt JEDEN Empfänger durchgehen der eine Mail bekommen soll
						// Hier muss entschieden werden, ob jeder Listeneintrag kommuniziert wird ODER
						// Das Empfängerobjekt alle Infos der Listeneinträge bekommt
						$aSelectedIdsTemp = $this->checkCommunicationDirection($sApplication, $sObject, $aSelectedIdsTemp);

						// Mailinhalte speichern damit sie für jeden Empfänger individuell ersetzt werden können
						$sTempContent = $oMailContent->content;
						$sTempSubject = $oMailContent->subject;

						foreach((array)$aSelectedIdsTemp as $iTempSelectedId){

							$oRecipient = clone $mValue;
							
							// ID des selektierten Hauptobjekts wird für Platzhalter gebraucht
							$oRecipient->iSelectedId = $iTempSelectedId;

							// Alle dekodierten Werte der GUI separat setzen, wird für die Platzhalter gebraucht
							// TODO Wenn man zwei mal den gleichen Schüler auswählt, stehen hier in beiden Fällen die gleichen Daten drin (also falsch)
							$oRecipient->aDecodedData = $aDecoded['decoded_data'][$iTempSelectedId];

							/*
							 * In das Platzhalter-Objekt wird das GUI-Objekt gepackt, in welches wiederum die IDs gesetzt werden. 
							 * Bei jedem Durchlauf (jede E-Mail) werden diese IDs überschrieben, daher muss beim Ersetzen das ganze wiederholt werden.
							 */
							$oPlaceholder = $this->_getPlaceholderObject($sObject, $oRecipient, $sApplication, $iTempSelectedId, $aSelectedIdsAdditionalConcrete);

							if(!empty($this->aDialogPassData['master_lang'])){
								// Sprache des Templates setzen, falls es nicht der Kundensprache enspricht
								$oPlaceholder->sTemplateLanguage = $this->aDialogPassData['master_lang'];
							}

							if($oPlaceholder instanceof Ext_TC_Placeholder_Abstract) {
								$oPlaceholder->setType('communication');
								$oPlaceholder->setCommunicationSender($oUserSender);
							}
							// wenn zum Kunden Kommuniziert wird, spezial Platzhalter ersetzen
							/*if(
								$this->_oInquiry instanceof Ext_TS_Inquiry && (
									$oObject instanceof Ext_TS_Inquiry_Contact_Abstract ||
									$oObject instanceof Ext_Thebing_Agency_Contact || // Auch Agenturadressen dürfen Logindaten wissen
									$oObject instanceof Ext_TS_Inquiry
								)
							) {

								// Mehrfachkommunikation: Falls Platzhalter noch nicht ersetzt wurden
								if(strpos($oMailContent->content, '{user_password}') !== false) {
									$oMailContent->content = str_replace('{user_password}', '[user_password]', $oMailContent->content);
								}

								// Fallback, falls beim Versand kein Kontakt ausgewählt wurde und hier die Buchung in der Variablen steht
								if($oObject instanceof Ext_TS_Inquiry) {
									$oObject = $oObject->getTraveller();
								}

								// Login-Daten des Kontakts holen (und generieren, wenn noch nicht vorhanden)
								if($oObject instanceof Ext_Thebing_Agency_Contact) {
									$oCustomer = $this->_oInquiry->getCustomer();
								} else {
									$oCustomer = $oObject;
								}

								self::replaceUserPasswordPlaceholder($oMailContent->content, $oCustomer, $oPlaceholder);

							}*/


							// EmpfängerInfos
							if(empty($aErrors)) {

								$sKey = $sObject.'_'.(int)$iId;

								// Hier bekommt das Empfängerobjekt bei Mehrfachauswahl mehrere E-Mails
								// Ansonsten würde der Key jedes mal überschrieben werden
								// Beispielsweise bekommt ein ein Agenturkontakt pro ausgewählten Schüler eine E-Mail
								if(
									$sObject === 'Ext_Thebing_Agency_Contact' || // Agenturkontakt
									$sObject === 'Ext_TS_Inquiry_Group_ContactPerson' // Ansprechpartner einer Gruppe
								) {
									if(!isset($iCountMails)) {
										$iCountMails = 0;
									}
									$sKey .= '_'.$iCountMails++;
								}

								$aEmail = array(
									'subject' => $oMailContent->subject,
									'content' => $oMailContent->content,
									'signature' => $oMailContent->signature,
									'recipients' => $aEmailRecipients,
									'object' => $sObject,
									'object_id' => (int)$iId,
									'selected_id' => (array)$oRecipient->aSelectedIds,
									'language' => $sLanguage,
									'template' => $oMailContent->template,
									'application' => $sApplication,
									'attachments' => $aAttachments,
									'selected_id_single' => $oRecipient->iSelectedId, // Bsp. Inquiry-ID
									'decoded_data' => $oRecipient->aDecodedData,
									'placeholder' => $oPlaceholder
								);

								$aEmails[$sKey] = $aEmail;

							}

						}

					}

				}

			}

		}

		/*
		 * Durchläuft jede E-Mail und führt weitere Flags aus
		 * Hier wird auch die Signatur angehangen und die Platzhalter ersetzt (immer).
		 */
		if(!empty($aEmails)) {
			foreach($aEmails as &$aEmail) {
				
				// Signatur an den Inhalt anhängen
				$sContentSignature = self::getMailContentSignature($aEmail['signature']);

				self::setLayoutAndSignature($aEmail['template'], $aEmail['language'], $aEmail['content'], $sContentSignature);

				// Platzhalter immer ersetzen, da auch im Layout noch Platzhalter drin sein können
				if(is_object($aEmail['placeholder'])) {
					$aEmail['subject'] = $aEmail['placeholder']->replace($aEmail['subject']);
					$content = $aEmail['placeholder']->replace($aEmail['content']);
					if ($aEmail['placeholder'] instanceof \Ext_Thebing_Inquiry_Placeholder) {
						$content = $aEmail['placeholder']->replaceFinalOutput($content);
					}
					$aEmail['content'] = $content;
				}

				if ($aEmail['placeholder'] instanceof Ext_TC_Placeholder_Abstract) {
					$aErrors = array_merge($aErrors, \Illuminate\Support\Arr::flatten($aEmail['placeholder']->getErrors()));
				}
				
				self::_prepareFlags($aFlags, $aEmail, $aErrors, $this->_oSchool);

			}
		}

		unset($aEmail);
		
		// Bei Fehler wieder zurück auf die Maske springen
		if(!empty($aErrors)) {

			$sError = L10N::t('Die E-Mail konnte nicht verschickt werden.', self::$_sL10NDescription);
			array_unshift($aErrors, $sError);

		} else {

			// Anhänge vorbereiten
			$aAttachments = array();
			$aUploads = array();

			$aUploadFileData = array();
			$sColumn = 'attachments';

			// Datei Informationen für den Upload vorbereiten vorbereiten
			$aFileNames = (array)$_FILES['save']['name'][$sColumn];
			$aFileTmpNames = (array)$_FILES['save']['tmp_name'][$sColumn];
			foreach($aFileNames as $iFileArrayId => $sFileName) {
				if(!empty($sFileName)) {
					$aUploadFileData[] = new \Illuminate\Http\UploadedFile($aFileTmpNames[$iFileArrayId], $sFileName);
				}
			}

			// Die Options müssen hier definiert werden,
		    // da hier kein Dialog-Objekt zur Verfügung steht
			$aOptionValues = array(
				'upload_path' => $this->_oSchool->getSchoolFileDir(false).self::$_sFileDir,
				'no_path_check' => 1,
				'add_column_data_filename' => 0,
				'add_id_filename' => 0
			);

			$oUpload = new \Gui2\Handler\Upload($aUploadFileData, $aOptionValues, true);
			$oUpload->setColumn($sColumn);
			$aMovedUploadFiles = $oUpload->handle();
			
			foreach($aMovedUploadFiles as $sMovedUploadFile) {
				$sFullMovedUploadFile = \Util::getDocumentRoot(false) . $aOptionValues['upload_path'] . $sMovedUploadFile;
				$aUploads[$sFullMovedUploadFile] = $sMovedUploadFile;
			}

			foreach((array)($aVars['save']['document_family'] ?? []) as $sPath) {
				$aInfo = pathinfo($sPath);
				$sPath = Util::getDocumentRoot().$sPath;
				$aUploads[$sPath] = $aInfo['basename'];
			}

			$aStudentDataFiles = array();
			// XLS mit Schülern für Transfer und Unterkunft
			if(isset($aVars['save']['document_students']) && $aVars['save']['document_students'] == 1) { //soll erst beim Absenden Dateien anhängen!
				$sStudentsFile = $this->aDialogPassData['document_students'];
				$aInfo = pathinfo($sStudentsFile);  //basename ? filename ?
				$sPath = $sStudentsFile;  ///var/www/vhosts/dev.thebing.com/httpdocs
				$aStudentDataFiles[$sPath] = $aInfo['basename'];
			}

			$aDocuments = array();
			$aDocuments['inquiry'] = array_merge(
				(array)($aVars['save']['documents'] ?? []),
				(array)($aVars['save']['invoices'] ?? []),
				(array)($aVars['save']['receipts_customer'] ?? []),
				(array)($aVars['save']['receipts_agency'] ?? []),
				(array)($aVars['save']['document_payments_customer'] ?? []),
				(array)($aVars['save']['document_payments_agency'] ?? [])
			);

			$aDocumentObjects = array();
			foreach((array)$aDocuments['inquiry'] as $iKey => $iVersion) {
				if($iVersion > 0) {
					$oVersion = Ext_Thebing_Inquiry_Document_Version::getInstance($iVersion);
					if($oVersion->document_id > 0) {
						$aDocumentObjects['inquiry'][$iVersion] = $oVersion;
						$aAttachments[$oVersion->getPath(true)] = 1;
					}
				}
			}

			// Schuldateien
			$aSchoolFiles = array();
			foreach((array)($aVars['save']['school_files'] ?? []) as $iFileId) {
				$oFile = Ext_Thebing_Upload_File::getInstance($iFileId);
				$sPath = $oFile->getPath();
				$aPaths = pathinfo($sPath);
				$sFileName = $oFile->description . '.' . $aPaths['extension'];
				$aSchoolFiles[$oFile->getPath()] = $sFileName;
			}

			// Creditnotes
			$aCreditnotesFiles = array();
			foreach((array)($aVars['save']['creditnotes'] ?? []) as $iCreditnoteVersionId) {
				$oVersion = Ext_Thebing_Accounting_Manual_Version::getInstance($iCreditnoteVersionId);
				// nur Dateien anhängen die einen Pfad gesetzt haben (#9658)
				if(empty($oVersion->path)) {
					continue;
				}
				$sVersionPath = $oVersion->getPath(true);
				// nur Dateien anhängen die wirklich existieren (#9658)
				if(!is_file($sVersionPath)) {
					continue;
				}
				$aCreditnotesFiles[$sVersionPath] = basename($sVersionPath);
			}

			// Verträge
			$aTeacherFiles = array();
			$aDocuments['contract'] = array();
			$aDocuments['teacher_documents'] = array();
			foreach((array)($aVars['save']['contracts'] ?? []) as $iVersionId) {
				$oVersion = Ext_Thebing_Contract_Version::getInstance($iVersionId);
				$aDocuments['contract'][] = (int)$iVersionId;
				$sPath = $oVersion->file;
				$aInfo = pathinfo($sPath);
				$sPath = $oVersion->getPath(true);
				$aTeacherFiles[$sPath] = $aInfo['basename'];
			}
			foreach((array)($aVars['save']['teacher_documents'] ?? []) as $iVersionId) {
				$oVersion = Ext_Thebing_Inquiry_Document_Version::getInstance($iVersionId);
				$aDocuments['teacher_documents'][] = (int)$iVersionId;
				$aInfo = pathinfo($oVersion->path);
				$sPath = $oVersion->getPath(true);
				$aTeacherFiles[$sPath] = $aInfo['basename'];
			}

			// Attachments der Vorlage
			$aMailTemplateFiles = array();
			foreach((array)($aVars['save']['template_files'] ?? []) as $iFileId) {
				$oAttachment = Ext_Thebing_Email_Template_Attachment::getInstance($iFileId);
				$aMailTemplateFiles[$oAttachment->getPath()] = $oAttachment->attachment;
			}

			// Attachments von Providerbezahlungen
			$aAccountingProviderFiles = array();
			foreach((array)($aVars['save']['accounting_provider'] ?? []) as $sPath) {
				$aAccountingProviderFileInfo = pathinfo($sPath);
				$aAccountingProviderFiles[$sPath] = $aAccountingProviderFileInfo['basename'];
			}

			$aAttachments = array_merge($aUploads, $aAttachments, $aSchoolFiles, $aTeacherFiles, $aMailTemplateFiles, $aStudentDataFiles, $aCreditnotesFiles, $aAccountingProviderFiles);

			if(
				// Meldungen ausgeben falls Anhänge zusammengefasst worden sind
				// und globale Empfänger vorhanden sind, oder ein Empfänger kein Anhang hat
				in_array('multiple_document_attachment', $aAttachments) &&
				$_VARS['ignore_errors'] != 1
			) {

				if(!empty($aGlobalRecipients)) {
					$aTransfer['action'] = 'saveDialogCallback';
					$aTransfer['data']['show_skip_errors_checkbox'] = 1;
					$aTransfer['data']['action'] = 'communication';
					$aTransfer['error'][] = array(
						'type' => 'hint',
						'message' => L10N::t('Es sind Anhänge ausgewählt und manuelle Empfänger eingetragen. Die manuellen Empfänger werden alle E-Mails und alle Belege erhalten da eine Zuordnung nicht möglich ist. Bitte überdenken Sie ihre Auswahl.', self::$_sL10NDescription),
						'hintMessage' => L10N::t('Ja, ich bin mir sicher!', self::$_sL10NDescription)
					);
					return $aTransfer;
				}

				foreach((array)$aEmails as $aEmail) {
					if(empty($aEmail['attachments'])) {
						$aTransfer['action'] = 'saveDialogCallback';
						$aTransfer['data']['show_skip_errors_checkbox'] = 1;
						$aTransfer['data']['action'] = 'communication';
						$aTransfer['error'][] = array(
							'type' => 'hint',
							'message' => L10N::t('Es sind Anhänge ausgewählt und für mindestens einen der gewählten Empfänger ist kein Anhang verfügbar. Der Empfänger wird keinen Anhang erhalten.', self::$_sL10NDescription),
							'hintMessage' => L10N::t('Ja, ich bin mir sicher!', self::$_sL10NDescription)
						);
						return $aTransfer;
					}
				}

			}

			// Anhänge die als Anhänge geloggt werden sollen - können beliebig erweiterbar werden…
			$aAttachmentsLog = array_merge($aUploads, $aMailTemplateFiles, $aStudentDataFiles, $aCreditnotesFiles, $aAccountingProviderFiles);
			$aAttachmentsLogOriginal = $aAttachmentsLog;

			// Attachments prüfen
			foreach((array)$aAttachments as $sFile => $sValue) {
				if(!is_file($sFile)) {
					unset($aAttachments[$sFile]);
					unset($aAttachmentsLog[$sFile]);
					unset($aAttachmentsLogOriginal[$sFile]);
				}
			}

			## START Schleife für Empfänger
			foreach((array)$aEmails as $iEmail => $aEmail) {

				$oTemplate = $aEmail['template'];
				$sLanguage = $aEmail['language'];
				$sSubject = $aEmail['subject'];
				$sContent = $aEmail['content'];
				$sSignature	= $aEmail['signature'];
				$aRecipients = $aEmail['recipients'];
				$iObjectId = $aEmail['object_id'];
				$aSelectedIds = $aEmail['selected_id'];

				$sCommunicationCode = null;
				
				// Mail-Code Platzhalter ersetzen
				if(
					strpos($sSubject, '[#]') !== false || 
					strpos($sContent, '[#]') !== false
				) {					
					$sCommunicationCode = Ext_TC_Communication::generateCode();
					$sCommunicationCodeTag = '[#'.$sCommunicationCode.']';
					$sSubject = str_replace('[#]', $sCommunicationCodeTag, $sSubject);
					$sContent = str_replace('[#]', $sCommunicationCodeTag, $sContent);
				}

				// E-Mail versenden
				$oMail = new WDMail();
				$oMail->subject = $sSubject;

				if($oTemplate->html) {
					$oMail->html = $sContent;
				} else {
					$oMail->text = $sContent;
				}

				if(!empty($aRecipients['cc'])) {
					$oMail->cc = array_map(function(Ext_Thebing_Communication_RecipientDTO $oRecipient) {
						return $oRecipient->sEmail;
					}, $aRecipients['cc']);
				}
				if(!empty($aRecipients['bcc'])) {
					$oMail->bcc = array_map(function(Ext_Thebing_Communication_RecipientDTO $oRecipient) {
						return $oRecipient->sEmail;
					}, $aRecipients['bcc']);
				}

				// Globale- und Empfänger-spezifische Attachments zusammenfügen
				$aAttachmentsLog = $aAttachmentsLogOriginal;
				$aAttachmentsFinal = $aAttachments;
				foreach((array)$aEmail['attachments'] as $sPath) {
					$aAttachmentsFinal[$sPath] = 1;
					// Das Log um das Empfänger-spezifische Attachment erweitern
					$aAttachmentsLog[$sPath] = basename($sPath);
				}

				if(!empty($aAttachmentsFinal)) {
					$oMail->attachments = $aAttachmentsFinal;
				}

				// Absenden
				Ext_Thebing_Mail::$oSchool = $this->_oSchool;
				$oMail->from_user = $oUserSender;

				$aRecipientsTo = array_map(function(Ext_Thebing_Communication_RecipientDTO $oRecipient) {
					return $oRecipient->sEmail;
				}, $aRecipients['to']);

				$bSuccess = $oMail->send($aRecipientsTo);

				if(!$bSuccess) {
					$aErrors[] = L10N::t('Der E-Mail-Server konnte die Nachricht nicht versenden.', self::$_sL10NDescription);
				} else {

					// DocumentRoot darf nicht gespeichert werden, könnte sich ja ändern
					$aAttachmentsLog = Ext_Thebing_Util::stripDocumentRoot($aAttachmentsLog, true);
					$aSchoolFiles = Ext_Thebing_Util::stripDocumentRoot($aSchoolFiles, true);

					$aRelations = [];
					
					// Versand protokollieren
					$oLog = new Ext_TC_Communication_Message();
					$oLog->date = date('Y-m-d H:i:s');
					$oLog->direction = 'out';
					if($oTemplate->html) {
						$oLog->content_type = 'html';
					} else {
						$oLog->content_type = 'text';
					}

					if(!empty($sCommunicationCode)) {
						$oLog->codes = [$sCommunicationCode];
					}

					$aRelations[] = [
						'relation' => $aEmail['object'],
						'relation_id' => $iObjectId
					];
					$oLog->creator_id = $user_data['id'];

					$oSender = $oMail->sender_object;
					
					if(!empty($oSender)) {

						$aRelations[] = ['relation' => get_class($oSender), 'relation_id' => $oSender->id];

						$oSenderLog = $oLog->getJoinedObjectChild('addresses');
						$oSenderLog->type = 'from';
						$oSenderLog->address = $oSender->email;
						$oSenderLog->name = $oSender->sFromName;
						if($oSender->id > 0) {
							$oSenderLog->relations = 
							[
								[
									'relation' => get_class($oSender),
									'relation_id' => $oSender->id
								]
							];
						}
						
					} else {
						// Muss mal ausprobiert werden, da der Account eigentlich immer da sein muss
						throw new RuntimeException('No sender for communication mail!');
					}

					$oLog->relations = $aRelations;
					
					$oLogTemplate = $oLog->getJoinedObjectChild('templates');
					$oLogTemplate->template_id = $oTemplate->id;

					foreach($aRecipients as $sType=>$aRecipientAddresses) {
						foreach($aRecipientAddresses as $oRecipientAddress) {
							$oRecipient = $oLog->getJoinedObjectChild('addresses');
							$oRecipient->type = $sType;
							$oRecipient->address = $oRecipientAddress->sEmail;
							$oRecipient->name = $oRecipientAddress->getCleanName();
							if($oRecipientAddress->iObjectId > 0) {
								$oRecipient->relations = [[
									'relation' => $oRecipientAddress->sObject,
									'relation_id' => $oRecipientAddress->iObjectId
								]];
							}
						}
					}

					$oLog->subject = (string)$sSubject;
					$oLog->content = (string)$sContent;
					
					foreach($aFlags as $sFlag) {
						$oFlag = $oLog->getJoinedObjectChild('flags');
						$oFlag->flag = $sFlag;
					}
					
					/**
					 * Folgender Abschnitt mit den Dateien ist kacke, aber das muss eh auf TC-Communication umgestellt werden
					 */
					$aFiles = [];
					
					if(!empty($aDocuments['inquiry'])) {
						foreach($aDocuments['inquiry'] as $iVersionId) {
							$oVersion = Ext_Thebing_Inquiry_Document_Version::getInstance($iVersionId);
							$aFiles[] = [
								'path' => '/storage'.$oVersion->path,
								'relation' => 'Ext_Thebing_Inquiry_Document_Version',
								'relation_id' => $iVersionId
							];
						}
					}

					if(!empty($aDocuments['contract'])) {
						foreach($aDocuments['contract'] as $iVersionId) {
							$oVersion = Ext_Thebing_Contract_Version::getInstance($iVersionId);
							$aFiles[] = [
								'path' => $oVersion->file,
								'relation' => 'Ext_Thebing_Contract_Version',
								'relation_id' => $iVersionId
							];
						}
					}

					if(!empty($aDocuments['teacher_documents'])) {
						foreach($aDocuments['teacher_documents'] as $iVersionId) {
							$oVersion = Ext_Thebing_Inquiry_Document_Version::getInstance($iVersionId);
							$aFiles[] = [
								'path' => '/storage'.$oVersion->path,
								'relation' => \Ext_Thebing_Inquiry_Document_Version::class,
								'relation_id' => $iVersionId
							];
						}
					}
					
					if(!empty($aSchoolFiles)) {
						foreach($aSchoolFiles as $sPath=>$sName) {
							$aFiles[] = [
								'path' => $sPath,
								'name' => $sName
							];
						}
					}

					if(!empty($aAttachmentsLog)) {
						foreach($aAttachmentsLog as $sPath=>$sName) {
							$aFiles[] = [
								'path' => $sPath,
								'name' => $sName
							];
						}
					}

					foreach($aFiles as $aFile) {

						if(empty($aFile['name'])) {
							$aPathInfo = pathinfo($aFile['path']);
							$aFile['name'] = $aPathInfo['basename'];
						}
					
						$oFile = $oLog->getJoinedObjectChild('files');
						$oFile->file = $aFile['path'];
						$oFile->name = $aFile['name'];
				
						if(!empty($aFile['relation'])) {
							$oFile->relations = [
								[
									'relation' => $aFile['relation'],
									'relation_id' => $aFile['relation_id']
								]
							];
						}

					}

					if($oMail->message instanceof \Symfony\Component\Mailer\SentMessage) {
						$oLog->imap_message_id = $oMail->message->getMessageId();
						$oLog->unseen = 0;
						if(!empty($oSender)) {
							$oLog->account_id = $oSender->id;
						}
					}

					$oLog->save();

					// Markierungen speichern
					$this->_setFlags($aFlags, $sApplication, $aEmail, $oLog->id);

					// Objektrelationen speichern
					$this->setLogRelations($oLog->id, $aSelectedIds, $aSelectedIdsAdditional, $sApplication);

					// Versenden von Inquiry Dokumenten speichern
					foreach((array)($aDocumentObjects['inquiry'] ?? []) as $oDocumentVersion) {
						$oDocumentVersion->sent = time();
						$oDocumentVersion->save();
					}
					
					// Mastersprache löchen
					unset($this->aDialogPassData['master_lang']);

				}

			}

			if(empty($aErrors)) {

				// Variablen zurücksetzen
				$_VARS['save']['template_id'] = 0;

				if($this->_bMultiSelection) {
					$iTab = false;
				} else {
					$iTab = 1;
				}

				// Dialog neu holen mit den zurückgesetzten Einstellungen
				$aData = $this->_oGui->getDataObject()->prepareOpenDialog('communication', $aOriginalSelectedIds, false, $sApplication);

				$this->aDialogPassData = [];

			}

		}

		$aTransfer = array();
		$aTransfer['action'] = 'saveDialogCallback';
		$aTransfer['dialog_id_tag'] = self::$_sIdTag;
		$aTransfer['success_message'] = L10N::t('Die E-Mail wurde erfolgreich verschickt.', self::$_sL10NDescription);
		$aTransfer['error'] = $aErrors;
		$aTransfer['data'] = $aData;
		$aTransfer['tab'] = $iTab;

		return $aTransfer;
	}

	/**
	 * Prüft ob ein Templatetype für ein Empfängerobject gedacht ist
	 *
	 * @param string $sObject
	 * @param string $sTemplateType
	 * @param string $sApplication
	 * @return bool
	 */
	protected function _checkTemplateType($sObject, $sTemplateType, $sApplication){

		// Manuell eingetragene Mails müssen in diesen beiden Listen an das Kunden Template gehen
		if(
			(
				$sApplication == 'accommodation_communication_customer_agency' &&
				$sObject == 'Ext_Thebing_Accommodation_Allocation'
			) || (
				$sApplication == 'transfer_customer_agency_information' &&
				$sObject == 'Ext_Thebing_Inquiry_Transfer'	
			)
		) {
			return true;
		} elseif(
			(
				$sApplication == 'accommodation_communication_agency' &&
				$sObject == 'Ext_Thebing_Accommodation_Allocation'
			) || (
				$sApplication == 'transfer_customer_agency_information' &&
				$sObject == 'Ext_Thebing_Inquiry_Transfer'	
			)
		) {
			return true;
		}

		$bReturn = true;
		switch($sTemplateType) {
			case 'customer':
				if(
					$sObject != 'Ext_TS_Inquiry_Contact_Traveller' &&
					$sObject != 'Ext_TS_Inquiry' &&
					$sObject != 'Ext_Thebing_Accommodation_Allocation' // Historie Unterk. Kommunikation
				) {
					$bReturn = false;
				}                    
				break;
			case 'agency':           
				if(                  
					$sObject != 'Ext_Thebing_Agency' &&
					$sObject != 'Ext_Thebing_Agency_Contact'
				) {
					$bReturn = false;
				}                    
				break;               
			default:                 
				$bReturn = true;
				break;
		}                            
                                     
		return $bReturn;             
	}

	/**
	 * @param $oObject
	 * @param string $sApplication
	 * @return string
	 * @throws Exception
	 */
	protected function _getLanguageFromObject($oObject, $sApplication) {

		switch($sApplication) {
			// TODO Kann man den Fall einfach rausnehmen oder welche Objekte kommen hier noch rein?
			case 'accommodation_communication_customer_agency':
			case 'insurance_customer':

				if(
					$oObject instanceof \TsCompany\Entity\Contact ||
					$oObject instanceof Ext_TS_Inquiry_Contact_Traveller ||
					$oObject instanceof Ext_TS_Inquiry_Group_ContactPerson
				) {
					$sLanguage = $oObject->getLanguage();
				} else {
					$sLanguage = $oObject->getInquiry()->getLanguage();
				}
				break;
			case 'activity':
				if ($oObject instanceof Ext_Thebing_Accommodation) {
					return $this->_oSchool->getLanguage();
				}
				return $oObject->getLanguage();
			case 'transfer_provider_request':
			case 'transfer_provider_confirm':
			case 'transfer_customer_accommodation_information':
			case 'insurance_provider':
			case 'accommodation_communication_provider':
			case 'accommodation_resources_provider':
			case 'accounting_accommodation':
			case 'accounting_transfer':
			case 'accommodation_communication_history_accommodation_canceled':
			case 'contract_accommodation':
				$sLanguage = $this->_oSchool->getLanguage();
				break;
			default:
				if (
					empty($oObject) || //$sApplication kann leer sein, wodurch $sObject leer ist
					$oObject instanceof Ext_Thebing_User
				) { // User bzw. Salesperson hat keine Language
					$sLanguage = $this->_oSchool->getLanguage();
				} else {
					$sLanguage = $oObject->getLanguage();
				}
				break;
		}
		
		return $sLanguage;
	}

	public static function getObjectFromApplication($sApplication, $iObjectId=null, $aAdditional=null) {
		return self::_getObjectFromApplication($sApplication, $iObjectId, $aAdditional);
	}
	
	/**                              
	 * »Universalmethode« für Holen des Mappings, Factory für WDBasic-Klassen und Factory für Applikation => WDBasic
	 *
	 * @TODO $aAdditional sollte Pflicht werden
	 *
	 * @param string $sApplication
	 * @param int $iObjectId
	 * @param array $aAdditional
	 */                              
	protected static function _getObjectFromApplication($sApplication, $iObjectId=null, $aAdditional=null) {

		switch($sApplication) {      
			case 'insurance_provider':
			case 'insurance_customer':
				$sObject = 'Ext_TS_Inquiry_Journey_Insurance';
				break;               
			case 'transfer_provider_request':
			case 'transfer_provider_confirm':
			case 'transfer_customer_accommodation_information':
			case 'transfer_customer_agency_information':
				$sObject = 'Ext_TS_Inquiry_Journey_Transfer';
				break;               
			case 'marketing_agencies':
				$sObject = 'Ext_Thebing_Agency';
				break;
			/*case 'marketing_companies':
				$sObject = \TsCompany\Entity\Company::class;
				break;*/
			case 'agencies_payments':
				$sObject = 'Ext_Thebing_Agency_Payment';
				break;               
			case 'marketing_agencies_contact':
				$sObject = 'Ext_Thebing_Agency_Contact';
				break;               
			case 'contract_teacher': 
			case 'contract_accommodation':
				$sObject = 'Ext_Thebing_Contract_Version';
				break;  
			case 'tuition_teacher':
			case 'accounting_teacher':
				$sObject = 'Ext_Thebing_Teacher';
				break;
			case 'simple_view':
			case 'arrival_list':
			case 'departure_list':
			case 'client_payment':
			case 'tuition_attendance':
			case 'placement_test':
			case 'agency_payment':
			case 'customer':
			case 'inbox':
			case 'feedback_list':
			case 'visum_list':
			case 'enquiry':
				$sObject = 'Ext_TS_Inquiry';
				break;               
			case 'accommodation_communication_customer_agency':
			case 'accommodation_communication_agency':
			case 'accommodation_communication_provider':
			// START Unterkunftskommunikation Historie
				case 'accommodation_communication_history_accommodation_canceled':
				case 'accommodation_communication_history_customer_canceled':
				case 'accommodation_communication_history_agency_canceled':
				case 'accommodation_communication_history_accommodation_confirmed':
				case 'accommodation_communication_history_customer_confirmed':
				case 'accommodation_communication_history_agency_confirmed':
			// ENDE
				$sObject = 'Ext_Thebing_Accommodation_Allocation';
				break; 
			case 'accommodation_communication_provider_requests':
				$sObject = TsAccommodation\Entity\Request\Recipient::class;
				break;
//			case 'enquiry':
//				$sObject = 'Ext_TS_Enquiry';
//				break;
			case 'accommodation_resources_provider':
			case 'accounting_accommodation':
				$sObject = 'Ext_Thebing_Accommodation';
				break;
			case 'accounting_transfer':
				if(
					// Da dies hier ja eine »Universalmethode« ist, darf bei Mapping-Rückgabe keine Exception geworfen werden
					$iObjectId !== null &&
					empty($aAdditional['provider_type'])
				) {
					throw new BadMethodCallException('Missing $aAdditional for application accounting_transfer');
				}

				if($aAdditional['provider_type'] === 'accommodation') {
					$sObject = 'Ext_Thebing_Accommodation';
				} else {
					$sObject = 'Ext_Thebing_Pickup_Company';
				}

				break;
			case 'activity':
				$sObject = '\TsActivities\Entity\Activity\BlockTraveller';
				break;
			default:
				// Hier kommen die Klassennamen direkt rein
				$sObject = $sApplication;
				break;               
		}                            

		if(
			$iObjectId !== null &&
			!empty($sObject)
		) {    
			 $oObject = call_user_func(array($sObject, 'getInstance'), (int)$iObjectId);
			 return $oObject;        
		}                            
                                     
		return $sObject;             
	}

	/**
	 * Returns GUI2 HTML Code
	 *
	 * @param array $aSelectedIds
	 * @param array $aSelectedIdsAdditional
	 * @param string $sApplication
	 * @param bool $bGlobal
	 * @return Ext_Gui2
	 */
	public function addRelationsToHistoryGui(Ext_Thebing_Gui2_Communication $oHistoryGui, $aSelectedIds, $aSelectedIdsAdditional, $sApplication, $bGlobal = false) {
                                     
		$iSelectedId = reset($aSelectedIds);
                               
		$oObject = null;
		if(!$bGlobal) {
			$oObject = self::_getObjectFromApplication($sApplication, $iSelectedId, $aSelectedIdsAdditional[key($aSelectedIds)]);
			$oHistoryGui->setMainObject($iSelectedId, get_class($oObject));
		}

		// Prüfen ob Application die History über die relationstabelle bezieht
		if(                          
			// Transferkommunikation 
			$sApplication  == 'transfer_provider_request' ||
			$sApplication  == 'transfer_customer_accommodation_information' ||
			$sApplication  == 'transfer_provider_confirm' ||
			$sApplication  == 'transfer_customer_agency_information' ||
			// Unterkunftskommunikation
			$sApplication  == 'accommodation_communication_customer_agency' ||
			// Unterkunfstkommunikation - History
			$sApplication  == 'accommodation_communication_history_customer_canceled' ||
			$sApplication  == 'accommodation_communication_history_customer_confirmed' ||
			$sApplication  == 'accommodation_communication_history_accommodation_canceled' ||
			$sApplication  == 'accommodation_communication_history_accommodation_confirmed' ||
			// inbox                 
			$sApplication  == 'inbox'
		){                           
			$bUseRelationsTableConnection = true;
		}else{                       
			$bUseRelationsTableConnection = false;
		}                            
                                     
		if($bUseRelationsTableConnection){
			$oHistoryGui->setApplication($sApplication);
		}                            
                                     
		// Bei der Agenturkommunikation werden alle Logs von allen Kontakten angezeigt
		/*                           
		if($sApplication  == 'marketing_agencies') {
			$oAgency = Ext_Thebing_Agency::getInstance($iSelectedId);
			$aContacts = $oAgency->getContacts(true);
			$aContacts = array_keys($aContacts);
			$oHistoryGui->setTableData('where', array('object'=>'Ext_Thebing_Agency_Contact', 'object_id'=>array('IN', (array)$aContacts)));
		} else if($sApplication  == 'agencies_payments') {
			$oPayment = Ext_Thebing_Agency_Payment::getInstance($iSelectedId);
			$oAgency = Ext_Thebing_Agency::getInstance($oPayment->agency_id);
			$aContacts = $oAgency->getContacts(true);
			$aContacts = array_keys($aContacts);
			$oHistoryGui->setTableData('where', array('object'=>'Ext_Thebing_Agency_Contact', 'object_id'=>array('IN', (array)$aContacts)));
		} else if(                   
			$bUseRelationsTableConnection
		){                           
			// Wird über die Relationstabelle in der Data Klasse gesteuert
			$oHistoryGui->setTableData('where', array('kel.application'=>$sApplication));
		} else {                     
			$oHistoryGui->setTableData('where', array('object'=>self::_getObjectFromApplication($sApplication), 'object_id'=>(int)$iSelectedId));
		}*/    
		

	 // Hier können zu den Bereits angezeigten Log Einträgen, optional noch weitere
		// Logs angezeigt werden     
		if($oObject instanceof Ext_Thebing_Accommodation_Allocation){
			$oInquiry = $oObject->getInquiry();
			$oHistoryGui->setMainObject($oInquiry->id, get_class($oInquiry));
                                     
			// Mails aus Transferkommunikation
			$aInquiryTransfers = $oInquiry->getTransfers();
                                     
			foreach((array)$aInquiryTransfers as $oTransfer) {
				$oHistoryGui->setRelationObject($oTransfer->id, get_class($oTransfer));
			}

			// Es soll die History aller Unterkunftsanbieter (auch gelöschte Zuweisungen) angezeigt werden
			$aAllocations = Ext_Thebing_Allocation::getAllocationByInquiryId($oInquiry->id, 0, true, false, true, true);
			foreach($aAllocations as $oAllocation) {
				$oHistoryGui->setRelationObject($oAllocation->id, get_class($oAllocation));
			}
                                     
		}elseif($oObject instanceof Ext_TS_Inquiry_Journey_Transfer){
			$oInquiry = $oObject->getInquiry();
			$oHistoryGui->setMainObject($oInquiry->id, get_class($oInquiry));
                                     
			// Mails aus Unterkunftskommunikation ---------------------------------------------------
			$aInquiryAccommodations	= $oInquiry->getAccommodations(true, true);
                                     
			// Zuweisungen der Inquiry bestimmen
			$aAllAllocations = array();
			foreach((array)$aInquiryAccommodations as $oInquiryAccommodation){
				$aAllocations = $oInquiryAccommodation->getAllocations();
				foreach((array)$aAllocations as $oAllocation){
					$aAllAllocations[] = $oAllocation;
				}                    
			}                        
                                     
			foreach((array)$aAllAllocations as $oAllocation){
				$oHistoryGui->setRelationObject($oAllocation->id, get_class($oAllocation));
			}                        
                                     
		}elseif($oObject instanceof Ext_TS_Inquiry){
			
			$this->_addRelationsToInquiry($oObject, $oHistoryGui);
                                     
		}elseif(                     
			$oObject instanceof Ext_Thebing_Agency ||
			$oObject instanceof Ext_Thebing_Agency_Payment
		){                           
			if($oObject instanceof Ext_Thebing_Agency){
				$oAgency = $oObject; 
			}else{                   
				$oAgency = $oObject->getAgency();
			}                        
			$aContacts = $oAgency->getContacts(true);
			$aContacts = array_keys($aContacts);
			$oHistoryGui->setMainObject($aContacts,'Ext_Thebing_Agency_Contact');
		} /*elseif(
			$oObject instanceof Ext_TS_Enquiry	
		){
			if($oObject->isConvertedToInquiry())
			{
				$aInquiries = $oObject->inquiries;
				
				foreach($aInquiries as $iInquiryId)
				{
					$oHistoryGui->setRelationObject($iInquiryId, 'Ext_TS_Inquiry');
					
					$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);
					$this->_addRelationsToInquiry($oInquiry, $oHistoryGui);
				}
			}
		}*/ elseif(
			$oObject instanceof Ext_Thebing_Teacher ||
			$oObject instanceof Ext_Thebing_Accommodation
		) {
			$aGroupings = $oObject->getPayedPaymentGroupings();
			foreach($aGroupings as $oGrouping) {
				$oHistoryGui->setRelationObject($oGrouping->id, get_class($oGrouping));
			}
		}

	}
                                     
	/**                              
	 * Bereitet aus einem String die Empfänger vor
	 *
	 * @param string $sEmails
	 */                              
	protected function decodeRecipients($sEmails) {

		$sEmails = strip_tags($sEmails, '<span>');

		preg_match_all("/<span.*?title=\"(.*?)\".*?>(.*?)<\/span>/i", $sEmails, $aMatches);

		$aRecipients = [];
		
		foreach((array)$aMatches[0] as $iKey=>$sMatch) {
			
			$aItem = $this->aDialogPassData['recipients'][$aMatches[1][$iKey]];
			
			$oItem = new Ext_Thebing_Communication_RecipientDTO;
			
			$oItem->sObject = $aItem['object'];
            $oItem->iObjectId = $aItem['object_id'];
            $oItem->sEmail = $aItem['email'];
            $oItem->sLanguage = $aItem['language'];
            $oItem->sName = $aItem['name'];
            $oItem->aAdditional = $aItem['additional'];
            $oItem->aSelectedIds = $aItem['selected_id'];

			$aRecipients[] = $oItem;
			$sEmails = str_replace($sMatch, '', $sEmails);
		}

		$aParts = \Ext_Thebing_Mail::splitEmails($sEmails);

		foreach((array)$aParts as $sPart) {
			$sPart = trim($sPart);   
			if(Util::checkEmailMx($sPart)) {
				$oItem = new Ext_Thebing_Communication_RecipientDTO;
				$oItem->sEmail = $sPart;
				$aRecipients[] = $oItem;
			}                        
		}

		return $aRecipients;
	}

	/**
	 * Methode speichert die Objectrelationen der gesendeten Mails
	 *
	 * @TODO Auf JoinTable relations umstellen!
	 *
	 * @param int $iLogId
	 * @param array $aSelectedIds
	 * @param array $aSelectedIdsAdditional
	 * @param string $sApplication
	 */
	protected function setLogRelations($iLogId, $aSelectedIds, $aSelectedIdsAdditional, $sApplication){
                                     
		#$aSelectedIds = $this->_convertSelectedIds($aSelectedIds, $sApplication);
		#$aSelectedIds = $aSelectedIds['decoded_ids'];

		foreach((array)$aSelectedIds as $iKey => $iSelectedId){
                                 		
			$oObject = self::_getObjectFromApplication($sApplication, $iSelectedId, $aSelectedIdsAdditional[$iKey]);
			$sObject = get_class($oObject);

			$aRelation = [
				'message_id' => $iLogId,
				'relation_id' => $iSelectedId,
				'relation' => $sObject

			];

			DB::insertData('tc_communication_messages_relations', $aRelation, true, true);

			// Zusätzlich speichern, damit die Mails in der Historie der Buchung auftauchen
			if($sApplication === 'activity') {

				/** @var \TsActivities\Entity\Activity\BlockTraveller $oObject */
				$oInquiry = $oObject->getInquiry();
				
				$aRelation = [
					'message_id' => $iLogId,
					'relation_id' => $oInquiry->id,
					'relation' => 'Ext_TS_Inquiry'
				];

				DB::insertData('tc_communication_messages_relations', $aRelation, true, true);

			}

			// Spalten: Datum und Benutzer der letzten Nachricht
			if($sObject === 'Ext_TS_Inquiry') {
				Ext_Gui2_Index_Stack::add('ts_inquiry', $iSelectedId, 0);
			}

		}

	}

	/**
	 * Dient dafür, dass vor dem setzen der Makierung
	 * noch Dinge ausgeführt werden können
	 *
	 * @TODO Man sollte sich hier generell etwas komplett Neues überlegen, da das total fehleranfällig ist (andere IDs reinkopieren und Objekte fehlen)
	 * @TODO Redundanz entfernen (hier wurde einfach der ganze Block kopiert)
	 *
	 * @param array $aFlags
	 * @param array $aEmail
	 * @param array $aErrors
	 * @param Ext_Thebing_School $oSchool
	 * @param $oObject
	 */
	public static function _prepareFlags($aFlags, &$aEmail, &$aErrors, Ext_Thebing_School $oSchool, $oInquiry = null) {

		// Es können zwei Dinge auftreten die zu einem Fehler führen müssen:
		// 1. Wenn Makierung vorhanden und der Platzhalter fehlt
		// 2. Wenn Markierung fehlt und der Platzhalter vorhanden ist
		if(!in_array('inquiry_feedback_invited', $aFlags) && strpos($aEmail['content'], '[FEEDBACKLINK') !== false) {
			$aErrors[] = L10N::t('Es ist ein Makierungs-Platzhalter vorhanden, allerdings keine Makierung aktiv!', self::$_sL10NDescription);
		}
		else if(in_array('inquiry_feedback_invited', $aFlags)) {
			if(strpos($aEmail['content'], '[FEEDBACKLINK') === false) {
				$aErrors[] = L10N::t('Es ist eine Makierung aktiv, allerdings kein Makierungs-Platzhalter vorhanden!', self::$_sL10NDescription);
			}
			/** Fragebogen versendet */
			if(empty($aErrors)) {
				preg_match_all('/(?=\[FEEDBACKLINK:(\d+):(\d+)\])/', $aEmail['content'], $aPlaceholderIds, PREG_SET_ORDER);
				foreach($aPlaceholderIds as $aPlaceholder) {
					/** @var $oQuestionary Ext_TC_Marketing_Feedback_Questionary */
					$oQuestionary = Factory::getInstance('Ext_TC_Marketing_Feedback_Questionary', $aPlaceholder[1]);
					$bCheckSubobjectValid = $oQuestionary->checkSubObjectsByJourneyId($aPlaceholder[2]);
					// @TODO Ich will nichts kaputt machen, aber hier muss eigentlich doch genau so nach der Schule abgefragt
					// werden? (Wert müsste in den Platzhalter geschrieben werden vorher)
					if(!$bCheckSubobjectValid) {
						$aErrors[] = L10N::t('Die Einstellungen des Fragebogens stimmen nicht mit den gebuchten Leistungen überein!', self::$_sL10NDescription);
						break;
					}
				}
				if(empty($aErrors)) {
					foreach($aPlaceholderIds as $aPlaceholder) {
						if(empty($aEmail['selected_id_single'])) {
							// Ohne Objekt (manueller Empfänger) funktioniert hier nichts
							$aErrors[] = L10N::t('Für den Fragebogen steht keine Buchung zur Verfügung. Bitte prüfen Sie, ob Sie einen Empfänger ausgewählt haben.', self::$_sL10NDescription);
							continue;
						}

						if($oInquiry === null) {
							$oInquiry = self::_getObjectFromApplication($aEmail['application'], $aEmail['selected_id_single']);
						}
						if(!$oInquiry instanceof Ext_TS_Inquiry) {
							// Wenn hier mal keine Inquiry zurückkommen sollte, weiß man hier auch nicht mehr weiter
							throw new RuntimeException('Feedback placeholder: Expected inquiry object but got '.get_class($oInquiry).'!');
						}

						// Da Kunden lustig den Feedback-Platzhalter mit IDs kopieren können, können die Kunden ohne Prüfung ziemlichen Müll in der Datenbank erzeugen #10125
						$aJourneyIds = array_map(function(Ext_TS_Inquiry_Journey $oJourney) {
							return $oJourney->id;
						}, $oInquiry->getJourneys());
						if(!in_array($aPlaceholder[2], $aJourneyIds)) {
							$aErrors[] = L10N::t('Der Platzhalter für den Fragebogen ist für diese Buchung nicht gültig.', self::$_sL10NDescription);
						}
						// TODO: Wird in manchen Fällen (keine Ahnung welche Bedingung) doppelt aufgerufen
						// (zwei Einträge pro E-Mail in der Datenbank)
						$oFeedbackProcess = new Ext_TS_Marketing_Feedback_Questionary_Process();
						$oFeedbackProcess->active = 0;
						$oFeedbackProcess->contact_id = $aEmail['object_id']; // Achtung, das muss nicht der Bucher sein (Agenturkontakt z.B.)
						$oFeedbackProcess->journey_id = $aPlaceholder[2];
						$oFeedbackProcess->invited = time();
						$oFeedbackProcess->link_key = $oFeedbackProcess->getUniqueKey();
						$oFeedbackProcess->questionary_id = $aPlaceholder[1];
						$oFeedbackProcess->email = $aEmail['recipients']['to'][0];

						if(
							empty($aErrors) &&
							$oFeedbackProcess->validate()
						) {
							$oFeedbackProcess->save();
							$sFeedbackUrl = $oSchool->url_feedback;
							if(strpos($sFeedbackUrl, '?') === false) {
								$sFeedbackUrl .= '?r=';
							} else {
								$sFeedbackUrl .= '&r=';
							}
							$aEmail['content'] = str_replace('[FEEDBACKLINK:'.$aPlaceholder[1].':'.$aPlaceholder[2].']', $sFeedbackUrl . $oFeedbackProcess->link_key, $aEmail['content']);
							$aEmail['processId'][] = $oFeedbackProcess->id;
						}

					}
				}
			}
		}

		// Es können beim Placementtest nur die Fehler auftreten die auch beim Feedback auftreten können.
		if(!in_array('inquiry_placementtest_invited', $aFlags) && strpos($aEmail['content'], '[PLACEMENTTEST:') !== false) {

			$aErrors[] = L10N::t('Es ist ein Makierungs-Platzhalter vorhanden, allerdings keine Makierung aktiv!', self::$_sL10NDescription);

		} else if(in_array('inquiry_placementtest_invited', $aFlags)) {

			if(strpos($aEmail['content'], '[PLACEMENTTEST:') === false) {
				$aErrors[] = L10N::t('Es ist eine Makierung aktiv, allerdings kein Makierungs-Platzhalter vorhanden!', self::$_sL10NDescription);
			}

			if(empty($aErrors)) {

				preg_match_all('/\[PLACEMENTTEST:(\d+):(\d+)\]/', $aEmail['content'], $aPlaceholderIds, PREG_SET_ORDER);

				foreach($aPlaceholderIds as $aPlaceholder) {

					$inquiryId = $aPlaceholder[2];

					$courseLanguageId = $aPlaceholder[1];

                    if($inquiryId !== null) {

                        $sLinkKey = '';
                        $dNow = new \DateTime();

						$oPlacementTestResult = Ext_Thebing_Placementtests_Results::getResultByInquiryAndCourseLanguage($inquiryId, $courseLanguageId);

						$placementtest = \TsTuition\Entity\Placementtest::getPlacementtestByCourseLanguage($courseLanguageId);
						$courseLanguageName = Ext_Thebing_Tuition_LevelGroup::getInstance($courseLanguageId)->getName();

						$courseLanguageErrorMessage = sprintf(L10N::t('Es existiert kein Einstufungstest für "%s".', self::$_sL10NDescription), $courseLanguageName);

						if($oPlacementTestResult === null) {

                            $oPlacementTestResult = new Ext_Thebing_Placementtests_Results();
                            $sLinkKey = $oPlacementTestResult->getUniqueKey();

							if ($placementtest->id != 0) {
								$oPlacementTestResult->active = 1;
								$oPlacementTestResult->inquiry_id = $inquiryId;
								$oPlacementTestResult->invited = $dNow->format('Y-m-d H:i:s');
								$oPlacementTestResult->key = $sLinkKey;
								$oPlacementTestResult->level_id = 0;
								$oPlacementTestResult->placementtest_date = '0000-00-00';
								$oPlacementTestResult->placementtest_id = $placementtest->id;
								$oPlacementTestResult->courselanguage_id = $courseLanguageId;
								$oPlacementTestResult->save();
							} else {
								// Bei alten PlacementtestResults, bei denen die Buchung neu eingeladen wird, kommt man hier nicht rein
								// wegen der Abfrage oben, damals gab es diesen Error aber noch nicht, also könnte es sein, dass
								// es Einladungen gibt mit Links zu Placementtests ohne Einträgen
								$aErrors[$courseLanguageId] = $courseLanguageErrorMessage;
							}

                        } else {

                            if($oPlacementTestResult->isAnswered()) {
								$inquiry = Ext_TS_Inquiry::getInstance($inquiryId);

                                $aErrors[$courseLanguageId] = sprintf(
									L10N::t('Der Einstufungstest für "%s" wurde von "%s" bereits ausgefüllt.', self::$_sL10NDescription),
									$courseLanguageName,
									$inquiry->getTraveller()->getName()
								);
                            } else {

								if ($placementtest->id == 0) {
									$aErrors[$courseLanguageId] = $courseLanguageErrorMessage;
								} else {

									$sLinkKey = $oPlacementTestResult->key;

									// TODO: Das müsste irgendwann entfernt werden
									if (empty($sLinkKey)) {
										$sLinkKey = $oPlacementTestResult->getUniqueKey();
										$oPlacementTestResult->key = $sLinkKey;
									}

									$oPlacementTestResult->invited = $dNow->format('Y-m-d H:i:s');
									$oPlacementTestResult->save();
								}
                            }

                        }

						if (in_array('inquiry_placementtest_halloai', $aFlags)) {
							try {
								$halloAiApi = new HalloAiApiService();
								$result = $halloAiApi->getAssessmentUrl($oPlacementTestResult);
								$sPlacementTestUrl = $result['assessmentUrl'];
							} catch(Exception $e) {
								$aErrors[] = L10N::t('Es ist ein Fehler in der Hallo.ai Api aufgetreten:', self::$_sL10NDescription)." ".$e->getMessage();
							}
						} else {
							$sPlacementTestUrl = $oSchool->url_placementtest;
							if (strpos($sPlacementTestUrl, '?') === false) {
								$sPlacementTestUrl .= '?r=';
							} else {
								$sPlacementTestUrl .= '&r=';
							}
							$sPlacementTestUrl .= $sLinkKey;
						}

                        if(empty($aErrors)) {
                            $aEmail['content'] = str_replace('[PLACEMENTTEST:'.$courseLanguageId.':'.$inquiryId.']', $sPlacementTestUrl, $aEmail['content']);
                        }

                    }
                }

			}

		}

		if(
			in_array('attendance_warning', $aFlags) &&
			empty($aEmail['decoded_data']['inquiry_journey_course_id_communication'])
		) {
			// Entweder gibt es die ID nicht (IF im Query für Anwesenheit-Buchungsansicht) oder es wurde kein Empfänger ausgewählt (kein Objekt)
			$aErrors[] = L10N::t('Für die ausgewählte Markierung steht keine Kursbuchung zur Verfügung. Bitte prüfen Sie die Ansicht der Liste und ob Sie einen Empfänger ausgewählt haben.', self::$_sL10NDescription);
		}

	}

	/**                              
	 * Methode setzt die Flags in den entsprechenden Tabellen
	 * @param array $aFlags
	 * @param string $sApplication
	 * @param array $aEmail
	 * @param int $iLogId
	 * @param $oObject
	 */                              
	public static function _setFlags($aFlags, $sApplication, $aEmail, $iLogId, $oObject = null) {
		
		global $user_data;

		if($oObject === null) {
			$oObject = self::_getObjectFromApplication($aEmail['object'], $aEmail['object_id']);
		}

		foreach((array)$aFlags as $sFlag) {
                                     
			switch($sFlag) {         
				case 'arrival_requested';
					if($oObject instanceof Ext_TS_Inquiry){
						$oObject->tspInfoRequestSent = time();
						$oObject->save();
					}                
					break;        
				case 'accommodation_confirmed_agency';
					if($oObject instanceof Ext_TS_Inquiry){
						$oObject->agencyAccInfoSent = time();
						$oObject->save();
					}                
					break;           
				case 'contract_sent';
					
					foreach((array)$aEmail['selected_id'] as $iContractVersionId){
						$oContractVersion = Ext_Thebing_Contract_Version::getInstance($iContractVersionId);
						$oContractVersion->sent = time();
						$oContractVersion->sent_by = (int)$user_data['id'];
						$oContractVersion->save(false);

						$oContract = $oContractVersion->getContract();
						$oContract->last_sent_version_id = $oContractVersion->id;
						$oContract->save();
					}              
					break;           
				case 'payment_reminder':

					if($oObject instanceof Ext_TS_Inquiry) {
						// Muss auch irgendwie mit Cronjob-E-Mails funktionieren
						$oInquiry = $oObject;
					} elseif($oObject instanceof Ext_TS_Inquiry_Contact_Abstract) {
						// TODO Inquiry ist eine interne Variable, die nicht immer gefüllt sein muss
						// Bei Massenkommunikation wird Inquiry zufällig durch die Platzhalter gesetzt!
						$oInquiry = $oObject->getInquiry();
					} else {
						
						$oInquiry = null;
						
						// Falls eine manuelle E-Mail Adresse eingetragen wird und wir uns in der InboxListe befinden,
						// anhand des selektierten Eintrages die Buchung laden, in den anderen Listen mit payment_reminders
						// ist das nicht möglich, da die Liste multiple sind und wir dann nicht wissen zu wem die Zahlungserinnerung
						// verschickt werden soll (war so gewünscht in Ticket #3935)
						/*if(
							$sApplication == 'inbox' ||
							$sApplication == 'simple_view' ||
							$sApplication == 'client_payment'
						) {
							$aSelectedIds	= (array)$aEmail['selected_id'];
							$iSelectedId	= (int)reset($aSelectedIds);
							
							if($iSelectedId > 0)
							{
								$oInquiry	= Ext_TS_Inquiry::getInstance($iSelectedId);
							}
						}*/

					}

					// Bei Einzelkommunikation steht im Kontakt-Objekt keine Buchung mehr drin
					if(
						$oInquiry === null &&
						!empty($aEmail['selected_id_single'])
					) {
						// Jede Application dieses Flags hat die Inquiry-ID (gibt auch kein encoded dort)
						$oInquiry = Ext_TS_Inquiry::getInstance($aEmail['selected_id_single']);

						// Wenn Empfänger = Student: Prüfen ob selected_id auch zum Kontakt gehört, da das dummerweise nur Zahlen sind
						if($oObject instanceof Ext_TS_Inquiry_Contact_Abstract) {
							$oContact = $oInquiry->getFirstTraveller(); // Nicht getCustomer(), da dort Inquiry in Instanz gesetzt wird
							if($oObject->id != $oContact->id) {
								throw new RuntimeException('Given contact does not match with inquiry contact from selected_id_single for payment_reminder flag');
							}
						}
					}
					
					if(
						$oInquiry instanceof Ext_TS_Inquiry &&
						$oInquiry->exist()
					) {
						$aReminders	= $oInquiry->payment_reminders;
						$aReminders[] = array(
							'log_id' => $iLogId,
						);
						
						$oInquiry->payment_reminders = $aReminders;
						$oInquiry->save();
					} else {
						/*$sClassNameForDebug = '';
						$aClassDataForDebug = array();

						if($oObject instanceof WDBasic)
						{
							$sClassNameForDebug = $oObject->getClassName();
							$aClassDataForDebug	= $oObject->getArray();
						}

						Ext_Thebing_Log::error('reminders are only for inquiries!', array(
							'object_class'	=> $sClassNameForDebug,
							'object_data'	=> $aClassDataForDebug,
							'application'	=> $sApplication,
							'selected_ids'	=> $aEmail['selected_id'],

						));*/
						throw new RuntimeException('No inquiry for payment_reminder flag');
					}
					
					break;           
				case 'transfer_provider_request':
					// Anfrage bei Providern
					// Alle angefrgten Provider durchgehen
                                     
					foreach((array)$aEmail['selected_id'] as $iTransferId){
                                     
						$oTransfer = Ext_TS_Inquiry_Journey_Transfer::getInstance($iTransferId);
                                     
						$oTransferRequest						= $oTransfer->getNewProviderRequest();
						if($oObject instanceof Ext_Thebing_Accommodation){
							// Unterkunft
							$oTransferRequest->provider_type	= 'accommodation';
						}else{       
							$oTransferRequest->provider_type	= 'provider';
						}            
						$oTransferRequest->provider_id			= $oObject->id;
                                     
						if(          
							$oTransferRequest->transfer_id > 0 &&
							$oTransferRequest->provider_id > 0
						){           
							$oTransferRequest->save();
						}            
                                     
					}                
					break;           
				case 'transfer_provider_confirm':

					// Bestätigen bei Providern/Unterkunft
					foreach((array)$aEmail['selected_id'] as $iTransferId){
                                     
						$oTransfer = Ext_TS_Inquiry_Journey_Transfer::getInstance($iTransferId);
                                     
						if(          
							$oObject instanceof Ext_Thebing_Accommodation &&
							$oTransfer->provider_type == 'accommodation' &&
							$oTransfer->provider_id == $oObject->id
						){           
							// Bestätigung an Familie erfolgreich verschickt
							$oTransfer->provider_confirmed	= time();
							$oTransfer->save();
						}elseif(     
							$oObject instanceof Ext_Thebing_Pickup_Company &&
							$oTransfer->provider_type == 'provider' &&
							$oTransfer->provider_id == $oObject->id
						){           
							$oTransfer->provider_confirmed	= time();
							$oTransfer->save();
						}            
					}                
					break;           
				case 'transfer_customer_accommodation_information':
					// Unterkunft Transfer bestätigen wenn Zielort eine Fam ist
					foreach((array)$aEmail['selected_id'] as $iTransferId){

						$oTransfer = Ext_TS_Inquiry_Journey_Transfer::getInstance($iTransferId);

						if(  
							(
								$oTransfer->end_type == 'accommodation' &&
								$oTransfer->end == $oObject->id
							) || (
								$oTransfer->start_type == 'accommodation' &&
								$oTransfer->start == $oObject->id	
							)
							
						){           

							$oTransfer->accommodation_confirmed = time();
							$oTransfer->save();
						}elseif(  
							(
								$oTransfer->end_type == 'accommodation' &&
								$oTransfer->end == '0'
							) || (
								$oTransfer->start_type == 'accommodation' &&
								$oTransfer->start == '0'
							)
						){           
							// Es wurde eine Unterkunft gewählt ohne spez. Familie
							// Es kann sich nur um eine Anreise handeln
							$oInquiry = $oTransfer->getInquiry();
							$aFirstLastAcc = $oInquiry->getFirstLastMatchedAccommodation();
							if(      
								is_object($aFirstLastAcc['first']) &&
								$aFirstLastAcc['first']->id == $oObject->id
							){       
								$oTransfer->accommodation_confirmed = time();
								$oTransfer->save();
							}        
						}            
					}                
					break;           
				case 'transfer_customer_agency_information':
					// Kunde/Agentur über Transfer informiert
                                     
					foreach((array)$aEmail['selected_id'] as $iTransferId){
                                     
                                     
						$oTransfer = Ext_TS_Inquiry_Journey_Transfer::getInstance($iTransferId);
						$oInquiry = $oTransfer->getInquiry();
                                     
						if(          
							$oInquiry->agency_id > 0 &&
							$oObject instanceof Ext_Thebing_Agency_Contact && // Agenturen
							$oInquiry->agency_id == $oObject->company_id
						){           
							// Agentur
							$oTransfer->customer_agency_confirmed = time();
							$oTransfer->save();
						}            
		                             
						if(          
							//$oInquiry->agency_id == 0 &&
							$oObject instanceof Ext_TS_Inquiry_Contact_Abstract  // Kunde
						){           
							$oInquiryTemp = $oObject->getInquiry();
						
							if($oInquiryTemp){
								if($oInquiry->id == $oInquiryTemp->id){
									// Kunde 
									$oTransfer->customer_agency_confirmed = time();
									$oTransfer->save();
								}    
							}        
						}               
					}                
					break;           
				case 'accommodation_arrival_requested':
					// Kunde/Agentur Flugdaten angefragt
					if($sApplication == 'accommodation_communication_customer_agency'){
						foreach((array)$aEmail['selected_id'] as $iAccommodationAllocationId){
							$oAccommodationAlloction = Ext_Thebing_Accommodation_Allocation::getInstance($iAccommodationAllocationId);
							$oInquiryAccommodation = $oAccommodationAlloction->getInquiryAccommodation();
							$oInquiry = $oInquiryAccommodation->getInquiry();
                                     
                                     
							$oInquiry->transfer_data_requested = time();
							$oInquiry->save();
						}            
					}                
					break;           
				case 'accommodation_confirmed_customer':
					// Kunde/Agentur Unterkunft bestätigt
                                     
					if(              
						$sApplication == 'accommodation_communication_customer_agency' ||
						$sApplication == 'accommodation_communication_history_customer_confirmed'
					){               
						foreach((array)$aEmail['selected_id'] as $iAccommodationAllocationId){
							$oAccommodationAlloction = Ext_Thebing_Accommodation_Allocation::getInstance($iAccommodationAllocationId);
                                     
							$oAccommodationAlloction->customer_agency_confirmed = time();
							$oAccommodationAlloction->save();
						}            
					}                
					break;           
				case 'accommodation_confirmed_provider':
					// Kundendaten an Unterkunft bestätigt
					if(              
						$sApplication == 'accommodation_communication_provider' ||
						$sApplication == 'accommodation_communication_history_accommodation_confirmed'
					){               
						foreach((array)$aEmail['selected_id'] as $iAccommodationAllocationId){
							$oAccommodationAlloction = Ext_Thebing_Accommodation_Allocation::getInstance($iAccommodationAllocationId);
                                     
							$oAccommodationAlloction->accommodation_confirmed = time();
							$oAccommodationAlloction->save();
						}            
					}                
					break;           
				case 'accommodation_confirmed_transfer':
					// Transferdaten an Unterkunft bestätigt
					if($sApplication == 'accommodation_communication_provider'){
						foreach((array)$aEmail['selected_id'] as $iAccommodationAllocationId){
							$oAccommodationAlloction = Ext_Thebing_Accommodation_Allocation::getInstance($iAccommodationAllocationId);
                                     
							$oAccommodationAlloction->accommodation_transfer_confirmed = time();
							$oAccommodationAlloction->save();
						}            
					}                
					break;           
				case 'accommodation_canceled_provider':
					// Unterkunft diese Unterkunft absagen
					if($sApplication == 'accommodation_communication_history_accommodation_canceled'){
						foreach((array)$aEmail['selected_id'] as $iAccommodationAllocationId){
							$oAccommodationAllocation = Ext_Thebing_Accommodation_Allocation::getInstance($iAccommodationAllocationId);
							$oAccommodationAllocation->accommodation_canceled  = time();
							$oAccommodationAllocation->save();
						             
							$aPayments = $oAccommodationAllocation->checkPaymentStatus();
                                     
							if(empty($aPayments)){
								$oAccommodationAllocation->deleteMatching();
							}        
						}            
					}                
					break;           
				case 'accommodation_canceled_customer':
					// Unterkunft des Kunden/Agentur absagen
					if($sApplication == 'accommodation_communication_history_customer_canceled'){
						foreach((array)$aEmail['selected_id'] as $iAccommodationAllocationId){
							$oAccommodationAllocation = Ext_Thebing_Accommodation_Allocation::getInstance($iAccommodationAllocationId);
                                     
							$oAccommodationAllocation->customer_agency_canceled  = time();
                                     
							$oAccommodationAllocation->save();
							         
							$aPayments = $oAccommodationAllocation->checkPaymentStatus();
                                     
							if(empty($aPayments)){
								$oAccommodationAllocation->deleteMatching();
							}        
						}            
					}                
					break;           
                                     
				case 'customer_info':
				case 'provider_info':

					// Falls das hier Probleme verursacht, andere Lösung finden und gucken das dabei noch #1682 läuft...
					if(is_object($oObject) && $oObject instanceof Ext_TS_Inquiry_Journey_Insurance) {
						$oJourneyInsurance = $oObject;
					} else {
						$aSelectedIds = (array)$aEmail['selected_id'];
						$iSelectedId = (int)reset($aSelectedIds);
						$oJourneyInsurance = self::_getObjectFromApplication($sApplication, $iSelectedId);
					}

					if($sApplication == 'insurance_customer') {
						$oJourneyInsurance->info_customer = time();
						$oJourneyInsurance->changes_info_customer = 0;
						$oJourneyInsurance->save();
					} elseif($sApplication == 'insurance_provider') {
						$oJourneyInsurance->info_provider = time();
						$oJourneyInsurance->changes_info_provider = 0;
						$oJourneyInsurance->save();
					}

					break;

				case 'inquiry_feedback_invited':
					$aProcessIds = (array)$aEmail['processId'];
					foreach($aProcessIds as $iProcessId) {
						$oProcess = Ext_TS_Marketing_Feedback_Questionary_Process::getInstance($iProcessId);
						$oProcess->message_id = $iLogId;
						$oProcess->active = 1;
						if($oProcess->validate()) {
							$oProcess->save();
						}
					}
					break;
				case 'attendance_warning':

					// Das müsste durch prepareFlags() abgefangen werden
					if(empty($aEmail['decoded_data']['inquiry_journey_course_id_communication'])) {
						throw new RuntimeException('Attendance warning flag checked but no journey course id!');
					}

					$oInquiryJourneyCourse = Ext_TS_Inquiry_Journey_Course::getInstance($aEmail['decoded_data']['inquiry_journey_course_id_communication']);

					$attendanceWarning = $oInquiryJourneyCourse->index_attendance_warning;

					if (empty($attendanceWarning)) {
						$attendanceWarning = [];
					}

					$attendanceWarning[] =
						[
							'date' => date('Y-m-d H:i:s'),
							'template_name' => $aEmail['template']->name
						];

					$oInquiryJourneyCourse->index_attendance_warning = $attendanceWarning;
					$oInquiryJourneyCourse->save();

					break;
			}
                                     
		}                            
                                     
	}                                
                                     
	/*                               
	 * Excel Export Pickup           
	 */                              
	public function getExcelPickupDocument($aSelectedIds){
                                     
		$sStudentsFile = '';         
		try {                        
                                     
			$oExport = new Ext_Thebing_Pickup_Export($this->_oSchool, self::$_sL10NDescription);
                                     
			foreach((array)$aSelectedIds as $iOriginalId) {
				$oInquiryTransfer = Ext_TS_Inquiry_Journey_Transfer::getInstance($iOriginalId);
				$oExport->loadStudentData($oInquiryTransfer);
}
                                     
			$sStudentsFile = $oExport->save();
		} catch(Exception $e) {
			__pout($e);
		}                            
                                     
		return $sStudentsFile;                                     
	}
                                
	/*                               
	 * Excel Export Accommodation    
	 */                              
//	public function getExcelAccommodationDocument($aSelectedIds){
//
//		$sStudentsFile = '';
//
//		try {
//
//			$oExport = new Ext_Thebing_Accommodation_Export($this->_oSchool, self::$_sL10NDescription);
//
//			foreach((array) $aSelectedIds as $iOriginalId){
//
//				$oAccommodationAllocation = Ext_Thebing_Accommodation_Allocation::getInstance($iOriginalId);
//				$oExport->loadStudentData($oAccommodationAllocation);
//			}
//
//			$sStudentsFile = $oExport->save();
//
//		} catch(Exception $e) {
//
//		}
//
//		return $sStudentsFile;
//
//	}
                                     
	/*                               
	 * Formatiert die Empfägerliste im dialog
	 */                              
	public function formatRecipients($aRecipients){
                                     
		foreach((array)$aRecipients as $sKey => $aData){
			foreach((array)$aData as $iKey => $sText){            
				$aRecipients[$sKey][$iKey] = $sText;
            }                                    
		}                            
		                             
		return $aRecipients;         
	}                                
	                                 
	/*                               
	 * Bestimmt anhand der Application ob Zu jedem Listeneintrag, ODER zum gewähltn object kommuniziert wird
	 */                              
	public function checkCommunicationDirection($sApplication, $sObject, $aSelectedIds){
		                             
		$aBack = $aSelectedIds;      
		                             
		$bIndividualCommunication = false;
		                             
		if(count($aBack)<= 1){       
			return $aBack;           
		}                            
		                             
		                             
		if(                          
			$sObject == 'Ext_Thebing_Agency_Contact' &&
			$sApplication == 'transfer_customer_agency_information'
		){                           
			// Agenturmitarbeiter sollen über alle Transfere gleichzeitig informiert werden können 
			$bIndividualCommunication = true;
		}elseif(                     
			$sObject == 'Ext_Thebing_Pickup_Company' &&
			$sApplication == 'transfer_provider_confirm'
		){                           
			// Transferprovider bestätigen sollen alle Transfere gleichzeitig bekommen
			$bIndividualCommunication = true;
		}elseif(                     
			$sObject == 'Ext_Thebing_Pickup_Company' &&
			$sApplication == 'transfer_provider_request'
		){                           
			// Transferprovider anfragen sollen alle Transfere gleichzeitig bekommen
			$bIndividualCommunication = true;
		}                            
                                     
		                             
		if($bIndividualCommunication){
			$iBack = reset($aBack);  
			$aBack = array($iBack);  
		}                            
                                     
		                             
		return $aBack;               
	}                                
	                                 
	/*                               
	 * Prüft ob kommuniziert werden darf und gibt einen Fehler zurück
	 */                              
	public function getCommunicationError($aSelectedIds, $sApplication, $aSelectedIdsAdditional) {
		                             
		$aError = array();           
		$aError['message'] = ''; // Einfache Fehlermeldung
		$aError['dialog'] = null; // Kompletter Fehlerdialog
		                             
		$bNoAgencyMails = true;      
		                             
		// Prüfen ob Agenturmailadressen verfügbar sind
		foreach((array)$aSelectedIds as $iKey => $iSelectedId) {
			$oObject = self::_getObjectFromApplication($sApplication, $iSelectedId, $aSelectedIdsAdditional[$iKey]);
                                     
			if(                      
				$oObject instanceof Ext_TS_Inquiry_Abstract &&
				$oObject->agency_id > 0 &&
				$bNoAgencyMails      
			){                       
				$aAgencyContacts = $oObject->getAgencyContactsWithValidEmails();
				foreach((array)$aAgencyContacts as $oContact){
					if($oContact->email != ''){
						$bNoAgencyMails = false;
						break;       
					}                
				}                    
                                     
			}                        
		}                            
                                     
		// Prüfen ob eine Mailadresse vorhanden ist für die Kommunikation
		foreach((array)$aSelectedIds as $iKey => $iSelectedId) {
			                         
			$oObject = self::_getObjectFromApplication($sApplication, $iSelectedId, $aSelectedIdsAdditional[$iKey]);
		                             
			if(                      
				(                    
					(
						($sApplication == 'customer') &&
						1 > count($oObject->getCustomerEmails())
					)
					||
					(                
						$sApplication == 'accommodation_communication_customer_agency' &&
						$oObject->getInquiry()->agency_id <= 0 &&
						1 > count($oObject->getInquiry()->getCustomerEmails())
					)                
				) && (               
					$bNoAgencyMails	 
				)                    
			){                       
                                     
				                     
				// Bei Kunden schon vorher alle E-Mails checken
				// Falls ein Kunde mit falscher E-Mail vorhanden, auffordern zum überprüfen
			                         
				$sTempError = L10N::t('Schüler {name} bzw. Agentur hat keine valide E-Mail-Adresse. Bitte überprüfen Sie Die Daten!', $this->_oGui->gui_description);
				$sTempError = preg_replace('/{name}/', $oObject->getCustomer()->name, $sTempError);
				$aError['message'] .= $sTempError;
				$aError['message'] .= '<br /><br />';
			}elseif(                 
				$sApplication == 'accommodation_communication_history_customer_canceled' ||
				$sApplication == 'accommodation_communication_history_accommodation_canceled'		
			){                       
				// Prüfen ob Zahlungen existieren
				if(is_object($oObject)){
					$oInquiry = $oObject->getInquiry();
					                 
					$aStornoCheck = Ext_Thebing_Storno_Condition::check($oInquiry, 'accommodation');
					if(!empty($aStornoCheck)){
						$aError['dialog'] = Ext_Thebing_Storno_Condition::getDialog($oObject->id, $aStornoCheck, $this->_oGui);
					}                
				}                    
				                     
				                     
				                     
			}                        
		}                            
		                             
			                         
			                         
		return $aError;              
			                         
	}

	public static function getNewIdentitiesData(&$oGui, $aVars, $aTransfer) {
		
		$oDummyDialog = new Ext_Gui2_Dialog();
		
		$oCommunication = new self($oDummyDialog, []);
		$oCommunication->_oGui = $oGui;
		
		$aTransfer = $oCommunication->_getNewIdentitiesData($aVars, $aTransfer);

		return $aTransfer;
	}
                                     
    protected function _getNewIdentitiesData($aVars, &$aTransfer)
	{
		$aSelectedIds	= (array)$aVars['id'];
		$sApplication	= $aVars['save']['application'];
		$iIdentity		= (int)$aVars['save']['identity_id'];

		$aSignatures = array();

		/**
		 * Ausgewählte Template IDs bearbeiten
		 */
		$aTemplateIds = array();
		foreach((array)$aVars['save']['template_id'] as $sKey=>$iValue) {
			$aTemplateIds[$sKey] = (int)$iValue;
		}

		$aOriginalSelectedIds = $aSelectedIds;
		$sOriginalApplication = $sApplication;

		$aDecoded = $this->_convertSelectedIds($aSelectedIds, $sApplication);
		$aSelectedIds = $aDecoded['decoded_ids'];

		// Falls mehrere Applications über ein Icon gesteuert werden müssen
		$sApplicationTemp = $this->_switchApplication($sApplication, $aSelectedIds);
		if($sApplicationTemp != $sApplication) {
			$sApplication = $sApplicationTemp;
			$aDecoded = $this->_convertSelectedIds($aOriginalSelectedIds, $sApplication);
			$aSelectedIds = $aDecoded['decoded_ids'];
		}

		$aSelectedIds = (array)$aSelectedIds;
		$aSelectedIdsAdditional = $aDecoded['additional'];

		/**
		 * Holt sich die Flags und sonstigen Einstellungen der ausgwählten Anwendung
		 */
		$this->_getApplicationSettings($sApplication, $aSelectedIds);

		foreach((array)$aSelectedIds as $iKey => $iId) {
			$oObject = self::_getObjectFromApplication($sApplication, $iId, $aSelectedIdsAdditional[$iKey]);
			$sLanguage = $this->_getLanguageFromObject($oObject, $sApplication);
		}

		$iCount = 0;

		foreach((array)$this->_aTemplateOptions as $sKey => $aOption){

			$iTemplateId = $aTemplateIds[$sKey];

			if((int)$iTemplateId <= 0){
				continue;
			}

			$oTemplate = Ext_Thebing_Email_Template::getInstance($iTemplateId);

			$oTemplateUser = Ext_Thebing_User::getInstance($iIdentity);

			// Signatur
			if($oTemplate->html == 1) {
				$sSignatureKey = 'signature_email_html_'.$sLanguage.'_'.$this->_oSchool->id;
			} else {
				$sSignatureKey = 'signature_email_text_'.$sLanguage.'_'.$this->_oSchool->id;
			}

			$sSignature = $oTemplateUser->$sSignatureKey;

			$sKey = $iCount . '_' . $oTemplate->id;

			$aSignatures[$sKey] = $sSignature;

			$iCount++;
		}


		if(!is_array($aTransfer)){
			$aTransfer					= array();
		}
		
		$aTransfer['aSignatures']	= $aSignatures;
		$aTransfer['action']		= 'updateIdentityCallback';

		$sSelectedIds				= implode('_',$aOriginalSelectedIds);

		if(!isset($aTransfer['data'])){
			$aTransfer['data']			= array();
		}
		
		$aTransfer['data']['id']	= self::$_sIdTag.'_'.$sSelectedIds;

		return $aTransfer;
	}
	
	protected function _getDialogTitleByApplication($sApplication, $aSelectedIds)
	{
		switch($sApplication)
		{
			case 'placement_test':

				//Das PlacementtestResult Object muss nicht immer befüllt sein in der Liste,
				//das heißt wir haben nicht immer die Möglichkeit über die WDBasic an den Kunden
				//ran zu kommen, deshalb müssen wir an der Stelle über die encode_data oder query_id_column
				//an die inquiry_id kommen
				$iInquiryId		= reset($aSelectedIds);
				$oInquiry		= Ext_TS_Inquiry::getInstance($iInquiryId);
				$oCustomer		= $oInquiry->getCustomer();
				$sCustomerName	= $oCustomer->getName();
			
				$sTitle = L10N::t('Kommunikation "{customer_name}"', self::$_sL10NDescription);
				$sTitle = str_replace('{customer_name}', $sCustomerName, $sTitle);
				break;
			case 'activity':
				$sTitle = L10N::t('Kommunikation', self::$_sL10NDescription);
				break;
			default:
				
				$sTitle = L10N::t('Kommunikation "{customer_name}"', self::$_sL10NDescription);
				break;
			
		}
		
		return $sTitle;
	}
                                     
	/**
	 * Liefert den Standardmail Inhalt für Mails, die NICHT an objecte gehenen (manuelle Adressen)
	 * 
	 * @global  $_VARS
	 */
	protected function _getDefaultMailContent($aVars, $oMailContent){

		$sTemplateid = reset(array_keys($aVars['save']['subject']));

		$aTemp = explode('_', $sTemplateid);
		$iCount			= (int)$aTemp[0];
		$iTemplateid	= (int)$aTemp[1];
					
		$oTemplate		= Ext_Thebing_Email_Template::getInstance($iTemplateid);

		$sSubject			= $aVars['save']['subject'][$sTemplateid];
		$sContent			= $aVars['save']['content'][$sTemplateid];
		$sSignature			= $aVars['save']['signature'][$sTemplateid];
		$sTemplateCategory	= $aVars['save']['template_category'][$sTemplateid];

		$oMailContent->subject = $sSubject;
		$oMailContent->content = $sContent;
		$oMailContent->signature = $sSignature;
		$oMailContent->template = $oTemplate;

		return $oMailContent;
	}
	
	protected function _addRelationsToInquiry(Ext_TS_Inquiry $oInquiry, $oHistoryGui)
	{
		$oObject = $oInquiry;
		
		// Mails aus der Unterkunftskommunikation -----------------------------------------------

		$aInquiryAccommodations	= $oObject->getAccommodations(true, true);

		// Zuweisungen der Inquiry bestimmen
		$aAllAllocations = array();
		foreach((array)$aInquiryAccommodations as $oInquiryAccommodation){
			$aAllocations = $oInquiryAccommodation->getAllocations(true, true);
			foreach((array)$aAllocations as $oAllocation){
				$aAllAllocations[] = $oAllocation;
			}                    
		}                        

		foreach((array)$aAllAllocations as $oAllocation){
			$oHistoryGui->setRelationObject($oAllocation->id, get_class($oAllocation));
		}                        

		// Mails aus der Transferkommunikation --------------------------------------------------
		$aInquiryTransfers = $oObject->getTransfers();

		foreach((array)$aInquiryTransfers as $oTransfer){
			$oHistoryGui->setRelationObject($oTransfer->id, get_class($oTransfer));
		}                        

		//Mails aus Versicherungen
		$aInsurances = $oObject->getInsurances();
		foreach((array)$aInsurances as $oInquiryInsurance){
			$oHistoryGui->setRelationObject($oInquiryInsurance->id, get_class($oInquiryInsurance));
		}                        

		//Mails aus Aktivitäten
		$aActivites = $oObject->getActivities();
		foreach((array)$aActivites as $oInquiryActivity){
			$oHistoryGui->setRelationObject($oInquiryActivity->id, get_class($oInquiryActivity));
		}

		// Alle sonstigen Inquiry-bezogenen Mails (attendance)
		$oHistoryGui->setRelationObject($oObject->id, get_class($oObject));
	}
	
	/**
	 * Dummy Methode, damit in der Schulsoftware beim Icon kein Dialog generiert wird 
	 */
	public static function createDialogObject(Ext_Gui2 &$oGui, $aAccess, $sApplication) {

		// Neue (TC) Kommunikation
		if(isset(self::$_aApplicationAllocations[$sApplication])) {
			return parent::createDialogObject($oGui, $aAccess, $sApplication);
		}
		
	}
	
	/**
	 * Fügt einer GUI die JS-Datei für die Kommunikation an
	 * 
	 * @param Ext_Gui2 $oGui 
	 */
	public static function addJsFile(Ext_Gui2 $oGui) {
		
		$oGui->addJs('js/communication.js', 'thebing');
	
		parent::addJsFile($oGui);
		
	}

	/**
	 * @param array $_VARS
	 * @return string
	 * @throws Exception
	 */
	private function getManualRecipientLanguage($_VARS) {

		switch($_VARS['additional']) {
			case 'marketing_agencies':
				$oObject = Ext_Thebing_Agency::getInstance(reset($_VARS['id']));
				break;
			/*case 'marketing_companies':
				$oObject = \TsCompany\Entity\Company::getInstance(reset($_VARS['id']));
				break;*/
			default:
				$oObject = Ext_Thebing_Client::getFirstSchool();
				break;
		}

		return $oObject->getLanguage();
	}

	/**
	 * @param string $sRecipient
	 * @param int $iRecipientId
	 * @param array $aSelectedIds
	 * @param array $aVars
	 * @return array
	 */
	public function getIndividualAttachments($sRecipient, $iRecipientId, $aSelectedIds, $aVars) {

		$aAttachments = [];

//		if($this->_bAgencyOpenPaymentsAttachment) {
//
//			// Prüfen ob Empfänger den Anhang bekommend arf
//			if(
//				$sRecipient == 'Ext_Thebing_Agency_Contact' &&
//				$iRecipientId > 0 &&
//				!empty($aVars['save']['agency_payment_overview'])
//			) {
//
//				$oRecipientObject = call_user_func(array($sRecipient, 'getInstance'), (int)$iRecipientId);
//				$sAgencyOpenPaymentPath = Ext_TS_Inquiry::getAgencyOpenPayment($aSelectedIds, true, $oRecipientObject->agency_id);
//
//				if(!empty($sAgencyOpenPaymentPath)) {
//					$aAttachments[] = $sAgencyOpenPaymentPath;
//				}
//
//			}
//
//		}

		// Welche Anhänge sollen hinzugefügt werden
		$aAttachmentSelection = $aVars['save']['accounting_provider'] ?? [];

		// Belege für jeden Empfänger auflösen
		if(isset($this->aAccountingProviderAttachments[$iRecipientId])) {
			foreach($this->aAccountingProviderAttachments[$iRecipientId] as $aAccountingProviderAttachment) {
				if(
					isset($aAccountingProviderAttachment['path']) &&
					(
						// Massenkommunikation
						in_array('multiple_document_attachment', $aAttachmentSelection) ||
						// Einzelner Provider ausgewählt
						in_array($aAccountingProviderAttachment['path'], $aAttachmentSelection)
					)
				) {
					$aAttachments[] = $aAccountingProviderAttachment['path'];
				}
			}
		}

		return $aAttachments;
	}

	/**
	 * @param string $sContent
	 * @param string $sSignature
	 * @param bool $bHtml
	 */
	public static function getMailContentSignature($sSignature) {

		// #793
		if(strpos($sSignature, 'src="/') !== false) {

			if($_SERVER['HTTPS'] == 'on') {
				$sUriAddon = 'https://';
			} else {
				$sUriAddon = 'http://';
			}

			$sDomain = $sUriAddon . $_SERVER['HTTP_HOST'] . '/';
			$sSrc = 'src="' . $sDomain;

			$sSignature = str_replace('src="/', $sSrc, $sSignature);

		}

		return $sSignature;
	}

	/**
	 *
	 * @param \Ext_Thebing_Email_Template $oTemplate
	 * @param string $sLanguage
	 * @param string $sContent
	 * @param string $sSignature
	 */
	static public function setLayoutAndSignature(\Ext_Thebing_Email_Template $oTemplate, string $sLanguage, string &$sContent, string $sSignature) {

		$iLayoutId = $oTemplate->{'layout_'.$sLanguage};

		if($oTemplate->html) {
			if($iLayoutId > 0) {
				$oLayout = \Ext_Thebing_Email_Layout::getInstance($iLayoutId);
			} else {
				$oLayout = \Ext_Thebing_Email_Layout::getBlankInstance();
			}
			$sLayout = $oLayout->html;

			if(strpos($sLayout, '{email_signature}') === false) {
				$sLayout = str_replace('{email_content}', '{email_content}{email_signature}', $sLayout);
			}

			$sContent = str_replace('{email_content}', $sContent, $sLayout);
			$sContent = str_replace('{email_signature}', $sSignature, $sContent);

		} else {
			$sContent .= "\n".$sSignature;
			$sContent = strip_tags($sContent);
		}

	}

	/**
	 * Select Options mit den Empfängergruppen
	 * @return array
	 */
	/*public static function getSelectRecipientGroups() {

		$aRetVal = array(
			'customer' => self::t('Kunden'),
			'company' => self::t('Firma'),
			'agency' => self::t('Agentur'),
			'sponsor' => self::t('Sponsor'),
			'group' => self::t('Gruppe'),
			'teacher' => self::t('Lehrer'),
			'accommodation_provider' => self::t('Unterkunftsanbieter'),
			'transfer_provider' => self::t('Transferanbieter'),
			'insurance_provider' => self::t('Versicherungsanbieter'),
		);

		return $aRetVal;
	}*/

	/**
	 * Select Options mit den Applications
	 * @return \Illuminate\Support\Collection
	 */
	public static function getSelectApplications(\Tc\Service\LanguageAbstract $l10n, \Access $access = null): \Illuminate\Support\Collection {

		$applications = parent::getSelectApplications($l10n, $access)
			->put('cronjob', $l10n->translate('Admin » Automatische E-Mails'))
			->put('mobile_app_forgotten_password', $l10n->translate('Mobile App » Passwort vergessen'));

		return $applications;
	}

	public static function getSelectApplicationRecipients(\Access $access = null): \Illuminate\Support\Collection {

		$applications = parent::getSelectApplicationRecipients($access)
			->put('cronjob', ['customer'])
			->put('mobile_app_forgotten_password', ['customer']);

		return $applications;
	}

	public static function getSelectApplicationFlags(\Access $access = null): \Illuminate\Support\Collection {

		$applications = parent::getSelectApplicationFlags($access)
			->put('cronjob', [])
			->put('mobile_app_forgotten_password', []);

		return $applications;
	}
	
	/**
	 * gibt ein Array mit den Klassennamen zurück, über die die Platzhalterklassen
	 * für die Übersichten geholt werden
	 *
	 * @return array 
	 */
	public static function getPlaceholderClasses() {

		$aReturn = [];

		$aApplications = self::getSelectApplications(\Communication\Facades\Communication::l10n())->toArray();

		$aReturn['booking'] = [
			'class' => Ext_TS_Inquiry::class,
			'title' => $aApplications['booking']
		];

		$aReturn['enquiry'] = [
			'class' => function () {
				$object = new \Ext_TS_Inquiry();
				$object->type = \Ext_TS_Inquiry::TYPE_ENQUIRY;
				$journey = $object->getJoinedObjectChild('journeys');
				$journey->type = Ext_TS_Inquiry_Journey::TYPE_REQUEST;
				$journey->getJoinedObjectChild('courses');
				$journey->getJoinedObjectChild('accommodations');
				$journey->getJoinedObjectChild('transfers');
				$journey->getJoinedObjectChild('insurances');
				return $object;
			},
			'title' => $aApplications['enquiry'],
		];

		$aReturn['arrival_list'] = [
			'class' => Ext_TS_Inquiry::class,
			'title' => $aApplications['arrival_list']
		];

		$aReturn['departure_list'] = [
			'class' => Ext_TS_Inquiry::class,
			'title' => $aApplications['departure_list']
		];

		$aReturn['feedback_list'] = [
			'class' => Ext_TS_Inquiry::class,
			'title' => $aApplications['feedback_list']
		];

		$aReturn['visum_list'] = [
			'class' => Ext_TS_Inquiry::class,
			'title' => $aApplications['visum_list']
		];

		$aReturn['simple_view'] = [
			'class' => Ext_TS_Inquiry::class,
			'title' => $aApplications['simple_view']
		];

		$aReturn['transfer_provider_request'] = [
			'class' => Ext_TS_Inquiry_Journey_Transfer::class,
			'title' => $aApplications['transfer_provider_request']
		];

		$aReturn['transfer_provider_confirm'] = [
			'class' => Ext_TS_Inquiry_Journey_Transfer::class,
			'title' => $aApplications['transfer_provider_confirm']
		];

		$aReturn['transfer_customer_agency_information'] = [
			'class' => Ext_TS_Inquiry_Journey_Transfer::class,
			'title' => $aApplications['transfer_customer_agency_information']
		];

		$aReturn['transfer_customer_accommodation_information'] = [
			'class' => Ext_TS_Inquiry_Journey_Transfer::class,
			'title' => $aApplications['transfer_customer_accommodation_information']
		];

		$aReturn['insurance_customer'] = [
			'class' => Ext_TS_Inquiry_Journey_Insurance::class,
			'title' => $aApplications['insurance_customer']
		];

		$aReturn['insurance_provider'] = [
			'class' => Ext_TS_Inquiry_Journey_Insurance::class,
			'title' => $aApplications['insurance_provider']
		];

		$aReturn['accommodation_resources_provider'] = [
			'class' => Ext_Thebing_Contract_Version::class,
			'title' => $aApplications['accommodation_resources_provider']
		];

		$aReturn['contract_accommodation'] = [
			'class' => Ext_Thebing_Contract_Version::class,
			'title' => $aApplications['contract_accommodation']
		];

		$aReturn['accommodation_communication_customer_agency'] = [
			'class' => Ext_Thebing_Accommodation_Allocation::class,
			'title' => $aApplications['accommodation_communication_customer_agency']
		];

		$aReturn['accommodation_communication_provider'] = [
			'class' => Ext_Thebing_Accommodation_Allocation::class,
			'title' => $aApplications['accommodation_communication_provider']
		];

		$aReturn['accommodation_communication_history_customer_confirmed'] = [
			'class' => Ext_Thebing_Accommodation_Allocation::class,
			'title' => $aApplications['accommodation_communication_history_customer_confirmed']
		];

		$aReturn['accommodation_communication_history_customer_canceled'] = [
			'class' => Ext_Thebing_Accommodation_Allocation::class,
			'title' => $aApplications['accommodation_communication_history_customer_canceled']
		];

		$aReturn['accommodation_communication_history_accommodation_confirmed'] = [
			'class' => Ext_Thebing_Accommodation_Allocation::class,
			'title' => $aApplications['accommodation_communication_history_accommodation_confirmed']
		];

		$aReturn['accommodation_communication_history_accommodation_canceled'] = [
			'class' => Ext_Thebing_Accommodation_Allocation::class,
			'title' => $aApplications['accommodation_communication_history_accommodation_canceled']
		];

		$aReturn['agencies_payments'] = [
			'class' => Ext_Thebing_Agency_Payment::class,
			'title' => $aApplications['agencies_payments']
		];

		$aReturn['client_payment'] = [
			'class' => Ext_TS_Inquiry::class,
			'title' => $aApplications['client_payment']
		];

		$aReturn['accounting_teacher'] = [
			'class' => Ext_TS_Accounting_Provider_Grouping_Teacher::class,
			'title' => $aApplications['accounting_teacher']
		];

		$aReturn['accounting_accommodation'] = [
			'class' => Ext_TS_Accounting_Provider_Grouping_Accommodation::class,
			'title' => $aApplications['accounting_accommodation']
		];

		$aReturn['accounting_transfer'] = [
			'class' => Ext_TS_Accounting_Provider_Grouping_Transfer::class,
			'title' => $aApplications['accounting_transfer']
		];

		/*$aReturn['activity'] = [
			'class' => Ext_TS_Inquiry_Journey_Transfer::class,
			'title' => $aApplications['activity']
		];*/

		$aReturn['marketing_agencies'] = [
			'class' => Ext_Thebing_Agency::class,
			'title' => $aApplications['marketing_agencies']
		];

		$aReturn['marketing_agencies_contact'] = [
			'class' => Ext_Thebing_Agency_Contact::class,
			'title' => $aApplications['marketing_agencies_contact']
		];

		$aReturn['tuition_attendance'] = [
			'class' => Ext_Thebing_School_Tuition_Allocation::class,
			'title' => $aApplications['tuition_attendance']
		];

		$aReturn['placement_test'] = [
			'class' => Ext_TS_Inquiry::class,
			'title' => $aApplications['placement_test']
		];

		$aReturn['tuition_teacher'] = [
			'class' => Ext_Thebing_Teacher::class,
			'title' => $aApplications['tuition_teacher']
		];

		$aReturn['contract_teacher'] = [
			'class' => Ext_Thebing_Contract_Version::class,
			'title' => $aApplications['contract_teacher']
		];

		$aReturn['cronjob'] = [
			'class' => Ext_TS_Inquiry::class,
			'title' => $aApplications['cronjob']
		];

		$aReturn['mobile_app_forgotten_password'] = [
			'class' => Ext_TS_Inquiry::class,
			'title' => $aApplications['mobile_app_forgotten_password']
		];

		$aReturn['invoice'] = [
			'class' => Ext_Thebing_Inquiry_Document::class,
			'title' => $aApplications['invoice']
		];

		$aReturn['tuition_allocation'] = [
			'class' => Ext_Thebing_School_Tuition_Allocation::class,
			'title' => $aApplications['tuition_allocation']
		];

		$aReturn['job_opportunity_allocation'] = [
			'class' => \TsCompany\Entity\JobOpportunity\StudentAllocation::class,
			'title' => $aApplications['job_opportunity_allocation']
		];

		return $aReturn;
	}

	protected function getInquiryProviderContacts(\Ext_TS_Inquiry $inquiry) {

		$providers = [];

		foreach ($inquiry->getAccommodationProvider() as $provider) {
			if (Util::checkEmailMx($provider->email)) {
				$providers[] = [
					'object' => get_class($provider),
					'object_id' => $provider->id,
					'email' => $provider->email,
					'name' => sprintf('%s: %s (%s)', L10N::t('Unterkunft'), $provider->getName(), $provider->email),
				];
			}

			$members = $provider->getMembersWithEmail();
			foreach ($members as $member) {
				$providers[] = [
					'object' => $member::class,
					'object_id' => $member->id,
					'email' => $member->email,
					'name' => sprintf('%s - %s: %s (%s)', $provider->getName(), \L10N::t('Zugehöriger'), $member->getName(), $member->email),
				];
			}
		}

		foreach ($inquiry->getTuitionTeachers() as $teacher) {
			$teacher = \Ext_Thebing_Teacher::getInstance($teacher['teacher_id']);
			if (Util::checkEmailMx($teacher->email)) {
				$providers[] = [
					'object' => get_class($teacher),
					'object_id' => $teacher->id,
					'email' => $teacher->email,
					'name' => sprintf('%s: %s (%s)', L10N::t('Lehrer'), $teacher->getName(), $teacher->email),
				];
			}
		}

		return $providers;

	}

	protected function addOtherContacts(\Ext_TS_Inquiry $inquiry, array &$options, string $language, $selectedId = null) {
		
		$emergencyEmails = $inquiry->getJoinedObjectChilds('other_contacts');
		
		if(!empty($emergencyEmails)) {

			if ($selectedId === null) {
				$selectedId = $inquiry->id;
			}

			$otherContactsTypes = Ext_TS_Inquiry_Index_Gui2_Data::getOtherContactsTypes($this->_oGui);

			foreach($emergencyEmails as $emergencyEmail) {

				$name = $otherContactsTypes[$emergencyEmail->type].': '.$emergencyEmail->getName().' ('.$emergencyEmail->email.')';

				$this->addToSessionIds($options, $name, get_class($emergencyEmail), $emergencyEmail->id, $selectedId, $emergencyEmail->email, $language);
			}
		}
		
	}

	protected function addAgencyContacts(Ext_Thebing_Agency $oAgency, array &$aOptions, int $iId, string $sLanguage) {

		foreach ($oAgency->getContacts(false, true) as $oContact) {
			$name = $oContact->getName();
			if($oContact->master_contact == 1) {
					$name .= ' ['.\L10N::t('Hauptkontakt').']';
			}
			$this->addToSessionIds($aOptions, sprintf('%s (%s)', $name, $oContact->email), get_class($oContact), $oContact->id, $iId, $oContact->email, $sLanguage);
		}

	}

	/**
	 * TODO das sollte auch über die Core-Signaturen laufen
	 *
	 * @param $sText
	 * @param $aOptions
	 * @return array|string
	 */
	public function replacePlaceholders($sText, $aOptions = array()) {

		if (str_contains($sText, '{email_signature}')) {

			$oObject = $aOptions['object'];

			$iSubObject = (int)$oObject->getSubObject()?->id;
			$oSubObject = Factory::getInstance('Ext_TC_SubObject', $iSubObject);

			if(
				$this->_oIdentityUser instanceof \Ext_Thebing_User &&
				$oSubObject instanceof \Ext_Thebing_School
			) {
				if($aOptions['type'] === 'html') {
					$sSignatureKey = 'signature_email_html_'.$aOptions['language'].'_'.$oSubObject->id;
				} else {
					$sSignatureKey = 'signature_email_text_'.$aOptions['language'].'_'.$oSubObject->id;
				}

				$sText = str_replace('{email_signature}', $this->_oIdentityUser->$sSignatureKey, $sText);
			}

		}

		return parent::replacePlaceholders($sText, $aOptions);
	}

	/**
	 * @deprecated siehe Ext_Thebing_Inquiry_Placeholder::replaceFinalOutput())
	 * @param string $content
	 * @param Ext_TS_Contact $contact
	 * @param $placeholderObject
	 * @param string $placeholder
	 * @return void
	 */
	static public function replaceUserPasswordPlaceholder(string &$content, Ext_TS_Contact $contact, $placeholderObject, string $placeholder = '[user_password]'): void {

		// Passwort ersetzen
		if(strpos($content, $placeholder) !== false) {

			$loginData = $contact->getLoginData(true);

			if($placeholderObject instanceof \Ext_Thebing_Placeholder) {
				$placeholderObject->addMonitoringEntry('user_password');
			}

			$password = $loginData->generatePassword();
			if(!empty($password)) {
				$content = str_replace($placeholder, $password, $content);
			}

		}
		
	}
	
}
