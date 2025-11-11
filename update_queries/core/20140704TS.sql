ALTER TABLE `tc_flex_sections_fields` ADD `i18n` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `title`;

ALTER TABLE `tc_flex_sections_fields_values` ADD `language_iso` CHAR( 2 ) NULL AFTER `item_id`;

ALTER TABLE `tc_flex_sections_fields_values` DROP PRIMARY KEY , ADD PRIMARY KEY ( `field_id` , `item_id` , `language_iso` );
