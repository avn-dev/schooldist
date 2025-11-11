<?php

class Ext_TC_System_Checks_Communication_MissingAccountRelation extends GlobalChecks {

	public function getTitle()
    {
		return 'Communication';
	}

	public function getDescription()
    {
		return 'Adds missing message relations';
	}

    private function getRelationClass(): string
    {
        return Factory::getClassName('Ext_TC_Communication_EmailAccount');
    }

	public function executeCheck() {

		set_time_limit(120);
		ini_set("memory_limit", '1024M');

        $messages = $this->getMessagesWithoutAccountRelations();

        if (empty($messages)) {
            // nothing to do
            return true;
        }

        $backup = \Util::backupTable('tc_communication_messages_relations');
        if (!$backup) {
            __pout('Backup error');
            return false;
        }

        foreach ($messages as $messageId) {
            $this->addProcess(['message_id' => $messageId], 500);
            //$this->executeProcess(['message_id' => $messageId]);
        }

		return true;

	}

    public function executeProcess(array $data)
    {
        $messageId = (int)$data['message_id'];
        $relationClass = $this->getRelationClass();

        $currentRelations = (array)\DB::getQueryCol("SELECT `relation` FROM `tc_communication_messages_relations` WHERE `message_id` = :message_id", ['message_id' => $messageId]);

        if (in_array($relationClass, $currentRelations)) {
            return true;
        }

        $emailAccountId = (int)$this->searchEmailAccountForMessage($messageId);

        if ($emailAccountId > 0) {
            DB::insertData('tc_communication_messages_relations', [
                'message_id' => $messageId,
                'relation' => $relationClass,
                'relation_id' => $emailAccountId
            ]);

            $this->logInfo('Add message relation', ['message_id' => $messageId, 'relation' => $relationClass, 'relation_id' => $emailAccountId]);
        } else {
            $this->logError('Cannot find message account', ['message_id' => $messageId]);
        }
    }

    private function getMessagesWithoutAccountRelations()
    {
        $relationClass = $this->getRelationClass();

        $sql = "
            SELECT
                `tc_cm`.`id`
            FROM 
                `tc_communication_messages` `tc_cm` LEFT JOIN
                `tc_communication_messages_relations` `tc_cmr` ON
                    `tc_cmr`.`message_id` = `tc_cm`.`id` AND
                    `tc_cmr`.`relation` = :relation
            WHERE
                `tc_cm`.`active` = 1 AND
                `tc_cm`.`type` = 'email' AND
                `tc_cm`.`date` >= :date AND
                `tc_cmr`.`message_id` IS NULL
            ORDER BY 
                `tc_cm`.`id` DESC
        ";

        return (array)DB::getQueryCol($sql, [
			'relation' => $relationClass,
			'date' => \Carbon\Carbon::now()->subMonth()->format("Y-m-d H:i:s")
		]);
    }

    private function searchEmailAccountForMessage(int $messageId): ?int
    {
        $relationClass = $this->getRelationClass();

        $sql = "
            SELECT
			   	`tc_cmar`.`relation_id` `address_relation_id`,
			   	`tc_cmi`.`account_id` `incoming_id`
			FROM
				`tc_communication_messages` `tc_cm` LEFT JOIN
				`tc_communication_messages_relations` `tc_cmr` ON
					`tc_cmr`.`message_id` = `tc_cm`.`id` AND
					`tc_cmr`.`relation` = :relation LEFT JOIN
				`tc_communication_messages_addresses` `tc_cma` ON
				    `tc_cm`.`direction` = 'out' AND
				    `tc_cma`.`message_id` = `tc_cm`.`id` AND
				    `tc_cma`.`type` = 'from' LEFT JOIN
				`tc_communication_messages_addresses_relations` `tc_cmar` ON
				    `tc_cmar`.`address_id` = `tc_cma`.`id` AND
				    `tc_cmar`.`relation` = :relation LEFT JOIN 
				`tc_communication_messages_incoming` `tc_cmi` ON
					`tc_cmi`.`message_id` = `tc_cm`.`id`
			WHERE
			    `tc_cm`.`id` = :message_id AND
				`tc_cmr`.`relation_id` IS NULL
			GROUP BY
				`tc_cm`.`id`
        ";

        $result = (array)DB::getQueryRow($sql, ['relation' => $relationClass, 'message_id' => $messageId]);

        if ($result['incoming_id'] !== null) {
            return (int)$result['incoming_id'];
        } else if ($result['address_relation_id'] !== null) {
            return (int)$result['address_relation_id'];
        }

        return null;
    }

}
