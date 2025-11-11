ALTER TABLE `ts_companies` CHANGE `changed_by` `editor_id` INT(11) NOT NULL DEFAULT '0';
ALTER TABLE `ts_companies_contacts` CHANGE `user_id` `editor_id` INT(11) NOT NULL;