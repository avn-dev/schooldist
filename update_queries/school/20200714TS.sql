ALTER TABLE `kolumbus_tuition_courses` ADD `online` TINYINT(1) NOT NULL DEFAULT '0' AFTER `levelgroup_id`, ADD INDEX (`online`);
ALTER TABLE `kolumbus_classroom` ADD `online` TINYINT(1) NOT NULL DEFAULT '0' AFTER `name`, ADD INDEX (`online`);
ALTER TABLE `kolumbus_tuition_blocks_inquiries_courses` ADD `room_id` INT NOT NULL AFTER `block_id`, ADD INDEX (`room_id`);

ALTER TABLE `kolumbus_tuition_blocks_inquiries_courses` DROP INDEX `uniquie_ktbic`, ADD UNIQUE `uniquie_ktbic` (`block_id`, `inquiry_course_id`, `course_id`, `room_id`) USING BTREE;

CREATE TABLE IF NOT EXISTS `kolumbus_tuition_blocks_to_rooms` (
  `block_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  PRIMARY KEY (`block_id`,`room_id`),
  KEY `course_id` (`block_id`),
  KEY `unit_id` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
