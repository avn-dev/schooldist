<?php

namespace TsAccounting\Gui2\Format\Payments;

class AmountDocuments extends \Ext_Thebing_Gui2_Format_Amount
{
	public function format($value, &$column = null, &$resultData = null)
	{
		$versions = explode('{||}', $value);
		$fullAmount = 0;

		foreach ($versions as $versionString) {
			[$id, $vatSetting, $amount, $discountAmount, $vatAmount] = explode('{|}', $versionString);

			$fullAmount += (float)$amount;
			$fullAmount -= (float)$discountAmount;

			if ((int)$vatSetting === 2) {
				$fullAmount += (float)$vatAmount;
			}
		}

		return parent::format($fullAmount, $column, $resultData);
	}
}