<?php

namespace TsAccounting\Gui2\Selection\Company\Combination;

class School extends \Ext_Gui2_View_Selection_Abstract
{

	protected $_bUseJoinedObject;


	public function __construct($bUseJoinedObject = false)
	{
		$this->_bUseJoinedObject = $bUseJoinedObject;
	}

	/**
	 *
	 * @param array $aSelectedIds
	 * @param array $aSaveField
	 * @param \Ext_TC_Basic|\TsAccounting\Entity\Company $oWDBasic
	 * @return array
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
	{
		if ($this->_bUseJoinedObject) {
			$oAllocationObject = $this->oJoinedObject;
		} else {
			$oAllocationObject = $oWDBasic;
		}

		if (empty($oAllocationObject)) {
			$oAllocationObject = $oWDBasic->getEmptyAllocationObject();
		}

		/* @var $oAllocationObject \TsAccounting\Entity\Company\CombinationAbstract */

		$aFreeCombinations = $oAllocationObject->getFreeCombinations();

		$aSelectOptions = (array)$aSaveField['select_options'];

		$aCompanyIds = (array)$oAllocationObject->getCompanyIds();

		$aBack = array();

		foreach ($aSelectOptions as $iSchoolId => $sSchoolName) {
			foreach ($aCompanyIds as $iCompanyId) {
				#if(isset($aFreeCombinations[$iCompanyId]) && isset($aFreeCombinations[$iCompanyId][$iSchoolId]))
				#{
				$aBack[$iSchoolId] = $sSchoolName;
				#}
			}
		}

		return $aBack;
	}
}