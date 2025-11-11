CREATE TABLE IF NOT EXISTS `ts_inquiries_journeys_additionalservices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `journey_id` mediumint(9) NOT NULL,
  `additionalservice_id` int(11) NOT NULL,
  `relation` enum('course','accommodation') DEFAULT NULL,
  `relation_id` int(11) DEFAULT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT '1',
  `comment` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `creator_id` (`creator_id`),
  KEY `journey_id` (`journey_id`),
  KEY `additionalservice_id` (`additionalservice_id`),
  KEY `active` (`active`),
  KEY `visible` (`visible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ts_inquiries_journeys_additionalservices_to_travellers` (
  `journey_additionalservice_id` int(11) NOT NULL,
  `contact_id` mediumint(9) NOT NULL,
  UNIQUE KEY `journey_insurance_contact` (`journey_additionalservice_id`,`contact_id`),
  KEY `contact_id` (`contact_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

