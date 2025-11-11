ALTER TABLE `tc_flex_sections_fields` ADD `parent_id` INT NOT NULL AFTER `section_id`, ADD INDEX (`parent_id`);
