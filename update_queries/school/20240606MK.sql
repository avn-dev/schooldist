CREATE TABLE `ts_tuition_absence_reasons` (`id` INT NOT NULL AUTO_INCREMENT , `changed` TIMESTAMP NOT NULL , `created` TIMESTAMP NOT NULL , `active` TINYINT NOT NULL DEFAULT '1' , `key` VARCHAR(100) NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;
ALTER TABLE `ts_tuition_absence_reasons` ADD `teacher_portal_available` TINYINT NOT NULL DEFAULT '0';
