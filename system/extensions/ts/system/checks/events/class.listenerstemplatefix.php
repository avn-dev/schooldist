<?php

use Illuminate\Support\Collection;

class Ext_TS_System_Checks_Events_ListenersTemplateFix extends \GlobalChecks
{
	public function getTitle()
	{
		return 'Event Control';
	}

	public function getDescription()
	{
		return 'Checks the template selection in event control';
	}

	public function executeCheck()
	{
		// Nur ausführen wenn der angepasst SchoolToCore-Check noch nicht durchgelaufen ist.
		$alreadyExecuted = (bool)\System::d(\Ext_TS_System_Checks_Templates_Mail_SchoolToCore::LOCK_KEY, 0);

		if ($alreadyExecuted) {
			return true;
		}

		$minDate = new \DateTime('2025-10-31 00:00:00');

		// Erste Backup-Tabelle seit dem 2025-10-31 (3.0.0 Update). Bei Maltalingua passt das Letzte nicht weil das Update scheinbar öfters
		// ausgeführt wurde
		$backupAttributes = $this->get300BackupTable('wdbasic_attributes', $minDate);
		$backupCustomerDb2 = $this->get300BackupTable('customer_db_2', $minDate);

		if (!$backupAttributes || !$backupCustomerDb2) {
			return false;
		}

		$log = function (string $text, array $data = [], string $type = 'info') {
			if ($type === 'error') {
				$this->logError($text, $data);
			} else {
				$this->logInfo($text, $data);
			}
			//dump($type, $text, $data);
		};

		$log('Backup-Table: ' . $backupAttributes);
		$log('Backup-Table: ' . $backupCustomerDb2);

		$backup = [
			\Util::backupTable('wdbasic_attributes'),
			\Util::backupTable('customer_db_2')
		];

		if (in_array(false, $backup)) {
			__pout('Backup error');
			return false;
		}

		\DB::begin(__METHOD__);

		try {

			$groupedListeners = $this->getCurrentListeners();

			foreach ($groupedListeners as $eventId => $listeners) {

				$event = \DB::getQueryRow("SELECT * FROM `tc_event_management` WHERE `id` = :id", ['id' => $eventId]);

				$log('Event: ' . $event['name']);

				foreach ($listeners as $listener) {

					$backupTemplateId = $this->getListenerTemplateBackupValue($backupAttributes, $listener['id']);

					if ($backupTemplateId === 0) {
						$log('No listener backup found', ['listener_id' => $listener['id'], 'class' => $listener['class'], 'created' => $listener['created']], 'error');
						continue;
					}

					// Maltalingua hat hier teilweise schon neu angelegt
					$newlyListenersOfSameType = (array)\DB::getPreparedQueryData("
						SELECT
							*
						FROM
							`tc_event_management_childs` 
						WHERE
							`event_id` = :event_id AND
							`class` = :class AND
							`id` != :id AND
							`active` = 1 AND
							DATE(`created`) >= :date
					
					", [
						'event_id' => $listener['event_id'],
						'class' => $listener['class'],
						'id' => $listener['id'],
						'date' => $minDate->format('Y-m-d')
					]);

					if (!empty($newlyListenersOfSameType)) {
						$log('Newer listeners of same type exists', ['id' => $listener['id'], 'class' => $listener['class'], 'existing' => array_map(fn ($loop) => \Illuminate\Support\Arr::only($loop, ['id', 'created']), $newlyListenersOfSameType)], 'error');
						continue;
					}

					$currentTemplate = \DB::getQueryRow("SELECT * FROM `tc_communication_templates` WHERE `id` = :id", ['id' => $listener['template_id']]);

					if ($listener['class'] === \Ts\Listeners\Inquiry\SendCustomerAppNotification::class) {
						// Bei App-Nachrichten auf jeden Fall das Backup holen, die waren schon auf Core-Templates
						$newTemplateId = $backupTemplateId;

						$newTemplate = \DB::getQueryRow("SELECT * FROM `tc_communication_templates` WHERE `id` = :id", ['id' => $newTemplateId]);

						if (empty($newTemplate) || $newTemplate['type'] !== 'app') {
							$log('APP: New template is no app template', ['listener_id' => $listener['id'], 'class' => $listener['class'], 'template_id ' => $newTemplate], 'error');
							continue;
						}

						$log('APP: New listener template', ['listener_id' => $listener['id'], 'current_template' => $currentTemplate['name'], 'new_template' => $newTemplate['name']]);

					} else {

						[$oldTemplate, $newTemplate] = $this->getNewTemplate($backupTemplateId);

						if (!$oldTemplate || !$newTemplate) {
							$log('EMAIL: Listener Backup template not found', ['listener_id' => $listener['id'], 'backup_template_id' => $backupTemplateId], 'error');
							continue;
						}

						if ($oldTemplate['name'] !== $newTemplate['name']) {
							$log('EMAIL: Listener template name missmatch', ['listener_id' => $listener['id'], 'old_template' => $oldTemplate['id'].':'.$oldTemplate['name'], 'new_template' => $newTemplate['id'].':'.$newTemplate['name']], 'error');
							continue;
						}

						$newTemplateId = $newTemplate['id'];

						$log('EMAIL: New listener template', ['listener_id' => $listener['id'], 'current_template' => $currentTemplate['name'], 'backup_template' => $oldTemplate['name'], 'new_template' => $newTemplate['name']]);
					}

					if ((int)$newTemplateId !== (int)$listener['template_id']) {
						\DB::executePreparedQuery("
							UPDATE
								`wdbasic_attributes`
							SET
								`value` = :value
							WHERE
								`id` = :id AND
								`key` = 'template_id'
						", ['value' => $newTemplateId, 'id' => $listener['attribute_id']]);
					}
				}
			}

			$schoolIds = \DB::getQueryCol("SELECT `id` FROM `customer_db_2` WHERE `active` = 1");

			foreach ($schoolIds as $schoolId) {

				$backupStudenAppTemplateId = $this->getCustomerDbBackupValue($backupAttributes, $schoolId, 'student_app_template_forgotten_password');
				$backupAccommodationLoginTemplateId = $this->getCustomerDbBackupValue($backupAttributes, $schoolId, 'accommodationlogin_template');
				$backupTeacherLoginTemplateId = (int)\DB::getQueryOne("SELECT `teacherlogin_template` FROM #table WHERE `id` = :id", ['table' => $backupCustomerDb2, 'id' => $schoolId]);
				$backupTeacherReportCardsTemplateId = (int)\DB::getQueryOne("SELECT `teacherlogin_reportcard_template` FROM #table WHERE `id` = :id", ['table' => $backupCustomerDb2, 'id' => $schoolId]);

				if ($backupStudenAppTemplateId > 0) {
					[$oldTemplate, $newTemplate] = $this->getNewTemplate($backupStudenAppTemplateId);
					$execute = true;

					if (!$oldTemplate || !$newTemplate) {
						$log('EMAIL: Backup template not found - student_app_template_forgotten_password', ['school_id' => $schoolId, 'key' => 'student_app_template_forgotten_password'], 'error');
						$execute = false;
					}

					if ($execute && $oldTemplate['name'] !== $newTemplate['name']) {
						$log('EMAIL: Template name missmatch - student_app_template_forgotten_password', ['school_id' => $schoolId, 'old_template' => $oldTemplate['id'].':'.$oldTemplate['name'], 'new_template' => $newTemplate['id'].':'.$newTemplate['name']], 'error');
						$execute = false;
					}

					if ($execute && (int)$newTemplate['id'] !== (int)$oldTemplate['id']) {
						\DB::executePreparedQuery("
							UPDATE
								`wdbasic_attributes`
							SET
								`value` = :value
							WHERE
								`entity` = 'customer_db_2' AND
								`entity_id` = :id AND
								`key` = 'student_app_template_forgotten_password'
						", ['value' => $newTemplate['id'], 'id' => $schoolId]);
						$log('EMAIL: New school template - student_app_template_forgotten_password', ['school_id' => $schoolId, 'old_template' => $oldTemplate['id'].':'.$oldTemplate['name'], 'new_template' => $newTemplate['id'].':'.$newTemplate['name']]);
					}
				}

				if ($backupAccommodationLoginTemplateId > 0) {
					[$oldTemplate, $newTemplate] = $this->getNewTemplate($backupAccommodationLoginTemplateId);
					$execute = true;

					if (!$oldTemplate || !$newTemplate) {
						$log('EMAIL: Backup template not found - accommodationlogin_template', ['school_id' => $schoolId, 'key' => 'accommodationlogin_template'], 'error');
						$execute = false;
					}

					if ($execute && $oldTemplate['name'] !== $newTemplate['name']) {
						$log('EMAIL: Template name missmatch - accommodationlogin_template', ['school_id' => $schoolId, 'old_template' => $oldTemplate['id'].':'.$oldTemplate['name'], 'new_template' => $newTemplate['id'].':'.$newTemplate['name']], 'error');
						$execute = false;
					}

					if ($execute && (int)$newTemplate['id'] !== (int)$oldTemplate['id']) {
						\DB::executePreparedQuery("
							UPDATE
								`wdbasic_attributes`
							SET
								`value` = :value
							WHERE
								`entity` = 'customer_db_2' AND
								`entity_id` = :id AND
								`key` = 'accommodationlogin_template'
						", ['value' => $newTemplate['id'], 'id' => $schoolId]);

						$log('EMAIL: New school template - accommodationlogin_template', ['school_id' => $schoolId, 'old_template' => $oldTemplate['id'].':'.$oldTemplate['name'], 'new_template' => $newTemplate['id'].':'.$newTemplate['name']]);
					}
				}

				if ($backupTeacherLoginTemplateId > 0) {
					[$oldTemplate, $newTemplate] = $this->getNewTemplate($backupTeacherLoginTemplateId);
					$execute = true;

					if (!$oldTemplate || !$newTemplate) {
						$log('EMAIL: Backup template not found - teacherlogin_template', ['school_id' => $schoolId, 'key' => 'teacherlogin_template'], 'error');
						$execute = false;
					}

					if ($execute && $oldTemplate['name'] !== $newTemplate['name']) {
						$log('EMAIL: Template name missmatch - teacherlogin_template', ['school_id' => $schoolId, 'old_template' => $oldTemplate['id'].':'.$oldTemplate['name'], 'new_template' => $newTemplate['id'].':'.$newTemplate['name']], 'error');
						$execute = false;
					}

					if ($execute && (int)$newTemplate['id'] !== (int)$oldTemplate['id']) {
						\DB::executePreparedQuery("
							UPDATE
								`customer_db_2`
							SET
								`changed` = `changed`,
								`teacherlogin_template` = :value
							WHERE
								`id` = :id
						", ['value' => $newTemplate['id'], 'id' => $schoolId]);

						$log('EMAIL: New school template - teacherlogin_template', ['school_id' => $schoolId, 'old_template' => $oldTemplate['id'].':'.$oldTemplate['name'], 'new_template' => $newTemplate['id'].':'.$newTemplate['name']]);
					}
				}

				if ($backupTeacherReportCardsTemplateId > 0) {
					[$oldTemplate, $newTemplate] = $this->getNewTemplate($backupTeacherReportCardsTemplateId);
					$execute = true;

					if (!$oldTemplate || !$newTemplate) {
						$log('EMAIL: Backup template not found - teacherlogin_reportcard_template', ['school_id' => $schoolId, 'key' => 'teacherlogin_reportcard_template'], 'error');
						$execute = false;
					}

					if ($execute && $oldTemplate['name'] !== $newTemplate['name']) {
						$log('EMAIL: Template name missmatch - teacherlogin_reportcard_template', ['school_id' => $schoolId, 'old_template' => $oldTemplate['id'].':'.$oldTemplate['name'], 'new_template' => $newTemplate['id'].':'.$newTemplate['name']], 'error');
						$execute = false;
					}

					if ($execute && (int)$newTemplate['id'] !== (int)$oldTemplate['id']) {
						\DB::executePreparedQuery("
							UPDATE
								`customer_db_2`
							SET
								`changed` = `changed`,
								`teacherlogin_reportcard_template` = :value
							WHERE
								`id` = :id
						", ['value' => $newTemplate['id'], 'id' => $schoolId]);

						$log('EMAIL: New school template - teacherlogin_reportcard_template', ['school_id' => $schoolId, 'old_template' => $oldTemplate['id'].':'.$oldTemplate['name'], 'new_template' => $newTemplate['id'].':'.$newTemplate['name']]);
					}
				}

			}

		} catch (\Throwable $e) {
			\DB::rollBack(__METHOD__);
			__pout($e);
			dd($e);
			return false;
		}

		\DB::commit(__METHOD__);

		\System::s(\Ext_TS_System_Checks_Templates_Mail_SchoolToCore::LOCK_KEY, 1);

		return true;
	}

	private function getCurrentListeners(): Collection
	{
		$sql = "
			SELECT 
			    `tc_event_management_childs`.*,
			    `wdbasic_attributes`.`id` `attribute_id`,
			    `wdbasic_attributes`.`value` `template_id`
			FROM 
			    `tc_event_management_childs` INNER JOIN
			    `wdbasic_attributes` ON 
			    	`wdbasic_attributes`.`entity` = 'tc_event_management_childs' AND 
			    	`wdbasic_attributes`.`entity_id` = `tc_event_management_childs`.`id` AND 
			    	`wdbasic_attributes`.`key` = 'template_id'
			WHERE
			    `tc_event_management_childs`.`active` = 1
		";

		return collect((array)\DB::getQueryData($sql))
			->mapToGroups(fn($listener) => [$listener['event_id'] => $listener]);
	}

	private function getListenerTemplateBackupValue(string $backupTable, int $listenerId): int
	{
		$sql = "
			SELECT 
			    `value`
			FROM 
			    #table 
			WHERE
				`entity` = 'tc_event_management_childs' AND 
				`entity_id` = :id AND 
				`key` = 'template_id'
		";

		return (int)\DB::getQueryOne($sql, ['table' => $backupTable, 'id' => $listenerId]);
	}

	private function getCustomerDbBackupValue(string $backupTable, int $id, string $key): int
	{
		$sql = "
			SELECT 
			    `value`
			FROM 
			    #table 
			WHERE
				`entity` = 'customer_db_2' AND 
				`entity_id` = :id AND 
				`key` = :key
		";

		return (int)\DB::getQueryOne($sql, ['table' => $backupTable, 'id' => $id, 'key' => $key]);
	}

	private function getNewTemplate(int $oldTemplateId): array
	{
		$sql = "
			SELECT
				`value`
			FROM
			    `wdbasic_attributes` 
			WHERE
			    `entity` = 'kolumbus_email_templates' AND
			    `entity_id` = :old_template_id AND
			    `key` = 'core_template_id'
			LIMIT 
				1
		";

		$newTemplateId = (int)\DB::getQueryOne($sql, ['old_template_id' => $oldTemplateId]);

		$oldTemplate = \DB::getQueryRow("SELECT * FROM `kolumbus_email_templates` WHERE `id` = :id", ['id' => $oldTemplateId]);
		$newTemplate = null;

		if ($newTemplateId > 0) {
			$newTemplate = \DB::getQueryRow("SELECT * FROM `tc_communication_templates` WHERE `id` = :id", ['id' => $newTemplateId]);
		} else if ($oldTemplate) {
			// TODO Fallback sicher?
			#$newTemplate = \DB::getQueryOne("SELECT * FROM `tc_communication_templates` WHERE `name` = :name AND `active` = 1", ['name' => $oldTemplate['name']]);
		}

		return [$oldTemplate, $newTemplate];
	}

	private function get300BackupTable(string $table, \DateTime $minDate): ?string
	{
		$tables = \DB::listTables();
		$pattern = '/^__\d{14}_.+$/';
		$backups = [];

		// Funktioniert nur bei kleinen Tabellen
		$expectedSuffix = $table;

		foreach ($tables as $t) {

			if (!preg_match($pattern, $t)) {
				continue;
			}

			$timestamp = substr($t, 2, 14);
			$baseName = substr($t, 17);

			if ($baseName !== $expectedSuffix) {
				continue;
			}

			$dt = \DateTime::createFromFormat('YmdHis', $timestamp);
			if (!$dt) {
				continue;
			}

			if (
				$minDate &&
				$dt < $minDate
			) {
				continue;
			}

			$backups[$t] = $dt;
		}

		if (empty($backups)) {
			return null;
		}

		uasort($backups, fn($a, $b) => $b <=> $a);

		// Die erste Backup-Tabelle seit dem 2025-10-31!
		return array_key_last($backups);
	}

}

