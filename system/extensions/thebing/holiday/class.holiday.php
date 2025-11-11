<?php

/**
 * @property int $id 	
 * @property int $created 	
 * @property int $changed 	
 * @property int $active 	
 * @property int $creator_id 	
 * @property int $user_id 	
 * @property int $client_id 	
 * @property int $status 	
 * @property string $name 	
 * @property string $comment 	
 * @property date $date 	
 * @property int $annual
 */
class Ext_Thebing_Holiday_Holiday extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'kolumbus_holidays_public';

	protected $_aFormat = array(
			'changed' => array(
							'format' => 'TIMESTAMP'
			),
			'created' => array(
							'format' => 'TIMESTAMP'
			),
			'client_id' => array(
							'validate' => 'INT_POSITIVE',
							'required' => true
			),
			'name' => array(
							'required' => true
			),
			'date' => array(
							'validate' => 'DATE',
							'required' => true
			)
	);

	protected $_aJoinTables = array(
				'join_school'=> array(
					'table'=>'kolumbus_holidays_public_schools',
					'foreign_key_field'=>'school_id',
	 				'primary_key_field'=>'holiday_id'
				)
			);
	
	public static function isHoliday($mTime, $iSchoolId)
	{
		if(
			is_object($mTime) &&
			$mTime instanceof WDDate
		){
			$oDate = $mTime;
		}else{
			if(
				is_numeric($mTime)
			){
				$sDatePart = WDDate::TIMESTAMP;
			}elseif(
				WDDate::isDate($mTime, WDDate::DB_DATE)
			){
				$sDatePart = WDDate::DB_DATE;
			}else{
				return false;
			}
			
			$oDate = new WDDate($mTime, $sDatePart);
		}
		
		$iClientId	= (int)Ext_Thebing_System::getClientId();
		$sDbDate	= $oDate->get(WDDate::DB_DATE);
		
		$sSql = "
			SELECT
				`khp`.`id`
			FROM
				`kolumbus_holidays_public` `khp` INNER JOIN
				`kolumbus_holidays_public_schools` `khps` ON
					`khps`.`holiday_id` = `khp`.`id`
			WHERE
				`khp`.`client_id` = :client_id AND 
				`khps`.`school_id` = :school_id AND
				`khp`.`active` = 1 AND
				`khp`.`status` = 1 AND
				IF(
					`khp`.`annual` = 0,
					`khp`.`date` = :db_date,
					MONTH(`khp`.`date`) = MONTH(:db_date) AND
					DAY(`khp`.`date`) = DAY(:db_date) AND
					YEAR(`khp`.`date`) <= YEAR(:db_date)
				)
			GROUP BY
				`khp`.`id`
		";
		
		$aSql = array(
			'db_date'		=> $sDbDate,
			'school_id'		=> $iSchoolId,
			'client_id'		=> $iClientId,
		);
		
		$aHolidays = DB::getQueryCol($sSql, $aSql);
		
		if(
			empty($aHolidays)
		){
			return false;
		}else{
			return true;
		}
	}

}