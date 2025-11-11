<?php

namespace Tc\Service\EventManager;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tc\Entity\EventManagement;
use Tc\Interfaces\EventManager\Repository;

class ModelRepository implements Repository
{
	public function forEvent(string $eventName): Collection
	{
		return EventManagement::query()
			->onlyValid()
			->where('event_name', $eventName)
			->get();
	}

	public function forTime(Carbon $date): Collection
	{
		return EventManagement::query()
			->onlyValid($date)
			->where('execution_time', $date->format('G'))
			->where(function ($query) use ($date) {
				if ($date->isWeekend()) {
					$query->where(function ($query) {
						// Wenn "Täglich" zzgl. Wochenende
						$query->whereNull('execution_day')
							->where('execution_weekend', 1);
					});
				} else {
					$query->whereNull('execution_day');
				}
				$query->orWhere('execution_day', \Ext_TC_Util::convertWeekdayToString($date->format('N')));
			})
			->get();
	}

	public function forEntity(string $eventName, \WDBasic $entity): Collection
	{
		$query = EventManagement::query()
			->onlyValid()
			->select('tc_em.*');

		$query->join('wdbasic_attributes as attr', function ($join) use ($entity) {
			$value = sprintf('%s::%s', $entity::class, $entity->getId());
			$join->where('attr.entity', '=', 'tc_event_management')
				->on('attr.entity_id', 'tc_em.id')
				->where('attr.key', 'entity')
				->where('attr.value', $value);
		});

		$query->where('event_name', $eventName);

		return $query->get();
	}
}