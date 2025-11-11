ALTER TABLE `ts_activities_providers` CHANGE `id` `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `ts_activities_blocks` ADD `provider_id` INT(11) UNSIGNED NULL DEFAULT NULL;
CREATE TABLE IF NOT EXISTS `ts_activities_to_activities_providers` (
   `activity_id` int(11) NOT NULL,
   `provider_id` int(11) NOT NULL,
    PRIMARY KEY (`activity_id`,`provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;