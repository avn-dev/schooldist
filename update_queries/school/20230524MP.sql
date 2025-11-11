ALTER TABLE `kolumbus_contracts` CHANGE `user_id` `editor_id` INT(11) NOT NULL DEFAULT '0';
ALTER TABLE `kolumbus_contracts_versions` CHANGE `user_id` `editor_id` INT(11) NOT NULL DEFAULT '0';
