<?php

/**
 * @property int $id
 * @property int $client_id
 * @property string $changed
 * @property string $created
 * @property int $user_id
 * @property int $active
 * @property int $ceator_id
 * @property string $name
 */

class Ext_Thebing_Agency_Category extends Ext_Thebing_Basic {
	
	protected $_sTable = 'kolumbus_agency_categories';

	// Tabellenalias
	protected $_sTableAlias = 'kacat';

	/**
	 * @param boolean $bAsObjects
	 * @return array
	 */
	public function getAgencys($bAsObjects = false) {
		
		$aBack = array();
		
		$sSql = " SELECT * FROM `ts_companies` WHERE `type` & ".\TsCompany\Entity\AbstractCompany::TYPE_AGENCY." AND ext_39 = :category_id AND active = 1 ";
		$aSql = array('category_id' => $this->id);
		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		if($bAsObjects) {
			foreach($aResult as $aData){
				$aBack[] = Ext_Thebing_Agency::getInstance($aData['id']);
			}
		} else {
			$aBack = $aResult;
		}
		
		return $aBack;
	}

}
