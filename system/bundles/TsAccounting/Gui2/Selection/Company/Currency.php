<?php

namespace TsAccounting\Gui2\Selection\Company;

class Currency extends \Ext_Gui2_View_Selection_Abstract
{

	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveField
	 * @param \WDBasic $oWDBasic
	 * @return array
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
	{

		$aCombinations = $oWDBasic->getCombinationsFromObjectContext();

		$intersectCurrencies = array();

		$aSchoolIds = array();

		foreach ($aCombinations as $oCombination) {

			$aSchools = (array)$oCombination->getSchools();

			foreach ($aSchools as $oSchool) {

				$schoolCurrencies = \Illuminate\Support\Arr::pluck($oSchool->getCurrencies(), 'name', 'iso4217');

				if (empty($intersectCurrencies)) {
					$intersectCurrencies = $schoolCurrencies;
				} else {

					$intersectCurrencies = array_intersect_assoc($intersectCurrencies, $schoolCurrencies);

				}

			}
		}

		return $intersectCurrencies;
	}

}