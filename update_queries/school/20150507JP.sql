CREATE TABLE IF NOT EXISTS `tc_frontend_combinations` (
	`id` int(11) NOT NULL,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `creator_id` int(11) NOT NULL,
  `editor_id` int(11) NOT NULL,
  `last_use` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `name` varchar(100) NOT NULL,
  `usage` varchar(40) NOT NULL,
  `key` varchar(16) NOT NULL,
  `overwritable` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `status` enum('ready','pending','fail') NOT NULL DEFAULT 'ready',
  `last_cache_refresh` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `tc_frontend_combinations`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `key` (`key`), ADD KEY `active` (`active`,`creator_id`,`editor_id`), ADD KEY `usage` (`usage`);

CREATE TABLE IF NOT EXISTS `tc_frontend_combinations_items` (
  `combination_id` int(11) NOT NULL,
  `item` varchar(40) NOT NULL,
  `item_value` varchar(11) NOT NULL,
  `position` mediumint(9) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `tc_frontend_combinations_items`
  ADD PRIMARY KEY (`combination_id`,`item`,`item_value`);