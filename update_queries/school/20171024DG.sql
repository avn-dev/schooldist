ALTER TABLE `kolumbus_costs_kategorie_teacher` ADD `grouping` ENUM('week','month') NOT NULL DEFAULT 'week';

ALTER TABLE `ts_teachers_payments` ADD `calculation` TINYINT NOT NULL AFTER `hours`;

ALTER TABLE `ts_teachers_payments` CHANGE `course_list` `course_list` VARCHAR(1024) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'CSV';

ALTER TABLE `ts_teachers_payments` ADD `block_list` VARCHAR(1024) NOT NULL COMMENT 'CSV' AFTER `course_list`;
