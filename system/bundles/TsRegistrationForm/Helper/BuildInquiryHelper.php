<?php

namespace TsRegistrationForm\Helper;

use Carbon\CarbonPeriod;
use Core\DTO\DateRange;
use Illuminate\Support\Collection;
use TsRegistrationForm\Interfaces\RegistrationCombination;

/**
 * Methoden, die dabei helfen, eine Inquiry und ein Document zu erzeugen.
 *
 * Diese Klasse wird von V2 und V3 verwendet.
 */
class BuildInquiryHelper {

	/**
	 * @var RegistrationCombination
	 */
	private $combination;

	public function __construct(RegistrationCombination $combination) {
		$this->combination = $combination;
	}

	public function createInquiryObject($booking = true): \Ext_TS_Inquiry {

		$form = $this->combination->getForm();
		$school = $this->combination->getSchool();
		$currency = \Ext_Thebing_Currency::getInstance($school->getCurrency());

		$inquiry = new \Ext_TS_Inquiry();
		$inquiry->type = $booking ? \Ext_TS_Inquiry::TYPE_BOOKING : \Ext_TS_Inquiry::TYPE_ENQUIRY;
		$inquiry->created = time(); // Nötig für Preisberechnung (Early Bird)
		$inquiry->payment_method = 1;
		$inquiry->currency_id = $currency->id;
		$inquiry->inbox = $form->inbox;

//		$inquiry->status_id = (int)$form->getSchoolSetting($school, 'student_status_id');
//		$inquiry->status = 'pending';

		if (
			\System::d('booking_auto_confirm') == \Ext_Thebing_Client::BOOKING_AUTO_CONFIRM_ALL ||
			(bool)$this->combination->getForm()->booking_auto_confirm
		) {
			$inquiry->confirmed = time();
		}

		if ($this->combination->getFrontendLog() !== null) {
			$inquiry->frontend_log_id = $this->combination->getFrontendLog()->id;
		}

		$this->prepareInquiry($inquiry);

//		// Zusatzgebühren hängen nur an der Rechnung und sind keine Services, was hier ziemlich unbrauchbar ist
//		$inquiry->transients['fees'] = [];
//
//		// Wird in Ext_TS_Inquiry::getObjectByAlias() verwendet
//		$inquiry->transients['flex'] = new \ArrayObject([], \ArrayObject::ARRAY_AS_PROPS);
//		$inquiry->transients['uploads'] = new \ArrayObject([], \ArrayObject::ARRAY_AS_PROPS);

		$contact = $inquiry->getCustomer();
		$contact->corresponding_language = $this->combination->getLanguage()->getLanguage();
//		$contact->bCheckGender = false; // Macht natürlich mehr als nur diese Prüfung abzuschalten

		$journey = $inquiry->getJourney();
		$journey->school_id = $school->id;
		$journey->productline_id = $school->getProductLineId();
		$journey->type = $booking ? \Ext_TS_Inquiry_Journey::TYPE_BOOKING : \Ext_TS_Inquiry_Journey::TYPE_REQUEST;

		return $inquiry;

	}

	public function createDocument(\Ext_TS_Inquiry $inquiry, \Ext_Thebing_Inquiry_Document_Numberrange $numberrange, array $data) {

		$type = $data['document_type'];
		$document = $inquiry->getLastDocument($type);
		if ($document !== null) {
			if (
				$this->combination->getForm()->isCreatingBooking() ||
				$type !== 'brutto'
			) {
				throw new \RuntimeException('Document of type '.$type.' does already exist!');
			}
			$type .= '_diff';
		}

		/** @var \Ext_Thebing_Inquiry_Document $document */
		/** @var \Ext_Thebing_Inquiry_Document_Version $version */
		[$document, $version] = $this->createDocumentAndVersionObject($inquiry, $type);

		try {
			// Platzhalter können fehlschlagen
			$this->prepareDocumentVersion($version, $data);
			$canCreatePdf = true;
		} catch (\Throwable $e) {
			$canCreatePdf = false;
		}

		// Zahlungsbedingungen (Anzahlung, Restzahlung)
		$paymentConditionService = $this->createPaymentCondition($inquiry, $data['document_date']);

		foreach ($paymentConditionService->generateRows($data['document_items']) as $row) {
			$paymentTerm = $version->getJoinedObjectChild('paymentterms');
			/** @var \Ext_TS_Document_Version_PaymentTerm $paymentTerm */
			$paymentTerm->setting_id = $row->iSettingId;
			$paymentTerm->type = $row->sType;
			$paymentTerm->date = $row->dDate->format('Y-m-d');
			$paymentTerm->amount = $row->fAmount;
		}

		// Wenn es keine Zahlungsbedingung gibt, gibt es trotzdem immer eine Restzahlung
		if (($paymentCondition = $paymentConditionService->getPaymentCondition()) !== null) {
			$version->payment_condition_id = $paymentCondition->id;
		}

		$priceIndex = new \Ext_Thebing_Inquiry_Document_Version_Price();
		$this->createDocumentVersionItems($version, $priceIndex, $data);

		$document->document_number = $numberrange->generateNumber();
		$document->numberrange_id = $numberrange->id;
		// Nicht als Entwurf anlegen.
		$document->overrideCreationAsDraft = true;
		$document->validate(true);
		$saved = $document->save();

		if (!$saved instanceof \Ext_Thebing_Inquiry_Document) {
			throw new \RuntimeException('$document->save() did not return an object! ' . print_r($saved, true));
		}

		// Wird benötigt für Amount-Platzhalter
		$priceIndex->savePrice($version->id);

		$pdfPath = false;
		if ($canCreatePdf) {
			// Dokument auch ohne PDF speichern
			try {
				$pdfPath = $document->createPdf();
			} catch (\Throwable $e) {
				$pdfPath = false;
			}
		}

		/*if (
			$pdfPath === false ||
			empty($pdfPath) ||
			mb_strpos($pdfPath, \Util::getDocumentRoot() . 'storage') !== 0
		) {
			throw new \RuntimeException('Failed to create PDF!');
		}*/

		if ($pdfPath !== false) {
			if (empty($pdfPath) || mb_strpos($pdfPath, \Util::getDocumentRoot() . 'storage') !== 0) {
				throw new \RuntimeException(sprintf('Failed to store PDF path [%s]!', $pdfPath));
			}
			$version->path = \Ext_Thebing_Inquiry_Document_Version::prepareAbsolutePath($pdfPath);
		} else {
			$version->addFlag('status', \Ext_Thebing_Inquiry_Document_Version::STATUS_PDF_CREATION_FAILED);
		}

		$version->validate(true);
		$version->save();

		// Da bereits angenommen, macht finalize nur die Registrierung
		$document->finalize();

		// Specials brauchen wie immer Sonderbehandlung: Müssen verknüpft werden und dann nochmal die Beträge aktualisiert werden
//		$version->updateItemIds([]);
//		$version->updateSpecialIndexFields();

		$inquiry->setInquiryStatus($document->type, false);
		$inquiry->getAmount(false, true, null, false);
		$inquiry->getAmount(true, true, null, false);

		return $document;
	}

	public function createDocumentAndVersionObject(\Ext_TS_Inquiry $inquiry, string $docType) {

		$school = $this->combination->getSchool();
		$language = $this->combination->getLanguage();

		$document = $inquiry->newDocument($docType, false);
		if (strpos($docType, 'offer') !== false) {
			$document->entity = \Ext_TS_Inquiry_Journey::class;
			$document->entity_id = $inquiry->getJourney()->id;
		}

		if(!$inquiry->exist()) {
			$document->setEntity($inquiry);
		}
		
		$document->bLockNumberrange = false;
		$version = $document->getJoinedObjectChild('versions');
		/** @var \Ext_Thebing_Inquiry_Document_Version $version */
		$version->setInquiry($inquiry);
		$version->tax = $school->tax;
		$version->template_language = $language->getLanguage();
		$version->sLanguage = $language->getLanguage();

		return [$document, $version];

	}

	private function prepareDocumentVersion(\Ext_Thebing_Inquiry_Document_Version $version, array $data) {

		$inquiry = $version->getInquiry();
		$school = $inquiry->getSchool();
		$customer = $inquiry->getCustomer();
		$language = $customer->getLanguage();

		// TODO Evtl. Überprüfung einbauen, ob template_language auch existiert, sonst stürzt das sowieso ab
		$template = $this->getTemplate($inquiry, $school);

		$booker = $inquiry->getBooker();
		if($booker) {
			$version->addresses = [['type' => 'billing', 'type_id' => 0]]; // Buchender benutzen
		} else {
			$version->addresses = [['type' => 'address', 'type_id' => 0]]; // Kundenadresse benutzen
		}
		
		$version->template_id = $template->id;
		$version->date = $data['document_date'];

		$aPlaceholderParams = array(
			'inquiry' => $inquiry,
			'contact' => $inquiry->getCustomer(),
			'school_format' => $school->id,
			'template_type' => $template->type,
			'options' => [],
		);

		$placeholderObj = $inquiry->createPlaceholderObject($aPlaceholderParams);
		$placeholderObj->setAdditionalData('document_address', $version->addresses);
		$placeholderObj->sTemplateLanguage = $language;

		$version->setDefaultTemplateTexts($placeholderObj, $school);
	}

	private function getTemplate(\Ext_TS_Inquiry $inquiry, \Ext_Thebing_School $school): \Ext_Thebing_Pdf_Template {

		$template = null;

		// offer_template_id gibt es erst ab V3 und ist kein Pflichtfeld
		if ($inquiry->type & \Ext_TS_Inquiry::TYPE_ENQUIRY) {
			$template = \Ext_Thebing_Pdf_Template::getInstance($this->combination->getForm()->getSchoolSetting($school, 'offer_template_id'));
		}

		if (
			!$template ||
			!$template->exist()
		) {
			$template = \Ext_Thebing_Pdf_Template::getInstance($this->combination->getForm()->getSchoolSetting($school, 'tpl_id'));
		}

		return $template;

	}

	private function createDocumentVersionItems(\Ext_Thebing_Inquiry_Document_Version $version, \Ext_Thebing_Inquiry_Document_Version_Price $priceIndex, array $data) {

//		$form = $this->combination->getForm();
		$school = $this->combination->getSchool();
		$inquiry = $version->getInquiry();

		$position = 1;
		foreach ($data['document_items'] as $item) {

			$itemObj = $version->newItem();
			$itemObj->index_from = $item['index_from'] ?? $item['from'];
			$itemObj->index_until = $item['index_until'] ?? $item['until'];
			$itemObj->parent_id = $item['parent_id'];
			$itemObj->parent_type = $item['parent_type'];
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
			$itemObj->contact_id = $inquiry->getCustomer()->id;

//			if ($form->getSchoolSetting($school, 'at_school_fees')) {
//				$itemObj->initalcost = 1;
//			}

			// Analog zu Ext_Thebing_Document den Steuersatz ergänzen
			if ($item['tax_category'] > 0) {
				$itemObj->tax = \Ext_TS_Vat::getTaxRate($item['tax_category'], $school->id);
			}

			$priceIndex->addItem($itemObj);
			$position++;

		}

	}

	/**
	 * Aufruf von buildItems für Preisberechnung und Submit
	 *
	 * @param \Ext_TS_Inquiry $inquiry
	 * @param string $docType
	 * @return array[]
	 */
	public function buildDocumentVersionItems(\Ext_TS_Inquiry $inquiry, string $docType) {

		$school = $this->combination->getSchool();
		$language = $this->combination->getLanguage()->getLanguage();

		/** @var \Ext_Thebing_Inquiry_Document_Version $version */
		[, $version] = $this->createDocumentAndVersionObject($inquiry, $docType);
		
		
		if ($this->combination->getForm()->purpose === \Ext_Thebing_Form::PURPOSE_EDIT) {
			
			$services = null;
			$services['courses'] = $this->filterJourneyServices($inquiry->getCourses());
			$services['accommodations'] = $this->filterJourneyServices($inquiry->getAccommodations());
			$services['transfers'] = $this->filterJourneyServices($inquiry->getTransfers());
			$services['insurances'] = $this->filterJourneyServices($inquiry->getInsurances());
			$services['activities'] = $this->filterJourneyServices($inquiry->getActivities());
			
			/*
			 * Wenn nur Aktivitäten dazugebucht werden, nur Items für diese berechnen!
			 * Das ist wichtig, dass beim Aktivitäten buchen NICHTS anderes berücksichtigt wird.
			 * @todo Eigentlich sollte das immer so sein, aber bei Kursen ist das komplizierter wg. der fortlaufenden 
			 * Preisberechnung und Zusatzgebühren
			 */
			if(
				empty($services['courses']) &&
				empty($services['accommodations']) &&
				empty($services['transfers']) &&
				empty($services['insurances'])					
			) {
				
				$items = $version->buildItems(null, $services);
				
			} else {

				$items = $version->buildItems();
				$diffService = new \Ts\Service\Invoice\Diff($inquiry);
				$diffService->loadItemsFromInvoices();
				$items = $diffService->getDiff($items);
				
			}
			
		} else {
			
			$items = $version->buildItems();
			
		}

		// Generelle Gebühren ergänzen, da das ja mal wieder was Eigenes sein muss
		// TODO Umstellen auf Ext_TS_Inquiry_Journey_Additionalservice, wenn das irgendwie möglich wird
		if (!empty($inquiry->transients['fees'])) {

			$saisonId = $inquiry->getSaisonFromFirstService();
			$generalFees = $school->getGeneralCosts(2, $inquiry->getCurrency(), $saisonId);

			foreach ($inquiry->transients['fees'] as $additionalFee) {

				if (!isset($generalFees[$additionalFee->id])) {
					throw new \RuntimeException('Additional general cost missing in return array! '.$additionalFee->id);
				}

				$fAmount = (float)$generalFees[$additionalFee->id]['price'];
				$iTaxCategory = \Ext_TS_Vat::getDefaultCombination('Ext_Thebing_School_Cost', $additionalFee->id, $school);

				$item = new \Ext_Thebing_Inquiry_Document_Version_Item();
				$item->type = 'additional_general';
				$item->type_id = $additionalFee->id;
				$item->description = $additionalFee->getName($language);
				$item->amount = $fAmount;
				$item->amount_net = $fAmount;
				$item->amount_provision = 0;
				$item->calculate = 1;
				$item->onPdf = 1;
				$item->amount_discount = 0;
				$item->tax_category = $iTaxCategory;
				$item->index_from = $inquiry->service_from;
				$item->index_until = $inquiry->service_until;
				$item->additional_info = '';
				$items[] = $item->getData();

			}
		}

		foreach ($items as $key => $item) {

			// Beschreibung setzen, wenn leer. Das sollte nicht vorkommen, kann es aber, wenn Sprachen einfach hinzugefügt werden
			// Für Zahlungsanbieter MUSS es eine Beschreibung geben
			if (empty(trim($item['description']))) {
				$items[$key]['description'] = 'No description';
			}

			$items[$key]['amount_with_tax'] = $item['amount'];
			$items[$key]['amount_tax'] = 0;

			// Steuerbeträge ergänzen, da die Bruttobeträge für Platzhalter und Zahlungsanbieter benötigt werden
			$taxStatus = $this->combination->getSchool()->getTaxStatus();
			if ($taxStatus == \Ext_Thebing_School::TAX_EXCLUSIVE) {
				$taxCategory = (int)$item['tax_category'];
				if ($taxCategory > 0) {
					$taxRate = \Ext_TS_Vat::getTaxRate($taxCategory, $this->combination->getSchool()->id);
					$taxData = \Ext_TS_Vat::calculateExclusiveTaxes($item['amount'], $taxRate);
					$items[$key]['amount_tax'] = $taxData['amount'];
					$items[$key]['amount_with_tax'] = $item['amount'] + $taxData['amount'];
				}
			}

		}

		return $items;
	}

	/**
	 * Anzahlungsposition generieren ($items werden damit dann überschrieben)
	 */
	public function generateDepositItem(\Ext_TS_Inquiry_Abstract $inquiry, Collection $items): ?array {

		// Zahlungsbedingungen generieren, um erste Anzahlung zu ermitteln
		$paymentConditionService = $this->createPaymentCondition($inquiry, date('Y-m-d'));
		$paymentConditionRows = collect($paymentConditionService->generateRows($items->toArray()));

		$terms = $paymentConditionRows->map(fn(\Ext_TS_Document_PaymentCondition_Row $row) => \Ext_TS_Document_Version_PaymentTerm::fromPaymentConditionRow($row));
		$dueTerms = \Ext_TS_Document_Version_PaymentTerm::calculateDueTerms($terms);
		$dueAmount = $dueTerms->sum('amount');
		$totalAmount = $items->sum('amount_with_tax');

		// Anzahlung darf nicht Totale sein, sonst macht eine Anzahlung keinen Sinn
		if (
			$dueAmount > 0 &&
			$totalAmount > $dueAmount
		) {
			return [
				'amount' => $dueAmount,
				'amount_with_tax' => $dueAmount,
				'description' => '',
				'tax_category' => 0
			];
		}

		return null;

	}

	/**
	 * Ferien zwischen Kursen verknüpfen
	 *
	 * Da die Splittung und das Speichern asynchron verlaufen und die Daten nicht im Form gespeichert werden,
	 * muss hier nachträglich geprüft werden, welche Kurse zusammengehören.
	 */
	public function mergeCourseHolidays(\Ext_TS_Inquiry_Abstract $inquiry, CarbonPeriod $period) {

		if (!$inquiry instanceof \Ext_TS_Inquiry) {
			return;
		}

		$school = $inquiry->getSchool();

		$schoolHolidays = $school->getSchoolHolidays($period->getStartDate(), $period->getEndDate());
		$schoolHolidays = collect($schoolHolidays)->transform(function (\Ext_Thebing_Absence $absence) use ($school) {
			$dateRange = new DateRange(new \DateTime($absence->from), new \DateTime($absence->until));
			\Ext_TS_Inquiry_Journey_Holiday_Split::expandDateRange($dateRange, $school->course_startday, true);
			return $dateRange;
		});

		// Kartesisches Produkt, um jeden Kurs mit jedem Kurs vergleichen zu können
		$journeyCourses = $inquiry->getJourney()->getCoursesAsObjects();
		$joinedCourses = collect($journeyCourses)->crossJoin($journeyCourses);
		/** @var \Ext_TS_Inquiry_Journey_Course[] $journeyCourses */

		foreach ($joinedCourses as $journeyCourses) {

			if (
				$journeyCourses[0] === $journeyCourses[1] ||
				$journeyCourses[0]->course_id !== $journeyCourses[1]->course_id
			) {
				continue;
			}

			$dateRange1 = new DateRange(new \DateTime($journeyCourses[0]->from), new \DateTime($journeyCourses[0]->until));
			\Ext_TS_Inquiry_Journey_Holiday_Split::expandDateRange($dateRange1, $school->course_startday, true);

			$dateRange2 = new DateRange(new \DateTime($journeyCourses[1]->from), new \DateTime($journeyCourses[1]->until));
			\Ext_TS_Inquiry_Journey_Holiday_Split::expandDateRange($dateRange2, $school->course_startday, true);

			$dateBeforeUntil = clone $dateRange1->until;
			$dateBeforeUntil->add(new \DateInterval('P1D'));

			$dateAfterFrom = clone $dateRange2->from;
			$dateAfterFrom->sub(new \DateInterval('P1D'));

			foreach ($schoolHolidays as $dateRangeHoliday) {

				if (
					$dateBeforeUntil == $dateRangeHoliday->from &&
					$dateAfterFrom == $dateRangeHoliday->until
				) {
					/** @var \Ext_TS_Inquiry_Holiday $holiday */
					$holiday = $inquiry->getJoinedObjectChild('holidays');
					$holiday->type = 'school';
					$holiday->weeks = \Ext_TS_Inquiry_Journey_Holiday_Split::getSchoolHolidayWeeks($dateBeforeUntil, $dateAfterFrom);
					$holiday->from = $dateBeforeUntil->format('Y-m-d');
					$holiday->until = $dateAfterFrom->format('Y-m-d');

					// Die Daten existieren nach dem asynchronen Split nicht mehr, werden aber für korrekte Diff-Rechnungen der Zusatzleistungen benötigt
					$originalWeeks = $journeyCourses[0]->weeks + $journeyCourses[1]->weeks;
					$originalUntil = clone $dateRange1->from;
					$originalUntil->add(new \DateInterval('P' . $originalWeeks . 'W'));

					$originalData = [
						'weeks' => $originalWeeks,
						'from' => $journeyCourses[0]->from,
						'until' => $originalUntil->format('Y-m-d')
					];

					$holiday->addSplitting($originalData, $journeyCourses[0], $journeyCourses[1]);
					continue 2;
				}

			}

		}

	}

	/**
	 * @param \Ext_TS_Inquiry $inquiry
	 * @return void
	 */
	public function prepareInquiry(\Ext_TS_Inquiry $inquiry) {

		$inquiry->status = 'pending';

		// Schülerstatus setzen
		if (($statusId = (int)$this->combination->getForm()->getSchoolSetting($this->combination->getSchool(), 'student_status_id'))) {
			$inquiry->status_id = $statusId;
		}

		// Zusatzgebühren hängen nur an der Rechnung und sind keine Services, was hier ziemlich unbrauchbar ist
		$inquiry->transients['fees'] = [];

		// Wird in Ext_TS_Inquiry::getObjectByAlias() verwendet
		$inquiry->transients['flex'] = new \ArrayObject([], \ArrayObject::ARRAY_AS_PROPS);
		$inquiry->transients['uploads'] = new \ArrayObject([], \ArrayObject::ARRAY_AS_PROPS);

		$contact = $inquiry->getCustomer();
		$contact->bCheckGender = false;

	}

	/**
	 * Bei vorhandener Buchung alle Leistungen ausfiltern, die nicht flüchtig sind (also schon in der Buchung exisieren)
	 *
	 * @param \Ext_TS_Inquiry_Journey_Service[] $services
	 * @return \Ext_TS_Inquiry_Journey_Service[]
	 */
	public static function filterJourneyServices(array $services) {

		return array_filter($services, function (\Ext_TS_Inquiry_Journey_Service $service) {
			return !empty($service->transients[\Ext_TS_Inquiry_Journey_Service::TRANSIENT_FORM_SERVICE]);
		});

	}

	private function createPaymentCondition(\Ext_TS_Inquiry_Abstract $inquiry, string $date): \Ext_TS_Document_PaymentCondition {

		$paymentConditionService = new \Ext_TS_Document_PaymentCondition($inquiry, true);
		$paymentConditionService->setDocumentDate($date);

		$conditionId = $this->combination->getForm()->getSchoolSetting($this->combination->getSchool(), 'payment_condition_id');
		if (!empty($conditionId)) {
			$paymentConditionService->setPaymentCondition(\Ext_TS_Payment_Condition::getInstance($conditionId));
		}

		return $paymentConditionService;

	}

}
