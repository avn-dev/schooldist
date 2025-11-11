CREATE TABLE IF NOT EXISTS `tc_event_management` (
     `id` mediumint(9) NOT NULL AUTO_INCREMENT,
     `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
     `creator_id` int(11) NOT NULL,
     `editor_id` int(11) NOT NULL,
     `valid_until` date NOT NULL DEFAULT '0000-00-00',
     `active` tinyint(1) NOT NULL DEFAULT '1',
     `name` varchar(255) NOT NULL,
     `event_name` varchar(255) NOT NULL,
     `execution_day` char(2) DEFAULT NULL,
     `execution_time` tinyint(2) DEFAULT NULL,
     `execution_weekend` tinyint(1) DEFAULT NULL,
     `last_action` timestamp NULL DEFAULT NULL,
     PRIMARY KEY (`id`),
     KEY `active` (`active`),
     KEY `created` (`created`),
     KEY `event_name` (`active`,`event_name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `tc_event_management_childs` (
    `id` mediumint(9) NOT NULL AUTO_INCREMENT,
    `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    `creator_id` int(11) NOT NULL,
    `editor_id` int(11) NOT NULL,
    `active` tinyint(1) NOT NULL DEFAULT '1',
    `event_id` int(11) NOT NULL,
    `type` enum('listener','condition') NOT NULL,
    `class` char(100) NOT NULL,
    `position` int(11) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `active` (`active`),
    KEY `created` (`created`),
    KEY `parent` (`active`,`event_id`,`type`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_event_management_listeners_to_users` (
    `listener_id` int(11) NOT NULL,
    `type` enum('user','group') NOT NULL,
    `type_id` int(11) NOT NULL,
    PRIMARY KEY (`listener_id`,`type`,`type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
