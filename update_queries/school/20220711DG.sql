ALTER TABLE `ts_accounting_companies` ADD `automatic_account_setting` ENUM('none','all','per_account') NOT NULL DEFAULT 'per_account';
