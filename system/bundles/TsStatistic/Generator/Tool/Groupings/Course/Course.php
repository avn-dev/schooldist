<?php

namespace TsStatistic\Generator\Tool\Groupings\Course;

use TsStatistic\Generator\Tool\Groupings\AbstractGrouping;

class Course extends AbstractGrouping {

	public function getTitle() {
		return self::t('Kurs');
	}

	public function getSelectFieldForId() {
		return "`ktc`.`id`";
	}

	public function getSelectFieldForLabel() {
		$sInterfaceLanguage = \System::getInterfaceLanguage();
		return "`ktc`.`name_{$sInterfaceLanguage}`";
	}

	public function getJoinParts() {
		return ['course'];
	}

	public function getColumnColor() {
		return 'service';
	}

}
