
ALTER TABLE `kolumbus_student_status` ADD `valid_until` DATE NOT NULL DEFAULT '0000-00-00' AFTER `active`;

CREATE TABLE IF NOT EXISTS `kolumbus_student_status_schools` (
  `status_id` smallint(5) UNSIGNED NOT NULL,
  `school_id` smallint(5) UNSIGNED NOT NULL,
  PRIMARY KEY (`status_id`,`school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
