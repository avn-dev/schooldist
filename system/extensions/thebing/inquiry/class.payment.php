<?php

use Core\Entity\ParallelProcessing\Stack;
use TsAccounting\Traits\Releaseable;

/**
 * @property string|int $id
 * @property $changed
 * @property $created
 * @property int $active
 * @property int $creator_id
 * @property int $editor_id
 * @property int $inquiry_id Bei Gruppen die ausgewählte Buchung der Gruppe
 * @property string $status
 * @property string $date
 * @property string $transaction_code
 * @property string $comment
 * @property int $method_id
 * @property int $type_id
 * @property string $sender
 * @property string $receiver
 * @property float $amount_inquiry
 * @property float $amount_school
 * @property int $currency_inquiry
 * @property int $currency_school
 * @property int grouping_id
 * @property string $additional_info
 * @property array $receipts
 * @property array $documents
 */
class Ext_Thebing_Inquiry_Payment extends Ext_Thebing_Basic {
	use Releaseable, \Core\Traits\WdBasic\MetableTrait;

	const STATUS_PENDING = 'pending';

	const STATUS_PAID = 'paid';

	const RECEIPT_OVERVIEW = 'overview';
	const RECEIPT_INVOICE = 'invoice';
	const RECEIPT_PAYMENT = 'payment';

	const TRANSLATION_PATH = 'Thebing » Payments';

	const JOINTABLE_RECEIPTS = 'receipts';

	/**
	 * @var string
	 */
	protected $_sTable = 'kolumbus_inquiries_payments';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'kip';

	protected $_sPlaceholderClass = \Ts\Service\Placeholder\Booking\Payment::class;

	/**
	 * @var array
	 */
	protected $_aFormat = array(
		// TODO Entfernen
		'changed' => array(
			'format' => 'TIMESTAMP'
		),
		// TODO Entfernen
		'created' => array(
			'format' => 'TIMESTAMP'
		),
		'inquiry_id' => [
			'required' => true,
		],
		'currency_inquiry' => [
			'required' => true,
			'validate' => 'INT_POSITIVE'
		],
		'currency_school' => [
			'required' => true,
			'validate' => 'INT_POSITIVE'
		]
	);

	/**
	 * @var array
	 */
	protected $_aJoinTables = array(
		// Verknüpfte Belege
		// Normalerweise gibt es hier 1-2 Einträge: Customer und Agency
		self::JOINTABLE_RECEIPTS => array(
			'table' => 'kolumbus_inquiries_payments_documents',
			'primary_key_field'	=> 'payment_id',
			'foreign_key_field' => 'document_id',
			'autoload' => false,
			'class' => Ext_Thebing_Inquiry_Document::class,
			'on_delete' => 'no_action'
		),
		// CN-Verrechnungen (Typ 4/5) dieser Zahlungen (Buchungen)
		'payment_creditnotes' => array(
			'table' => 'ts_inquiries_payments_to_creditnote_payments',
			'primary_key_field'	=> 'payment_id',
			'foreign_key_field' => 'creditnote_payment_id',
			'autoload' => false,
			'class' => 'Ext_Thebing_Inquiry_Payment'
		),
		// Zahlungen (Buchung) dieser CN-Verrechnung (Typ 4/5)
		'creditnote_payments' => array(
			'table' => 'ts_inquiries_payments_to_creditnote_payments',
			'primary_key_field'	=> 'creditnote_payment_id',
			'foreign_key_field' => 'payment_id',
			'autoload' => false,
			'class' => 'Ext_Thebing_Inquiry_Payment',
			'readonly' => true
		),
		// Verknüpfte Rechnungen
		// TODO Das wird kaum verwendet und sollte auf den Prüfstand, denn früher (und weiterhin) geht das über die Items
		'documents' => array(
			'table' => 'ts_documents_to_inquiries_payments',
			'primary_key_field'	=> 'payment_id',
			'foreign_key_field' => 'document_id',
			'autoload' => false,
			'class' => 'Ext_Thebing_Inquiry_Document',
			'on_delete' => 'no_action'
		),
		'agency_payments' => [
			'table' => 'kolumbus_inquiries_payments_agencypayments',
			'primary_key_field'	=> 'payment_id',
			'foreign_key_field' => 'agency_payment_id',
			'autoload' => false,
			'class' => 'Ext_Thebing_Agency_Payment'
		],
		'release' => array(
			'table' => 'ts_inquiries_payments_release',
			'primary_key_field' => 'payment_id',
			'autoload' => false,
			'on_delete' => 'no_action'
		),
	);

	/**
	 * @var array
	 */
	protected $_aJoinedObjects = array(
		'items' => [
			'class' => Ext_Thebing_Inquiry_Payment_Item::class,
			'key' => 'payment_id',
			'check_active' => true,
			'type' => 'child',
			'bidirectional' => true,
			'on_delete' => 'cascade'
		],
		'overpayments' => [
			'class' => Ext_Thebing_Inquiry_Payment_Overpayment::class,
			'key' => 'payment_id',
			'check_active' => true,
			'type' => 'child',
			'bidirectional' => true,
			'on_delete' => 'cascade'
		],
		'manual_creditnote_payments' => array(
			'class' => 'Ext_Thebing_Agency_Manual_Creditnote_Payment',
			'key' => 'payment_id',
			'check_active' => true,
			'type' => 'child'
		)
	);

	protected $_aFlexibleFieldsConfig = [
		'inquiries_payments' => []
	];

	/**
	 * Beim Löschen einer CN-Verrechnung wird auch die entsprechende Zahlung, die an die Agentur zugewiesen wurde,
	 * gelöscht. Allerdings löscht diese Zahlung wiederum ihre CN-Verrechnung, sodass beim Löschen eine Endlosschleife
	 * entstehen würde. Damit das nicht passiert, werden hier die IDs gesammelt. Das wäre gar nicht nötig,
	 * wenn es einen Entity-Manager gäbe, und nicht 1000 Instanzen des eigentlich selben Objekts…
	 *
	 * @var array
	 */
	private static $aDeletedAgencyPaymentIds = array();

	/**
	 * @var array
	 */
	public $aErrors = array();

	/**
	 * @var string
	 */
	public static $_sIdTag = 'PAYMENT_';

	/**
	 * @var array
	 */
	protected $_aCacheGetAllInquries = array();

	/**
	 * @var array
	 */
	protected $_aCacheGetAllDocuments = array();

	/**
	 * @todo: joinobjectchilds
	 * @var array
	 */
	public $aItems = array();

	/**
	 * @var array
	 */
	protected static $aDocumentStatusIcons;

	/**
	 * @return array|mixed|string
	 * @throws Exception
	 */
	public function getUsername() {
		$oUser  = Ext_Thebing_User::getInstance($this->editor_id);
		$sUser  = $oUser->name;
		return $sUser;
	}

	public function getCompany() {
		$inquiry = $this->getInquiry();

		if($inquiry) {
			return \TsAccounting\Entity\Company::searchByCombination($inquiry->getSchool(), $inquiry->getInbox());
		}

		return null;
	}

	/**
	 * @return Ext_Thebing_Inquiry_Payment_Item[]
	 */
	public function getItems() {

		return $this->getJoinedObjectChilds('items', true);

//		if($this->id <= 0) {
//			return array();
//		}
//
//		$sSql = "
//			SELECT
//				`kipi`.*
//			FROM
//				`kolumbus_inquiries_payments_items` `kipi` INNER JOIN
//				`kolumbus_inquiries_payments` `kip` ON
//					`kip`.`id` = `kipi`.`payment_id`
//			WHERE
//				`kipi`.`payment_id` = :payment_id AND
//				`kip`.`active` = 1
//		";
//
//		$aResult = (array)DB::getQueryRows($sSql, array(
//			'payment_id' => (int)$this->id)
//		);
//
//		$aPayments = array();
//		foreach($aResult as $aData){
//			$aPayments[] = Ext_Thebing_Inquiry_Payment_Item::getObjectFromArray($aData);
//		}
//
//		return $aPayments;
	}

	/**
	 * @param array $aSelectedIds
	 * @param bool|string $sAdditional
	 * @return array
	 */
	public static function buildPaymentDataArray(&$aSelectedIds, $sAdditional = false) {

		if(empty($aSelectedIds)) {
			return array();
		}

		$bGroup = false;
		$bAnyInvoice = false;
		$aInquiries = array(); /** @var Ext_TS_Inquiry[] $aInquiries */

		foreach((array)$aSelectedIds as $iInquiryId) {

			if($iInquiryId <= 0) {
				continue;
			}

			$oInquiry = Ext_TS_Inquiry::getInstance((int)$iInquiryId);

			if($oInquiry->group_id > 0) {
				$bGroup = true;
				$oGroup = Ext_Thebing_Inquiry_Group::getInstance($oInquiry->group_id);
				$aGroupInquiries = $oGroup->getInquiries(false, false);
				foreach($aGroupInquiries as $oGroupInquiry){
					$aInquiries[$oGroupInquiry->id] = $oGroupInquiry;
					// nicht selectierte gruppen auswählen
					$aSelectedIds[] = $oGroupInquiry->id;
					$bAnyInvoice = $oGroupInquiry->has_invoice ? true : $bAnyInvoice;
				}
			} else {
				$aInquiries[$oInquiry->id] = $oInquiry;
				$bAnyInvoice = (bool)$oInquiry->has_invoice;
			}

		}

		$aSelectedIds = array_unique($aSelectedIds);

		if (
			!$bGroup &&
			count($aSelectedIds) > 1
		) {
			throw new \RuntimeException('Multiple selection is not possible anymore.');
		}

		$i = 0;

		// Aufbau des Payment Arrays
		$aPaymentData = array();

		// Alle Buchungen durchgehen
		foreach ($aInquiries as $oInquiry) {

			$aPaymentData[$i] = array();
			$aPaymentData[$i]['inquiry'] = $oInquiry;
			$aPaymentData[$i]['documents'] = array();

			$sDocType = $bAnyInvoice ? 'invoice_without_proforma' : 'proforma';
			if($sAdditional == 'commission_payout') {
				$sDocType = 'creditnote';
			}

			$aDocuments = $oInquiry->getDocuments($sDocType, true, true);

			// #20413 - ältere Rechnung nach oben bringen
			$aDocuments = array_reverse($aDocuments);

			// letzte Versionen holen
			foreach((array)$aDocuments as $iKey => $oDocument) {
				/* @var Ext_Thebing_Inquiry_Document $oDocument */

				// Proforma dürfen nicht bezahlt werden wenn es danach andere Rechnungen gibt
				$mPos = strpos($oDocument->type, 'proforma');
                
				if(
                    (is_int($mPos) && $iKey != 0 ) || 
                    $oDocument->type == 'brutto_diff_special'
                ) {
					continue;
				}

				$DocArray = array();
				$DocArray['document'] = $oDocument;

				$oLastVersions = $oDocument->getLastVersion();
				if(is_object($oLastVersions)) {
					/* Items holen, dabei nach Betrag sortieren, damit negative
					 * zuerst kommen. Das ist wichtig für die Verteilung bei
					 * Gruppenbezahlung */
					$DocArray['items'] = $oLastVersions->getItemObjects(true, false);
				}
				$aPaymentData[$i]['documents'][] = $DocArray;

			}

			$i++;

		}

		return $aPaymentData;
	}

	/**
	 * @param Ext_TS_Inquiry $oInquiry
	 * @param bool|string $sAdditional
	 * @return bool
	 */
	public static function checkPDFTemplates(Ext_TS_Inquiry $oInquiry, $sAdditional = false) {

		$oClient = Ext_Thebing_Client::getInstance();

		$oCustomer = $oInquiry->getCustomer();
		$sLang = $oCustomer->getLanguage();
		$oSchool = $oInquiry->getSchool();
		$iSchoolId = $oSchool->id;
		$oInbox = $oInquiry->getInbox();
		
		// Kunden Templates
		$sTemplateType1	= 'document_invoice_customer_receipt';
		$sTemplateType2	= 'document_customer_document_payment_overview';
		$sTemplateType3	= 'document_customer_document_payment';

		// Agentur
		$sTemplateType4	= 'document_invoice_agency_receipt';
		$sTemplateType5	= 'document_agency_document_payment_overview';
		$sTemplateType6	= 'document_agency_document_payment';
		$sTemplateType7	= 'document_invoice_agency_receipt_brutto';

		// CreditNote
		$sTemplateType8 = 'document_creditnote_receipt';
		$sTemplateType9 = 'document_creditnote_document_payment';
		$sTemplateType10 = 'document_creditnote_document_payment_overview';

		// Template suchen
		$aTemplate1	= Ext_Thebing_Pdf_Template_Search::s($sTemplateType1, $sLang, $iSchoolId, $oInbox->id);
		$aTemplate2	= Ext_Thebing_Pdf_Template_Search::s($sTemplateType2, $sLang, $iSchoolId, $oInbox->id);
		$aTemplate3	= Ext_Thebing_Pdf_Template_Search::s($sTemplateType3, $sLang, $iSchoolId, $oInbox->id);

		$bCheck	= true;

		if(empty($aTemplate1) && $oClient->inquiry_payments_receipt == 1) {
			$bCheck = false;
		}
		if(empty($aTemplate2) && $oClient->inquiry_payments_overview == 1) {
			$bCheck = false;
		}
		if(empty($aTemplate3) && $oClient->inquiry_payments_invoice == 1) {
			$bCheck = false;
		}

		if(
			$bCheck &&
			$oInquiry->agency_id > 0
		) {

			// Wenn "Netto" eingestellt ist
			if($oInquiry->hasNettoPaymentMethod()) {

				// Template suchen
				$aTemplate4	= Ext_Thebing_Pdf_Template_Search::s($sTemplateType4, $sLang, $iSchoolId, $oInbox->id);
				$aTemplate5	= Ext_Thebing_Pdf_Template_Search::s($sTemplateType5, $sLang, $iSchoolId, $oInbox->id);
				$aTemplate6	= Ext_Thebing_Pdf_Template_Search::s($sTemplateType6, $sLang, $iSchoolId, $oInbox->id);
				$aTemplate7	= Ext_Thebing_Pdf_Template_Search::s($sTemplateType7, $sLang, $iSchoolId, $oInbox->id);

				if(empty($aTemplate4) && $oClient->inquiry_payments_receipt == 1) {
					$bCheck = false;
				}
				if(empty($aTemplate5) && $oClient->inquiry_payments_overview == 1) {
					$bCheck = false;
				}
				if(empty($aTemplate6) && $oClient->inquiry_payments_invoice == 1) {
					$bCheck = false;
				}
				if(empty($aTemplate7) && $oClient->inquiry_payments_invoice == 1) {
					$bCheck = false;
				}

			}

		} else if(
			$bCheck &&
			$sAdditional == 'commission_payout'
		) {

			$aTemplate8	= Ext_Thebing_Pdf_Template_Search::s($sTemplateType8, $sLang, $iSchoolId, $oInbox->id);
			$aTemplate9	= Ext_Thebing_Pdf_Template_Search::s($sTemplateType9, $sLang, $iSchoolId, $oInbox->id);
			$aTemplate10 = Ext_Thebing_Pdf_Template_Search::s($sTemplateType10, $sLang, $iSchoolId, $oInbox->id);

			if(empty($aTemplate8) && $oClient->inquiry_payments_creditnote_receipt == 1) {
				$bCheck = false;
			}
			if(empty($aTemplate9) && $oClient->inquiry_payments_creditnote == 1) {
				$bCheck = false;
			}
			if(empty($aTemplate10) && $oClient->inquiry_payments_creditnote_overview == 1) {
				$bCheck = false;
			}

		}

		return $bCheck;
	}

	protected static $fAmountOverpay		= 0;
	protected static $iSchoolOverpay		= 0;
	protected static $iCurrencyOverpay		= 0;

	/**
	 * Liefert das Dialogobjekt des Payment Dialoges (statisch da nicht payment bezogen)
	 *
	 * @param Ext_Gui2 $oGui
	 * @param array $aSelectedIds
	 * @param array $aSelectedIdsParentGui
	 * @param string $sAccess
	 * @param bool|string $sAdditional
	 * @return mixed
	 */
	public static function getDialog($oGui, &$aSelectedIds, $aSelectedIdsParentGui = array(), $sAccess = '', $sAdditional = false) {

		if (!in_array($sAdditional, ['inquiry', 'agency_payment', 'commission_payout'])) {
			throw new RuntimeException('Wrong type for payment dialog');
		}

		if($sAdditional === 'agency_payment') {
			if(empty($aSelectedIdsParentGui)) {
				throw new RuntimeException('No agency payment id for agency payment');
			}

			$iAgencyPaymentId = reset($aSelectedIdsParentGui);
			$oAgencyPayment = Ext_Thebing_Agency_Payment::getInstance($iAgencyPaymentId);
			$bAgencyPayments = true;
		} else {
			$bAgencyPayments = false;
		}

		// Datenarrey für Paymentdialog
		// aSelectedIds per ref. ändern wenn Gruppe
		$aOriginalSelectedIds = $aSelectedIds;
		$aPaymentData = self::buildPaymentDataArray($aSelectedIds, $sAdditional);

		// Falls GUI mal rumbuggt, leere IDs abfangen
		if(empty($aSelectedIds)) {
			$sError = L10N::t('Bitte markieren Sie zum Bezahlen einen Eintrag.', $oGui->gui_description);
			return $oGui->getDataObject()->getErrorDialog($sError);
		}

		$iLastGroupId = null;
		$bAnyInvoice = $bAnyProforma = false;
		foreach($aSelectedIds as $iInquiryId) {
			$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);
			$bAnyProforma = $oInquiry->has_proforma ? true : $bAnyProforma;
			$bAnyInvoice = $oInquiry->has_invoice ? true : $bAnyInvoice;

			/*
			 * Es können bei Mehrfachauswahl entweder mehrere Buchungen ohne Gruppe oder
			 * nur eine Gruppe ausgewählt werden. Das wird zwar durch das Icon abgefangen,
			 * aber falls updateIcons zu langsam ist, kann man trotzdem das Icon noch anklicken.
			 */
			if(
				$iLastGroupId !== null &&
				$iLastGroupId != $oInquiry->group_id
			) {
				return $oGui->getDataObject()->getErrorDialog($oGui->t('Es können nur entweder mehrere einzelne Buchungen oder einzelne Gruppen bezahlt werden.'));
			}

			$iLastGroupId = $oInquiry->group_id;

			// Bei CN-Ausbezahlung dürfen nur der CNs zugewiesenen Überbezahlungen berücksichtigt werden
			// Overpayments aller selektierten IDs berechnen (aus historischen Gründen eine statische Variable)
			$aOverpayments = $oInquiry->getOverpayments($sAdditional === 'commission_payout' ? 'creditnote' : 'invoice');
			foreach($aOverpayments as $oOverpayment) {

				$fAmountOverpay = $oOverpayment->amount_inquiry;
				if($sAdditional === 'commission_payout') {
					// Überraschung: Weil Beträge im Dialog andersrum eingegeben werden, muss auch die Überbezhahlung invertiert werden
					$fAmountOverpay *= -1;
				}

				self::$fAmountOverpay += $fAmountOverpay;
			}
		}

		$oSchool = $oInquiry->getSchool();

		self::$iSchoolOverpay = $oSchool->id;
		self::$iCurrencyOverpay	= $oInquiry->getCurrency();

		## START Templates Prüfen!
			$bCheck = true;
			if($bCheck) {
				foreach($aPaymentData as $aData) {
					$oInquiry = $aData['inquiry'];
					$bCheck = self::checkPDFTemplates($oInquiry, $sAdditional);
					if(!$bCheck) {
						$sError = L10N::t('Es fehlen PDF Vorlagen!', $oGui->gui_description);
						break;
					}
				}
			}
			if(!$bCheck) {
				return $oGui->_oData->getErrorDialog($sError);
			}
		## ENDE template prüfung

		// Proforma umwandeln
		if (
			!\System::d('ts_payments_without_invoice') &&
			!$bAnyInvoice
		) {
			if (!$bAnyProforma) {
				return $oGui->getDataObject()->getErrorDialog($oGui->t('Der Schüler besitzt weder eine Proforma noch eine Rechnung.'));
			}

			$aCustomers = [];
			$aProformaDocuments = [];
			foreach ($aSelectedIds as $iInquiryId) {
				$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);
				if (($oDocument = $oInquiry->getDocuments('invoice_proforma', false, true))) {
					$aCustomers[] = $oInquiry->getCustomer()->getName();
					$aProformaDocuments[] = $oDocument->id;
				}
			}

			$oDialog = $oGui->createDialog($oGui->t('Proforma umwandeln'));
			$oDialog->width = 500;
			$oDialog->height = 300;
			$oDialog->save_button  = false;

			$oDialog->setElement($oDialog->create('div')->setElement($oGui->t('Für folgende Schüler fehlen Rechnungen') . ': '.join(', ', $aCustomers)));
			$oDialog->aButtons = [
				[
					'label' => $oGui->t('Umwandeln'),
					'task' => 'request',
					'action' => 'convertProformaDocument',
					'request_data' => '&'.implode('&', [
						...array_map(fn(int $iDocumentId) => 'document_ids[]=' . $iDocumentId, $aProformaDocuments),
						// Die Umwandlung wurde über den Bezahlen-Button gestartet
						'initiated_by=payment_dialog'
					])
				]
			];

			return $oDialog;
		}

		$oDialog = $oGui->createDialog($oGui->t('Bezahlen'), $oGui->t('Bezahlen'), $oGui->t('Bezahlen'));
		$oDialog->sDialogIDTag	= self::$_sIdTag;

		$oPaymentTab = $oDialog->createTab(L10N::t('Bezahlen', $oGui->gui_description));
		$oPaymentTab->access = 'thebing_invoice_enter_payments';
		$oPaymentTab->class = 'tab_payments_payment';
//		$oPaymentTab->aOptions['section'] = 'inquiries_payments';

		$oHistoryTab = $oDialog->createTab(L10N::t('Historie', $oGui->gui_description));
		$oHistoryTab->access = 'thebing_invoice_enter_payments_history';
		$oHistoryTab->class = 'tab_payments_history';

		$sOverpayTabRight = 'thebing_invoice_enter_payments';
		if($sAdditional === 'commission_payout') {
			$sOverpayTabRight = 'thebing_accounting_provision_enter_payments';
			$oPaymentTab->access = 'thebing_accounting_provision_enter_payments';
			$oHistoryTab->access = 'thebing_accounting_provision_payment_history';
		}

		// Oberpayment-Tab darf nur mit normalen Bezahlen-Tab existieren
		if(
			$sAdditional !== 'agency_payment' &&
			count($aOriginalSelectedIds) === 1 && // Tab funktioniert bei Mehrfachauswahl nicht
			Ext_Thebing_Access::hasRight($sOverpayTabRight) &&
			self::$fAmountOverpay > 0
		) {
			$oOverpayTab = $oDialog->createTab(L10N::t('Überbezahlung', $oGui->gui_description));
			$oOverpayTab->access = $sOverpayTabRight;
			$oOverpayTab->class = 'tab_payments_overpayment';
		}

		if($bAgencyPayments) {

			$fFreeAmount = round($oAgencyPayment->getOpenAmount(), 2);

			/* Mehrere Schüler mit verschiedenen Währungen darf eh nicht bezahlt werden, wurde in der
			 * Ext_Thebing_Gui2_Data abgedeckt, weil unsere jetzige Struktur das nicht zulässt, schon
			 * wegen der Bezahlmethode nicht... */

			$iCurrencyInquiry = $oInquiry->getCurrency();
			$iCurrencyAgencyAmount = $oAgencyPayment->amount_currency;
			
			$fFreeAmount = Ext_Thebing_Format::ConvertAmount($fFreeAmount, $iCurrencyAgencyAmount, $iCurrencyInquiry);

			// Offener Betrag der ausgewählten Agenturzahlung
			$oDialog->setOptionalAjaxData('payment_amount_available', $fFreeAmount);

			$aPaymentDialogData = array();
			$aPaymentDialogData['data']['method']['value'] = $oAgencyPayment->method_id;
			$aPaymentDialogData['data']['method']['readonly'] = 1;
			$aPaymentDialogData['data']['comment']['value'] = $oAgencyPayment->comment;
			$aPaymentDialogData['data']['comment']['readonly'] = 1;
			$aPaymentDialogData['data']['date']['value'] = $oAgencyPayment->date;
			$aPaymentDialogData['data']['date']['readonly'] = 1;

		}

		$aPaymentDialogData['inquries'] = $aPaymentData;
		$aPaymentDialogData['oAgencyPayment'] = $oAgencyPayment;
		$aPaymentDialogData['multiple_inquiries_selected'] = count($aOriginalSelectedIds) > 1;

		// Kontext, in welchem der Dialog benutzt wird, übergeben
		$oDialog->setOptionalAjaxData('payment_dialog_type', $sAdditional);
		$oDialog->setOptionalAjaxData('multiple_inquiries_selected', $aPaymentDialogData['multiple_inquiries_selected']);

		$oPaymentTab->setElement(self::getPaymentDialog($aPaymentDialogData, $oDialog, $bAgencyPayments, $oGui->gui_description, $sAccess, $sAdditional));
		$oHistoryTab->setElement(self::getOverviewDialog($aPaymentData, $aSelectedIds, $oGui, $aOriginalSelectedIds, $sAdditional));

		$oDialog->setElement($oPaymentTab);

		if(isset($oOverpayTab)) {
			$oDialog->setElement($oOverpayTab);
		}

		$oDialog->setElement($oHistoryTab);

		return $oDialog;
	}

	/**
	 * Liefert Tab Inhalt - Payment
	 *
	 * @param array $aPaymentData
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param bool $bAgencyPayments
	 * @param string $sDescription
	 * @param string $sAccess
	 * @param bool|string $sAdditional
	 * @return Ext_Gui2_Html_Div
	 * @throws Exception
	 */
	public static function getPaymentDialog($aPaymentData, $oDialog, $bAgencyPayments, $sDescription, $sAccess = '', $sAdditional = false) {

		$oData = $oDialog->oGui->getDataObject(); /** @var Ext_TS_Inquiry_Index_Gui2_Data $oData */
		$sDescription = Ext_Thebing_Inquiry_Payment::TRANSLATION_PATH;
		$aData = $aPaymentData['data'];
		$oAgencyPayment = $aPaymentData['oAgencyPayment'];

		if(!is_object($aPaymentData['inquries'][0]['inquiry'])){
			return false;
		}

		$oSchoolForFormat = Ext_Thebing_Client::getFirstSchool($sAccess);

		$oDateFormat = new Ext_Thebing_Gui2_Format_Date(false, $oSchoolForFormat->id);
		$aFormatData = array('school_id' => $oSchoolForFormat->id);
		$oFirstInquiry = $aPaymentData['inquries'][0]['inquiry']; /** @var Ext_TS_Inquiry $oFirstInquiry */

		// TODO Funktioniert das hier in den Agenturzahlungen unter All Schools?
		$aPaymentMethod = \Ext_Thebing_Admin_Payment::getPaymentMethods(true, [$oFirstInquiry->getSchool()->id]);
		$aPaymentMethod = Ext_Thebing_Util::addEmptyItem($aPaymentMethod);
		$aPaymentType = self::getTypeOptions();
		$aSendRec = self::getSenderOptions();

		## START Vorbelegung

			$sCurrentDate	= date('Y-m-d');
			$iMethod		= 0;
			$iType			= 0;
			$sFrom			= 'customer';
			$sTo			= 'customer';
			$sComment		= '';

			if($sAdditional == 'commission_payout') {
				unset(
					$aPaymentType[1],
					$aPaymentType[2],
					$aPaymentType[3],
					$aSendRec['customer']
				);
				$iType = 4;
			} else {
				if($bAgencyPayments) {
					unset($aPaymentType[3]);
				}
				unset($aPaymentType[4]);
				unset($aPaymentType[5]);
			}

			$iCurrentDateReadonly	= 0;
			$iMethodReadonly		= 0;
			$iTypeReadonly			= 0;
			$iFromReadonly			= 0;
			$iToReadonly			= 0;
			$iCommentReadonly		= 0;

			if(!empty($aData['date'])){
				if(!empty($aData['date']['value'])){
					$sCurrentDate = $aData['date']['value'];
				}
				if(!empty($aData['date']['readonly'])){
					$iCurrentDateReadonly = $aData['date']['readonly'];
				}
			}

			if(!empty($aData['method'])){
				if(!empty($aData['method']['value'])){
					$iMethod = $aData['method']['value'];
				}
				if(!empty($aData['method']['readonly'])){
					$iMethodReadonly = $aData['method']['readonly'];
				}
			}

			if(!empty($aData['type'])){
				if(!empty($aData['type']['value'])){
					$iType = $aData['type']['value'];
				}
				if(!empty($aData['type']['readonly'])){
					$iTypeReadonly = $aData['type']['readonly'];
				}
			}

			if(!empty($aData['from'])){
				if(!empty($aData['from']['value'])){
					$sFrom = $aData['from']['value'];
				}
				if(!empty($aData['from']['readonly'])){
					$iFromReadonly = $aData['from']['readonly'];
				}
			}

			if(!empty($aData['to'])){
				if(!empty($aData['to']['value'])){
					$sTo = $aData['to']['value'];
				}
				if(!empty($aData['to']['readonly'])){
					$iToReadonly = $aData['to']['readonly'];
				}
			}

			if(!empty($aData['comment'])){
				if(!empty($aData['comment']['value'])){
					$sComment = $aData['comment']['value'];
				}
				if(!empty($aData['comment']['readonly'])){
					$iCommentReadonly = $aData['comment']['readonly'];
				}
			}

			$sCurrentDateFormatted = $oDateFormat->format($sCurrentDate, $oDummy, $aFormatData);


		## ENDE

		$oDiv				= new Ext_Gui2_Html_Div();

		$oDivDataContainer	= new Ext_Gui2_Html_Div();
		$oDivDataContainer->class = 'payment_data';

		$sDisabled = '';
		$sDisabledClass = '';
		if($iCurrentDateReadonly){
			$sDisabled = 'disabled';
			$sDisabledClass = 'readonly';
			$oHidden = $oDialog->createSaveField('hidden', array('value'=> $sCurrentDate, 'db_column' => 'payment][date'));
			$oDivDataContainer->setElement($oHidden);
			$aDateData['readonly'] = 'readonly';
		}

		$oRow = $oDialog->createRow(L10N::t('Geldeingang', $sDescription), 'calendar', array('value'=> $sCurrentDateFormatted, 'db_column' => 'payment][date', 'format' => $oDateFormat, 'required' => 1, 'disabled' => $sDisabled, 'class' => $sDisabledClass, 'info_icon_key' => 'inquiry_payment_date'));
		$oDivDataContainer->setElement($oRow);

		$sDisabled	= '';
		$sDisabledClass = '';
		if($iMethodReadonly){
			$sDisabled = 'disabled';
			$sDisabledClass = 'readonly';
			$oHidden = $oDialog->createSaveField('hidden', array('value'=> $iMethod, 'db_column' => 'payment][method_id'));
			$oDivDataContainer->setElement($oHidden);
		}

		$oRow = $oDialog->createRow(L10N::t('Methode', $sDescription), 'select', array('default_value' => $iMethod, 'db_column' => 'payment][method_id', 'select_options' => $aPaymentMethod, 'disabled' => $sDisabled, 'required' => true, 'class' => $sDisabledClass, 'info_icon_key' => 'inquiry_payment_method'));
		$oDivDataContainer->setElement($oRow);

		$sDisabled	= '';
		$sDisabledClass = '';
		if($iTypeReadonly){
			$sDisabled = 'disabled';
			$sDisabledClass = 'readonly';
			$oHidden = $oDialog->createSaveField('hidden', array('value'=> $iType, 'db_column' => 'payment][type_id'));
			$oDivDataContainer->setElement($oHidden);
		}

		$oRow = $oDialog->createRow(L10N::t('Art', $sDescription), 'select', array('default_value' => $iType, 'db_column' => 'payment][type_id', 'select_options' => $aPaymentType, 'disabled' => $sDisabled, 'class' => $sDisabledClass, 'info_icon_key' => 'inquiry_payment_type'));
		$oDivDataContainer->setElement($oRow);

		$sDisabled	= '';
		$sDisabledClass = '';
		if($iFromReadonly){
			$sDisabled = 'disabled';
			$sDisabledClass = 'readonly';
			$oHidden = $oDialog->createSaveField('hidden', array('value'=> $sFrom, 'db_column' => 'payment][sender'));
			$oDivDataContainer->setElement($oHidden);
		}

		$oRow = $oDialog->createRow(L10N::t('Bezahlt von', $sDescription), 'select', array('default_value' => $sFrom, 'db_column' => 'payment][sender', 'select_options' => $aSendRec, 'disabled' => $sDisabled, 'class' => $sDisabledClass, 'info_icon_key' => 'inquiry_payment_sender'));
		$oDivDataContainer->setElement($oRow);

		$sDisabled	= '';
		$sDisabledClass = '';
		if($iToReadonly){
			$sDisabled = 'disabled';
			$sDisabledClass = 'readonly';
			$oHidden = $oDialog->createSaveField('hidden', array('value'=> $sTo, 'db_column' => 'payment][receiver'));
			$oDivDataContainer->setElement($oHidden);
		}

		$oRow = $oDialog->createRow(L10N::t('Bezahlt an', $sDescription), 'select', array('default_value' => $sTo, 'db_column' => 'payment][receiver', 'select_options' => $aSendRec, 'disabled' => $sDisabled, 'class' => $sDisabledClass, 'info_icon_key' => 'inquiry_payment_receiver'));
		$oDivDataContainer->setElement($oRow);

		$sDisabled	= '';
		$sDisabledClass = '';
		if($iCommentReadonly){
			$sDisabled = 'disabled';
			$sDisabledClass = 'readonly';
			$oHidden = $oDialog->createSaveField('hidden', array('value'=> $sComment, 'db_column' => 'payment][comment'));
			$oDivDataContainer->setElement($oHidden);
		}

		$oRow = $oDialog->createRow(L10N::t('Bemerkung', $sDescription), 'textarea', array('default_value' => $sComment, 'db_column' => 'payment][comment', 'disabled' => $sDisabled, 'class' => $sDisabledClass, 'info_icon_key' => 'inquiry_payment_comment'));
		$oDivDataContainer->setElement($oRow);

		// Flex-Felder manuell setzen, da die ansonsten unter der Tabelle auftauchen würden und zur Inquiry gehören würden
		if($sAdditional !== 'commission_payout') {
			$oDivDataContainer->setElement($oData->getFlexEditDataHTML($oDialog, ['inquiries_payments'], new Ext_Thebing_Inquiry_Payment(), 0, 1, 'save[payment_flex]'));
		}

		$oDiv->setElement($oDivDataContainer);

		// Überbezahlungsfelder

		$oDivOverDataContainer	= new Ext_Gui2_Html_Div();
		$oDivOverDataContainer->class = 'overpayment_data';

		$oRow = $oDialog->createRow(L10N::t('Überbezahlungsgrund', $sDescription), 'textarea', array('default_value' => $sComment, 'db_column' => 'overpay][comment', 'disabled' => $sDisabled, 'class' => $sDisabledClass));
		$oDivOverDataContainer->setElement($oRow);

		$oDiv->setElement($oDivOverDataContainer);

		// Benötigt für Save-Methode (saveDialogPaymentTab() oder saveDialogOverpaymentTab())
		$oHidden = new Ext_Gui2_Html_Input();
		$oHidden->type = 'hidden';
		$oHidden->name = $oHidden->id = 'payment_type';
		$oHidden->value = 'payment';
		$oDiv->setElement($oHidden);

		// Tabelle mit Positionen wird hier generiert
		$oTableGenerator = new Ext_Thebing_Inquiry_Payment_Gui2_Helper_Table($oDialog, $aPaymentData, $sAdditional);
		$oTableGenerator->setStaticVariables(self::$fAmountOverpay, self::$iCurrencyOverpay, self::$iSchoolOverpay);

		// Wenn vorhanden, Agenturzahlung setzen
		// Das generiert auch die ganzen Creditnote-Zeilen
		if($bAgencyPayments) {
			$oTableGenerator->setAgencyPayment($oAgencyPayment);
		}

		$oDivItems = $oTableGenerator->render();
		$oDiv->setElement($oDivItems);

		// Konfiguration für das JS, wann welche Sender/Empfänger verfügbar sind
		$oDialog->setOptionalAjaxData('type_select_config', self::getTypeOptionsConfig($bAgencyPayments));

		// Faktor zum Umrechnen der Schulwährung setzen (vorausgewähltes Datum)
		// Da man nicht mehrere Währungen auswählen kann, funktioniert das auch mit nur einem Faktor
		$iCurrencyFromId = $oFirstInquiry->getCurrency();
		$iCurrencyToId = $oFirstInquiry->getSchool()->getCurrency();
		$oDialog->setOptionalAjaxData('currency_from', $iCurrencyFromId);
		$oDialog->setOptionalAjaxData('currency_to', $iCurrencyToId);
		if($iCurrencyFromId == $iCurrencyToId) {
			$oDialog->setOptionalAjaxData('school_currency_factor', 1);
		} else {
			$oCurrency = Ext_Thebing_Currency::getInstance($oFirstInquiry->getCurrency());
			$oDialog->setOptionalAjaxData('school_currency_factor', $oCurrency->getConversionFactor($oFirstInquiry->getSchool()->getCurrency(), $sCurrentDate));
		}

		return $oDiv;

	}

	/**
	 * Liefert Tab Inhalt - History
	 *
	 * @param array $aInquiries
	 * @param array $aSelectedIds
	 * @param Ext_Gui2 $oGui
	 * @param array $aOriginalSelectedIds
	 * @param bool|string $sAdditional
	 * @return string
	 * @throws Exception
	 */
	public static function getOverviewDialog($aInquiries, $aSelectedIds, &$oGui, $aOriginalSelectedIds, $sAdditional = false) {

		$sDescription	= $oGui->gui_description;

		$aInquiryIds = array();

		foreach($aInquiries as $aInquirieData){
			$oInquiry = $aInquirieData['inquiry'];
			$aInquiryIds[] = $oInquiry->id;
		}

		$iFirstInquiry = reset($aSelectedIds);
		$oFirstInquiry = Ext_TS_Inquiry::getInstance($iFirstInquiry);

		$oContainer = new Ext_Gui2_Html_Div();
		$oH3 = new Ext_Gui2_Html_H4();
		$oH3->setElement(L10N::t('Zahlungsübersicht', $sDescription));
		$oContainer->setElement($oH3);
		// Obere Liste

		$aPayments = self::searchPaymentsByInquiryArray($aInquiryIds, $sAdditional);

		$sDocumentNrLabel = 'R.-Nr.';
		$sDocumentDateLabel = 'R.-Datum';
		$sDocumentOverviewLabel = 'Rechnungsübersicht';
		if($sAdditional == 'commission_payout') {
			$sDocumentNrLabel = 'Gu.-Nr.';
			$sDocumentDateLabel = 'Gu.-Datum';
			$sDocumentOverviewLabel = 'Gutschriftsübersicht';
		}

		$aHeader = array();
		$aHeader['document_number']['l10n'] = L10N::t($sDocumentNrLabel, $sDescription);
		$aHeader['document_number']['width']= '100px';
		$aHeader['receipt_number']['l10n'] = L10N::t('B.-Nr', $sDescription);
		$aHeader['receipt_number']['width']= '100px';
		$aHeader['date']['l10n'] = L10N::t('Erstellt / Quittungsdatum', $sDescription);
		$aHeader['date']['width'] = '100px';
		$aHeader['comment']['l10n'] = L10N::t('Bemerkung', $sDescription);
		$aHeader['comment']['width'] = 'auto';
		$aHeader['methode']['l10n'] = L10N::t('Methode', $sDescription);
		$aHeader['methode']['width'] = '100px';
		$aHeader['sender']['l10n'] = L10N::t('Gezahlt von', $sDescription);
		$aHeader['sender']['width'] = '100px';
		$aHeader['type']['l10n'] = L10N::t('type', $sDescription);
		$aHeader['type']['width'] = '100px';
		$aHeader['summ']['l10n'] = L10N::t('Summe', $sDescription);
		$aHeader['summ']['width'] = '100px';
		$aHeader['user']['l10n'] = L10N::t('Erstellt von', $sDescription);
		$aHeader['user']['width'] = '80px';

		$sAccess = 'thebing_invoice_payments_delete';
		if($sAdditional === 'commission_payout') {
			$sAccess = 'thebing_accounting_provision_delete';
		}
		foreach($aPayments as $aPayment) {
			if(Ext_Thebing_Access::hasRight($sAccess, $aPayment['school_id'])) {
				$aHeader['action']['l10n'] = L10N::t('L', $sDescription);
				$aHeader['action']['width'] = '20px';
				break;
			}
		}

		if($sAdditional != 'commission_payout') {
			$aHeader['brutto']['l10n'] = L10N::t('K', $sDescription); // L10N::t('Brutto', $sDescription);
			$aHeader['brutto']['width'] = '20px';
			$aHeader['netto']['l10n'] = L10N::t('A', $sDescription);; // L10N::t('Netto', $sDescription);
			$aHeader['netto']['width'] = '20px';
		}

		$oTable = new Ext_Gui2_Html_Table();
		//$oTable->style = 'width:100%;border-spacing:none;border-collapse:collapse;';
		$oTable->class = 'table tblDocumentTable';

		//Header
		$oTr = new Ext_Gui2_Html_Table_Tr();

		foreach((array)$aHeader as $sKey => $aData){
			$oTh = new Ext_Gui2_Html_Table_Tr_Th();
				$oTh->setElement($aData['l10n']);
				$oTh->style = "width:".$aData['width'];
			$oTr->setElement($oTh);
		}

		$oTable->setElement($oTr);

		// Body
		foreach ((array)$aPayments as $aPayment){

			$oPayment = Ext_Thebing_Inquiry_Payment::getInstance($aPayment['id']);
			$oInquiry = Ext_TS_Inquiry::getInstance($aPayment['first_inquiry_id']);

			$iSchoolId		= $aPayment['school_id'];
			$iCurrencyId	= $aPayment['currency_id'];

			$oFormat = new Ext_Thebing_Gui2_Format_Date();
			$oTemp = null;
			$aFormatData = array('school_id' => $iSchoolId);

			/**
			 * Array bauen für Bezahlbelege (beide Typen, Redundanz vermeiden)
			 * @param $mPaymentDocuments
			 * @return array
			 */
			$oBuildReceiptArray = function($mPaymentDocuments) {
				$aReturn = array();

				foreach($mPaymentDocuments as $oPaymentDocument) {
					$oPaymentVersion = $oPaymentDocument->getLastVersion();
					$oTemplate = Ext_Thebing_Pdf_Template::getInstance($oPaymentVersion->template_id);

					$aReturn[] = array(
						'document' => $oPaymentDocument,
						'template' => $oTemplate,
						'version' => $oPaymentVersion
					);
				}

				return $aReturn;
			};

			// Kunden-Quittungen
			$aCustomerPaymentDocuments = $oPayment->getReceipts($oInquiry, 'receipt_customer');
			$aCustomerPaymentDocuments = $oBuildReceiptArray($aCustomerPaymentDocuments);

			//todo: weitere PDFs für die 3 Zahlungsarten (vor Abreise,vor Ort, refund)

			// Agentur-Quittungen
			$aAgencyPaymentDocuments = array();
			if($oInquiry->agency_id > 0) {
				$aAgencyPaymentDocuments = $oPayment->getReceipts($oInquiry, 'receipt_agency');
				$aAgencyPaymentDocuments = $oBuildReceiptArray($aAgencyPaymentDocuments);
			}

			$oTr = new Ext_Gui2_Html_Table_Tr();
			if(System::d('debugmode') == 2) {
				$oTr->setDataAttribute('payment-id', $oPayment->id);
			}
			foreach((array)$aHeader as $sKey => $aData){

				$mValue = '';
				$sClass = '';

				switch ($sKey) {

					case 'document_number':
						$aTemp = explode(',', $aPayment['document_number']);
						$mValue = implode('<br/>', $aTemp);
						break;
					case 'receipt_number':
						$mValue = collect($oPayment->getJoinTableObjects(Ext_Thebing_Inquiry_Payment::JOINTABLE_RECEIPTS))
							->map(fn(Ext_Thebing_Inquiry_Document $d) => $d->document_number)
							->unique()
							->join('<br>');
						break;
					case 'created':
					case 'date':
						if($oPayment->created > 0) {
							$mValue .= Ext_Thebing_Format::LocalDate($oPayment->created, $iSchoolId);
						} else {
							$mValue .= L10N::t('Unbekannt', $sDescription);
						}
						$mValue .= ' / ';
						if($oPayment->date > 0) {
							$mValue .= $oFormat->format($oPayment->date, $oTemp, $aFormatData);
						} else {
							$mValue .= L10N::t('Unbekannt', $sDescription);
						}
						break;

					case 'comment':
						$mValue = nl2br($oPayment->comment);
						if ($oPayment->status === self::STATUS_PENDING) {
							$mValue .= '<br><strong>'.$oGui->t('Diese Zahlung steht noch aus.').'</strong>';
						}

						break;

					case 'methode':
						$mValue = $oPayment->getMethod()->getName();
						break;

					case 'sender':
						$mValue = self::getSenderOptions()[$oPayment->sender];
						break;

					case 'summ':
						$fAmount = $oPayment->getAmount();
						if($sAdditional === 'commission_payout') {
							$fAmount *= -1;
						}
						$mValue = Ext_Thebing_Format::Number($fAmount, $iCurrencyId, $iSchoolId);
						$sClass = 'amount';
						break;

					case 'user':
						$mValue = $oPayment->getUsername();
						break;

					case 'action':
						$oDiv = new Ext_Gui2_Html_Div();

						$sAccess = 'thebing_invoice_payments_delete';
						if($sAdditional === 'commission_payout') {
							$sAccess = 'thebing_accounting_provision_delete';
						}

						/*
						 * Beim Löschen der einzelnen Schülerzahlung muss das Recht für die Schule des Schülers überprüft werden,
						 * egal ob 'all_schools' Ansicht oder nicht.
						 */
						if(
							Ext_Thebing_Access::hasRight($sAccess, $iSchoolId) &&
							$oPayment->isReleased() === false
						) {

							$aCreditNotePayments = $oPayment->getJoinTableObjects('creditnote_payments');
							$aPaymentCreditNotes = $oPayment->getJoinTableObjects('payment_creditnotes');

							$sJavaScriptFunction = 'deleteAgencyPaymentAndCreditnote';
							if(!empty($aCreditNotePayments)) {
								$sParam = ', \'creditnote_payments\', \''.self::getDeletePaymentMessage($oPayment, $aCreditNotePayments, 'creditnote_payments').'\'';
							} elseif(!empty($aPaymentCreditNotes)) {
								$sParam = ', \'payment_creditnotes\', \''.self::getDeletePaymentMessage($oPayment, $aPaymentCreditNotes, 'payment_creditnotes').'\'';
							} else {
								$sParam = '';
								$sJavaScriptFunction = 'deletePayment';
							}

							$oIcon = new Ext_Gui2_Html_I();
							$oIcon->class = 'fa '.Ext_Thebing_Util::getIcon('delete');
							$oIcon->title = L10N::t('Löschen', $sDescription);
							$oIcon->style = 'cursor:pointer; padding-right:3px;';
							$oIcon->onclick = 'aGUI[\''.$oGui->hash.'\'].'.$sJavaScriptFunction.'('.(int)$oPayment->id.', \''.$sAdditional.'\''.$sParam.');';
							$oDiv->setElement($oIcon);

							$mValue = $oDiv;

						} else {
							$mValue = '';
						}

						break;

					case 'brutto': // Kundenbeleg
						$oDiv = new Ext_Gui2_Html_Div();

						foreach((array)$aCustomerPaymentDocuments as $aCustomerPaymentDocument) {
							$oImg = self::createOverviewPdfIcon($aCustomerPaymentDocument['document'], $aCustomerPaymentDocument['version'], $oDiv);
							$oImg->title = L10N::t('Kundenbeleg', $sDescription).' - '.$aCustomerPaymentDocument['template']->name;
						}

						$mValue = $oDiv;
						break;

					case 'netto': // Agenturbeleg
						$oDiv = new Ext_Gui2_Html_Div();

						foreach((array)$aAgencyPaymentDocuments as $aAgencyPaymentDocument) {
							$oImg = self::createOverviewPdfIcon($aAgencyPaymentDocument['document'], $aAgencyPaymentDocument['version'], $oDiv);
							$oImg->title = L10N::t('Agenturbeleg', $sDescription).' - '.$aAgencyPaymentDocument['template']->name;
						}

						$mValue = $oDiv;
						break;

					case 'type':
						$aPaymentType	= self::getTypeOptions();
						$mValue = $aPaymentType[$oPayment->type_id];
						break;

				}

				$oTd = new Ext_Gui2_Html_Table_Tr_Td();

				if(is_array($mValue)){

					foreach($mValue as $mVal){
						$oTd->setElement($mVal);
					}

				} elseif($mValue !== null) {
					$oTd->setElement($mValue);
				}

				$oTd->class = $sClass;

				 $oTr->setElement($oTd);

			}
			$oTable->setElement($oTr);

		}

		$oContainer->setElement($oTable);

		$oH3 = new Ext_Gui2_Html_H4();
		$oH3->style = 'padding-right: 7px';
		$oH3->setElement(L10N::t($sDocumentOverviewLabel, $sDescription));

		// Checken ob Übersichts PDFs angezeigt werden dürfen
		$bShowOverview = false;
		$oInquiryOriginal = Ext_TS_Inquiry::getInstance(reset($aOriginalSelectedIds));

		if(count($aSelectedIds) == 1){
			// Nur suchen wenn die Übersicht nur EINEN Kunden darstellt
			$bShowOverview = true;
		}elseif(
			$oInquiryOriginal->id > 0 &&
			$oInquiryOriginal->group_id > 0
		){
			// Bei Gruppen auch Übersicht anzeigen
			$bShowOverview = true;
		}
		///////////////////////////////////////////////////

		// Bezahlbelege über alle Rechnungen (Icons in Header-Zeile)
		if($bShowOverview) {

			if($sAdditional !== 'commission_payout') {
				$iCustomerIconPadding = 30;

				// Wenn Agentur dann bledne Agentur PDF Icon ein
				if($oInquiry->agency_id > 0) {

					$oVersionAgency = $oDocAgency = null;
					foreach($aInquiryIds as $inquiryId) {
						$overviewInquiry = Ext_TS_Inquiry::getInstance($inquiryId);
						$oDocAgency = $overviewInquiry->getDocuments('document_payment_overview_agency', false, true);
						if ($oDocAgency) {
							break;
						}
					}

					if($oDocAgency) {
						$oVersionAgency = $oDocAgency->getLastVersion();
					}
					
					$oImg = self::createOverviewPdfIcon($oDocAgency, $oVersionAgency, $oH3);
					$oImg->title = L10N::t('PDF Agentur Übersicht', $sDescription);
					$oImg->style .= 'float:right;';
					$iCustomerIconPadding = 10;
				}

				$oVersionCustomer = $oDocCustomer = null;
				foreach($aInquiryIds as $inquiryId) {
					$overviewInquiry = Ext_TS_Inquiry::getInstance($inquiryId);
					$oDocCustomer = $overviewInquiry->getDocuments('document_payment_overview_customer', false, true);
					if ($oDocCustomer) {
						break;
					}
				}
				
				if ($oDocCustomer) {
					$oVersionCustomer = $oDocCustomer->getLastVersion();
				}
				
				$oImg = self::createOverviewPdfIcon($oDocCustomer, $oVersionCustomer, $oH3);
				$oImg->title = L10N::t('PDF Kunde Übersicht', $sDescription);
				$oImg->style .= 'float:right; margin-right:'.$iCustomerIconPadding.'px !important;'; // 30px 10px

			}

		}

		$oContainer->setElement($oH3);

		// Untere Liste

		$aHeader = array();
		$aHeader['document_number']['l10n'] = L10N::t($sDocumentNrLabel, $sDescription);
		$aHeader['document_number']['width']= '100px';
		$aHeader['document_date']['l10n']	= L10N::t($sDocumentDateLabel, $sDescription);
		$aHeader['document_date']['width']	= '100px';
		$aHeader['customer']['l10n']		= L10N::t('Kunde', $sDescription);
		$aHeader['customer']['width']		= 'auto';
		$aHeader['total']['l10n']			= L10N::t('Total', $sDescription);
		$aHeader['total']['width']			= '100px';
		$aHeader['paid']['l10n']			= L10N::t('Bezahlt', $sDescription);
		$aHeader['paid']['width']			= '100px';
		$aHeader['balance']['l10n']			= L10N::t('Offen', $sDescription);
		$aHeader['balance']['width']		= '100px';

		if($sAdditional != 'commission_payout') {
			$aHeader['brutto']['l10n'] = L10N::t('K', $sDescription);//L10N::t('Brutto', $sDescription);
			$aHeader['brutto']['width'] = '20px';
			$aHeader['netto']['l10n'] = L10N::t('A', $sDescription);//L10N::t('Netto', $sDescription);
			$aHeader['netto']['width'] = '20px';
		}

		$oTable = new Ext_Gui2_Html_Table();
		//$oTable->style = 'width:100%;border-spacing:none;border-collapse:collapse;';
		$oTable->class = 'table tblDocumentTable';

		//Header
		$oTr = new Ext_Gui2_Html_Table_Tr();

		foreach((array)$aHeader as $sKey => $aData){
			$oTh = new Ext_Gui2_Html_Table_Tr_Th();
				$oTh->setElement($aData['l10n']);
				$oTh->style = "width:".$aData['width'];
			$oTr->setElement($oTh);
		}

		$oTable->setElement($oTr);

		// Body

		foreach ((array)$aInquiries as $aInqData) {

			/** @var Ext_TS_Inquiry $oInquiry */
			$oInquiry = $aInqData['inquiry'];
			$oSchool = $oInquiry->getSchool();
			$oCustomer = $oInquiry->getCustomer();
			$iSchoolId = $oSchool->id;
			$iCurrencyId = $oInquiry->getCurrency();

			foreach ((array)$aInqData['documents'] as $aDocData) {

				/** @var Ext_Thebing_Inquiry_Document $oDocument */
				$oDocument = $aDocData['document'];

				$mPayedAmount = $oDocument->getPayedAmount($iCurrencyId);

				// @TODO
				if($sAdditional === 'commission_payout') {
					$mPayedAmount *= -1;
				}

				$mAmount = $oDocument->getAmount();

				$oTr = new Ext_Gui2_Html_Table_Tr();
				foreach((array)$aHeader as $sKey => $aData){

					$mValue = '';
					$sClass = '';

					switch ($sKey) {
						case 'customer':
							$oFormat = new Ext_Thebing_Gui2_Format_CustomerName();
							$aTemp = array();
							$aNameData = array('lastname'=>$oCustomer->lastname, 'firstname'=>$oCustomer->firstname);
							$mValue = $oFormat->format($aTemp, $aTemp, $aNameData);
							break;

						case 'document_number':
							$mValue = $oDocument->document_number;
							break;

						case 'document_date':
							$mValue = Ext_Thebing_Format::LocalDate($oDocument->created, $iSchoolId);
							break;

						case 'total':
							$mValue = Ext_Thebing_Format::Number($mAmount, $iCurrencyId, $iSchoolId);
							$sClass = 'amount';
							break;

						case 'paid':
							$mValue = Ext_Thebing_Format::Number($mPayedAmount, $iCurrencyId, $iSchoolId);
							$sClass = 'amount';
							break;

						case 'balance':
							$fBalance = round($mAmount, 2) - $mPayedAmount;
							$mValue = Ext_Thebing_Format::Number($fBalance, $iCurrencyId, $iSchoolId);
							$sClass = 'amount';
							break;

						case 'brutto': // Kundenzahlungen je Rechnung

							$oPaymentDocument = $oDocument->searchDocumentsPaymentDocuments('document_payment_customer');
							$oPaymentVersion = null;
							if($oPaymentDocument) {
								$oPaymentVersion = $oPaymentDocument->getLastVersion();
							}

							if($oPaymentDocument instanceof Ext_Thebing_Inquiry_Document) {

								$oImg = self::createOverviewPdfIcon($oPaymentDocument, $oPaymentVersion);
								$oImg->title = L10N::t('PDF Kunde Übersicht einzeln', $sDescription);

								$mValue = $oImg;
							}

							break;

						case 'netto': // Agenturzahlungen je Rechnung

							if($oInquiry->agency_id > 0) {
								$oPaymentDocument = $oDocument->searchDocumentsPaymentDocuments('document_payment_agency');
								$oPaymentVersion = null;
								if($oPaymentDocument) {
									$oPaymentVersion = $oPaymentDocument->getLastVersion();
								}

								if($oPaymentDocument instanceof Ext_Thebing_Inquiry_Document) {

									$oImg = self::createOverviewPdfIcon($oPaymentDocument, $oPaymentVersion);
									$oImg->title = L10N::t('PDF Agentur-Übersicht einzeln', $sDescription);

									$mValue = $oImg;
								}
							}

							break;
					}

					$oTd = new Ext_Gui2_Html_Table_Tr_Td();
						$oTd->setElement($mValue);
						$oTd->class = $sClass;
					$oTr->setElement($oTd);
				}
				$oTable->setElement($oTr);
			}

		}

		// Overpayment gehört zur Buchung, daher unter den Dokumenten anzeigen
		if(abs(self::$fAmountOverpay) > 0) {
			$oInquiry = reset($aInquiries)['inquiry'];
			$oTr = new Ext_Gui2_Html_Table_Tr();
			$oTable->setElement($oTr);

			$oTd = new Ext_Gui2_Html_Table_Tr_Td();
			$oTd->setElement($oGui->t('Überbezahlung'));
			$oTd->colspan = 5;
			$oTr->setElement($oTd);

			// Da Betrag in Pending steht, muss dieser umgedreht werden
			$oTd = new Ext_Gui2_Html_Table_Tr_Td();
			$oTd->setElement(Ext_Thebing_Format::Number(self::$fAmountOverpay * -1, $oInquiry->getCurrency()));
			$oTd->class = 'amount';
			$oTr->setElement($oTd);

			if($sAdditional !== 'commission_payout') {
				$oTd = new Ext_Gui2_Html_Table_Tr_Td();
				$oTd->colspan = 2;
				$oTr->setElement($oTd);
			}
		}

		$oContainer->setElement($oTable);

		return $oContainer->generateHTML();
	}

	/**
	 * Liefert alle Payment-Typen (Label)
	 *
	 * @return array
	 */
	public static function getTypeOptions() {

		return [
			'1' => L10N::t('Vor Anreise', Ext_Thebing_Inquiry_Payment::TRANSLATION_PATH),
			'2' => L10N::t('Vor Ort', Ext_Thebing_Inquiry_Payment::TRANSLATION_PATH),
			'3' => L10N::t('Auszahlung', Ext_Thebing_Inquiry_Payment::TRANSLATION_PATH),
			'4' => L10N::t('Auszahlung Gutschrift', Ext_Thebing_Inquiry_Payment::TRANSLATION_PATH),
			'5' => L10N::t('Zurückgenommene Auszahlung', Ext_Thebing_Inquiry_Payment::TRANSLATION_PATH)
		];

	}

	/**
	 * @return array
	 */
	public static function getSenderOptions() {

		return [
			'customer' => L10N::t('Kunde', Ext_Thebing_Inquiry_Payment::TRANSLATION_PATH),
			'agency' => L10N::t('Agentur', Ext_Thebing_Inquiry_Payment::TRANSLATION_PATH),
			'school' => L10N::t('Schule', Ext_Thebing_Inquiry_Payment::TRANSLATION_PATH),
			'sponsor' => L10N::t('Sponsor', Ext_Thebing_Inquiry_Payment::TRANSLATION_PATH),
		];

	}

	/**
	 * Nach allen Zahlungen für die übergebenen Inquiry-IDs suchen (über Dokumente und Overpayments)
	 *
	 * @param int[] $aInquiries
	 * @param bool|int $sAdditional
	 * @return array
	 */
	public static function searchPaymentsByInquiryArray($aInquiries, $sAdditional = false) {

		if(empty($aInquiries)) {
			return [];
		}

		$sSqlType = "`kid`.`type` != 'creditnote' AND";
		if($sAdditional == 'commission_payout') {
			$sSqlType = "`kid`.`type` = 'creditnote' AND";
		}

		$sSql = "
 			SELECT
				*,
				GROUP_CONCAT( DISTINCT `document_number`) `document_number`
			FROM
				(
					(
						/* Zahlungen über Items */
						SELECT
							`kip`.`id` `id`,
							GROUP_CONCAT( DISTINCT `kid`.`document_number`) `document_number`,
							`ts_i_j`.`school_id`							`school_id`,
							`ts_i`.`currency_id`							`currency_id`,
							`ts_i`.`id`										`first_inquiry_id`
						FROM
							`kolumbus_inquiries_payments` `kip` INNER JOIN
							`kolumbus_inquiries_payments_items` `kipi` ON
								`kipi`.`payment_id` = `kip`.`id` AND
								`kipi`.`active` = 1 INNER JOIN
							`kolumbus_inquiries_documents_versions_items` `kidvi` ON
								`kidvi`.`id` = `kipi`.`item_id` AND
								`kidvi`.`active` = 1 INNER JOIN
							`kolumbus_inquiries_documents_versions` `kidv` ON
								`kidv`.`id` = `kidvi`.`version_id` AND
								`kidv`.`active` = 1 INNER JOIN
							`kolumbus_inquiries_documents` `kid` ON
								`kid`.`id` = `kidv`.`document_id` AND
								".$sSqlType."
								`kid`.`active` = 1 INNER JOIN
							`ts_inquiries` `ts_i` ON
								`kid`.`entity` = '".Ext_TS_Inquiry::class."' AND
								`ts_i`.`id` = `kid`.`entity_id` AND
								`ts_i`.`id` IN (".implode(',', $aInquiries).") AND
								`ts_i`.`active` = 1 INNER JOIN
							`ts_inquiries_journeys` `ts_i_j` ON
								`ts_i_j`.`inquiry_id` = `ts_i`.`id` AND
								`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
								`ts_i_j`.`active` = 1								
						WHERE
							`kip`.active = 1
						GROUP BY
							`kip`.`id`
					) UNION ALL
					(
						/* Zahlungen über Overpayments */
						SELECT
							`kip`.`id` `id`,
							GROUP_CONCAT( DISTINCT `kid`.`document_number`) `document_number`,
							`ts_i_j`.`school_id`							`school_id`,
							`ts_i`.`currency_id`							`currency_id`,
							`ts_i`.`id`										`first_inquiry_id`
						FROM
							`kolumbus_inquiries_payments` `kip` INNER JOIN
							`kolumbus_inquiries_payments_overpayment` `kipo` ON
								`kipo`.`payment_id` = `kip`.`id` AND
								`kipo`.`active` = 1 INNER JOIN
							`kolumbus_inquiries_documents` `kid` ON
								`kid`.`id` = `kipo`.`inquiry_document_id` AND
								".$sSqlType."
								`kid`.`active` = 1 INNER JOIN
							`ts_inquiries` `ts_i` ON
								`kid`.`entity` = '".Ext_TS_Inquiry::class."' AND
								`ts_i`.`id` = `kid`.`entity_id` AND
								`ts_i`.`id` IN (".implode(',', $aInquiries).") AND
								`ts_i`.`active` = 1 INNER JOIN
							`ts_inquiries_journeys` `ts_i_j` ON
								`ts_i_j`.`inquiry_id` = `ts_i`.`id` AND
								`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
								`ts_i_j`.`active` = 1
						WHERE
							`kip`.active = 1
						GROUP BY
							`kip`.`id`
					) UNION ALL
					(
						/* Zahlungen über inquiry_id (Zahlungen mit 0 haben weder Items noch Overpayments) */
						SELECT
							`kip`.`id` `id`,
							'' `document_number`,
							`ts_i_j`.`school_id`							`school_id`,
							`ts_i`.`currency_id`							`currency_id`,
							`ts_i`.`id`										`first_inquiry_id`
						FROM
							`kolumbus_inquiries_payments` `kip` INNER JOIN
							`ts_inquiries` `ts_i` ON
								`ts_i`.`id` = `kip`.`inquiry_id` AND
								`ts_i`.`id` IN (".implode(',', $aInquiries).") AND
								`ts_i`.`active` = 1 INNER JOIN
							`ts_inquiries_journeys` `ts_i_j` ON
								`ts_i_j`.`inquiry_id` = `ts_i`.`id` AND
								`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
								`ts_i_j`.`active` = 1
						WHERE
							`kip`.active = 1
						GROUP BY
							`kip`.`id`
					)
				) `union`
			GROUP BY
				`id`
		";

		$aResult = DB::getQueryData($sSql);

		foreach((array)$aResult as $iKey => $aData){
			$aTemp = explode(',', $aData['document_number']);
			$aTemp = array_unique($aTemp);
			$aResult[$iKey]['document_number'] = implode(',', $aTemp);
		}

		return $aResult;
	}

	public function getPayedAmountObject(): \Ts\Dto\Amount
	{
		return new \Ts\Dto\Amount(
			$this->amount_inquiry,
			Ext_Thebing_Currency::getInstance($this->currency_inquiry)
		);
	}

	/**
	 * Summiert die Zahlungen zu diesem Payment, oder zu der Buchung, auf der dieses Payment basiert
	 *
	 * Bei übergebener $iInquiryId beachtet diese Methode KEINE Overpayments oder Refund!
	 *
	 * @param bool $bCalculateToBrutto
	 * @param int $iInquiryId
	 * @return float
	 */
	public function getPayedAmount($bCalculateToBrutto = false, $iInquiryId = 0) {

		$sFilter = "";

		if($iInquiryId > 0) {
			$sFilter = " AND
							`kipi`.`item_id` IN (
									SELECT
										`kidvi`.`id`
									FROM
										`kolumbus_inquiries_documents_versions_items` `kidvi` INNER JOIN
										`kolumbus_inquiries_documents_versions` `kidv` ON
											`kidv`.`id` = `kidvi`.`version_id` AND
											`kidv`.`active` = 1 INNER JOIN
										`kolumbus_inquiries_documents` `kid` ON
											`kid`.`id` = `kidv`.`document_id` AND
											`kid`.`entity` = '".Ext_TS_Inquiry::class."' AND
											`kid`.`active` = 1 INNER JOIN
										`ts_inquiries` `ts_i` ON
											`ts_i`.`id` = `kid`.`entity_id` AND
											`ts_i`.`id` = :inquiry_id AND
											`ts_i`.`active` = 1
									WHERE
										`kidvi`.`active` = 1
								) ";
		}

		$aSql = array();
		$aSql['payment_id']	= (int)$this->id;
		$aSql['inquiry_id']	= (int)$iInquiryId;

		/*if($bCalculateToBrutto) {

			$sSql = " SELECT
						SUM(
								`kipi`.`amount_inquiry` *
								(
									`kidvi`.`amount` /
									IF(
										`kidvi`.`amount_net` = 0,
										`kidvi`.`amount`,
										`kidvi`.`amount_net`
									)
								)
						) `sum`
					FROM
						`kolumbus_inquiries_payments_items` `kipi` INNER JOIN
						`kolumbus_inquiries_documents_versions_items` `kidvi` ON
							`kidvi`.`id` = `kipi`.`item_id` INNER JOIN
						`kolumbus_inquiries_payments` `kip` ON
							`kip`.`id` = `kipi`.`payment_id`

					WHERE
						`kipi`.`payment_id` = :payment_id AND
						`kipi`.`active` = 1 AND
						`kip`.`active` = 1" . $sFilter;
		} else {*/
			$sSql = " SELECT
						SUM(`kipi`.`amount_inquiry`) `sum`
					FROM
						`kolumbus_inquiries_payments_items` `kipi` INNER JOIN
						`kolumbus_inquiries_payments` `kip` ON
							`kip`.`id` = `kipi`.`payment_id`
					WHERE
						`kip`.`id` = :payment_id AND
						`kip`.`active` = 1 AND
						`kipi`.`active` = 1
					" . $sFilter;
		/*}*/

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		$fReturn = (float)$aResult[0]['sum'];

		return $fReturn;

	}

	/**
	 * Liefert den Betrag der Zahlung, der keinem Payment-Item zugewiesen ist
	 *
	 * Das trifft in der Regel auf die Beträge von Overpayments und Refunds zu,
	 * welche von getPayedAmount() und getAmount() ignoriert werden.
	 *
	 * @return float
	 */
	public function getNotItemAllocatedAmount() {

		$sSql = "
			SELECT
				`kip`.`amount_inquiry` - COALESCE(
					(
						SELECT
							SUM(`kipi`.`amount_inquiry`)
						FROM
							`kolumbus_inquiries_payments_items` `kipi`
						WHERE
							`kipi`.`payment_id` = `kip`.`id` AND
							`kipi`.`active` = 1
					)
				, 0)
			FROM
				`kolumbus_inquiries_payments` `kip`
			WHERE
				`kip`.`id` = :payment_id
		";

		return (float)DB::getQueryOne($sSql, ['payment_id' => $this->id]);

	}

	/**
	 * Liefert den Betrag eines Payments zurück, komplett oder für eine Buchung
	 * @param type $iInquiryId
	 * @param type $iDocumentId
	 * @return type
	 */
	public function getAmount($iInquiryId = 0, $iDocumentId = 0){

		if($iInquiryId > 0){

			$sWhere = ($iDocumentId > 0)
				? " AND `kid`.`id` = :document_id "
				: "";

			$sSql = "SELECT
							SUM(`kipi`.`amount_inquiry`)
						FROM
							`kolumbus_inquiries_payments_items` `kipi`
						WHERE
							`kipi`.`active` = 1 AND
							`kipi`.`payment_id` = :payment_id AND
							`kipi`.`item_id` IN (
									SELECT
										`kidvi`.`id`
									FROM
										`kolumbus_inquiries_documents_versions_items` `kidvi` INNER JOIN
										`kolumbus_inquiries_documents_versions` `kidv` ON
											`kidv`.`id` = `kidvi`.`version_id` AND
											`kidv`.`active` = 1 INNER JOIN
										`kolumbus_inquiries_documents` `kid` ON
											`kid`.`id` = `kidv`.`document_id` AND
											`kid`.`entity` = '".Ext_TS_Inquiry::class."' AND
											`kid`.`active` = 1 INNER JOIN
										`ts_inquiries` `ki` ON
											`ki`.`id` = `kid`.`entity_id` AND
											`ki`.`id` = :inquiry_id AND
											`ki`.`active` = 1
									WHERE
										`kidvi`.`active` = 1
									".$sWhere."
							)
					";

			$aSql = array();
			$aSql['payment_id'] = (int)$this->id;
			$aSql['inquiry_id'] = (int)$iInquiryId;
			$aSql['document_id'] = (int)$iDocumentId;

			$fAmount = DB::getQueryOne($sSql, $aSql);

			// Überbezahlung ergänzen falls vorhanden

			if($iDocumentId > 0) {
				$sOverpaymentWhere = " AND `kipo`.`inquiry_document_id` = :document_id ";
 			} else {
				$sOverpaymentWhere = " AND `kipo`.`inquiry_document_id` IN (
								SELECT
										`kid`.`id`
									FROM
										`kolumbus_inquiries_documents` `kid` INNER JOIN
										`ts_inquiries` `ki` ON
											`ki`.`id` = `kid`.`entity_id` AND
											`ki`.`id` = :inquiry_id AND
											`ki`.`active` = 1
									WHERE
										`kid`.`entity` = '".Ext_TS_Inquiry::class."' AND
										`kid`.`entity_id` != 0 AND
										`kid`.`active` = 1

							) ";
			}

			$sSql = "SELECT
						SUM(`amount_inquiry`)
					FROM
						`kolumbus_inquiries_payments_overpayment` `kipo`
					WHERE
						`kipo`.`active` = 1 AND
						`kipo`.`payment_id` = :payment_id 
						".$sOverpaymentWhere;

			$aSql = array();
			$aSql['payment_id'] = (int)$this->id;
			$aSql['inquiry_id'] = (int)$iInquiryId;
			$aSql['document_id'] = (int)$iDocumentId;

			$fOverpayAmount = (float)DB::getQueryOne($sSql, $aSql);

			$fAmount += $fOverpayAmount;
		}else{
			$fAmount = $this->amount_inquiry;
		}

		return $fAmount;
	}

	/**
	 * Speichert ein Item zum Payment
	 *
	 * @param int $iDocumentItemId
	 * @param array $aItemData
	 * @return Ext_Thebing_Inquiry_Payment_Item|array|true
	 */
	public function saveDialogPaymentItem($iDocumentItemId, array $aItemData) {

		// Da hier früher mal float und mal unkonvertierte Werte reinkamen, abfangen
		if(is_string($aItemData['amount_inquiry'])) {
			throw new InvalidArgumentException('Amount with type string is not valid');
		}

		// Wenn Betrag 0, soll das Item nicht gespeichert werden
		if($aItemData['amount_inquiry'] == 0) {
			return true;
		}

		$oItem = Ext_Thebing_Inquiry_Payment_Item::getInstance();
		$oItem->amount_inquiry = (float)$aItemData['amount_inquiry'];
		$oItem->amount_school = (float)$aItemData['amount_school'];
		$oItem->currency_inquiry = (int)$aItemData['currency_inquiry'];
		$oItem->currency_school = (int)$aItemData['currency_school'];
		$oItem->item_id	= (int)$iDocumentItemId;
		$oItem->payment_id = (int)$this->id;

		$mValidate = $oItem->validate();
		if($mValidate === true) {
			// Anmerkung: Hier wird auch die Relation zwischen Zahlung und Dokument (Zwischentabelle) gespeichert
			$oItem->save();
			return $oItem;
		} else {
			return $mValidate;
		}

	}


	/**
	 * Liefert alle Inquries die mit dem Payment zu tun haben
	 *
	 * @TODO Einbauen, dass inquiry_id aus Payment hinzugefügt wird?
	 * Wurde schon in prepareInquiryPaymentOverviewPdfs() und delete() manuell ergänzt
	 *
	 * @return Ext_TS_Inquiry[]
	 */
	public function getAllInquiries() {

		$aCache = $this->_aCacheGetAllInquries;

		if(empty($aCache)){
			$sSql = " SELECT
						`id`
					FROM
						(
							(
								SELECT
									`ts_i`.`id`
								FROM
									`kolumbus_inquiries_payments` `kip` INNER JOIN
									`kolumbus_inquiries_payments_items` `kipi` ON
										`kipi`.`payment_id` = `kip`.`id` INNER JOIN
									`kolumbus_inquiries_documents_versions_items` `kidvi` ON
										`kidvi`.`id` = `kipi`.`item_id` INNER JOIN
									`kolumbus_inquiries_documents_versions` `kidv` ON
										`kidv`.`id` = `kidvi`.`version_id` INNER JOIN
									`kolumbus_inquiries_documents` `kid` ON
										`kid`.`id` = `kidv`.`document_id` INNER JOIN
									`ts_inquiries` `ts_i` ON
									    `kid`.`entity` = '".Ext_TS_Inquiry::class."' AND
										`ts_i`.`id` = `kid`.`entity_id`
								WHERE
									`kip`.`id` = :payment_id
								GROUP BY
									`ts_i`.`id`
							) UNION ALL
							(
								SELECT
									`ts_i`.`id`
								FROM
									`kolumbus_inquiries_payments` `kip` INNER JOIN
									`kolumbus_inquiries_payments_overpayment` `kipo` ON
										`kipo`.`payment_id` = `kip`.`id`  INNER JOIN
									`kolumbus_inquiries_documents` `kid` ON
										`kid`.`id` = `kipo`.`inquiry_document_id` INNER JOIN
									`ts_inquiries` `ts_i` ON
										`kid`.`entity` = '".Ext_TS_Inquiry::class."' AND
										`ts_i`.`id` = `kid`.`entity_id`
								WHERE
									`kip`.`id` = :payment_id
								GROUP BY
									`ts_i`.`id`
							) UNION ALL
							(
								SELECT
									`ts_i`.`id`
								FROM
									`kolumbus_inquiries_payments` `kip` INNER JOIN
									`ts_inquiries` `ts_i` ON
										`ts_i`.`id` = `kip`.`inquiry_id`
								WHERE
									`kip`.`id` = :payment_id
								GROUP BY
									`ts_i`.`id`
							)
						) `union`
						GROUP BY
							`id`
				";

			$aSql = array();
			$aSql['payment_id'] = (int)$this->id;

			$aResult = DB::getPreparedQueryData($sSql, $aSql);

			$aInquiries = array();

			foreach($aResult as $aData){
				$aInquiries[$aData['id']] = Ext_TS_Inquiry::getInstance($aData['id']);
			}

			$this->_aCacheGetAllInquries = $aInquiries;

		} else {
			$aInquiries = $this->_aCacheGetAllInquries;
		}

		return $aInquiries;
	}

	/**
	 * @TODO Sollte eigentlich gegen documents ersetzt werden können, allerdings werden keine Overpayments berücksichtigt (haben diese überhaupt Relevanz?)
	 * @deprecated
	 *
	 * Liefert alle Documents die mit dem Payment zu tun haben
	 * @return Ext_Thebing_Inquiry_Document[]
	 */
	public function getAllDocuments() {

		$aCache = $this->_aCacheGetAllDocuments;

		if(empty($aCache)){
			$sSql = " SELECT
						`id`
					FROM
						(
							(
								SELECT
									`kid`.`id`
								FROM
									`kolumbus_inquiries_payments` `kip` INNER JOIN
									`kolumbus_inquiries_payments_items` `kipi` ON
										`kipi`.`payment_id` = `kip`.`id` INNER JOIN
									`kolumbus_inquiries_documents_versions_items` `kidvi` ON
										`kidvi`.`id` = `kipi`.`item_id` INNER JOIN
									`kolumbus_inquiries_documents_versions` `kidv` ON
										`kidv`.`id` = `kidvi`.`version_id` INNER JOIN
									`kolumbus_inquiries_documents` `kid` ON
										`kid`.`id` = `kidv`.`document_id`
								WHERE
									`kip`.`id` = :payment_id
								GROUP BY
									`kid`.`id`
							) UNION ALL
							(
								SELECT
									`kid`.`id`
								FROM
									`kolumbus_inquiries_payments` `kip` INNER JOIN
									`kolumbus_inquiries_payments_overpayment` `kipo` ON
										`kipo`.`payment_id` = `kip`.`id`  INNER JOIN
									`kolumbus_inquiries_documents` `kid` ON
										`kid`.`id` = `kipo`.`inquiry_document_id`
								WHERE
									`kip`.`id` = :payment_id
								GROUP BY
									`kid`.`id`
							)
						) `union`
						GROUP BY
							`id`
				";

			$aSql = array('payment_id' => $this->id);

			$aResult = DB::getPreparedQueryData($sSql, $aSql);

			$aDocs = array();

			foreach($aResult as $aData){
				$aDocs[] = Ext_Thebing_Inquiry_Document::getInstance($aData['id']);
			}

			$this->_aCacheGetAllDocuments = $aDocs;

		} else {
			$aDocs = $this->_aCacheGetAllDocuments;
		}

		return $aDocs;
	}

	/**
	 * Löscht das Payment und alles was dazugehört
	 */
	public function delete() {

		$mDeleted = parent::delete();

		if ($mDeleted !== true) {
			return $mDeleted;
		}

		// Alle Inquries die mit dem Payment zu tun haben holen
		$aInquiries = $this->getAllInquiries();

		// Ausgewählte Buchung dieser Zahlung hinzufügen, wenn nicht vorhanden
		if(
			$this->inquiry_id > 0 &&
			!isset($aInquiries[$this->inquiry_id])
		) {
			$aInquiries[$this->inquiry_id] = $this->getInquiry();
		}

		foreach ($this->getJoinTableObjects(self::JOINTABLE_RECEIPTS) as $oDocument) {
			/** @var Ext_Thebing_Inquiry_Document $oDocument */
			$oDocument->delete();
		}

		/*
		 * Dokumente aktualisieren, da das ggf. nicht passiert #9803
		 * Normalerweise passiert das durch Registry und $oInquiry->calculatePayedAmount(), aber nicht immer
		 */
		foreach((array)$this->documents as $iDocumentId) {
			Ext_Gui2_Index_Stack::add('ts_document', $iDocumentId, 0);
		}

		$this->deleteAgencyPayments();
//		$this->documents = array();
		$this->deleteManualCreditnotePayments();

		if($this->id > 0) {
			$this->active = 0;
			$this->save();
		}

		try {
			[$bCustomerReceipt, $bAgencyReceipt] = Ext_Thebing_Inquiry_Payment::getNeededPaymentReceiptTypes($this->getInquiry(), Ext_Thebing_Inquiry_Payment::RECEIPT_OVERVIEW);
			if ($bCustomerReceipt) $this->prepareInquiryPaymentOverviewPdfs();
			if ($bAgencyReceipt) $this->prepareInquiryPaymentOverviewPdfs(true);
		} catch(PDF_Exception $e) {
			$aError = array('message' => L10N::t('Nach dem Löschen der Zahlung konnten die PDFs nicht erstellt werden! Bitte überpüfen Sie die die Vorlageneinstellungen', 'Thebing » Errors'));
			$this->aErrors[] = $aError;
		}

		// Übersichtspdf neu generieren und Gruppenbuchungen im Index aktualisieren
		foreach($aInquiries as $oInquiry) {

			$aDocuments = $oInquiry->getDocuments('invoice', true, true);
			[$bCustomerReceipt, $bAgencyReceipt] = Ext_Thebing_Inquiry_Payment::getNeededPaymentReceiptTypes($oInquiry, Ext_Thebing_Inquiry_Payment::RECEIPT_INVOICE);

			foreach((array)$aDocuments as $iKey => $oDocument) {
				if ($bCustomerReceipt) {
					$oDocument->preparePaymentDocument();
				}
				if ($bAgencyReceipt) {
					$oDocument->preparePaymentDocument(true);
				}
			}

			// Beträge von Gruppenbuchungen ebenso aktualisieren
			if($oInquiry->hasGroup()) {
				$aGroupInquiries = (array)$oInquiry->getGroup()->getInquiries(false, false, true);
				foreach($aGroupInquiries as $oTmpInquiry) {
					if($oTmpInquiry->id != $oInquiry->id) {
						$oTmpInquiry->calculatePayedAmount();
						Ext_Gui2_Index_Stack::add('ts_inquiry', $oTmpInquiry->id, 0);
					}
				}
			}

			$oInquiry->calculatePayedAmount();

			// Zur Sicherheit auch nochmal explizit aktualisieren, da das implizit durch den Beleg passiert
			Ext_Gui2_Index_Stack::add('ts_inquiry', $oInquiry->id, 0);

		}

		return true;

	}

	/**
	 * Bei Agenturzahlungen: Zugewiesene CN-Verrechnungen mit löschen
	 * Bei CN-Verrechnungen: Zugewiesene Agenturzahlungen mit löschen
	 *
	 * Siehe auch Kommentar von self::$aDeletedAgencyPaymentIds
	 */
	private function deleteAgencyPayments() {

		// Verrechnungen der Agenturzahlung löschen
		$aPayments = $this->getJoinTableObjects('payment_creditnotes');
		foreach($aPayments as $oPayment) {
			if(!isset(self::$aDeletedAgencyPaymentIds[$oPayment->getId()])) {
				self::$aDeletedAgencyPaymentIds[$oPayment->getId()] = true;
				$oPayment->delete();
			}
		}
		$this->payment_creditnotes = array();

		// Agenturzahlungen der Verrechnung löschen
		$aCreditnotePayments = $this->getJoinTableObjects('creditnote_payments');
		foreach($aCreditnotePayments as $oCreditNotePayment) {
			if(!isset(self::$aDeletedAgencyPaymentIds[$oCreditNotePayment->getId()])) {
				self::$aDeletedAgencyPaymentIds[$oCreditNotePayment->getId()] = true;
				$oCreditNotePayment->delete();
			}
		}
		$this->creditnote_payments = array();

	}

	/**
	 * Overpayment-Tab speichern
	 *
	 * @param array $aSelectedIds
	 * @param array $aData
	 * @param Ext_Gui2 $oGui
	 * @param string $sAdditional
	 * @return array
	 */
	public static function saveDialogOverpaymentTab($aSelectedIds, $aData, Ext_Gui2 $oGui, $sAdditional) {

		// Mit Exception abfangen, da hier früher Overpayments bei Mehrfachauswahl lustig miteinander verrechnet wurden
		if(count($aSelectedIds) > 1) {
			throw new BadMethodCallException('Overpayment allocation does not work for multiple selection');
		}

		// Irgendein Item muss vorhanden sein, sonst kann das hier alles nicht funktionieren
		if(
			empty($aData['items']) ||
			!is_array($aData['items'])
		) {
			throw new BadMethodCallException('No dialog data (payment items) given');
		}

		// Total-Betrag nicht übermittelt: Abbruch
		if(!isset($aData['payment']['amount_inquiry'])) {
			throw new BadMethodCallException('Total payment amount missing');
		}

		$aErrors = [];
		DB::begin(__METHOD__);

		// Aus erstem Item Inquiry ermitteln (Mehrfachauswahl durch Exception abgefangen)
		$bGroupPaymentMerged = (bool)$aData['payment_dialog']['group_payment_merged'];
		if($bGroupPaymentMerged) {
			// Bei gemergten Positionen ist $iDocumentId = $iInquiryId
			$oInquiry = Ext_TS_Inquiry::getInstance(key($aData['items']));
		} else {
			$oDocumentItem = Ext_Thebing_Inquiry_Document_Version_Item::getInstance(key($aData['items']));
			$oInquiry = $oDocumentItem->getDocument()->getInquiry();
		}

		if(!$oInquiry->exist()) {
			throw new RuntimeException('No valid inquiry given');
		}

		$oSchool = $oInquiry->getSchool();

		// Wenn Gruppe: Alle Buchungen der Gruppe holen, ansonsten nur Einzelbuchung
		$aInquiries = [$oInquiry]; /** @var Ext_TS_Inquiry[] $aInquiries */
		$oGroup = $oInquiry->getGroup();
		if($oGroup) {
			$aInquiries = $oGroup->getInquiries(false, false);
		}

		// Dokumente die in dem Vorgang benutzt werden
		$aDocumentIds = [];

		// Overpayments über alle Buchungen sammeln
		$aOverpayments = []; /** @var Ext_Thebing_Inquiry_Payment_Overpayment[] $aOverpayments */
		foreach($aInquiries as $oInquiry) {
			// Bei CN-Ausbezahlungen müssen die CN-Overpayments benutzt werden
			$aOverpayments = array_merge($aOverpayments, $oInquiry->getOverpayments($sAdditional === 'commission_payout' ? 'creditnote' : 'invoice'));
		}

		// Betrag prüfen: Total darf nicht größer sein als vorhandenes Overpayment, sonst ist Verrechnung paradox
		$fPaymentAmountTotal = Ext_Thebing_Format::convertFloat($aData['payment']['amount_inquiry'], $oSchool->id);
		$fOverpayAmount = array_sum(array_map(function(Ext_Thebing_Inquiry_Payment_Overpayment $oOverpayment) {
			return $oOverpayment->amount_inquiry;
		}, $aOverpayments));

		if($fPaymentAmountTotal > $fOverpayAmount) {
			$aErrors[0]['message'] = $oGui->t('Der zu verrechnende Betrag ist höher als der verfügbare Betrag der Überbezahlung.');
		}

		if(
			empty($aErrors) &&
			!$bGroupPaymentMerged
		) {
			// Einzelbuchung: Document Items direkt verrechnen

			foreach($aData['items'] as $iDocumentItem => $aItemData) {

				$oDocumentItem = Ext_Thebing_Inquiry_Document_Version_Item::getInstance($iDocumentItem);
				if(!$oDocumentItem->exist()) {
					throw new RuntimeException('Invalid document item: '.$iDocumentItem);
				}

				$aDocumentIds[] = $oDocumentItem->getVersion()->document_id;

				$fItemAmount = (float)Ext_Thebing_Format::convertFloat($aItemData['amount_inquiry'], $oSchool->id);
				$fItemAmountSchool = (float)Ext_Thebing_Format::convertFloat($aItemData['amount_school'], $oSchool->id);

				foreach($aOverpayments as $oOverpayment) {
					$oPayment = $oOverpayment->getPayment();
					// $fItemAmount und $fItemAmountSchool sind Referenzen!
					$bSaveItem = self::saveDialogOverpaymentTabItem($oDocumentItem, $fItemAmount, $fItemAmountSchool, $oOverpayment);
					if(!$bSaveItem) {
						$aErrors[0]['message'] = $oGui->t('Beim Speichern der Verrechnung der Überbezahlung ist ein Fehler aufgetreten.');
						break 2;
					}
				}

			}

		} elseif(empty($aErrors)) {
			// Gruppenbuchung: Hier müssen erst einmal umständlich die wahren Positionen geholt werden

			$aInquiryIds = array_map(function(Ext_TS_Inquiry $oInquiry) {
				return $oInquiry->id;
			}, $aInquiries);

			// Positionen aller Schüler holen (gleiche Methode wie beim Aufbau der Tabelle)
			$aPaymentData = self::buildPaymentDataArray($aInquiryIds, $sAdditional);

			foreach($aPaymentData as $aPaymentData2) {
				$oInquiry = $aPaymentData2['inquiry']; /** @var Ext_TS_Inquiry $oInquiry */

				// self::buildPaymentDataArray() wird auch beim Dialogaufbau benutzt und sollte in beiden Fällen dasselbe liefern
				if(
					!isset($aData['items'][$oInquiry->id]['amount_inquiry']) ||
					!isset($aData['items'][$oInquiry->id]['amount_school'])
				) {
					throw new RuntimeException('Amount input for group inquiry ('.$oInquiry->id.') missing');
				}

				// Eingegebene Beträge aus dem Dialog
				$fAmount = $fAmountOriginal = Ext_Thebing_Format::convertFloat($aData['items'][$oInquiry->id]['amount_inquiry']);
				$fAmountSchool = Ext_Thebing_Format::convertFloat($aData['items'][$oInquiry->id]['amount_school']);

				// Beträge verteilen und Item hinzufügen
				foreach((array)$aPaymentData2['documents'] as $aTmpDocuments) {
					foreach((array)$aTmpDocuments['items'] as $oTmpItem) {
						foreach($aOverpayments as $oOverPayment) {
							// $fItemAmount und $fItemAmountSchool sind Referenzen!
							$bSaveItem = self::saveDialogOverpaymentTabItem($oTmpItem, $fAmount, $fAmountSchool, $oOverPayment);
							if(!$bSaveItem) {
								$aErrors[0]['message'] = $oGui->t('Beim Speichern der Verrechnung der Überbezahlung ist ein Fehler aufgetreten.');
								break 4;
							}
						}
					}

					if (isset($aTmpDocuments['document'])) {
						$aDocumentIds[] = $aTmpDocuments['document']->id;
					}
				}
			}

			// Inquiry-Betrags-Spalten aktualisieren #7313
			// Das muss in einer separaten Schleife passieren, da Overpayments bei Gruppen voneinander abhängig sind
			foreach($aPaymentData as $aPaymentData2) {
				$oInquiry = $aPaymentData2['inquiry']; /** @var Ext_TS_Inquiry $oInquiry */
				$oInquiry->calculatePayedAmount();
				// TODO wird das an dieser Stelle noch benötigt? Passiert unten auch nochmal..
				Ext_Gui2_Index_Stack::add('ts_inquiry', $oInquiry->id, 0);
			}

		}

		// Betrag der Zahlung = Summe einzelner gezahlten Positionen + Überbezahlung
		if(empty($aErrors)) {
			foreach($aOverpayments as $oOverpayment) {
				$oPayment = $oOverpayment->getPayment();
				if(!$oPayment->checkAmount()) {
					$aErrors[0]['message'] = $oGui->t('Der Zahlbetrag stimmt nicht mit den Positionsbeträgen überein!');
					break;
				}
			}
		}

		if(empty($aErrors)) {

			DB::commit(__METHOD__);

			foreach ($aInquiries as $oInquiry) {
				Ext_Gui2_Index_Stack::add('ts_inquiry', $oInquiry->id, 0);
			}

			foreach (array_unique($aDocumentIds) as $iDocumentId) {
				Ext_Gui2_Index_Stack::add('ts_document', $iDocumentId, 0);
			}

		} else {
			DB::rollback(__METHOD__);
		}

		if (empty($aErrors)) {
			foreach (array_unique($aDocumentIds) as $iDocumentId) {
				$oInquiry = \Illuminate\Support\Arr::first($aInquiries);
				$oDocument = \Ext_Thebing_Inquiry_Document::getInstance($iDocumentId);
				\Ext_Thebing_Document::refreshPaymentReceipts($oInquiry, $oDocument);
			}
		}

		// Request Daten definieren
		$aTransfer						= array();
		$aTransfer['action']			= 'saveDialogCallback';
		$aTransfer['dialog_id_tag']		= self::$_sIdTag;
		$aTransfer['success_message']	= L10N::t('Das Payment wurde gespeichert.', $oGui->gui_description);
		$aTransfer['error']				= $aErrors;
		$aTransfer['tab']				= 1;

		// Wenn fehler aufgetreten sind
		if(is_object($oGui)){
			// Dialog neu holen mit den zurückgesetzten Einstellungen
			$aTransferData			= $oGui->getDataObject()->prepareOpenDialog('payment', $aSelectedIds, false, $sAdditional);
			$aTransfer['data']		= $aTransferData;
		} else {
			$aTransfer['data']		= array('id' => 'PAYMENT_'.$oPayment->id, 'save_id' => $oPayment->id);
		}

		return $aTransfer;

	}

	/**
	 * zieht von der übergebenen Overpayment den richtigen Betrag ab und speichert ein neues 
	 * Payment-Item
	 * 
	 * @param Ext_Thebing_Inquiry_Document_Version_Item $oItem
	 * @param float $fItemAmount
	 * @param float $fItemAmountSchool
	 * @param Ext_Thebing_Inquiry_Payment_Overpayment $oOverPayment
	 * @return mixed
	 */
	protected static function saveDialogOverpaymentTabItem(Ext_Thebing_Inquiry_Document_Version_Item $oItem, &$fItemAmount, &$fItemAmountSchool, Ext_Thebing_Inquiry_Payment_Overpayment $oOverPayment) {

		if($fItemAmount <= 0){
			return true;
		}

		// Überbezahlter Betrag
		$fOverpayAmount				= (float)$oOverPayment->amount_inquiry;
		$fOverpayAmountSchool		= (float)$oOverPayment->amount_school;

		$fSave						=	(float)$fItemAmount;
		$fSaveSchool				=	(float)$fItemAmountSchool;

		// Wenn die Überbezahlung ausreicht um das Item zu bezahlen
		// reduziere den OverpayAmount
		if(
			(float)$fOverpayAmount >=	(float)$fItemAmount
		){
			$fOverpayAmount = (float)bcsub($fOverpayAmount, $fItemAmount);
			$fOverpayAmountSchool = (float)bcsub($fOverpayAmountSchool, $fItemAmountSchool);

			$fItemAmount = 0;

		// Wenn der Überbezahlungsbetrag kleiner ist als der des Items
		// dann setzte Overpay auf 0 und merke dir den rest des Items
		} else if(
			(float)$fOverpayAmount < (float)$fItemAmount
		){
			$fItemAmount = (float)bcsub($fItemAmount, $fOverpayAmount);
			$fItemAmountSchool = (float)bcsub($fItemAmountSchool, $fOverpayAmountSchool);
			$fSave					= (float)$fOverpayAmount;
			$fSaveSchool			= (float)$fOverpayAmountSchool;
			$fOverpayAmount			= 0;
			$fOverpayAmountSchool	= 0;
		}

		$oOverPayment->amount_inquiry	= (float)$fOverpayAmount;
		$oOverPayment->amount_school	= (float)$fOverpayAmountSchool;
		if((float)$fOverpayAmount == 0){
			$oOverPayment->active = 0;
		}

		$mReturn = $oOverPayment->save();

		if(is_array($mReturn))
		{
			return false;
		}
		else
		{

			$aSaveData = array();

			$aSaveData['currency_inquiry']	= $oOverPayment->currency_inquiry;
			$aSaveData['currency_school']	= $oOverPayment->currency_school;

			$aSaveData['amount_inquiry']	= $fSave;
			$aSaveData['amount_school']		= $fSaveSchool;

			$oPayment = $oOverPayment->getPayment();
			$mSaveDialogItem = $oPayment->saveDialogPaymentItem((int)$oItem->id, $aSaveData);

			if($mSaveDialogItem instanceof Ext_Thebing_Inquiry_Payment_Item) {
				$oOverPayment->saveRebooking($mSaveDialogItem->id);
			} else {
				// true = Item soll nicht gespeichert werden (Betrag 0)
				if($mSaveDialogItem !== true) {
					return false;
				}
			}

		}
		
		return true;
	}

	/**
	 * Speichert ein neues Payment mit Items (normaler Tab, nicht Overpayment-Tab!)
	 *
	 * Methode hieß früher saveNewPayment().
	 *
	 * @TODO Diese ganze Bezahlbeleg-Template-Logik sollte ausgelagert werden
	 * @TODO Fehler sollten eventuell mit Exceptions weitergereicht werden (ganzer if/merge-Kram fiele weg)
	 * @TODO Wofür ist/war $bCreatePDFs da?
	 *
	 * @param $oGui
	 * @param $aSelectedIds
	 * @param $aData
	 * @param string $sAdditional
	 * @param bool $bCreatePDFs
	 * @return array|bool
	 * @throws ErrorException
	 * @throws Exception
	 */
	public static function saveDialogPaymentTab(Ext_Gui2 $oGui, $aSelectedIds, $aData, $sAdditional, $bCreatePDFs = true) {
		global $_VARS;

		set_time_limit(1800);
		ini_set('memory_limit', '1G');

		DB::begin('inquiry_payment_save_new');

		if(!$sAdditional) {
			throw new RuntimeException('No additional given for payment dialog');
		}

		if(
			$sAdditional === 'agency_payment' &&
			empty($_VARS['parent_gui_id'])
		) {
			throw new RuntimeException('No agency payment id given!');
		}

		// Bei CN-Auszahlungen gibt es momentan keine Bezahlbelege
		if($sAdditional === 'commission_payout') {
			$bCreatePDFs = false;
		}

		$oPayment = new self(0);
		$oSchoolForFormat = Ext_Thebing_Client::getFirstSchool();
		$bGroupPaymentMerged = (bool)$aData['payment_dialog']['group_payment_merged'];
		$bError = false;

		// Zahlungsgruppierung über alle Zahlungen
		$oPaymentGrouping = new Ext_TS_Accounting_Payment_Grouping();
		$oPaymentGrouping->save();
		$oPayment->grouping_id = $oPaymentGrouping->id;

		// Werte aus dem Dialog direkt ins Payment-Objekt setzen
		foreach($aData['payment'] as $sField => $mValue) {
			
			// Umformatieren, falls notwendig
			if($sField == 'date') {
				$bValidDate = WDDate::isDate($mValue, WDDate::DB_DATE);
				if($bValidDate !== true) {
					$mValue = Ext_Thebing_Format::ConvertDate($mValue, $oSchoolForFormat->id, 1);
				}
			}

			// Zahlen in float umwandeln
			if(
				$sField == 'amount_inquiry' ||
				$sField == 'amount_school'
			) {
				$mValue = Ext_Thebing_Format::convertFloat($mValue, $oSchoolForFormat->id);
			}

			$oPayment->$sField = $mValue;

		}
		## ENDE

		// Provision ausbezahlen ist leider eine enkodierte GUI, daher ID dekodieren
		if($oGui->checkEncode()) {
			$aSelectedIdsOriginal = $aSelectedIds;
			$aSelectedIds = $oGui->decodeId($aSelectedIds, 'entity_id');
			if (empty($aSelectedIds) || empty($aSelectedIds[0])) {
				throw new RuntimeException('Something went wrong while encoding inquiry ids!');
			}

			if(count($aSelectedIdsOriginal) != count(array_unique($aSelectedIds))) {
				// Bezahldialog kann nur mit einer selben Inquiry-ID gleichzeitig umgehen
				// Wird eigentlich schon in der Icon-Klasse abgefangen, aber falls updateIcons zu langsam ist…
				throw new RuntimeException('Tried to pay the same inquiry more than one time!');
			}
		}

		/** @var Ext_Thebing_Inquiry_Payment[] $aInquiryGroupedPayments Mehrfachauswahl => mehrere Payments */
		$aInquiryGroupedPayments = array();
		$bMultipleSelection = count($aSelectedIds) > 1;

//		$fOverpayAmount = Ext_Thebing_Format::convertFloat($aData['overpay']['amount_inquiry']);
//		$fOverpayAmountSchool = Ext_Thebing_Format::convertFloat($aData['overpay']['amount_school']);

		// Mehrfachauswahl: Mehrere Buchungen markiert (Client Payments, Agency Payments)
		if($bMultipleSelection) {

			throw new RuntimeException('Multiple selection is not possible anymore.');

			// wenn mehrfachauswahl, dann pro schüler ein payment generieren
			foreach((array)$aData['items'] as $iDocumentItem => $aItemData) {

				$iInquiry = $aItemData['inquiry'];
				$aItemData['amount_inquiry'] = Ext_Thebing_Format::convertFloat($aItemData['amount_inquiry']);
				$aItemData['amount_school'] = Ext_Thebing_Format::convertFloat($aItemData['amount_school']);

				if(!array_key_exists($iInquiry, $aInquiryGroupedPayments)) {
					// Betrag & id resetten, siehe __clone
					$oClone = clone($oPayment);
					$aInquiryGroupedPayments[$iInquiry] = $oClone;
				}

				//Beträge pro Buchung aufteilen
				$oPaymentTemp = $aInquiryGroupedPayments[$iInquiry];
				$oPaymentTemp->aItems[$iDocumentItem] = $aItemData;
				$oPaymentTemp->amount_inquiry += $aItemData['amount_inquiry'];
				$oPaymentTemp->amount_school += $aItemData['amount_school'];
				$aInquiryGroupedPayments[$iInquiry] = $oPaymentTemp;

			}

		} else {

			// Einzelauswahl: Nur eine Buchung (oder Buchung einer Gruppe)
			$aSelectedIds = (array)$aSelectedIds;
			$iSelectedId = reset($aSelectedIds);

//			if(!$bGroupPaymentMerged) {
//				// Normale Buchung (nicht Gruppe): Daten aus dem Dialog einfach setzen (Beträge müssen aber konvertiert werden)
//				foreach((array)$aData['items'] as $iDocumentItem => $aItemData) {
//					$aItemData['amount_inquiry'] = Ext_Thebing_Format::convertFloat($aItemData['amount_inquiry']);
//					$aItemData['amount_school'] = Ext_Thebing_Format::convertFloat($aItemData['amount_school']);
//					$oPayment->aItems[$iDocumentItem] = $aItemData;
//				}
//			} else {
				// Gruppe: Beträge müssen erst einmal aufteilt werden (was der Dialog eigentlich macht)

				// @TODO Hidden-Input all_inquiries sollte entfernt werden, Inquiries sollten über Items geholt werden
				$sAllInquiries = $aData['payment_dialog']['all_inquiries'];
				$aAllInquiries = explode(',', $sAllInquiries);

				// Positionen aller Schüler holen (gleiche Methode wie beim Aufbau der Tabelle)
				$aPaymentData = self::buildPaymentDataArray($aAllInquiries, $sAdditional);

				// Werte im Dialog ignorieren (Rundungsfehler etc.) und direkt in PHP verteilen
				$oTmpInquiry = \Illuminate\Support\Arr::first($aPaymentData)['inquiry']; /** @var Ext_TS_Inquiry $oTmpInquiry */
				$aGroupAmounts[$oTmpInquiry->id] = Ext_Thebing_Format::convertFloat($aData['payment']['amount_inquiry']);

				$aItems = [];
				foreach ($aPaymentData as $paymentData) {
					foreach ($paymentData['documents'] as $documentData) {
						$aItems += array_filter($documentData['items'], fn(Ext_Thebing_Inquiry_Document_Version_Item $oItem) => !empty($aData['items'][$bGroupPaymentMerged ? $paymentData['inquiry']->id : $oItem->id]['checked']));
					}
				}

//				$aGroupAmounts = array_map(fn(array $aItem) => Ext_Thebing_Format::convertFloat($aItem['amount_inquiry']), $aData['items']);
//
//				// Buchungen durchlaufen
//				foreach($aPaymentData as $iIndex => $aPaymentData2) {
//
//					/** @var Ext_TS_Inquiry $oTmpInquiry */
//					$oTmpInquiry = $aPaymentData2['inquiry'];
					$oTmpSchool = $oTmpInquiry->getSchool();
//
//					// Die Items aller relevanten Rechnungsdokumente pro Buchung sammeln
//					$aItems = [];
//					foreach((array)$aPaymentData2['documents'] as $aTmpDocuments) {
//						foreach((array)$aTmpDocuments['items'] as $oTmpItem) {
//							$aItems[] = $oTmpItem;
//						}
//					}

					// Verfügbaren Betrag auf die einzelnen Items verteilen
					// Gruppen: Beträge runden, da wg. Discount usw. sonst krumme Overpayments entstehen
					$oAllocateAmountService = new Ext_TS_Payment_Item_AllocateAmount($aItems, $aGroupAmounts[$oTmpInquiry->id]);

					// Bei Ausbezahlung: Overpayments setzen, damit diese zuerst ausbezahlt/verrechnet werden
					if ($aGroupAmounts[$oTmpInquiry->id] < 0) {
						$fOverpaymentsSum = array_map(function (Ext_Thebing_Inquiry_Payment_Overpayment $oOverpayment) use ($sAdditional) {
							return (float)$oOverpayment->amount_inquiry * ($sAdditional === 'commission_payout' ? -1 : 1);
						}, $oTmpInquiry->getOverpayments($sAdditional === 'commission_payout' ? 'creditnote' : 'invoice'));
						$oAllocateAmountService->setOverPayment(array_sum($fOverpaymentsSum));
					}

					// CN-Payout: Beträge sind intern negativ, aber alles ist in dem Dialog umgekehrt…
					$oAllocateAmountService->bInvertPayedAmount = $sAdditional === 'commission_payout';

					$aAllocatedAmounts = $oAllocateAmountService->allocateAmounts();

					$fOverpayAmount = $oAllocateAmountService->getOverPayment();
					$fOverpayAmountSchool = $fOverpayAmount;
					if ($oTmpInquiry->getCurrency() != $oTmpSchool->getCurrency()) {
						$fFactor = Ext_Thebing_Currency::getInstance($oTmpInquiry->getCurrency())->getConversionFactor($oTmpSchool->getCurrency());
						$fOverpayAmountSchool = round($fOverpayAmount * $fFactor, 2);
					}

					/*$fOverpaymentDifference = $oAllocateAmountService->getOverPayment() - $fOverpayAmount;

					// Da es ein Overpayment pro Gruppe nur einmal gibt, muss das der Dialog abfangen
					if (abs($fOverpaymentDifference) > 0) {
//					if ($oAllocateAmountService->hasOverPayment()) {
						// Rundungsdifferenzen auf die nächste Buchung/Zeile übertragen
//						if ($aPaymentData[$iIndex + 1]['inquiry']->id) {
//							$aGroupAmounts[$aPaymentData[$iIndex + 1]['inquiry']->id] += $oAllocateAmountService->getOverPayment();
//						} else {
							// Rest auf Overpayment rechnen (keine Ahnung, ob das richtig ist, weil es vorher die Exception gab)
							$fOverpayAmount += $fOverpaymentDifference;
							if ($oTmpInquiry->getCurrency() != $oTmpSchool->getCurrency()) {
								$fFactor = Ext_Thebing_Currency::getInstance($oTmpInquiry->getCurrency())->getConversionFactor($oTmpSchool->getCurrency());
								$fOverpayAmountSchool += round($oAllocateAmountService->getOverPayment() * $fFactor, 2);
							} else {
								$fOverpayAmountSchool += $fOverpaymentDifference;
							}

//							throw new RuntimeException('Overpayment for single group members is not possible');
//						}
					}*/

					// Schulbetrag ausrechnen und Items in erwartetes aItems-Array schreiben
					/** @var Ext_Thebing_Inquiry_Document_Version_Item[] $aItems */
					foreach($aItems as $oItem) {
						$fAllocatedAmount = $aAllocatedAmounts[$oItem->id];

						// Bei unterschiedlichen Währungen muss der Schulbetrag berechnet werden
						$fAllocatedAmountSchool = $fAllocatedAmount;
						if($oTmpInquiry->getCurrency() != $oTmpSchool->getCurrency()) {
							$fFactor = Ext_Thebing_Currency::getInstance($oTmpInquiry->getCurrency())->getConversionFactor($oTmpSchool->getCurrency());
							$fAllocatedAmountSchool = round($fAllocatedAmount * $fFactor, 2); // Wird im JS ebenso auf zwei Stellen gerundet
						}

						// Gleiche Struktur wie Daten aus dem Dialog
						$oPayment->aItems[$oItem->id] = [
							'currency_inquiry' => $oTmpInquiry->getCurrency(),
							'currency_school' => $oTmpSchool->getCurrency(),
							'inquiry' => $oItem->getInquiry()->id,
							'amount_inquiry' => (float)$fAllocatedAmount,
							'amount_school' => (float)$fAllocatedAmountSchool,
						];

					}

//				}

//			}

			$aInquiryGroupedPayments = array(
				$iSelectedId => $oPayment
			);
		}

		// Wenn Gruppenzahlung und mehere Buchungen (nicht Gruppe): Fehler, das darf nicht sein (updateIcons zu lahm)
		if(
			$bGroupPaymentMerged &&
			$bMultipleSelection
		) {
			throw new RuntimeException('It\'s not possible to pay indiviual bookings and group bookings in same payment dialog');
		}

		/*
		 * Creditnotes ausbezahlen: Creditnotes haben positive Beträge, allerdings sind die Zahlungen intern negativ.
		 * Da wegen den positiven Beträgen auch die Bezahlungen positiv eingegeben werden,
		 * müssen hier alle Beträge invertiert werden.
		 */
		if($sAdditional === 'commission_payout') {
			foreach($aInquiryGroupedPayments as $oPayment) {
				// Summe der Zahlung invertieren
				$oPayment->amount_inquiry *= -1;
				$oPayment->amount_school *= -1;

				// Beträge aller Items invertieren
				foreach($oPayment->aItems as $iDocumentItemId => &$aItemData) {
					$aItemData['amount_inquiry'] *= -1;
					$aItemData['amount_school'] *= -1;
				}

				unset($aItemData);
			}

			// Overpayment-Beträge invertieren
			$fOverpayAmount *= -1;
			$fOverpayAmountSchool *= 1;
		}

		// Wirklich benutzte Inquiries
		$aInquiries = array();

		/** @var $oPayment self */
		foreach($aInquiryGroupedPayments as $iInquiryId => $oPayment) {
			$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);

			// Inquiry-ID der ausgewählten Bezahlung setzen
			$oPayment->inquiry_id = $iInquiryId;
			$oPayment->currency_inquiry = $oInquiry->getCurrency();
			$oPayment->currency_school = $oInquiry->getSchool()->getCurrency();

			// Payments Validieren
			$mPaymentErrors = $oPayment->validate();

			// Wenn keine Fehler
			if($mPaymentErrors === true) {

				// Speichern
				$oPayment->save();

				## START  Items durchgehen und speichern/Validieren
				foreach($oPayment->aItems as $iDocumentItem => $aItemData) {

					$mPaymentIdSave = $oPayment->saveDialogPaymentItem($iDocumentItem, $aItemData);

					if(is_array($mPaymentIdSave)) {
						$bError = true;

						$oPayment->aErrors = array_merge($oPayment->aErrors, $mPaymentIdSave);

						// Hier muss alles gestoppt werden. Bevor irgendetwas gespeichert wird! Sollten
						// mehrere Leute auf einmal bezahlt werden sollen. Darf das auch nicht passieren!
						break 2;
					} else {
						//inquiry ermitteln, nicht $iInquiryId nehmen!
						$aInquiries[] = $aItemData['inquiry'];
					}

				}
			
				// Zahlung der Items neu auslesen
				Ext_Thebing_Inquiry_Document_Version_Item::truncatePayedAmountCache();

				// Bei Agenturzahlungen Verknüpfung abspeichern und benutzte Creditnotes verrechnen
				if(
					$bError === false &&
					in_array($sAdditional, ['inquiry', 'agency_payment'])
				) {
					$oAgencyPayment = null;
					if ($sAdditional === 'agency_payment') {
						$oAgencyPayment = Ext_Thebing_Agency_Payment::getInstance(reset($_VARS['parent_gui_id']));
						if (!$oAgencyPayment->exist()) {
							throw new RuntimeException('Agency payment does not exist!');
						}
					}

					$bError = !self::saveAgencyPaymentAllocation((array)$aData['creditnote'], $oPayment, $oAgencyPayment);
				}

				// Überbezahlung
				if(
					!$bError &&
					abs($fOverpayAmount) > 0
				) {

					// Hier gibt es keine Überbezahlung und wenn es doch eine übermittelt sein sollte, ist im Dialog etwas schief gelaufen
					if(
						$sAdditional === 'agency_payment' ||
						count($aInquiryGroupedPayments) > 1
					) {
						throw new RuntimeException('Overpayment is not possible. Dialog amount allocation error?');
					}

					// Irgendein Dokument aus dieser Zahlung nehmen; hier ist nur wichtig, ob Rechnung oder CN
					if (!empty($oPayment->aItems)) {
						$aFirstItem = reset($oPayment->aItems);
						$oDocumentItem = Ext_Thebing_Inquiry_Document_Version_Item::getInstance(key($oPayment->aItems));
						$oDocumentForOverpayment = $oDocumentItem->getDocument();

						// Wenn es eine Überbezahlung gibt, muss es auch irgendein Dokument geben
						if(!$oDocumentForOverpayment->exist()) {
							throw new RuntimeException('No document for overpayment');
						}
					} else {
						if ($bMultipleSelection) {
							throw new RuntimeException('Overpayment without document not possible for multiple selection.');
						}

						$aInquiries[] = $oInquiry->id;
						$oDocumentForOverpayment = null;
						$aFirstItem = [
							'currency_inquiry' => $oInquiry->getCurrency(),
							'currency_school' => $oInquiry->getSchool()->getCurrency()
						];
					}

					// Überbezahlung abspeichern
					$oOverpayment = Ext_Thebing_Inquiry_Payment_Overpayment::getInstance();
					$oOverpayment->payment_id = (int)$oPayment->id;
					$oOverpayment->inquiry_document_id	= $oDocumentForOverpayment?->id;
					$oOverpayment->amount_inquiry = $fOverpayAmount;
					$oOverpayment->amount_school = $fOverpayAmountSchool;
					$oOverpayment->currency_inquiry = $aFirstItem['currency_inquiry'];
					$oOverpayment->currency_school = $aFirstItem['currency_school'];

					// Wird eigentlich schon durch Ext_Inquiry_Document_Version_Item::save() gemacht, aber nicht immer (Betrag 0 oder es gibt kein Item)
					if ($oDocumentForOverpayment) {
						$oPayment->updateDocumentRelation($oDocumentForOverpayment->id);
					}

					$mValidateOverPayment = $oOverpayment->validate();

					// Auf 0 setzen, damit nur eine Überbezahlung generiert wird (wird oben gesetzt)
					$fOverpayAmount = 0;

					if($mValidateOverPayment === true) {

						$mReturnOverPaymentSave = $oOverpayment->save();
						// Überbezahlung für die einzelnen Positionen merken
						// @TODO Wird das jemals ausgeführt? Diese Keys [overpay][items] gibt es im Dialog nicht
						if(isset($aData['overpay']['items'])) {
							foreach($aData['overpay']['items'] as $iItem => $fAmount) {
								$oOverpayment->saveRebooking($iItem);
							}
						}

						if(is_array($mReturnOverPaymentSave)) {
							$oPayment->aErrors = array_merge($oPayment->aErrors, $mReturnOverPaymentSave);
						}

					} else {
						$oPayment->aErrors = array_merge($oPayment->aErrors, $mValidateOverPayment);
					}

					if(!empty($oPayment->aErrors)) {
						$bError = true;
					}

				}

				if(!$bError) {
					Ext_TC_Flexibility::saveData($aData['payment_flex'][0], $oPayment->id);
				}

				// Prüfen welche Bezahlbelege pro Zahlung generiert werden müssen
				if ($bError === false) {
					$oPayment->writePostSaveTask($oInquiry, $bCreatePDFs);
				}

				/*if(!$bCreatePDFs) {
					$bCreatePaymentPdf = false;
					$bCreatePaymentPdfAgency = false;
				} else {
					list($bCreatePaymentPdf, $bCreatePaymentPdfAgency) = self::getNeededPaymentReceiptTypes($oInquiry, self::RECEIPT_PAYMENT);
				}

				// Wenn keine Fehler bei den Items
				if(
					$bError === false &&
					$bCreatePaymentPdf
				) {

					//Kunden Quittungen
					try {
						$oPayment->preparePaymentPdfs();
					} catch(Exception $e) {
						$sFurtherMessage = '';
						if(System::d('debugmode') == 2) {
							$sFurtherMessage = ': '.$e->getMessage();
						}
						$aError = array('message' => L10N::t('Rechnungs PDFs konnten nicht vollständig erstellt werden', 'Thebing » Errors').$sFurtherMessage);
						$oPayment->aErrors[] = $aError;
						$bError = true;
					}

				}

				// Wenn keine Fehler bei den Items
				if(
					$bError === false &&
					$bCreatePaymentPdfAgency
				) {

					//Agentur Quittungen
					try {
						$oPayment->preparePaymentPdfs(true);
					} catch(Exception $e) {
						$sFurtherMessage = '';
						if(System::d('debugmode') == 2) {
							$sFurtherMessage = ': '.$e->getMessage();
						}
						$aError = array('message' => L10N::t('Agenturrechnungs PDFs konnten nicht vollständig erstellt werden', 'Thebing » Errors').$sFurtherMessage);
						$oPayment->aErrors[] = $aError;
						$bError = true;
					}

				}*/

			} else {

				// Fehler durchgehen
				foreach((array)$mPaymentErrors as $aErrors){
					foreach($aErrors as $sMessage){
						$oPayment->aErrors[] = L10N::t($sMessage, 'Thebing » Errors');
						$bError = true;
					}
				}

			}
		}

		/*
		 * siehe Kommentar checkAmount() & Ticket #803
		 */
		foreach($aInquiryGroupedPayments as &$oPayment) {
			if(!$oPayment->checkAmount()) {
				$oPayment->aErrors[] = L10N::t('Der Zahlbetrag stimmt nicht mit den Positionsbeträgen überein!', 'Thebing » Errors');
				$bError = true;
			}
		}

		// Dialog ID aufbauen anhand der verwendeten Buchungen
		$sId = '';
		$aInquiries = array_unique($aInquiries);
		sort($aInquiries);
		$sId = implode('_', $aInquiries);

		// Dialog ID aufbauen anhand ALLER Buchungen
		$aTemp = array_keys($aInquiryGroupedPayments);
		sort($aTemp);
		$sAllId = implode('_', $aTemp);

		$aErrorsAll = array();

		foreach($aInquiryGroupedPayments as $oPayment) {

			// Wenn fehler aufgetreten sind (1. Prüfung)
			if($bError === true) {

				// Payment wieder Rückgängig machen
				try {
					$oPayment->delete();
				} catch(PDF_Exception $e) {
					$aError = array('message' => L10N::t('Nach dem Löschen der Zahlung konnten die PDFs nicht erstellt werden! Bitte überpüfen Sie die die Vorlageneinstellungen', 'Thebing » Errors'));
					$oPayment->aErrors[] = $aError;
				}

				$sMesage = L10N::t('Fehler beim Speichern', Ext_Gui2::$sAllGuiListL10N);
				$aErrors = array($sMesage);
				$aErrors = array_merge($aErrors, $oPayment->aErrors);

				$aErrosConverted = self::_getPaymentErrorMessages($aErrors, $oGui);
 
				$aErrorsAll = array_merge($aErrorsAll, $aErrosConverted);
			}

			// Bezahlbelege (Übersicht je Buchung) generieren
			// Bei einer Gruppe wird nur ein tatsächliches PDF generiert, aber für jede Buchung ein Dokument
			if(empty($aErrorsAll)) {
				$oPaymentInquiry = $oPayment->getInquiry();

				// Prüfen welche Bezahlbelege (Übersicht über die Buchung) generiert werden müssen
				if(!$bCreatePDFs) {
					$bCreateDocumentPaymentOverviewPdf = false;
					$bCreateDocumentPaymentOverviewPdfAgency = false;
				} else {
					list($bCreateDocumentPaymentOverviewPdf, $bCreateDocumentPaymentOverviewPdfAgency) = self::getNeededPaymentReceiptTypes($oPaymentInquiry, self::RECEIPT_OVERVIEW);
				}

				// Bezahlbeleg: Übersicht der Buchung (Kunde)
				if($bCreateDocumentPaymentOverviewPdf) {
					$oPayment->prepareInquiryPaymentOverviewPdfs();
				}

				// Bezahlbeleg: Übersicht der Buchung (Agentur)
				if($bCreateDocumentPaymentOverviewPdfAgency) {
					$oPayment->prepareInquiryPaymentOverviewPdfs(true);
				}
			}
		}

		if(empty($aErrorsAll)) {

			// Alle kunden durchgehen
			foreach((array)$aInquiries as $iInquiryId) {

				$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);

				// Prüfen welche Bezahlbelege (Übersicht über die Buchung) generiert werden müssen
				if(!$bCreatePDFs) {
					$bCreateDocumentPaymentPdf = false;
					$bCreateDocumentPaymentPdfAgency = false;
				} else {
					list($bCreateDocumentPaymentPdf, $bCreateDocumentPaymentPdfAgency) = self::getNeededPaymentReceiptTypes($oInquiry, self::RECEIPT_INVOICE);
				}

				// Muss auch bei jeder Buchung neu berechnet werden wegen Index-Betragsspalten
				$oInquiry->calculatePayedAmount();

				// Buchung im Index aktualisieren
				// Das passiert an sich durch Erzeugung des Zahlungsbelegs (durch den Dokumenteindex)
				// Bei Gruppenbuchungen werden allerdings nicht ständig neue Dokumente angelegt,
				//	dennoch müssen alle Buchungen der Gruppe aktualisiert werden! #5726
				Ext_Gui2_Index_Stack::add('ts_inquiry', $iInquiryId, 0);

				// Das darf nicht gemacht werden, da ansonsten für Gruppen-Buchungen keine Belege geniert werden #6345
				/*if(
					!isset($aInquiryGroupedPayments[$iInquiryId]) &&
					$iMultiple == 1
				) {
					continue;
				}*/

				// wird auf true gesetzt falls bei den einzelübersichten ein Fehler auftritt
				$bCreateDocumentPaymentPDfError = false;

				//if($iMultiple == 1) {
					$iKeyForPayment = $iInquiryId;
				//} else {
				//	$iKeyForPayment = $iFirstPaymentKey;
				//}

				/*
				 * Zuvor wurde das Payment immer aus $aInquiryGroupedPayments mit Index geholt.
				 * Da aber für eine Gruppe eine Bezahlung mit mehreren Buchungen existieren kann,
				 * 	und man auch nur ein Mitglied einer Gruppe wählen kann bei Mehrfachauswahl,
				 * 	muss dann das erstbeste Payment (es sollte nur eines geben) geholt werden. #6345
				 */
				if(isset($aInquiryGroupedPayments[$iKeyForPayment])) {
					$oPayment = $aInquiryGroupedPayments[$iKeyForPayment];
				} else {
					$oPayment = reset($aInquiryGroupedPayments);
				}

				// @TODO Eventuell auf $oPayment->documents umstellen, sofern doch nicht immer für alle Rechnungen Belege erzeugt werden müssen
				$aDocuments = $oInquiry->getDocuments('invoice_without_proforma', true, true);

				$aCreateDocumentErrors = [];
				
				foreach($aDocuments as $iKey => $oDocument) {

					$mCreateDocumentPaymentOverviewPdf = true;
					$mCreateDocumentPaymentOverviewAgencyPdf = true;

					// Zahlungsübersicht für Rechnungsdokument vorbereiten
					if($bCreateDocumentPaymentPdf){
						try {
							$mCreateDocumentPaymentOverviewPdf = $oDocument->preparePaymentDocument();
						} catch (PDF_Exception $e) {
							$aCreateDocumentErrors[] = $e->getMessage();
							$mCreateDocumentPaymentOverviewPdf = false;
						}
					}

					// Zahlungsübersicht für Rechnungsdokument (Agentur-Bezahlbeleg) vorbereiten
					if(
						$bCreateDocumentPaymentPdfAgency &&
						$mCreateDocumentPaymentOverviewPdf === true
					) {
						try {
							$mCreateDocumentPaymentOverviewAgencyPdf = $oDocument->preparePaymentDocument(true);
						} catch (PDF_Exception $e) {
							$aCreateDocumentErrors[] = $e->getMessage();
							$mCreateDocumentPaymentOverviewAgencyPdf = false;
						}
					}

					// Prüfen, ob das Erstellen einer der beiden Bezahlbelege fehlschlug
					if(
						(
							$bCreateDocumentPaymentPdf &&
							$mCreateDocumentPaymentOverviewPdf !== true
						) || (
							$bCreateDocumentPaymentPdfAgency &&
							$mCreateDocumentPaymentOverviewAgencyPdf !== true
						)
					) {
						$bCreateDocumentPaymentPDfError = true;
						
						// Einfach letzte Fehlermeldungen holen, da Zahlung ohnehin nicht erfolgreich war
						if(isset($mCreateDocumentPaymentOverviewPdf) && is_array($mCreateDocumentPaymentOverviewPdf)) {
							$aCreateDocumentErrors = array_merge($aCreateDocumentErrors, $mCreateDocumentPaymentOverviewPdf);
						}
						if(isset($mCreateDocumentPaymentOverviewAgencyPdf) && is_array($mCreateDocumentPaymentOverviewAgencyPdf)) {
							$aCreateDocumentErrors = array_merge($aCreateDocumentErrors, $mCreateDocumentPaymentOverviewAgencyPdf);
						}
						
					}

				}

				if($bCreateDocumentPaymentPDfError) {
					$oPayment->aErrors[] = L10N::t('Rechnungsübersichts PDFs konnten nicht vollständig erstellt werden', 'Thebing » Errors');

					if(!empty($aCreateDocumentErrors)) {
						$oPayment->aErrors = array_merge($oPayment->aErrors, $aCreateDocumentErrors);
					}

					$bError = true;
					$oPayment->delete();
				}

				$aInquiryGroupedPayments[$iKeyForPayment] = $oPayment;
			}

			foreach($aInquiryGroupedPayments as $oPayment) {

				$aPaymentDocumentIds = (array)$oPayment->documents;
				foreach($aPaymentDocumentIds as $iPaymentDocumentId) {
					$iPrio = 0;
					if($bGroupPaymentMerged) {
						// Bei Gruppen lieber 1, sonst könnte der Dialog wieder 100 Jahre brauchen…
						$iPrio = 1;
					}

					/*
					 * Betroffene Dokumente dieser Zahlung explizit sofort aktualisieren #9803
					 * Prinzipiell funktioniert das »auf gut Glück« durch Registry und $oInquiry->calculatePayedAmount(),
					 * das funktioniert aber bei CNs nicht.
					 */
					Ext_Gui2_Index_Stack::add('ts_document', $iPaymentDocumentId, $iPrio);
				}

				if(!empty($oPayment->aErrors)){
					$aErros = self::_getPaymentErrorMessages($oPayment->aErrors, $oGui);
					$aErrorsAll = array_merge($aErrorsAll, $aErros);
					$bError = true;
				}
			}
		}

//		$aSelectedIds = explode('_', $sId);

		$sDialogId	= $_VARS['dialog_id'];
		$sId		= substr($sDialogId, 8);//PAYMENT_
		
		// Fehler nicht mehrfach anzeigen
		$aErrorsAll = array_values(array_unique($aErrorsAll));

		// Kein Fehler, erfolgreich
		if(
			empty($aErrorsAll) &&
			$bError === false
		) {
			DB::commit('inquiry_payment_save_new');
			$aErrorsAll = true;
		} else {
			DB::rollback('inquiry_payment_save_new');
		}

		// Fehler zurückgeben
		return $aErrorsAll;

	}

	/**
	 * saveDialogPaymentTab: Verknüpfung zu einer Agenturzahlung abspeichern und Creditnote-Beträge verteilen
	 *
	 * @param Ext_Thebing_Inquiry_Payment $oPayment
	 * @param Ext_Thebing_Agency_Payment $oAgencyPayment
	 * @param array $aCreditnotes
	 * @return bool
	 */
	protected static function saveAgencyPaymentAllocation(array $aCreditnotes, Ext_Thebing_Inquiry_Payment $oPayment, Ext_Thebing_Agency_Payment $oAgencyPayment = null) {

		if ($oAgencyPayment) {
			$oPayment->saveAgencyPaymentId($oAgencyPayment->id);
		}

		$fAmountTotal = $oPayment->amount_inquiry;

		// Ausgewählte CNs (echte Creditnotes und manuelle Creditnotes) verrechnen
		$aInquiryDocumentCreditnoteIds = [];
		foreach($aCreditnotes as $sType => $aIds) {
			foreach($aIds as $iId) {

				// Mehr CNs angegklickt als Summe der Zahlung: Abbruch
				if($fAmountTotal < 0) {
					break 2;
				}

				/** @var Ext_Thebing_Inquiry_Document|Ext_Thebing_Agency_Manual_Creditnote $oCreditnote */
				if($sType === 'manual_creditnote') {
					$oCreditnote = Ext_Thebing_Agency_Manual_Creditnote::getInstance($iId);
				} else {
					$oCreditnote = Ext_Thebing_Inquiry_Document::getInstance($iId);
					if($oCreditnote->type !== 'creditnote') {
						throw new RuntimeException('Invalid creditnote: '.$oCreditnote->id);
					}
				}

				if(!$oCreditnote->exist()) {
					throw new RuntimeException('Invalid creditnote: '.$oCreditnote->id);
				}

				// Neu berechnen und nicht aus dem Dialog nehmen, da Werte veraltet sein könnten
				$fOpenCreditnoteAmount = $oCreditnote->getCommissionAmount() - $oCreditnote->getAllocatedAccountingAmount();
				
				// Runden, da die Methode die Beträge errechnet und somit zu viele Nachkommastellen rauskommen können
				$fOpenCreditnoteAmount = Ext_Thebing_Format::roundBySchoolSettings($fOpenCreditnoteAmount);

				// Wenn CN schon voll bezahlt wurde: Ignorieren
				// Das hier ist z.B. notwendig bei Mehrfachauswahl, da ansonsten CN-Payouts mit dem Betrag 0 generiert werden
				if(bccomp($fOpenCreditnoteAmount, 0) == 0) {
					continue;
				}

				// Wenn offener CN-Betrag höher als übriger Betrag: Nur noch übrigen Betrag verrechnen
				if($fOpenCreditnoteAmount > $fAmountTotal) {
					$fOpenCreditnoteAmount = $fAmountTotal;
					$fAmountTotal = 0;
				} else {
					$fAmountTotal -= $fOpenCreditnoteAmount;
				}

				// Währungsfaktor zum Umrechnen des Schulbetrags
				$oInquiry = $oPayment->getInquiry();
				$oInquiryCurrency = Ext_Thebing_Currency::getInstance($oInquiry->getCurrency());
				$fSchoolFactor = $oInquiryCurrency->getConversionFactor($oInquiry->getSchool()->getCurrency());

				// Betrag an Creditnote (Dokument oder manuelle CN) zuweisen und Beträge auf Items verteilen
				$mReturn = $oCreditnote->saveCreditnotePayment($fOpenCreditnoteAmount, $fSchoolFactor, $oPayment, $oAgencyPayment);

				if($mReturn instanceof Ext_Thebing_Inquiry_Payment) {
					// IDs für Verknüpfung sammeln
					$aInquiryDocumentCreditnoteIds[] = $mReturn->getId();
				} elseif($mReturn instanceof Ext_Thebing_Agency_Manual_Creditnote_Payment) {
					// Nichts tun, das Payment hat eine Verknüpfung über die Agenturzahlung
				} else {
					// Wenn anderer Rückgabewert (eigentlich nur Array): Fehler
					$oPayment->aErrors = array_merge($oPayment->aErrors, $mReturn);
					return false;
				}

			}
		}

		// CN-Verrechnungen für diese Bezahlung speichern
		if(!empty($aInquiryDocumentCreditnoteIds)) {
			$oPayment->payment_creditnotes = $aInquiryDocumentCreditnoteIds;
			$oPayment->save(); // save() wird unter Umständen nicht mehr aufgerufen
		}

		return true;

	}

	public function saveAgencyPaymentId($iAgencyPaymentId) {
		$aKeys = array('payment_id' => (int)$this->id, 'agency_payment_id' => $iAgencyPaymentId);
		$aJoinData = array($iAgencyPaymentId);
		DB::updateJoinData('kolumbus_inquiries_payments_agencypayments', $aKeys, $aJoinData, 'agency_payment_id');
	}

	// Gibt den Betrag zurück der zuviel bezahlt wurde
	public function getOverpayAmount(){

		$sSql = " SELECT
						SUM(`kipo`.`amount_inquiry`) `sum`
					FROM
						`kolumbus_inquiries_payments_overpayment` `kipo` INNER JOIN
						`kolumbus_inquiries_payments` `kip` ON
							`kip`.`id` = `kipo`.`payment_id`
					WHERE
						`kipo`.`payment_id` = :payment_id AND
						`kip`.`active` =  1 AND
						`kipo`.`active` = 1";
		$aSql = array();
		$aSql['payment_id']	= (int)$this->id;

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		return (float)$aResult[0]['sum'];
	}

	/**
	 * TODO kann man hier nicht generell von $this->inquiry_id ausgehen?
	 * 	Nein.
	 *
	 * @see PostPaymentSave
	 */
	public function writePostSaveTask(\Ext_TS_Inquiry $inquiry, $createPDFs)
	{
		if (!$this->exist() || !$inquiry->exist()) {
			throw new \InvalidArgumentException('Invalid entities for post save task.');
		}

		$receipts = [];

		if ($createPDFs) {
			[$customerReceipts, $agencyReceipts] = \Ext_Thebing_Inquiry_Payment::getNeededPaymentReceiptTypes($inquiry, \Ext_Thebing_Inquiry_Payment::RECEIPT_PAYMENT);

			if ($customerReceipts) {
				$receipts = array_merge($receipts, $this->preparePaymentPdfs());
			}
			if ($agencyReceipts) {
				$receipts = array_merge($receipts, $this->preparePaymentPdfs(true));
			}
		}

		if (empty($this->aErrors)) {
			\Core\Entity\ParallelProcessing\Stack::getRepository()
				->writeToStack('ts/post-payment-save', [
					'payment_id' => $this->id,
					'inquiry_id' => $inquiry->id,
					'receipts' => $receipts
				], 1);
		}
	}

	/**
	 * Erstellung der Bezahlbelege vorbereiten (payment_receipt)
	 *
	 * Reihenfolge:
	 * 	1. Alle zugewiesenen Templates aus Zahlungsmethode holen
	 * 	2. Templates abfragen
	 * 	3. Templates prüfen, ob diese in der Sprache verfügbar sind
	 * 	4. Falls kein Template gefunden werden konnte, wird das Erstbeste genommen
	 *
	 * @see Ext_Thebing_Inquiry_Payment::createPaymentPdf()
	 *
	 * @param bool $bAgencyReceipt Agentur-Bezahlbeleg
	 * @param bool $bAsync
	 * @return array
	 * @throws Exception
	 */
	protected function preparePaymentPdfs($bAgencyReceipt = false) {

		$aInquiries = $this->getAllInquiries();

		// Wenn leer, wurde die (erste) Zahlung nicht gespeichert und es gibt keine Relation
		if(empty($aInquiries)) {
			return [];
		}

		$aPayedDocuments = $this->getDocuments();

		if(
			isset($aInquiries[$this->inquiry_id]) &&
			$aInquiries[$this->inquiry_id] instanceof Ext_TS_Inquiry
		) {
			$oPaymentInquiry = $aInquiries[$this->inquiry_id];
		} else {
			// Wenn ausgewählte Buchung nicht von Zahlung betroffen, dann explizit holen
			$oPaymentInquiry = Ext_TS_Inquiry::getInstance($this->inquiry_id);
			$aInquiries[$oPaymentInquiry->id] = $oPaymentInquiry;
		}

		$oPaymentCustomer = $oPaymentInquiry->getCustomer();
		$sLanguage = $oPaymentCustomer->getLanguage();
		$oSchool = $oPaymentInquiry->getSchool();
		$oInbox = $oPaymentInquiry->getInbox();

		$oClient = Ext_Thebing_Client::getFirstClient();
		$oPaymentMethod = $this->getMethod();
		$sLastLanguage = '';
		$mDocumentNumber = false;
		$iDocumentNumberrangeId = 0;

		$sType = 'receipt_customer';
		$sTemplateType = 'document_invoice_customer_receipt';
		$sPaymentMethodTemplateKey = 'reciept_template_customer';

		if(
			// Wenn rechtes PDF (agentur)
			$bAgencyReceipt &&
			// und es eine Agentur gibt
			$oPaymentInquiry->agency_id > 0	 &&
			(
				// Und
				(
					// NUR Das PDF der Zahlungsmethode erstellt werden soll
					$oClient->inquiry_payments_receipt == 1 &&
					// Darf es keine BRUTTO Zahlungsmethode sein
					$oPaymentInquiry->hasNettoPaymentMethod()
				) ||
				// Oder beide Doc. müssen erstellt werden
				$oClient->inquiry_payments_receipt == 2
			)
		){
			// Wenn Agentur
			$sType = 'receipt_agency';
			// Wenn "Netto" eingestellt ist
			if($oPaymentInquiry->hasNettoPaymentMethod()) {
				$sTemplateType = 'document_invoice_agency_receipt';
			// Wenn "Brutto" eingestellt ist
			} else {
				$sTemplateType = 'document_invoice_agency_receipt_brutto';
			}
			$sPaymentMethodTemplateKey = 'reciept_template_agency';
		} else if($bAgencyReceipt) {
			// wenn immernoch Rechtes PDF
			// das oben aber nicht zugetroffen hat
			// -> wird das 2te PDF nicht benötigt da das erste (brutto) ausreicht
			return [];
		} else if(
			// Wenn erstes PDF
			!$bAgencyReceipt &&
			// Und Agentur
			$oPaymentInquiry->agency_id > 0	 &&
			// und Zahlungsmethode NETTO
			$oPaymentInquiry->hasNettoPaymentMethod() &&
			// Und nur zahlungsmethoden PDF -> wird nur das erst
			$oClient->inquiry_payments_receipt == 1
		) {
			// wird das erste nicht benötigt da das pdf brutto wäre
			// was nicht benötigt wird
			return [];
		}

		$aTemplates = array();

		$aRecieptTemplateIds = $oPaymentMethod->$sPaymentMethodTemplateKey;
		foreach((array)$aRecieptTemplateIds as $aRecieptTemplateId) {

			//nur die jenigen PDFs generieren die auch dem gewählten PaymentType entsprechen (vor Abreise, vor Ort, refund) sowie der PaymentMethod (Scheck, Creditcard,..)
			if($this->type_id == $aRecieptTemplateId['payment_type_id']) {
				$oRecieptTemplate = Ext_Thebing_Pdf_Template::getInstance((int)$aRecieptTemplateId['template_id']);

				if(
					in_array($sLanguage, $oRecieptTemplate->languages) &&
					in_array($oInbox->id, $oRecieptTemplate->inboxes)
				) {
					$aTemplates[] = $oRecieptTemplate;
				}

			}

		}

		// Template suchen
		// TODO Wenn kein Template eingestellt ist, warum wird hier dann trotzdem gesucht?
		// TODO Entfernen
		if(empty($aTemplates)) {
			$aTemplate = Ext_Thebing_Pdf_Template_Search::s($sTemplateType, $sLanguage, $oSchool->id, $oInbox->id);

			$oTemplate = reset($aTemplate);
			// Wenn nicht gefunden aber zur vorgänger sprache, dann nehme dieses

			if(empty($aTemplate) && $sLastLanguage != '') {
				$sLanguage = $sLastLanguage;
				$aTemplate = Ext_Thebing_Pdf_Template_Search::s($sTemplateType, $sLanguage, $oSchool->id, $oInbox->id);

				$oTemplate = reset($aTemplate);
			}

			$aTemplates = array($oTemplate);
		}

		// Überprüfung, ob Array ein richtige Array ist
		foreach((array)$aTemplates as $iKey => $oTemplate) {
			if(!($oTemplate instanceof Ext_Thebing_Pdf_Template)) {
				unset($aTemplates[$iKey]);
			}
		}

		// Wenn kein Template gefunden wird, muss auch kein Beleg generiert werden
		if(empty($aTemplates)) {

			try {
				// Die Methode ruft hasRight auf und ist damit nicht geeignet fürs PP
				$sTemplateLabel = Ext_Thebing_Pdf_Template::getApplications($sTemplateType);
			} catch (Exception $e) {
				$sTemplateLabel = $sTemplateType;
			}

			//löschen damit keine halben payments in der db sind
			$aError = array('message' => L10N::t('Kein PDF Template gefunden!', 'Thebing » Errors') . ' (' . $sTemplateLabel . ')');
			$this->aErrors[] = $aError;

			return [];
		}

		$aStackData = array();

		// Zu jeder Buchung das Dokument erzeugen
		foreach((array)$aInquiries as $oInquiry) {

			// Nummer wird nur für erstes Dokument erzeugt, danach immer nur kopiert
			if($mDocumentNumber === false) {

				// Numberrange der letzten Rechnung
				//$oLastInvoiceDocument = $oInquiry->getDocuments('invoice', false, true);
				// Numberrange der ersten Rechnung aus der Bezahlung nehmen
				$oLastInvoiceDocument = \Illuminate\Support\Arr::first($aPayedDocuments);

				$iInvoiceNumberrangeId = null;
				if($oLastInvoiceDocument) {
					// Anmerkung: Hier stand zuvor $oDocument->numberrange_id ($oDocument gab es nicht, 05.08.2014)
					$iInvoiceNumberrangeId = $oLastInvoiceDocument->numberrange_id;
				}

				if($oInquiry instanceof Ext_TS_Inquiry) {
					$oInbox = $oInquiry->getInbox();
					Ext_TS_NumberRange::setInbox($oInbox);
				}

				$oNumberrange = Ext_Thebing_Inquiry_Document_Numberrange::getObject($sType, false, $oSchool->id, $iInvoiceNumberrangeId);
				$oNumberrange->bAllowDuplicateNumbers = true; // Nicht so wichtig hier, außer der Kunde hat überall denselben Nummernkreis…

				if(
					$oNumberrange instanceof Ext_TC_NumberRange &&
					$oNumberrange->id > 0
				) {
					if($oNumberrange->acquireLock()) {
						$sNumber = $oNumberrange->generateNumber();
					} else {
						$this->aErrors[] = array('message' => 'NUMBERRANGE_LOCKED');
						return [];
					}
				} else {
					// Nach #6134 müssen Bezahlbelege nun einen Nummernkreis haben
					$this->aErrors[] = array('message' => 'NUMBERRANGE_REQUIRED');
					return [];
				}

				$mDocumentNumber = $sNumber;
				$iDocumentNumberrangeId = $oNumberrange->id;

			}

			// Alle Templates durchlaufen und Dokumente für jeden Template-Typ erstellen
			foreach($aTemplates as $oTemplate) {

				// Bereits vorhandenen Zahlungsbeleg mit diesem Typen finden
				$oDocument = $this->getReceipts($oInquiry, $sType)->first();
				if (!$oDocument) {
					$oDocument = $oInquiry->newDocument($sType);
				}

				$oDocument->status = 'pending';

				if($oDocument->document_number == '') {
					$oDocument->document_number = $mDocumentNumber;
					$oDocument->numberrange_id = $iDocumentNumberrangeId;
				}

				$oDocument->bUpdateIndexEntry = false;
				$oDocument->save();

				// Relation zwischen Zahlung und Dokument
				$this->{$this::JOINTABLE_RECEIPTS} = [...$this->{$this::JOINTABLE_RECEIPTS}, $oDocument->id];

				if($oInquiry->id == $oPaymentInquiry->id) {

					// Daten für createPaymentPdf und Platzhalter
					// []-Operator, da $oPaymentInquiry nicht die erste Inquiry sein muss
					$aStackData[$oTemplate->id]['type'] = 'payment_receipt';
					$aStackData[$oTemplate->id]['payment_id'] = $this->id;
					$aStackData[$oTemplate->id]['main_document_id'] = $oDocument->id;
					$aStackData[$oTemplate->id]['template_id'] = $oTemplate->id;
					$aStackData[$oTemplate->id]['agency_receipt'] = $bAgencyReceipt;

				} else {

					// Dokument wird nur für ausgewählte Buchung erzeugt, Rest wird kopiert (Gruppen!)
					$aStackData[$oTemplate->id]['document_ids'][] = $oDocument->id;

				}
			}

			// Nummernkreis nach dem Speichern des ersten Dokuments freigeben
			// Einfache Freigabe hier sollte funktionieren, da Methode im Fehlerfall mit return endet
			if(isset($oNumberrange)) {
				$oNumberrange->removeLock();

				// Jeder Durchlauf würde die Sperre global erneut freigeben, obwohl die Sperre schon weg ist
				unset($oNumberrange);
			}
		}

		///** @var Core\Entity\ParallelProcessing\StackRepository $oStackRepository */
		//$oStackRepository = Stack::getRepository();
		// Pro Template einen Eintrag erzeugen (es gibt nur ein Dokument)
		//foreach($aStackData as $aData) {
		//	$oStackRepository->writeToStack('ts/document-generating', $aData, 8);
		//}

		// Dokument-Relationen müssen abgespeichert werden
		$this->save();

		return $aStackData;
	}

	/**
	 * Bezahlbelege erzeugen (payment_receipt)
	 *
	 * Bei Gruppen wird nur für die ausgewählte Buchung der Bezahlbeleg erzeugt,
	 * der Rest wird kopiert (analog zur Rechnungserstellung bei Gruppen).
	 *
	 * @see Ext_Thebing_Inquiry_Payment::preparePaymentPdfs()
	 *
	 * @param array $aData Werte aus preparePaymentPdfs()
	 * @return bool
	 */
	public function createPaymentPdf(array $aData) {

		$oMainDocument = Ext_Thebing_Inquiry_Document::getInstance($aData['main_document_id']);
		$oTemplate = Ext_Thebing_Pdf_Template::getInstance($aData['template_id']);
		$bAgencyReceipt = $aData['agency_receipt'];

		if(
			$oMainDocument->id == 0 ||
			$oTemplate->id == 0
		) {
			throw new InvalidArgumentException('Invalid document ('.$aData['document_id'].') or template ('.$aData['template_id'].')');
		}

		$oInquiry = $oMainDocument->getInquiry();
		$oCustomer = $oInquiry->getCustomer();
		$sLang = $oCustomer->getLanguage();
		$oSchool = $oInquiry->getSchool();
		$oPlaceholder = new Ext_Thebing_Inquiry_Placeholder($oInquiry->id, 0, $oSchool->id); // $oSchool->id ist sehr wichtig im Parallel Processing!
		$oPlaceholder->_oDocument = $oMainDocument;

		// Spezielle Beleg-Platzhalter
		$aSpecialPlaceholders = $this->getPaymentPdfPlaceholderData($oMainDocument, $bAgencyReceipt);

		// Bisher gab es keine Version, diese wird jetzt erst angelegt
		$oMainVersion = $oMainDocument->newVersion();

		$oMainVersion->date = $oTemplate->getStaticElementValue($sLang, 'date');
		$oMainVersion->txt_address = $oTemplate->getStaticElementValue($sLang, 'address');
		$oMainVersion->txt_subject = $oTemplate->getStaticElementValue($sLang, 'subject');
		$oMainVersion->txt_intro = $oTemplate->getStaticElementValue($sLang, 'text1');
		$oMainVersion->txt_outro = $oTemplate->getStaticElementValue($sLang, 'text2');
		$oMainVersion->txt_pdf = $oTemplate->getOptionValue($sLang, $oSchool->id, 'first_page_pdf_template');
		$oMainVersion->txt_signature = $oTemplate->getOptionValue($sLang, $oSchool->id, 'signatur_text');
		$oMainVersion->signature = $oTemplate->getOptionValue($sLang, $oSchool->id, 'signatur_img');
		$oMainVersion->comment = $this->comment;
		$oMainVersion->template_id = $oTemplate->id;
		$oMainVersion->template_language = $sLang;

		// Spezielle Beleg-Platzhalter ersetzen
		$oMainVersion->txt_address = str_replace($aSpecialPlaceholders['search'], $aSpecialPlaceholders['replace'], $oMainVersion->txt_address);
		$oMainVersion->txt_subject = str_replace($aSpecialPlaceholders['search'], $aSpecialPlaceholders['replace'], $oMainVersion->txt_subject);
		$oMainVersion->txt_intro = str_replace($aSpecialPlaceholders['search'], $aSpecialPlaceholders['replace'], $oMainVersion->txt_intro);
		$oMainVersion->txt_outro = str_replace($aSpecialPlaceholders['search'], $aSpecialPlaceholders['replace'], $oMainVersion->txt_outro);

		// Normale Platzhalter
		$oMainVersion->date = $oPlaceholder->replace($oMainVersion->date);
		$oMainVersion->txt_address = $oPlaceholder->replace($oMainVersion->txt_address);
		$oMainVersion->txt_subject = $oPlaceholder->replace($oMainVersion->txt_subject);
		$oMainVersion->txt_intro = $oPlaceholder->replace($oMainVersion->txt_intro);
		$oMainVersion->txt_outro = $oPlaceholder->replace($oMainVersion->txt_outro);

		$oMainVersion->date = Ext_Thebing_Format::ConvertDate($oMainVersion->date, $oSchool->id, 1);

		// Wenn Layout Positionen hat: Pro Dokument der Zahlung eine Tabelle generieren
		$aTables = [];
		if($oTemplate->canShowInquiryPositions()) {
			$aPaymentDocuments = (array)$this->getJoinTableObjects('documents'); /** @var Ext_Thebing_Inquiry_Document[] $aPaymentDocuments */
			foreach($aPaymentDocuments as $oPaymentDocument) {
				$aTables[] = $oPaymentDocument->generatePaymentOverviewPositionTable($bAgencyReceipt, [$this->id]);
			}
		}

		// PDF wird nur einmal erzeugt
		$oPDF = new Ext_Thebing_Pdf_Basic($oMainVersion->template_id, $oSchool->id);
		$oPDF->setAllowSave(false); // Nicht speichern lassen, da ansonsten zwei verschiedene Instanzen existieren
		$oPDF->createDocument($oMainDocument, $oMainVersion, $aTables);
		$aFileName = Ext_Thebing_Inquiry_Document::buildFileNameAndPath($oMainDocument, $oMainVersion, $oSchool);
		$sFilePath = $oPDF->createPdf($aFileName['path'], $aFileName['filename']);

		if(!is_file($sFilePath)) {
			throw new RuntimeException('File "'.$sFilePath.'" does not exists!');
		}

		$oMainVersion->path = $oMainVersion->prepareAbsolutePath($sFilePath);

		// Dokument als fertig generiert markieren
		$oMainDocument->status = 'ready';
		$oMainDocument->save();

		// Gruppen: Pro Dokument-ID eigene Version erzeugen, aber als PDF das zuvor generierte setzen
		foreach((array)$aData['document_ids'] as $iDocumentId) {
			$oDocument = Ext_Thebing_Inquiry_Document::getInstance($iDocumentId);

			// Sollte das mit dem Neuholen der Version schief gehen, Excepion schmeißen…
			if(empty($oMainVersion->path)) {
				throw new RuntimeException('Path of main version empty: WDBasic instance problem?');
			}

			$oVersion = $oDocument->newVersion();
			$oVersion->date = $oMainVersion->date;
			$oVersion->txt_address = $oMainVersion->txt_address;
			$oVersion->txt_subject = $oMainVersion->txt_subject;
			$oVersion->txt_intro = $oMainVersion->txt_intro;
			$oVersion->txt_outro = $oMainVersion->txt_outro;
			$oVersion->txt_pdf = $oMainVersion->txt_pdf;
			$oVersion->txt_signature = $oMainVersion->txt_signature;
			$oVersion->signature = $oMainVersion->signature;
			$oVersion->comment = $oMainVersion->comment;
			$oVersion->template_id = $oMainVersion->template_id;
			$oVersion->template_language = $oMainVersion->template_language;
			$oVersion->path = $oMainVersion->path;
			$oVersion->save();

			$oDocument->status = 'ready';
			$oDocument->save();
		}

		return true;
	}

	/**
	 * Liefert ein str_replace()-kompatibles Array für das Ersetzen der speziellen Beleg-Platzhalter
	 *
	 * @param Ext_Thebing_Inquiry_Document $oDocument
	 * @param bool $bAgencyReceipt
	 * @return array
	 */
	protected function getPaymentPdfPlaceholderData(Ext_Thebing_Inquiry_Document $oDocument, $bAgencyReceipt) {

		$oInquiry = $oDocument->getInquiry();
		$oSchool = $oInquiry->getSchool();

		$oTemp = null;
		$aFormatData = array('school_id' => $oSchool->id);

		$oFormatDate = new Ext_Thebing_Gui2_Format_Date();
		$mDate = $oFormatDate->format($this->date, $oTemp, $aFormatData);

		$sPaymentMethodName = $this->getMethod()->getName();
		$fAmountOverpay = $this->getOverpayAmount();

		// Wenn Kundenquittung ABER Agentur
		// muss ihrgendwie ien brutto betrag hergezaubert werde...
		if(
			$oDocument->type == 'receipt_customer' &&
			$oInquiry->agency_id > 0 &&
			$oInquiry->hasNettoPaymentMethod()
		) {
			// Betrag auf BRUTTO umrechnen, schwachsin aber was solls....
			$bCalculateAgencyGross = true;
		} else {
			$bCalculateAgencyGross = false;
		}

		$fAmountPayedTotal = 0;
		$aInvoiceDocumentNumbers = array();

		// Alle zu dieser Bezahlung zugewiesen Dokumente (über Items und über Überbezahlungen)
		$aAllocatedDocuments = $this->getJoinTableObjects('documents'); /** @var Ext_Thebing_Inquiry_Document[] $aAllocatedDocuments */
		foreach ($aAllocatedDocuments as $oAllocatedDocument) {
			$fAmountPayedTotal += $oAllocatedDocument->getPayedAmount(0, 0, $bCalculateAgencyGross, [$this->id]);
			$aInvoiceDocumentNumbers[] = $oAllocatedDocument->document_number;
		}

		// Das Zaubern findet bereits in getPayedAmount() statt und funktioniert korrekter als der Query in $this->getPayedAmount()
		$fAmountPayed = $fAmountPayedTotal + $fAmountOverpay;
		$sAmountPayed = Ext_Thebing_Format::Number($fAmountPayed, $oInquiry->getCurrency(), $oSchool->id);

		$sInvoiceDocumentNumber = implode(', ', array_unique($aInvoiceDocumentNumbers));

		$aData = [
			'search' => ['{receipt_amount_paid}', '{receipt_comment}', '{receipt_method}', '{receipt_date}', '{document_number}'],
			'replace' => [$sAmountPayed, $this->comment, $sPaymentMethodName, $mDate, $sInvoiceDocumentNumber]
		];

		return $aData;
	}

	/**
	 * Generieren der Bezahlübersicht pro Buchung vorbereiten (inquiry_payment_overview)
	 *
	 * @see Ext_TS_Inquiry::createInquiryDocumentOverview()
	 *
	 * Bei Gruppen wird – analog zur Rechnungserstellung – nur ein PDF erstellt,
	 * aber für jedes Gruppenmitglied ein Dokument und Versionen.
	 * @param bool $bAgencyDocument
	 * @return bool
	 */
	public function prepareInquiryPaymentOverviewPdfs($bAgencyDocument=false) {

		$aInquiries = $this->getAllInquiries();

		// Wenn leer, wurde die (erste) Zahlung nicht gespeichert und es gibt keine Relation
		if(empty($aInquiries)) {
			return false;
		}

		if(
			isset($aInquiries[$this->inquiry_id]) &&
			$aInquiries[$this->inquiry_id] instanceof Ext_TS_Inquiry
		) {
			$oPaymentInquiry = $aInquiries[$this->inquiry_id];
		} else {
			// Wenn ausgewählte Buchung nicht von Zahlung betroffen, dann explizit holen
			$oPaymentInquiry = Ext_TS_Inquiry::getInstance($this->inquiry_id);
			$aInquiries[$oPaymentInquiry->id] = $oPaymentInquiry;
		}

		$sDocumentNumber = '';
		$iNumberrangeId = 0;

		$sType = 'document_payment_overview_customer';
		$sTemplateType = 'document_customer_document_payment_overview';

		$oCustomer = $oPaymentInquiry->getCustomer();
		$oSchool = $oPaymentInquiry->getSchool();
		$sLanguage = $oCustomer->getLanguage();
		$oInbox = $oPaymentInquiry->getInbox();

		if($bAgencyDocument) {

			$oAgency = $oPaymentInquiry->getAgency();
			if($oAgency instanceof Ext_Thebing_Agency) {

				$sType = 'document_payment_overview_agency';
				if($oPaymentInquiry->hasNettoPaymentMethod()) {
					// Netto
					$sTemplateType = 'document_agency_document_payment_overview';
				} else {
					// Brutto
					$sTemplateType = 'document_customer_document_payment_overview';
				}
			} else {
				return true;
			}
		}

		// Template suchen
		$aTemplate = Ext_Thebing_Pdf_Template_Search::s($sTemplateType, $sLanguage, $oSchool->id, $oInbox->id);

		// Wenn kein Template gefunden werden konnte muss auch kein Bezahlbeleg generiert werden
		if(empty($aTemplate)) {
			return true;
		}

		$oTemplate = reset($aTemplate);

		// Bereits vorhandenes Übersichtsdokumente suchen
		$aInquiryPaymentOverviews = array(); /** @var Ext_Thebing_Inquiry_Document[] $aInquiryPaymentOverviews */
		foreach($aInquiries as $oInquiry) {
			$oLastDocument = $oInquiry->getDocuments($sType, false, true);

			if($oLastDocument) {
				$oDocument = $oLastDocument;
			} else {
				$oDocument = $oInquiry->newDocument($sType);
			}

			// Schauen, ob irgendein (in Bezug auf Gruppen) Dokument eine Nummer hat, damit diese verwendet werden kann
			// Bei einer Gruppe haben alle Gruppenmitglieder dieselbe Nummer bei diesem Bezahlbeleg
			if($oDocument->document_number !== '') {
				$sDocumentNumber = $oDocument->document_number;
				$iNumberrangeId = $oDocument->numberrange_id;
			}


			$aInquiryPaymentOverviews[$oInquiry->id] = $oDocument;
		}

		$aStackData = array(
			'type' => 'inquiry_payment_overview',
			'inquiry_id' => $oPaymentInquiry->id,
			'template_id' => $oTemplate->id
		);

		// Dokumente anlegen und ggf. Dokumentennummer erzeugen
		foreach($aInquiries as $oInquiry) {

			$oDocument = $aInquiryPaymentOverviews[$oInquiry->id];

			// Wenn Nummer leer: Nummer von bereits vorhandenem Dokument nehmen oder neu generieren
			if($oDocument->document_number === '') {

				// Es konnte keine Nummer gefunden werden, daher muss eine generiert werden
				if($sDocumentNumber === '') {
					// Numberrange der letzten Rechnung
					$oLastInvoiceDocument = $oInquiry->getDocuments('invoice', false, true);

					$iInvoiceNumberrangeId = null;
					if($oLastInvoiceDocument) {
						$iInvoiceNumberrangeId = $oLastInvoiceDocument->numberrange_id;
					}

					Ext_TS_NumberRange::setInbox($oInbox);

					$oNumberrange = Ext_Thebing_Inquiry_Document_Numberrange::getObject($sType, false, $oSchool->id, $iInvoiceNumberrangeId);
					$oNumberrange->bAllowDuplicateNumbers = true; // Nicht so wichtig hier, außer der Kunde hat überall denselben Nummernkreis…

					// Nummernkreis darf kein leeres Objekt sein, sonst schlägt $oDocument->save() fehl
					if(
						!$oNumberrange instanceof Ext_TC_NumberRange ||
						$oNumberrange->id == 0
					) {
						// Nach #6134 müssen Bezahlbelege nun einen Nummernkreis haben
						$this->aErrors[] = array('message' => 'NUMBERRANGE_REQUIRED');
						return false;
					}

					if(!$oNumberrange->acquireLock()) {
						$this->aErrors[] = array('message' => 'NUMBERRANGE_LOCKED');
						return false;
					}

					$sDocumentNumber = $oNumberrange->generateNumber();
					$iNumberrangeId = $oNumberrange->id;
				}

				$oDocument->document_number = $sDocumentNumber;
				$oDocument->numberrange_id = $iNumberrangeId;
			}

			// Dokument muss noch vom ParallelProcessing generiert werden
			$oDocument->status = 'pending';

			$oDocument->bUpdateIndexEntry = false;
			$mSave = $oDocument->save();

			if(is_array($mSave)) {
				// Normalerweise Fehler vom Nummernkreis
				$this->aErrors[] = array('message' => reset($mSave));
				return false;
			}

			// Nummernkreis nach dem Speichern des Dokuments freigeben
			if(isset($oNumberrange)) {
				$oNumberrange->removeLock();

				// Jeder Durchlauf würde die Sperre global erneut freigeben, obwohl die Sperre schon weg ist
				unset($oNumberrange);
			}

			if($oPaymentInquiry->id == $oInquiry->id) {
				// Ausgewählte Buchung liefert Hauptdokument und Daten für Platzhalter
				$aStackData['main_document_id'] = $oDocument->id;
			} else {
				// Alle andere Buchungen (der Gruppe) erhalten Kopie der Version
				$aStackData['document_ids'][] = $oDocument->id;
			}
		}

		$oStackRepository = Stack::getRepository();
		$oStackRepository->writeToStack('ts/document-generating', $aStackData, 8);

		return true;
	}

	/**
	 * Erzeugt ein Query für eine Liste mit Items dieses Objektes
	 * @return array
	 */
	public function getListQueryData($oGui=null) {

		$aQueryData = array();

		$aQueryData['data'] = array();

		$sInterfaceLanguage = Ext_TC_System::getInterfaceLanguage();

		$aQueryData['sql'] = "
				SELECT
					`ip`.*,
					`kpm`.`name` `method_name`,
					SUM(`ip`.`amount`) `amount`,
					UNIX_TIMESTAMP(`ip`.`changed`) `changed`,
					UNIX_TIMESTAMP(`ip`.`created`) `created`
				FROM
					(
						/* Zahlungen mit Document-Items */
						(
							SELECT
								`kip`.*,
								`ts_ipr`.`created` `release_time`,
								`ts_ipr`.`creator_id` `release_time_by`,
								`tc_c`.`lastname`,
								`tc_c`.`firstname`,
								`tc_c_n`.`number` `customerNumber`,
								`kid`.`document_number`,
								`ts_i_j`.`school_id`,
								`ts_i`.`currency_id` `currency_id`,
								(
									SELECT 
										SUM(`amount_inquiry`) 
									FROM 
										`kolumbus_inquiries_payments_items` `kipi_sub` 
									WHERE 
										`kipi_sub`.`payment_id` = `kip`.`id` AND
										`kipi_sub`.`active` = 1
								) `amount`,
								/*
									TODO Könnte man theoretisch auch direkt berechnen aber die Liste hat eh schon Performance-Probleme? 
									(siehe Ext_Thebing_Accounting_Gui2_Agency_Provision->getTableQueryData())
								*/
							    GROUP_CONCAT(DISTINCT
								    IF (
								    	INSTR(`kid`.`type`, 'netto') = 0,
								    	CONCAT(`kidvpi`.`id`, '{|}', `kidv`.`tax`, '{|}', `kidvpi`.`amount_gross`, '{|}', `kidvpi`.`amount_discount_gross`, '{|}', `kidvpi`.`amount_vat_gross`),
								    	CONCAT(`kidvpi`.`id`, '{|}', `kidv`.`tax`, '{|}', `kidvpi`.`amount_net`, '{|}', `kidvpi`.`amount_discount_net`, '{|}', `kidvpi`.`amount_vat_net`)
								    )
								SEPARATOR '{||}') `amount_documents`,
								`ka`.`ext_1` `agency`,
								`ts_an`.`number` `agency_number`,
								`ts_i`.`inbox`,
								`dc`.`cn_short_{$sInterfaceLanguage}` `nationality`,
								`ts_i`.`service_from` `service_from`,
								`ts_i`.`service_until` `service_until`,
								MIN(`kidvi`.`index_from`) `items_service_from`,
								MAX(`kidvi`.`index_until`) `items_service_until`,
								`ka`.`id` `agency_id`,
								`ts_i`.`sales_person_id`,
								`ts_i`.`created` `inquiry_created`,
								`ts_i`.`number` `inquiry_number`,
								(
									SELECT 
										`kid_receipt_sub`.`document_number`
									FROM
										`kolumbus_inquiries_payments_documents` `kipd_sub` JOIN
										`kolumbus_inquiries_documents` `kid_receipt_sub` ON
												`kid_receipt_sub`.`id` = `kipd_sub`.`document_id` 
									WHERE
										`kipd_sub`.`payment_id` = `kip`.`id`
									LIMIT 1
								) `receipt_number`
							FROM
								`kolumbus_inquiries_payments` `kip` LEFT JOIN
								`ts_inquiries_payments_release` `ts_ipr` ON
									`ts_ipr`.`payment_id` = `kip`.`id` JOIN
								`kolumbus_inquiries_payments_items` `kipi` ON
									`kipi`.`payment_id` = `kip`.`id` AND
									`kipi`.`active` = 1 INNER JOIN
								`kolumbus_inquiries_documents_versions_items` `kidvi` ON
									`kidvi`.`id` = `kipi`.`item_id` INNER JOIN
								`kolumbus_inquiries_documents_versions` `kidv` ON
									`kidv`.`id` = `kidvi`.`version_id` LEFT JOIN
						    	`kolumbus_inquiries_documents_versions_priceindex` `kidvpi` ON 
						    		`kidvpi`.`version_id` = `kidv`.`id` AND
						    		`kidvpi`.`active` = 1 INNER JOIN
								`kolumbus_inquiries_documents` `kid` ON
									`kid`.`id` = `kidv`.`document_id` AND
									`kid`.`type` != 'creditnote' INNER JOIN
								`ts_inquiries`	`ts_i` ON
									`kid`.`entity` = '".Ext_TS_Inquiry::class."' AND
									`ts_i`.`id` = `kid`.`entity_id` INNER JOIN
								`ts_inquiries_journeys` `ts_i_j` ON
									`ts_i_j`.`inquiry_id` = `ts_i`.`id` AND
									`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
									`ts_i_j`.`active` = 1 LEFT JOIN
								`ts_inquiries_to_contacts` `ts_i_to_c` ON
									`ts_i_to_c`.`inquiry_id` = `ts_i`.`id` AND
									`ts_i_to_c`.`type` = 'traveller' INNER JOIN
								`tc_contacts` `tc_c` ON
									`tc_c`.`id` = `ts_i_to_c`.`contact_id` AND
									`tc_c`.`active` = 1	LEFT JOIN
								`tc_contacts_numbers` `tc_c_n` ON
									`tc_c_n`.`contact_id` = `tc_c`.`id` LEFT JOIN
								`data_countries` `dc` ON
									`dc`.`cn_iso_2` = `tc_c`.`nationality` LEFT JOIN
								`ts_companies` `ka` ON
									`ts_i`.`agency_id` = `ka`.`id` AND
									`ka`.`active` = 1 LEFT JOIN
								`ts_companies_numbers` `ts_an` ON
									`ts_an`.`company_id` = `ka`.`id`
							WHERE
								`kip`.`active` = 1
							GROUP BY
								`kip`.`id`
							ORDER BY
								`kip`.`date` DESC
						)
						UNION ALL
						/* Überbezahlungen */
						(
							SELECT
								`kip`.*,
								`ts_ipr`.`created` `release_time`,
								`ts_ipr`.`creator_id` `release_time_by`,
								`tc_c`.`lastname`,
								`tc_c`.`firstname`,
								`tc_c_n`.`number` `customerNumber`,
								`kid`.`document_number`,
								`ts_i_j`.`school_id`,
								`ts_i`.`currency_id` `currency_id`,
								(
									SELECT 
										SUM(`amount_inquiry`) 
									FROM 
										`kolumbus_inquiries_payments_overpayment` `kipo_sub` 
									WHERE 
										`kipo_sub`.`payment_id` = `kip`.`id` AND
										`kipo_sub`.`active` = 1
								) `amount`,
								/* 
									TODO Könnte man theoretisch auch direkt berechnen aber die Liste hat eh schon Performance-Probleme? 
									(siehe Ext_Thebing_Accounting_Gui2_Agency_Provision->getTableQueryData())
								*/
								GROUP_CONCAT(DISTINCT
								    IF (
								    	INSTR(`kid`.`type`, 'netto') = 0,
								    	CONCAT(`kidvpi`.`id`, '{|}', `kidv`.`tax`, '{|}', `kidvpi`.`amount_gross`, '{|}', `kidvpi`.`amount_discount_gross`, '{|}', `kidvpi`.`amount_vat_gross`),
								    	CONCAT(`kidvpi`.`id`, '{|}', `kidv`.`tax`, '{|}', `kidvpi`.`amount_net`, '{|}', `kidvpi`.`amount_discount_net`, '{|}', `kidvpi`.`amount_vat_net`)
								    )
								SEPARATOR '{||}') `amount_documents`,
								`ka`.`ext_1` `agency`,
								`ts_an`.`number` `agency_number`,
								`ts_i`.`inbox`,
								`dc`.`cn_short_{$sInterfaceLanguage}` `nationality`,
								`ts_i`.`service_from` `service_from`,
								`ts_i`.`service_until` `service_until`,
								MIN(`kidvi`.`index_from`) `items_service_from`,
								MAX(`kidvi`.`index_until`) `items_service_until`,
								`ka`.`id` `agency_id`,
								`ts_i`.`sales_person_id`,
								`ts_i`.`created` `inquiry_created`,
								`ts_i`.`number` `inquiry_number`,
								(
									SELECT 
										`kid_receipt_sub`.`document_number`
									FROM
										`kolumbus_inquiries_payments_documents` `kipd_sub` JOIN
										`kolumbus_inquiries_documents` `kid_receipt_sub` ON
												`kid_receipt_sub`.`id` = `kipd_sub`.`document_id` 
									WHERE
										`kipd_sub`.`payment_id` = `kip`.`id`
									LIMIT 1
								) `receipt_number`
							FROM
								`kolumbus_inquiries_payments` `kip` LEFT JOIN
								`ts_inquiries_payments_release` `ts_ipr` ON
									`ts_ipr`.`payment_id` = `kip`.`id` INNER JOIN
								`kolumbus_inquiries_payments_overpayment` `kipo` ON
									`kipo`.`payment_id` = `kip`.`id` AND
									`kipo`.`active` = 1 LEFT JOIN
								`kolumbus_inquiries_documents` `kid` ON
									`kid`.`id` = `kipo`.`inquiry_document_id` AND
									`kid`.`type` != 'creditnote' INNER JOIN
								`kolumbus_inquiries_documents_versions` `kidv` ON
									`kidv`.`id` = `kid`.`latest_version` LEFT JOIN
						    	`kolumbus_inquiries_documents_versions_priceindex` `kidvpi` ON 
						    		`kidvpi`.`version_id` = `kidv`.`id` AND
						    		`kidvpi`.`active` = 1 INNER JOIN
								`kolumbus_inquiries_documents_versions_items` `kidvi` ON
									`kidvi`.`version_id` = `kidv`.`id` AND
									`kidvi`.`active` = 1 INNER JOIN
								`ts_inquiries`	`ts_i` ON
									`ts_i`.`id` = `kip`.`inquiry_id` INNER JOIN
								`ts_inquiries_journeys` `ts_i_j` ON
									`ts_i_j`.`inquiry_id` = `ts_i`.`id` AND
									`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
									`ts_i_j`.`active` = 1 LEFT JOIN
								`ts_inquiries_to_contacts` `ts_i_to_c` ON
									`ts_i_to_c`.`inquiry_id` = `ts_i`.`id` AND
									`ts_i_to_c`.`type` = 'traveller' INNER JOIN
								`tc_contacts` `tc_c` ON
									`tc_c`.`id` = `ts_i_to_c`.`contact_id` AND
									`tc_c`.`active` = 1	LEFT JOIN
								`tc_contacts_numbers` `tc_c_n` ON
									`tc_c_n`.`contact_id` = `tc_c`.`id` LEFT JOIN
								`data_countries` `dc` ON
									`dc`.`cn_iso_2` = `tc_c`.`nationality` LEFT JOIN
								`ts_companies` `ka` ON
									`ts_i`.`agency_id` = `ka`.`id` AND
									`ka`.`active` = 1 LEFT JOIN
								`ts_companies_numbers` `ts_an` ON
									`ts_an`.`company_id` = `ka`.`id`
							WHERE
								`kip`.`active` = 1
							GROUP BY
								`kip`.`id`
							ORDER BY
								`kip`.`date` DESC

						)
					) `ip` LEFT JOIN
					`kolumbus_payment_method` AS `kpm` ON
						`ip`.`method_id` = `kpm`.`id`  LEFT JOIN
					`ts_inquiries_payments_release`	`ts_ipr` ON
						`ts_ipr`.`payment_id` = `ip`.`id`
			GROUP BY
				`id`
			ORDER BY
				`date` DESC
		";

		$aQueryData['sql'] = str_replace('{TABLE}', $this->_sTable, $aQueryData['sql']);

		return $aQueryData;

	}

	public static function getPaymentErrorMessages($aErrors, $oGui){
		return self::_getPaymentErrorMessages($aErrors, $oGui);
	}
	
	/**
	 * Fehlermeldungen vorbereiten nach ErrorKey
	 * @todo überall wo L10N mit Fehlern definiert wird in diese Funktion reinbringen saveNewPayment Fehlerbehandlung anders lösen
	 * 2.Paremeter nicht entfernen, falls das eine Listenspezifische Übersetzung sein soll
	 * @param array $aErrors
	 * @param Ext_Gui2 $oGui
	 * @return array
	 */
	protected static function _getPaymentErrorMessages($aErrors, $oGui) {

		$aErrorMessages = array();
		$aErrors = (array)$aErrors;
		
		foreach($aErrors as $mColumn => $mErrorKey) {
			
			switch($mColumn) {
				case 'currency_id':
				case 'amount_currency':
					$sLabel = $oGui->t('Währung');
					break;
				case 'currency_school_id':
				case 'amount_school_currency':
					$sLabel = $oGui->t('Schulwährung');
					break;
				default:
					$sLabel = $mColumn;
			}
			
			if(is_array($mErrorKey)) {
				foreach($mErrorKey as $sError) {
					$sErrorMessage = $oGui->getDataObject()->getErrorMessage($sError, $mColumn, $sLabel);
					$aErrorMessages[] = $sErrorMessage;
				}
			} else {
				switch($mErrorKey){
					case 'CURRENCY_CONVERT_ERROR':
						$sMessage			= $oGui->t('Fehler beim Konvertieren der Währung.');
						$aErrorMessages[]	= $sMessage;
						break;
					case 'OVERPAYMENT_CURRENCY_NOT_MATCH_METHOD_CURRENCY':
						$sMessage			= $oGui->t('Die Währung des Kontos der Bezahlmethode entspricht nicht der zu zahlenden Währung!');
						$aErrorMessages[]	= $sMessage;
						break;
					case 'ACCOUNT_NOT_FOUND_FOR_OVERPAYMENT':
						$sMessage			= $oGui->t('Es konnte kein passendes Konto für die Überbezahlung gefunden werden!');
						$aErrorMessages[]	= $sMessage;
						break;
					case 'OVERPAYMENT_METHOD_TRANSACTION_FAILED':
						$sMessage			= $oGui->t('Die Verrechnung für die Überbezahlung konnte nicht gespeichert werden!');
						$aErrorMessages[]	= $sMessage;
						break;
					case 'NUMBERRANGE_LOCKED':
						$aErrorMessages[] = Ext_Thebing_Document::getNumberLockedError();
						break;
					case 'NUMBERRANGE_REQUIRED':
						$aErrorMessages[] = Ext_Thebing_Document::getNumberrangeNotFoundError();
						break;
					default:
						$aErrorMessages[]	= $mErrorKey;
				}	
			}
		}

		return $aErrorMessages;
	}

	public function __clone()
	{
		$this->_aData['id']		= 0;
		$this->amount_inquiry	= 0;
		$this->amount_school	= 0;
	}

	/**
	 * Überprüfen ob in die Spalte gespeicherter summierter Betrag der Zahlung = Summe einzelner gezahlten Positionen + Überbezahlung
	 * @return bool
	 */
	public function checkAmount() {

		$fAmount = $this->amount_inquiry;
        
		$fItemsAmount = (float)$this->getPayedAmount();
		$fOverpayAmount = (float)$this->getOverpayAmount();
		$fAmountNow = round($fItemsAmount + $fOverpayAmount, 3);

		if(Ext_Thebing_Util::compareFloat($fAmount, $fAmountNow) !== 0) {
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * Liefert die Bezahlmethode des Payments
	 * @return Ext_Thebing_Admin_Payment 
	 */
	public function getMethod(){
		$oPaymentMethod = Ext_Thebing_Admin_Payment::getInstance($this->method_id);
		
		return $oPaymentMethod;
	}

	/**
	 * Liefert den Namen der Bezahlmethode des Payments
	 */
	public function getMethodName(){
		return $this->getMethod()->name;
	}

	/**
	 * @inheritdoc
	 */
	public function validate($bThrowExceptions = false) {
		$mSucccess = parent::validate($bThrowExceptions);

		if($mSucccess === true) {
			if(!self::checkAmountValuesPlausibility($this->amount_inquiry, $this->amount_school)) {
				return [['kip.amount_inquiry' => 'CURRENCY_CONVERT_ERROR']];
			}
		}

		return $mSucccess;
	}

	/**
	 * Prüfen, ob beide Beträge plausibel sind: Wenn ein Betrag da ist, muss auch im anderen Betrag etwas da sein
	 *
	 * @param float $fAmount
	 * @param float $fAmountSchool
	 * @return bool
	 */
	public static function checkAmountValuesPlausibility($fAmount, $fAmountSchool) {

		if(
			(
				$fAmount != 0 &&
				$fAmountSchool == 0
			) || (
				$fAmountSchool != 0 &&
				$fAmount == 0
			)
		) {
			return false;
		}

		return true;

	}
	
	/**
	 * Ersten Payment Item Holen
	 * @return Ext_Thebing_Inquiry_Payment_Item
	 */
	public function getFirstItem()
	{
		$aItems = (array)$this->getItems();
		
		if(!empty($aItems))
		{
			$oFirstItem = reset($aItems);
		}
		else
		{
			$oFirstItem = new Ext_Thebing_Inquiry_Payment_Item();
		}
		
		return $oFirstItem;
	}
	
	public function getIdTag(){
		return self::$_sIdTag;
	}
	
	public function deleteManualCreditnotePayments()
	{	
		if($this->id <= 0) {
			return;
		}

		// Vorher wurde das über die Items gemacht, aber diese payment_item_id war auch einfach mal 0 #6359
		// Es ist allerdings wichtig, dass die Zahlungen der manuellen Creditnotes auch tatsächlich gelöscht werden!
		$aManualCreditnotePayments = $this->getJoinedObjectChilds('manual_creditnote_payments');
		foreach($aManualCreditnotePayments as $oManualCreditNotePayment) {
			/** @var $oManualCreditNotePayment Ext_Thebing_Agency_Manual_Creditnote_Payment */
			$oManualCreditNotePayment->delete();
		}
	}

	/**
	 * @return Ext_TS_Inquiry|null
	 */
	public function getInquiry() {

		if($this->inquiry_id > 0) {
			return Ext_TS_Inquiry::getInstance($this->inquiry_id);
		}

		return null;
	}

	/**
	 * @param Ext_Thebing_Inquiry_Document|null $oDocument
	 * @param Ext_Thebing_Inquiry_Document_Version|null $oVersion
	 * @param Ext_Gui2_Html_Abstract|null $oContainer
	 * @return Ext_Gui2_Html_Image|string
	 */
	protected static function createOverviewPdfIcon($oDocument, $oVersion=null, $oContainer=null) {

		$oImg = new Ext_Gui2_Html_I();
		$oImg->class = 'fa fa-colored ';

		// Dokument muss nicht unbedingt vorhanden sein
		if($oDocument === null) {
			return $oImg;
		}

		// Icons dynamisch aus dem LoadingIndicator-Handler
		if(!self::$aDocumentStatusIcons) {
			$oLoadingIndicatorDocumentHandler = new \Ts\Handler\LoadingIndicator\Document();
			self::$aDocumentStatusIcons = $oLoadingIndicatorDocumentHandler->getIcons();
		}

		$oImg->class = self::$aDocumentStatusIcons[$oDocument->status];

		if($oDocument->status === 'pending') {
			$oImg->__set('data-type', 'loading-indicator');
			$oImg->__set('data-handler', 'ts/document');
			$oImg->__set('data-id', $oDocument->id);
		} elseif(
			$oVersion instanceof Ext_Thebing_Inquiry_Document_Version &&
			$oVersion->path != ''
		) {
			$oImg->style .= 'cursor:pointer;';
			$oImg->onclick = 'window.open(\'/storage/download'.$oVersion->path.'\'); return false;';
		}

		// Bei ready muss eine Version existieren plus Pfad
		if(
			$oContainer && (
				$oDocument->status === 'pending' ||
				$oDocument->status === 'fail' || (
					$oVersion instanceof Ext_Thebing_Inquiry_Document_Version &&
					$oVersion->path != ''
				)
			)
		) {
			$oContainer->setElement($oImg);
		}

		return $oImg;
	}

	/**
	 * Relation zwischen Dokument und Zahlung aktualisieren
	 *
	 * @param int $iDocumentId
	 */
	public function updateDocumentRelation($iDocumentId) {

		if($iDocumentId == 0) {
			// Eventuell rausnehmen, je nachdem, was da für Fehler kommen (aber dann nicht den Query ausführen!)
			throw new RuntimeException('updateDocumentRelation() with document-ID 0');
		}

		$sSql = "
			REPLACE INTO
				`ts_documents_to_inquiries_payments`
			SET
				`document_id` = :document_id,
				`payment_id` = :payment_id
		";

		DB::executePreparedQuery($sSql, array(
			'document_id' => (int)$iDocumentId,
			'payment_id' => (int)$this->id
		));

	}

	/**
	 * @param Ext_Thebing_Inquiry_Payment $oPayment
	 * @param Ext_Thebing_Inquiry_Payment[]|array $aRelationPayments
	 * @param string $sMode
	 * @return string
	 */
	private static function getDeletePaymentMessage(Ext_Thebing_Inquiry_Payment $oPayment, array $aRelationPayments, $sMode) {

		$aPaymentTranslations = Ext_Thebing_Util::getPaymentTranslations();

		if(isset($aPaymentTranslations[$sMode.'_delete'])) {
			$sMessage = $aPaymentTranslations[$sMode.'_delete'];
		} else {
			return '';
		}

		$aFind = array();
		$aReplace = array();
		$oPaymentInquiry = $oPayment->getInquiry();

		if($sMode === 'creditnote_payments') {

			$fTotalAmount = 0;
			$aCustomerNumbers = array();
			$aCreditnoteNumbers = array();

			foreach($aRelationPayments as $oRelationPayment) {

				$fTotalAmount += $oRelationPayment->getAmount();

				/** @var Ext_Thebing_Inquiry_Document[] $aDocuments */
				$aCustomerNumbers[] = $oRelationPayment->getInquiry()->getCustomer()->getCustomerNumber();

				/** @var Ext_Thebing_Inquiry_Payment[] $aCreditnotePayments */
				$aCreditnotePayments = $oRelationPayment->getJoinTableObjects('payment_creditnotes');
				foreach($aCreditnotePayments as $oCreditnotePayment) {
					/** @var Ext_Thebing_Inquiry_Document $aDocuments */
					$aDocuments = $oCreditnotePayment->getJoinTableObjects('documents');
					foreach($aDocuments as $oDocument) {
						$aCreditnoteNumbers[] = $oDocument->document_number;
					}
				}

			}

			$aFind = array(
				'{customer_numbers}',
				'{creditnote_numbers}',
				'{total_amount}'
			);

			$aReplace = array(
				implode(', ', $aCustomerNumbers),
				implode(', ', $aCreditnoteNumbers),
				Ext_Thebing_Format::Number($fTotalAmount, $oPaymentInquiry->getCurrency())
			);

		} else if($sMode === 'payment_creditnotes') {

			$aCreditnoteNumbers = array();
			$fPaymentAmount = 0;
			foreach($aRelationPayments as $oRelationPayment) {
				$fPaymentAmount += $oRelationPayment->getAmount();
				/** @var Ext_Thebing_Inquiry_Document $aDocuments */
				$aDocuments = $oRelationPayment->getJoinTableObjects('documents');
				foreach($aDocuments as $oDocument) {
					$aCreditnoteNumbers[] = $oDocument->document_number;
				}
			}

			$aFind = array(
				'{creditnote_numbers}',
				'{total_amount}'
			);

			$aReplace = array(
				implode(', ', $aCreditnoteNumbers),
				Ext_Thebing_Format::Number($fPaymentAmount * -1, $oPaymentInquiry->getCurrency())
			);

		}

		$sMessage = str_replace($aFind, $aReplace, $sMessage);

		return $sMessage;
	}

	/**
	 * Konfiguration für das JavaScript, wann welche Sender/Empfänger je nach Typ verfügbar sind
	 *
	 * @see getTypeOptions()
	 * @param bool|false $bAgencyPayment
	 * @return array
	 */
	public static function getTypeOptionsConfig($bAgencyPayment=false) {
		$aConfig = [];

		// Vor Anreise
		$aConfig[1] = [
			'senders' => ['customer', 'agency', 'school', 'sponsor'],
			'receivers' => ['school'],
			'show_receivers' => false
		];

		// Vor Ort
		$aConfig[2] = [
			'senders' => ['customer', 'agency', 'school'],
			'receivers' => ['school'],
			'show_receivers' => false
		];

		// Ausbezahlung
		$aConfig[3] = [
			'senders' => ['school', 'agency', 'sponsor'],
			'receivers' => ['customer', 'agency', 'sponsor'],
			'show_receivers' => true
		];

		// Ausbezahlung Gutschrift (Creditnote ausbezahlen)
		$aConfig[4] = [
			'senders' => ['school'],
			'receivers' => ['agency'],
			'show_receivers' => true
		];

		// Zurückgenommene Auszahlung (Creditnote ausbezahlen)
		$aConfig[5] = [
			'senders' => ['agency'],
			'receivers' => ['school'],
			'show_receivers' => true
		];

		// Bei Agenturzahlungen ist standardmäßig die Agentur ausgewählt
		if($bAgencyPayment) {
			$aConfig[1]['senders'] = ['agency', 'customer', 'school'];
			$aConfig[2]['senders'] = ['agency', 'customer', 'school'];
			$aConfig[3]['senders'] = ['agency', 'school'];
		}

		return $aConfig;
	}

	/**
	 * Prüfen, welche Bezahlbelege pro Zahlung generiert werden müssen
	 *
	 * @param Ext_TS_Inquiry $oInquiry
	 * @return array
	 */
	public static function getNeededPaymentReceiptTypes(Ext_TS_Inquiry $oInquiry, $sType=self::RECEIPT_OVERVIEW) {

		$oClient = Ext_Thebing_Client::getFirstClient();

		switch($sType) {
			case self::RECEIPT_OVERVIEW:
				$iSetting = $oClient->inquiry_payments_overview;
				break;
			case self::RECEIPT_PAYMENT:
				$iSetting = $oClient->inquiry_payments_receipt;
				break;
			case self::RECEIPT_INVOICE:
				$iSetting = $oClient->inquiry_payments_invoice;
				break;
			default:
				throw new InvalidArgumentException('Invalid receipt type "'.$sType.'"');
				break;
		}

		// $oClient->inquiry_payments_overview == 2 // 2 = Beide
		$bCustomerReceipt = true;
		$bAgencyReceipt = true;

		if($iSetting == 0) { // 0 = Keine Bezahlbelege
			$bCustomerReceipt = false;
			$bAgencyReceipt = false;
		} elseif($iSetting == 1) { // 1 = Entsprechend der Bezahlmethode
			if(
				// Bei netto kein Kundenbeleg
				$oInquiry->payment_method != 1 &&
				$oInquiry->payment_method != 3
			) {
				$bCustomerReceipt = false;
			} elseif(
				// Bei brutto kein Agenturbeleg
				$oInquiry->payment_method != 0 &&
				$oInquiry->payment_method != 2
			) {
				$bAgencyReceipt = false;
			}
		}

		return [$bCustomerReceipt, $bAgencyReceipt];
	}
	
	public function save($bLog = true) {
		
		$bInsert = $this->isNew();
		
		parent::save($bLog);
		
		$this->initUpdateTransactions();
		
		if($bInsert) {
			System::wd()->executeHook('ts_inquiry_payment_create', $this);
		} else {
			System::wd()->executeHook('ts_inquiry_payment_update', $this);	
		}
		
		return $this;
	}
	
	public function getAccountData() {

		// TODO Kann null sein
		$oInquiry = $this->getInquiry();
		
		// Umkehren
		if($this->type_id == 3) {
			$sSender = $this->receiver;
		} else {
			$sSender = $this->sender;
		}

		switch($sSender) {
			case 'school':
			case 'customer':
				
				// Bei Gruppenbuchungen kann das die Gruppe sein, je nach dem wer Rechnungsempfänger ist
				if (
					$sSender === 'customer' &&
					$oInquiry->hasGroup()
				) {
					return ['type' => 'group', 'id' => $oInquiry->group_id];
				}
				
				$oContact = $oInquiry->getTraveller();
				$sType = 'contact';
				$iId = $oContact->id;
				break;
			case 'agency':
				$sType = 'agency';
				$iId = $oInquiry->agency_id;
				break;
			case 'sponsor':
				$sType = 'sponsor';
				$iId = $oInquiry->sponsor_id;
				break;
		}

		return ['type' => $sType, 'id' => $iId];
	}

	public function getAccountNameData() {

		$aData = [];

		$aAccountData = $this->getAccountData();

		$aData['type'] = $aAccountData['type'];
		$aData['id'] = (int)$aAccountData['id'];

		switch($aData['type']) {

			case 'contact':

				$oInquiry = $this->getInquiry();

				if($oInquiry) {
					$oTraveller	= $oInquiry->getFirstTraveller();
					if($oTraveller) {
						$aData['firstname'] = $oTraveller->firstname;
						$aData['lastname']	= $oTraveller->lastname;
					}
				}

				break;

			case 'agency':

				$oAgency = Ext_Thebing_Agency::getInstance($aData['id']);

				$aData['object_name'] = $oAgency->getName(true);

				$oContactPerson = $oAgency->getMasterContact();

				if($oContactPerson) {
					$aData['firstname']	= $oContactPerson->firstname;
					$aData['lastname']	= $oContactPerson->lastname;
				}

				break;

			case 'sponsor':

				$oSponsor = TsSponsoring\Entity\Sponsor::getInstance($aData['id']);

				$aData['object_name']	= $oSponsor->getName();

				// TODO Hauptkontakt?

				break;

		}

		return $aData;
	}

	public function initUpdateTransactions() {
		\Core\Entity\ParallelProcessing\Stack::getRepository()->writeToStack('ts/update-transactions', ['payment_id'=>$this->id], 10);
	}
	
	public function updateTransaction() {
		
		// Zahlung darf nur einmal vorkommen
		\TsAccounting\Service\Accounts\Transactions::delete('payment', $this->id, !$this->isActive() ? 'DELETED' : 'UPDATE_TRANSACTION');

		if($this->isActive()) {
			
			$oInquiry = $this->getInquiry();

			// TODO null wurde nicht abgefangen; was soll hier passieren?
			if (!$oInquiry) {
				return;
			}

			$oCurrency = $oInquiry->getCurrency(true);

			// 'agency', 'group', 'contact', 'sponsor'
			$aAccountData = $this->getAccountData();

			$fAmount = $this->amount_inquiry * -1;

			\TsAccounting\Service\Accounts\Transactions::add($aAccountData['type'], $aAccountData['id'], 'payment', $this->id, $fAmount, $oCurrency->getIso(), new Carbon\Carbon($this->date));
		
		}
		
	}

	public function releasePayment($iCreatorId = null) {

		if($this->isReleased()) {
			$this->_sError = 'PAYMENT_RELEASED';
			return false;
		}

		$oUnreleasedDocuments = collect($this->getAllDocuments())
			->filter(function(Ext_Thebing_Inquiry_Document $oDocument) {
				return !$oDocument->isProforma() && !$oDocument->isReleased();
			});

		if($oUnreleasedDocuments->isNotEmpty()) {
			$this->_sError = 'DOCUMENT_NOT_RELEASED';
			return false;
		}

		// TODO bei der Zahlungsfreigabe muss das Interface umgestellt werden damit man Warnings anzeigen kann
		$ignoreErrors = ['no_receipt_text_found'];

		$oGenerator = new Ext_TS_Accounting_Bookingstack_Generator_Payment($this, $ignoreErrors);
		$bSuccess = $oGenerator->createStack();

		if($bSuccess){
			$this->insertRelease($iCreatorId);
			return true;
		}

		return false;
	}

	/**
	 * @return Ext_Thebing_Inquiry_Payment_Overpayment[]
	 */
	public function getOverpayments(): array {
		return $this->getJoinedObjectChilds('overpayments', true);
	}

	/**
	 * Zahlungsbeträge neu zuweisen: Zahlung behalten, alles andere löschen
	 *
	 * Achtung:
	 *   - Das sollte immer in einer Transaktion passieren!
	 *   - Die Beträge der Buchung (calculatePayedAmount) werden hier nicht neu berechnet
	 */
	public function reallocateAmounts(array $items)
	{

		// Bei erster Proforma sollte es keine Items geben; bei erster Rechnung sind es die Items der Proforma
		foreach ($this->getItems() as $item) {
			$item->delete(); // Alles neu verrechnen
		}

		foreach ($this->getOverpayments() as $overpayment) {
			$overpayment->delete(); // Wird ggf. neu erzeugt
		}

		// Alle löschen, damit nachher z.B. nicht Proforma und Rechnung verknüpft sind
		$this->documents = [];

		$service = new \Ts\Service\InquiryPaymentBuilder($this->getInquiry(), $items);
		$service->execute($this);

	}

	/**
	 * @return \Illuminate\Support\Collection<Ext_Thebing_Inquiry_Document>
	 */
	public function getReceipts(Ext_TS_Inquiry $inquiry, string $type = null): \Illuminate\Support\Collection
	{
		return collect($this->getJoinTableObjects($this::JOINTABLE_RECEIPTS))
			->filter(fn(Ext_Thebing_Inquiry_Document $d) => $d->entity === Ext_TS_Inquiry::class && $d->entity_id == $inquiry->id)
			->filter(fn(Ext_Thebing_Inquiry_Document $d) => $type === null || $type === $d->type);
	}

	/**
	 * TODO extra nicht über jointable gegangen da dort auch ein TODO steht.
	 * @return array
	 */
	public function getDocuments(): array
	{
		$items = $this->getItems();
		$documents = [];

		foreach ($items as $item) {
			$document = $item->getVersionItem()?->getVersion()?->getDocument();
			if ($document && $document->exist()) {
				$documents[$document->id] = $document;
			}
		}

		// Überbezahlungen
		$overpayments = $this->getOverpayments();
		foreach($overpayments as $overpayment) {
			$document = $overpayment->getInquiryDocument();
			if ($document && $document->exist()) {
				$documents[$document->id] = $document;
			}
		}
		
		return array_values($documents);
	}

}
