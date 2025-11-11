ALTER TABLE `kolumbus_forms` ADD `purpose` ENUM('new','template','edit','confirm') NOT NULL DEFAULT 'new' AFTER `type`;

CREATE TABLE IF NOT EXISTS `ts_inquiries_form_processes` (
	`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
	`changed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
	`active` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
	`valid_until` date DEFAULT NULL,
	`inquiry_id` mediumint(8) UNSIGNED NOT NULL,
	`combination_id` smallint(5) UNSIGNED NOT NULL,
	`key` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
	`multiple` tinyint(1) NOT NULL DEFAULT 0,
	`seen` timestamp NULL DEFAULT NULL,
	`submitted` timestamp NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
