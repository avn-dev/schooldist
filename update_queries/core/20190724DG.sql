CREATE TABLE IF NOT EXISTS `tc_frontend_log` (
	`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`combination_id` smallint(5) UNSIGNED DEFAULT NULL,
	`template_id` smallint(5) UNSIGNED DEFAULT NULL,
	`session_id` varchar(255) DEFAULT NULL,
	`method` varchar(255) DEFAULT NULL,
	`url` varchar(1000) DEFAULT NULL,
	`user_agent` varchar(1000) DEFAULT NULL,
	`ip` varbinary(16) DEFAULT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='DO NOT TRUNCATE';