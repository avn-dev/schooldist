CREATE TABLE IF NOT EXISTS `ts_tuition_courses_to_courses` (
  `master_id` smallint(5) UNSIGNED NOT NULL,
  `course_id` smallint(5) UNSIGNED NOT NULL,
  `type` enum('combination','preparation') NOT NULL COMMENT 'ENUM',
  PRIMARY KEY (`master_id`,`course_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;