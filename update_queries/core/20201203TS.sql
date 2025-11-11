ALTER TABLE `tc_vat_rates_allocations_countries` ADD PRIMARY KEY( `allocation_id`, `type`, `country`);

CREATE TABLE IF NOT EXISTS `tc_vat_rates_allocations_address_types` (
  `allocation_id` int(11) NOT NULL,
  `type` char(40) NOT NULL,
  PRIMARY KEY (`allocation_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
