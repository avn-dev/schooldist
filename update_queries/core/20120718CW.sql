CREATE TABLE IF NOT EXISTS `tc_gui2_filtersets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `creator_id` int(11) NOT NULL,
  `editor_id` int(11) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `name` varchar(255) NOT NULL,
  `application` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `creator_id` (`creator_id`),
  KEY `editor_id` (`editor_id`),
  KEY `active` (`active`),
  KEY `application` (`application`),
  KEY `active_2` (`active`,`application`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `tc_gui2_filtersets_bars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `creator_id` int(11) NOT NULL,
  `editor_id` int(11) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `set_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `set_id` (`set_id`),
  KEY `active` (`active`),
  KEY `editor_id` (`editor_id`),
  KEY `creator_id` (`creator_id`),
  KEY `active_2` (`active`,`set_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_gui2_filtersets_bars_elements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `creator_id` int(11) NOT NULL,
  `editor_id` int(11) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `bar_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'input',
  PRIMARY KEY (`id`),
  KEY `creator_id` (`creator_id`),
  KEY `editor_id` (`editor_id`),
  KEY `active` (`active`),
  KEY `bar_id` (`bar_id`),
  KEY `active_2` (`active`,`bar_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_gui2_filtersets_bars_elements_basedon` (
  `element_id` int(11) NOT NULL,
  `base_on` varchar(50) NOT NULL,
  PRIMARY KEY (`element_id`,`base_on`),
  KEY `base_on` (`base_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_gui2_filtersets_bars_elements_i18n` (
  `element_id` int(11) NOT NULL,
  `language_iso` varchar(3) NOT NULL,
  `label` varchar(255) NOT NULL,
  PRIMARY KEY (`element_id`,`language_iso`),
  KEY `language_iso` (`language_iso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_gui2_filtersets_bars_to_usergroups` (
  `bar_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY (`bar_id`,`group_id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;