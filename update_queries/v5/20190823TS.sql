CREATE TABLE IF NOT EXISTS `system_notifications_confirm` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL,
  `notification_key` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `active` (`user_id`),
  KEY `name` (`notification_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;