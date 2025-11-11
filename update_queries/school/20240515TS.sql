ALTER TABLE `kolumbus_tuition_courses` ADD `lessons_unit` ENUM('per_week', 'absolute') NULL DEFAULT NULL AFTER `lessons_per_week`;
ALTER TABLE `kolumbus_tuition_courses` ADD `automatic_extension` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `automatic_renewal`;
ALTER TABLE `ts_inquiries_journeys_courses` ADD `state` BIT(3) NULL DEFAULT NULL AFTER `index_latest_level_change_progress_id`;
ALTER TABLE `ts_inquiries_journeys_courses_tuition_index` ADD `cancelled_lessons` DECIMAL(12,4) UNSIGNED NOT NULL DEFAULT '0.0' AFTER `allocated_lessons`;
UPDATE `kolumbus_tuition_courses` SET `changed` = `changed`, `lessons_unit` = 'absolute' WHERE `per_unit` = 1;
UPDATE `kolumbus_tuition_courses` SET `changed` = `changed`, `lessons_unit` = 'per_week' WHERE `per_unit` IN (0, 2);

CREATE TABLE IF NOT EXISTS `ts_inquiries_journeys_courses_lessons_contingent` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `changed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `editor_id` mediumint(8) UNSIGNED NOT NULL,
    `journey_course_id` int(11) UNSIGNED NOT NULL,
    `program_service_id` int(11) UNSIGNED NOT NULL,
    `absolute` decimal(6,2) NOT NULL DEFAULT 0.00,
    `used` decimal(6,2) NOT NULL DEFAULT 0.00,
    `cancelled` decimal(6,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;