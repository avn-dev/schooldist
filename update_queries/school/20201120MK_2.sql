ALTER TABLE `ts_companies` ADD `book_net_with_gross_and_commission` TINYINT NOT NULL DEFAULT '0';
ALTER TABLE `ts_companies` ADD `financial_year_start` TINYINT NOT NULL DEFAULT '1';
ALTER TABLE `ts_companies` ADD `split_export_by_financial_year` TINYINT NOT NULL DEFAULT '0';