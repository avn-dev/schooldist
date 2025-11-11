CREATE TABLE IF NOT EXISTS `tc_uploader` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `editor_id` int(11) NOT NULL DEFAULT '0',
  `description` varchar(30) NOT NULL,
  `path` varchar(255) NOT NULL,
  `namespace_id` int(11) NOT NULL,
  `namespace` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `active` (`active`),
  KEY `creator_id` (`creator_id`),
  KEY `editor_id` (`editor_id`),
  KEY `namespace_id` (`namespace_id`,`namespace`),
  KEY `namespace_id_2` (`namespace_id`),
  KEY `namespace` (`namespace`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;