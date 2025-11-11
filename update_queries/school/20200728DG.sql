CREATE TABLE IF NOT EXISTS `ts_inquiries_payments_processes` (
	`id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
	`created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
	`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`active` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
	`document_id` mediumint(8) UNSIGNED NOT NULL,
	`payment_id` mediumint(9) DEFAULT NULL,
	`hash` varchar(32) CHARACTER SET ascii NOT NULL,
	`paymentterm_index` smallint(5) UNSIGNED NOT NULL,
	`seen` timestamp NULL DEFAULT NULL,
	`payed` timestamp NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `hash` (`hash`),
	UNIQUE KEY `unique` (`document_id`,`paymentterm_index`) USING BTREE,
	KEY `document_id` (`document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;