<?php

class Ext_TS_Enquiry_Convert2 {

	/**
	 * @var Ext_TS_Inquiry
	 */
	private $inquiry;

	/**
	 * @var Ext_Thebing_Client_Inbox
	 */
	private $inbox;

	/**
	 * @var Ext_TS_Inquiry_Journey|null
	 */
	private $journey;

	/**
	 * @var Ext_TS_NumberRange
	 */
	private $numberrange;

	/**
	 * @var Ext_Thebing_Pdf_Template
	 */
	private $template;

	/**
	 * @var bool
	 */
	private $createProforma;

	/**
	 * @var array
	 */
	private $attachedAdditional = [];

	/**
	 * @var string[]
	 */
	private $groupFields = [
		'agency_id' => 'ts_i.agency_id',
		'agency_contact_id' => 'ts_i.agency_contact_id',
		'payment_methode_group' => 'ts_i.payment_method',
		'payment_method_comment_group' => 'ts_i.payment_method_comment',
		'currency_id' => 'ts_i.currency_id',
		'sales_person_id' => 'ts_i.sales_person_id',
		'nationality_id' => 'tc_c.nationality',
		'correspondence_id' => 'tc_c.corresponding_language',
		'language_id' => 'tc_c.language',
		'address' => 'tc_a_c.address', // Als hätte die Gruppe keinen Kontakt
		'address_addon' => 'tc_a_c.address_addon',
		'plz' => 'tc_a_c.zip',
		'city' => 'tc_a_c.city',
		'state' => 'tc_a_c.state',
		'country' => 'tc_a_c.country_iso',
	];

	public function __construct(Ext_TS_Inquiry $inquiry) {
		$this->inquiry = $inquiry;
	}

	public function setInbox(Ext_Thebing_Client_Inbox $inbox) {
		$this->inbox = $inbox;
	}

	public function setJourney(Ext_TS_Inquiry_Journey $journey, Ext_TS_NumberRange $numberrange, Ext_Thebing_Pdf_Template $template, bool $createProforma = false) {

		$this->journey = $journey;
		$this->numberrange = $numberrange;
		$this->template = $template;
		$this->createProforma = $createProforma;
	}

	public function setAttachedAdditional(array $attachedAdditional) {
		$this->attachedAdditional = $attachedAdditional;
	}

	public function convert() {

		// Bei Gruppen ist die schöne, neue Welt dann auch schon wieder vorbei
		if ($this->inquiry->hasGroup()) {
			$this->convertGroup();
			return;
		}

		$this->inquiry->type |= Ext_TS_Inquiry::TYPE_BOOKING;
		$this->inquiry->inbox = $this->inbox->short;
		$this->inquiry->converted = time(); // Früher war das ts_i.created, aber das kann so nicht mehr funktionieren

		if ($this->journey) {
			$this->journey->type |= Ext_TS_Inquiry_Journey::TYPE_BOOKING;
			$document = $this->createDocument($this->inquiry, $this->numberrange->generateNumber());
			$this->createPdf($document);
		}

		$this->inquiry->setInquiryStatus($document->type ?? '', false, $document?->isDraft() ?? false);

		$this->inquiry->getAmount(false, true, null, false);
		$this->inquiry->getAmount(true, true, null, false);

		$this->inquiry->save();

		$this->finishInquiry($this->inquiry);

	}

	private function finishInquiry(Ext_TS_Inquiry $inquiry) {

		$tuitionIndex = new Ext_TS_Inquiry_TuitionIndex($inquiry);
		$tuitionIndex->update();

		$customerNumber = new Ext_Thebing_Customer_CustomerNumber($inquiry);
		$customerNumber->saveCustomerNumber();

	}

	private function convertGroup() {

		$school = $this->inquiry->getSchool();
		$group = $this->inquiry->getGroup();
		$inquiries = $documents = [];
		$docNumber = null;

		if ($this->journey) {
			$docNumber = $this->numberrange->generateNumber();
		}

		if (!$group instanceof Ext_TS_Enquiry_Group) {
			throw new \LogicException('Invalid group type: '.get_class($group));
		}

		// Werte einmalig in die Gruppe schreiben, die der alte Dialog benötigt
		$group->school_id = $school->id;
		$group->inbox_id = $this->inbox->id;
		$group->newsletter = $this->inquiry->getTraveller()->detail_newsletter;
		$group->contact_id = $this->inquiry->getCustomer()->id;
		$group->course_data = 'no';
		$group->accommodation_data = 'no';
		$group->transfer_data = 'no';

		// Felder kopieren
		foreach ($this->groupFields as $key => $column) {
			[$alias, $column] = explode('.', $column);
			$group->{$key} = $this->inquiry->getObjectByAlias($alias)->{$column};
		}

		// Traveller in eigene Buchungen kopieren
		$members = $group->getMembers();
		foreach ($members as $member) {

			$inquiry = $this->cloneInquiry($member);
			$inquiry->type = Ext_TS_Inquiry::TYPE_BOOKING; // Gruppenbuchungen dürfen auf keinen Fall TYPE_ENQUIRY gesetzt haben
			$inquiry->inbox = $this->inbox->short;
			$inquiry->converted = time(); // Bei dem blöden Kopieren für Gruppen auf beiden Seiten setzen
			$inquiry->save(); // Hier muss leider gespeichert werden, da die WDBasic zu blöd ist, Relations morphen zu können (Doc-FK)
			$group->setJoinedObjectChild('inquiries', $inquiry);
			$inquiries[] = $inquiry;

			if ($this->journey) {
				$document = $this->createDocument($inquiry, $docNumber);
				$documents[] = $document;
			}

			$inquiry->setInquiryStatus($document->type ?? '', false);

			// save wird durch Gruppe aufgerufen
			$inquiry->getAmount(false, true, null, false);
			$inquiry->getAmount(true, true, null, false);

		}

		// Bei Gruppen hat zwar jede Inquiry ein Dokument, aber das PDF wird nur einmal erzeugt. Dafür MÜSSEN alle Items vorher gespeichert sein…
		if (!empty($documents)) {
			// PDF wird aus erstbester Buchung erzeugt. Läuft in Ext_Thebing_document auch so.
			$mainVersion = $this->createPdf(reset($documents));

			foreach ($documents as $document) {
				$version = $document->getLastVersion();
				$version->path = $mainVersion->path;
				$version->save();
			}
		}

		$group->save();

		$this->inquiry->inquiries_childs = array_column($inquiries, 'id');
		$this->inquiry->save();

		foreach ($inquiries as $inquiry) {
			$this->finishInquiry($inquiry);
		}

	}

	/**
	 * Rechnungsdialog + Umwandlung: Klont eine Inquiry inkl. aller Leistungen, ohne diese zu speichern
	 *
	 * @param Ext_TS_Contact $member
	 * @param bool $keepServiceIds
	 * @return Ext_TS_Inquiry
	 */
	public function cloneInquiry(Ext_TS_Contact $member, $keepServiceIds = false) {

		$inquiryData = $this->inquiry->getData();
		$inquiryData['id'] = 0; // Wichtig, damit die WDBasic keinen Cache bei den Childs usw. verwendet

		$contact = Ext_TS_Inquiry_Contact_Traveller::getObjectFromArray($member->getData()); // Cast
		$contact->detail_newsletter = $member->detail_newsletter;
		$inquiry = new Ext_TS_Inquiry();
		$inquiry->setData($inquiryData);
		$inquiry->getCustomer($contact);
		$inquiry->addJoinTableObject('travellers', $contact); // getCustomer() setzt zwar intern einen State, aber keine Relation
		$inquiry->number = '';
		$inquiry->numberrange_id = 0;

		$journey = $inquiry->getJourney();
		$journey->school_id = $this->inquiry->getJourney()->school_id;
		$journey->productline_id = $this->inquiry->getJourney()->productline_id;
		$journey->transfer_mode = $this->inquiry->getJourney()->transfer_mode;
		$journey->transfer_comment = $this->inquiry->getJourney()->transfer_comment;

		// Komische Tabelle mit zwei FKs (journey_id+contact_id) befüllen, da hier die Gruppen-Flags drinstehen
		foreach (Ext_TS_Group_Contact::FLAGS as $flag) {
			if ($contact->getDetail($flag)) {
				/** @var Ext_TS_Inquiry_Journey_Traveller $detail */
				$detail = $journey->getJoinedObjectChild('traveller_detail');
				$detail->traveller_id = $contact->id;
				$detail->type = $flag;
				$detail->value = 1;
			}
		}

		$this->cloneJourneyServices($journey, $contact, 'courses', $keepServiceIds);
		$this->cloneJourneyServices($journey, $contact, 'accommodations', $keepServiceIds);
		$this->cloneJourneyServices($journey, $contact, 'transfers', $keepServiceIds);
		$this->cloneJourneyServices($journey, $contact, 'insurances', $keepServiceIds);

		return $inquiry;

	}

	private function cloneJourneyServices(Ext_TS_Inquiry_Journey $journey, Ext_TS_Inquiry_Contact_Traveller $contact, string $key, $keepServiceIds = false) {

		/** @var Ext_TS_Inquiry_Journey_Service[] $services */
		$services = $this->inquiry->getJourney()->getJoinedObjectChilds($key);

		foreach ($services as $service) {

			// Nur die Leistungen übernehmen, die dem Schüler auch zugewiesen wurden
			if (!in_array($contact->id, $service->travellers)) {
				continue;
			}

			$data = $service->getData();

			// Der Rechnungsdialog braucht bei den Positionen zwingend type_id, da sonst z.B. dieses Commission-Reload-Geraffel nicht funktioniert
			if (!$keepServiceIds) {
				$data['id'] = 0;
			}

			/** @var Ext_TS_Inquiry_Journey_Service $newService */
			$newService = $journey->getJoinedObjectChild($key);
			$newService->setData($data);
			$newService->transients['origin_service'] = $service;

		}

	}

	private function createDocument(Ext_TS_Inquiry $inquiry, string $number): Ext_Thebing_Inquiry_Document {

		$offer = $this->journey->getDocument();
		$type = str_replace('offer_', '', $offer->type);
		$type = $this->createProforma ? 'proforma_'.$type : $type;
		$comment = L10N::t('Angebot in Rechnung umwandeln', Ext_Thebing_Document::$sL10NDescription);

		// createCopy speichert natürlich
		$additional = ['numberrange_id' => $this->numberrange->id, 'document_number' => $number, 'parent_documents_offer' => [$offer->id]];
		$document = $offer->createCopy2($type, $comment, $inquiry, $this->template->id, $additional);
		if (!$document instanceof Ext_Thebing_Inquiry_Document) {
			throw new LogicException('createCopy returned no document');
		}

		// FKs der Items (Journey-Services) umschreiben
		if ($this->inquiry->hasGroup()) {
			$document->getLastVersion()->adaptItems();
		}

		if (!empty($this->attachedAdditional)) {
			$thebingDocument = new Ext_Thebing_Document();
			$thebingDocument->prepareAttachedAdditionalDocumentsGenerating($document, ['attached_additional_document' => $this->attachedAdditional]);
		}

		return $document;

	}

	private function createPdf(Ext_Thebing_Inquiry_Document $document): Ext_Thebing_Inquiry_Document_Version {

		$version = $document->getLastVersion();
		$inquiry = $document->getInquiry();
		$language = $inquiry->getCustomer()->getLanguage();

		$placeholderObj = $inquiry->getOldPlaceholderObject();
		$placeholderObj->setAdditionalData('document_address', $version->addresses);
		$placeholderObj->sTemplateLanguage = $language;

		$version->setDefaultTemplateTexts($placeholderObj, $inquiry->getSchool());

		$pdfPath = $document->createPdf();

		if (empty($pdfPath)) {
			throw new LogicException('PDF path is empty');
		}

		$version->path = \Ext_Thebing_Inquiry_Document_Version::prepareAbsolutePath($pdfPath);

		return $version;

	}

}