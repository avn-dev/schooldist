ALTER TABLE `kolumbus_tuition_courses` CHANGE `user_id` `editor_id` INT(11) NOT NULL;
ALTER TABLE `kolumbus_tuition_courses` CHANGE `active` `active` TINYINT(4) NOT NULL DEFAULT '1';
ALTER TABLE `ts_tuition_courselanguages` CHANGE `user_id` `editor_id` INT(11) NOT NULL;

