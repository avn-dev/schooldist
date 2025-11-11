INSERT INTO `system_elements` (`id`, `title`, `description`, `element`, `category`, `file`, `version`, `parent`, `image`, `documentation`, `template`, `sql`, `administrable`, `include_backend`, `include_frontend`, `include_mode`, `active`)
VALUES (NULL, 'TsRegistrationForm', '', 'bundle', 'Fidelo', 'TsRegistrationForm', '0.01', '', '', '', '', '', '0', '1', '0', '0', '1');

DELETE FROM `kolumbus_forms_translations` WHERE `item` = 'form' AND `field` IN ('errorinternal', 'extension', 'extensionsize');

UPDATE `kolumbus_forms` SET `active` = 0 WHERE `type` = 'registration';

ALTER TABLE `ts_inquiries` ADD `status` ENUM('ready','pending','fail') CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT 'ready' AFTER `active`;

UPDATE `kolumbus_forms_pages_blocks_settings` SET `value` = 'file_photo' WHERE `setting` = 'type' AND `value` = 'photo';

UPDATE `kolumbus_forms_pages_blocks_settings` SET `value` = 'file_passport' WHERE `setting` = 'type' AND `value` = 'pass';
