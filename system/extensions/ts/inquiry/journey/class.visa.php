<?php

/**
 * @TODO Hier muss entweder $journey_id oder $traveller_id entfernt werden,
 *   - da es hier sonst eine zirkuläre Abhängigkeit mit der Inquiry gibt.
 *
 * @property integer $id
 * @property string $journey_id
 * @property string $traveller_id
 * @property string servis_id
 * @property string tracking_number
 * @property string $status
 * @property string $required
 * @property string $passport_number
 * @property string $passport_date_of_issue
 * @property string $passport_due_date
 * @property string date_from
 * @property string date_until
 *
 */
class Ext_TS_Inquiry_Journey_Visa extends Ext_Thebing_Basic {

	protected $_sTable = 'ts_journeys_travellers_visa_data';

	protected $_sTableAlias = 'ts_ijv';

	static protected $_aCache = array();

    protected $_aJoinedObjects = array(
		'traveller'		=> array(
			'class'				=> 'Ext_TS_Inquiry_Contact_Traveller',
			'key'				=> 'traveller_id',
			'type'				=> 'parent'
		)
	);

	protected $_aFormat = array( 
							'date_from' => array(
								'validate' => 'DATE',
							),	
							'date_until' => array(
								'validate' => 'DATE',
							),	
							'passport_date_of_issue' => array(
								'validate' => 'DATE',
							),	
							'passport_due_date' => array(
								'validate' => 'DATE',
							),	
						);

	public function validate($bThrowExceptions = false) {

		$mValidate = parent::validate($bThrowExceptions);
		
		if($mValidate===true){
			
			$mValidate		= array();

			$sVisumFrom		= $this->date_from;
			$sVisumUntil	= $this->date_until;
			$sPassFrom		= $this->passport_date_of_issue;
			$sPassUntil		= $this->passport_due_date;

			if(
				!empty($sVisumFrom) &&
				$sVisumFrom != '0000-00-00' &&
				WDDate::isDate($sVisumFrom, WDDate::DB_DATE) &&
				!empty($sVisumUntil) &&
				$sVisumUntil != '0000-00-00' &&
				WDDate::isDate($sVisumUntil, WDDate::DB_DATE)
			){
				$oDate = new WDDate($sVisumUntil, WDDate::DB_DATE);
				$iCompare = $oDate->compare($sVisumFrom, WDDate::DB_DATE);
				if($iCompare < 1){
					$mValidate['ki.visum_date_until'] = 'VISUM_UNTIL';
				}
			}

			if(
				!empty($sPassFrom) &&
				$sPassFrom != '0000-00-00' &&
				WDDate::isDate($sPassFrom, WDDate::DB_DATE) &&
				!empty($sPassUntil) &&
				$sPassUntil != '0000-00-00' &&
				WDDate::isDate($sPassUntil, WDDate::DB_DATE)
			){
				$oDate = new WDDate($sPassUntil, WDDate::DB_DATE);
				$iCompare = $oDate->compare($sPassFrom, WDDate::DB_DATE);
				if($iCompare < 1){
					$mValidate['ki.visum_passport_due_date'] = 'PASS_UNTIL';
				}
			}

			if(empty($mValidate)){
				$mValidate = true;
			}
		}

		return $mValidate;
	}
	
	
	/**
	 * Sucht die Visadaten zu einer Journey/Traveller Kombination
	 *
	 * Achtung! Diese Methode funktioniert NICHT objekt-relational! Ohne IDs liefert jeder Aufruf ein neues Objekt zurück!
	 * @internal
	 *
	 * @TODO Redundant mit \Ext_TS_Inquiry_Journey::getVisa()
	 * @see \Ext_TS_Inquiry_Journey::getVisa()
	 *
	 * @param Ext_TS_Inquiry_Journey $oJourney
	 * @param Ext_TS_Inquiry_Contact_Traveller $oTraveller
	 * @return Ext_TS_Inquiry_Journey_Visa
	 */
	public static function searchData(Ext_TS_Inquiry_Journey $oJourney, Ext_TS_Inquiry_Contact_Traveller $oTraveller, $createNew=true) {

        if(
			!$oJourney->exist() ||
			!$oTraveller->exist() ||
			!isset(self::$_aCache[$oJourney->id][$oTraveller->id])
		) {
			
			$aVisas = $oJourney->getJoinedObjectChilds('visa', true);

            $oFinalVisa = null;

			foreach($aVisas as $oVisa) {
				if($oVisa->getJoinedObject('traveller') === $oTraveller) {
                    $oFinalVisa = $oVisa;
                    break;
                }
            }

			// ich habe hier ne if Bedingung ergänzt, ist ja Schwachsinn oben nen FinalVisa zu finden
			// und hier unten wieder zu überschreiben :) (#4441)
			if(!$oFinalVisa) {
				
				if(!empty($aVisas)) {
					$oFinalVisa = reset($aVisas);
				} elseif($createNew) {				
					$oFinalVisa = $oJourney->getJoinedObjectChild('visa');
				}
				
				if($oFinalVisa) {
					$oFinalVisa->setJoinedObject('traveller', $oTraveller);
				}
				
			}

			if(
				$oJourney->exist() &&
				$oTraveller->exist() &&
				$oFinalVisa
			) {
				self::$_aCache[$oJourney->id][$oTraveller->id] = $oFinalVisa;
			}

			return $oFinalVisa;
        }

		return self::$_aCache[$oJourney->id][$oTraveller->id];
	}

	/**
	 * {@inheritdoc}
	 */
	public function save($bLog = true) {

		if($this->isAllEmpty()) {
			if($this->id > 0) {
				$this->delete();
			} else {
				return true;
			}
		}

		parent::save($bLog);

	}

	public function isAllEmpty(){
		if(
			empty($this->_aData['servis_id']) &&
			empty($this->_aData['tracking_number']) &&
			empty($this->_aData['status']) &&
			empty($this->_aData['required']) &&
			empty($this->_aData['passport_number']) &&
			$this->_aData['passport_date_of_issue'] == '0000-00-00' &&
			$this->_aData['passport_due_date'] == '0000-00-00' &&
			$this->_aData['date_from'] == '0000-00-00' &&
			$this->_aData['date_until'] == '0000-00-00'
		){
			return true;
		}else{
			return false;
		}
	}
	
	public function delete($bLog = true) {
		$sSql = "
			DELETE FROM
				#table
			WHERE
				`id` = :data_id
		";
		
		$aSql = array(
			'table'		=> $this->_sTable,
			'data_id'	=> (int)$this->id
		);
		
		$bSuccess = DB::executePreparedQuery($sSql, $aSql);
		
		// Log entry
		if($bLog && $bSuccess) {
			$this->log(Ext_Thebing_Log::DELETED, $this->_aData);
		}
	}
	
	/**
	 * Liefert das Visum zu diesen Daten
	 * @return Ext_Thebing_Visum 
	 */
	public function getVisa() {
		return Ext_Thebing_Visum::getInstance((int)$this->status);
	}
	
	/**
	 *
	 * Status-Wert für den Index, falls leer, setzen wir eine "-1"
	 * 
	 * @return int 
	 */
	public function getStatusForIndex() {		
		if(
			$this->status == '' ||
			$this->status == 0
		) {
			return -1;
		}

		return $this->status;		
	}
	
	public function getFrom() {
		
		$sFrom = $this->date_from;
		if($sFrom == '0000-00-00') {
			return null;
		}
		
		return $sFrom;
	}
	
	public function getUntil() {
		
		$sUntil = $this->date_until;
		if($sUntil == '0000-00-00') {
			return null;
		}
		
		return $sUntil;
	}
	
}