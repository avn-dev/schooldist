CREATE TABLE IF NOT EXISTS `tc_complaints_histories` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `creator_id` mediumint(9) NOT NULL,
  `editor_id` mediumint(9) NOT NULL,
  `complaint_id` mediumint(9) NOT NULL,
  `comment` text NOT NULL,
  `state` varchar(255) NOT NULL,
  `comment_type` varchar(255) NOT NULL,
  `followup` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `creator_id` (`creator_id`,`editor_id`,`complaint_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_complaints_categories` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `creator_id` mediumint(9) NOT NULL,
  `editor_id` mediumint(9) NOT NULL,
  `position` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `creator_id` (`creator_id`,`editor_id`),
  KEY `position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_complaints_categories_subcategories` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `creator_id` mediumint(9) NOT NULL,
  `editor_id` mediumint(9) NOT NULL,
  `category_id` mediumint(9) NOT NULL,
  `position` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `creator_id` (`creator_id`,`editor_id`,`category_id`),
  KEY `position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;