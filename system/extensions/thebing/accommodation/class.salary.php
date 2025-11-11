<?php

/**
* @property $id 
* @property $changed 	
* @property $created 	
* @property $active 	
* @property $creator_id 	
* @property $user_id 	
* @property $accommodation_id 	
* @property $costcategory_id 	
* @property $valid_from 	
* @property $valid_until 	
* @property $comment 	
* @property $salary 	
* @property $salary_period 	
*/

class Ext_Thebing_Accommodation_Salary extends Ext_Thebing_Basic {

	protected $_sTable = 'kolumbus_accommodations_salaries';
	protected $_sTableAlias = 'kts';

	protected $_aFormat = array(
		'accommodation_id' => array(
			'required' => true
			),
		'valid_from' => array(
			'validate' => 'DATE'
			),
		'costcategory_id' => array(
			'validate' => 'REGEX',
			'validate_value' => '\-?[0-9]+'
			)
	);

	public function  __get($sName) {

		if($sName == 'accommodation_name') {
			if($this->_aData['accommodation_id'] > 0) {
				$oAccommodation = Ext_Thebing_Accommodation::getInstance($this->_aData['accommodation_id']);
				$sValue = $oAccommodation->ext_33;
			} else {
				$sValue = '';
			}
		} else {
			$sValue = parent::__get($sName);
		}

		return $sValue;
	}
	
	public function validate($bThrowExceptions = false) {

		$aErrors = parent::validate($bThrowExceptions);

		if($aErrors === true) {
			$aErrors = array();
		}

		/*
		 * Prüfen, ob "Gültig ab" nach dem aktuellsten "Gültig ab" liegt - aber nur, wenn der eingegebene Wert
		 * ein gültiges Datum ist
		 */
		if(
			empty($aErrors['valid_from']) &&
			empty($aErrors['kts.valid_from'])
		) {

			$iLatestEntry = $this->getLatestEntry();

			if($iLatestEntry) {

				$oLatestEntry = self::getInstance($iLatestEntry);

				$oDate = new WDDate($oLatestEntry->valid_from, WDDate::DB_DATE);
				$iCompare = $oDate->compare($this->valid_from, WDDate::DB_DATE);

				if($iCompare >= 0) {
					$aErrors['valid_from'][] = 'Der Wert in Feld "Gültig ab" ist zu klein!';
				}

			}

		}

		$aIntersectionData	= (array)$this->getIntersectionData();

		if(
			array_key_exists('costcategory_id', $aIntersectionData) ||
			$this->active == 0
		){
			$aPayments			= $this->getPayments();

			if(!empty($aPayments)){
				$aErrors['costcategory_id'][] = 'COSTCATEGORY_NOT_CHANGABLE';
			}
		}

		if(empty($aErrors)) {
			return true;
		}

		return $aErrors;
	}

	public function save($bSetLastEntry=true) {

		// Alter oder neuer Eintrag
		if($this->_aData['id'] > 0) {
			$bInsert = false;
		} else {
			$bInsert = true;
		}

		// Eintrag soll gelöscht werden
		// Gültig bis Datum bei dem letzten Eintrag anpassen
		if($this->active == 0) {
			$sSql = "
					SELECT
						`id`
					FROM
						`kolumbus_accommodations_salaries`
					WHERE
						`active` = 1 AND
						`accommodation_id` = :accommodation_id AND
						`valid_until` != '0000-00-00'
					ORDER BY
						`valid_until` DESC
					LIMIT 1
						";
			$aSql = array('accommodation_id'=>$this->accommodation_id);
			$iLastEntry = DB::getQueryOne($sSql, $aSql);

			if($iLastEntry > 0) {
				$oLastEntry = self::getInstance($iLastEntry);
				$oLastEntry->valid_until = '0000-00-00';
				$oLastEntry->save(false);
			}

			$bSetLastEntry = false;

		}

		parent::save();

		if(
			$bSetLastEntry &&
			$this->id > 0
		) {
			// Gültig bis Datum bei dem letzten Eintrag anpassen
			$iLastEntry = $this->getLatestEntry();

			if($iLastEntry > 0) {
				$oDate = new WDDate($this->valid_from, WDDate::DB_DATE);
				$oDate->sub(1, WDDate::DAY);

				$oLastEntry = self::getInstance($iLastEntry);
				$oLastEntry->valid_until = $oDate->get(WDDate::DB_DATE);
				$oLastEntry->save(false);
			}

		}

		return $this;
	}

	public function getLatestEntry() {

		$sSql = "
				SELECT
					`id`
				FROM
					`kolumbus_accommodations_salaries`
				WHERE
					`active` = 1 AND
					`accommodation_id` = :accommodation_id AND
					`valid_until` = '0000-00-00' AND
					`id` != :id
				ORDER BY
					`valid_from` DESC
				LIMIT 1
					";
		$aSql = array('accommodation_id'=>$this->accommodation_id, 'id'=>$this->id);
		$iLastEntry = DB::getQueryOne($sSql, $aSql);

		return $iLastEntry;
	}

	public static function getPeriods($sPeriod=false) {

		$aPeriods = array();
		$aPeriods['month'] = L10N::t('Monat', 'Thebing » Tuition » Teachers');

		if($sPeriod) {
			return $aPeriods[$sPeriod];
		} else {
			return $aPeriods;
		}

	}

	public function getPayments(){

		$dUntil = $this->valid_until;

		if($dUntil == '0000-00-00' || empty($dUntil)){
			$oDate = new WDDate();
			$oDate->add(10, WDDate::YEAR);
			$dUntil = $oDate->get(WDDate::DB_DATE);	
		}

		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_accommodations_payments` `kap` INNER JOIN
				`customer_db_4` `cdb4` ON
					`cdb4`.`id` = `kap`.`accommodation_id` AND
					`cdb4`.`active` = 1
			WHERE
				`kap`.`accommodation_id` = :accommodation_id AND
				`kap`.`timepoint` BETWEEN :valid_from AND :valid_until AND
				`kap`.`active` = 1
		";

		$aSql = array(
			'accommodation_id'			=> (int)$this->accommodation_id,
			'valid_from'				=> $this->valid_from,
			'valid_until'				=> $dUntil,
		);

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		return $aResult;
	}

}