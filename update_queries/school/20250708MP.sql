CREATE TABLE `ts_groups_additionalservices` ( `group_id` INT(11) NOT NULL , `additionalservice_id` INT(11) NOT NULL , `relation` ENUM('course','accommodation') NULL DEFAULT NULL , `relation_id` INT(11) NULL DEFAULT NULL) ENGINE = InnoDB;
ALTER TABLE `ts_groups_additionalservices` ADD UNIQUE (`group_id`, `additionalservice_id`, `relation`, `relation_id`);
ALTER TABLE `ts_groups_additionalservices` ADD INDEX (`relation_id`);
ALTER TABLE `ts_groups_additionalservices` ADD INDEX (`relation`);