<?php

namespace Tc\Service\Wizard\Structure;

use Core\Factory\ValidatorFactory;
use Gui2\Entity\InfoText;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Tc\Service\Wizard;

class Form
{
	const HEADING = 'heading';
	const SEPARATOR = 'separator';
	const FIELD_INPUT = 'input';
	const FIELD_HIDDEN = 'hidden';
	const FIELD_PASSWORD = 'password';
	const FIELD_COLOR = 'color';
	const FIELD_SELECT = 'select';
	const FIELD_CHECKBOX = 'checkbox';
	const FIELD_UPLOAD = 'upload';
	const FIELD_DATE = 'date';

	private array $fields = [];

	private ?string $dateFormat = null;

	private array $tooltips = [];

	// Tooltips und Fehlermeldungen
	private ?string $gui2Hash = null;
	private string $gui2DataClass = \Ext_Gui2_Data::class;


	public function __construct(private \WDBasic $entity, private ?string $queryParameter = null, private ?string $title = null) {}

	public function setDateFormat(string $dateFormat): static
	{
		$this->dateFormat = $dateFormat;
		return $this;
	}

	public function getDateFormat(): ?string
	{
		return $this->dateFormat;
	}

	public function getDateTimeFormat(): ?string
	{
		return str_replace('%', '', $this->dateFormat);
	}

	public function setGui2Information(string $hash, string $dataClass = null): static
	{
		$this->gui2Hash = $hash;
		if ($dataClass !== null) {
			$this->gui2DataClass = $dataClass;
		}
		if (!empty($this->gui2Hash)) {
			$gui2Tooltips = InfoText::query()
				->where('gui_hash', $this->gui2Hash)
				->where('private', 0)
				->get()
				->mapWithKeys(fn (InfoText $infoText) => [$infoText->field => $infoText->getInfoText(\System::getInterfaceLanguage())])
				->toArray()
			;
			$this->setTooltipValues($gui2Tooltips);
		}

		return $this;
	}

	public function setTooltipValues(array $tooltips): static
	{
		$this->tooltips = array_merge($this->tooltips, $tooltips);

		if (!empty($this->fields)) {
			foreach (array_keys($this->fields) as $fieldKey) {
				if (
					isset($tooltips[$fieldKey]) &&
					!isset($this->fields[$fieldKey]['config']['tooltip'])
				) {
					$this->fields[$fieldKey]['config']['tooltip'] = $tooltips[$fieldKey];
				}
			}
		}

		return $this;
	}

	public function getEntity(): \WDBasic
	{
		return $this->entity;
	}

	public function getTitle(): ?string
	{
		return $this->title;
	}

	public function heading(string $heading, string $size = 'h3'): static
	{
		$this->fields[] = [
			'label' => $heading,
			'type' => self::HEADING,
			'config' => ['size' => $size],
		];
		return $this;
	}

	public function separator(): static
	{
		$this->fields[] = [
			'type' => self::SEPARATOR
		];
		return $this;
	}

	public function addI18N(string $key, string $label, string $fieldType, array $config = []): static
	{
		if(strpos($key, '_') !== false) {
			throw new \RuntimeException('Invalid key "'.$key.'"');
		}

		$languagesKeys = $config['languages'] ?? [];
		$allLanguages = \Factory::executeStatic('Ext_TC_Util', 'getTranslationLanguages');

		foreach (array_values($allLanguages) as $index => $language) {

			if (!empty($languagesKeys) && !in_array($language['iso'], $languagesKeys)) {
				continue;
			}

			$column = $key.'_'.$language['iso'];

			if (false !== $flagIcon = \Ext_TC_Util::getFlagIcon($language['iso'])) {
				$config['addon'] = '<img src="'.$flagIcon.'" data-toggle="tooltip" title="'.$language['name'].'"/>';
			} else {
				$config['addon'] = '<i class="fa fa-question-circle" data-toggle="tooltip" title="'.$language['name'].'"></i>';
			}

			$this->add($column, $label, $fieldType, $config);
			$config['show_label'] = false;
		}
		return $this;
	}

	public function add(string $key, string $label, string $fieldType, array $config = []): static
	{
		if ($fieldType === self::FIELD_COLOR) {
			$config['addon'] = '';
			$config['css_class'] = 'colorpicker-element';
		} else if ($fieldType === self::FIELD_DATE) {

			if ($this->dateFormat === null) {
				throw new \RuntimeException('No date format set for form ['.get_class($this->entity).']');
			}

			$validationRule = 'date_format:'.$this->getDateTimeFormat();

			if (!isset($config['rules'])) {
				$config['rules'] = $validationRule;
			} else {
				$config['rules'] .= '|'.$validationRule;
			}

			$config['addon'] = '';
			$config['css_class'] = 'datepicker-element';
		}

		if (
			isset($this->tooltips[$key]) &&
			!isset($config['tooltip'])
		) {
			$config['tooltip'] = $this->tooltips[$key];
		}

		$this->fields[$key] = [
			'label' => $label,
			'type' => $fieldType,
			'config' => $config,
		];
		return $this;
	}

	public function getFields(): array
	{
		return $this->fields;
	}

	public function getFieldsWithValues(): array
	{
		$fields = [];
		foreach ($this->fields as $key => $field) {
			if (!in_array($field['type'], [self::HEADING, self::SEPARATOR])) {
				$field['value'] = $this->getFieldValue($key, $field);
			}
			$fields[$key] = $field;
		}

		return $fields;
	}

	public function save(Wizard $wizard, Request $request, Step $step): ?MessageBag
	{
		$validationRules = collect($this->fields)
			->mapWithKeys(fn ($field, $key) => [$key => $field['config']['rules'] ?? []]);

		$attributes = collect($this->fields)->mapWithKeys(fn ($field, $key) => [$key => $field['label']]);

		$validator = (new ValidatorFactory($wizard->getLanguageObject()->getLanguage()))
			->make($request->all(), $validationRules->toArray(), [], $attributes->toArray());

		if ($validator->fails()) {
			return $validator->getMessageBag();
		}

		$this->fillEntity($wizard, $request, $step);

		$success = $this->entity->validate(false);

		if (is_array($success)) {
			return $this->toMessageBag($wizard, $success);
		}

		$isNew = !$this->entity->exist();

		$this->entity->save();

		if (
			$isNew && $this->queryParameter &&
			null !== $log = $step->getLog()
		) {
			$step->getParent()->query($this->queryParameter, $this->entity->getId(), false);
			$log->setQueryParameter($this->queryParameter, $this->entity->getId());
			$wizard->writeLog($step, $log);
		}

		return null;
	}

	protected function fillEntity(Wizard $wizard, Request $request, Step $step): void
	{
		foreach (array_keys($this->fields) as $field) {
			if (in_array($this->fields[$field]['type'], [self::HEADING, self::SEPARATOR])) {
				continue;
			}

			$this->setFieldValue($field, $request);
		}

		$step->prepareEntity($wizard, $request, $this->entity);
	}

	protected function getFieldValue(string $key, array $fieldData): mixed
	{
		if ($fieldData['config']['value']) {
			$value = $fieldData['config']['value']($this->entity);
		} else if ($fieldData['type'] === self::FIELD_DATE) {
			if ($this->entity->{$key} !== '0000-00-00') {
				$date = \DateTime::createFromFormat('Y-m-d', $this->entity->{$key});
				$value = ($date) ? $date->format($this->getDateTimeFormat()) : '';
			} else {
				$value = '';
			}
		} else if ($fieldData['config']['i18n_table']) {
			[$fieldName, $languageIso] = $this->splitI18NFieldName($key);

			$values = $this->entity->{$fieldData['config']['i18n_table']};

			foreach ($values as $i18n) {
				if ($i18n['language_iso'] === $languageIso) {
					$value = $i18n[$fieldName] ?? '';
					break;
				}
			}
		} else {
			$value = $this->entity->{$key};
		}

		if ($fieldData['type'] === self::FIELD_SELECT) {
			// Wegen Multiselect immer in ein Array wrappen
			$value = Arr::wrap($value);
		}

		return $value;
	}

	protected function setFieldValue(string $key, Request $request): void
	{
		if (isset($this->fields[$key]['config']['save'])) {
			$this->fields[$key]['config']['save']($this->entity, $request);
		} else if ($this->fields[$key]['type'] === self::FIELD_UPLOAD) {

			if (empty($this->fields[$key]['config']['target'])) {
				throw new \RuntimeException('Missing target directory path for upload [' . $key . ']');
			}

			/* @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
			if (null === $file = $request->files->get($key)) {
				return;
			}

			$file->move($this->fields[$key]['config']['target'], $file->getClientOriginalName());

			$this->entity->{$key} = $file->getClientOriginalName();

			if (isset($this->fields[$key]['config']['post_process'])) {
				$callback = $this->fields[$key]['config']['post_process'];
				$callback($this->entity, $file, $this->fields[$key]['config']);
			}

		} else if ($this->fields[$key]['type'] === self::FIELD_DATE) {

			$date = \DateTime::createFromFormat($this->getDateTimeFormat(), $request->input($key, ''));
			$this->entity->{$key} = $date->format('Y-m-d');

		} else {
			if ($this->fields[$key]['config']['i18n_table']) {
				$existing = $this->entity->{$this->fields[$key]['config']['i18n_table']};

				$value = $request->input($key, '');

				[$fieldName, $languageIso] = $this->splitI18NFieldName($key);
				$found = false;

				foreach ($existing as &$i18n) {
					if ($i18n['language_iso'] === $languageIso) {
						$i18n[$fieldName] = $value;
						$found = true;
						break;
					}
				}

				if (!$found) {
					$existing[] = [
						'language_iso' => $languageIso,
						$fieldName => $value,
					];
				}

				$this->entity->{$this->fields[$key]['config']['i18n_table']} = $existing;
			} else {
				$this->entity->{$key} = $request->input($key, '');
			}
		}
	}

	protected function splitI18NFieldName(string $name): array
	{
		list($fieldName, $languageIso) = explode('_', $name, 2);

		return [$fieldName, $languageIso];
	}

	protected function toMessageBag(Wizard $wizard, array $errorData): MessageBag
	{
		$fallback = function ($error) {
			return call_user_func_array([$this->gui2DataClass, 'convertErrorKeyToMessage'], [$error]);
		};

		$fieldLabels = collect($this->fields)->mapWithKeys(function ($fieldConfig, $field) {
			return [$field => $fieldConfig['label']];
		});

		return $wizard->toMessageBag(
			$errorData,
			$this->getErrorKeyMessages($wizard),
			$fallback,
			$fieldLabels->toArray()
		);

	}

	protected function getErrorKeyMessages(Wizard $wizard): array
	{
		return [];
	}

}