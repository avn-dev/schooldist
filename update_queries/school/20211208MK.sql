ALTER TABLE `ts_superordinate_courses` ADD `position` INT UNSIGNED NOT NULL AFTER `coursecategory_id`;
INSERT INTO `tc_flex_sections` (`id`, `changed`, `created`, `active`, `title`, `type`, `category`) VALUES (53, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '1', 'Administration » Schule » Frontend', 'schools_frontend', 'schools');
ALTER TABLE `customer_db_2` ADD `state` VARCHAR(100) NOT NULL AFTER `city`;
