<?php

namespace Tc\Entity;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Tc\Interfaces\EventManager\Process;
use Tc\Service\Placeholder\EventManager\EventEntityPlaceholders;

/**
 * @property int $id
 * @property string $event_name
 * @property string $execution_day
 * @property int $execution_time
 */
class EventManagement extends AbstractManagedEntity implements Process
{
	protected $_sTableAlias = 'tc_em';

	protected $_sTable = 'tc_event_management';

	protected $_sPlaceholderClass = EventEntityPlaceholders::class;

	protected $_aJoinedObjects = [
		'listeners' => [
			'class' => EventManagement\Listener::class,
			'key' => 'event_id',
			'type' => 'child',
			'static_key_fields' => ['type' => EventManagement\Listener::TYPE],
			'on_delete' => 'cascade',
			'check_active' => true,
			'orderby' => 'position'
		],
		'conditions' => [
			'class' => EventManagement\Condition::class,
			'key' => 'event_id',
			'type' => 'child',
			'static_key_fields' => ['type' => EventManagement\Condition::TYPE],
			'on_delete' => 'cascade',
			'check_active' => true,
			'orderby' => 'position'
		]
	];

	public function getIdentifier(): string|int
	{
		return $this->id;
	}

	public function getHumanReadableText($l10n): string
	{
		return $this->name;
	}

	public function getProcessName(): string
	{
		return $this->event_name;
	}

	/**
	 * @return EventManagement\Listener[]
	 */
	public function getListeners(): Collection
	{
		return collect($this->getJoinedObjectChilds('listeners', true));
	}

	/**
	 * @return EventManagement\Condition[]
	 */
	public function getConditions(): Collection
	{
		return collect($this->getJoinedObjectChilds('conditions', true));
	}

	/**
	 * @return array
	 */
	public function getSettings(): array
	{
		$settings = $this->getAllMetaData();
		$settings['execution_day'] = $this->execution_day;
		$settings['execution_time'] = $this->execution_time;
		return $settings;
	}

	public function getSetting(string $key, $default = null)
	{
		return Arr::get($this->getSettings(), $key, $default);
	}

	public function updateLastAction(): bool
	{
		if(!$this->exist()) {
			return false;
		}

		$sSql = "
			UPDATE
				#table
			SET
				`changed` = `changed`,
				`last_action` = NOW()
			WHERE
				`id` = :id
			LIMIT
				1
		";

		\DB::executePreparedQuery($sSql, ['table' => $this->_sTable, 'id' => $this->id]);

		return true;

	}

	/**
	 * @param $sqlParts
	 * @param $view
	 * @return void
	 */
	public function manipulateSqlParts(&$sqlParts, $view = null)
	{
		$sqlParts['select'] .= "
			, COUNT(`tc_eml`.`id`) `listeners_count`
		";

		$sqlParts['from'] .= " LEFT JOIN
			`tc_event_management_childs` `tc_eml` ON
				`tc_eml`.`event_id` = `tc_em`.`id` AND		
				`tc_eml`.`type` = '".EventManagement\Listener::TYPE."' AND		
				`tc_eml`.`active` = 1 LEFT JOIN
			`tc_event_management_listeners_to_users` `tc_emltu` ON
				`tc_emltu`.`listener_id` = `tc_eml`.`id`
		";

		$sqlParts['groupby'] = "`tc_em`.`id`";

	}

}
