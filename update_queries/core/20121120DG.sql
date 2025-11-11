CREATE TABLE IF NOT EXISTS `tc_communication_automatictemplates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `editor_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `layout_id` int(11) NOT NULL,
  `type_id` tinyint(1) NOT NULL,
  `execution_time` time DEFAULT NULL,
  `event_id` tinyint(4) NOT NULL,
  `additional_id` tinyint(4) NOT NULL,
  `days` tinyint(4) NOT NULL,
  `date_id` tinyint(4) NOT NULL,
  `date` date NOT NULL,
  `to` varchar(255) NOT NULL,
  `cc` varchar(255) NOT NULL,
  `bcc` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `active` (`active`),
  KEY `user_id` (`editor_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_communication_automatictemplates_recipients` (
  `template_id` int(10) unsigned NOT NULL,
  `recipient` varchar(50) NOT NULL,
  PRIMARY KEY (`template_id`,`recipient`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;