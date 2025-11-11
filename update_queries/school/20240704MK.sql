ALTER TABLE `kolumbus_specials` ADD `created_from` DATE NULL DEFAULT NULL AFTER `visible`, ADD `created_until` DATE NULL DEFAULT NULL AFTER `created_from`, ADD `service_from` DATE NULL DEFAULT NULL AFTER `created_until`, ADD `service_until` DATE NULL DEFAULT NULL AFTER `service_from`;
ALTER TABLE `kolumbus_specials` ADD `discount_code_enabled` TINYINT NOT NULL DEFAULT '0';
ALTER TABLE `kolumbus_specials` RENAME `ts_specials`; 
ALTER TABLE `kolumbus_specials_agencies` RENAME `ts_specials_agencies`; 
ALTER TABLE `kolumbus_specials_agencies_categories` RENAME `ts_specials_agencies_categories`; 
ALTER TABLE `kolumbus_specials_agencies_country` RENAME `ts_specials_agencies_country`; 
ALTER TABLE `kolumbus_specials_agencies_group` RENAME `ts_specials_agencies_group`; 
ALTER TABLE `kolumbus_specials_blocks` RENAME `ts_specials_blocks`; 
ALTER TABLE `kolumbus_specials_blocks_conditions` RENAME `ts_specials_blocks_conditions`; 
ALTER TABLE `kolumbus_specials_blocks_data` RENAME `ts_specials_blocks_data`; 
ALTER TABLE `kolumbus_specials_countries` RENAME `ts_specials_countries`; 
ALTER TABLE `kolumbus_specials_countries_group` RENAME `ts_specials_countries_group`; 
ALTER TABLE `kolumbus_specials_to_student_status` RENAME `ts_specials_to_student_status`;
CREATE TABLE `ts_specials_codes` (
  `id` int(11) NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `special_id` int(11) NOT NULL,
  `code` varchar(100) NOT NULL,
  `latest_use` timestamp NULL DEFAULT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL
) ENGINE=InnoDB;
ALTER TABLE `ts_specials_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `special_id` (`special_id`,`code`);
ALTER TABLE `ts_specials_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
CREATE TABLE `ts_specials_codes_usages` ( 
	`code_id` INT NOT NULL AUTO_INCREMENT , 
	`created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , 
	`inquiry_id` INT NOT NULL , 
	PRIMARY KEY (`code_id`), 
	INDEX (`inquiry_id`)
) ENGINE = InnoDB;
ALTER TABLE `ts_specials` ADD `direct_bookings` TINYINT NOT NULL AFTER `direct_booking`, ADD `agency_bookings` TINYINT NOT NULL AFTER `direct_bookings`;
CREATE TABLE `ts_specials_schools` ( `special_id` INT NOT NULL , `school_id` INT NOT NULL , PRIMARY KEY (`special_id`, `school_id`)) ENGINE = InnoDB;
ALTER TABLE `ts_specials_codes` ADD `valid` TINYINT NOT NULL DEFAULT '1' AFTER `code`;
CREATE TABLE `ts_specials_nationalities` ( `special_id` INT NOT NULL , `nationality_iso` CHAR(2) NOT NULL , PRIMARY KEY (`special_id`, `nationality_iso`)) ENGINE = InnoDB;
ALTER TABLE `ts_specials` ADD `minimum_courses` TINYINT NULL DEFAULT NULL;
ALTER TABLE `ts_specials` ADD `exclusive` TINYINT NOT NULL DEFAULT '1' AFTER `minimum_courses`;
