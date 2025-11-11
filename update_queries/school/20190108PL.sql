CREATE TABLE IF NOT EXISTS `ts_activities` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`name` varchar(70) NOT NULL,
	`short` varchar(5) NOT NULL,
	`without_course` tinyint(1) NOT NULL,
	`billing_period` enum('payment_per_week','payment_per_block') NOT NULL,
	`availability` enum('always_available','limited_availability') NOT NULL,
	`min_students` int(11) NOT NULL,
	`max_students` int(11) NOT NULL,
	`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
	`active` tinyint(1) NOT NULL DEFAULT '1',
	`creator_id` int(11) NOT NULL,
	`editor_id` int(11) NOT NULL,
	`valid_until` date NOT NULL DEFAULT '0000-00-00',
	`position` tinyint(5) NOT NULL,
	`provider_id` int(11) NOT NULL,
	`free_of_charge` tinyint(1) NOT NULL,
	`show_for_free` tinyint(1) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `ts_activities_allocations` (
 `id` int(11) NOT NULL,
 `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
 `creator_id` int(11) NOT NULL,
 `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `editor_id` int(11) NOT NULL,
 `active` tinyint(4) NOT NULL,
 `traveller_id` int(11) NOT NULL,
 `journey_activity_id` int(11) NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `ts_activities_blocks` (
	`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
	`active` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
	`editor_id` int(10) UNSIGNED NOT NULL,
	`creator_id` int(10) UNSIGNED NOT NULL,
	`name` varchar(255) NOT NULL,
	`weeks` tinyint(4) NOT NULL,
	`start_date` varchar(10) NOT NULL,
	`repeat_weeks` tinyint(4) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `ts_activities_blocks_accompanying_persons` (
 `block_id` int(11) NOT NULL,
 `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `ts_activities_blocks_days` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
 `active` tinyint(4) NOT NULL DEFAULT '1',
 `editor_id` int(11) NOT NULL,
 `creator_id` int(11) NOT NULL,
 `activity_block_id` int(11) NOT NULL,
 `start_time` time NOT NULL,
 `end_time` time NOT NULL,
 `weekday` varchar(20) NOT NULL,
 `place` varchar(80) NOT NULL,
 `comment` varchar(180) NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `ts_activities_blocks_to_activities` (
	`block_id` int(11) NOT NULL,
	`activity_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `ts_activities_blocks_to_travellers` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`block_id` int(11) NOT NULL,
	`traveller_id` int(11) NOT NULL,
	`journey_activity_id` int(11) NOT NULL,
	`week` date NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `ts_activities_i18n` (
	`activity_id` mediumint(8) UNSIGNED NOT NULL,
	`language_iso` varchar(50) CHARACTER SET ascii NOT NULL,
	`name` varchar(255) CHARACTER SET utf8 NOT NULL,
	PRIMARY KEY (`activity_id`,`language_iso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `ts_activities_prices` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`changed` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
	`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`active` tinyint(4) NOT NULL DEFAULT '1',
	`creator_id` int(11) NOT NULL DEFAULT '0',
	`school_id` int(11) NOT NULL,
	`activity_id` int(11) NOT NULL,
	`currency_iso` varchar(8) NOT NULL,
	`price` decimal(16,5) NOT NULL,
	`changed_by` int(11) NOT NULL,
	PRIMARY KEY (`id`),
	KEY `school_id` (`school_id`),
	KEY `activity_id` (`activity_id`),
	KEY `currency_id` (`currency_iso`),
	KEY `changed_by` (`changed_by`),
	KEY `creator_id_2` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `ts_activities_providers` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `changed` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
 `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
 `creator_id` int(11) NOT NULL,
 `editor_id` int(11) NOT NULL,
 `contact_id` int(11) NOT NULL,
 `active` tinyint(4) NOT NULL DEFAULT '1',
 `valid_until` date NOT NULL,
 `comment` varchar(140) NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `ts_activities_schools` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`activity_id` int(11) NOT NULL,
	`school_id` int(11) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `ts_activities_schools_to_courses` (
	`activity_school_id` int(11) NOT NULL,
	`course_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `ts_activities_validities` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
	`active` tinyint(1) NOT NULL DEFAULT '1',
	`creator_id` int(11) NOT NULL,
	`editor_id` int(11) NOT NULL,
	`activity_id` int(11) NOT NULL,
	`valid_from` date NOT NULL,
	`valid_until` date NOT NULL,
	`comment` text NOT NULL,
	PRIMARY KEY (`id`),
	KEY `parent_id` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ts_inquiries_journeys_activities` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
	`active` tinyint(1) NOT NULL DEFAULT '1',
	`creator_id` int(11) NOT NULL DEFAULT '0',
	`journey_id` mediumint(9) NOT NULL,
	`activity_id` int(11) NOT NULL,
	`from` date NOT NULL,
	`till` date NOT NULL,
	`weeks` smallint(5) UNSIGNED DEFAULT NULL,
	`visible` tinyint(1) NOT NULL DEFAULT '1',
	`comment` text NOT NULL,
	PRIMARY KEY (`id`),
	KEY `insurance_id` (`activity_id`),
	KEY `creator_id` (`creator_id`),
	KEY `journey_id` (`journey_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ts_inquiries_journeys_activities_to_travellers` (
	`journey_activity_id` mediumint(9) NOT NULL,
	`contact_id` mediumint(9) NOT NULL,
	UNIQUE KEY `journey_insurance_contact` (`journey_activity_id`,`contact_id`),
	KEY `contact_id` (`contact_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
