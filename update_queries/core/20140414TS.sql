ALTER TABLE `tc_exchangerates_tables_rates` ADD `factor` DECIMAL( 16, 5 ) NOT NULL AFTER `price` ,
ADD `rate` DECIMAL( 16, 5 ) NOT NULL AFTER `factor` ;