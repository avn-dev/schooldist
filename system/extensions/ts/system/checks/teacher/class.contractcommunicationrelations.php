<?php

class Ext_TS_System_Checks_Teacher_ContractCommunicationRelations extends GlobalChecks
{
	public function getTitle()
	{
		return 'Teacher contracts';
	}
	
	public function getDescription()
	{
		return 'Corrects communication history for teacher contracts';
	}
	
	public function executeCheck() {
		
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		$messages = $this->getTeacherContractsMails();

		if (empty($messages)) {
			return true;
		}

		if (!\Util::backupTable('tc_communication_messages_relations')) {
			__pout('Backup error');
			return false;
		}

		foreach ($messages as $message) {
			if ($message['teacher_id'] > 1) {
				$this->addProcess($message);
			}
		}

		return true;
	}

	public function executeProcess(array $data)
	{
		$teacherRelation = $this->getTeacherRelation($data['message_id']);

		if (!$teacherRelation) {
			\DB::insertData('tc_communication_messages_relations', [
				'message_id' => $data['message_id'],
				'relation' => \Ext_Thebing_Teacher::class,
				'relation_id' => $data['teacher_id'],
			]);
		} else if ($teacherRelation['relation_id'] != $data['teacher_id']) {
			\DB::updateData('tc_communication_messages_relations', ['relation_id' => $data['teacher_id']], $teacherRelation);
		}

		return true;
	}

	private function getTeacherContractsMails(): array
	{
		$sql = "
			SELECT
				`tc_cmr`.`message_id`,
				`ts_c`.`item_id` `teacher_id`
			FROM
			    `tc_communication_messages_relations` `tc_cmr` INNER JOIN 
			    `tc_communication_messages` `tc_cm` ON 
			        `tc_cm`.`id` = `tc_cmr`.`message_id` AND
			        `tc_cm`.`active` = 1 INNER JOIN 
				`kolumbus_contracts_versions` `ts_cv` ON
					`ts_cv`.`id` = `tc_cmr`.`relation_id` AND  
					`ts_cv`.`active` = 1 INNER JOIN 
				`kolumbus_contracts` `ts_c` ON
					`ts_c`.`id` = `ts_cv`.`contract_id` AND
					`ts_c`.`item` = 'teacher' AND
					`ts_c`.`active` = 1
			WHERE
			    `tc_cmr`.`relation` = :relation
		";

		return (array) \DB::getPreparedQueryData($sql, ['relation' => \Ext_Thebing_Contract_Version::class]);
	}

	private function getTeacherRelation(int $messageId): ?array
	{
		$sql = "
			SELECT
				*
			FROM
			    `tc_communication_messages_relations`
			WHERE
			    `message_id` = :message_id AND
			    `relation` = :relation
			LIMIT 1
		";

		return \DB::getQueryRow($sql, [
			'relation' => \Ext_Thebing_Teacher::class,
			'message_id' => $messageId
		]);
	}
}