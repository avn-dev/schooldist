CREATE TABLE IF NOT EXISTS `gui2_dialog_infotexts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `private` tinyint(1) NOT NULL DEFAULT '0',
  `gui_hash` varchar(100) NOT NULL,
  `dialog_id` varchar(100) NOT NULL,
  `field` varchar(150) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `gui_hash` (`gui_hash`,`field`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `gui2_dialog_infotexts_i18n` (
  `infotext_id` int(11) NOT NULL,
  `language` char(2) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`infotext_id`,`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;