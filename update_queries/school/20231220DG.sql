UPDATE `ts_companies` SET `interface` = 'sage50' WHERE `interface` = 'sage';
ALTER TABLE `ts_booking_stacks` ADD `amount_if_claim` DECIMAL(15,5) NOT NULL AFTER `agency_id`, ADD `amount_if_position` DECIMAL(15,5) NOT NULL AFTER `amount_if_claim`, ADD `amount_tax` DECIMAL(15,5) NOT NULL AFTER `amount_if_position`;
