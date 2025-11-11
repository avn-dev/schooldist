ALTER TABLE  `tc_complaints_categories` ADD  `short_name` VARCHAR( 255 ) NOT NULL AFTER  `title`;
ALTER TABLE  `tc_complaints_categories_subcategories` ADD  `short_name` VARCHAR( 255 ) NOT NULL AFTER  `title`;
ALTER TABLE `tc_complaints_categories_subcategories` ADD `valid_until` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `description`;