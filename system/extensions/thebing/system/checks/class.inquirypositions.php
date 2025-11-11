<?php


class Ext_Thebing_System_Checks_InquiryPositions extends GlobalChecks
{
	public function isNeeded(){
		global $user_data;

		return true;

	}
	
	public function executeCheck(){
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		Ext_Thebing_Util::backupTable('kolumbus_positions_order');

		$sSql = "SELECT
						*
					FROM
						`kolumbus_positions_order`
				";
		$aSql = array();
		
		$aResult = DB::getPreparedQueryData($sSql, $aSql);
		
		foreach((array)$aResult as $aData){
			
			$sName = '';
			
			switch($aData['position']){
				case 'transfer':
					$sName = '{transfer}: {from} - {to} ({weekday} {date} {time})';
					break;
				case 'insurance':
					$sName = '{insurance} {from} - {until}';
					break;
				case 'additional_course_cost':
					$sName = '{name} {description}';
					break;
				case 'additional_accommodation_cost':
					$sName = '{name} {description}';
					break;
				case 'additional_general_cost':
					$sName = '{name}';
					break;
				case 'extra_week':
					$sName = '{weeks}';
					break;
				case 'extra_night':
					$sName = '{nights}';
					break;
				case 'course':
					$sName = '{weeks_units} {course} {from} - {until}';
					break;
				case 'accommodation':
					$sName = '{weeks} {accommodation} {category} ({roomtype}/{meal}) {from} - {until}';
					break;
			}
			
			$sSql = "UPDATE
							`kolumbus_positions_order`
						SET
							`title` = :title
						WHERE
							`id` = :id
			";
			
			$aSql = array();
			$aSql['title'] = $sName;
			$aSql['id'] = (int)$aData['id'];
			
			DB::executePreparedQuery($sSql, $aSql);
			
		}

		return true;

	}

	public function getTitle()
	{
		return 'Inquiry Positions';
	}

	public function getDescription()
	{
		return 'Import invoice line items into new Database structure.';
	}

}