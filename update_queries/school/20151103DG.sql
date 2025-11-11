CREATE TABLE IF NOT EXISTS `ts_system_user_schoolsettings` (
  `user_id` smallint(5) unsigned NOT NULL,
  `school_id` smallint(5) unsigned NOT NULL,
  `use_setting` tinyint(1) NOT NULL,
  `emailaccount_id` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
