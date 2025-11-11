CREATE TABLE IF NOT EXISTS `ts_accommodation_cleaning_status` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `user_id` mediumint(9) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `date` date NOT NULL,
  `room_id` int(11) NOT NULL,
  `bed` tinyint(2) NOT NULL,
  `type_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `status` char(30) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `creator_id` (`creator_id`),
  KEY `date` (`date`,`room_id`),
  KEY `type_id` (`type_id`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ts_accommodation_cleaning_types` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `user_id` mediumint(9) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `name` varchar(255) NOT NULL,
  `short` char(30) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ts_accommodation_cleaning_types_cycles` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `user_id` mediumint(9) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `type_id` int(11) NOT NULL,
  `mode` char(30) NOT NULL,
  `count` tinyint(2) NOT NULL,
  `count_mode` char(30) NOT NULL,
  `time` char(30) NOT NULL,
  `weekday` char(2) NOT NULL,
  `depending` tinyint(1) NOT NULL DEFAULT '0',
  `depending_days` tinyint(2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `creator_id` (`creator_id`),
  KEY `type_id` (`type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ts_accommodation_cleaning_types_to_accommodation_categories` (
  `type_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`type_id`,`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ts_accommodation_cleaning_types_to_rooms` (
  `type_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  PRIMARY KEY (`type_id`,`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ts_accommodation_cleaning_types_to_schools` (
  `type_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  PRIMARY KEY (`type_id`,`school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
