RENAME TABLE `ts_tuition_blocks_days_data` TO `ts_tuition_blocks_daily_units`;
ALTER TABLE `ts_tuition_blocks_daily_units` ADD `id` INT UNSIGNED NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`);