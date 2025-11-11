CREATE TABLE `ts_number_ranges_allocations_sets_companies` ( `set_id` mediumint(9) NOT NULL,  `company_id` mediumint(9) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `ts_number_ranges_allocations_sets_currencies` (  `set_id` mediumint(9) NOT NULL,  `currency_id` mediumint(9) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
ALTER TABLE `ts_number_ranges_allocations_sets_companies` ADD PRIMARY KEY (`set_id`,`company_id`);
ALTER TABLE `ts_number_ranges_allocations_sets_currencies` ADD PRIMARY KEY (`set_id`,`currency_id`);

