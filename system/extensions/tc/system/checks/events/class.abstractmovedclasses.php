<?php

abstract class Ext_TC_System_Checks_Events_AbstractMovedClasses extends \GlobalChecks
{
	protected $events = [];
	protected $listeners = [];
	protected $conditions = [];

	public function getTitle()
	{
		return 'Event control';
	}

	public function getDescription()
	{
		return 'Changes event control structure';
	}

	public function executeCheck()
	{
		$first = \Tc\Entity\EventManagement::query()->first();
		if ($first === null) {
			return true;
		}

		$backup = [];

		if (!empty($this->events)) {
			$backup[] = \Util::backupTable('tc_event_management');
		}

		if (!empty($this->listeners) || !empty($this->conditions)) {
			$backup[] = \Util::backupTable('tc_event_management_childs');
		}

		if (in_array(false, $backup)) {
			__pout('Backup error');
			return false;
		}

		\DB::begin(__METHOD__);

		try {

			$replace = function ($table, $column, $old, $new) {
				$sql = "UPDATE #table SET `changed` = `changed`, #column = :new WHERE #column = :old";
				\DB::executePreparedQuery($sql, ['table' => $table, 'column' => $column, 'new' => $new, 'old' => $old]);
			};

			foreach ($this->events as $old => $new) {
				$replace('tc_event_management', 'event_name', $old , $new);
			}

			foreach (array_merge($this->listeners, $this->conditions) as $old => $new) {
				$replace('tc_event_management_childs', 'class', $old , $new);
			}

		} catch (\Throwable $e) {
			\DB::rollback(__METHOD__);
			__pout($e);
			return false;
		}

		\DB::commit(__METHOD__);

		return true;
	}

}