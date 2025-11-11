ALTER TABLE `kolumbus_accommodations_categories` CHANGE `user_id` `editor_id` INT(11) NOT NULL DEFAULT '0';
ALTER TABLE `kolumbus_accommodations_roomtypes` CHANGE `user_id` `editor_id` INT(11) NOT NULL DEFAULT '0';
ALTER TABLE `kolumbus_accommodations_roomtypes` CHANGE `active` `active` TINYINT(4) NOT NULL DEFAULT '1';
