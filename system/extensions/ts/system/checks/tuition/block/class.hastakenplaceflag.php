<?php


class Ext_TS_System_Checks_Tuition_Block_HasTakenPlaceFlag extends GlobalChecks {

    /**
     * @return string
     */
    public function getTitle() {
        return 'Update Tuition Block database';
    }

    /**
     * @return string
     */
    public function getDescription() {
        return 'Mark existing block as "has taken place"';
    }

    /**
     * - Raum des Blockes in kolumbus_tuition_blocks_to_rooms eintragen
     * - Raum des Blockes in kolumbus_tuition_blocks_inquiries_courses eintragen
     *
     * @return boolean
     */
    public function executeCheck() {

        $blockIds = $this->getBlocksWithoutFlag();
        if(empty($blockIds)) {
            return true;
        }

        $backup = [
            \Util::backupTable('ts_tuition_blocks_daily_units'),
        ];

        if(in_array(false, $backup)) {
            __pout('Backup error');
            return false;
        }

		foreach ($blockIds as $id) {
			\Core\Entity\ParallelProcessing\Stack::getRepository()
				->writeToStack('ts-tuition/block-status-flag', ['id' => $id], 10);
		}

        return true;
    }


	private function getBlocksWithoutFlag(): ?array
	{
		$currentWeek = \Carbon\Carbon::now()->startOfWeek(1);
		$sql = "
			SELECT
				`ktb`.`id`
			FROM 
			    `kolumbus_tuition_blocks` `ktb` LEFT JOIN
			    `ts_tuition_blocks_daily_units` `ts_tbdd` ON 
			    	`ts_tbdd`.`block_id` = `ktb`.`id` AND
			    	`ts_tbdd`.`state` IS NOT NULL
				WHERE
				    `ktb`.`active` = 1 AND
				    `ktb`.`week` <= :week AND
				    `ts_tbdd`.`state` IS NULL
		";

		$blockIds = \DB::getQueryCol($sql, ['week' => $currentWeek->toDateString()]);

		return $blockIds;
	}

}
