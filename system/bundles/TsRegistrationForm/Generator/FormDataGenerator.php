<?php

namespace TsRegistrationForm\Generator;

use Carbon\Carbon;
use FileManager\Traits\FileManagerTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tc\Service\Language\Frontend as FrontendLanguage;
use TcFrontend\Dto\WidgetPath;
use TsRegistrationForm\Factory\ServiceBlockFactory;
use TsRegistrationForm\Helper\FormValidatorHelper;
use TsRegistrationForm\Helper\ServiceDatesHelper;

class FormDataGenerator {

	/**
	 * @var CombinationGenerator
	 */
	private $combination;

	/**
	 * @var \Ext_Thebing_Form
	 */
	private $form;

	/**
	 * @var array
	 */
	private $config;

	/**
	 * @var \Ext_Thebing_School
	 */
	private $school;

	/**
	 * @var \Ext_TS_Frontend_Combination_Inquiry_Helper_Services
	 */
	private $helper;

	/**
	 * @var array
	 */
	private $settings;

	/**
	 * @var Collection
	 */
	private $schoolData;

	/**
	 * @var array
	 */
	private $widgetPaths = [];

	/**
	 * @var array
	 */
	private $blockServices = [];

	/**
	 * Zusatzgebühren von Kurs/Unterkunft
	 *
	 * @var \Ext_Thebing_School_Additionalcost[]
	 */
	private $additionalServices = [];

	/**
	 * @param CombinationGenerator $combination
	 * @param FrontendLanguage $language
	 */
	public function __construct(CombinationGenerator $combination) {
		$this->combination = $combination;
		$this->form = $combination->getForm();
		$this->school = $combination->getSchool();
		$this->config = (new \Core\Helper\Bundle())->readBundleFile('TsRegistrationForm', 'registration');
	}

	/**
	 * @return array
	 */
	public function generateWidgetData() {

		$this->settings = $this->getSettings();

		return [
			'settings' => &$this->settings,
			'icons' => $this->config['icons'],
			'schools' => []
		];

	}

	/**
	 * @return array
	 * @throws \Exception
	 */
	private function getSettings() {

		if ($this->settings) {
			return $this->settings;
		}

		$schoolProxy = new \Ext_Thebing_School_Proxy(\Ext_Thebing_School::getInstance(reset($this->form->schools)));
		$language = $this->combination->getLanguage()->getLanguage();

		$priceBlocks = $this->form->getFilteredBlocks(function(\Ext_Thebing_Form_Page_Block $oBlock) {
			return $oBlock->block_id == \Ext_Thebing_Form_Page_Block::TYPE_PRICES;
		});

		$this->settings = [
			'key' => $this->combination->getCombination()->key,
			'language' => $language,
			'debug' => (bool)$this->form->ignore_cache,
			'has_prices' => !empty($priceBlocks),
			'tracking_key' => \System::d('ts_registration_form_tracking_key') ?: null,
			'purpose' => $this->form->purpose,
			// Wenn Locale nicht in JS Intl vorhanden ist, wird die Locale vom Browser verwendet
			'datepicker' => ['locale' => Str::replaceFirst('_', '-', $language), 'format' => $schoolProxy->getDateFormat('moment')],
			'translation_error' => $this->form->getTranslation('error', $this->combination->getLanguage()),
			'translation_empty_option' => $this->form->getTranslation('defaultdd', $this->combination->getLanguage()),
			'translation_internal_error' => $this->form->getTranslation('errorinternal', $this->combination->getLanguage()),
			'translation_error_time' => $this->form->getTranslation('error_time', $this->combination->getLanguage()),
			'transfer_force_accommodation_option' => false,
			'has_service_period_blocks' => true,
			'has_booking' => false // Bei bereits vorhandener Buchung (Parameter direkt im Widget-JS) nicht booking-Request ausführen
		];

		// Mapping von Block (courses_123) zu Block-Typ (courses)
		$this->settings['blocks'] = collect($this->form->getFilteredBlocks(function (\Ext_Thebing_Form_Page_Block $block) {
			return $block->isServiceBlock();
		}))->mapWithKeys(function (\Ext_Thebing_Form_Page_Block $block) {
			return [$block->getServiceBlockKey() => $block->getServiceBlockType()];
		});

		$courseBlock = $this->form->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_COURSES, false);

		$accommodationBlock = $this->form->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS, false);
		if ($accommodationBlock !== null) {
			$this->settings['accommodation_availability_start_end'] = $accommodationBlock->getSetting('availability_start_end') ?? 'course_period';
		}

		if (!$courseBlock && !$accommodationBlock) {
			$this->settings['has_service_period_blocks'] = false;
		}

		return $this->settings;

	}

	/**
	 * @return Collection
	 */
	public function generateSchoolData(): Collection {

		$this->schoolData = new Collection();

		$this->helper = new \Ext_TS_Frontend_Combination_Inquiry_Helper_Services($this->form, $this->school, $this->combination->getLanguage()->getLanguage());
		$this->helper->bGenerateAllData = false;

		$this->config['settings'] = $this->getSettings();
		$pageGenerator = new FormPagesGenerator($this->combination, $this->config);
		$this->schoolData->put('pages', $pageGenerator->generate());
		$fields = $pageGenerator->getInputFields();

		foreach ((new ServiceBlockFactory())->makeAll($this->combination, $this->schoolData) as $serviceBlock) {
			$serviceBlock->generateCacheData($this->helper, $this->additionalServices);
		}

		// TODO Alles gegen ServiceBlocks ersetzen
//		$this->generateCourseData();
		$this->generateAccommodationData();
		$this->generateTransferData();
		$this->generateInsuranceData();
		$this->generateFeeData();
		$this->generateActivityData();

		$fields['fields']['token'] = ['key' => 'token', 'value' => $this->combination->getToken(), 'internal' => true];
		$fields['fields']['booking'] = ['key' => 'booking', 'value' => null, 'internal' => true];
		if ($this->settings['tracking_key']) {
			$fields['fields']['tracking_key'] = ['key' => 'tracking_key', 'value' => null, 'internal' => true];
		}

		// Alter für checkAge (Kurse) hinzufügen (wenn nicht bereits vorhanden)
		if (
			!$this->combination->getForm()->isCreatingBooking() &&
			empty($fields['fields'][\Ext_Thebing_Form_Page_Block::SUBTYPE_DATE_BIRTHDATE])
		) {
			// TODO Auf Definition von FormFieldsGenerator umstellen
			$fields['fields'][\Ext_Thebing_Form_Page_Block::SUBTYPE_DATE_BIRTHDATE] = [
				'key' => \Ext_Thebing_Form_Page_Block::SUBTYPE_DATE_BIRTHDATE,
				'type' => \Ext_Thebing_Form_Page_Block::TYPE_DATE,
				'value' => null,
				'mapping' => ['tc_c', 'birthday']
			];
		}

		$validatorRulesGenerator = new FormValidatorHelper(FormValidatorHelper::VALIDATE_CLIENT_ALL, $this->combination, $this->schoolData, $this->getSettings());
		$validatorRulesGenerator->appendServiceSelectionFields($this->blockServices, $fields);
		$validatorRulesGenerator->appendFieldsClientValidators($fields);
		$this->schoolData->put('fields', $fields); // Nicht im Frontend benötigt, aber für Validierung (Frontend+Backend)
		$this->schoolData->put('fields_client', $this->generateClientsFieldsDefinition($fields));

		$currency = \Ext_Thebing_Currency::getInstance($this->school->getCurrency());
		$currency->bThinspaceSign = true;
		$this->schoolData->put('prices', [
			'total' => \Ext_Thebing_Format::Number(0, $currency, $this->school->id, true, 2),
			'items' => []
		]);

		$this->schoolData->put('booking', $this->combination->getBookingGenerator()->createBookingStructure($fields));
		$this->schoolData->put('paths', $this->widgetPaths);

		return $this->schoolData;

	}

	private function generateAccommodationData() {

		$block = $this->form->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS);
		if ($block === null) {
			return;
		}

		$accommodations = new Collection();
		foreach ($this->helper->getAccommodations() as $dto) {

			// Jeder Eintrag entspricht einer Kategorie
			if (!$accommodations->has($dto->oCategory->id)) {

				$accommodations->put($dto->oCategory->id, new Collection([
					'key' => $dto->oCategory->id,
					'label' => $dto->oCategory->getName($this->combination->getLanguage()->getLanguage()),
					'combinations' => new Collection(),
					'description' => $dto->oCategory->{'description_'.$this->combination->getLanguage()->getLanguage()},
					'img' => $this->getFrontendImage('acc', $dto->oCategory)
				]));

			}

			// Alle Kombinationen aus Raum+Verpflegung werden bei der Kategorie hinterlegt
			$combinations = $accommodations->get($dto->oCategory->id)->get('combinations');
			/** @var Collection $combinations */
			if (!$combinations->has($dto->oRoomtype->id . '_' . $dto->oMeal->id)) {
				$combinations->put($dto->oRoomtype->id . '_' . $dto->oMeal->id, [
					'key_room' => $dto->oRoomtype->id,
					'key_board' => $dto->oMeal->id,
					'label_room' => $dto->oRoomtype->getName($this->combination->getLanguage()->getLanguage()),
					'label_board' => $dto->oMeal->getName($this->combination->getLanguage()->getLanguage()),
					'additional_services' => array_column($dto->additionalServices, 'id')
				]);
			}

			foreach ($dto->additionalServices as $additionalService) {
				$this->additionalServices[$additionalService->id] = $additionalService;
			}

		}

		foreach ($accommodations as $accommodationCategory) {
			$accommodationCategory->put('combinations', $accommodationCategory->get('combinations')->values());
		}

		$this->schoolData->put('accommodations', $accommodations->values());

		// Wenn Unterkunftsverfügbarkeit jeder gültige Starttag der Unterkunft ist, werden die Daten in den JSON-Cache gesetzt
		if ($block->getSetting('availability_start_end') === 'accommodation_start_end') {
			$helper = new ServiceDatesHelper($this->combination, new \Ext_TS_Inquiry());
			[$map, $dates] = $helper->generateAccommodationServiceDates('accommodation_start_end', $accommodations);
			$this->schoolData->put('accommodation_dates_map', $map);
			$this->schoolData->put('accommodation_dates', $dates);
		}

	}

	private function generateTransferData() {

		$transferBlock = $this->form->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS);
		if ($transferBlock === null) {
			return;
		}

		$locations = new Collection();
		foreach ($this->helper->getTransfers() as $dto) {
			if (!$locations->has($dto->sFromKey)) {
				$locations->put($dto->sFromKey, [
					'key' => $dto->sFromKey,
					'label' => $dto->sFromLabel,
					'type' => 'origin',
					'locations' => new Collection(),
					'position' => $dto->fromPosition
				]);
			}
			if (!$locations->has($dto->sToKey)) {
				$locations->put($dto->sToKey, [
					'key' => $dto->sToKey,
					'label' => $dto->sToLabel,
					'type' => 'destination',
					'locations' => new Collection(),
					'position' => $dto->toPosition
				]);
			}
			$locations[$dto->sFromKey]['locations']->push($dto->sToKey);
			$locations[$dto->sToKey]['locations']->push($dto->sFromKey);
		}

		$locations = $locations->sortBy('position')->transform(function (array $location) {
			unset($location['position']);
			return $location;
		});

		$this->schoolData->put('transfer_locations', $locations->values());

		$this->blockServices[$transferBlock->getServiceBlockKey()][] = 'transfer';

	}

	private function generateInsuranceData() {

		$block = $this->form->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_INSURANCES);
		if ($block === null) {
			return;
		}

		$insurances = new Collection();
		foreach ($this->helper->getInsurances() as $insurance) {
			$start = Carbon::now('UTC');
//			if ($insurance->payment == \Ext_Thebing_Insurance::TYPE_WEEK) {
//				// Erstes mögliches Startdatum für die Versicherung
//				$start = $start->startOfWeek((int)\Ext_TC_Util::convertWeekdayToCarbonWeekday($insurance->start_day));
//			}

			$insurances[] = [
				'key' => (int)$insurance->id,
				'label' => $insurance->getName($this->combination->getLanguage()->getLanguage()),
				'type' => $insurance->payment == \Ext_Thebing_Insurance::TYPE_WEEK ? 'week' : 'day',
//				'start' => $start->subWeek()->toDateString(),
				'start' => $start->toDateString(),
				'duration' => (int)$this->school->frontend_years_of_bookable_services * 52,
				'description' => $insurance->{'description_'.$this->combination->getLanguage()->getLanguage()},
				'icon' => $insurance->frontend_icon_class
			];
			$this->blockServices[$block->getServiceBlockKey()][] = $insurance->id;
		}

		$this->schoolData->put('insurances', $insurances);

	}

	private function generateFeeData() {

		$feeBlock = $this->form->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_FEES, false);
		if (
			$feeBlock === null &&
			empty($this->additionalServices)
		) {
			return;
		}

		$allFees = array_merge($this->helper->getFees(), $this->additionalServices);
		$fees = new Collection();

		foreach ($allFees as $fee) {

//			$dependencies = [];
//
//			// Semi-automatische Gebühren sind abhängig von der gewählten Leistung
//			if ($fee->charge === 'semi') {
//				if ($fee->type == \Ext_Thebing_School_Additionalcost::TYPE_COURSE) {
//					// IDs filtern wegen Multiselect/All +
//					$allIds = $this->schoolData['courses']->pluck('key');
//					$ids = collect($fee->costs_courses)->intersect($allIds);
//					if ($ids->isEmpty()) {
//						continue; // Bleibt keine Abhängigkeit übrig, macht diese Gebühr keinen Sinn
//					}
//					$dependencies = ['services.$any.courses:fn:hasAnyCourse:'.$ids->join(',')];
//				} elseif ($fee->type == \Ext_Thebing_School_Additionalcost::TYPE_ACCOMMODATION) {
//					// Kombinationen filtern wegen Multiselect/All + (hier kann viel unnötiger Ballast zusammenkommen)
//					$allCombinations = $this->schoolData['accommodations']->reduce(function (Collection $collection, Collection $accommodation) {
//						$accommodation['combinations']->each(function ($combination) use ($collection, $accommodation) {
//							$collection->push(sprintf('%s_%s_%s', $accommodation['key'], $combination['key_room'], $combination['key_board']));
//						});
//						return $collection;
//					}, collect());
//					$combinations = collect($fee->costs_accommodations)->intersect($allCombinations);
//					if ($combinations->isEmpty()) {
//						continue; // Bleibt keine Abhängigkeit übrig, macht diese Gebühr keinen Sinn
//					}
//					$dependencies = ['services.$any.accommodations:fn:hasAnyAccommodation:'.$combinations->join(',')];
//				} else {
//					throw new \DomainException('Unknown semi-automatic fee: '.$fee->id);
//				}
//			}

			$fees[] = [
				'key' => (int)$fee->id,
				'label' => $fee->getName($this->combination->getLanguage()->getLanguage()),
				'description' => $fee->{'description_'.$this->combination->getLanguage()->getLanguage()},
				'icon' => $fee->frontend_icon_class,
				'blocks' => $this->mapServiceResourceBlocks($fee),
//				'type' => $fee->type == \Ext_Thebing_School_Additionalcost::TYPE_GENERAL ? 'general' : 'service'
//				'dependencies' => $dependencies
			];

		}

		$fees = $fees->sortBy('label')->values();

		$this->schoolData->put('fees', $fees);

	}

	private function generateActivityData() {

		$block = $this->form->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_ACTIVITY, false);
		if ($block === null) {
			return;
		}

		$activities = new Collection();
		foreach ($this->helper->getActivities() as $activity) {

			// Aktivität abhängig von Kursen
			$courses = new Collection();
			if ($activity->needsCourses()) {
				$schoolCourses = $this->schoolData->get('courses'); /** @var Collection $schoolCourses */
				$courses = $courses->merge($activity->getSchoolSettings())
					->filter(function (\TsActivities\Entity\Activity\ActivitySchool $allocation) {
						return $allocation->school_id == $this->school->id;
					})
					->map(function (\TsActivities\Entity\Activity\ActivitySchool $allocation) {
						return $allocation->courses;
					})
					->flatten()
					->map(function ($courseId) {
						return (int)$courseId;
					})
					->filter(function ($courseId) use ($schoolCourses) {
						// Keine Kurse -> z.B. reines Aktivitäten-Form
						if($schoolCourses->isEmpty()) {
							return true;
						}
						// Kurs auch im Form verfügbar?
						return $schoolCourses->firstWhere('key', $courseId) !== null;
					});

				// Wenn es keine Kurse (Pflichtfeld) gibt, läuft wohl irgendwas in den Einstellungen schief
				if ($courses->isEmpty()) {
					continue;
				}
			}

			$activities[] = [
				'key' => (int)$activity->id,
				'label' => $activity->getName($this->combination->getLanguage()->getLanguage()),
				'type' => $activity->billing_period === 'payment_per_block' ? 'block' : 'week',
				'mode' => $block->getSetting('based_on'), // availability|scheduling
				'description' => $activity->getI18NName('ts_act_i18n', 'description_short', $this->combination->getLanguage()->getLanguage()),
				'icon' => $activity->frontend_icon_class,
				'img' => $this->getFrontendImage('act', $activity),
				'blocks' => $this->mapServiceResourceBlocks($activity),
				'courses' => $courses->toArray()
			];

		}

		// Aktivitäten werden in CombinationGenerator::getWidgetData() initial rausgeworfen und kommen immer dynamisch über ServiceMutationHelper!
		$this->schoolData->put('activities', $activities);

	}

	/**
	 * @param string $key
	 * @param \WDBasic|FileManagerTrait $entity
	 * @return string|null
	 */
	private function getFrontendImage(string $key, \WDBasic $entity): ?string {

		if (!in_array(FileManagerTrait::class, class_uses($entity))) {
			throw new \LogicException('Wrong entity for getFrontendImage: '.get_class($entity));
		}

		$image = $entity->getFirstFile('Frontend-Image');
		$path = null;

		if ($image && $image->getSize() < 100*1024) {
			// Zu große Bilder einfach ignorieren, da die Kunden es sonst nicht lernen
			// TODO Soll gegen Imgbuilder ersetzt werden
			$image->checkPublicFile();
			$path = 'path:'.$key.'_'.$entity->id;

			// Lokal: https://CUSTOMER.fidelo.com/storage/public/filemanager/ts_accommodation_category/3_625-200x300.jpg
			// Extern: https://proxy.fidelo.com/app/registration-form/1.0/:CUSTOMER/img_acc/3_625-200x300.jpg
			$this->widgetPaths[$key.'_'.$entity->id] = new WidgetPath($image->getPublicPath(false), $image->file, 'registration-form:img_'.$key);
		}

		return $path;

	}

	/**
	 * @param \WDBasic $entity
	 * @return array|string[]
	 */
	private function mapServiceResourceBlocks(\WDBasic $entity): array {

		return array_map(function (\Ext_Thebing_Form_Page_Block $block) use ($entity) {
			$this->blockServices[$block->getServiceBlockKey()][] = $entity->id;
			return $block->getServiceBlockKey();
		}, (array)$entity->transients['blocks']);

	}

	/**
	 * $fields vorbereiten für Übergabe ans JS
	 *
	 * @param array $fields
	 * @return array
	 */
	private function generateClientsFieldsDefinition(array $fields): array {

		return collect($fields)->map(function ($fields) {
			return collect($fields)->map(function ($field) {
				$mapField = function ($field) {
					$field = collect($field);
					$field->put('validation', $field->get('validation_client', []));
					$field->put('dependencies', $field->get('dependencies_client', []));
					return $field->only('id', 'page', 'actions', 'validation', 'fields', 'dependencies', 'type');
				};
				if ($field['fields']) { // services
					$field['fields'] = $field['fields']->map($mapField);
				}
				return $mapField($field);
			});
		})->toArray();

	}

}
