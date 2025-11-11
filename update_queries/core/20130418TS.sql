CREATE TABLE IF NOT EXISTS `tc_marketing_questionaries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `valid_until` date NOT NULL DEFAULT '0000-00-00',
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `editor_id` int(10) unsigned NOT NULL,
  `creator_id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `active` (`active`),
  KEY `valid_until` (`valid_until`),
  KEY `valid_until_active` (`valid_until`,`active`),
  KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_marketing_questionaries_childs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `valid_until` date NOT NULL DEFAULT '0000-00-00',
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `editor_id` int(10) unsigned NOT NULL,
  `creator_id` int(10) unsigned NOT NULL,
  `questionnaire_id` int(11) NOT NULL,
  `type` char(20) NOT NULL,
  `position` tinyint(2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `active` (`active`),
  KEY `valid_until` (`valid_until`),
  KEY `valid_until_active` (`valid_until`,`active`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_marketing_questionaries_childs_headings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `editor_id` int(10) unsigned NOT NULL,
  `creator_id` int(10) unsigned NOT NULL,
  `child_id` int(11) NOT NULL,
  `type` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_marketing_questionaries_childs_headings_i18n` (
  `topic_id` mediumint(8) unsigned NOT NULL,
  `language_iso` char(2) NOT NULL,
  `heading` varchar(255) NOT NULL,
  PRIMARY KEY (`topic_id`,`language_iso`),
  KEY `fk_ta_areas_to_data_languages_data_languages1` (`language_iso`),
  KEY `fk_ta_areas_to_data_languages_ta_areas1` (`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `tc_marketing_questionaries_childs_questions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `editor_id` int(10) unsigned NOT NULL,
  `creator_id` int(10) unsigned NOT NULL,
  `child_id` int(11) NOT NULL,
  `topic_id` mediumint(9) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_marketing_questionaries_childs_questions_to_questions` (
  `questionary_question_id` mediumint(9) NOT NULL,
  `question_id` mediumint(9) NOT NULL,
  PRIMARY KEY (`questionary_question_id`,`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_marketing_questionaries_to_objects` (
  `questionary_id` mediumint(9) NOT NULL,
  `object_id` mediumint(9) NOT NULL,
  PRIMARY KEY (`questionary_id`,`object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_marketing_questionaries_to_subobjects` (
  `questionary_id` mediumint(9) NOT NULL,
  `subobject_id` mediumint(9) NOT NULL,
  PRIMARY KEY (`questionary_id`,`subobject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_marketing_questions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `valid_until` date NOT NULL DEFAULT '0000-00-00',
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `editor_id` int(10) unsigned NOT NULL,
  `creator_id` int(10) unsigned NOT NULL,
  `topic_id` int(11) NOT NULL,
  `question_type` char(20) NOT NULL,
  `dependency_on` char(40) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `active` (`active`),
  KEY `valid_until` (`valid_until`),
  KEY `valid_until_active` (`valid_until`,`active`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_marketing_questions_i18n` (
  `question_id` mediumint(8) unsigned NOT NULL,
  `language_iso` char(2) NOT NULL,
  `question` mediumtext NOT NULL,
  PRIMARY KEY (`question_id`,`language_iso`),
  KEY `fk_ta_areas_to_data_languages_data_languages1` (`language_iso`),
  KEY `fk_ta_areas_to_data_languages_ta_areas1` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_marketing_questions_to_dependency_objects` (
  `question_id` mediumint(9) NOT NULL,
  `object_id` mediumint(9) NOT NULL,
  PRIMARY KEY (`question_id`,`object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_marketing_questions_to_dependency_subobjects` (
  `question_id` mediumint(9) NOT NULL,
  `subobject_id` mediumint(9) NOT NULL,
  PRIMARY KEY (`question_id`,`subobject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_marketing_ratings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `valid_until` date NOT NULL DEFAULT '0000-00-00',
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `editor_id` int(10) unsigned NOT NULL,
  `creator_id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `number_of_ratings` mediumint(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `active` (`active`),
  KEY `valid_until` (`valid_until`),
  KEY `valid_until_active` (`valid_until`,`active`),
  KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_marketing_ratings_childs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `valid_until` date NOT NULL DEFAULT '0000-00-00',
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `editor_id` int(10) unsigned NOT NULL,
  `creator_id` int(10) unsigned NOT NULL,
  `rating_id` int(11) NOT NULL,
  `rating` mediumint(2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `active` (`active`),
  KEY `valid_until` (`valid_until`),
  KEY `valid_until_active` (`valid_until`,`active`),
  KEY `rating_id` (`rating_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_marketing_ratings_childs_i18n` (
  `child_id` mediumint(8) unsigned NOT NULL,
  `language_iso` char(2) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`child_id`,`language_iso`),
  KEY `fk_ta_areas_to_data_languages_data_languages1` (`language_iso`),
  KEY `fk_ta_areas_to_data_languages_ta_areas1` (`child_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_marketing_topics` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `valid_until` date NOT NULL DEFAULT '0000-00-00',
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `editor_id` int(10) unsigned NOT NULL,
  `creator_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `active` (`active`),
  KEY `valid_until` (`valid_until`),
  KEY `valid_until_active` (`valid_until`,`active`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `tc_marketing_topics_i18n` (
  `topic_id` mediumint(8) unsigned NOT NULL,
  `language_iso` char(2) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`topic_id`,`language_iso`),
  KEY `fk_ta_areas_to_data_languages_data_languages1` (`language_iso`),
  KEY `fk_ta_areas_to_data_languages_ta_areas1` (`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;