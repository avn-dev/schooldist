<?php

namespace TsAccounting\Gui2\Selection\Company\Combination;

class Inbox extends \Ext_Gui2_View_Selection_Abstract
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
	 * @param \TsAccounting\Entity\Company $oWDBasic
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

		$aSchoolIds = (array)$oAllocationObject->getSchoolIds();

		$aBack = array();

		foreach ($aSelectOptions as $iInboxId => $sInboxName) {
			foreach ($aCompanyIds as $iCompanyId) {
				foreach ($aSchoolIds as $iSchoolId) {

					// Vorerst keine Überprüfung, da das nur selten geändert wird und eine Doppelauswahl einer Kombination keine schlimmen Folgen hat
					if (1 ||
						isset($aFreeCombinations[$iCompanyId]) &&
						isset($aFreeCombinations[$iCompanyId][$iSchoolId]) &&
						isset($aFreeCombinations[$iCompanyId][$iSchoolId][$iInboxId])
					) {
						$aBack[$iInboxId] = $sInboxName;
					}
				}
			}
		}

		return $aBack;
	}

}