ALTER TABLE `system_gui2_flex_data`
	CHANGE `db_column` `db_column` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
	CHANGE `db_alias` `db_alias` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;

UPDATE
	`system_gui2_flex_data`
SET
	`db_column` = 'amount_additional_accommodation_commission_original'
WHERE
	`db_column` = 'amount_additional_accommodation_commission_origina';
