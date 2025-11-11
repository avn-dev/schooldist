CREATE TABLE IF NOT EXISTS `tc_language_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `file_id` int(11) NOT NULL DEFAULT '0',
  `code` text NOT NULL,
  `de` text NOT NULL,
  `en` text NOT NULL,
  `fr` text NOT NULL,
  `nl` text NOT NULL,
  `es` text NOT NULL,
  `it` text NOT NULL,
  `ja` text NOT NULL,
  `zh` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `file_id` (`file_id`),
  KEY `active` (`active`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;