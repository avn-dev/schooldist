ALTER TABLE `system_user` ADD `ts_is_sales_person` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';
CREATE TABLE `ts_system_user_sales_persons_nationalities` (
	`user_id` INT(11) UNSIGNED NOT NULL ,
	`country_iso` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
	`school_id` INT(11) UNSIGNED NOT NULL ,
	INDEX (`user_id`),
	INDEX (`school_id`)
) ENGINE = InnoDB;

CREATE TABLE `ts_system_user_sales_persons_agencies` (
	`user_id` INT(11) UNSIGNED NOT NULL ,
	`agency_id` INT(11) UNSIGNED NOT NULL ,
	`school_id` INT(11) UNSIGNED NOT NULL ,
	INDEX (`user_id`),
	INDEX (`agency_id`),
	INDEX (`school_id`)
) ENGINE = InnoDB;

ALTER TABLE `ts_inquiries` ADD `sales_person_id` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `transfer_data_requested`, ADD INDEX `user_id` (`sales_person_id`);
ALTER TABLE `ts_enquiries` ADD `sales_person_id` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `promotion_code`, ADD INDEX `user_id` (`sales_person_id`);