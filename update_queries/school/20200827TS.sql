ALTER TABLE `ts_inquiries_contacts_logins` CHANGE `password` `password` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;
UPDATE `customer_db_config` SET `external_table_accesscode` = 'access_code' WHERE `customer_db_config`.`id` = 77;
UPDATE `customer_db_config` SET `allow_accesscode` = '1' WHERE `customer_db_config`.`id` = 77;
