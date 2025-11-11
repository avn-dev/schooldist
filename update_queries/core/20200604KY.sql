CREATE TABLE IF NOT EXISTS `tc_vat_rates_i18n`
(
	`vat_rate_id` mediumint(8) UNSIGNED NOT NULL,
	`language_iso` varchar(50) NOT NULL,
	`note` TEXT NOT NULL,
	PRIMARY KEY (`vat_rate_id`,`language_iso`),
	KEY `language_iso` (`language_iso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;