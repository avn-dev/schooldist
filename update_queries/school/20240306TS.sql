ALTER TABLE `kolumbus_classroom` ADD `teacher_portal` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `online`;
ALTER TABLE `ts_teachers` CHANGE `access_rights` `access_rights` BIT(7) NOT NULL DEFAULT b'10111';
RENAME TABLE `kolumbus_tuition_blocks_days_comments` TO `ts_tuition_blocks_days_data`;
ALTER TABLE `ts_tuition_blocks_days_data` ADD `state` BIT(3) NULL DEFAULT NULL AFTER `changed`;
ALTER TABLE `ts_tuition_blocks_days_data` ADD `state_comment` MEDIUMTEXT NULL DEFAULT NULL AFTER `state`;
