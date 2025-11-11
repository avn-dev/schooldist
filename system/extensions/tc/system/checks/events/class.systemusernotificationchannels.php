<?php

class Ext_TC_System_Checks_Events_SystemUserNotificationChannels extends \GlobalChecks
{
	public function getTitle()
	{
		return 'Event control';
	}

	public function getDescription()
	{
		return 'Adds default communication channel to user notifications';
	}

	public function executeCheck()
	{
		$listeners = \Tc\Entity\EventManagement\Listener::query()
			->where('class', \Tc\Listeners\SendSystemUserNotification::class)
			->get();

		if ($listeners->isEmpty()) {
			return true;
		}

		$backup = [\Util::backupTable('wdbasic_attributes')];

		if (in_array(false, $backup)) {
			__pout('Backup error');
			return false;
		}

		\DB::begin(__METHOD__);

		try {
			foreach ($listeners as $listener) {
				/* @var \Tc\Entity\EventManagement\Listener $listener */
				$meta = $listener->getMeta('channels', null);
				if ($meta === null) {
					$listener->setMeta('channels', ['database']);
					$listener->save();
				}
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