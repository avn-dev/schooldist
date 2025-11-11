UPDATE `kolumbus_forms_pages_blocks_settings` SET `value` = '1' WHERE (`setting` LIKE 'course_%' OR `setting` LIKE 'coursefollowing_%') AND `value` = 'on';

DELETE FROM `kolumbus_forms_pages_blocks_settings` WHERE (`setting` LIKE 'course_%' OR `setting` LIKE 'coursefollowing_%') AND (`value` = '0' OR `value` = 'off');
