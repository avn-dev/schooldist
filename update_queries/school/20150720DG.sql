
ALTER TABLE `kolumbus_tuition_attendance`
	CHANGE `created` `created` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	CHANGE `user_id` `user_id` SMALLINT NOT NULL,
	ADD `changed` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `created`,
	ADD `active` TINYINT NOT NULL DEFAULT '1' AFTER `changed`,
	ADD `creator_id` SMALLINT NOT NULL AFTER `active`,
	ADD INDEX (`active`)
;

CREATE TABLE IF NOT EXISTS `ts_examinations_templates_terms` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,                                                                                                                                                                                              
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',                                                                                                                                                                                      
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `creator_id` smallint(5) unsigned NOT NULL,
  `editor_id` smallint(5) unsigned NOT NULL,
  `template_id` mediumint(8) unsigned NOT NULL,
  `type` enum('fix','individual') CHARACTER SET ascii NOT NULL,
  `period` enum('one_time','recurring') CHARACTER SET ascii NOT NULL,
  `period_length` smallint(5) unsigned NOT NULL,
  `period_unit` enum('days','weeks') CHARACTER SET ascii NOT NULL,
  `start_date` date NOT NULL DEFAULT '0000-00-00',
  `start_from` enum('after_course_start','before_course_end') CHARACTER SET ascii NOT NULL,
  PRIMARY KEY (`id`),
  KEY `template_id` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `customer_db_2`
	ADD `examination_score_passed` DECIMAL(7,5) NOT NULL AFTER `critical_attendance`;

ALTER TABLE `kolumbus_pdf_templates_types`
	ADD `html_as_textarea` TINYINT(1) NOT NULL DEFAULT '0' AFTER `name`;