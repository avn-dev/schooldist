UPDATE `tc_flex_sections` SET `category` = 'admin_users' WHERE `type` = 'admin_users';
UPDATE `tc_flex_sections_fields` SET `section_id` = 40 WHERE `section_id` = 50;
DELETE FROM `tc_flex_sections` WHERE `id` = 50;