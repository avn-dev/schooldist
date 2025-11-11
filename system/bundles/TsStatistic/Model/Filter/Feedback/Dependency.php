<?php

namespace TsStatistic\Model\Filter\Feedback;

use \TcStatistic\Model\Filter\AbstractFilter;

class Dependency extends AbstractFilter {

	public function getKey() {
		return 'question_dependency';
	}

	public function getTitle() {
		return self::t('Abhängigkeit');
	}

	public function getInputType() {
		return 'select';
	}

	public function getSelectOptions() {
		return \Util::addEmptyItem(\Ext_TS_Marketing_Feedback_Question::getDependencies(), self::t('ohne Abhängigkeit'));
	}

	public function getDefaultValue() {
		return '0';
	}

}
