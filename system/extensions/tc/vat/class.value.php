<?php

/**
 * @property int $rate_id
 * @property string $valid_from (DATE)
 * @property string $valid_until (DATE)
 * @property float $rate
 * @method static \Ext_TC_Vat_ValueRepository getRepository()
 */
class Ext_TC_Vat_Value extends Ext_TC_Validity {

 	protected $_sTable = 'tc_vat_rates_values';
	protected $_sTableAlias = 'tc_vrv';

	public $sParentColumn = 'rate_id';
	
	protected static $_sStaticTable = 'tc_vat_rates_values';

	protected $_aFormat = array(
		'tax_rate'=>array(
			'validate'=>'FLOAT_NOTNEGATIVE'
		)
	);

	public function getRate() {
		return (float) $this->rate;
	}

	public function getLatestEntry($bIncludeSelf = false, $bWithEndDate = false, $iDependencyId = null) {

		$sWhere = '';

		if(!$bIncludeSelf) {
			$sWhere .= " AND `id` != :id";
		}

		if(!$bWithEndDate) {
			$sWhere .= " AND `valid_until` = '0000-00-00'";
		}# else {
		#	$sWhere .= " AND `valid_until` != '0000-00-00'";
		#}

		$sSql = "
				SELECT
					`id`
				FROM
					#table
				WHERE
					`active` = 1 AND
					`rate_id` = :rate_id
					".$sWhere."
				ORDER BY
					`valid_from` DESC
				LIMIT 1
					";
		$aSql = array(
			'table'			=> $this->_sTable,
			'id'			=> $this->id,
			'rate_id'		=> $this->rate_id
		);

		$iLastEntry = DB::getQueryOne($sSql, $aSql);

		return $iLastEntry;

	}

	public function getEntries($bIncludeSelf=false, $bWithEndDate=false) {

		$sWhere = '';

		if(!$bIncludeSelf) {
			$sWhere .= " AND `id` != :id";
		}

		$sSql = "
				SELECT
					`id`
				FROM
					#table
				WHERE
					`active` = 1 AND
					`rate_id` = :rate_id
					".$sWhere."
				ORDER BY
					`valid_from` DESC
					";
		$aSql = array(
			'table'			=> $this->_sTable,
			'id'			=> $this->id,
			'rate_id'		=> $this->rate_id
		);

		$aEntries = DB::getQueryCol($sSql, $aSql);

		return $aEntries;

	}	
	
	protected function _getInstance($iItemId) {
		return self::getInstance($iItemId);
	}
	
	public function validate($bThrowExceptions = false) {
		
		$aErrors = parent::validate($bThrowExceptions);

		return $aErrors;

	}
	
	public function checkIgnoringErrors(){

		if($this->id<=0){
			return true;
		}

		// Prüfen ob Datum in der Vergangenheit ist
		$mCheck = WDDate::isDate($this->valid_from, WDDate::DB_DATE);

		if($mCheck){

			$oDate = new WDDate();
			$oDateTemp = new WDDate($this->valid_from, WDDate::DB_DATE);
			
			$iCompare = $oDateTemp->compare($oDate);

			if($iCompare<0){
				$mCheck = false;
			}
		}
		
		if(!$mCheck){
			$mCheck = array();
			$mCheck[$this->_sTableAlias . '.valid_from'] = array('INVALID_DATE_TAX');
		}
		

		return $mCheck;
	}

	
 }
