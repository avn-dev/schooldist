<?php

class Ext_Thebing_Accommodation_AllocationRepository extends WDBasic_Repository {
	
	/**
	 * Liefert alle, als noch nicht vollständig bezahlt markierte Zuweisungen, deren Start in der Vergangenheit liegen
	 * 
	 * @param Ext_Thebing_School $oSchool
	 * @param DateTime|null $oDate
	 * @return Ext_Thebing_Accommodation_Allocation[]
	 */
	public function getNotCompletePayedAllocations(Ext_Thebing_School $oSchool, \DateTime $oDate = null) {

		$sTableName = $this->_oEntity->getTableName();
		$sTableAlias = $this->_oEntity->getTableAlias();

		$sSql = "
			SELECT
				#table_alias.*
			FROM
				#table #table_alias JOIN
				`ts_inquiries_journeys_accommodations` `ts_ija` ON
					#table_alias.`inquiry_accommodation_id` = `ts_ija`.`id` AND
					`ts_ija`.`active` = 1 JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ija`.`journey_id` = `ts_ij`.`id` AND
					`ts_ij`.`active` = 1 AND
					`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."'
			WHERE
				`ts_ij`.`school_id` = :school_id AND
				#table_alias.`payment_generation_completed` IS NULL AND
				#table_alias.`active` = 1 AND
				#table_alias.`status` = 0
		";
		$aSql = array(
			'table' => $sTableName,
			'table_alias' => $sTableAlias,
			'school_id' => (int)$oSchool->id
		);

		if($oDate !== null) {
			$sSql .= " AND #table_alias.`until` > :date";
			$aSql['date'] = $oDate->format('Y-m-d');
		}
		
		$aResults = DB::getQueryRows($sSql, $aSql);

		$aEntities = array();
		if(is_array($aResults)) {
			$aEntities = $this->_getEntities($aResults);
		}

		return $aEntities;
	}

	public function getAdjacentAllocations() {
		
		/* @var $oInquiry \Ext_TS_Inquiry */
		$oInquiry = $this->_oEntity->getInquiry();

		$aSql = [
			'inquiry_id' => (int)$oInquiry->id,
			'id' => (int)$this->_oEntity->id,
			'journey_accommodation_id' => (int)$this->_oEntity->inquiry_accommodation_id,
			'room_id' => (int)$this->_oEntity->room_id,
			'bed' => (int)$this->_oEntity->bed
		];

		$sSql = "
				SELECT
					`kaal`.*
				FROM
					`kolumbus_accommodations_allocations` AS `kaal` INNER JOIN
					`ts_inquiries_journeys_accommodations` `ts_ija` ON
						`ts_ija`.`id` = `kaal`.`inquiry_accommodation_id` INNER JOIN
					`ts_inquiries_journeys` `ts_ij` ON
						`ts_ij`.`id` = `ts_ija`.`journey_id` AND
						`ts_ij`.`active` = 1 AND
						`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
						`ts_ij`.`inquiry_id` = :inquiry_id INNER JOIN
					`ts_inquiries_journeys_accommodations` `ts_ija2` ON
						`ts_ija2`.`id` = :journey_accommodation_id
				WHERE
					`kaal`.`active` = 1 AND
					`kaal`.`status` = 0 AND
					`kaal`.`id` != :id AND
					`kaal`.`room_id` = :room_id AND
					/* Hier muss die Verpflegung verglichen werden, da die Kosten pro Raumkategorie/Verpflegung gelten */
					`ts_ija`.`meal_id` = `ts_ija2`.`meal_id` AND
					`kaal`.`bed` = :bed
				ORDER BY
					`kaal`.`from`
				";

		$aResults = DB::getQueryRows($sSql, $aSql);

		$aEntities = array();
		if(is_array($aResults)) {
			$aEntities = $this->_getEntities($aResults);
		}

		return $aEntities;
	}

	/**
	 * @param \Carbon\Carbon $oDate
	 * @return Ext_Thebing_Accommodation_Allocation[]
	 */
	public function getExpiredReservations(\Carbon\Carbon $oDate) {

		$sSql = "
			SELECT
				*
			FROM	
				`kolumbus_accommodations_allocations`
			WHERE
				`reservation` IS NOT NULL AND
				`reservation_date` <= :date AND
				`active` = 1 AND
				`status` = 0
			";

		$aSql = [
			'date' => $oDate->toDateString()
		];

		$aResults = DB::getQueryRows($sSql, $aSql);

		$aEntities = [];
		if(is_array($aResults)) {
			$aEntities = $this->_getEntities($aResults);
		}

		return $aEntities;
	}

}
