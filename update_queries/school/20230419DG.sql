CREATE TABLE IF NOT EXISTS `ts_reporting_agegroups` (
    `id` mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
    `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    `changed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `active` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
    `creator_id` mediumint(9) UNSIGNED NOT NULL,
    `editor_id` mediumint(9) UNSIGNED NOT NULL,
    `name` varchar(255) NOT NULL,
    `age_from` tinyint(3) UNSIGNED NOT NULL,
    `age_until` tinyint(3) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    KEY `active` (`active`)
) ENGINE=InnoDB;
