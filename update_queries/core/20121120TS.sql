ALTER TABLE `tc_number_ranges_allocations_receipts` DROP PRIMARY KEY ;

ALTER TABLE `tc_number_ranges_allocations_receipts` ADD `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST ;

ALTER TABLE `tc_number_ranges_allocations_receipts` ADD INDEX ( `invoice_numberrange_id` , `receipt_numberrange_id` ) ;