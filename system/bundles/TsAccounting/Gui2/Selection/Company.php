<?php

namespace TsAccounting\Gui2\Selection;

class Company extends \Ext_Gui2_View_Selection_Abstract
{

	/**
	 *
	 * @param array $aSelectedIds
	 * @param array $aSaveField
	 * @param \TsAccounting\Entity\Company\TemplateReceiptText $oWDBasic
	 * @return array
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
	{
		$aFreeCombinations = $oWDBasic->getFreeCombinations();

		$aSelectOptions = (array)$aSaveField['select_options'];

		$aBack = array();

		foreach ($aSelectOptions as $iCompanyId => $sCompanyName) {
			if (isset($aFreeCombinations[$iCompanyId])) {
				$aBack[$iCompanyId] = $sCompanyName;
			}
		}

		return $aBack;
	}

}