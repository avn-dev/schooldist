ALTER TABLE `ts_teachers` CHANGE `access_rights` `access_rights` BIT(8) NOT NULL DEFAULT b'10111';
ALTER TABLE `kolumbus_groups` ADD `sales_person_id` INT(11) NOT NULL AFTER `numberrange_id`;