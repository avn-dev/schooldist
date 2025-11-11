ALTER TABLE `ts_accommodations_payments_groupings` ADD `index_processed_user_id` MEDIUMINT NULL DEFAULT NULL;

CREATE TABLE `ts_accommodations_payments_groupings_histories` (
	`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`changed` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
	`created` timestamp NOT NULL,
	`creator_id` int(10) UNSIGNED NOT NULL,
	`editor_id` int(10) UNSIGNED NOT NULL,
	`active` TINYINT(1) NOT NULL DEFAULT 1,
	`file` varchar(255) CHARACTER SET utf8 NOT NULL,
	`absolute_path` VARCHAR(255) CHARACTER SET utf8 NOT NULL,
	PRIMARY KEY(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ts_accommodations_payments_groupings_to_histories` (
	`payment_grouping_id` MEDIUMINT(9) NOT NULL,
	`history_id` MEDIUMINT(9) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;