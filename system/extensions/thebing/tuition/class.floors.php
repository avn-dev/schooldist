<?php

class Ext_Thebing_Tuition_Floors extends Ext_Thebing_Basic
{
	// Tabellenname
	protected $_sTable = 'kolumbus_school_floors';

	// Tabellenalias
	protected $_sTableAlias = 'ksf';

	protected $_aFormat = array(

		'title' => array(
			'required' => true
		),
		'building_id' => array(
			'required'	=> true,
			'validate'	=> 'INT_POSITIVE'
		),

	);

	public function getListWithBuildings() {
		
		$oSchool	= Ext_Thebing_School::getSchoolFromSession();
		$iSchoolId	= $oSchool->id;

		$sSql		= "
			SELECT
				`ksf`.`id`,`ksf`.`title` `floor`, `ksb`.`title` `building`
			FROM
				`kolumbus_school_floors` `ksf` INNER JOIN
				`kolumbus_school_buildings` `ksb` ON `ksf`.`building_id` = `ksb`.`id`
			WHERE
				`ksb`.`active` = 1 AND
				`ksb`.`school_id` = ".$iSchoolId." AND
				`ksf`.`active` = 1
			ORDER BY
				`ksb`.`title` ASC, `ksf`.`title` ASC
		";
		$aBack		= array();

		$aResult = DB::getPreparedQueryData($sSql, array());
		if(is_array($aResult)) {
			foreach($aResult as $aData) {
				$aBack[$aData['id']] = $aData['building'].', '.$aData['floor'];
			}
		}

		return $aBack;
	}

	public function getListWithBuildingsByClassrooms($iSchoolId=null) {

		if($iSchoolId === null) {
			$oSchool	= Ext_Thebing_School::getSchoolFromSession();
			$iSchoolId	= $oSchool->id;
		}

		$sSql		= "
			SELECT
				`ksf`.`id`,
				`ksf`.`title` `floor`, 
				`ksb`.`title` `building`
			FROM
				`kolumbus_classroom` `kc` JOIN
				`kolumbus_school_floors` `ksf` ON
					`kc`.`floor_id` = `ksf`.`id` JOIN
				`kolumbus_school_buildings` `ksb` ON 
					`ksf`.`building_id` = `ksb`.`id` LEFT JOIN
				`ts_schools_classrooms_usage` `ts_scu` ON
					`ts_scu`.`classroom_id` = `kc`.`id`
			WHERE
				`kc`.`active` = 1 AND
				`ksb`.`active` = 1 AND
				`ksf`.`active` = 1 AND
				(
					`kc`.`idSchool` = :school_id OR
					`ts_scu`.`school_id` = :school_id
				)
			GROUP BY
				`ksf`.`id`
			ORDER BY
				`ksb`.`title` ASC, 
				`ksf`.`title` ASC
		";
		
		$aBack = [];

		$aResult = DB::getPreparedQueryData($sSql, ['school_id'=>(int)$iSchoolId]);
		
		if(is_array($aResult)) {
			foreach($aResult as $aData) {
				$aBack[$aData['id']] = $aData['building'].', '.$aData['floor'];
			}
		}

		return $aBack;
	}
	
	public function getRoomsCount()
	{
		$sSql = "
			SELECT
				COUNT(*)
			FROM
				`kolumbus_classroom`
			WHERE
				`floor_id`	= :floor_id AND
				`active`	= 1
		";

		$aSql = array(
			'floor_id' => $this->id
		);

		$iCount = DB::getQueryOne($sSql, $aSql);

		return $iCount;
	}

	public function  delete($bLog = true)
	{
		$iRooms = $this->getRoomsCount();
		if( 0 < $iRooms )
		{
			return array(L10N::t('Es existieren noch Klassenzimmer zu dieser Etage, bitte zuerst alle löschen!', 'Thebing » Tuition » Resources » Buildings'));
		}
		else
		{
			return parent::delete($bLog);
		}
	}

	/**
	 * @return Ext_Thebing_Tuition_Buildings
	 */
	public function getBuilding()
	{
		$iBuildingId = (int)$this->building_id;
		$oBuilding = Ext_Thebing_Tuition_Buildings::getInstance($iBuildingId);
		return $oBuilding;
	}
}