<?php

namespace TsAccommodation\Events;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagementData;
use Tc\Interfaces\EventManager\Process;
use Tc\Interfaces\Events\Settings;
use Ts\Events\Inquiry\InquiryDayEvent;

class ExpiringAccommodationRequirements extends MissingAccommodationRequirements
{
	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Ablaufende Unterkunftsvoraussetzungen');
	}

	public static function toReadable(Settings $settings): string
	{
		$days = (int)$settings->getSetting('days', 0);
		$daysTranslation = ($days === 1)
			? EventManager::l10n()->translate('Tag')
			: EventManager::l10n()->translate('Tage');

		$validDateTranslation = EventManager::l10n()->translate('jedes Gültigkeitsdatum');

		if ($days === 0) {
			$data = [$validDateTranslation];
		} else {
			$data = [
				$days,
				$daysTranslation,
				InquiryDayEvent::getSelectOptionsDirection()[$settings->getSetting('direction')],
				$validDateTranslation
			];
		}

		return
			EventManager::l10n()->translate('Unterkunftsvoraussetzungen').': '.
			implode(' ', $data);
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, EventManagementData $dataClass): void
	{
		self::addExecutionTimeRow($dialog, $tab, $dataClass);
		self::addExecutionWeekendRow($dialog, $tab, $dataClass);

		$l10n = EventManager::l10n();

		$tab->setElement($dialog->createMultiRow($dataClass->t('Bedingung'), [
			'db_alias' => 'tc_emc',
			'row_class' => 'condition_row',
			'items' => [
				[
					'db_column' => 'meta_days',
					'input' => 'input',
					'class' => 'txt w50',
					'style' => 'width: 50px;',
					'text_after' => $l10n->translate('Tage').'&nbsp;'
				],
				[
					'db_column' => 'meta_direction',
					'input' => 'select',
					'class' => 'txt auto_width',
					'select_options' => InquiryDayEvent::getSelectOptionsDirection(),
					'text_after' => $l10n->translate(' jedes Gültigkeitsdatum').'&nbsp;'
				],
			]
		]));
	}

	protected static function getAffectedAccommodationsWithRequirementIds(Carbon $time, Process $process): Collection
	{
		$direction = $process->getSetting('direction');
		$days = $process->getSetting('days', 0);

		// Tage vor/nach
		// Wenn der Wert auf 0 steht, wird demnach der heutige Tag ($time) als Filter benutzt
		if($days > 0) {
			if ($direction === 'after') {
				$time->subDays($days);
			} else {
				$time->addDays($days);
			}
		}

		// Alle Unterkunftsanbieter, bei denen das Gültigkeitsdatum der Unterkunftsvoraussetzung gleich dem Tag der gewählten Einstellungen ist
		$result = \DB::table('customer_db_4')
			->select('customer_db_4.*')
			->selectRaw('GROUP_CONCAT(DISTINCT `ts_aprd`.`requirement_id`) `requirements_ids`')
			->join('ts_accommodation_providers_requirements_documents as ts_aprd', 'ts_aprd.accommodation_provider_id', 'customer_db_4.id')
			->where('ts_aprd.active', 1)
			->whereDate('ts_aprd.valid', $time)
			->where('ts_aprd.always_valid', 0)
			->groupBy('customer_db_4.id')
			->get()
			->map(fn ($row) => [
				\Ext_Thebing_Accommodation::getObjectFromArray(Arr::except($row, 'requirements_ids')),
				explode(',', $row['requirements_ids'])
			]);

		return $result;
	}

}
