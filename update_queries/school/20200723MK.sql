ALTER TABLE `ts_companies` CHANGE `export_delimiter` `export_delimiter` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ';';
ALTER TABLE `ts_companies_colums_export` ADD `width` TINYINT(3) UNSIGNED NULL DEFAULT NULL AFTER `column`;
ALTER TABLE `ts_companies_colums_export` ADD `content` VARCHAR(100) NULL DEFAULT NULL AFTER `width`;
ALTER TABLE `ts_companies` ADD `export_enclosure` VARCHAR(20) NOT NULL DEFAULT 'double_quotes';
ALTER TABLE `ts_companies` ADD `export_headlines` TINYINT(1) NOT NULL DEFAULT '1' AFTER `export_enclosure`;