<?php

class Ext_TS_System_Checks_Specials2024 extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Update specials structure';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = '...';
		return $sDescription;
	}

	public function executeCheck() {
		
		\Util::backupTable('ts_specials');
		
		$columns = \DB::describeTable('ts_specials', true);
		
		if(
			isset($columns['from']) &&
			isset($columns['to'])
		) {

			$specials = \DB::getQueryRows("SELECT * FROM `ts_specials`");

			foreach($specials as $special) {

				$sqlParam = [
					'id' => $special['id']
				];
				// Service
				if($special['period_type'] == 1) {
					$sqlParam['field_from'] = 'service_from';
					$sqlParam['field_until'] = 'service_until';
					$sqlParam['value_from'] = $special['from'];
					$sqlParam['value_until'] = $special['to'];
				// Created
				} elseif($special['period_type'] == 2) {
					$sqlParam['field_from'] = 'created_from';
					$sqlParam['field_until'] = 'created_until';
					$sqlParam['value_from'] = $special['from'];
					$sqlParam['value_until'] = $special['to'];				
				} else {
					// Diesen Fall dürfte es nicht geben
					continue;
				}

				\DB::executePreparedQuery("UPDATE `ts_specials` SET changed = changed, #field_from = :value_from, #field_until = :value_until WHERE id = :id", $sqlParam);

			}

			\DB::executeQuery("ALTER TABLE `ts_specials` DROP `from`, DROP `to`, DROP `period_type`");
			
		}

		if(isset($columns['direct_booking'])) {
			
			$specials = \DB::getQueryRows("SELECT * FROM `ts_specials`");

			foreach($specials as $special) {

				$sqlParam = [
					'id' => $special['id'],
					'direct_bookings' => 0,
					'agency_bookings' => 0,
				];

				if($special['direct_booking'] == 1) {
					$sqlParam['direct_bookings'] = 1;
				} else {
					$sqlParam['agency_bookings'] = 1;	
				}

				\DB::executePreparedQuery("UPDATE `ts_specials` SET changed = changed, `direct_bookings` = :direct_bookings, `agency_bookings` = :agency_bookings WHERE id = :id", $sqlParam);

			}
			
			\DB::executeQuery("ALTER TABLE `ts_specials` DROP `direct_booking`");
			
		}
		
		if(isset($columns['school_id'])) {

			$specials = \DB::getQueryRows("SELECT * FROM `ts_specials`");

			foreach($specials as $special) {

				\DB::insertData('ts_specials_schools', ['special_id' => $special['id'], 'school_id' => $special['school_id']]);
				
			}
			
			\DB::executeQuery("ALTER TABLE `ts_specials` DROP `school_id`");
						
		}
		
		return true;
	}
		
}
