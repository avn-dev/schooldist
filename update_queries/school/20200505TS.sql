CREATE TABLE IF NOT EXISTS `ts_inquiries_contacts_logins_devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login_id` mediumint(8) UNSIGNED NOT NULL,
  `app_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `app_version` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `os` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `os_version` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `last_action` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login_id` (`login_id`,`app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
