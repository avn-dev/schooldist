ALTER TABLE `tc_flex_sections_fields_values` ADD `item_type` VARCHAR( 100 ) NOT NULL AFTER `item_id`;

ALTER TABLE `tc_flex_sections_fields_values` DROP PRIMARY KEY , ADD PRIMARY KEY ( `field_id` , `item_id` , `language_iso` , `item_type` );
