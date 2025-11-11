<?php

namespace TsRegistrationForm\Helper;

use Core\Validator\Rules\DateIn;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use TsFrontend\Exceptions\PaymentError;
use TsFrontend\Helper\PaymentMethodsHelper;
use TsRegistrationForm\Factory\ServiceBlockFactory;
use TsRegistrationForm\Generator\CombinationGenerator;
use TsRegistrationForm\Service\InquiryBuilder;
use TsRegistrationForm\Validation\Rules\AccommodationAvailabilityRule;
use TsRegistrationForm\Validation\Rules\AccommodationCombinationRule;
use TsRegistrationForm\Validation\Rules\ActivityCourseRule;

class FormValidatorHelper {

	/**
	 * Client: Vuelidate-Validierung
	 */
	public const VALIDATE_CLIENT_ALL = 1;

	/**
	 * Server-Validierung (nicht alles)
	 */
	public const VALIDATE_SERVER = 2;

	/**
	 * Server-Validierung: Service-Date-Validierung (damit ohne diesen Flag ServiceMutationHelper Datumsangaben anpassen kann)
	 */
	public const VALIDATE_SERVER_ALL = 4;

	/**
	 * Server-Validierung: Payment
	 */
	public const VALIDATE_SERVER_PAYMENT = 8;

	/**
	 * @var string
	 */
	private $level;

	/**
	 * @var CombinationGenerator
	 */
	private $combination;

	/**
	 * @var Collection
	 */
	private $schoolData;

	/**
	 * @var array 
	 */
	private $settings;

	/**
	 * @var ServiceDatesHelper
	 */
	private $helperServiceDates;

	public function __construct(int $level, CombinationGenerator $combination, Collection $schoolData, array $settings) {

		if ($level & self::VALIDATE_CLIENT_ALL && $level !== self::VALIDATE_CLIENT_ALL) {
			throw new \InvalidArgumentException('VALIDATE_CLIENT_ALL level is exclusive.');
		}

		$this->level = $level;
		$this->combination = $combination;
		$this->schoolData = $schoolData;
		$this->settings = $settings;

	}

	/**
	 * @return int BIT
	 */
	public function getLevel(): int {
		return $this->level;
	}

	public function setServiceDatesHelper(ServiceDatesHelper $helper) {
		$this->helperServiceDates = $helper;
	}

	public function getAccommodationRules(array $accommodation = null): array {

		$accommodationBlock = $this->combination->getForm()->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS);

		if ($this->level === self::VALIDATE_CLIENT_ALL) {
			return [
				// Da die Unterkunft immer angezeigt wird, muss das so gelöst werden
				//  - Vuelidate validiert den Eintrag nämlich dann, sobald dieser vorhanden ist
				'accommodation' => $accommodationBlock->required ? ['fn:required'] : [],
				'roomtype' => ['fn:requiredIf:requiredIfAccommodation'],
				'board' => ['fn:requiredIf:requiredIfAccommodation'],
				'start' => ['fn:requiredIf:requiredIfAccommodation'],
				'end' => ['fn:requiredIf:requiredIfAccommodation'],
			];
		}

		$ruleAccommodationIds = Rule::in($this->schoolData->get('accommodations')->pluck('key'));

		$rules = [
			'accommodation' => [$accommodationBlock->required ? 'required' : 'nullable', 'integer', $ruleAccommodationIds],
			'roomtype' => ['required_with:accommodation', 'integer'],
			'board' => ['required_with:accommodation', 'integer'],
			'start' => ['required_with:accommodation', 'date', 'before:end'],
			'end' => ['required_with:accommodation', 'date', 'after:start'],
		];

		if ($accommodation !== null) {
			$combinations = new Collection($accommodation['combinations']);
			$rules['roomtype'][] = Rule::in($combinations->pluck('key_room'));
			$rules['board'][] = Rule::in($combinations->pluck('key_board'));
		}

		if (
			$this->level & self::VALIDATE_SERVER_ALL &&
			$accommodation !== null &&
			$this->helperServiceDates !== null
		) {
			// In Kursen eingestellte Unterkunftskombinationen überprüfen
			// Verwende start, da es accommodation nicht als sichtbares Feld gibt (Fehleranzeige)
			$rules['start'][] = new AccommodationCombinationRule($accommodation['request_accommodation'], $this->helperServiceDates);

			// Unterkunftsverfügbarkeit prüfen (Start und Ende verfügbar)
			[$map, $allDates] = $this->helperServiceDates->getAccommodationDates();
			$dates = $this->helperServiceDates->getAccommodationDatesForCategory($accommodation['request_accommodation']['accommodation'], $map, $allDates);
			$rules['start'][] = new AccommodationAvailabilityRule('start', $dates);
			$rules['end'][] = new AccommodationAvailabilityRule('end', $dates);
		}

		return $rules;

	}

	public function getTransferRules(int $tranfsferMode = null, int $transferType = null): array {

		$transferBlock = $this->combination->getForm()->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS);

		if ($this->level === self::VALIDATE_CLIENT_ALL) {
			return [
				'origin' => ['fn:requiredIf:requiredIfTransfer'],
				'destination' => ['fn:requiredIf:requiredIfTransfer'],
				'date' => $transferBlock->getSetting('show_extended_form') ? ['fn:requiredIf:requiredIfTransfer'] : [],
				'time' => ['fn:time'],
				'mode' => $transferBlock->required && $transferBlock->getSetting('show_field_type') && empty($transferBlock->getSetting('dependency_type')) ? ['fn:requiredIf:requiredIfTransferHasLocations'] : []
			];
		}

		$locations = collect($this->schoolData->get('transfer_locations'));
		$ruleTransferOrigins = Rule::in($locations->where('type', 'origin')->pluck('key'));
		$ruleTransferDestinations = Rule::in($locations->where('type', 'destination')->pluck('key'));

		if ($transferType === \Ext_TS_Inquiry_Journey_Transfer::TYPE_DEPARTURE) {
			$ruleTransferOriginsOriginal = $ruleTransferOrigins;
			$ruleTransferOrigins = $ruleTransferDestinations;
			$ruleTransferDestinations = $ruleTransferOriginsOriginal;
		}

		$booked = \Ext_TS_Inquiry_Journey_Transfer::checkIfBooked($transferType, $tranfsferMode);

		return [
			'origin' => [!$booked ? 'nullable' : '', $ruleTransferOrigins],
			'destination' => [!$booked ? 'nullable' : '', $ruleTransferDestinations],
			'date' => [!$booked ? 'nullable' : '', 'date'],
			'time' => ['nullable', 'date_format:"H:i"'],
			'mode' => $transferBlock->required && $transferBlock->getSetting('show_field_type') ? ['required'] : []
		];

	}

	public function getInsuranceRules(array $insurance = null): array {

		if ($this->level === self::VALIDATE_CLIENT_ALL) {
			return [
				'insurance' => ['fn:required'],
				'start' => ['fn:required'],
				'duration' => ['fn:requiredIf:requiredIfInsuranceIsWeekly'],
				'end' => ['fn:requiredIf:requiredIfInsuranceIsDaily'],
			];
		}

		$ruleInsuranceIds = Rule::in($this->schoolData->get('insurances')->pluck('key'));

		$rules = [
			'insurance' => ['required', 'integer', $ruleInsuranceIds],
			'start' => ['required', 'date'],
			'end' => ['required', 'date'],
		];

		if ($insurance !== null && $insurance['type'] === 'week') {
			unset($rules['end']);
			$rules['duration'] = ['required', 'integer', 'between:1,156'];
		}

		// Aktuell komplett rausgenommen, weil das nun komplizierter (wie immer) geworden ist und für den Bereich kaum Relevanz hat
//		if (
//			$this->level & self::VALIDATE_SERVER_ALL &&
//			$this->helperServiceDates !== null
//		) {
//			$dates = $this->helperServiceDates->getServicePeriod();
//			if ($dates === null) {
//				throw new \LogicException('No service period available for insurance');
//			}
//
//			$rules['start'][] = 'after_or_equal:'.$dates->getStartDate()->toISOString();
//			$rules['end'][] = 'before_or_equal:'.$dates->getEndDate()->toISOString();
//		}

		return $rules;

	}

	public function getFeeRules(): array {

		if ($this->level === self::VALIDATE_CLIENT_ALL) {
			return [
				'fee' => ['fn:required']
			];
		}

		$ruleFeeIds = Rule::in($this->schoolData->get('fees')->pluck('key'));

		return [
			'fee' => ['required', 'integer', $ruleFeeIds]
		];

	}

	public function getActivityRules(array $activity = null): array {

		if ($this->level === self::VALIDATE_CLIENT_ALL) {
			return [
				'activity' => ['fn:required'],
				'start' => ['fn:requiredIf:requiredIfActivityIsWeekly'],
				'duration' => ['fn:requiredIf:requiredIfActivityIsWeekly'],
				'units' => ['fn:requiredIf:requiredIfActivityIsBlock', 'fn:integer', 'fn:between:1:156']
			];
		}

		$activityIds = Rule::in($this->schoolData->get('activities')->pluck('key'));

		$rules = [
			'activity' => ['required', 'integer', $activityIds],
			'start' => ['required', 'date'],
			'duration' => ['required', 'integer', 'between:1,156'],
			'units' => ['required', 'integer', 'between:1,156']
		];

		if ($activity !== null) {
			if ($activity['type'] === 'week') {
				unset($rules['units']);
			} else {
				unset($rules['start']);
				unset($rules['duration']);
			}

			if (
				$this->level & self::VALIDATE_SERVER_ALL &&
				$activity['mode'] === 'availability'
			) {
				// start verwenden, da activity als Feld nicht angezeigt wird
				$rules['start'][] = new ActivityCourseRule($activity['courses'], $activity['selected_courses']);

				// Bei wöchentlichen Aktivtäten prüfen, ob zumindest das Startdatum vorkommt
				if ($activity['type'] === 'week') {
					$dates = Arr::get($this->helperServiceDates->generateActivityServiceDates(), $activity['key'], []);
					$rules['start'][] = new DateIn(Arr::pluck($dates, 'start'));
				}
			}
		}

		return $rules;

	}

	public function getFieldRules($fields = null, $namespace = 'fields'): array {

		$rules = [];
		if ($fields === null) {
			$fields = $this->schoolData->get('fields');
		}

		foreach ($fields[$namespace] as $name => $field) {
			$rules[$name] = [];

			// Pfichtfeld oder Abhängigkeit: Bei Pfichtfeld+Abhängigkeit das Feld auf nullable setzen (s.u.)
			$dependency = false;
			$required = collect($field['validation'])->some(function (string $rule) use (&$dependency) {
				if (Str::startsWith($rule, 'required_if')) { // required_if + required_if_service_value
					$dependency = true;
				}
				return $rule === 'required' || $dependency;
			});

			if ($this->level === self::VALIDATE_CLIENT_ALL) {
				// Achtung: Wann auch immer hier etwas hinzugefügt werden sollte, müssen Funktionen mit den Abhängigkeiten funktionieren!
				// Bsp.: sameAsTrue prüft die Abhängigkeiten, andere Funktionen aber nicht (wo das eben nie benötigt wurde)
				if ($required) {
					// Abhängigkeiten stehen in einem anderen Array (dependencies_client)
					$rules[$name][] = $dependency ? 'fn:requiredIfDependency' : 'fn:required';
					if ($field['type'] == \Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX) {
						$rules[$name][] = 'fn:sameAsTrue'; // JS Quirk: false ist auch Vuelidate.required() === true
					}
				}
			} else {

				$fieldRules = Arr::get($field, 'validation', []);

				// Interne Felder müssen immer manuell validiert werden, da es ansonsten eine zirkuläre Abhängigkeit geben kann (z.B. payment)
				if ($field['internal']) {
					$required = false;
					Arr::pull($fieldRules, array_search('required', $fieldRules));
				}

				// https://laravel.com/docs/7.x/validation#a-note-on-optional-fields
				if (!$required) {
					$rules[$name][] = 'nullable';
				}

				// required oder required_if (Abhängigkeiten) werden direkt so an Laravel weitergegeben
				$rules[$name] = array_merge($rules[$name], $fieldRules);

				// required_if mit einer anderen Regel (z.B. email_mx) funktioniert auch nicht mit ggf. optionalen Feldern
				// Laravel-Logik: Wenn required_if zutrifft, wird nullable aber ignoriert
				if ($required && $dependency) {
					$rules[$name][] = 'nullable';
				}
			}
		}

		return $rules;

	}

	/**
	 * @deprecated
	 * @see \Ext_Thebing_Form_Page_Block::getServiceBlockType()
	 * @param string $type
	 * @return array
	 */
	private function getServiceRules(string $type): array {

		switch($type) {
//			case 'courses':
//				return $this->getCourseRules();
			case 'accommodations':
				return $this->getAccommodationRules();
			case 'transfers':
				return $this->getTransferRules();
			case 'insurances':
				return $this->getInsuranceRules();
			case 'fees':
				return $this->getFeeRules();
			case 'activities':
				return $this->getActivityRules();
			default:
				throw new \InvalidArgumentException('Unknown type '.$type);
		}

	}

	/**
	 * selections-Felder pro vorhandener Leistung pro Block erweitern
	 *
	 * Jede Leistung mit ja/nein-Radio-Buttons benötigt ein komplett eigenes Feld.
	 *
	 * @param array $blockServices
	 * @param array $fields
	 */
	public function appendServiceSelectionFields(array $blockServices, array &$fields) {

		$fields['selections'] = collect($fields['selections'])->mapWithKeys(function ($props, $key) use ($blockServices) {
			return collect($blockServices[$key])->mapWithKeys(function ($serviceKey) use ($props, $key) {
				return [$key.'_'.$serviceKey => $props];
			});
		})->toArray();

	}

	/**
	 * Validator-Regeln für Vuelidate vorbereiten
	 *
	 * @param array $fields
	 */
	public function appendFieldsClientValidators(array &$fields) {

		// $namespace = fields, services, selections
		foreach (array_keys($fields) as $namespace) {
			$rules = $this->getFieldRules($fields, $namespace);
			$fields[$namespace] = collect($fields[$namespace])->map(function ($fieldData, $field) use ($namespace, $rules) {
				$rules[$field]['remote'] = 'fn:remote:'.$namespace.'.'.$field;
				$fieldData['validation_client'] = $this->convertClientValidatorRules($rules[$field]);
				return $fieldData;
			});
		}

		// Das gleiche wie oben, nur für alle Felder pro Service-Block
		foreach ($fields['services'] as $key => $service) {
			[$type] = explode('_', $key, 2);

			if (isset(ServiceBlockFactory::CLASSES[$type])) {
				$rules = (new ServiceBlockFactory())->make($type, $this->combination, $this->schoolData)->buildValidationRules($this->level);
			} else {
				$rules = $this->getServiceRules($type);
			}

			$service['fields']->transform(function ($fieldData, $field) use ($key, $rules) {
				$rules[$field]['remote'] = 'fn:remote:services.'.$key.'.'.$field;
				$fieldData['validation_client'] = $this->convertClientValidatorRules($rules[$field]);
				return $fieldData;
			});
		}

	}

	/**
	 * Rule als Key setzen (wird von Vuelidate irgendwie so benötigt)
	 *
	 * @param array $rules
	 * @return array
	 */
	private function convertClientValidatorRules(array $rules): array {

		return collect($rules)->mapWithKeys(function ($rule) {
			[, $key] = explode(':', $rule);
			return [$key => $rule];
		})->toArray();

	}

	public function createPaymentValidatorHook(): \Closure {

		return function (Validator $validator) {

			if (!($this->level & self::VALIDATE_SERVER_PAYMENT)) {
				return;
			}

			$block = $this->combination->getForm()->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_PAYMENT);
			if ($block === null) {
				// Wenn der Block da ist, ist dieser sowieso ein Pflichtfeld (bei optional muss skip ausgewählt werden)
				return;
			}

			$providers = collect($block->getSetting('provider'))->push('skip');
			$input = Arr::get($validator->getData(), 'payment') ?? [];

			$paymentMethodsHelper = new PaymentMethodsHelper($this->combination);
			$paymentMethodsHelper->generatePaymentProviders($providers, $this->combination->getSchool());
			$paymentMethodsHelper->setMethod(Arr::get($input, 'method'));
			$handler = $paymentMethodsHelper->createPaymentHandler(); /** @var \TsFrontend\Interfaces\PaymentProvider\RegistrationForm $handler */

			try {
				if (!$handler->checkPayment(collect($input))) {
					throw (new PaymentError('checkPayment returned non valid state'))->setAdditional($input);
				}
			} catch (PaymentError $e) {
				$msg = $this->combination->getForm()->getTranslation('errorinternal', $this->combination->getLanguage()).' '.$e->getMessage();
				$this->combination->log('createPaymentValidatorHook failed', [$e->getMessage(), $e->getAdditional(), $validator->getData()]);
				$validator->errors()->add('payment', $msg);
			}

		};

	}

	public function appendServiceValueExtension(Validator $validator, InquiryBuilder $builder) {

		// required_if_service_value:courses,course,1,2,3
		// ImplicitExtension bedeutet: Wird immer ausgeführt
		$validator->addImplicitExtension('required_if_service_value', function($attribute, $value, array $parameters, Validator $validator) use ($builder) {

			/**
			 * @param string $attribute Eigenliches Feld zum Validieren
			 * @param mixed $value Eigenliches Feld zum Validieren
			 * @param array $parameters Block (courses_123, courses_$any), Service-Feld, ...Values
			 * @param Validator $validator
			 */

			$blockKey = array_shift($parameters); // courses_123 | courses_$any
			[$type] = explode('_', $blockKey); // courses
			$field = array_shift($parameters); // course (id)

			// Alle Services des Typ (courses) aus Request holen und ggf. filtern
			$values = $builder
				->getServiceRequestData($type)
				->filter(function ($service) use ($blockKey) {
					// Abhängigkeit auf irgendeinen Block oder expliziten Block
					return Str::endsWith($blockKey, '$any') || $blockKey === $service['block'];
				})
				->map(function (array $service) use ($field) {
					if ($field === 'accommodation_combination') {
						return sprintf('%s_%s_%s', $service['accommodation'], $service['roomtype'], $service['board']);
					}
					return $service[$field];
				});

			$required = $values->some(function ($value) use ($parameters) {
				return in_array($value, $parameters);
			});

			if (!$required) {
				// Abhängigkeit nicht erfüllt: Kein Pflichtfeld, kein Wert benötigt
				return true;
			}

			// Eigentliche Prüfung auf required
			return $validator->validateRequired($attribute, $value);

		});

	}

}
