<?php

namespace TsStatistic\Generator\Tool\Groupings\Accommodation;

use TsStatistic\Generator\Tool\Groupings\AbstractGrouping;

class Category extends AbstractGrouping {

	public function getTitle() {
		return self::t('Unterkunftskategorie');
	}

	public function getSelectFieldForId() {
		return "`kac`.`id`";
	}

	public function getSelectFieldForLabel() {
		$sInterfaceLanguage = \System::getInterfaceLanguage();
		return "`kac`.`name_{$sInterfaceLanguage}`";
	}

	public function getJoinParts() {
		return ['accommodation'];
	}

	public function getColumnColor() {
		return 'service';
	}

}
