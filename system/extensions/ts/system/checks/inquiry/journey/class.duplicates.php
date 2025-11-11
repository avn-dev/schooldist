<?php

class Ext_TS_System_Checks_Inquiry_Journey_Duplicates extends GlobalChecks {

	public function getTitle() {
		return 'Check for journey duplicates';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {

		$backupSuccess = Ext_Thebing_Util::backupTable('ts_inquiries_journeys');
		if($backupSuccess == false) {
			__pout('Backup error!');
			return false;
		}
		
		DB::begin(__METHOD__);
		
		$sqlQuery = "SELECT GROUP_CONCAT(`id`) `ids`, COUNT(*) `c` FROM `ts_inquiries_journeys` WHERE active = 1 AND type & 2 GROUP BY `inquiry_id` HAVING c > 1";
		
		$items = (array)\DB::getQueryRows($sqlQuery);

		$this->logInfo('Items', [count($items)]);
		
		$tablesWithJourneyLink = [
			'tc_feedback_questionaries_processes',
			'tc_incomingfiles_to_journeys',
			'ts_enquiries_combinations',
			'ts_inquiries_journeys_accommodations',
			'ts_inquiries_journeys_activities',
			'ts_inquiries_journeys_additionalservices',
			'ts_inquiries_journeys_courses',
			'ts_inquiries_journeys_insurances',
			#'ts_inquiries_journeys_transfers',
			'ts_journeys_travellers_detail',
			#'ts_journeys_travellers_visa_data',
		];
		
		foreach($items as $item) {
			
			$ids = explode(',', $item['ids']);
			sort($ids, SORT_NUMERIC);

			foreach($ids as $index=>$id) {
				
				$empty = true;
				foreach($tablesWithJourneyLink as $table) {

					$sqlQueryCheck = "SELECT * FROM #table WHERE `journey_id` = :journey_id LIMIT 1";
					$check = DB::getQueryRow($sqlQueryCheck, ['table'=> $table, 'journey_id'=>$id]);
					
					if(!empty($check)) {
						$empty = false;
						unset($ids[$index]);
						break;
					}
					
				}
				
			}

			if(empty($ids)) {
				$this->logError('Journeys not empty', [$item['ids']]);
				__out('Both journeys not empty! ('.$item['ids'].')',1);
			}

			$lastId = end($ids);
			
			if(!empty($lastId)) {
				DB::executePreparedQuery("UPDATE `ts_inquiries_journeys` SET `changed` = `changed`, `active` = 0 WHERE `id` = :journey_id", ['journey_id'=>$lastId]);
			}
			
		}

		DB::commit(__METHOD__);
		
		$this->logInfo('Done');

		return true;
	}

}
