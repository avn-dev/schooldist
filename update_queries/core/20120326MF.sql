CREATE TABLE IF NOT EXISTS `tc_frontend_templates_templates` (
  `template_id` mediumint(8) unsigned NOT NULL,
  `type` varchar(30) NOT NULL,
  `template` text NOT NULL,
  PRIMARY KEY (`template_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;