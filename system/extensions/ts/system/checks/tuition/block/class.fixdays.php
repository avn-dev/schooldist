<?php


class Ext_TS_System_Checks_Tuition_Block_FixDays extends GlobalChecks {

    /**
     * @return string
     */
    public function getTitle() {
        return 'Fix block days';
    }

    /**
     * @return string
     */
    public function getDescription() {
        return '...';
    }

    /**
     * @return boolean
     */
    public function executeCheck() {
 
		set_time_limit(28800);
		ini_set("memory_limit", '16G');
		
        $backup = [
            \Util::backupTable('kolumbus_tuition_blocks_days'),
            \Util::backupTable('kolumbus_tuition_blocks_to_rooms'),
        ];

        if(in_array(false, $backup)) {
            __pout('Backup error');
            return false;
        }
		
		\DB::addField('kolumbus_tuition_blocks', 'fix_days', 'TINYINT(1) DEFAULT 0');
		
		$errors = \DB::getQueryRows("
			SELECT 
				*,
				json_value(data, '$.id') attendance_id 
			FROM 
				`core_parallel_processing_stack_error` 
			WHERE 
				type = 'core/check-handler' AND data LIKE '%Ext_TS_System_Checks_Tuition_Attendance_CacheDurations%'
		");
		
		$blocks = \DB::getQueryPairs("
			SELECT 
				kolumbus_tuition_blocks.id id1,
				kolumbus_tuition_blocks.id id2
			FROM 
				`kolumbus_tuition_blocks` LEFT JOIN 
				kolumbus_tuition_blocks_days ON 
					kolumbus_tuition_blocks_days.block_id = kolumbus_tuition_blocks.id LEFT JOIN 
				kolumbus_tuition_blocks_to_rooms ON 
					kolumbus_tuition_blocks_to_rooms.block_id = kolumbus_tuition_blocks.id 
			WHERE 
				(kolumbus_tuition_blocks_days.block_id IS NULL OR kolumbus_tuition_blocks_to_rooms.block_id IS NULL) AND 
				kolumbus_tuition_blocks.active = 1 
			ORDER BY 
				`kolumbus_tuition_blocks_days`.`day` ASC
		");

		foreach($errors as $error) {
			if(empty($error['attendance_id'])) {
				continue;
			} 
			$attendance = Ext_Thebing_Tuition_Attendance::getInstance($error['attendance_id']);
			$allocation = $attendance->getAllocation();
			$blocks[$allocation->block_id] = $allocation->block_id;
		}
	
		$this->logInfo('Blocks', [count($blocks)]);
		
		#$blocks = array_slice($blocks, 0, 1000);
		arsort($blocks);
		
		$block = Ext_Thebing_School_Tuition_Block::getInstance(reset($blocks));
		$this->logInfo('First block', [$block->aData]);
	
		$storageLogsDir = Util::getDocumentRoot().'storage/logs/';
 
		if(!is_file($storageLogsDir.'blocks_20240422.txt')) {
			unlink($storageLogsDir.'blocks_20240422.txt');
			$cmd = sprintf('cd %s; find . -type f -name "entity*" | xargs zgrep -A 4 -B 0 "DEBUG: Ext_Thebing_School_Tuition_Block::" > blocks_20240422.txt', $storageLogsDir);
			\Update::executeShellCommand($cmd);
		}

//		$blocks = \DB::getQueryCol("SELECT id FROM kolumbus_tuition_blocks WHERE active = 1 AND fix_days = 0 ORDER BY id DESC LIMIT 500");
		
		$processedBlocks = 0;
		foreach($blocks as $blockId) {

			$check = \DB::getQueryOne("SELECT id FROM kolumbus_tuition_blocks WHERE active = 1 AND fix_days = 0 AND id = :id", ['id'=>$blockId]);

			// Eintrag wurde schon bearbeitet
			if(empty($check)) {
				$this->logInfo('Skip block', [$blockId]);
				continue;
			}

			$cmd = sprintf('cd %s; find . -type f -name "blocks_20240422.txt" | xargs zgrep -A 4 -B 0 "DEBUG: Ext_Thebing_School_Tuition_Block::'.(int)$blockId.' \["', $storageLogsDir);
			$result = \Update::executeShellCommand($cmd);

			$lines = explode("\n", $result);

			$actions = [];
			$latestKey = null;
			foreach($lines as $line) {
				
				if(strpos($line, 'DEBUG') !== false) {
					
					$preg = preg_match("/\[([0-9]{4}\-[0-9]{2}\-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}).*\] Log\.DEBUG: Ext_Thebing_School_Tuition_Block::".(int)$blockId." \[\"(.*)\"\]/", $line, $match);

					if($preg == 1) {
						$latestKey = $match[1];
						$actions[$latestKey]['action'] = $match[2];
					}
					
				} elseif(strpos($line, 'INFO: VARS') !== false) {

					$preg = preg_match("/Log\.INFO: VARS (\{.*\})/", $line, $match);

					if($preg == 1) {
						
						$actions[$latestKey]['vars'] = json_decode($match[1], true);
						$actions[$latestKey]['task'] = $actions[$latestKey]['vars']['task'];
						if(
							$actions[$latestKey]['vars']['copy_last_week'] == 1 && 
							empty($actions[$latestKey]['task'])
						) {
							$actions[$latestKey]['task'] = 'copy_last_week';
						}
						
						
					}
					
				} elseif(strpos($line, 'Jointables') !== false) {

					$preg = preg_match("/Jointables (\{.*\})/", $line, $match);

					if($preg == 1) {
						$actions[$latestKey]['jointables'] = json_decode($match[1], true);
					}
					
				}

				if($latestKey) {
					$actions[$latestKey]['lines'][] = $line;
				}
				
				// Letzte Zeile zu diesem Eintrag
				if(strpos($line, 'INFO: VARS') !== false) {
					$latestKey = null;
				}
				
			}
						
			ksort($actions);

			foreach($actions as $index=>&$action) {

				if(
					$action['task'] != 'copyBlockChanges' &&
					$action['task'] != 'saveDialog' &&
					$action['task'] != 'copy_last_week'
				) {
					unset($actions[$index]);
					continue;
				}

				if(
					$action['action'] == 'ADDED' &&
					isset($action['jointables']['days']) &&
					isset($action['jointables']['rooms'])
				) {
					$action['days'] = $action['jointables']['days'];
					$action['rooms'] = $action['jointables']['rooms'];
				} elseif(
					isset($action['vars']['save']['blocks'][$blockId]['days']) &&
					isset($action['vars']['save']['blocks'][$blockId]['rooms'])
				) {
					$action['days'] = $action['vars']['save']['blocks'][$blockId]['days'];
					$action['rooms'] = $action['vars']['save']['blocks'][$blockId]['rooms'];
				} else {
					unset($actions[$index]);
					continue;
				}
				
			}
			
			if(!empty($actions)) {
				$lastLog = end($actions);
				#__out($lastLog);

				$currentDays = \DB::getJoinData('kolumbus_tuition_blocks_days', ['block_id' => $blockId], 'day');
				$currentRooms = \DB::getJoinData('kolumbus_tuition_blocks_to_rooms', ['block_id' => $blockId], 'room_id');

				$intersectDays = array_intersect($currentDays, $lastLog['days']);
				$intersectRooms = array_intersect($currentRooms, $lastLog['rooms']);

				if(
					count($intersectDays) != count($lastLog['days']) &&
					count($lastLog['days']) > count($currentDays)
				) {
					\DB::updateJoinData('kolumbus_tuition_blocks_days', ['block_id' => $blockId], $lastLog['days'], 'day');
					$this->logInfo('Update Days', [$currentDays, $lastLog['days']]);
				}

				if(
					count($intersectRooms) != count($lastLog['rooms']) &&
					count($lastLog['rooms']) > count($currentRooms)
				) {
					\DB::updateJoinData('kolumbus_tuition_blocks_to_rooms', ['block_id' => $blockId], $lastLog['rooms'], 'room_id');
					$this->logInfo('Update Rooms', [$currentRooms, $lastLog['rooms']]);
				}

				\DB::executePreparedQuery("UPDATE kolumbus_tuition_blocks SET changed = changed, fix_days = 1 WHERE id = :id", ['id'=> $blockId]);
				
			} else {
				
				$this->logInfo('Empty log', [$blockId]);
				
				\DB::executePreparedQuery("UPDATE kolumbus_tuition_blocks SET changed = changed, fix_days = 2 WHERE id = :id", ['id'=> $blockId]);
				
			}
				
			$processedBlocks++;
			
			if($processedBlocks > 1000) {
				break;
			}
			
		}
			
        return true;
    }

}
