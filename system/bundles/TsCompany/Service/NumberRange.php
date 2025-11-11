<?php

namespace TsCompany\Service;

use TsCompany\Entity\AbstractCompany;

class NumberRange extends \Ext_TS_NumberRange {

	protected $_sNumberTable = 'ts_companies_numbers';

	protected $_sNumberField = 'number';

	/**
	 * Liefert das Numberrange-Objekt, welches für diese Klasse benutzt wird
	 * Dieses wird zentral bei den Nummernkreisen eingestellt.
	 * @static
	 * @return \Ext_TC_NumberRange|null
	 */
	public static function getObject(AbstractCompany $oEntity) {

		/* @var \Ext_TC_Config $oConfig */
		$oConfig = \Factory::getInstance('Ext_TC_Config');

		if($oEntity instanceof \Ext_Thebing_Agency) {
			$mConfigNumberRangeId = $oConfig->getValue('ts_agencies_numbers');
		} else {
			$mConfigNumberRangeId = $oConfig->getValue('ts_companies_numbers');
		}

		$oNumberRange = null;
		if(is_numeric($mConfigNumberRangeId)) {
			$oNumberRange = \TsCompany\Service\NumberRange::query()->find($mConfigNumberRangeId);
		}

		return $oNumberRange;
	}

	public static function checkPossibility(AbstractCompany $oEntity, string $sNumber): bool {

		$oExisting = $oEntity->newQuery()
			->select('ka.*')
			->join('ts_companies_numbers', function ($join) use ($sNumber) {
				$join->on('ts_companies_numbers.company_id', '=', 'ka.id')
					->where('ts_companies_numbers.number', '=', $sNumber);
			})
			->whereKeyNot($oEntity->getId())
			->first()
		;

		if($oExisting !== null) {
			return false;
		}

		return true;
	}

}
