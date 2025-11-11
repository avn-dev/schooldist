ALTER TABLE `kolumbus_classroom` CHANGE `sort_order` `position` INT(11) NOT NULL;
ALTER TABLE `kolumbus_classroom` CHANGE `user_id` `editor_id` INT(11) NOT NULL;
ALTER TABLE `kolumbus_tuition_templates` CHANGE `user_id` `editor_id` INT(11) NOT NULL;
ALTER TABLE `kolumbus_tuition_colors` CHANGE `user_id` `editor_id` INT(11) NOT NULL;
ALTER TABLE `kolumbus_transfers_packages` CHANGE `user_id` `editor_id` MEDIUMINT(9) NOT NULL;
ALTER TABLE `kolumbus_reasons` CHANGE `user_id` `editor_id` INT(11) NOT NULL;
ALTER TABLE `kolumbus_periods` CHANGE `user_id` `editor_id` INT(11) NOT NULL;
ALTER TABLE `kolumbus_periods` CHANGE `active` `active` TINYINT(4) NOT NULL DEFAULT '1';