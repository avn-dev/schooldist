ALTER TABLE `kolumbus_insurance_providers` CHANGE `user_id` `editor_id` INT(11) NOT NULL;
ALTER TABLE `kolumbus_insurance_providers` DROP `client_id`;
