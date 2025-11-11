<?php

abstract class Ext_TC_Frontend_CombinationTemplate extends Ext_TC_Basic {

	public function save($bLog = true) {

		if (empty($this->key)) {
			$iCounter = 0;
			$iCounterMax = 2000;
			do {
				if($iCounter>$iCounterMax) {
					break;
				}
				$this->key = Ext_TC_Util::generateRandomString(16);
				$mIsUnique = $this->_isUnique('key',true);
				$iCounter++;
			} while($mIsUnique !== true);
		}

		return parent::save($bLog);

	}

	/**
	 * Update the last_use time
	 *
	 * @return boolean
	 */
	public function updateLastUse()	{

		if(!$this->exist()) {
			return false;
		}

		$sSql = "
			UPDATE
				#sTable
			SET
				`changed` = `changed`,
				`last_use` = NOW()
			WHERE
				`id` = :iID
			LIMIT
				1
		";
		
		$aSql = array(
			'iID'		=> $this->id,
			'sTable'	=> $this->_sTable
		);
		
		DB::executePreparedQuery($sSql, $aSql);

		return true;

	}

	public function createCopy($sForeignIdField = null, $iForeignId = null, $aOptions = array()){

		$aOptions['copy_unique'] = array();
		$aOptions['copy_unique']['key'] = ':random16';
		$oClone = parent::createCopy($sForeignIdField, $iForeignId, $aOptions);

		return $oClone;
	}
	
}