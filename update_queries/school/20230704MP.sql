ALTER TABLE `ts_screens` CHANGE `user_id` `editor_id` INT(10) UNSIGNED NOT NULL;

RENAME TABLE `ts_schools_to_productlines` TO `ts_productlines_schools`;