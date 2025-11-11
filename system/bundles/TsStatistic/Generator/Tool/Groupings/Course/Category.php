<?php

namespace TsStatistic\Generator\Tool\Groupings\Course;

use TsStatistic\Generator\Tool\Groupings\AbstractGrouping;

class Category extends AbstractGrouping {

	public function getTitle() {
		return self::t('Kurskategorien');
	}

	public function getSelectFieldForId() {
		return "`ktcc`.`id`";
	}

	public function getSelectFieldForLabel() {
		return "`ktcc`.`name_".\Ext_Thebing_School::fetchInterfaceLanguage()."`";
	}

	public function getJoinParts() {
		return ['course'];
	}

	public function getColumnColor() {
		return 'service';
	}

}
