ALTER TABLE `ts_activities_blocks`
    CHANGE `school_id` `school_id` SMALLINT(5) UNSIGNED NOT NULL,
	ADD INDEX(`active`),
	ADD INDEX(`school_id`),
	ADD INDEX(`start_week`);
