ALTER TABLE `ts_inquires_payments_unallocated` CHANGE `transaction` `comment` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `ts_inquires_payments_unallocated` ADD `transaction_code` VARCHAR(255) NOT NULL AFTER `file`, ADD INDEX (`transaction_code`);

ALTER TABLE `kolumbus_inquiries_payments` ADD `transaction_code` VARCHAR(255) NOT NULL COMMENT 'Online-Payments' AFTER `comment`;
