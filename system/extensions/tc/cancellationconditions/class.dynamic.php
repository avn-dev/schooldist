<?php
	
class Ext_TC_Cancellationconditions_Dynamic extends Ext_TC_Basic {

	protected $_sTable = 'tc_cancellation_conditions_fees_dynamic';

	protected $_sTableAlias = 'tc_ccfd';

	/**
	 * get Select Options
	 * @return type 
	 */
	public static function getSelectOptions(){
		$oTemp = new self();
		$aList = $oTemp->getArrayList(true);
		return $aList;
	
	}

	public function __get($sName) {
		return match($sName) {
			'selection' => json_decode((string)$this->_aData['selection']),
			default => parent::__get($sName)
		};
	}

	public function __set($sName, $mValue) {

		if ($sName === 'selection') {
			if (empty($mValue)) {
				$mValue = null;
			} else {
				$mValue = json_encode(\Illuminate\Support\Arr::wrap($mValue));
			}
		}

		parent::__set($sName, $mValue);

	}

	public static function getDynamicFeeKinds($bForSelect = false){
		
		$sTranslationPath = Ext_TC_System_Navigation::tp();

		$aReturn =  array(
			1 => L10N::t('Prozent', $sTranslationPath),
			2 => L10N::t('Währung', $sTranslationPath)
		);
		
		if($bForSelect){
			$aReturn = Ext_TC_Util::addEmptyItem($aReturn);
		}
		
		return $aReturn;
		
	}
	
}
