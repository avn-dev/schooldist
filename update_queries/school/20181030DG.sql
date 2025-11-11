CREATE TABLE IF NOT EXISTS `ts_transfer_locations` (
  `id` smallint(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `changed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `creator_id` smallint(6) UNSIGNED NOT NULL DEFAULT 0,
  `editor_id` smallint(5) UNSIGNED NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT 1,
  `valid_until` date NOT NULL DEFAULT '0000-00-00',
  `position` smallint(11) UNSIGNED NOT NULL DEFAULT 0,
  `short` varchar(50) NOT NULL,
  `address` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `active` (`active`),
  KEY `valid_until` (`valid_until`),
  KEY `position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ts_transfer_locations_schools` (
  `location_id` smallint(5) UNSIGNED NOT NULL,
  `school_id` smallint(5) UNSIGNED NOT NULL,
  PRIMARY KEY (`location_id`,`school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ts_transfer_locations_i18n` (
  `location_id` smallint(8) UNSIGNED NOT NULL,
  `language_iso` varchar(50) CHARACTER SET ascii NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`location_id`,`language_iso`),
  KEY `language_iso` (`language_iso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ts_transfer_locations_terminals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `changed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `creator_id` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `editor_id` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `location_id` smallint(5) UNSIGNED NOT NULL,
  `short` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `active` (`active`),
  KEY `location_id` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ts_transfer_locations_terminals_i18n` (
  `location_terminal_id` smallint(8) UNSIGNED NOT NULL,
  `language_iso` varchar(50) CHARACTER SET ascii NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`location_terminal_id`,`language_iso`),
  KEY `language_iso` (`language_iso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP FUNCTION IF EXISTS getTransferLocation;
