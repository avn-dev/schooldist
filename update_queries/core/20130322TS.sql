CREATE TABLE IF NOT EXISTS `tc_flex_sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `title` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  KEY `type` (`type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_flex_sections_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `placeholder` varchar(255) NOT NULL,
  `type` tinyint(2) NOT NULL DEFAULT '0',
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `validate_by` varchar(255) NOT NULL,
  `regex` varchar(255) NOT NULL,
  `error` text NOT NULL,
  `position` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `section_id` (`section_id`),
  KEY `user_id` (`user_id`),
  KEY `creator_id` (`creator_id`),
  KEY `active_section_id` (`active`,`section_id`),
  KEY `position` (`position`),
  KEY `active` (`active`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `tc_flex_sections_fields_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `user_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `position` int(3) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `active` (`active`),
  KEY `user_id` (`user_id`),
  KEY `field_id` (`field_id`),
  KEY `position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_flex_sections_fields_options_values` (
  `option_id` int(11) NOT NULL,
  `lang_id` varchar(5) NOT NULL,
  `title` varchar(255) NOT NULL,
  KEY `option_id` (`option_id`),
  KEY `lang_id` (`lang_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_flex_sections_fields_values` (
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `field_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`field_id`,`item_id`),
  KEY `field_id` (`field_id`,`item_id`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;