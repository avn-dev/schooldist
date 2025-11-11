CREATE TABLE IF NOT EXISTS `ts_student_app_page_log` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `token_hash` varchar(50) NOT NULL,
    `date` timestamp NOT NULL DEFAULT current_timestamp(),
    `page_key` varchar(100) NOT NULL,
    `page_action` varchar(100) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `login_id` (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO `filemanager_tags` (`id`, `changed`, `created`, `active`, `editor_id`, `creator_id`, `entity`, `tag`) VALUES (NULL, current_timestamp(), '0000-00-00 00:00:00.000000', 1, 0, 0, 'Ext_Thebing_Tuition_Class', 'App-Upload');
INSERT INTO `filemanager_tags` (`id`, `changed`, `created`, `active`, `editor_id`, `creator_id`, `entity`, `tag`) VALUES (NULL, current_timestamp(), '0000-00-00 00:00:00.000000', 1, 0, 0, 'Ext_Thebing_Teacher', 'Profile-Picture');

ALTER TABLE `kolumbus_tuition_blocks` ADD `description_app` TEXT NULL DEFAULT NULL AFTER `description`;

UPDATE `wdbasic_attributes` SET `key` = 'student_app_attendance_settings' WHERE `key` = 'student_app_enabled_fields' AND `entity` = 'customer_db_2';
UPDATE `wdbasic_attributes` SET `value` = JSON_ARRAY_INSERT(`value`, '$[0]', 'charts_course') WHERE `key` = 'student_app_attendance_settings' AND `entity` = 'customer_db_2';
UPDATE `wdbasic_attributes` SET `value` = JSON_ARRAY_INSERT(`value`, '$[0]', 'charts_teacher') WHERE `key` = 'student_app_attendance_settings' AND `entity` = 'customer_db_2';

INSERT INTO `wdbasic_attributes` SELECT NULL `id`, 'customer_db_2' `entity`, `cdb2`.`id` `entity_id`, 'student_app_student_can_change_picture' `key`, 1 `value` FROM `customer_db_2` `cdb2` WHERE `cdb2`.`active` = 1 AND EXISTS(SELECT `id` FROM `tc_external_apps` WHERE `app_key` = 'fidelo_student_app' AND `active` = 1);

ALTER TABLE `kolumbus_tuition_blocks` ADD `description_student` TEXT NULL DEFAULT NULL AFTER `description`;

ALTER TABLE `ts_inquiries_contacts_logins_devices` ADD `intro_finished` TINYINT(1) UNSIGNED NULL DEFAULT NULL AFTER `push_permission`;

RENAME TABLE `ts_app_faq_entries` TO `ts_student_app_contents`;
RENAME TABLE `ts_app_faq_entries_i18n` TO `ts_student_app_contents_i18n`;
ALTER TABLE `ts_student_app_contents` ADD `type` ENUM('faq','intro','announcement') NOT NULL DEFAULT 'faq' AFTER `school_id`, ADD INDEX (`type`);
ALTER TABLE `ts_student_app_contents` ADD `valid_until` DATE NOT NULL DEFAULT '0000-00-00' AFTER `created`;

ALTER TABLE `ts_activities_blocks` ADD `advertise` TINYINT(1) UNSIGNED NULL DEFAULT NULL AFTER `released_for_app`;