CREATE TABLE IF NOT EXISTS `ts_frontend_booking_templates` (
	`id` mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
	`created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
	`changed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
	`active` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
	`creator_id` smallint(9) UNSIGNED NOT NULL,
	`editor_id` smallint(9) UNSIGNED NOT NULL,
	`key` varchar(255) NOT NULL,
	`form_id` smallint(6) UNSIGNED NOT NULL,
	`course_id` smallint(6) UNSIGNED NOT NULL,
	`course_from` enum('empty','next') NOT NULL DEFAULT 'empty',
	`course_duration` enum('empty','next') NOT NULL DEFAULT 'empty',
	`course_id_locked` tinyint(1) NOT NULL DEFAULT 0,
	`course_duration_locked` tinyint(1) NOT NULL DEFAULT 0,
	PRIMARY KEY (`id`),
	UNIQUE KEY `key` (`key`),
	KEY `active` (`active`),
	KEY `form_id` (`form_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
