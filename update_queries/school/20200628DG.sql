ALTER TABLE `ts_inquires_payments_unallocated` ADD `additional_info` TEXT NULL DEFAULT NULL;

ALTER TABLE `kolumbus_inquiries_payments` ADD `additional_info` TEXT NULL DEFAULT NULL;

ALTER TABLE `kolumbus_forms` CHANGE `default_language` `default_language` VARCHAR(50) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL;

ALTER TABLE `kolumbus_forms_languages` CHANGE `language` `language` VARCHAR(50) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL;

ALTER TABLE `kolumbus_forms_schools` CHANGE `payment_provider` `payment_provider` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT 'V2', CHANGE `payment_method` `payment_method` SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'V2', CHANGE `pay_deposit` `pay_deposit` TINYINT(4) NOT NULL DEFAULT '0' COMMENT 'V2';

UPDATE `kolumbus_forms_schools` SET `payment_provider` = 'paypal' WHERE `payment_provider` = 'TsFrontend\\Handler\\Payment\\PayPal';
UPDATE `kolumbus_forms_schools` SET `payment_provider` = 'stripe' WHERE `payment_provider` = 'TsFrontend\\Handler\\Payment\\Stripe';
UPDATE `kolumbus_forms_schools` SET `payment_provider` = 'redsys' WHERE `payment_provider` = 'TsFrontend\\Handler\\Payment\\Redsys';
UPDATE `kolumbus_forms_schools` SET `payment_provider` = '' WHERE `payment_provider` = 'TsFrontend\\Handler\\Payment\\CopyAndPay';
