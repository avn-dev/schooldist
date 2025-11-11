<?php

/**
 * @property $id
 * @property $created 	
 * @property $changed 	
 * @property $active 	
 * @property $creator_id 	
 * @property $special_id 	
 * @property $option_id 	
 * @property $percent 	
 * @property $weeks 	
 * @property $free_weeks 	
 * @property $dependency_on_duration
 */
class Ext_Thebing_Special_Block_Block extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'ts_specials_blocks';
    
    protected $_aGetAddtionalDataCache = null;

    public $aSpecialAmountCalculation = [];

	protected $_aJoinedObjects = array(
		'conditions' => array(
			'class' => 'Ext_Thebing_Special_Block_Condition',
			'key' => 'special_block_id',
			'type' => 'child',
			'check_active' => true,
			'on_delete' => 'cascade'
		)
	);
 
	/*
	 * Speichert die Datan zu diesem Block
	 */
	public function saveAdditionalData($aData, $sType = 'multiselect'){

		if($this->id > 0){
			foreach((array)$aData as $iKey => $sValue){
				$sSql = "INSERT INTO
							`ts_specials_blocks_data`
						SET
							`block_id`= :block_id,
							`type` = :type,
							`value` = :value
				";



				$aSql = array();
				$aSql['block_id']	= (int)$this->id;
				$aSql['type']		= $sType;
				$aSql['value']		= $sValue;

				if($sType == 'currency'){
					$sSql .= ", `type_id` = :currency_id";
					$aSql['currency_id'] = $iKey;
				}

				DB::executePreparedQuery($sSql, $aSql);
			}


		}

	}

	public function __get($sName){
		
		Ext_Gui2_Index_Registry::set($this);
		
		if($sName == 'additional_data'){
			return $this->getAdditionalData();
		}else{
			return parent::__get($sName);
		}
	}

    public function getAddtionalDataValue($sType, $iCurrency = 0){
        $aData = $this->getAdditionalData();
        foreach($aData as $aArray){
            if(
                $aArray['type'] == $sType &&
                (
                    $iCurrency == 0 ||
                    $iCurrency == $aArray['type_id']        
                )
            ){
                return $aArray['value'];
            }
        }
        return '';
    }
    
	/*
	 * Additional Daten holen
	 */
	public function getAdditionalData(){
        
		if($this->_aGetAddtionalDataCache === null){
            $sSql = "SElECT
                            *
                        FROM
                            `ts_specials_blocks_data`
                        WHERE
                            `block_id` = :block_id
                    ";

            $aSql = array();
            $aSql['block_id'] = (int)$this->id;

            $this->_aGetAddtionalDataCache = DB::getPreparedQueryData($sSql, $aSql);
        }

		return $this->_aGetAddtionalDataCache;
	}

	/*
	 * Additional Daten für Select
	 */
	public function getAdditionalDataForSelect(){
		$aData = $this->getAdditionalData();
		$aBack = array();
		foreach((array)$aData as $aValues){
			if($aValues['type'] == 'currency'){
				continue;
			}
			$aBack[] = $aValues['value'];
		}
		return $aBack;
	}

	/*
	 * Additional Daten für Select (Currencys)
	 */
	public function getAdditionalCurrencyData(){
		$aData = $this->getAdditionalData();
		$aBack = array();
		foreach((array)$aData as $aValues){
			if($aValues['type'] == 'currency'){
				$aBack[$aValues['type_id']] = $aValues['value'];
			}
		}
		return $aBack;
	}


	/*
	 * Löscht die Daten zu diesem Block
	 */
	public function deleteAdditionalData(){
		$sSql = "DELETE FROM
						`ts_specials_blocks_data`
					WHERE
						`block_id` = :block_id
				";
		$aSql = array();
		$aSql['block_id'] = (int)$this->id;
		
		DB::executePreparedQuery($sSql, $aSql);
	}

	public function delete($bLog = true) {
		// Daten löschen
		// Dürfen NICHT gelöscht werden da hier noch zugegriffen wird auch wenn der Block aktiv 0 ist!
		//$this->deleteAdditionalData();

		parent::delete();
	}

	public function getSpecial(){
		return Ext_Thebing_School_Special::getInstance($this->special_id);
	}

	/**
	 * 
	 * @param int $iWeeks
	 * @return boolean
	 */
	public function validateWeeks($iWeeks) {
		
		if($this->dependency_on_duration == 0) {
			return true;
		}
		
		$aConditions = $this->getJoinedObjectChilds('conditions', true);
		
		foreach($aConditions as $oCondition) {
			$bValidateWeeks = $oCondition->validateWeeks($iWeeks);
			// Sobald eine Bedingung fehlschlägt
			if(!$bValidateWeeks) {
				return false;
			}
		}

		return true;

	}


}