
CREATE TABLE IF NOT EXISTS `ts_referrers_to_schools` (
  `referrer_id` smallint(5) UNSIGNED NOT NULL,
  `school_id` smallint(5) UNSIGNED NOT NULL,
  PRIMARY KEY (`referrer_id`,`school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
