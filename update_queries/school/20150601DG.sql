CREATE TABLE IF NOT EXISTS `ts_transfers_packages_providers_accommodation_providers` (
  `package_id` mediumint(8) unsigned NOT NULL,
  `provider_id` mediumint(8) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `ts_transfers_packages_providers_accommodation_providers`
  ADD PRIMARY KEY (`package_id`,`provider_id`);

ALTER TABLE `ts_transfers_payments_groupings`
    ADD `school_id` MEDIUMINT UNSIGNED NOT NULL AFTER `creator_id`,
    ADD INDEX (`school_id`),
    ADD `provider_type` ENUM('provider','accommodation') NOT NULL DEFAULT 'provider' AFTER `provider_id`;