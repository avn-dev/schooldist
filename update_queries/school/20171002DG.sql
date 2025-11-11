CREATE TABLE `ts_schools_classrooms_usage` (
  `school_id` smallint(5) UNSIGNED NOT NULL,
  `classroom_id` smallint(5) UNSIGNED NOT NULL,
  `position` smallint(5) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
