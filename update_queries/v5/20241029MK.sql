CREATE TABLE `cms_dynamic_routings_contents` ( `dynamic_route_id` INT NOT NULL , `key` VARCHAR(255) NOT NULL , `field` VARCHAR(255) NOT NULL , `value` MEDIUMTEXT NOT NULL , `changed` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , UNIQUE (`dynamic_route_id`, `key`, `field`)) ENGINE = InnoDB;
ALTER TABLE `cms_dynamic_routings_contents` ADD `language` CHAR(2) NOT NULL AFTER `field`;
ALTER TABLE `cms_dynamic_routings_contents` DROP INDEX `field`, ADD UNIQUE `field` (`field`, `key`, `dynamic_route_id`, `language`) USING BTREE;
