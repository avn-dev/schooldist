CREATE TABLE IF NOT EXISTS `tc_frontend_templates_fields_dependencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `editor_id` int(11) NOT NULL,
  `creator_id` int(11) NOT NULL,
  `field_id` mediumint(9) NOT NULL,
  `dependency_field_id` int(11) NOT NULL,
  `value` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `active` (`active`,`editor_id`,`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_frontend_templates_fields_dependencies_values` (
  `dependency_id` mediumint(8) unsigned NOT NULL,
  `value` varchar(100) NOT NULL,
  PRIMARY KEY (`dependency_id`,`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;