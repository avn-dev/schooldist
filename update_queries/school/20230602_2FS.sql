ALTER TABLE `kolumbus_subject` CHANGE `editor_id` `user_id` INT(11) NOT NULL DEFAULT '0'; 
ALTER TABLE `kolumbus_subject` CHANGE `user_id` `editor_id` INT(11) NOT NULL DEFAULT '0'; 
