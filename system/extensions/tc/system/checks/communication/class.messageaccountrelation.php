<?php

/**
 * Verwendeter Account von Messages direkt in tc_communication_messages_relations schreiben,
 * notwendig für Rechte auf E-Mail-Accounts.
 */
class Ext_TC_System_Checks_Communication_MessageAccountRelation extends GlobalChecks {

	public function getTitle() {
		return 'Communication overview maintenance';
	}

	public function getDescription() {
		return 'Improved right management for communication overview.';
	}

	public function executeCheck() {

		set_time_limit(120);
		ini_set("memory_limit", '1024M');

		$sRelationClass = Factory::getClassName('Ext_TC_Communication_EmailAccount');

		$sSql = "
			SELECT
				`tc_cm`.id,
			    `tc_cm`.`direction`,
			   	`tc_cmar`.`relation_id`,
			   	`tc_cmi`.`account_id`
			FROM
				`tc_communication_messages` `tc_cm` LEFT JOIN
				`tc_communication_messages_relations` `tc_cmr` ON
					`tc_cmr`.`message_id` = `tc_cm`.`id` AND
					`tc_cmr`.`relation` = :class LEFT JOIN
				`tc_communication_messages_addresses` `tc_cma` ON
				    `tc_cm`.`direction` = 'out' AND
				    `tc_cma`.`message_id` = `tc_cm`.`id` AND
				    `tc_cma`.`type` = 'from' LEFT JOIN
				`tc_communication_messages_addresses_relations` `tc_cmar` ON
				    `tc_cmar`.`address_id` = `tc_cma`.`id` AND
				    `tc_cmar`.`relation` = :class LEFT JOIN 
				`tc_communication_messages_incoming` `tc_cmi` ON
				    `tc_cm`.`direction` = 'in' AND
					`tc_cmi`.`message_id` = `tc_cm`.`id`
			WHERE
				`tc_cmr`.`relation_id` IS NULL
			GROUP BY
				`tc_cm`.`id`
		";

		$aMessages = (array)DB::getQueryRows($sSql, ['class' => $sRelationClass]);

		$this->logInfo(count($aMessages). ' messages without account relation');

		if(!empty($aMessages)) {
			Util::backupTable('tc_communication_messages_relations');
		}

		foreach($aMessages as $aMessage) {

			if($aMessage['direction'] === 'out') {
				$iAccountId = $aMessage['relation_id'];
			} else {
				$iAccountId = $aMessage['account_id'];
			}

			if(!empty($iAccountId)) {

				DB::insertData('tc_communication_messages_relations', [
					'message_id' => $aMessage['id'],
					'relation' => $sRelationClass,
					'relation_id' => $iAccountId
				]);

				$this->logInfo('Account '.$iAccountId.' found for message '.$aMessage['id']);

			} else {

				$this->logError('No account found for message '.$aMessage['id']);

			}

		}

		return true;

	}

}
