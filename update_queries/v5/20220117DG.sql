CREATE TABLE IF NOT EXISTS `gui2_filter_queries` (
	`id` mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
	`created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
	`changed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
	`active` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
	`creator_id` mediumint(9) UNSIGNED NOT NULL,
	`editor_id` mediumint(9) UNSIGNED NOT NULL,
	`gui_hash` varchar(100) NOT NULL,
	`name` varchar(25) NOT NULL,
	`dependency` varchar(255) DEFAULT NULL,
	`visibility` enum('all','user') NOT NULL DEFAULT 'all',
	PRIMARY KEY (`id`),
	KEY `active` (`active`),
	KEY `gui_hash` (`gui_hash`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `gui2_filter_queries_filters` (
	`filter_query_id` smallint(5) UNSIGNED NOT NULL,
	`filter` varchar(50) CHARACTER SET ascii NOT NULL,
	`type` varchar(20) NOT NULL,
	`negated` tinyint(1) NOT NULL DEFAULT 0,
	`value` varchar(1024) NOT NULL,
	PRIMARY KEY (`filter_query_id`,`filter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
