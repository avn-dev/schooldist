CREATE TABLE IF NOT EXISTS `tc_wdmvc_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `creator_id` int(11) NOT NULL,
  `editor_id` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `token` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `token` (`token`),
  KEY `active` (`active`,`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `tc_wdmvc_tokens_ips` (
  `token_id` int(11) NOT NULL,
  `ip` varchar(20) NOT NULL,
  PRIMARY KEY (`token_id`,`ip`),
  KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8; 


CREATE TABLE IF NOT EXISTS `tc_wdmvc_tokens_applications` (
  `token_id` int(11) NOT NULL,
  `application` varchar(20) NOT NULL,
  PRIMARY KEY (`token_id`,`application`),
  KEY `application` (`application`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `tc_wdmvc_tokens_ips` CHANGE `ip` `ip` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;