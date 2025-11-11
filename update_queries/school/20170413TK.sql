CREATE TABLE IF NOT EXISTS `ts_accommodation_providers_requirements` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `user_id` mediumint(9) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ts_accommodation_providers_requirements_documents` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `requirement_id` int(8) NOT NULL,
  `accommodation_provider_id` int(8) NOT NULL,
  `active` int(11) NOT NULL DEFAULT '1',
  `name` varchar(255) NOT NULL DEFAULT '',
  `file` varchar(255) DEFAULT NULL,
  `valid` date NOT NULL,
  `always_valid` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `accommodation_provider_id` (`accommodation_provider_id`),
  KEY `requirement_id` (`requirement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ts_accommodation_categories_to_requirements` (
  `requirement_id` int(11) NOT NULL,
  `accommodation_category_id` int(11) NOT NULL,
  PRIMARY KEY (`requirement_id`,`accommodation_category_id`),
  KEY `requirement_id` (`requirement_id`),
  KEY `accommodation_category_id` (`accommodation_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;