CREATE TABLE `ts_accounts_transactions` (`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, `account_type` enum('agency','group','contact') NOT NULL, `account_id` int(10) UNSIGNED NOT NULL, `amount` decimal(16,5) NOT NULL, `type` enum('invoice','proforma','payment') NOT NULL, `type_id` INT NOT NULL, `due_date` date DEFAULT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE `ts_accounts_transactions` ADD KEY `due_date` (`due_date`), ADD KEY `created` (`created`), ADD KEY `type` (`type`), ADD KEY `account` (`account_type`,`account_id`);
ALTER TABLE `ts_accounts_transactions` ADD `currency_iso` CHAR(3) NOT NULL DEFAULT 'EUR' AFTER `amount`;
ALTER TABLE `ts_accounts_transactions` ADD INDEX `type_combination` ( `type`, `type_id`);
 