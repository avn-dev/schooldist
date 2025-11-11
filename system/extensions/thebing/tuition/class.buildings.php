<?php

/**
* @property $id 
* @property $changed 	
* @property $created 	
* @property $active 	
* @property $creator_id 	
* @property $user_id 	
* @property $school_id 	
* @property $title
*/

class Ext_Thebing_Tuition_Buildings extends Ext_Thebing_Basic
{
	// Tabellenname
	protected $_sTable = 'kolumbus_school_buildings';

	protected $_aFormat = array(

		'title' => array(
			'required' => true
		),
		'school_id' => array(
			'required' => true,
			'validate' => 'INT_POSITIVE'
		)

	);
	
	public function getFloorsCount()
	{
		$sSql = "
			SELECT
				COUNT(*)
			FROM
				`kolumbus_school_floors`
			WHERE
				`building_id`	= :building_id AND
				`active`		= 1
		";

		$aSql = array(
			'building_id' => $this->id
		);

		$iCount = DB::getQueryOne($sSql, $aSql);

		return $iCount;
	}

	public function delete($bLog = true)
	{
		$iFloors = $this->getFloorsCount();
		if( 0 < $iFloors )
		{
			return array(L10N::t('Es existieren noch Etagen zu diesem Gebäude, bitte zuerst alle löschen!', 'Thebing » Tuition » Resources » Buildings'));
		}
		else
		{
			return parent::delete($bLog);
		}
	}

	public function getName() {
		return $this->title;
	}
}