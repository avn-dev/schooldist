CREATE TABLE IF NOT EXISTS `tc_external_apps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `editor_id` int(11) NOT NULL,
  `creator_id` int(11) NOT NULL,
  `app_key` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_key` (`app_key`,`active`) USING BTREE,
  KEY `active` (`active`,`editor_id`,`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `system_elements` (`id`, `title`, `description`, `element`, `category`, `file`, `version`, `parent`, `image`, `documentation`, `template`, `sql`, `administrable`, `include_backend`, `include_frontend`, `include_mode`, `active`) VALUES (NULL, 'TcExternalApps', '', 'bundle', 'Thebing', 'TcExternalApps', '0.010', '', '', '', '', '', '0', '1', '0', '0', '1');