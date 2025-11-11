<?php

namespace TsRegistrationForm\Generator;

use Carbon\Carbon;
use Ext_Thebing_Form_Page_Block;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tc\Service\Language\Frontend as FrontendLanguage;

class FormPagesGenerator {

	/**
	 * @var CombinationGenerator
	 */
	private $combination;

	/**
	 * @var \Ext_Thebing_Form
	 */
	private $form;

	/**
	 * @var \Ext_Thebing_School
	 */
	private $school;

	/**
	 * @var FrontendLanguage
	 */
	private $language;

	/**
	 * @var array
	 */
	private $config;

	/**
	 * @var int
	 */
	private $currentPage = 0;

	/**
	 * @var int
	 */
	private $pageCount;

	/**
	 * @var array
	 */
	private $inputFields = [
		'fields' => [],
		'services' => [],
		'selections' => []
	];

	/**
	 * @var \Illuminate\Support\Collection
	 */
	private $formFields;

	private bool $hasPayment = false;

	public function __construct(CombinationGenerator $combination, array $config) {

		$this->combination = $combination;
		$this->form = $combination->getForm();
		$this->school = $combination->getSchool();
		$this->language = $combination->getLanguage();
		$this->config = $config;

		$formFieldsGenerator = new FormFieldsGenerator();
		$formFieldsGenerator->setFrontendLanguage($this->language);
		$this->formFields = $formFieldsGenerator->generate();

	}

	public function generate() {

		$pages = [];

		$formPages = array_values($this->form->getPages());
		$this->pageCount = count($formPages);

		foreach ($formPages as $index => $page) {
			$pages[] = $this->generatePage($page, $index);
			$this->currentPage++;
		}
		$pages[] = $this->generateConfirmPage();

		return $pages;

	}

	private function generatePage(\Ext_Thebing_Form_Page $page, int $index) {

		$components = [];

		$blocks = array_values($page->getBlocks());
		$this->preparePage($blocks);

		foreach ($blocks as $block) {
			$components[] = $this->getBlockProps($block);
		}

		$props = [
			'key' => 'p' . $page->id,
			'label' => $page->getTitle($this->language->getLanguage()),
			'hide' => false,
			'components' => $components,
			'submit' => null
		];

		if (
			$index === $this->pageCount - 1 //&&
			//!$this->hasPayment
		) {
			$props['submit'] = $page->type;
		}

		return $props;

	}

	private function preparePage(&$blocks) {

		$hasNavSteps = false;
		$hasButtons = false;
		$posNavSteps = 0;
		foreach ($blocks as $key => $block) {
			if ($block->block_id == Ext_Thebing_Form_Page_Block::TYPE_NAV_STEPS) {
				$hasNavSteps = true;
				$posNavSteps = $key;
			}
			if ($block->block_id == Ext_Thebing_Form_Page_Block::TYPE_NAV_BUTTONS) {
				$hasButtons = true;
			}
		}

		// Notifications-Element immer hinzufügen
		$block = new Ext_Thebing_Form_Page_Block();
		$block->block_id = Ext_Thebing_Form_Page_Block::TYPE_NOTIFICATIONS;
		array_splice($blocks, $posNavSteps, 0, [$block]);

		// Navigation ergänzen, wenn keine vorhanden und mehr als eine Seite
		if (
			!$hasNavSteps &&
			$this->pageCount > 1
		) {
			$block = new Ext_Thebing_Form_Page_Block();
			$block->block_id = Ext_Thebing_Form_Page_Block::TYPE_NAV_STEPS;
			array_unshift($blocks, $block);
		}

		// Buttons ergänzen, wenn keine vorhanden
		if (!$hasButtons) {
			$block = new Ext_Thebing_Form_Page_Block();
			$block->block_id = Ext_Thebing_Form_Page_Block::TYPE_NAV_BUTTONS;
			$block->set_align = 'justify';
			$blocks[] = $block;
		}

		$block = new Ext_Thebing_Form_Page_Block();
		$block->block_id = Ext_Thebing_Form_Page_Block::TYPE_HONEYPOT;
		array_unshift($blocks, $block);

	}

	private function getBlockProps(Ext_Thebing_Form_Page_Block $block) {

		if (!isset($this->config['block_type_mapping'][$block->block_id])) {
			throw new \InvalidArgumentException('Unknown block component: ' . $block->block_id);
		}

		$props = $this->config['block_type_mapping'][$block->block_id];
		$component = array_shift($props);

		$props['key'] = $this->getBlockKey($block); // Eindeutiger Key für Vue Reactivity
		$props['component'] = $component;

		switch ($block->block_id) {
			case Ext_Thebing_Form_Page_Block::TYPE_COLUMNS:
				$props['cols'] = $this->generateBlockColumns($block);
				break;
			case Ext_Thebing_Form_Page_Block::TYPE_HEADLINE2:
			case Ext_Thebing_Form_Page_Block::TYPE_HEADLINE3:
				$props['text'] = $block->getTranslation('title', $this->language);
				$this->setBlockDependencies($block, $props);
				break;
			case Ext_Thebing_Form_Page_Block::TYPE_STATIC_TEXT:
				$props['text'] = $block->getBlockText($this->language);
				$this->setBlockDependencies($block, $props);
				break;
			case Ext_Thebing_Form_Page_Block::TYPE_DOWNLOAD:
				$files = $this->form->getDownloadFileList($this->school, $this->language->getLanguage());
				$props['label'] = $block->getTranslation('title', $this->language);
				$props['file'] = basename($files[$block->getInputBlockName()]);
				$props['translations'] = [
					'download' => $this->form->getTranslation('filedownload', $this->language),
				];
				break;
			case Ext_Thebing_Form_Page_Block::TYPE_INPUT:
			case Ext_Thebing_Form_Page_Block::TYPE_SELECT:
			case Ext_Thebing_Form_Page_Block::TYPE_DATE:
			case Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX:
			case Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA:
			case Ext_Thebing_Form_Page_Block::TYPE_MULTISELECT:
			case Ext_Thebing_Form_Page_Block::TYPE_YESNO:
				$this->setBlockInputProps($block, $props);
				$this->setBlockDependencies($block, $props);
				break;
			case Ext_Thebing_Form_Page_Block::TYPE_UPLOAD:
				$this->setBlockInputProps($block, $props);
				$this->setBlockDependencies($block, $props);
				$props['translations'] = [
					'choose' => $this->form->getTranslation('filechoose', $this->language),
					'browse' => $this->form->getTranslation('filebrowse', $this->language),
				];
				break;
			case Ext_Thebing_Form_Page_Block::TYPE_NAV_BUTTONS:
				$props['align'] = $block->set_align;
				$props['translations'] = [
					'back' => $this->form->getTranslation('prevbtn', $this->language),
					'next' => $this->form->getTranslation('nextbtn', $this->language),
					'submit_booking' => $this->form->getTranslation('sendbtn', $this->language),
					'submit_quote' => $this->form->getTranslation('sendquotebtn', $this->language),
				];
				break;
			case Ext_Thebing_Form_Page_Block::TYPE_HONEYPOT:
				$props['html'] = $this->combination->getSpamShield()->generate();
				break;
			case Ext_Thebing_Form_Page_Block::TYPE_PAYMENT:
				$this->setPaymentBlockProps($block, $props);
				$this->setBlockDependencies($block, $props);
				break;
			case Ext_Thebing_Form_Page_Block::TYPE_COURSES:
			case Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS:
			case Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS:
			case Ext_Thebing_Form_Page_Block::TYPE_INSURANCES:
			case Ext_Thebing_Form_Page_Block::TYPE_FEES:
			case Ext_Thebing_Form_Page_Block::TYPE_ACTIVITY:
				$this->setBlockServiceProps($block, $props);
				$this->setBlockDependencies($block, $props);
				break;
			case Ext_Thebing_Form_Page_Block::TYPE_PRICES:
				$priceBlocks = $this->form->getFilteredBlocks($this->form->createFilteredBlocksCallbackType(Ext_Thebing_Form_Page_Block::TYPE_PRICES));
				$block2 = reset($priceBlocks); // Übersetzungen des ersten Blocks verwenden
				$props['translations'] = [
					'summary' => $block2->getTranslation('priceTitle', $this->language),
					'total' => $block2->getTranslation('priceTotal', $this->language),
					'deposit' => $block2->getTranslation('deposit', $this->language),
					'deposit_description' => $block2->getTranslation('depositDescription', $this->language),
					'pay_full_amount' => $block2->getTranslation('pay_full_amount', $this->language)
				];
				break;
		}

		return $props;

	}

	private function getBlockKey(Ext_Thebing_Form_Page_Block $block) {

		// Gleiche Keys, damit Vue das Element nicht neu rendert und Transitions funktionieren
		// Wenn das Element mit Key mehrfach auf einer Seite vorkommt, wird Vue einen Fehler in der Konsole schmeißen
		switch ($block->block_id) {
			case Ext_Thebing_Form_Page_Block::TYPE_NAV_STEPS:
				return 's1';
			case Ext_Thebing_Form_Page_Block::TYPE_NAV_BUTTONS:
				return 's2';
			case Ext_Thebing_Form_Page_Block::TYPE_NOTIFICATIONS:
				return 's3';
			case Ext_Thebing_Form_Page_Block::TYPE_PRICES:
				return 's4';
		}

		return 'b' . $block->id;

	}

	private function generateBlockColumns(Ext_Thebing_Form_Page_Block $block) {

		$cols = [];

		foreach ($block->getAreaWidths() as $iArea => $areaWidth) {

			$components = [];
			$childBlocks = $block->getChildBlocksForArea($iArea);

			foreach ($childBlocks as $childBlock) {
				$components[] = $this->getBlockProps($childBlock);
			}

			$cols[] = [
				'width' => $areaWidth,
				'components' => $components
			];

		}

		return $cols;

	}

	private function setBlockServiceProps(Ext_Thebing_Form_Page_Block $block, array &$props) {

		$props['block'] = $block->getServiceBlockKey();

		$fields = collect($block->getServiceBlockFields());
		$translationKeys = collect($block->getTranslationConfig())->keys();
		$translationKeys = $translationKeys->except(['extra', 'extra_week', 'title_arr', 'title_arr_dep']);

		$props['translations'] = $translationKeys->mapWithKeys(function ($key) use ($block, $fields) {
			$translation = $block->getTranslation($key, $this->language);
//			if ($block->checkIfTranslationIsFieldLabel($key)) {
//				$translation .= ' *';
//			}

			return [Str::snake($key) => $translation];
		});

		$props['translations']['error_required'] = $this->form->getTranslation('errorrequired', $this->language);

		if ($block->block_id == \Ext_Thebing_Form_Page_Block::TYPE_COURSES) {
			if ($block->getSetting('based_on') === 'scheduling') {
				$props['component'] = 'block-service-blocks';
				$props['use-card-footer'] = true;
				$props['filter-component'] = 'course-filter';
				$props['view'] = 'checkbox';
				$props['check-services'] = false;
				$props['translations']['clear_filters'] = $this->language->translate('Clear filters');
			}

			// Kursauswahl als Select oder Blöcke (Radio-Buttons)
			$props['selection'] = $block->getSetting('selection') ?? 'select';
			// Kursabhängigkeiten werden nicht standardmäßig geprüft, da Prüfung in beide Richtungen geht
			$props['check-dependencies'] = !!$block->getSetting('check_dependencies');

			if ($block->getSetting('grouping')) {
				// Kursgruppierung: Kategorie oder Sprache (Levelgroup)
				$props['grouping-type'] = $block->getSetting('grouping');
				// Bei mehr als vier Tabs werden Buttons verwendet (wird vom JS gesteuert)
				$props['grouping-selection'] = $block->getSetting('grouping_selection') ?? 'button';
				// Bei Tabs muss initial ein Tab ausgewählt sein, da das Form ansonsten dysfunktional aussieht
				$props['hide-fields'] = $props['grouping-selection'] === 'button' && $block->getSetting('hide_fields_initially');
			}
		}

		if (in_array($block->block_id, [\Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS, \Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS, \Ext_Thebing_Form_Page_Block::TYPE_INSURANCES, \Ext_Thebing_Form_Page_Block::TYPE_ACTIVITY])) {
			// Datepicker
			$props['translations']['course_start'] = $this->language->translate('Course start');
			$props['translations']['course_end'] = $this->language->translate('Course end');
			$props['translations']['service_start'] = $this->language->translate('Service start');
			$props['translations']['service_end'] = $this->language->translate('Service end');
		}

		if (in_array($block->block_id, [\Ext_Thebing_Form_Page_Block::TYPE_INSURANCES, \Ext_Thebing_Form_Page_Block::TYPE_FEES])) {
			$props['translations']['choose_one'] = $this->form->getTranslation('choose_one', $this->language);
			$props['show-two-columns'] = !!$block->getSetting('show_two_columns');
			$props['view'] = 'checkbox';

			if (
				!$block->required &&
				$block->getSetting('require_selection')
			) {
				$props['view'] = 'selection';
				$this->inputFields['selections'][$block->getServiceBlockKey()] = [
					'page' => $this->currentPage,
					'validation' => ['required']
				];
				$props['translations']['yes'] = $this->language->translate('Yes');
				$props['translations']['no'] = $this->language->translate('No');
			} elseif(
				$block->required &&
				$block->getSetting('require_selection')
			) {
				$props['view'] = 'radio';
			}
		}

		if ($block->block_id == \Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS) {
			$props['extended'] = (bool)$block->getSetting('show_extended_form');
			$props['fields-by-type'] = $block->getSetting('show_extended_form') && !$block->getSetting('show_fields_without_type_check');
			$props['icon'] = $block->getSetting('icon_class');
			if ($block->required && empty($block->getSetting('dependency_type'))) $props['translations']['type'] .= ' *';

			$props['type-options'] = [
				['key' => \Ext_TS_Inquiry_Journey::TRANSFER_MODE_NONE, 'label' => $block->getTranslation('no_transfer', $this->language)],
				['key' => \Ext_TS_Inquiry_Journey::TRANSFER_MODE_ARRIVAL, 'label' => $block->getTranslation('arrival', $this->language)],
				['key' => \Ext_TS_Inquiry_Journey::TRANSFER_MODE_DEPARTURE, 'label' => $block->getTranslation('departure', $this->language)],
				['key' => \Ext_TS_Inquiry_Journey::TRANSFER_MODE_BOTH, 'label' => $block->getTranslation('arrival_departure', $this->language)]
			];

			$props['field-settings'] = [];
			foreach (array_keys(\Ext_TS_Inquiry_Journey_Transfer::REGISTRATION_FORM_FIELDS) as $transferField) {
				$mandatory = ($this->form->isCreatingBooking() || !$props['extended']) && in_array($transferField, ['type', 'locations']);
				$props['field-settings'][$transferField] = [
					'visible' => $block->getSetting('show_field_'.$transferField) || $mandatory,
					'required' =>$block->getSetting('required_field_'.$transferField) || $mandatory
				];
			}
		}

		if ($block->block_id == \Ext_Thebing_Form_Page_Block::TYPE_ACTIVITY) {
			if ($block->getSetting('based_on') === 'scheduling') {
				$props['component'] = 'block-service-options';
				$props['options-key'] = 'activity_dates';
			}
		}

		$rules = [];
		if ($block->required) {
			$rules[] = 'required';
		}

		if (isset($props['max'])) {
			$props['max'] = (int)$block->getSetting('count_max') ?: $props['max'];
		}

		$fieldTypes = [
			'additional_services' => 'array',
			'field_state' => 'object'
		];

		$this->inputFields['services'][$block->getServiceBlockKey()] = [
			'id' => $fields->first(), // Erstes Feld (Kurs-ID usw.) dient als Identifikation für Service-Blöcke (sollte also nur einmalig vorkommen)
			'page' => $this->currentPage, // Damit Validator weiß, welche Page den Fehler enthält
			'actions' => $this->getFieldActions('services', $block->getServiceBlockType().'.$change'),
			'fields' => $fields->mapWithKeys(function ($field) use ($block, $fieldTypes) {
				return [$field => [
					'page' => $this->currentPage,
					'actions' => $this->getFieldActions('services', $block->getServiceBlockType().'.'.$field),
					'type' => Arr::get($fieldTypes, $field, 'scalar')
				]];
			}),
			'validation' => $rules
		];

	}

	private function setBlockInputProps(Ext_Thebing_Form_Page_Block $block, array &$props) {

		$field = collect($this->formFields->firstWhere('key', $block->set_type));
		if ($field->isEmpty()) {
			throw new \RuntimeException('Field "' . $block->set_type . '" does not exist (block ' . $block->id . ')');
		}

		$fieldKey = $block->getSetting('type');

		$props['name'] = $fieldKey;
		$props['label'] = $block->getTranslation('title', $this->language);
		$props['error-message'] = $block->getTranslation('error', $this->language);
		$field['value'] = null; // Werte sollten in generateBooking() gesetzt werden
		$field['page'] = $this->currentPage; // Damit Validator weiß, welche Page den Fehler enthält

		if ($field->has('subtype')) {
			$props['type'] = $field['subtype']; // type=email|tel
		}

		if (!$field->has('validation')) {
			$field->put('validation', []);
		}

		// In setBlockDependencies() wird required ggf. überschrieben
		if ($block->required) {
			$rules = ['required'];
			if ($block->block_id == Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX) {
				$rules[] = 'boolean_true'; // false ist für required auch ein gültiger Wert
			}
			$field->put('validation', array_merge($rules, $field->get('validation')));
			$props['label'] .= ' *';
		}

		if (
			$block->block_id == Ext_Thebing_Form_Page_Block::TYPE_SELECT ||
			$block->block_id == Ext_Thebing_Form_Page_Block::TYPE_MULTISELECT ||
			$block->block_id == Ext_Thebing_Form_Page_Block::TYPE_YESNO
		) {
			if ($block->block_id == Ext_Thebing_Form_Page_Block::TYPE_MULTISELECT) {
				$field['value'] = [];
			}
			$this->setBlockInputSelectProps($block, $props);
		}

		if ($field['validation'] && in_array('before:today', $field['validation'])) {
			// Die Kombination sollte jeden Tag durch einen Cronjob aktualisiert werden,´sofern das funktioniert
			$props['max-date'] = Carbon::yesterday()->toDateString(); // V-Calendar
		}
		if ($field['validation'] && in_array('after:'.Ext_Thebing_Form_Page_Block::VALIDATION_MIN_DATE, $field['validation'])) {
			// Die Kombination sollte jeden Tag durch einen Cronjob aktualisiert werden,´sofern das funktioniert
			$props['min-date'] = Carbon::parse(Ext_Thebing_Form_Page_Block::VALIDATION_MIN_DATE)->toDateString(); // V-Calendar
		}

		$this->addInputField($fieldKey, $field);

	}

	private function setBlockInputSelectProps(Ext_Thebing_Form_Page_Block $block, array &$props) {

		// Options konvertieren
		$options = $block->getSelectOptions($this->school, $this->language, false);
		$props['options'] = collect($options)->transform(function ($label, $key) {
			return compact('key', 'label');
		})->values();

		if ($block->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_SCHOOL) {
			// Values werden in FormDataGenerator::generateBooking() gesetzt
			$props['empty-option'] = false;
		}

		// Select als Radio anzeigen: Props überschrieben
		if ($block->getSetting('select_as_radio')) {
			$props['type'] = 'radio';

			// Abwahl muss möglich sein
			if (!$block->required) {
				$props['options'][] = [
					'key' => 0,
					'label' => $this->language->translate('N/A'),
				];
			}
		}

	}

	/**
	 * Abhängigkeiten pro Block
	 *
	 * @param Ext_Thebing_Form_Page_Block $block
	 * @param array $props
	 */
	private function setBlockDependencies(Ext_Thebing_Form_Page_Block $block, array &$props) {

		// Nur eine Abhängigkeit pro Block
		if (!$block->getSetting('dependency_type')) {
			return;
		}

		$dependencies = [];
		$dependenciesClient = [];

		switch ($block->getSetting('dependency_type')) {
			case Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX:
			case Ext_Thebing_Form_Page_Block::TYPE_YESNO:
				$block2 = Ext_Thebing_Form_Page_Block::getInstance($block->getSetting('dependency_field'));
				if (!$block2->exist()) {
					break;
				}
				switch ($block->getSetting('dependency_type')) {
					case Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX:
						$dependencies = ['required_if:'.$block2->getSetting('type').',true'];
						$dependenciesClient = ['fields.'.$block2->getSetting('type').':fn:sameAsTrue'];
						$props['dependencies'] = $dependenciesClient;
						break;
					case Ext_Thebing_Form_Page_Block::TYPE_YESNO:
						$dependencies = ['required_if:'.$block2->getSetting('type').',yes'];
						$dependenciesClient = ['fields.'.$block2->getSetting('type').':fn:in:yes'];
						$props['dependencies'] = $dependenciesClient;
						break;
				}
				break;
			case Ext_Thebing_Form_Page_Block::TYPE_COURSES:
			case Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS:
				$services = (array)$block->getSetting('dependency_services');
				$key = $block->getSetting('dependency_type') == Ext_Thebing_Form_Page_Block::TYPE_COURSES ? 'course' : 'accommodation';
				if (!empty($services)) {
					// Entweder irgendein Block (any) muss den Wert haben oder ein expliziter Block (courses_123) muss den Wert haben
					$dependencyField = $block->getSetting('dependency_field') ?: 'any';
					if ($dependencyField !== 'any') {
						// Falls Block doch kein Service-Block ist, $any-Fallback (else-Fall)
						$block2 = Ext_Thebing_Form_Page_Block::getInstance((int)$dependencyField);
						$block2Valid = $block2->exist() && $block2->isServiceBlock();
						$field = $block2Valid ? $block2->getServiceBlockKey() : sprintf('%ss_$any', $key); // courses_$any
						$fieldClient = 'services.'.($block2Valid ? $block2->getServiceBlockKey() : sprintf('$any.%ss', $key)); // services.$any.courses
					} else {
						$field = sprintf('%ss_$any', $key); // courses_$any
						$fieldClient = sprintf('services.$any.%ss', $key); // services.$any.courses
					}
					// Das muss auch auf PHP-Seite beachtet werden, da ein Pflichtfeld, das eine Abhängigkeit zu einem expliziten Service-Block hat,
					//   nicht Pflicht sein darf. Bsp.: Upload hängt an zweitem Block mit Prüfung, aber Prüfung kann auch im ersten Block gewählt werden.
					// Außerdem wird hiermit die Regel auf im JS geändert, sonst könnte man niemals das Feld bei Verschachtelung überspringen.
					$dependencies = [sprintf('required_if_service_value:%s,%s,%s', $field, $key === 'accommodation' ? 'accommodation_combination' : $key, join(',', $services))];
					$dependenciesClient = [sprintf('%s:fn:hasAny%s:%s', $fieldClient, ucfirst($key), join(',', $services))]; // hasAnyCourse, hasAnyAccommodation
					$props['dependencies'] = $dependenciesClient;
				}
				break;
			default:
				return;
		}

		// Die Validierung muss required-Felder entsprechend auch ignorieren
		// Hier wird required gegen required_if (Laravel) ausgetauscht, was dann auf Client-Seite zu requiredIfDependency (Vuelidate) wird
		if (
			$block->isInputBlock() ||
			$block->isServiceBlock() ||
			$block->block_id == Ext_Thebing_Form_Page_Block::TYPE_PAYMENT
		) {
			$namespace = $block->isServiceBlock() ? 'services' : 'fields';
			$key = $block->isServiceBlock() ? $block->getServiceBlockKey() : $block->getSetting('type');

			// required austauschen gegen required_if
			$this->replaceRequiredValidation($namespace, $key, $dependencies, $dependenciesClient);

			// Bei Service-Pflichtauswahl sollten die Abhängigkeit auch beachtet werden
			// Da diese Felder nicht sichtbar sind, kann man zwar weiter die Seite wechseln, aber die Seite wird als Fehler markiert
			if ($namespace === 'services') {
				$this->replaceRequiredValidation('selections', $key, $dependencies, $dependenciesClient);
			}
		}

	}

	private function replaceRequiredValidation(string $namespace, string $key, array $dependencies, array $dependenciesClient) {

		if (
			!empty($dependencies) &&
			!empty($this->inputFields[$namespace][$key]['validation']) &&
			in_array('required', $this->inputFields[$namespace][$key]['validation'])
		) {
			// required austauschen gegen required_if
			$validation = $this->inputFields[$namespace][$key]['validation'];
			$validation = array_diff($validation, ['required']); // required entfernen
			$validation = array_merge($dependencies, $validation);
			$this->inputFields[$namespace][$key]['validation'] = $validation;
		}

		// Da die Felder unabhängig von den Komponenten sind ($props), muss das jeweilige Feld auch seine Abhängigkeiten kennen
		if (!empty($dependenciesClient)) {
			$this->inputFields[$namespace][$key]['dependencies_client'] = $dependenciesClient;
		}

	}

	private function generateConfirmPage() {

		// Eigener Typ in BlockStatic, der Inhalt statisch aus State zieht
		$block = [
			'key' => 'b_confirmation',
			'component' => 'block-static',
			'type' => 'confirm'
		];

		$buttons = new Ext_Thebing_Form_Page_Block();
		$buttons->block_id = Ext_Thebing_Form_Page_Block::TYPE_NAV_BUTTONS;
		$buttons->set_align = 'justify';

		return [
			'key' => 'p_confirm',
			'label' => '',
			'hide' => true,
			'components' => [
				$block,
				$this->getBlockProps($buttons)
			],
			'submit' => null
		];

	}

	private function setPaymentBlockProps(Ext_Thebing_Form_Page_Block $block, array &$props) {

		$this->hasPayment = true;

		// Block ist wegen der expliziten Skip-Option immer required
		$required = (bool)$block->required;
		$block->required = true;

		$block->set_type = 'payment';
		$this->setBlockInputProps($block, $props);

		$block->required = $required;

		$props['error-message'] = $this->form->getTranslation('paymenterror2', $this->language);
		$props['translations'] = [
			'retry' => $this->language->translate('Retry'),
			'undo' => $this->language->translate('Undo payment'),
			'payment_locked' => $this->form->getTranslation('paymentlocked', $this->language),
//			'payment_authorized' => $this->form->getTranslation('paymentauthorized', $this->language)
		];

		$this->addInputField('payment_full', ['key' => 'payment_full', 'value' => false, 'internal' => true]);

	}

	private function getFieldActions($type, $field) {

		$actions = Arr::get($this->config, 'field_actions.'.$type.'.'.$field, []);

		// Wenn es keinen Preisblock gibt, dann auch den prices-Request nicht ausführen
		if (
			!$this->config['settings']['has_prices'] &&
			($key = array_search('prices', $actions)) !== false
		) {
			unset($actions[$key]);
		}

		return $actions;

	}

	private function addInputField(string $fieldKey, array|Collection $field) {

		$field['actions'] = $this->getFieldActions('fields', $fieldKey);

		$this->inputFields['fields'][$fieldKey] = $field;

	}

	public function getInputFields(): array {
		return $this->inputFields;
	}

}
