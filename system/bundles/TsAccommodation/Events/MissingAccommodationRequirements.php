<?php

namespace TsAccommodation\Events;

use Carbon\Carbon;
use Core\Enums\AlertLevel;
use Core\Interfaces\HasAlertLevel;
use Core\Traits\WithAlertLevel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Interfaces\EventManager\Process;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;
use Ts\Events\Inquiry\Conditions;
use Ts\Interfaces\Events;
use Ts\Traits\Events\Manageable\WithManageableIndividualCommunication;
use Ts\Traits\Events\Manageable\WithManageableOneTimeExecutionTime;
use TsAccommodation\Dto\AccommodationRequirements;
use TsAccommodation\Entity\Requirement;
use TsAccommodation\Events\Conditions\ActiveCondition;

class MissingAccommodationRequirements implements ManageableEvent, \Tc\Interfaces\Events\AccommodationEvent, Events\MultipleSchoolsEvent, HasAlertLevel
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableOneTimeExecutionTime,
		WithManageableSystemUserCommunication,
		WithManageableIndividualCommunication,
		WithAlertLevel;

	public function __construct(
		private readonly \Ext_Thebing_Accommodation $accommodation,
		private readonly array $requirements
	) {}

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Fehlende Unterkunftsvoraussetzungen');
	}

	public function getAlertLevel(): ?AlertLevel
	{
		return AlertLevel::WARNING;
	}

	public function getIcon(): ?string
	{
		return 'fa fa-home';
	}

	public function getAccommodation(): \Ext_Thebing_Accommodation
	{
		return $this->accommodation;
	}

	public function getRequirements(): array
	{
		return $this->requirements;
	}

	public function getSchools(): array
	{
		return $this->getAccommodation()->getJoinTableObjects('schools');
	}

	public static function getPlaceholderObject(self $event = null): ?\Ext_TC_Placeholder_Abstract
	{
		$DTO = ($event)
			? new AccommodationRequirements($event->getAccommodation(), $event->getRequirements())
			: new AccommodationRequirements();

		return $DTO->getPlaceholderObject();
	}

	public static function dispatchScheduledOnce(Carbon $time, Process $process): void
	{
		$result = static::getAffectedAccommodationsWithRequirementIds($time, $process);

		if ($result->isNotEmpty()) {
			// So viele sollten es nicht sein, deswegen kann man hier ruhig alle holen
			$requirements = Requirement::query()->get()->mapWithKeys(fn (Requirement $requirement) => [$requirement->id => $requirement]);
			foreach ($result as $row) {
				// e.g. [$accommodation, [1,2]]
				static::dispatch($row[0], $requirements->only($row[1])->values()->toArray());
			}
		}
	}

	/**
	 * Alle Unterkunftsanbieter, bei denen es kein Datum oder ein nicht mehr gültiges Datum bei den Voraussetzungen gibt
	 * (Und die "Unbegrenzt gültig"-Checkbox nicht aktiv ist)
	 *
	 * @param Carbon $time
	 * @param Process $process
	 * @return Collection
	 */
	protected static function getAffectedAccommodationsWithRequirementIds(Carbon $time, Process $process): Collection
	{
		$result = \DB::table('customer_db_4')
			->select('customer_db_4.*')
			->selectRaw('GROUP_CONCAT(DISTINCT `ts_aprd`.`requirement_id`) `requirements_ids`')
			->join('ts_accommodation_providers_requirements_documents as ts_aprd', 'ts_aprd.accommodation_provider_id', 'customer_db_4.id')
			->where('ts_aprd.active', '1')
			->where('ts_aprd.always_valid', 0)
			->whereDate('ts_aprd.valid', '<', $time)
			->groupBy('customer_db_4.id')
			->get()
			->map(fn ($row) => [
				\Ext_Thebing_Accommodation::getObjectFromArray(Arr::except($row, 'requirements_ids')),
				explode(',', $row['requirements_ids'])
			]);

		return $result;
	}

	public static function manageEventListenersAndConditions(): void
	{
		self::addManageableCondition(Conditions\AccommodationCategoryProvider::class);
		self::addManageableCondition(ActiveCondition::class);
		self::addManageableListener(\Ts\Listeners\SendSchoolNotification::class);
		self::addManageableListener(\Tc\Listeners\SendAccommodationProviderNotification::class);
	}

}
