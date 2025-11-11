CREATE TABLE IF NOT EXISTS `kolumbus_course_startdates_levels` (
	`type` enum('startdate','runtime') CHARACTER SET ascii NOT NULL,
	`type_id` mediumint(8) UNSIGNED NOT NULL,
	`level_id` smallint(5) UNSIGNED NOT NULL,
	PRIMARY KEY (`type`,`type_id`,`level_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='startdates + runtimes';
