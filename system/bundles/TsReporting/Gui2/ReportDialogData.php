<?php

namespace TsReporting\Gui2;

use Core\Exception\Entity\ValidationException;
use Gui2\Dialog\VueDialogData;
use TsReporting\Generator\Columns\AbstractColumn;
use TsReporting\Generator\Groupings\AbstractGrouping;

/**
 * @property \TsReporting\Entity\Report $_oWDBasic
 */
class ReportDialogData extends VueDialogData
{
	/**
	 * @TODO Besser umsetzen
	 */
	public static function createDialog(\Ext_Gui2 $gui): \Ext_Gui2_Dialog
	{
		$dialog = $gui->createDialog($gui->t('Auswertung "{name}" bearbeiten'), $gui->t('Neue Auswertung anlegen'));
		$dialog->setDataObject(self::class);
		$dialog->save_as_new_button = true;

		$dialog->createSaveField('input', [
			'db_column' => 'name',
			'required' => true,
		]);

		$dialog->createSaveField('input', [
			'db_column' => 'base',
			'required' => true,
		]);

		$dialog->createSaveField('input', [
			'db_column' => 'description',
			'required' => true,
		]);

		$dialog->createSaveField('input', [
			'db_column' => 'visualization',
			'required' => true,
		]);

		$dialog->createSaveField('checkbox', [
			'db_column' => 'visualization_grand_totals',
			'required' => true,
		]);

		$dialog->createSaveField('checkbox', [
			'db_column' => 'visualization_row_totals',
			'required' => true,
		]);

		$container = $dialog->createJoinedObjectContainer('groupings');

		$container->createRow($gui->t('Gruppierung'), 'select', [
			'db_alias' => 'groupings',
			'db_column' => 'object',
			'select_options' => []
		]);

		$container->createRow('', 'input', [
			'db_alias' => 'groupings',
			'db_column' => 'config'
		]);

		$container->createRow('', 'input', [
			'db_alias' => 'groupings',
			'db_column' => 'position'
		]);

		$container = $dialog->createJoinedObjectContainer('columns');

		$container->createRow($gui->t('Spalte'), 'select', [
			'db_alias' => 'columns',
			'db_column' => 'object',
			'select_options' => []
		]);

		$container->createRow('', 'input', [
			'db_alias' => 'columns',
			'db_column' => 'config'
		]);

		$container->createRow('', 'input', [
			'db_alias' => 'columns',
			'db_column' => 'position'
		]);

//		$container = $dialog->createJoinedObjectContainer('filters');
//
//		$container->createRow($gui->t('Filter'), 'select', [
//			'db_alias' => 'filters',
//			'db_column' => 'object',
//			'select_options' => []
//		]);
//
//		$container->createRow('', 'input', [
//			'db_alias' => 'filters',
//			'db_column' => 'config'
//		]);

		return $dialog;
	}

	public function getHtml($sAction, $aSelectedIds, $sAdditional = false)
	{
		$gui = $this->getGui();
		$data = parent::getHtml($sAction, $aSelectedIds, $sAdditional);

		$config = (new \Core\Helper\Bundle())->readBundleFile('TsReporting', 'definitions');

		$definitions = [];
		$bases = array_map(fn(string $class) => ['key' => $class, 'label' => (new $class())->getTitle()], $config['bases']);
		$groupings = $this->generateColumOptions($config['groupings'], $definitions);
		$columns = $this->generateColumOptions($config['columns'], $definitions);

		$data['html'] = '0';

		$groupingConfig = [
			[
				'component' => 'SelectField',
				'key' => 'object',
				'label' => $gui->t('Gruppierung'),
				'options' => $groupings,
				'required' => true
			],
			[
				'component' => 'TsReporting.GuiDialogColumnOptions',
				'key' => 'config',
				'definitions' => $definitions,
				'nested-label' => $gui->t('Kind-Gruppierungen'),
				'first-layer' => true,
				'label-subtotals' => $gui->t('Zwischensummen')
			]
		];

		// Einstellungen für aggregierte Gruppierung
		$groupingConfigNested = $groupingConfig;
		$groupingConfigNested[0]['options'] = collect($groupingConfigNested[0]['options'])
			->reject(fn(array $option) => $option['key'] === \TsReporting\Generator\Groupings\Aggregated::class)
			->values();
		$groupingConfigNested[1]['first-layer'] = false;
		$groupingConfigNested[1]['definitions'] = array_map(function (array $definitions) {
			return array_values(array_filter($definitions, fn(array $definition) => $definition['key'] !== 'pivot'));
		}, $groupingConfigNested[1]['definitions']);
		$groupingConfig[1]['nested-components'] = $groupingConfigNested;

		$data['vue'] = [
			'components' => [
				[
					'component' => 'InputField',
					'key' => 'name',
					'label' => $gui->t('Name'),
					'required' => true
				],
				[
					'component' => 'SelectField',
					'key' => 'base',
					'label' => $gui->t('Basis'),
					'options' => $bases,
					'required' => true
				],
				[
					'component' => 'TextareaField',
					'key' => 'description',
					'label' => $gui->t('Beschreibung')
				],
				[
					'component' => 'ContentHeading',
					'tag' => 'h4',
					'content' => $gui->t('Visualisierung')
				],
				[
					'component' => 'SelectField',
					'key' => 'visualization',
					'label' => $gui->t('Visualisierung'),
					'options' => collect(\TsReporting\Gui2\ReportData::getVisualizationOptions())
						->map(fn(string $label, string $key) => compact('key', 'label'))
						->values(),
					'required' => true
				],
				[
					'component' => 'CheckboxField',
					'key' => 'visualization_row_totals',
					'label' => $gui->t('Zeilensummen'),
					'dependencies' => [
						[
							'type' => 'visibility',
							'field' => 'visualization',
							'values' => ['pivot']
						]
					]
				],
				[
					'component' => 'CheckboxField',
					'key' => 'visualization_grand_totals',
					'label' => $gui->t('Gesamtsummen'),
					'dependencies' => [
						[
							'type' => 'visibility',
							'field' => 'visualization',
							'values' => ['pivot']
						]
					]
				],
				[
					'component' => 'RepeatableSection',
					'key' => 'groupings',
					'label' => $gui->t('Gruppierungen'),
					'components' => $groupingConfig,
					'sortable' => true
				],
				[
					'component' => 'RepeatableSection',
					'key' => 'columns',
					'min' => 1,
					'sortable' => true,
					'label' => $gui->t('Spalten'),
					'components' => [
						[
							'component' => 'SelectField',
							'key' => 'object',
							'label' => $gui->t('Spalte'),
							'options' => $columns,
							'required' => true
						],
						[
							'component' => 'TsReporting.GuiDialogColumnOptions',
							'key' => 'config',
							'definitions' => $definitions,
							'first-layer' => true
						]
					]
				],
//				[
//					'component' => 'RepeatableSection',
//					'key' => 'filters',
//					'label' => $gui->t('Filter'),
//					'components' => [
//						[
//							'component' => 'SelectField',
//							'key' => 'object',
//							'label' => $gui->t('Filter'),
//							'options' => [],
//							'required' => true
//						]
//					]
//				]
			]
		];

		return $data;
	}

	private function generateColumOptions(array $classes, &$definitions)
	{
		return collect($classes)->transform(function (string $class) use (&$definitions) {
			$column = new $class(); /** @var AbstractColumn|AbstractGrouping $column */

			$fields = array_map(function (array $field) {
				$field['required'] = true;
				$field['options'] = array_map(fn(string $key, string $label) => compact('key', 'label'), array_keys($field['options']), $field['options']);
				return $field;
			}, $column->getConfigOptions());

			if ($column instanceof AbstractGrouping) {
				$fields[] = [
					'key' => 'pivot',
					'label' => $this->_oGui->t('Visualisierung'),
					'type' => 'select',
					'required' => true,
					'options' => [
						['key' => 'row', 'label' => $this->_oGui->t('Zeile')],
						['key' => 'col', 'label' => $this->_oGui->t('Spalte')]
					],
					'dependencies' => [
						[
							'type' => 'visibility',
							'field' => 'visualization',
							'values' => ['pivot']
						]
					]
				];
			}

//			$groupings = $column->getAvailableGroupings();
//			if (!empty($groupings)) {
//				array_unshift($fields, [
//					'key' => 'grouping',
//					'label' => $this->_oGui->t('Gruppierung'),
//					'type' => 'select',
//					'required' => false,
//					'options' => array_map(fn(string $class) => ['key' => $class, 'label' => (new $class())->getTitle()], $groupings)
//				]);
//			}

			$definitions[$class] = $fields;
			return ['key' => $class, 'label' => $column->getTitle()];
		})->sortBy('label')->values();
	}

	public function getErrorMessage($error, $field, $label = '')
	{
		if (
			$error instanceof ValidationException &&
			$error->getMessage() === 'COLUMN_INCOMPATIBILITY'
		) {
			return str_replace(
				['{column}', '{grouping}'],
				[$error->getAdditional()['column'], $error->getAdditional()['grouping']],
				$this->_oGui->t('Die Spalte "{column}" und die Gruppierung "{grouping}" sind nicht miteinander kompatibel.')
			);
		}

		return match ($error) {
			'MISSING_GROUPINGS_FOR_PIVOT' => $this->_oGui->t('Eine Pivot-Tabelle benötigt Gruppierungen auf beiden Achsen.'),
			'AGE_GROUPS_OVERLAPPING' => $this->_oGui->t('Altersgruppen dürfen sich nicht überschneiden.'),
			default => parent::getErrorMessage($error, $field, $label)
		};
	}
}
