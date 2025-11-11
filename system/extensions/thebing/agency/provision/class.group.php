<?php

class Ext_Thebing_Agency_Provision_Group extends Ext_Thebing_Basic {

	protected $_sTable = 'ts_agencies_to_commission_categories';

	protected $_sTableAlias = 'kapg';

	protected $_aFormat = array(
			'changed' => array(
							'format' => 'TIMESTAMP'
			),
			'created' => array(
							'format' => 'TIMESTAMP'
			),
			'valid_from' => array(
							'format' => 'DATE'
			)
	);

	public function __set($name, $value) {
		
		if(
			$name === 'school_id' && 
			$value === '0'
		) {
			$value = null;
		}
		
		parent::__set($name, $value);
		
	}
	
	public function validate($bThrowExceptions = false) {

		$aErrors = parent::validate($bThrowExceptions);

		// Prüfen, ob "Gültig ab" nach dem aktuellsten "Gültig ab" liegt
		$iLatestEntry = $this->getLatestEntry();

		if($iLatestEntry) {
			$oLatestEntry = self::getInstance($iLatestEntry);

			$oDate = new WDDate($oLatestEntry->valid_from, WDDate::DB_DATE);
			$iCompare = $oDate->compare($this->valid_from, WDDate::DB_DATE);

			if($iCompare >= 0) {
				if($aErrors === true) {
					$aErrors = array();
				}
				$aErrors['kapg.valid_from'][] = 'Der Wert in Feld "Gültig ab" ist zu klein!';
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
						id
					FROM
						`ts_agencies_to_commission_categories`
					WHERE
						`active` = 1 AND
						`agency_id` = :agency_id AND
						`valid_until` != '0000-00-00'
					ORDER BY
						`valid_until` DESC
					LIMIT 1
						";
			$aSql = array('agency_id'=>$this->agency_id);
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

		$latestEntry = self::query()
			->select('id')
			->where('active', '=', 1)
			->where('agency_id', '=', $this->agency_id)
			->where(function($query) {
				$query->where('school_id', '=', $this->school_id)
					->orWhere('school_id', '=', null);
			})
			->where('valid_until', '=', '0000-00-00')
			->where('id', '!=', $this->id)
			->pluck('id')->first();

		return $latestEntry;
	}

	/**
	 * @return Ext_Thebing_Provision_Group|null
	 */
	public function getProvisionGroup(){
		if($this->group_id > 0){
			return Ext_Thebing_Provision_Group::getInstance($this->group_id);
		}else{
			return null;
		}
	}

	/**
	 * Analog zu Ext_TC_Validity
	 * @return Ext_Thebing_Provision_Group
	 */
	public function getItem() {
		return $this->getProvisionGroup();
	}
	
	/**
	 * Analog zu Ext_TC_Validity
	 * @return Ext_Thebing_Agency
	 */
	public function getParent() {
		return $this->getAgency();
	}
	
	/**
	 * @return Ext_Thebing_Agency|null
	 */
	public function getAgency() {

		if($this->agency_id > 0) {
			return Ext_Thebing_Agency::getInstance($this->agency_id);
		}

		return null;

	}

}