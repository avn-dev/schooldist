<?php

namespace TsStatistic\Generator\Tool\Groupings\Revenue;

use TsStatistic\Generator\Tool\Groupings\AbstractGrouping;

class GeneralFees extends AbstractGrouping {

	public function getTitle() {
		return self::t('Zusätzliche generelle Gebühren');
	}

	public function getSelectFieldForId() {
		return "`kc_items`.`id`";
	}

	public function getSelectFieldForLabel() {
		$sInterfaceLanguage = \System::getInterfaceLanguage();
		return "`kc_items`.`name_{$sInterfaceLanguage}`";
	}

	public function getJoinPartsAdditions() {
		return [
			'JOIN_ITEMS' => " AND
				`kidvi`.`type` = '{$this->getFeeType()}'
			",
			"JOIN_ITEMS_JOINS" => " INNER JOIN
				`kolumbus_costs` `kc_items` ON
					`kc_items`.`id` = `kidvi`.`type_id`
			"
		];
	}

	public function getColumnColor() {
		return 'revenue';
	}

	protected function getFeeType() {
		return 'additional_general';
	}

}
