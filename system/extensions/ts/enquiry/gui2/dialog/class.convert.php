<?php

/**
 * Speichern:
 * @see \Ext_TS_Enquiry_Gui2::saveDialogData() Obere GUI
 * @see \Ext_TS_Enquiry_Combination_Gui2_Data::saveDialogData() Untere GUI/Angebote
 */
class Ext_TS_Enquiry_Gui2_Dialog_Convert implements \Gui2\Dialog\FactoryInterface {

	/**
	 * @var Ext_TS_Inquiry[]
	 */
	private $inquiries;

	/** @var Ext_TS_Inquiry_Journey */
	private $journey;

	public function __construct(array $inquiries, Ext_TS_Inquiry_Journey $journey = null) {
		$this->inquiries = $inquiries;
		$this->journey = $journey;
	}

	public function create(\Ext_Gui2 $gui): \Ext_Gui2_Dialog {

		// Alle Anfragen Müssen von einer Schule stammen. Da die Nummernkreise/Templates Schulgebunden sind
		$school = null;
		/** @var Ext_Thebing_School $school */
		$inquiryIds = array();
		$inboxId = null;

		foreach ($this->inquiries as $inquiry) {
			if (
				!is_null($school) &&
				$school->id != $inquiry->getSchool()->id
			) {
				$sError = $gui->t('Anfragen müssen von derselben Schule stammen.');
				return $gui->getDataObject()->getErrorDialog($sError);
			} else {
				if ($inboxId === null) {
					$inboxId = $inquiry->getInbox()->id;
				} elseif ($inboxId != $inquiry->getInbox()->id) {
					// Werden Anfrage aus verschiedenen Inboxen gewählt, wird keine Inbox vorausgewählt
					$inboxId = null;
				}
				$school = $inquiry->getSchool();
				$inquiryIds[] = $inquiry->id;
			}
		}

		$inboxes = Ext_Thebing_Client::getFirstClient()->getInboxList('use_id', true);
		$documentOptions = $school->getEnquiryDocumentOptions();

		$dialog = $gui->createDialog($gui->t('Wollen Sie die Anfrage in eine feste Buchung umwandeln?'));
		$dialog->width = 768;
		$dialog->height = 555;
		$dialog->sDialogIDTag = 'CONVERT_';

		// Trotz default_inbox_id ist inbox_id immer 0, weil der Dialog dann das Select überhaupt nicht initialisiert
//		$inboxRowStyle = '';
//		// Je nachdem wieviele Inboxen vorhanden sind muss hier eine gewählt werden
//		if (count($inboxes) > 1) {
//			// Select muss eingeblendet sein
//		} elseif (count($inboxes) == 1) {
//			// Es gibt nur eine Inbox: Select muss nicht sichtbar sein
//			$inboxRowStyle = 'display: none;';
//		} else {
//			// Keine Inbox vorhanden Fehler Dialog erstellen
//			return $gui->getDataObject()->getErrorDialog( $gui->t('Bitte legen Sie zunächst eine Inbox an.'));
//		}

		$tab = $dialog->createTab($gui->t('Umwandlung'));
		if (
			!$this->journey instanceof Ext_TS_Inquiry_Journey ||
			!Ext_Thebing_Access::hasRight('thebing_invoice_dialog_document_tab')
		) {
			// Wenn Recht nicht da: Alles ohne Tab anzeigen
			$tab = $dialog;
		}

		$tab->setElement($dialog->createNotification($gui->t('Achtung'), $gui->t('Nach dem Umwandeln kann die Anfrage nur noch eingeschränkt bearbeitet werden. Eine Bearbeitung der Kombinatinen oder eine Umwandlung sind nicht mehr möglich.'), 'info'));

		$tab->setElement($dialog->createRow($gui->t('Buchungseingang'), 'select', array(
			'name' => 'save[inbox_id]',
			'db_column' => 'inbox_id',
			'select_options' => $inboxes,
//			'row_style' => $inboxRowStyle,
			'default_value' => $inboxId,
			'required' => true
		)));

		if ($this->journey !== null) {

			// In der oberen Liste gibt es keine Angebote daher sind die Felder nicht erforderlich

			$documentRowStyle = '';
			if (count($documentOptions) > 1) {
				// Select muss eingeblendet sein
			} elseif (count($documentOptions) == 1) {
				// Es gibt nur eine Inbox -> Select muss nicht sichtbar sein
				$documentRowStyle = 'display: none;';
			} else {
				// Keine Inbox vorhanden Fehler Dialog erstellen
				$sError = $gui->t('Sie haben keine Rechte zum anlegen entsprechender Dokumente.');
				return $gui->getDataObject()->getErrorDialog($sError);
			}

			$numberrangeRowStyle = '';
			if (!Ext_Thebing_Access::hasRight('thebing_invoice_numberranges')) {
				$numberrangeRowStyle = 'display: none;';
			}

			$tab->setElement($dialog->createRow($gui->t('Dokument'), 'select', array(
				'name' => 'save[document_type]',
				'db_column' => 'document_type',
				'select_options' => $documentOptions,
				'row_style' => $documentRowStyle,
				'required' => true
			)));

			$tab->setElement($dialog->createRow($gui->t('Template'), 'select', array(
				'name' => 'save[template_id]',
				'db_column' => 'template_id',
				'select_options' => array(), // Werden durch JS nachgeladen
				'required' => true
			)));

			$tab->setElement($dialog->createRow($gui->t('Nummernkreis'), 'select', array(
				'name' => 'save[numberrange_id]',
				'db_column' => 'numberrange_id',
				'select_options' => array(), // Werden durch JS nachgeladen
				'row_style' => $numberrangeRowStyle,
				'required' => true
			)));
		}

		if ($tab instanceof Ext_Gui2_Dialog_Tab) {
			$dialog->setElement($tab);
		}

		// Tab: Automatische Generierung von Zusatzdokumenten
		if (
			$this->journey !== null &&
			$this->journey->getDocument() !== null &&
			!$this->journey->getInquiry()->hasGroup() &&
			Ext_Thebing_Access::hasRight('thebing_invoice_dialog_document_tab')
		) {
			$inquiry = $this->journey->getInquiry();
			$document = $this->journey->getDocument();
			$thebingDocument = new Ext_Thebing_Document();
			$thebingDocument->oGui = $gui;
			$tab = $dialog->createTab($gui->t('Dokumente'));
			$tab->setElement($thebingDocument->getAttachedAdditionalDocumentTabHtml($inquiry, $document));
			$dialog->setElement($tab);
		}

		return $dialog;

	}

	/**
	 * Irgendein sehr spezielles Array erzeugen für convert_enquiry_to_inquiry/convert_offer_to_inquiry
	 *
	 * @param Ext_TS_Inquiry_Journey $journey
	 * @return array
	 * @throws Exception
	 */
	public static function getConvertDialogTemplateOptions(Ext_TS_Inquiry_Journey $journey): array {

		$options = [];

		$school = $journey->getSchool();
		$inquiry = $journey->getInquiry();
		$contact = $inquiry->getCustomer();
		$document = $journey->getDocument();

		$type = $document->isNetto() ? 'document_invoice_agency' : 'document_invoice_customer';

		$templates = Ext_Thebing_Pdf_Template_Search::s($type, $contact->getLanguage(), $school->id);
		foreach ($templates as $template) {
			foreach ($template->getJoinTableObjects('inboxes') as $inbox) {
				$options['invoice'][$inbox->id][$template->id] = $template->name;
				$options['proforma'][$inbox->id][$template->id] = $template->name;
			}
		}

		return $options;

	}

}