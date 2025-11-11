ALTER TABLE `tc_flex_sections_fields_options` ADD `key` VARCHAR(255) NOT NULL AFTER `position`;
ALTER TABLE `tc_flex_sections_fields_options_values` CHANGE `lang_id` `lang_id` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL; 
ALTER TABLE `tc_flex_sections_fields_options_values` ADD PRIMARY KEY( `option_id`, `lang_id`);