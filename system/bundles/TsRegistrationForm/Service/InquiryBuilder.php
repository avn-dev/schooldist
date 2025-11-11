<?php

namespace TsRegistrationForm\Service;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Core\Factory\ValidatorFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use TcFrontend\Traits\WithInputCleanUp;
use Ts\Events\Inquiry\PaymentFailed;
use TsActivities\Enums\AssignmentSource;
use TsFrontend\Entity\InquiryFormProcess;
use TsFrontend\Exceptions\PaymentError;
use TsFrontend\Helper\PaymentMethodsHelper;
use TsRegistrationForm\Factory\ServiceBlockFactory;
use TsRegistrationForm\Generator\CombinationGenerator;
use TsRegistrationForm\Helper\BuildInquiryHelper;
use TsRegistrationForm\Helper\FormValidatorHelper;
use TsRegistrationForm\Helper\ServiceDatesHelper;
use TsRegistrationForm\Helper\UploadHelper;
use TsRegistrationForm\Validation\Rules\ActivityCourseRule;

/**
 * InquirySaver für Registration Form V3
 */
class InquiryBuilder {
	use WithInputCleanUp;

	/**
	 * @var CombinationGenerator
	 */
	private $combination;

	/**
	 * @var Request
	 */
	private $request;

	/**
	 * @var Collection
	 */
	private $schoolData;

	/**
	 * @var BuildInquiryHelper
	 */
	private $helper;

	/**
	 * @var ServiceDatesHelper
	 */
	private $serviceDatesHelper;

	/**
	 * @var FormValidatorHelper
	 */
	private $rulesGenerator;

	/**
	 * @var UploadHelper
	 */
	private $uploadHelper;

	/**
	 * @var \Ext_TS_Inquiry
	 */
	private $inquiry;

	/**
	 * @var array
	 */
	private $errors = [];

	/**
	 * @var array
	 */
	private $items = [];

	/**
	 * @var \Ext_TS_Inquiry_Payment_Unallocated
	 */
	private $payment;

	/**
	 * @var array
	 */
	private $debugTimes = [];

	/**
	 * @var bool
	 */
	private $hasServices = false;

	/**
	 * @param CombinationGenerator $combination
	 * @param Request $request
	 * @param int $validationLevel
	 */
	public function __construct(CombinationGenerator $combination, Request $request, int $validationLevel) {

		$this->combination = $combination;
		$this->request = $request;
		$this->schoolData = $combination->getSchoolData();
		$this->helper = new BuildInquiryHelper($this->combination);
		$this->rulesGenerator = new FormValidatorHelper($validationLevel, $this->combination, $this->schoolData, $combination->getSettings());
		$this->uploadHelper = new UploadHelper($this->combination, $this->request);
		$this->inquiry = $combination->getInquiry();

	}

	public function generate(): \Ext_TS_Inquiry {

		$debugTime = microtime(true);

		if ($this->combination->getForm()->isCreatingBooking()) {
			$this->inquiry = $this->helper->createInquiryObject($this->request->input('type') !== 'quote');
		} else {
			if ($this->inquiry === null) {
				throw new \RuntimeException('InquiryBuilder: No inquiry for key: '.$this->request->input('fields.booking'));
			}
		}

		$this->serviceDatesHelper = new ServiceDatesHelper($this->combination, $this->inquiry);
		$this->rulesGenerator->setServiceDatesHelper($this->serviceDatesHelper);

		$this->transformFields(); // Felder zuerst, da Kurse ggf. Alter brauchen

		foreach ((new ServiceBlockFactory())->makeAll($this->combination, $this->schoolData) as $serviceBlock) {
			$serviceBlock->transform($this, $this->rulesGenerator->getLevel());
		}

		// TODO Alle anderen Services ebenso umstellen
//		$this->transformCourses();
		$this->transformAccommodations();
		$this->transformTransfers();
		$this->transformInsurances();
		$this->transformFees();
		$this->transformActivities();

		if (
			!$this->hasServices &&
			(int)$this->getInquiry()->getJourney()->type === \Ext_TS_Inquiry_Journey::TYPE_REQUEST
		) {
			// Wenn es keine Leistung gibt, muss die Kombination auch nicht angezeigt werden, wird aber für school_id zwingend benötigt
			$this->getInquiry()->getJourney()->type = \Ext_TS_Inquiry_Journey::TYPE_DUMMY;
		}

		$this->inquiry->findSpecials(false);
		
		$this->debugTimes['generate'] = microtime(true) - $debugTime - $this->debugTimes['holidays'];

		return $this->inquiry;
	}

	/**
	 * Alle Services eines Typs ermitteln, unabhängig von unterschiedlichen Blöcken
	 *
	 * Diese Methode ist public, da diese auch vom ValidatorHelper verwendet wird.
	 *
	 * @param string $type
	 * @return Collection
	 */
	public function getServiceRequestData(string $type): Collection {

		return collect($this->combination->getSettings()['blocks'])
			->filter(function (string $blockType) use ($type) {
				return $blockType === $type;
			})
			->keys()
			->map(function (string $blockKey) {
				return collect($this->request->input('services.'.$blockKey, []))->map(function (array $service, int $index) use ($blockKey) {
					$service['block'] = $blockKey;
					$service['index'] = $index;
					return $service;
				});
			})
			// Block-Ebene wegwerfen
			->flatten(1);

	}

	public function transformAdditionalServices(\Ext_TS_Inquiry_Journey_Service $object, array $service, array $matchingService) {

		// Zusatzleistungen pro Leistung; nicht durch den Validator, da nested Felder bisher nicht im Validator funktionieren
		foreach ($service['additional_services'] as $additionalService) {
			if (in_array($additionalService['fee'], $matchingService['additional_services'])) {

				$fee = \Ext_Thebing_School_Additionalcost::getInstance($additionalService['fee']);

				$journeyService = new \Ext_TS_Inquiry_Journey_Additionalservice();
				$journeyService->transients[\Ext_TS_Inquiry_Journey_Service::TRANSIENT_FORM_SERVICE] = true;
				$journeyService->additionalservice_id = $fee->id;
				$journeyService->visible = 1;

				$this->setJourneyChild('additionalservices', $journeyService);

				// relation_id
				$object->setJoinedObjectChild('additionalservices', $journeyService);

				// Notwendig für Preisberechnung/buildItems()
				$object->addJoinTableObject('additionalservices', $fee);

			}
		}

	}

	private function transformAccommodations() {

		$accommodationBlock = $this->combination->getForm()->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS, true);

		foreach ($this->getServiceRequestData('accommodations') as $accommodation) {

			// Die Unterkunft kommt bei der aktuellen Implementierung immer mit, daher vor Validierung aussortieren
			// Immer überspringen, wenn leer, weil dann bspw. eine Abhängigkeit greift (Überprüfung nur client-seitig)
			if (
//				$index === 0 &&
//				!$accommodationBlock->required &&
				$accommodation['accommodation'] === null
			) {
				continue;
			}

			$accommodation['start'] = $this->convertRequestDate($accommodation['start']);
			$accommodation['end'] = $this->convertRequestDate($accommodation['end']);

			$matchingAccommodation = $this->schoolData->get('accommodations')->firstWhere('key', $accommodation['accommodation']);

			if ($matchingAccommodation === null) {
				//throw new \RuntimeException('Accommodation '.$accommodation['accommodation'].' does not exist in form cache');
				$this->combination->log('Accommodation '.$accommodation['accommodation'].' does not exist in form cache, skipping', [$accommodation, $this->request->all()]);
				continue;
			}

			// Kontext weitergeben für Validation-Rules
			// https://github.com/laravel/ideas/issues/1431
			// https://github.com/laravel/framework/issues/23094
			$matchingAccommodation['request_accommodation'] = $accommodation;
			$validator = (new ValidatorFactory())->make($accommodation, $this->rulesGenerator->getAccommodationRules($matchingAccommodation));

			if (!$validator->passes()) {
				$this->addValidatorErrors($validator->messages()->messages(), 'services.'.$accommodationBlock->getServiceBlockKey().'.'.$accommodation['index']);
				continue;
			}

			$matchingCombination = collect($matchingAccommodation['combinations'])
				->where('key_room', $accommodation['roomtype'])
				->where('key_board', $accommodation['board'])
				->first();

			$from = new Carbon($accommodation['start']);
			$until = new Carbon($accommodation['end']);
			$weeks = \Ext_Thebing_Accommodation_Amount::getWeekCount($from, $until, $this->combination->getSchool());

			$journeyAccommodation = new \Ext_TS_Inquiry_Journey_Accommodation();
			$journeyAccommodation->transients[\Ext_TS_Inquiry_Journey_Service::TRANSIENT_FORM_SERVICE] = true;
			$journeyAccommodation->accommodation_id = $accommodation['accommodation'];
			$journeyAccommodation->roomtype_id = $accommodation['roomtype'];
			$journeyAccommodation->meal_id = $accommodation['board'];
			$journeyAccommodation->weeks = max($weeks, 1);
			$journeyAccommodation->from = $from->toDateString();
			$journeyAccommodation->until = $until->toDateString();
			$journeyAccommodation->calculate = 1;
			$journeyAccommodation->visible = 1;
			$journeyAccommodation->for_matching = 1;

			$this->setJourneyChild('accommodations', $journeyAccommodation);

			$this->transformAdditionalServices($journeyAccommodation, $accommodation, $matchingCombination);

		}
	}

	private function transformTransfers() {

		$transferBlock = $this->combination->getForm()->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS);
		$period = $this->serviceDatesHelper->getServicePeriod();

		if (
			$transferBlock === null ||
			($this->combination->getSettings()['has_service_period_blocks'] && !$period)
		) {
			return;
		}

		$journey = $this->inquiry->getJourney();

		// Modus/Typ wird in den einzelnen Services mitgeschliffen, da das entweder als echter Block existierten müsste oder hier (Validierung, Abhängigkeiten, usw.)
		$mode = $this->request->input('services.'.$transferBlock->getServiceBlockKey().'.0.mode');
		if ($mode !== null) {
			$journey->transfer_mode = (int)$mode;
		}

		foreach ($this->getServiceRequestData('transfers') as $transfer) {

			$filled = collect($transfer)
				->only(['origin', 'destination', 'airline', 'flight_number', 'date', 'time', 'comment'])
				->some(fn ($value) => $value !== null && $value !== '');

			if (!$filled) {
				continue;
			}

			$type = match ($transfer['type']) {
				'arrival' => \Ext_TS_Inquiry_Journey_Transfer::TYPE_ARRIVAL,
				'departure' => \Ext_TS_Inquiry_Journey_Transfer::TYPE_DEPARTURE
			};

			// Bei einem einfachen Transferblock werden Werte für beide Services gesetzt, aber nur die gebuchten dürfen übernommen werden
			if (
				(!$transferBlock->getSetting('show_extended_form') || !$transferBlock->getSetting('show_fields_without_type_check')) &&
				!\Ext_TS_Inquiry_Journey_Transfer::checkIfBooked($type, (int)$journey->transfer_mode)
			) {
				continue;
			}

			if (empty($transfer['date'])) {
				if ($type === \Ext_TS_Inquiry_Journey_Transfer::TYPE_ARRIVAL) $transfer['date'] = $period->getStartDate();
				if ($type === \Ext_TS_Inquiry_Journey_Transfer::TYPE_DEPARTURE) $transfer['date'] = $period->getEndDate();
			}

			$transfer['date'] = $this->convertRequestDate($transfer['date']);

			$validator = (new ValidatorFactory())->make($transfer, $this->rulesGenerator->getTransferRules((int)$journey->transfer_mode, $type), [
				'date_format' => $this->combination->getForm()->getTranslation('error_time', $this->combination->getLanguage())
			]);

			if (!$validator->passes()) {
				$this->addValidatorErrors($validator->messages()->messages(), 'services.'.$transferBlock->getServiceBlockKey().'.'.$transfer['index']);
				continue;
			}

			$journeyTransfer = collect($journey->getTransfersAsObjects())->firstWhere('transfer_type', $type) ?? new \Ext_TS_Inquiry_Journey_Transfer();
			$journeyTransfer->transfer_type = $type;
			$journeyTransfer->transfer_date = $transfer['date']?->toDateString();
			$journeyTransfer->transfer_time = $transfer['time'];
			$journeyTransfer->airline = $transfer['airline'];
			$journeyTransfer->flightnumber = $transfer['flight_number'];
			$journeyTransfer->comment = $transfer['comment'];
			$journeyTransfer->booked = 1;
			$journeyTransfer->setLocationByMergedString('start', $transfer['origin'] ?? '0');
			$journeyTransfer->setLocationByMergedString('end', $transfer['destination'] ?? '0');

			if ($this->combination->getForm()->purpose === \Ext_Thebing_Form::PURPOSE_EDIT) {
				$journeyTransfer->setChanged((string)$journey->transfer_mode !== $journey->getOriginalData('transfer_mode'));
			} else {
				// Nur Rechnung für diesen Service erzeugen bei neuen Buchungen
				$journeyTransfer->transients[\Ext_TS_Inquiry_Journey_Service::TRANSIENT_FORM_SERVICE] = true;
			}

			$this->setJourneyChild('transfers', $journeyTransfer);

		}

	}

	private function transformInsurances() {

		$block = $this->combination->getForm()->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_INSURANCES);
		if ($block === null) {
			return;
		}

		$period = $this->serviceDatesHelper->getServicePeriod();

		foreach ($this->getServiceRequestData('insurances') as $insurance) {

			$insurance['start'] = $this->convertRequestDate($insurance['start']);
			$insurance['end'] = $this->convertRequestDate($insurance['end']);

			$matchingInsurance = $this->schoolData->get('insurances')->firstWhere('key', $insurance['insurance']);
			if ($matchingInsurance === null) {
				$this->combination->log('Insurance '.$insurance['insurance'].' does not exist in form cache, skipping', [$insurance, $this->request->all()]);
				continue;
			}

			$validator = (new ValidatorFactory())->make($insurance, $this->rulesGenerator->getInsuranceRules($matchingInsurance));

			if (!$validator->passes()) {
				$this->addValidatorErrors($validator->messages()->messages(), 'services.'.$insurance['block'].'.'.$insurance['index']);
				continue;
			}

			$from = new Carbon($insurance['start']);
			if (
				// Explizit abfragen, da bei ServiceMutation die Versicherung dann alle Werte hat
				$matchingInsurance['type'] === 'week' ||
				empty($insurance['end'])
			) {
				// Wöchentliche Versicherung: Startdatum und Dauer
				$weeks = $insurance['duration'] ?: 1;
				$until = $from->copy()->addWeeks($weeks);

				// Wenn das Ende der Versicherung in die Endwoche des Leistungszeitraum fällt, Ende automatisch verlängern
				if (
					$period->getEndDate() > $until &&
					$until->diffInDays($period->getEndDate()) < 7
				) {
					$until = clone $period->getEndDate();
				}
			} else {
				// Tägliche/einmalige Versicherung: Start- und Enddatum
				// Die Logik, wie man auf die Wochen kommen kann, gibt es eigentlich nicht, weil bei Versicherungen mit Wochen das Enddatum gesperrt ist
				// \Ext_Thebing_Insurances_Gui2_Customer::format hat die Wochen damals immer auf die selbe Weise errechnet: 1 Tag addieren und aufrunden
				$until = new Carbon($insurance['end']);
				$days = $from->diffInDays($until->copy()->addDay());
				$weeks = ceil($days / 7);
				$weeks = $weeks > 0 ? $weeks : 1;
			}

			$journeyInsurance = new \Ext_TS_Inquiry_Journey_Insurance();
			$journeyInsurance->transients[\Ext_TS_Inquiry_Journey_Service::TRANSIENT_FORM_SERVICE] = true;
			$journeyInsurance->insurance_id = $insurance['insurance'];
			$journeyInsurance->weeks = $weeks;
			$journeyInsurance->from = $from->toDateString();
			$journeyInsurance->until = $until->toDateString();
			$journeyInsurance->visible = 1;

			$this->setJourneyChild('insurances', $journeyInsurance);

		}

	}

	private function transformFees() {

		foreach ($this->getServiceRequestData('fees') as $fee) {

			$validator = (new ValidatorFactory())->make($fee, $this->rulesGenerator->getFeeRules());

			if (!$validator->passes()) {
				$this->addValidatorErrors($validator->messages()->messages(), 'services.'.$fee['block'].'.'.$fee['index']);
				continue;
			}

			$feeObj = \Ext_Thebing_School_Additionalcost::getInstance($fee['fee']);
			$feeObj->transients[\Ext_TS_Inquiry_Journey_Service::TRANSIENT_FORM_SERVICE] = true;
			$this->inquiry->transients['fees'][] = $feeObj;
			$this->hasServices = true;

		}

	}

	private function transformActivities() {

		$block = $this->combination->getForm()->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_ACTIVITY, false);
		if ($block === null) {
			return;
		}

		$selectedCourses = ActivityCourseRule::pluckCourseIds($this->inquiry);
		$inquiryPeriod = $this->serviceDatesHelper->getServicePeriod();
		if ($inquiryPeriod === null) {
			// Fake-Periode setzen, da der ServiceMutationHelper die Aktivitäten löschen können muss
			$inquiryPeriod = new CarbonPeriod(Carbon::now(), Carbon::now()->addWeek());
		}

		$combinations = collect();
		if ($block->getSetting('based_on') === 'scheduling') {
			$combinations = (new \TsActivities\Service\ActivityService(AssignmentSource::REGISTRATION_FORM))
				->searchAvailableBlocksForInquiry($this->inquiry, $inquiryPeriod->getIncludedStartDate(), $inquiryPeriod->getIncludedEndDate()->endOfDay());
		}

		foreach ($this->getServiceRequestData('activities') as $activity) {

			$matchingActivity = $this->schoolData->get('activities')->firstWhere('key', $activity['activity']);
			if ($matchingActivity === null) {
				$this->combination->log('Activity '.$activity['activity'].' does not exist in form cache, skipping', [$activity, $this->request->all()]);
				continue;
			}

			// Basierend auf Planung: Passende Kombination (Block + Woche) suchen
			$activityCombination = null;
			if (
				$block->getSetting('based_on') === 'scheduling' &&
				!empty($activity['additional']) &&
				($activityCombination = $combinations->first(fn(\TsActivities\Dto\ActivityBlockWeekCombination $c) => $c->buildKey() === $activity['additional'])) !== null
			) {
				/** @var \TsActivities\Dto\ActivityBlockWeekCombination $activityCombination */
				// Immer ganze Woche, analog zu \TsActivities\Service\ActivityService::assignBlockToInquiry()
				$activity['start'] = Carbon::instance(\Ext_Thebing_Util::getPreviousCourseStartDay($activityCombination->week, 1));
				$activity['duration'] = 1;

				// Gibt es mehr als ein Event bei einer Block-Aktivität, muss die Anzahl gesetzt werden für den korrekten Preis
				if ($activityCombination->activity->isCalculatedPerBlock()) {
					$activity['units'] = count($activityCombination->dates);
				}
			}

			$activity['start'] = $this->convertRequestDate($activity['start']);

			$matchingActivity['selected_courses'] = $selectedCourses; // ActivityCourseRule
			$validator = (new ValidatorFactory())->make($activity, $this->rulesGenerator->getActivityRules($matchingActivity));

			if (!$validator->passes()) {
				$this->addValidatorErrors($validator->messages()->messages(), 'services.'.$activity['block'].'.'.$activity['index']);
				continue;
			}

			// Statt Fehlermeldung: Aktivitäten einfach überspringen
			if ($matchingActivity['mode'] === 'scheduling') {
				if (
					$activityCombination === null ||
					!$activityCombination->activity->isValidForInquiry($this->inquiry) ||
					!$activityCombination->block->hasFreeSeats($activityCombination->activity, $activityCombination->dates[0]->start)
				) {
					$this->combination->log('Invalid scheduled activity, ignoring', [$activityCombination]);
					continue;
				}
			}

			if ($matchingActivity['mode'] === 'scheduling') {
				// Werte werden durch ActivityBlockWeekCombination gesetzt
				$from = new Carbon($activity['start']);
				$weeks = (int)$activity['duration'];
				$until = $from->copy()->addWeeks($weeks)->subDay();
				$units = (int)$activity['units'];
			} elseif ($matchingActivity['type'] === 'block') {
				// Bei Aktivität pro Block gibt es nur die Auswahl der Units
				$from = $inquiryPeriod->getStartDate();
				$until = $inquiryPeriod->getEndDate();
				$weeks = ceil($from->diffInDays($until) / 7);
				$units = (int)$activity['units'] ?: 1;
			} else {
				$from = new Carbon($activity['start']);
				$weeks = (int)$activity['duration'];
				$until = $from->copy()->addWeeks($weeks)->subDay();
				$units = 0;
			}

			$journeyActivity = new \Ext_TS_Inquiry_Journey_Activity();
			$journeyActivity->transients[\Ext_TS_Inquiry_Journey_Service::TRANSIENT_FORM_SERVICE] = true;
			$journeyActivity->activity_id = $activity['activity'];
			$journeyActivity->weeks = $weeks;
			$journeyActivity->from = $from->toDateString();
			$journeyActivity->until = $until->format('Y-m-d');
			$journeyActivity->blocks = $units;
			$journeyActivity->comment = \L10N::t('Automatisch über Formular gebucht.', \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH);
			$journeyActivity->transients['block'] = $activity['block'];
			$journeyActivity->transients['courses'] = $matchingActivity['courses'];

			$this->setJourneyChild('activities', $journeyActivity);

			// Zuweisung zu Aktivität (1 Woche oder 1 Block)
			if ($matchingActivity['mode'] === 'scheduling') {
				/** @var \TsActivities\Entity\Activity\BlockTraveller $allocation */
				$allocation = $journeyActivity->getJoinedObjectChild('allocations');
				$allocation->setJoinedObject('contact', $this->inquiry->getCustomer());
				$allocation->block_id = $activityCombination->block->id;
				$allocation->week = $from->toDateString();
			}

		}

	}

	private function transformFields() {

		$input = $this->request->input('fields', []);
		$fields = $this->schoolData->get('fields')['fields']; // Anderer Key ist services

		$validator = (new ValidatorFactory())->make($input, $this->rulesGenerator->getFieldRules(), [
			// Alle anderen Fehlermeldungen sollten vom Frontend abgefangen werden
			'email_mx' => $this->combination->getForm()->getTranslation('error_email', $this->combination->getLanguage())
		]);

		// Uploads prüfen, ob physische Datei auch wirklich existiert (sollte eigentlich nicht vorkommen, außer Form wurde schon submitted)
		$validator->after($this->uploadHelper->createUploadValidatorHook());

		$validator->after($this->rulesGenerator->createPaymentValidatorHook());

		// Prüfen, ob ein Feld implizit Pflichtfeld ist aufgrund einer erfüllten Service-Abhängigkeit (required_if_service_value)
		$this->rulesGenerator->appendServiceValueExtension($validator, $this);

		if (!$validator->passes()) {
			$this->addValidatorErrors($validator->messages()->messages(), 'fields');
			// Hier darf nicht abgebrochen werden, da die Werte dennoch für bspw. die Preisberechnung benötigt werden
			// return;
		}

		foreach ($input as $name => $value) {

			if (!isset($fields[$name])) {
				$this->combination->log('Unknown form field, skipping:'.$name, [$this->request->all()], false);
				continue;
			}

			if (
				!empty($fields[$name]['internal']) ||
				isset($validator->failed()[$name])
			) {
				continue;
			}

			/*
			 * Wenn für ein Feld kein Wert übermittelt wird, soll auch nicht erst ein Objekt dafür geholt werden.
			 * Nur bei NEW, damit man im EDIT Modus Werte entfernen kann.
			 */
			if(
				$this->combination->getForm()->purpose === \Ext_Thebing_Form::PURPOSE_NEW &&
				empty($value)
			) {
				continue;
			}
			
			$field = $fields[$name];
			[$alias, $column] = $field['mapping'];
			$object = $this->inquiry->getObjectByAlias($alias, $column);

			if ($object === null) {
				throw new \RuntimeException(sprintf('No object for field: %s.%s (%s)', $alias, $column, $name));
			}

			if (isset($field['validation'])) {
				if (in_array('date', $field['validation'])) {
					$value = $this->convertRequestDate($value);
					if ($value !== null) {
						$value = $value->toDateString();
					}
				}

				if (in_array('boolean', $field['validation'])) {
					$value = (int)$value;
				}
			}

			// HTML-Tags entfernen (z.B. XSS)
			// Hier kommen auch Zahlen, null, Arrays rein. Alles wird zu String oder bei Array zu einem TypeError
			if (is_string($value)) {
				$value = $this->cleanUp($value);
			}

			// Sonderfall Flex-Mehrsprachigkeit: Array mit Sprache
			if (!empty($field['flex_i18n'])) {
				$value = [$this->combination->getLanguage()->getLanguage() => $value];
			}

			$object->$column = $value;

		}

		if (!array_key_exists(\Ext_Thebing_Form_Page_Block::SUBTYPE_CHECKBOX_NEWSLETTER, $input)) {
			$this->inquiry->getCustomer()->setDetail(\Ext_Thebing_Form_Page_Block::SUBTYPE_CHECKBOX_NEWSLETTER, '1');
		}

	}

	/**
	 * https://github.com/nathanreyes/v-calendar/issues/286
	 *
	 * @param string $input ISO 8601 (JS)
	 * @return Carbon|null
	 */
	public function convertRequestDate($input) {

		$date = Carbon::make($input);

		if (!$date instanceof Carbon) {
			return null;
		}

		// Das muss wegen DST auf Client-Seite passieren
//		$offset = $this->request->input('tz_offset', 0);
//		if ($offset !== 0) {
//			$date->subMinutes($offset);
//		}

		return $date;

	}

	public function addValidatorErrors(array $messages, string $ctx) {

		foreach ($messages as $field => $messages) {
			$this->errors[$ctx . '.' . $field] = $messages;
		}

	}

	public function setJourneyChild(string $key, \Ext_TS_Inquiry_Journey_Service $service) {

		if (!$service->validate()) {
			// Es sollte vorab alles abgefangen sein, da hier dann unbekannte Fehler auftreten
			throw new \RuntimeException('WDBasic validation error for ' . get_class($service) . '! ' . print_r($service->getData()));
		}

		$journey = $this->inquiry->getJourney();
		$journey->setJoinedObjectChild($key, $service);

		if ($service->transients[\Ext_TS_Inquiry_Journey_Service::TRANSIENT_FORM_SERVICE]) {
			$this->hasServices = true;
		}

	}

	/**
	 * Buchung final speichern
	 *
	 * Hier sollte nichts passieren, was bereits vorher notwendig wird, z.B. Abhängigkeiten auf irgendeinen Status
	 * o.ä. Dafür dient dann die prepareInquiry.
	 */
	public function save(): bool {

		$this->combination->getSpamShield()->detect();

		$debugTime = microtime(true);

		if (!$this->inquiry->exist()) {
			
			$this->handleTrackingKey();

			// Kundennummer immer generieren #16279
			// Das muss außerdem nach dem Tracking-Key/Agentur passieren
			$customerNumberGenerator = new \Ext_Thebing_Customer_CustomerNumber($this->inquiry);
			$customerNumberGenerator->saveCustomerNumber(false, false);
			
			$booker = $this->inquiry->getBooker();
			if ($booker) {
				$bookerNumberGenerator = new \Ext_Thebing_Customer_CustomerNumber($this->inquiry);
				$bookerNumberGenerator->setCustomer($booker);
				$bookerNumberGenerator->saveCustomerNumber(false, false);
			}
			
		}

		// Hier sollte nichts mehr kommen, weil alles durch die Validierung abgefangen sein sollte
		if (($validation = $this->inquiry->validate()) !== true) {
			$this->combination->log('Inquiry validation failed but will be ignored', [$validation, $this->request->all()]);
		}

		if (
			!$this->inquiry->exist() &&
			$this->combination->getFrontendLog() === null
		) {
			throw new \RuntimeException('No frontend log for online booking');
		}

		\System::wd()->executeHook('ts_registration_form_inquiry_save', $this->combination, $this->request, $this->inquiry);

		$this->inquiry->disableValidate();
		
		$saved = $this->inquiry->save();

		// Specials müssen neu gesetzt werden, damit die IDs korrekt sind
		$this->inquiry->findSpecials(true);

		if (!$saved instanceof \Ext_TS_Inquiry) {
			return false;
		}

		if (($process = $this->combination->getBookingGenerator()->getProcess()) instanceof InquiryFormProcess) {
			$process->submitted = Carbon::now()->toDateTimeString();
			$process->save();
		}

		\Log::add($new ? \Ext_TS_Inquiry::LOG_INQUIRY_CREATED : \Ext_TS_Inquiry::LOG_INQUIRY_UPDATED, $this->inquiry->id, get_class($this->inquiry), [
			'combination_id' => (int)$this->combination->getCombination()->id,
			'form_id' => (int)$this->combination->getForm()->id,
			'tracking_key' => $this->request->input('fields.tracking_key')
		]);

		\Ext_TC_Flexibility::saveData($this->inquiry->transients['flex'], $this->inquiry->id);

		$this->uploadHelper->moveUploads($this->inquiry->getCustomer());

		$this->debugTimes['save'] = microtime(true) - $debugTime;

		$this->handlePayment();

		$this->prepareTask();

		return true;

	}

	private function handlePayment() {

		$debugTime = microtime(true);

		// Falls hier irgendwas fehlschlägt, wird die Buchung nicht blockiert, da die Buchung bereits gespeichert wurde
		try {

			$block = $this->combination->getForm()->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_PAYMENT, false);
			if (!$block) {
				return;
			}

			$input = (array)$this->request->input('fields.payment');
			$providers = collect($block->getSetting('provider'))->push('skip');
			$paymentMethodsHelper = new PaymentMethodsHelper($this->combination);
			$paymentMethodsHelper->generatePaymentProviders($providers, $this->combination->getSchool());
			$paymentMethodsHelper->setMethod(Arr::get($input, 'method'));
			$handler = $paymentMethodsHelper->createPaymentHandler(); /** @var \TsFrontend\Interfaces\PaymentProvider\RegistrationForm $handler */

			// Zahlung nicht Pflicht
			if (!$block->required && empty($input)) {
				return;
			}

			$payment = $handler->capturePayment($this->inquiry, collect($this->request->input('fields.payment')));
			if ($payment === null) {
				// Explizit kein Payment anlegen (z.B. Skip)
				return;
			}

			$language = new \Tc\Service\Language\Frontend($this->combination->getSchool()->getLanguage());
			$payment->comment .= "\n" . $language->translate('Paid via registration form (combination ID ' . $this->combination->getCombination()->id . ')');
			if (empty($payment->payment_method_id)) {
				$payment->payment_method_id = $handler->getAccountingPaymentMethod()->id;
			}

			$payment->inquiry_id = $this->inquiry->id;
			$payment->save();

			$this->combination->log('capturePayment: Captured '.get_class($handler), [$payment->getData()], false);

			$this->payment = $payment;

		} catch (PaymentError $e) {
			$this->combination->log('PaymentError: capturePayment failed! '.$e->getMessage(), ['payment' => $e->getAdditional(), 'inquiry' => $this->inquiry->getData()]);
			PaymentFailed::dispatch($this->inquiry);
		} catch (\Throwable $e) {
			$this->combination->log('Throwable: capturePayment failed! '.$e->getMessage(), ['inquiry' => $this->inquiry->getData(), 'trace' => $e->getTraceAsString()]);
			PaymentFailed::dispatch($this->inquiry);
		}

		$this->debugTimes['payment'] = microtime(true) - $debugTime;

	}

	private function prepareTask() {

		$docType = null;
		$numberrange = null;

		// Wenn Leistungen vorhanden sind, Items und Dokument generieren
		if ($this->hasServices) {

			$docType = 'brutto';
			if ($this->combination->getForm()->isCreatingBooking()) {
				$docType = $this->combination->getForm()->getSchoolSetting($this->combination->getSchool(), 'generate_invoice') ? 'brutto' : 'proforma_brutto';
				if ($this->inquiry->type & \Ext_TS_Inquiry::TYPE_ENQUIRY) {
					$docType = 'offer_brutto';
				}
			} elseif (
				$this->combination->getForm()->purpose == \Ext_Thebing_Form::PURPOSE_EDIT &&
				!$this->inquiry->has_invoice &&
				!$this->inquiry->has_proforma
			) {
				// Proforma für update booking nur erlaubt wenn keine Rechnung oder Proforma vorhanden
				$docType = $this->combination->getForm()->getSchoolSetting($this->combination->getSchool(), 'generate_invoice') ? 'brutto' : 'proforma_brutto';
			}

			\Ext_Thebing_Inquiry_Document_Numberrange::setInbox($this->inquiry->getInbox());
			$numberrange = \Ext_Thebing_Inquiry_Document_Numberrange::getObject($docType, false, $this->combination->getSchool()->id);

			$debugTime = microtime(true);
			$this->items = $this->helper->buildDocumentVersionItems($this->inquiry, $docType);
			$this->debugTimes['items'] = microtime(true) - $debugTime;

		}

		$debugTime = microtime(true);

		$stackData = [
			'combination_id' => (int)$this->combination->getCombination()->id,
			'object' => get_class($this->inquiry),
			'object_id' => (int)$this->inquiry->id,
			'document_type' => $docType,
			'document_items' => $this->items,
			'document_date' => date('Y-m-d'),
			'numberrange_id' => (int)$numberrange?->id,
			'unallocated_payment_id' => $this->payment?->id
		];

		\Core\Entity\ParallelProcessing\Stack::getRepository()
			->writeToStack('ts-registration-form/inquiry-task', $stackData, 2);

		\Ext_Gui2_Index_Stack::save(true); // Kein executeCache, sondern 0er-Einträge in den Stack

		$this->debugTimes['task'] = microtime(true) - $debugTime;

	}

	private function handleTrackingKey() {

		$key = $this->request->input('fields.tracking_key');
		if ($key === null) {
			return;
		}

		$agency = \Ext_Thebing_Agency::getRepository()->findOneBy(['tracking_key' => $key]);
		if ($agency !== null) {
			$this->inquiry->agency_id = $agency->id;
		}

	}

	public function createPaymentItemFunction(\Ext_Thebing_Form_Page_Block $block): \Closure {

		return function () use ($block) {
			$items = collect($this->getHelper()->buildDocumentVersionItems($this->getInquiry(), 'brutto'));

			// Nur Anzahlungsbetrag
			if (
				$block->getSetting('pay_deposit') &&
				!$this->request->boolean('fields.payment_full')
			) {
				$item = $this->getHelper()->generateDepositItem($this->getInquiry(), $items);
				if ($item !== null) {
					$item['description'] = $this->combination->getLanguage()->translate('Anzahlung');
					$items = collect([$item]);
				}
			}

			// Zahlungen mit einem Betrag von 0 werden von Zahlungsanbietern generell nicht akzeptiert
			$sum = $items->sum('amount_with_tax');
			if (empty($sum)) {
				$this->combination->log('RegistrationController::payment::skip_no_amount', [], false);
				$items = collect();
			}

			return $items;
		};

	}

	public function hasErrors(): bool {
		return !empty($this->errors);
	}

	public function getErrors(): array {
		return $this->errors;
	}

	public function getItems(): array {
		return $this->items;
	}

	public function getDebugTimes(): array {
		return $this->debugTimes + ['total' => array_sum($this->debugTimes)];
	}

	public function getHelper(): BuildInquiryHelper {
		return $this->helper;
	}

	public function getDateHelper(): ServiceDatesHelper {
		return $this->serviceDatesHelper;
	}

	public function getInquiry(): ?\Ext_TS_Inquiry {
		return $this->inquiry;
	}

	public function getPayment(): ?\Ext_TS_Inquiry_Payment_Unallocated {
		return $this->payment;
	}

}
