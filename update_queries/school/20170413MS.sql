UPDATE `customer_db_config` SET `external_table_email` = 'email' WHERE `customer_db_config`.`id` = 13;
UPDATE `customer_db_config` SET `external_table_user` = 'email' WHERE `customer_db_config`.`id` = 13;

CREATE TABLE `ts_agencies_activation_codes` (
	`agency_id` INT UNSIGNED NOT NULL ,
	`activation_code` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
	INDEX (`agency_id`)
) ENGINE = InnoDB;

ALTER TABLE `ts_agencies_activation_codes` ADD `expired` TIMESTAMP NOT NULL AFTER `activation_code`;