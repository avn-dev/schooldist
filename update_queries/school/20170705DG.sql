
CREATE TABLE IF NOT EXISTS `kolumbus_visum_status_flex_fields` (
  `visa_status_id` smallint(5) UNSIGNED NOT NULL,
  `flex_field_id` smallint(5) UNSIGNED NOT NULL,
  PRIMARY KEY (`visa_status_id`,`flex_field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* DB-UPDATE

INSERT INTO `tc_flex_sections` (`id`, `changed`, `created`, `active`, `title`, `type`, `category`) VALUES
(39, '2017-07-05 15:08:33', '0000-00-00 00:00:00', 1, 'Student Record  » Visum » Status', 'student_record_visum_status', 'student_record');

*/
