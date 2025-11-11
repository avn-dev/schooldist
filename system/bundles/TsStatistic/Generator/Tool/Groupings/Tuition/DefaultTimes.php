<?php

namespace TsStatistic\Generator\Tool\Groupings\Tuition;

use TcStatistic\Exception\NoResultsException;
use TsStatistic\Generator\Tool\Groupings\AbstractGrouping;
use TsStatistic\Generator\Tool\Groupings\AllLabelsInterface;

class DefaultTimes extends AbstractGrouping implements AllLabelsInterface {

	public function getTitle() {
		return self::t('Standardzeit');
	}

	public function getSelectFieldForId() {
		return "0"; // Wird in den Spalten ersetzt
	}

	public function getSelectFieldForLabel() {
		return "0"; // Wird in den Spalten ersetzt
	}

	public function getAllLabels(): array {

		$labels = collect(\Ext_Thebing_Management_Settings_TuitionTime::findAll())
			->map(function (\Ext_Thebing_Management_Settings_TuitionTime $setting) {
				return $setting->getTuitionTime();
			})
			->filter(function (\Ext_Thebing_Tuition_Template $template) {
				return in_array($template->school_id, $this->filterValues->schools);
			})
			->mapWithKeys(function (\Ext_Thebing_Tuition_Template $template) {
				$name = $template->name;
				if (count($this->filterValues->schools) > 1) {
					$name = sprintf('%s: %s', $template->getSchool()->short, $template->name);
				}
				return [$template->id => $name];
			});

		// Das macht höchstens für den Klassenraumbedarf Sinn
		//$labels->prepend(self::t('keine Standardzeit'), 0);

		if ($labels->isEmpty()) {
			throw new NoResultsException(self::t('Es wurden keine Standardzeiten für die Statistik definiert.'));
		}

		return $labels->toArray();

	}

}
