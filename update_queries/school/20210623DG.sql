ALTER TABLE `kolumbus_forms_pages` ADD `type` VARCHAR(255) NOT NULL AFTER `form_id`;

ALTER TABLE `kolumbus_forms_schools` ADD `offer_template_id` SMALLINT NOT NULL COMMENT 'V3' AFTER `tpl_id`;

UPDATE `kolumbus_forms_pages_blocks_settings` SET `value` = 'comment' WHERE `setting` = 'type' AND `value` IN('notice', 'enquiry_student_comment');

UPDATE `kolumbus_forms_pages` SET `type` = 'booking' WHERE `type` = '' AND `form_id` IN (SELECT id FROM `kolumbus_forms` WHERE `type` = 'registration_v3' AND `active` = 1);
