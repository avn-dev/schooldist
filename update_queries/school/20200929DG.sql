UPDATE `kolumbus_forms_pages_blocks_settings` SET `setting` = 'show_level' WHERE `setting` = 'show_course_level';
UPDATE `kolumbus_forms_pages_blocks_settings` SET `value` = 'all' WHERE `setting` = 'show_level' AND `value` = '1';
DELETE FROM `kolumbus_forms_pages_blocks_settings` WHERE `setting` = 'show_level' AND `value` = '0';