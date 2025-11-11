ALTER TABLE `ts_companies` ADD `export_linebreak` VARCHAR(10) NOT NULL DEFAULT 'unix';
ALTER TABLE `ts_companies_colums_export` DROP PRIMARY KEY, ADD PRIMARY KEY (`company_id`, `column`, `position`) USING BTREE;
ALTER TABLE `ts_booking_stacks` ADD `debit_credit` VARCHAR(10) NULL DEFAULT NULL;
ALTER TABLE `ts_booking_stacks` ADD `earliest_commencement` date NULL DEFAULT NULL;
ALTER TABLE `ts_booking_stacks` ADD `account_type` varchar(10) NULL DEFAULT NULL;