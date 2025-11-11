CREATE TABLE IF NOT EXISTS `tc_frontend_templates_messages` (
  `template_id` mediumint(8) unsigned NOT NULL,
  `type` varchar(255) NOT NULL COMMENT '''mandatoryfield'',''formatcheck'',''date'',''birthdate'',''email'',''phone''',
  `message` text NOT NULL,
  PRIMARY KEY (`template_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
