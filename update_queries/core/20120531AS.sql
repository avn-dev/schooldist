ALTER TABLE
	`tc_frontend_combinations`
ADD
	`overwritable` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `key`;