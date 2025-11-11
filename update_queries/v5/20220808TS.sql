CREATE TABLE IF NOT EXISTS `system_user_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `read_at` timestamp NULL DEFAULT NULL,
  `notifiable` int(11) UNSIGNED NOT NULL,
  `type` varchar(255) NOT NULL,
  `data` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`notifiable`),
  KEY `user_id_2` (`notifiable`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

RENAME TABLE `system_user_messages` TO `__system_user_messages`;
RENAME TABLE `system_notifications_confirm` TO `__system_notifications_confirm`;
