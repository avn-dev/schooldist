ALTER TABLE `ts_companies` ADD `automatic_stack_export_time` TINYINT(2) NOT NULL AFTER `automatic_stack_export`;
ALTER TABLE `ts_companies` ADD `automatic_release_time` TINYINT(2) NOT NULL AFTER `automatic_release`;
