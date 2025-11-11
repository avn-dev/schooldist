<?php

namespace Tc\Entity\EventManagement;

use Core\Database\WDBasic\Builder;
use Tc\Entity\AbstractManagedEntity;
use Tc\Entity\EventManagement;
use Tc\Enums\EventManager\Process\TaskType;
use Tc\Interfaces\EventManager\Process\Task;

/**
 * @property int $event_id
 * @property string $class
 * @property array $users
 * @property array $user_groups
 */
class Listener  extends AbstractManagedChild
{
	const TYPE = 'listener';

	protected $_aJoinTables = [
		'users' => [
			'table' => 'tc_event_management_listeners_to_users',
			'foreign_key_field' => 'type_id',
			'static_key_fields' => ['type' => 'user'],
			'primary_key_field' => 'listener_id',
		],
		'user_groups' => [
			'table' => 'tc_event_management_listeners_to_users',
			'foreign_key_field' => 'type_id',
			'static_key_fields' => ['type' => 'group'],
			'primary_key_field' => 'listener_id',
		]
	];

	public function getType(): TaskType
	{
		return TaskType::LISTENER;
	}

	public function getSettings(): array
	{
		$settings = parent::getSettings();
		$settings['users'] = $this->getUsers();

		return $settings;
	}

	public function getUsers(): array
	{
		$userType = $this->getMeta('receivers_type');

		if ($userType === 'users') {
			return array_map(fn($userId) => \Factory::getInstance(\Ext_TC_User::class, $userId), $this->users);
		} else if ($userType === 'user_groups') {

			$userGroups = array_map(fn($groupId) => \Tc\Entity\SystemTypeMapping::getInstance($groupId), $this->user_groups);

			return \Factory::executeStatic(\User::class, 'query')
				->select('su.*')
				->join('tc_employees_to_categories as tc_etc', function($join) use ($userGroups) {
					$join->on('tc_etc.employee_id', '=', 'su.id')
						->whereIn('tc_etc.category_id', array_column($userGroups, 'id'));
				})
				->get()
				->toArray();
		}

		return [];
	}

	public static function booted()
	{
		static::addGlobalScope('type', function (Builder $builder) {
			$builder->where($builder->getModel()->qualifyColumn('type'), self::TYPE);
		});
	}

}
