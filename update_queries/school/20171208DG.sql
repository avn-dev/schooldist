
INSERT INTO `system_elements` (`title`, `description`, `element`, `category`, `file`, `version`, `parent`, `image`, `documentation`, `template`, `sql`, `administrable`, `include_backend`, `include_frontend`, `include_mode`, `active`) VALUES ('TsFrontend', '', 'modul', 'Thebing', 'tsfrontend', 0.01, '', '', '', '', '', 0, 0, 0, 0, 1);

ALTER TABLE `customer_db_2`
	ADD `paypal_client_id` VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL AFTER `url_feedback`,
	ADD `paypal_client_secret` VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL AFTER `paypal_client_id`,
	ADD `paypal_webprofile_id` VARCHAR(50) CHARACTER SET ascii COLLATE ascii_bin NOT NULL AFTER `paypal_client_secret`;

ALTER TABLE `kolumbus_payment_method` CHANGE `type` `type` VARCHAR(255) NOT NULL;

ALTER TABLE `kolumbus_forms_schools` ADD `use_paypal` TINYINT NOT NULL DEFAULT 0;
ALTER TABLE `kolumbus_forms_schools` ADD `pay_deposit` TINYINT NOT NULL DEFAULT '0' AFTER `use_paypal`;

ALTER TABLE `ts_inquires_payments_unallocated` ADD `payment_method_id` SMALLINT NOT NULL AFTER `changed`;
ALTER TABLE `ts_inquires_payments_unallocated` CHANGE `transaction` `transaction` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
