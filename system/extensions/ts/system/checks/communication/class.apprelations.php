<?php

class Ext_TS_System_Checks_Communication_AppRelations extends GlobalChecks {

	public function getDescription()
	{
		return 'App Notifications';
	}

	public function getTitle()
	{
		return 'Speeds up loading of app notifications';
	}

	/**
	 * Kein Backup benötigt, da Tabellen leer
	 * @return boolean
	 */
	public function executeCheck()
	{
		set_time_limit(14400);
		ini_set('memory_limit', '4G');

		$appMessages = $this->getMessages();

		if (empty($appMessages)) {
			return true;
		}

		$backup = \Util::backupTable('tc_communication_messages_app_index');

		if (!$backup) {
			__pout('Backup error');
			return false;
		}

		foreach ($appMessages as $messageId) {

			try {

				[$from, $to] = $this->getAppRelation($messageId);

				foreach ($to as $receiver) {
					\DB::insertData('tc_communication_messages_app_index', [
						'message_id' => $messageId,
						'device_relation' => $receiver[0],
						'device_relation_id' => $receiver[1],
						'thread_relation' => $from[0],
						'thread_relation_id'  => $from[1],
					]);
				}

			} catch (\Exception $e) {
				$this->logError($e->getMessage());
				continue;
			}

		}

		return true;

	}

	private function getMessages(): array
	{
		$sql = "
			SELECT
				`tc_cm`.`id`
			FROM
			    `tc_communication_messages` `tc_cm` LEFT JOIN
			    `tc_communication_messages_app_index` `tc_cmai` ON
			        `tc_cmai`.`message_id` = `tc_cm`.`id`
			WHERE
			    `tc_cm`.`active` = 1 AND
			    `tc_cm`.`type` = 'app' AND
			    `tc_cm`.`direction` = 'out' AND
			    `tc_cmai`.`message_id` IS NULL
		";

		return (array)\DB::getQueryCol($sql);
	}

	private function getAppRelation(int $messageId): array {

		$sql = "
			SELECT
			    `tc_cma`.`type`,
				GROUP_CONCAT( CONCAT (`tc_cmar`.`relation`, '{|}', `tc_cmar`.`relation_id`) SEPARATOR '{||}') `relations`
			FROM
			    `tc_communication_messages_addresses` `tc_cma` INNER JOIN
			    `tc_communication_messages_addresses_relations` `tc_cmar` ON
			        `tc_cmar`.`address_id` = `tc_cma`.`id` AND
			        `tc_cmar`.`relation` != ''
			WHERE
			    `tc_cma`.`message_id` = :message_id
			GROUP BY 
			    `tc_cma`.`id`
		";

		$addresses = \DB::getQueryRows($sql, ['message_id' => $messageId]);

		$final = [];
		foreach ($addresses as $address) {
			$final[$address['type']][] = explode('{|}', $address['relations']);
		}

		if (!isset($final['from']) || !isset($final['to'])) {
			throw new \RuntimeException('Missing relations for message "'.$messageId.'"');
		}

		return [reset($final['from']), $final['to']];
	}

}
