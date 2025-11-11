<?php

namespace TsRegistrationForm\Helper;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use TsActivities\Enums\AssignmentSource;
use TsRegistrationForm\Factory\ServiceBlockFactory;
use TsRegistrationForm\Generator\CombinationGenerator;
use TsRegistrationForm\Validation\Rules\AccommodationAvailabilityRule;
use TsRegistrationForm\Validation\Rules\AccommodationCombinationRule;
use TsRegistrationForm\Validation\Rules\ActivityCourseRule;

class ServiceMutationHelper {

	/**
	 * @var CombinationGenerator
	 */
	private $combination;

	/**
	 * @var \Ext_TS_Inquiry
	 */
	private $inquiry;

	/**
	 * @var string
	 */
	private $changeTrigger;

	/**
	 * @var array
	 */
	private $actions = [];

	/**
	 * @var array
	 */
	private $mutations = [];

	/**
	 * @var ServiceDatesHelper
	 */
	private $serviceDatesHelper;

	/**
	 * @var array
	 */
	private $debugTimes = [];

	public function __construct(CombinationGenerator $combination, \Ext_TS_Inquiry $inquiry, string $changeTrigger) {

		$this->combination = $combination;
		$this->inquiry = $inquiry;
		$this->changeTrigger = $changeTrigger;

	}

	public function execute() {

		$this->serviceDatesHelper = new ServiceDatesHelper($this->combination, $this->inquiry);

		foreach ((new ServiceBlockFactory())->makeAll($this->combination, collect()) as $serviceBlock) {
			$serviceBlock->check($this->inquiry, $this->changeTrigger, $this->actions);
		}

//		$this->checkCourses();
		$this->checkAccommodations();
		$this->checkInsurances();
		$this->checkActivities();
//		$this->checkFees();

		$this->mutations[] = [
			'handler' => 'REPLACE_DATA',
			'key' => 'periods',
			'value' => [
				'accommodation_default' => $this->serviceDatesHelper->convertPeriodForJson($this->serviceDatesHelper->getDefaultAccommodationPeriod()),
				'course' => $this->serviceDatesHelper->convertPeriodForJson($this->serviceDatesHelper->getCoursePeriod()),
				'course_and_accommodation' => $this->serviceDatesHelper->convertPeriodForJson($this->serviceDatesHelper->getServicePeriod())
			]
		];

	}

	public function getActions(): array {
		return $this->actions;
	}

	public function getMutations(): array {
		return $this->mutations;
	}

	public function getDebugTimes(): array {
		return $this->debugTimes + ['total' => array_sum($this->debugTimes)];
	}

	private function checkAccommodations() {

		$accommodationBlock = $this->combination->getForm()->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS, true);
		if ($accommodationBlock === null) {
			return;
		}

		$accommodationAvailability = $accommodationBlock->getSetting('availability_start_end');
		[$accommodationDatesMap, $accommodationDates] = $this->serviceDatesHelper->getAccommodationDates();

		$debugTime = microtime(true);

		$journey = $this->inquiry->getJourney();

		// Zeitraume gewählter Unterkünfte überprüfen und ggf. korrigieren
		$accommodations = $journey->getAccommodationsAsObjects();
		foreach ($accommodations as $key => $journeyAccommodation) {

			$dates = $this->serviceDatesHelper->getAccommodationDatesForCategory((int)$journeyAccommodation->accommodation_id, $accommodationDatesMap, $accommodationDates);
			$combinationAvailable = (new AccommodationCombinationRule($journeyAccommodation->getRegistrationFormData(), $this->serviceDatesHelper))->passes(null, null);

			$accommodationDefaultPeriod = $this->serviceDatesHelper->getDefaultAccommodationPeriod($journeyAccommodation->getCategory());

			// Wenn es keine Verfügbarkeitsdaten gibt oder die Kombination in keinem Kurs vorkommt, Unterkunft löschen
			if (
				$dates->isEmpty() ||
				!$combinationAvailable || (
					// Wenn kein Standardzeitraum auf Basis von Kurs (nur wenn UK abhängig von Kurs!)
					$accommodationAvailability !== 'accommodation_start_end' &&
					$accommodationDefaultPeriod === null
				)
			) {

				// Edge Case abfangen (Fehler im Unterkunftsblock darf nicht persistieren)
				$this->mutations[] = [
					'handler' => 'DELETE_SERVER_VALIDATION',
					'pass' => array_map(function ($field) use ($accommodationBlock) {
						return 'services.'.$accommodationBlock->getServiceBlockKey().'.'.$field;
					}, array_keys($journeyAccommodation->getRegistrationFormData()))
				];

				$this->actions[] = [
					'handler' => 'addNotification',
					'key' => 'accommodation_removed',
					'type' => 'danger',
					'message' => $accommodationBlock->getTranslation('serviceRemoved')
				];

				$this->actions[] = [
					'handler' => 'deleteService',
					'type' => $accommodationBlock->getServiceBlockKey(),
					'index' => $key
				];

			} else {

				$from = Carbon::parse($journeyAccommodation->from, 'UTC');
				$until = Carbon::parse($journeyAccommodation->until, 'UTC');

				// Wenn nicht verfügbar: Unterkunft auf Standardzeitraum ändern
				if (
					!(new AccommodationAvailabilityRule('start', $dates))->passes('start', $from) ||
					!(new AccommodationAvailabilityRule('end', $dates))->passes('end', $until)
				) {

					$journeyAccommodation->from = $accommodationDefaultPeriod->getStartDate()->toDateString();
					$journeyAccommodation->until = $accommodationDefaultPeriod->getEndDate()->toDateString();

					$this->actions[] = [
						'handler' => 'addNotification',
						'key' => 'accommodation_changed',
						'type' => 'warning',
						'message' => $accommodationBlock->getTranslation('serviceChanged')
					];

					$this->actions[] = [
						'handler' => 'replaceService',
						'type' => $accommodationBlock->getServiceBlockKey(),
						'index' => $key,
						'service' => $journeyAccommodation->getRegistrationFormData()
					];

				}

			}

		}

		// Wenn Unterkunft abhängig von Kurs: Unterkunftsdaten immer ersetzen
		if ($accommodationAvailability !== 'accommodation_start_end') {
			$this->mutations[] = [
				'handler' => 'REPLACE_DATA',
				'key' => 'accommodation_dates_map',
				'value' => $accommodationDatesMap
			];

			$this->mutations[] = [
				'handler' => 'REPLACE_DATA',
				'key' => 'accommodation_dates',
				'value' => $accommodationDates
			];
		}

		$this->debugTimes['check_accs'] = microtime(true) - $debugTime;

	}

	private function checkInsurances() {

		$debugTime = microtime(true);

		$block = $this->combination->getForm()->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_INSURANCES, true);
		if ($block === null) {
			return;
		}

		$insurances = collect($this->combination->getSchoolData()['insurances']);
		$serviceDates = $this->serviceDatesHelper->getServicePeriod();

		// Zeitraume gewählter Versicherungen überprüfen und ggf. korrigieren
		$journey = $this->inquiry->getJourney();
		foreach ($journey->getInsurancesAsObjects() as $key => $journeyInsurance) {

			$label = Arr::get($insurances->firstWhere('key', $journeyInsurance->insurance_id), 'label', '');

			// Kein Leistungszeitraum = keine Versicherung möglich
			if ($serviceDates === null) {

//				$this->actions[] = [
//					'handler' => 'addNotification',
//					'key' => sprintf('insurance_%d_removed', $journeyInsurance->insurance_id),
//					'type' => 'danger',
//					'message' => str_replace('{name}', $label, $block->getTranslation('serviceRemoved'))
//				];
//
//				$this->actions[] = [
//					'handler' => 'deleteService',
//					'type' => $block->getServiceBlockKey(),
//					'index' => $key
//				];

			} else {

//				$insurance = $journeyInsurance->getInsurance();
				$comparePeriod = $serviceDates->copy();
				$from = new Carbon($journeyInsurance->from, 'UTC');
				$until = new Carbon($journeyInsurance->until, 'UTC');

				// Durch den Starttag der Versicherung kann das Datum vor dem Leistungsbeginn (Kurs/Unterkunft) liegen
//				if ($insurance->payment == \Ext_Thebing_Insurance::TYPE_WEEK) {
//					$from = $comparePeriod->setStartDate($from->startOfWeek((int)\Ext_TC_Util::convertWeekdayToCarbonWeekday($insurance->start_day)));
//				}

				if (
					!$comparePeriod->contains($from) ||
					!$comparePeriod->contains($until)
				) {
					$journeyInsurance->from = $comparePeriod->getStartDate()->toDateString();
					$journeyInsurance->until = $comparePeriod->getEndDate()->toDateString();
					$journeyInsurance->weeks = ceil($comparePeriod->getStartDate()->diffInDays($comparePeriod->getEndDate()) / 7);

					$this->actions[] = [
						'handler' => 'addNotification',
						'key' => sprintf('insurance_%d_changed', $journeyInsurance->insurance_id),
						'type' => 'warning',
						'message' => str_replace('{name}', $label, $block->getTranslation('serviceChanged'))
					];

					$this->actions[] = [
						'handler' => 'replaceService',
						'type' => $block->getServiceBlockKey(),
						'index' => $key,
						'service' => $journeyInsurance->getRegistrationFormData()
					];
				}

			}

		}

		$this->debugTimes['check_ins'] = microtime(true) - $debugTime;

	}

	private function checkActivities() {

		$debugTime = microtime(true);

		$block = $this->combination->getForm()->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_ACTIVITY, false);
		if ($block === null) {
			return;
		}

		$inquiryPeriod = $this->serviceDatesHelper->getServicePeriod();
		$allActivities = collect($this->combination->getSchoolData()['activities']);
		$dates = [];

		// Startdaten für Aktivitäten
		if (
			$block->getSetting('based_on') === 'scheduling' &&
			$inquiryPeriod !== null
		) {

			$dateFormat = new \Ext_Thebing_Gui2_Format_Date('frontend_date_format', $this->combination->getSchool()->id);
			$combinations = (new \TsActivities\Service\ActivityService(AssignmentSource::REGISTRATION_FORM))
				->searchAvailableBlocksForInquiry($this->inquiry, $inquiryPeriod->getIncludedStartDate(), $inquiryPeriod->getIncludedEndDate()->endOfDay());

			foreach ($combinations as $combination) {
				if (
					!$combination->isBookableInFrontend(Carbon::now()) ||
					!in_array(\TsActivities\Dto\ActivityBlockWeekCombination::STATUS_BOOKABLE, $combination->status)
				) {
					continue;
				}

				$label = $combination->getDates()->map(function (\TsActivities\Dto\BlockEvent $event) use ($combination, $dateFormat) {
					return sprintf('%s, %s – %s (%s)', $dateFormat->format($event->start), $event->start->isoFormat('LT'), $event->end->isoFormat('LT'), $event->start->isoFormat('z'));
				});

				$dates[$combination->activity->id][] = [
//					'type' => 'option',
					'key' => $combination->buildKey(),
					'label' => $label,
					'price' =>  $combination->price,
				];
			}

		} else {

			// Basierend auf Verfügbarkeiten
			$dates = $this->serviceDatesHelper->generateActivityServiceDates();

		}

		// Aktivitäten werden immer dynamisch gesetzt, da diese ggf. gar nicht zur Verfügung stehen (im Gegensatz zu allen anderen Service-Blocks)
		$activities = $allActivities->filter(function (array $activityData) use ($dates) {
			// Aktivität abhängig von Kurs: Kurs überhaupt vorhanden?
			$activityAvailable = new ActivityCourseRule($activityData['courses'], ActivityCourseRule::pluckCourseIds($this->inquiry));
			if (!$activityAvailable->passes(null, null)) {
				return false;
			}

			// Keine Startdaten = Aktivität überhaupt nicht verfügbar (es gibt bei immer verfügbar mindestens die vom Kurs)
			if (empty($dates[$activityData['key']])) {
				return false;
			}

			return true;
		});

		// Übermittelte Journey-Aktivitäten überprüfen
		$journey = $this->inquiry->getJourney();
		$services = \TsRegistrationForm\Helper\BuildInquiryHelper::filterJourneyServices($journey->getActivitiesAsObjects());

		foreach ($services as $key => $journeyActivity) {

			$activityData = $activities->firstWhere('key', $journeyActivity->activity_id);
			$label = Arr::get($allActivities->firstWhere('key', $journeyActivity->activity_id), 'label', '');

			$from = new Carbon($journeyActivity->from, 'UTC');
			$until = new Carbon($journeyActivity->until, 'UTC');

			if (
				$inquiryPeriod === null ||
				$activityData === null
			) {

				$this->actions[] = [
					'handler' => 'addNotification',
					'key' => sprintf('activity_%d_removed', $journeyActivity->activity_id),
					'type' => 'danger',
					'message' => str_replace('{name}', $label, $block->getTranslation('service_removed'))
				];

				$this->actions[] = [
					'handler' => 'deleteService',
					'type' => $journeyActivity->transients['block'],
					'index' => $key
				];

			} elseif (
				// Nicht bei basierend auf Planung ausführen, da Periode ggf. zu kurz ist (Akivität nach aktuellem Leistungszeitraum) und additional-Info fehlt
				$block->getSetting('based_on') === 'availability' && (
					!$inquiryPeriod->contains($from) ||
					!$inquiryPeriod->contains($until)
				)
			) {

				$date = reset($dates[$journeyActivity->activity_id]);
				if (!$date) {
					throw new \LogicException('Activity '.$journeyActivity->activity_id.' date does not exist but activity has not been removed.');
				}

				$from = Carbon::parse($date['start']);
				$journeyActivity->from = $from->toDateString();
				$journeyActivity->until = $from->addWeeks($date['max'])->toDateString();
				$journeyActivity->weeks = $date['max'];

				$this->actions[] = [
					'handler' => 'addNotification',
					'key' => sprintf('activity_%d_changed', $journeyActivity->activity_id),
					'type' => 'warning',
					'message' => str_replace('{name}', $label, $block->getTranslation('service_changed'))
				];

				$this->actions[] = [
					'handler' => 'replaceService',
					'type' => $block->getServiceBlockKey(),
					'index' => $journeyActivity->transients['block'],
					'service' => $journeyActivity->getRegistrationFormData()
				];

			}

		}

		$this->mutations[] = [
			'handler' => 'REPLACE_DATA',
			'key' => 'activities',
			'value' => $activities->values()
		];

		$this->mutations[] = [
			'handler' => 'REPLACE_DATA',
			'key' => 'activity_dates',
			'value' => empty($dates) ? new \stdClass() : $dates
		];

		$this->debugTimes['check_act'] = microtime(true) - $debugTime;

	}

//	private function checkFees() {
//
//		$feeBlock = $this->combination->getForm()->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_FEES, false);
//		$fees = collect($this->combination->getSchoolData()['fees']);
//
//		foreach ($this->inquiry->getJourney()->getAdditionalServicesAsObjects() as $journeyFee) {
//
//			// Wird in \TsRegistrationForm\Service\InquiryBuilder::transformFees() gesetzt (oder eben auch nicht)
//			if (!empty($journeyFee->transients['service'])) {
//				continue;
//			}
//
//			$label = Arr::get($fees->firstWhere('key', $journeyFee->additionalservice_id), 'label', '');
//
//			$this->actions[] = [
//				'handler' => 'addNotification',
//				'key' => sprintf('fee_%d_removed', $journeyFee->additionalservice_id),
//				'type' => 'danger',
//				'message' => str_replace('{name}', $label, $feeBlock->getTranslation('service_removed'))
//			];
//
//			$this->actions[] = [
//				'handler' => 'deleteService',
//				'type' => $journeyFee->transients['block'],
//				'index' => $journeyFee->transients['index']
//			];
//
//		}
//
//	}

}