<?php

class Ext_TC_Communication_MessageRepository extends WDBasic_Repository {

	public function searchByEntityRelation(WDBasic $oEntity) {

		$sSql = "
			SELECT
				`tc_cm`.*
			FROM
				`tc_communication_messages` `tc_cm` JOIN
				`tc_communication_messages_relations` `tc_cmr` ON
					`tc_cmr`.`message_id` = `tc_cm`.`id`
			WHERE
				`tc_cmr`.`relation` = :class AND
				`tc_cmr`.`relation_id` = :id
		";

		$aResults = (array)DB::getQueryRows($sSql, [
			'id' => $oEntity->id,
			'class' => get_class($oEntity)
		]);

		$aEntities = array();
		if(is_array($aResults)) {
			$aEntities = $this->_getEntities($aResults);
		}

		return $aEntities;
	}

    public function searchLastMessagesForEntity(WDBasic $oEntity, int $iCount, array $aTypes = ['email', 'sms'], array $aDirections = ['in', 'out']) {

        $sSql = "
			SELECT
				`tc_cm`.*
			FROM
				`tc_communication_messages` `tc_cm` JOIN
				`tc_communication_messages_relations` `tc_cmr` ON
					`tc_cmr`.`message_id` = `tc_cm`.`id`
			WHERE
				`tc_cmr`.`relation` = :class AND
				`tc_cmr`.`relation_id` = :id AND 
				`tc_cm`.`direction` IN (:directions) AND				
				`tc_cm`.`type` IN (:types)				
			ORDER BY 
			    `tc_cm`.`date` DESC 
			LIMIT
			    0, :limit
			    
		";

        $aResults = (array)DB::getQueryData($sSql, [
            'id' => $oEntity->getId(),
            'class' => get_class($oEntity),
            'types' => $aTypes,
            'directions' => $aDirections,
            'limit' => $iCount
        ]);

        $aEntities = [];
        if(is_array($aResults)) {
            $aEntities = $this->_getEntities($aResults);
        }

        return $aEntities;
    }

    /**
     * Sucht für die übergebenen Imap-Message-Ids die entsprechenden Entitäten (falls vorhanden)
     *
     * @param Ext_TC_Communication_EmailAccount $account
     * @param \Illuminate\Support\Collection $imapMessageIds
     * @return \Illuminate\Support\Collection
     */
    public function findByImapMessageIds(\Ext_TC_Communication_EmailAccount $account, \Illuminate\Support\Collection $imapMessageIds) {

        $entities = collect();

        $chunks = $imapMessageIds->chunk(500);

        foreach ($chunks as $chunkIds) {
            $messages = \DB::table('tc_communication_messages_incoming as tc_cmi')
                ->select(['tc_cm.*', 'tc_cmi.imap_message_id'])
                ->join('tc_communication_messages as tc_cm', function ($join) {
                    $join->on('tc_cm.id', '=', 'tc_cmi.message_id');
                })
                ->where('tc_cmi.account_id', $account->id)
                ->whereIn('tc_cmi.imap_message_id', $chunkIds)
                ->get()
                ->mapWithKeys(fn ($message) => [
                    $message['imap_message_id'] => Factory::executeStatic(Ext_TC_Communication_Message::class, 'getObjectFromArray', [\Illuminate\Support\Arr::except($message, ['imap_message_id'])])
                ]);

            $entities = $entities->merge($messages);
        }

         return $entities;
    }

}
