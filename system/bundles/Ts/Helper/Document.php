<?php

namespace Ts\Helper;

/**
 * @TODO Funktionen zum Generieren einer Rechnung von BuildInquiryHelper hierhin migrieren
 * @see \TsRegistrationForm\Helper\BuildInquiryHelper
 */
final class Document {

	private \WDBasic $entity;

	private \Ext_Thebing_School $school;

	private \Ext_Thebing_Pdf_Template $template;

	private \Ext_Thebing_Inquiry_Document $document;

	private \Ext_Thebing_Inquiry_Document_Version $version;

	private \Ext_Thebing_Inquiry_Document_Version_Price $priceIndex;
	
	private array $items;

	private string $language;

	public function __construct(\WDBasic $entity, \Ext_Thebing_School $school, \Ext_Thebing_Pdf_Template $template, string $language) {

		$this->template = $template;
		$this->entity = $entity;
		$this->school = $school;
		$this->language = $language;

	}

	public function create(string $type = 'additional_document', int $companyId=null) {

		$this->document = new \Ext_Thebing_Inquiry_Document();
		$this->document->entity = get_class($this->entity);
		$this->document->entity_id = $this->entity->id;
		$this->document->type = $type;

		$this->version = $this->document->newVersion();
		$this->version->sLanguage = $this->language;
		$this->version->template_language = $this->language;
		$this->version->template_id = $this->template->id;

		if ($this->entity instanceof \Ext_TS_Inquiry) {
			$this->version->addresses = [['type' => 'address', 'type_id' => 0]];
		}

		if($companyId) {
			$this->version->company_id = $companyId;
			\Ext_TS_NumberRange::setCompany($companyId);
		}

		// TODO Welchen Sinn hat das? \Ext_Thebing_Inquiry_Document::generateNumber() überschreibt diesen Wert dann sowieso
		// TODO $type = \Ext_TS_Inquiry_Abstract::getTypeForNumberrange
		$numberrange = \Ext_Thebing_Inquiry_Document_Numberrange::getObject($type, false, $this->school->id);
		if($numberrange) {
			$this->document->numberrange_id = $numberrange->id;
		}
		
	}

	/**
	 * 
	 * @param string $view net oder gross
	 */
	public function setAddress(string $view='gross') {
		
		if ($this->entity instanceof \Ext_TS_Inquiry) {
			$documentAddress = new \Ext_Thebing_Document_Address($this->entity);
			$addressArray = explode('_', $documentAddress->getSelectedAdressSelect($this->version, $view));

			$this->version->addresses = [['type'=>$addressArray[0], 'type_id'=>$addressArray[1]]];
		}
		
	}
	
	public function setParentDocument(\Ext_Thebing_Inquiry_Document $document, string $parentKey) {

		$version = $document->getLastVersion();

		$this->document->gui2 = $document->gui2;
		$this->version->addresses = $version->addresses;

		// Items übernehmen für Zusatzdokumente mit Positionstabelle (z.B. Pretend Invoice)
		if ($this->template->canShowInquiryPositions()) {
			$items = $version->getItemObjects();
			foreach ($items as $item) {
				$newItem = $this->version->newItem();
				$newItem->setOtherItemData($item);
			}
		}

		$this->setUser(\User::getInstance($document->creator_id));

		$this->document->{$parentKey} = [...$this->document->{$parentKey}, $document->id];

	}

	public function setUser(\User $user) {

		if (!$user->exist()) {
			return;
		}

		$this->document->creator_id = $user->id;
		$this->document->editor_id = $user->id;

		$this->version->creator_id = $user->id;
		$this->version->user_id = $user->id;
		$this->version->signature_user_id = $user->id;

	}

	private function createPlaceholderObject(): \Ext_Thebing_Placeholder {

		$params = array(
			'school_format' => $this->school->id,
			'template_type' => $this->template->type,
			'options' => [],
		);

		if ($this->entity instanceof \Ext_TS_Inquiry) {
			$params['inquiry'] = $this->entity;
			$params['contact'] = $this->entity->getCustomer();
		}

		$placeholderObj = $this->entity->createPlaceholderObject($params);
		$placeholderObj->setAdditionalData('document_address', $this->version->addresses);
		$placeholderObj->sTemplateLanguage = $this->language;

		return $placeholderObj;

	}

	/**
	 * @TODO Das Generieren von Nummern sollte auf keinen Fall automatisch hier passieren!
	 */
	public function save($generateNumber = false) {

		if (empty($this->document->entity_id)) {
			throw new \RuntimeException('entity_id is empty');
		}

		$this->document->validate(true);
		$this->document->save(!$generateNumber);

		// Muss nach den Setter-Methoden passieren
		$this->version->setDefaultTemplateTexts($this->createPlaceholderObject(), $this->school);

		$pdfPath = $this->document->createPdf();

		if (
			empty($pdfPath) ||
			mb_strpos($pdfPath, \Util::getDocumentRoot() . 'storage') !== 0
		) {
			throw new \RuntimeException('Failed to create PDF!');
		}

		$this->version->path = \Ext_Thebing_Inquiry_Document_Version::prepareAbsolutePath($pdfPath);
		$this->version->validate(true);
		$this->version->save();

		// Ist da wenn es Items gibt
		if(isset($this->priceIndex)) {
			$this->priceIndex->savePrice($this->version->id);
		}
		
		// Nur bei Rechnungen und Proforma ausführen
		if (
			$this->entity instanceof \Ext_TS_Inquiry &&
			$this->document->checkDocumentType('invoice_without_storno') === true
		) {
			if (!$this->document->isDraft()) {
				$this->entity->setInquiryStatus($this->document->type, false);
			}
			$this->entity->getAmount(false, true, null, false);
			$this->entity->getAmount(true, true, null, false);
			$this->entity->save();
		}
		
	}

	public function setItems(array $items) {
		
		$this->priceIndex = new \Ext_Thebing_Inquiry_Document_Version_Price();
		
		$this->version->tax = $this->school->tax;
		
		$this->items = $items;
		
		$position = 1;
		foreach ($this->items as $item) {

			$itemObj = $this->version->newItem();
			$itemObj->index_from = $item['from'] ?? $item['index_from'];
			$itemObj->index_until = $item['until'] ?? $item['index_until'];
			$itemObj->parent_id = $item['parent_id'];
			$itemObj->parent_type = $item['parent_type'] ?? null;
			$itemObj->parent_booking_id = $item['parent_booking_id'];
			$itemObj->type = $item['type'];
			$itemObj->description = $item['description'];
			$itemObj->old_description = $item['old_description'];
			$itemObj->amount = $item['amount'];
			$itemObj->amount_net = $item['amount_net'];
			$itemObj->amount_provision = $item['amount_provision'];
			$itemObj->calculate = $item['calculate'];
			$itemObj->onPdf = $item['onPdf'];
			$itemObj->type_id = $item['type_id'];
			$itemObj->amount_discount = $item['amount_discount'];
			$itemObj->tax_category = $item['tax_category'];
			$itemObj->additional_info = $item['additional_info'];
			$itemObj->type_object_id = $item['type_object_id'];
			$itemObj->type_parent_object_id = $item['type_parent_object_id'];
			$itemObj->position = $position;
			$itemObj->contact_id = $this->entity->getCustomer()->id;

			// Analog zu Ext_Thebing_Document den Steuersatz ergänzen
			if ($item['tax_category'] > 0) {
				$itemObj->tax = \Ext_TS_Vat::getTaxRate($item['tax_category'], $this->school->id);
			}

			$this->priceIndex->addItem($itemObj);
			$position++;

		}
			
	}
	
	public function setPaymentConditions(\Ext_TS_Payment_Condition $paymentCondition=null) {

		if(empty($this->items)) {
			return;
		}
		
		$paymentConditionService = new \Ext_TS_Document_PaymentCondition($this->entity, true);
		$paymentConditionService->setDocumentDate();

		if($paymentCondition) {
			$paymentConditionService->setPaymentCondition($paymentCondition);
		}

		foreach ($paymentConditionService->generateRows($this->items) as $row) {
			$paymentTerm = $this->version->getJoinedObjectChild('paymentterms');
			/** @var \Ext_TS_Document_Version_PaymentTerm $paymentTerm */
			$paymentTerm->setting_id = $row->iSettingId;
			$paymentTerm->type = $row->sType;
			$paymentTerm->date = $row->dDate->format('Y-m-d');
			$paymentTerm->amount = $row->fAmount;
		}

		// Wenn es keine Zahlungsbedingung gibt, gibt es trotzdem immer eine Restzahlung
		if (($paymentCondition = $paymentConditionService->getPaymentCondition()) !== null) {
			$this->version->payment_condition_id = $paymentCondition->id;
		}

	}

	public function getDocument(): \Ext_Thebing_Inquiry_Document {
		return $this->document;
	}

	public function getVersion(): \Ext_Thebing_Inquiry_Document_Version {
		return $this->version;
	}

}
