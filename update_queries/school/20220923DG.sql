ALTER TABLE `ts_inquires_payments_unallocated`
    ADD `inquiry_id` MEDIUMINT UNSIGNED NULL DEFAULT NULL AFTER `changed`,
	ADD `process_id` MEDIUMINT UNSIGNED NULL DEFAULT NULL COMMENT 'payment_process_id' AFTER `payment_method_id`,
	ADD `status` ENUM('paid','pending') NOT NULL DEFAULT 'paid' AFTER `payment_method_id`,
	ADD `instructions` TEXT NULL DEFAULT NULL AFTER `payment_date`,
    ADD INDEX (`payment_method_id`),
    ADD INDEX (`status`);

DROP TABLE IF EXISTS `ts_payments_transfermate_processes`;
