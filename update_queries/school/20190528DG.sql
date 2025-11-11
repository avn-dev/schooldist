CREATE TABLE IF NOT EXISTS `ts_inquiries_holidays` (
	`id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
	`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`active` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
	`creator_id` smallint(5) UNSIGNED NOT NULL,
	`editor_id` smallint(5) UNSIGNED NOT NULL,
	`inquiry_id` mediumint(8) UNSIGNED NOT NULL,
	`type` enum('school','student') CHARACTER SET ascii NOT NULL DEFAULT 'student',
	`weeks` tinyint(3) UNSIGNED NOT NULL,
	`from` date NOT NULL,
	`until` date NOT NULL,
	PRIMARY KEY (`id`),
	KEY `inquiry_id` (`inquiry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ts_inquiries_holidays_splitting` (
	`id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
	`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`changed` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
	`active` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
	`creator_id` smallint(5) UNSIGNED NOT NULL,
	`editor_id` smallint(5) UNSIGNED NOT NULL,
	`holiday_id` mediumint(8) UNSIGNED NOT NULL,
	`journey_course_id` int(10) UNSIGNED DEFAULT NULL,
	`journey_split_course_id` int(10) UNSIGNED DEFAULT NULL,
	`journey_accommodation_id` int(10) UNSIGNED DEFAULT NULL,
	`journey_split_accommodation_id` int(10) UNSIGNED DEFAULT NULL,
	`original_weeks` smallint(5) UNSIGNED DEFAULT NULL,
	`original_from` date DEFAULT NULL,
	`original_until` date DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `holiday_id` (`holiday_id`),
	KEY `journey_course_id` (`journey_course_id`),
	KEY `journey_split_course_id` (`journey_split_course_id`),
	KEY `journey_accommodation_id` (`journey_accommodation_id`),
	KEY `journey_split_accommodation_id` (`journey_split_accommodation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
