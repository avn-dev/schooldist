CREATE TABLE IF NOT EXISTS `tc_placeholder_examples` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `valid_until` date NOT NULL DEFAULT '0000-00-00',
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `editor_id` int(10) unsigned NOT NULL,
  `creator_id` int(10) unsigned NOT NULL,
  `position` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `active` (`active`),
  KEY `valid_until` (`valid_until`),
  KEY `valid_until_active` (`valid_until`,`active`),
  KEY `name` (`position`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_placeholder_examples_entries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `valid_until` date NOT NULL DEFAULT '0000-00-00',
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `editor_id` int(10) unsigned NOT NULL,
  `creator_id` int(10) unsigned NOT NULL,
  `example_id` mediumint(8) NOT NULL,
  `value` text NOT NULL,
  `position` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `active` (`active`),
  KEY `valid_until` (`valid_until`),
  KEY `valid_until_active` (`valid_until`,`active`),
  KEY `name` (`position`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_placeholder_examples_entries_i18n` (
  `example_entry_id` mediumint(8) unsigned NOT NULL,
  `language_iso` char(2) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`language_iso`,`example_entry_id`),
  KEY `fk_ta_courses_i18n_t_languages1` (`language_iso`),
  KEY `fk_ta_accommodations_i18n_copy1_ta_accommodationcategories1` (`example_entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_placeholder_examples_entries_to_applications` (
  `example_entry_id` mediumint(9) NOT NULL,
  `application` varchar(50) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'traveller',
  PRIMARY KEY (`example_entry_id`,`application`,`type`),
  KEY `type` (`type`),
  KEY `inquiry_id` (`example_entry_id`,`type`),
  KEY `contact_id` (`application`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_placeholder_examples_i18n` (
  `example_id` mediumint(8) unsigned NOT NULL,
  `language_iso` char(2) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`language_iso`,`example_id`),
  KEY `fk_ta_courses_i18n_t_languages1` (`language_iso`),
  KEY `fk_ta_accommodations_i18n_copy1_ta_accommodationcategories1` (`example_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;