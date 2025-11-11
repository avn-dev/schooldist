CREATE TABLE IF NOT EXISTS `kolumbus_email_flags` (
	`id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT ,
	`log_id` MEDIUMINT UNSIGNED NOT NULL ,
	`flag` VARCHAR(255) COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
	PRIMARY KEY  (`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `ts_inquiries_journeys_courses` ADD `index_attendance_warning_count` INT(11) NOT NULL DEFAULT 0 AFTER `editor_id`, ADD `index_attendance_warning_latest_date` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `index_attendance_warning_count`;