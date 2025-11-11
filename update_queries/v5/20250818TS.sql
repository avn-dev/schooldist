ALTER TABLE `system_user` CHANGE `authentication` `authentication` ENUM('simple','googletwofactor','passkeys','passkeys_extern') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;
ALTER TABLE `system_user` ADD `multi_login` TINYINT(1) NULL DEFAULT NULL AFTER `authentication`;

CREATE TABLE IF NOT EXISTS `system_user_passkeys` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `changed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    `creator_id` int(11) NOT NULL DEFAULT 0,
    `editor_id` int(11) NOT NULL,
    `active` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
    `user_id` int(11) UNSIGNED NOT NULL,
    `name` varchar(255) NOT NULL,
    `credential_id` varchar(255) NOT NULL,
    `data` text NOT NULL,
    `last_login` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `credential_id` (`credential_id`),
    KEY `creator_id` (`creator_id`),
    KEY `editor_id` (`editor_id`),
    KEY `active` (`active`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `system_user_devices` (
    `user_id` int(11) UNSIGNED NOT NULL,
    `device_id` int(11) UNSIGNED NOT NULL,
    `last_login` timestamp NULL DEFAULT NULL,
    `login_count` int(10) UNSIGNED NOT NULL,
    `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY (`user_id`,`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `system_trusted_devices` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `changed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    `creator_id` int(11) NOT NULL DEFAULT 0,
    `editor_id` int(11) NOT NULL,
    `active` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
    `name` varchar(255) NOT NULL,
    `device_token` varchar(255) NOT NULL,
    `expires_at` timestamp NULL DEFAULT NULL,
    `ip` varchar(255) NOT NULL,
    `user_agent` text NOT NULL,
    `last_login` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `credential_id` (`device_token`),
    KEY `creator_id` (`creator_id`),
    KEY `editor_id` (`editor_id`),
    KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;