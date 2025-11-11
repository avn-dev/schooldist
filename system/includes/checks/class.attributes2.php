<?php

class Checks_Attributes2 extends GlobalChecks {

	private $tables = [
		'wdbasic_attributes_decimal',
		'wdbasic_attributes_float',
		'wdbasic_attributes_int',
		'wdbasic_attributes_text',
		'wdbasic_attributes_tinyint',
		'wdbasic_attributes_varchar'
	];

	public function getTitle() {
		return 'Migrate entity attributes';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {

		$structure = DB::describeTable('wdbasic_attributes', true);
		if (!empty($structure['entity_id'])) {
			return true;
		}

		$this->createTable();

		foreach ($this->tables as $table) {
			$this->migrateTable($table);
		}

		Util::backupTable('wdbasic_attributes');

		DB::executeQuery("DROP TABLE `wdbasic_attributes`");

		DB::executeQuery("RENAME TABLE `wdbasic_attributes_v2` TO `wdbasic_attributes`");

		foreach ($this->tables as $table) {
			DB::executeQuery("DROP TABLE $table");
		}

		WDCache::flush();

		return true;

	}

	private function migrateTable($table) {

		Util::backupTable($table);

		DB::executeQuery("
			INSERT INTO `wdbasic_attributes_v2`
				(`entity`, `entity_id`, `key`, `value`)
			SELECT
				`attributes`.`table` `entity`,
				`attributes`.`class_id` `entity_id`,
			    `attributes`.`name` `key`,
			    `values`.`value` `value`
			FROM
				$table `values` INNER JOIN
				`wdbasic_attributes` `attributes` ON
					`attributes`.`id` = `values`.`attribute_id`
		");

	}

	private function createTable() {

		DB::executeQuery("DROP TABLE IF EXISTS `wdbasic_attributes_v2`");

		DB::executeQuery("
			CREATE TABLE IF NOT EXISTS `wdbasic_attributes_v2` (
			  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
			  `entity` varchar(255) NOT NULL COMMENT 'Legacy: Table name',
			  `entity_id` int(10) UNSIGNED NOT NULL,
			  `key` varchar(255) NOT NULL,
			  `value` text NOT NULL,
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `entity` (`entity`,`entity_id`,`key`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");

	}

}
