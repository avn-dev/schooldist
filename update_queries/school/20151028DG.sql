
ALTER TABLE `ts_companies`
	ADD `position` SMALLINT UNSIGNED NOT NULL AFTER `editor_id`,
	ADD `export_file_extension` ENUM('csv','txt') NOT NULL DEFAULT 'csv',
	ADD `export_delimiter` VARCHAR(1) NOT NULL DEFAULT ';',
	ADD `export_charset` VARCHAR(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL;

CREATE TABLE IF NOT EXISTS `ts_companies_colums_export` (
  `company_id` smallint(5) unsigned NOT NULL,
  `column` varchar(255) CHARACTER SET ascii NOT NULL,
  `position` smallint(5) unsigned NOT NULL,
	PRIMARY KEY (`company_id`,`column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE
	`ts_companies` `ts_com`
SET
	`export_charset` = COALESCE((
		SELECT
			`cdb2`.`csv_charset`
		FROM
			`ts_companies_combinations` `ts_comc` INNER JOIN
			`ts_companies_combinations_to_schools` `ts_comcts` ON
				`ts_comcts`.`company_combination_id` = `ts_comc`.`id` INNER JOIN
			`customer_db_2` `cdb2` ON
				`cdb2`.`id` = `ts_comcts`.`school_id`
		WHERE
			`ts_comc`.`company_id` = `ts_com`.`id` AND
			`cdb2`.`csv_charset` != '0'
		LIMIT
			1
	), 'CP1252')
WHERE
	`export_charset` = '';

UPDATE
	`tc_gui2_filtersets_bars_elements_basedon`
SET
	`base_on` = REPLACE(`base_on`, 'tbs.', 'ts_bs.')
WHERE
	`element_id` IN (
		SELECT
			`tc_gfbe`.`id`
		FROM
			`tc_gui2_filtersets_bars_elements` `tc_gfbe` INNER JOIN
			`tc_gui2_filtersets_bars` `tc_gfb` ON
				`tc_gfb`.`id` = `tc_gfbe`.`bar_id` INNER JOIN
			`tc_gui2_filtersets` `tc_gf` ON
				`tc_gf`.`id` = `tc_gfb`.`set_id`
		WHERE
			`tc_gf`.`application` = 'ts_booking_stack'
	);