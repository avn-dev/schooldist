<?php

namespace TsStatistic\Model\Filter\Feedback;

use \TcStatistic\Model\Filter\AbstractFilter;

class Topic extends AbstractFilter {

	public function getKey() {
		return 'question_topic';
	}

	public function getTitle() {
		return self::t('Thema');
	}

	public function getInputType() {
		return 'select';
	}

	public function getSelectOptions() {
		return \Util::addEmptyItem(\Ext_TC_Marketing_Feedback_Topic::getSelectOptions());
	}

	public function getDefaultValue() {
		return key($this->getSelectOptions());
	}

}
