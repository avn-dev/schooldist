<?php

namespace TsStatistic\Model\Filter\Feedback;

use \TcStatistic\Model\Filter\AbstractFilter;

class Questionnaire extends AbstractFilter {

	public function getKey() {
		return 'questionnaire';
	}

	public function getTitle() {
		return self::t('Fragebogen');
	}

	public function getInputType() {
		return 'select';
	}

	public function getSelectOptions() {
		return \Ext_TS_Marketing_Feedback_Questionary::getSelectOptions();
	}

	public function getDefaultValue() {
		return key($this->getSelectOptions());
	}

}
