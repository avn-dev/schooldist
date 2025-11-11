<?php

namespace TsReporting\Entity;

use Core\Exception\Entity\ValidationException;
use Illuminate\Support\Arr;
use TsReporting\Generator\Groupings\AbstractGrouping;
use TsReporting\Generator\Groupings\Aggregated;

class Report extends \Ext_TC_Basic
{
	const TRANSLATION_PATH = 'Fidelo » Reporting';

	protected $_sTable = 'ts_reporting_reports';

	protected $_aJoinedObjects = [
		'groupings' => [
			'class' => ReportSetting::class,
			'key' => 'report_id',
			'type' => 'child',
			'static_key_fields' => ['type' => ReportSetting::TYPE_GROUPING],
			'orderby' => 'position',
			'orderby_set' => false
		],
		'columns' => [
			'class' => ReportSetting::class,
			'key' => 'report_id',
			'type' => 'child',
			'static_key_fields' => ['type' => ReportSetting::TYPE_COLUMN],
			'orderby' => 'position',
			'orderby_set' => false
		],
		'filters' => [
			'class' => ReportSetting::class,
			'key' => 'report_id',
			'type' => 'child',
			'static_key_fields' => ['type' => ReportSetting::TYPE_FILTER]
		]
	];

	public function validate($bThrowExceptions = false)
	{
		$validate = parent::validate($bThrowExceptions);

		// Spalten-Abhängigkeiten
		if ($validate === true) {
			$columns = $this->createGeneratorObjects('columns');
			$groupings = array_reduce($this->createGeneratorObjects('groupings'), function (array $carry, AbstractGrouping $grouping) {
				$carry[] = $grouping;
				if ($grouping instanceof Aggregated) {
					$carry = [...$carry, ...$grouping->getChildGroupings()];
				}
				return $carry;
			}, []);

			foreach ($columns as $column) {
				$columnGroupings = $column->getAvailableGroupings();
				foreach ($groupings as $grouping) {
					if (!in_array(get_class($grouping), $columnGroupings)) {
						throw (new ValidationException('COLUMN_INCOMPATIBILITY'))
							->setAdditional(['column' => $column->getTitle(), 'grouping' => $grouping->getTitle()]);
					}
				}
			}
		}

		if (
			$validate === true &&
			$this->visualization === 'pivot'
		) {
			$groupings = $this->createGeneratorObjects('groupings');
			if (
				!Arr::first($groupings, fn(AbstractGrouping $grouping) => $grouping->pivot === 'row') ||
				!Arr::first($groupings, fn(AbstractGrouping $grouping) => $grouping->pivot === 'col')
			) {
				return ['visualization' => 'MISSING_GROUPINGS_FOR_PIVOT'];
			}
		}

		// Überlappende Altersgruppen überprüfen
		if ($validate === true) {
			foreach ($this->createGeneratorObjects('groupings') as $grouping) {
				if ($grouping instanceof \TsReporting\Generator\Groupings\Booking\AgeGroup) {
					$ageGroups = $grouping->getAgeGroups();
					foreach ($ageGroups as $ageGroup1) {
						foreach ($ageGroups as $ageGroup2) {
							if (
								$ageGroup1 !== $ageGroup2 &&
								$ageGroup1->age_from <= $ageGroup2->age_until &&
								$ageGroup2->age_from <= $ageGroup1->age_until
							) {
								return ['groupings.object' => 'AGE_GROUPS_OVERLAPPING'];
							}
						}
					}
				}
			}
		}

		return $validate;
	}

	public function createGeneratorBase(): \TsReporting\Generator\Bases\AbstractBase
	{
		return new $this->base();
	}

	public function createGeneratorObjects(string $type): array
	{
		return array_map(function (ReportSetting $setting) {
			/** @var \TsReporting\Generator\Columns\AbstractColumn|AbstractGrouping $column */
			$column = new $setting->object();
			$column->setId($setting->id);
			$column->setConfig($setting->config);
			return $column;
		}, $this->getJoinedObjectChilds($type, true));
	}
}