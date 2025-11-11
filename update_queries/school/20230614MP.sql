ALTER TABLE `kolumbus_email_templates` CHANGE `user_id` `editor_id` INT(11) NOT NULL;
ALTER TABLE `kolumbus_contract_templates` CHANGE `user_id` `editor_id` INT(11) NOT NULL DEFAULT '0';
ALTER TABLE `tc_frontend_templates` CHANGE `active` `active` TINYINT(1) NOT NULL DEFAULT '1';
