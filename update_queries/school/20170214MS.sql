CREATE TABLE `ts_system_user_sales_persons_schools` (
	`setting_id` INT UNSIGNED NOT NULL ,
	`school_id` INT UNSIGNED NOT NULL ,
	INDEX (`setting_id`),
	INDEX (`school_id`)
) ENGINE = InnoDB CHARSET=utf8 COLLATE utf8_general_ci;

CREATE TABLE `ts_system_user_sales_persons_settings` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
	`user_id` INT NOT NULL ,
	PRIMARY KEY (`id`),
	INDEX (`user_id`)
) ENGINE = InnoDB CHARSET=utf8 COLLATE utf8_general_ci COMMENT = 'Dient als obergeordnete join table des sales persons';

ALTER TABLE `ts_system_user_sales_persons_nationalities` DROP `school_id`;
ALTER TABLE `ts_system_user_sales_persons_nationalities` DROP `user_id`;
ALTER TABLE `ts_system_user_sales_persons_nationalities` ADD `setting_id` INT UNSIGNED NOT NULL FIRST, ADD INDEX (`setting_id`);

ALTER TABLE `ts_system_user_sales_persons_agencies` DROP `user_id`;
ALTER TABLE `ts_system_user_sales_persons_agencies` DROP `school_id`;
ALTER TABLE `ts_system_user_sales_persons_agencies` ADD `setting_id` INT UNSIGNED NOT NULL FIRST, ADD INDEX (`setting_id`);