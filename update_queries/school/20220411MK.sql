UPDATE `tc_flex_sections_fields` SET `changed` = `changed`, `section_id` = 3, `usage` = 'enquiry' WHERE `section_id` = 32 AND `active` = 1;
UPDATE `tc_flex_sections` SET `active` = '0' WHERE `id` = 32;
