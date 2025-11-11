ALTER TABLE `kolumbus_accommodations_costs_weeks` CHANGE `user_id` `editor_id` INT(11) NOT NULL DEFAULT '0';
ALTER TABLE `kolumbus_accommodations_costs_weeks` CHANGE `active` `active` TINYINT(4) NOT NULL DEFAULT '1';  
