CREATE TABLE IF NOT EXISTS `ts_schools_app_settings` (
  `school_id` smallint(5) unsigned NOT NULL,
  `key` varchar(255) CHARACTER SET armscii8 NOT NULL,
  `additional` varchar(255) CHARACTER SET armscii8 NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;