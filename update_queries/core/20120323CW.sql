CREATE TABLE IF NOT EXISTS `tc_frontend_templates_css` (
  `template_id` mediumint(8) unsigned NOT NULL,
  `type` varchar(30) NOT NULL COMMENT '''default'',''valid'',''invalid''',
  `class` text NOT NULL,
  PRIMARY KEY (`template_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
