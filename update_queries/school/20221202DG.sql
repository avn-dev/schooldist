INSERT INTO `system_elements` (`title`, `description`, `element`, `category`, `file`, `version`, `parent`, `image`, `documentation`, `template`, `sql`, `administrable`, `include_backend`, `include_frontend`, `include_mode`, `active`) VALUES
    ('TsReporting', '', 'bundle', 'Fidelo', 'TsReporting', '0.01', '', '', '', '', '', 0, '1', '0', 0, 1);

CREATE TABLE IF NOT EXISTS `ts_reporting_reports` (
    `id` mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
    `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    `changed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `active` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
    `creator_id` mediumint(9) UNSIGNED NOT NULL,
    `editor_id` mediumint(9) UNSIGNED NOT NULL,
    `name` varchar(50) NOT NULL,
    `base` varchar(255) NOT NULL,
    `last_access` TIMESTAMP NULL DEFAULT NULL,
    `visualization` enum('table','pivot') NOT NULL,
    `description` TEXT NOT NULL,
    PRIMARY KEY (`id`),
    KEY `active` (`active`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `ts_reporting_reports_settings` (
    `id` mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
    `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    `changed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `active` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
    `report_id` mediumint(8) UNSIGNED NOT NULL,
    `position` tinyint(3) UNSIGNED NOT NULL,
    `type` varchar(50) NOT NULL,
    `object` varchar(255) NOT NULL,
    `config` text DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `active` (`active`),
    KEY `report_id` (`report_id`)
) ENGINE=InnoDB;

ALTER TABLE `ts_reporting_reports` CHANGE `name` `name` VARCHAR(255) NOT NULL;
