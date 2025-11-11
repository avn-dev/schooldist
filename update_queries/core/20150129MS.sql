CREATE TABLE IF NOT EXISTS `tc_complaints` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `creator_id` mediumint(9) NOT NULL,
  `editor_id` mediumint(9) NOT NULL,
  `inquiry_id` mediumint(9) NOT NULL,
  `category_id` mediumint(9) NOT NULL,
  `sub_category_id` mediumint(9) NOT NULL,
  `type_id` mediumint(9) NOT NULL,
  `type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `inquiry_id` (`inquiry_id`,`category_id`,`sub_category_id`,`type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;