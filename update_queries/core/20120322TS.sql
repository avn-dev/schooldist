CREATE TABLE IF NOT EXISTS `tc_frontend_checkboxes`
(
	`id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
	`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
	`active` tinyint(1) unsigned NOT NULL DEFAULT '1',
	`valid_until` date NOT NULL,
	`creator_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
	`editor_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
	`name` varchar(255) NOT NULL,
	PRIMARY KEY (`id`),
	KEY `active` (`active`),
	KEY `valid_until` (`valid_until`),
	KEY `creator_id` (`creator_id`),
	KEY `editor_id` (`editor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `tc_frontend_checkboxes_contents_i18n`
(
	`content_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
	`language_iso` char(2) NOT NULL,
	`content` text NOT NULL,
	PRIMARY KEY (`content_id`,`language_iso`),
	KEY `language_iso` (`language_iso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_frontend_checkboxes_contents`
(
	`id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
	`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
	`active` tinyint(1) unsigned NOT NULL DEFAULT '1',
	`creator_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
	`editor_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
	`checkbox_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
	`type` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	KEY `active` (`active`),
	KEY `checkbox_id` (`checkbox_id`),
	KEY `creator_id` (`creator_id`),
	KEY `editor_id` (`editor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;