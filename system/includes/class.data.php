<?php

class Data
{
	/**
	 * @author Sebastian Kaiser
	 * @param boolean $bPrepareSelect if is set true, entries will be returned with query pairs / prepared for usage in select fields
	 * @return array all available currencys as multidimensional array or optional as prepared array for select usage
	 */
	public static function getCurrencys($mPrepareSelect = false) {
		
		if($mPrepareSelect === true) {
			$sSQL = "
				SELECT
					`iso4217` 	AS `id`,
					`name`		AS `value`
				FROM
					`data_currencies`
			";
			$aCurrencys = DB::getQueryPairs($sSQL);
		} elseif($mPrepareSelect === 'sign') {
			$sSQL = "
				SELECT
					`iso4217` 	AS `id`,
					`sign`		AS `value`
				FROM
					`data_currencies`
			";
			$aCurrencys = DB::getQueryPairs($sSQL);
		} else {
			$sSQL = "
				SELECT
					*
				FROM
					`data_currencies`
			";
			$aCurrencys = DB::getQueryData($sSQL);	
		}

		return (array)$aCurrencys;
	}
}