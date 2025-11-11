<?php

namespace TsCompany\Gui2\Format\JobOpportunity;

class ValueUnit extends \Ext_Thebing_Gui2_Format_Format {

	public function format($value, &$volumn = null, &$resultData = null) {

		switch ($value) {
			case 'hour':
				$unit = \L10N::t('Stunde');
				break;
			case 'day':
				$unit = \L10N::t('Tag');
				break;
			case 'week':
				$unit = \L10N::t('Woche');
				break;
			case 'month':
				$unit = \L10N::t('Monat');
				break;
			default:
				$unit = $value;
		}

		return $unit;
	}

}
