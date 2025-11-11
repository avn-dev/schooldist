ALTER TABLE `kolumbus_tuition_courses` ADD `numberrange_id` SMALLINT UNSIGNED NULL DEFAULT NULL AFTER `fix_duration`;
ALTER TABLE `ts_inquiries_journeys_courses` ADD `number` VARCHAR(50) NULL DEFAULT NULL AFTER `flexible_allocation`;
ALTER TABLE `ts_inquiries_journeys_courses` ADD `numberrange_id` SMALLINT UNSIGNED NULL DEFAULT NULL AFTER `number`;
