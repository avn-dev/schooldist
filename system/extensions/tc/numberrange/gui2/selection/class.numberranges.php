<?php

class Ext_TC_Numberrange_Gui2_Selection_Numberranges extends Ext_Gui2_View_Selection_Abstract {

protected $_sCategory = '';

	/**
	* Filter
	 *
	* @param string $sCategory
	*/
	public function __construct($sCategory = '') {
		$this->_sCategory = $sCategory;
	}

	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveField
	 * @param WDBasic $oWDBasic
	 * @return array
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aNumberranges = Ext_TC_NumberRange::getRepository()->findAll();
		$aReturn = array();

		foreach($aNumberranges as $oNumberrange) {

			if(
				(
					!empty($this->_sCategory) &&
					$this->_sCategory == $oNumberrange->category
				) ||
				empty($this->_sCategory)
			) {
				$aReturn[$oNumberrange->id] = $oNumberrange->getName();
			}

		}

		$aReturn = Ext_TC_Util::addEmptyItem($aReturn);

		return $aReturn;
	}

}