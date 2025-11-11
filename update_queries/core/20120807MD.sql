CREATE TABLE IF NOT EXISTS `wdbasic_attributes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `class_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u1_attributes` (`name`,`class`,`class_id`),
  KEY `fk1_attributes` (`class`,`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `wdbasic_attributes_decimal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attribute_id` int(10) unsigned NOT NULL,
  `value` decimal(7,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `attribute_id` (`attribute_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `wdbasic_attributes_int` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attribute_id` int(10) unsigned NOT NULL,
  `value` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `attribute_id` (`attribute_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `wdbasic_attributes_tinyint` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attribute_id` int(10) unsigned NOT NULL,
  `value` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `attribute_id` (`attribute_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `wdbasic_attributes_varchar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attribute_id` int(10) unsigned NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `attribute_id` (`attribute_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;