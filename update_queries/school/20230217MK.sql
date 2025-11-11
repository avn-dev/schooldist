RENAME TABLE `kolumbus_agency_schools` TO `ts_agencies_to_schools`;
ALTER TABLE `ts_agencies_to_schools` DROP `changed`;
ALTER TABLE `ts_companies` ADD `schools_limited` TINYINT NOT NULL DEFAULT '0';
