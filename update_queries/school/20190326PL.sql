CREATE TABLE IF NOT EXISTS `ts_activities` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
			`active` tinyint(1) NOT NULL DEFAULT '1',
			`valid_until` date NOT NULL DEFAULT '0000-00-00',
			`editor_id` int(11) NOT NULL,
			`creator_id` int(11) NOT NULL,
			`provider_id` int(11) NOT NULL,
			`position` tinyint(5) NOT NULL,
			`name` varchar(70) NOT NULL,
			`short` varchar(5) NOT NULL,
			`without_course` tinyint(1) NOT NULL,
			`billing_period` enum('payment_per_week','payment_per_block') NOT NULL,
			`availability` enum('always_available','limited_availability') NOT NULL,
			`min_students` int(11) NOT NULL,
			`max_students` int(11) NOT NULL,
			`free_of_charge` tinyint(1) NOT NULL,
			`show_for_free` tinyint(1) NOT NULL,
			PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `ts_activities_blocks_to_travellers`
	RENAME TO `ts_activities_blocks_travellers`;

ALTER TABLE `ts_activities_blocks_travellers` ADD `changed` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `id`, ADD `created` TIMESTAMP NOT NULL AFTER `changed`, ADD `active` TINYINT NOT NULL DEFAULT '1' AFTER `created`, ADD `valid_until` DATE NOT NULL AFTER `active`, ADD `editor_id` INT NOT NULL AFTER `valid_until`, ADD `creator_id` INT NOT NULL AFTER `editor_id`;

ALTER TABLE `ts_activities_schools_to_courses` ADD PRIMARY KEY( `activity_school_id`, `course_id`);

ALTER TABLE `ts_activities_blocks_accompanying_persons` ADD PRIMARY KEY( `block_id`, `user_id`);

ALTER TABLE `ts_activities_blocks_days` ADD INDEX(`activity_block_id`);

ALTER TABLE `ts_activities_blocks_to_activities` ADD PRIMARY KEY( `block_id`, `activity_id`);

ALTER TABLE `ts_activities_schools` ADD UNIQUE( `activity_id`, `school_id`);

ALTER TABLE `ts_inquiries_journeys_activities` ADD INDEX(`active`), ADD INDEX(`visible`);
