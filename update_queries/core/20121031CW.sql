ALTER TABLE `tc_flex_sections_fields_values` DROP `id` , DROP `active` ;
ALTER TABLE `tc_flex_sections_fields_values` ADD PRIMARY KEY ( `field_id` , `item_id` ) ;