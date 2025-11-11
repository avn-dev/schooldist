<?php

namespace TsStatistic\Model\Filter;

use \TcStatistic\Model\Filter\AbstractFilter;

class Cancellation extends AbstractFilter {

	public function getKey() {
		return 'cancellation';
	}

	public function getTitle() {
		return self::t('Stornierte Buchungen berücksichtigen');
	}

	public function getInputType() {
		return 'select';
	}

	public function getSelectOptions() {
		return \Ext_TC_Util::getYesNoArray(false);
	}

	public function getDefaultValue() {
		return 'no';
	}

}
